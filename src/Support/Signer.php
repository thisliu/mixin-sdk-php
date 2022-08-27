<?php

namespace Thisliu\Mixin\Support;

use Base64Url\Base64Url;
use Firebase\JWT\JWT;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Easy\Build;
use phpseclib\Crypt\RSA;
use Ramsey\Uuid\Uuid;
use Thisliu\Mixin\Config;
use Thisliu\Mixin\Exceptions\InvalidArgumentException;
use Thisliu\Mixin\Exceptions\LoadPrivateKeyException;
use Thisliu\Mixin\Transaction\Input;
use Thisliu\Mixin\Transaction\Output;
use Thisliu\Mixin\Transaction\Transaction;

class Signer
{
    const TYPE_PIN = 'pin';
    const TYPE_PRIVATE = 'private';
    const TYPE_OAUTH = 'oauth';

    /**
     * @throws \Thisliu\Mixin\Exceptions\LoadPrivateKeyException
     * @throws \SodiumException
     * @throws \Thisliu\Mixin\Exceptions\InvalidArgumentException
     */
    public static function build(string $type, Config $config): string
    {
        return match ($type) {
            // 访问私有 API 的 JWT 签名
            self::TYPE_PRIVATE => self::buildPrivateJwt($config),
            // OAuth 用户访问 API 的 JWT 签名
            self::TYPE_OAUTH => self::buildOauthJwt($config),
            // 转帐，提现，创建地址需要的 pin 签名
            self::TYPE_PIN => self::buildPin($config),
            default => throw new InvalidArgumentException('无效的签名类型！')
        };
    }

    // $method, $uri, $body, $expire = 200, $scope = 'FULL'
    public static function buildPrivateJwt(Config $config): string
    {
        $token = [
            "uid" => $config->get('client_id'),
            "sid" => $config->get('session_id'),
            "iat" => time(),
            "exp" => time() + $expire,
            "jti" => Uuid::uuid4()->toString(),
            "sig" => bin2hex(hash('sha256', $method.$uri.$body, true)),
            'scp' => $scope,
        ];

        $algorithm = self::getKeyAlgorithm($config->get('private_key'));

        if ($algorithm === 'Ed25519') {
            $keyRaw = Base64Url::decode($config->get('private_key'));

            $jwk = JWKFactory::createFromValues(
                [
                    "kty" => "OKP",
                    "crv" => "Ed25519",
                    // seed
                    "d"   => Base64Url::encode(substr($keyRaw, 0, 32)),
                    // public
                    "x"   => Base64Url::encode(substr($keyRaw, 32)),
                ]
            );

            $jws = Build::jws();

            foreach ($token as $key => $value) {
                $jws = $jws->claim($key, $value);
            }

            // ed25519
            $jwt = (string)$jws->alg('EdDSA')->sign($jwk);
        } else {
            $jwt = JWT::encode($token, $config->get('private_key'), $algorithm);
        }

        return $jwt;
    }

    /**
     * @throws \SodiumException
     * @throws \Thisliu\Mixin\Exceptions\LoadPrivateKeyException
     * // $pin
     */
    public static function buildPin(Config $config, array $options = []): string
    {
        $privateKey = $config->get('private_key');
        $pinToken   = $config->get('pin_token');
        $sessionId  = $config->get('session_id');

        $iterator = ($options['iterator'] ?? []);
        $pin = ($options['pin'] ?? '');

        $iterator = empty($iterator)
            ? microtime(true) * 100000
            : array_shift($iterator);

        $algorithm = self::getKeyAlgorithm($privateKey);

        if ($algorithm === 'Ed25519') {
            $keyRaw   = Base64Url::decode($privateKey);
            $public    = Base64Url::decode($pinToken);
            $curve     = sodium_crypto_sign_ed25519_sk_to_curve25519($keyRaw);
            $keyBytes = sodium_crypto_scalarmult($curve, $public);
        } else {
            //载入私钥
            $rsa = new RSA();

            if (!$rsa->loadKey($privateKey)) {
                throw new LoadPrivateKeyException('local private key error.');
            }

            //使用 RSAES-OAEP + MGF1-SHA256 的方式，似乎只有这个 Phpseclib/Phpseclib 库来实现...
            $rsa->setHash("sha256");
            $rsa->setMGFHash("sha256");
            $keyBytes = $rsa->_rsaes_oaep_decrypt(base64_decode($pinToken), $sessionId);
        }

        //使用 私钥 加密 pin
        $pin_bytes = (string) $pin.pack("P", time()).pack("P", $iterator);

        return self::encryptOpenssl($pin_bytes, $keyBytes);
    }

    public static function buildOauthJwt(Config $config): string
    {
        return '';
    }

    private static function getKeyAlgorithm(string $key): string
    {
        return str_contains($key, 'PRIVATE KEY') ? 'RS512' : 'Ed25519';
    }

    public static function generateSSLKey(): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        // 获取 private Key
        openssl_pkey_export($key, $privateKey);

        // 获取 public Key
        $publicKey = openssl_pkey_get_details($key)['key'];

        // 生成 session_secret
        $sessionSecret = str_replace(["-----BEGIN PUBLIC KEY-----\n", "-----END PUBLIC KEY-----", "\n"], '', $publicKey);

        return [$privateKey, $publicKey, $sessionSecret];
    }

    public static function generateEdDSAKey(): array
    {
        $key = JWKFactory::createOKPKey('Ed25519');
        $privateKey = Base64Url::encode(Base64Url::decode($key->get('d')).Base64Url::decode($key->get('x')));
        $pubKey = $key->get('x');

        return [$privateKey, $pubKey, $pubKey];
    }

    public static function encryptOpenssl($msg, $key): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));

        $encryptedMessage = openssl_encrypt($msg, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv.$encryptedMessage);
    }

    public static function decryptOpenssl($payload, $key): string
    {
        $payload  = base64_decode($payload);

        $iv   = substr($payload, 0, 16);
        $data = substr($payload, 16);

        return openssl_decrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

    public static function uniqueConversationId(string $userId, string $recipientId): string
    {
        [$minId, $maxId] = [$userId, $recipientId];

        if (strcmp($userId, $recipientId) > 0) {
            [$maxId, $minId] = [$userId, $recipientId];
        }

        $sum = md5($minId.$maxId);
        $replacement = dechex((hexdec($sum[12].$sum[13]) & 0x0f) | 0x30);
        $sum = substr_replace($sum, $replacement, 12, 2);

        $replacement = dechex((hexdec($sum[16].$sum[17]) & 0x3f) | 0x80);
        $sum = substr_replace($sum, $replacement, 16, 2);

        return Uuid::fromString($sum)->toString();
    }

    /**
     * @throws \Thisliu\Mixin\Exceptions\InvalidArgumentException
     */
    public static function buildRaw(string $assetId, array $inputs, array $outputs, string $memo, int $version = 0x01): string
    {
        $version = match (true) {
            empty($assetId) => throw new InvalidArgumentException("func 'BuildRaw' need assetUuid, but your assetUuid param is empty!"),
            empty($inputs) => throw new InvalidArgumentException("func 'BuildRaw' need \$inputs not empty!"),
            empty($outputs) => throw new InvalidArgumentException("func 'BuildRaw' need \$outputs not empty!"),
            default => $version ?: 0x01,
        };

        // 检查 $inputs 和 $outputs 的类型
        foreach ($inputs as $input) {
            if (!$input instanceof Input) {
                throw new InvalidArgumentException("\$inputs must use 'TransactionInput' object");
            }
        }

        foreach ($outputs as $output) {
            if (!$output instanceof Output) {
                throw new InvalidArgumentException("\$outputs must use 'TransactionOutput' object");
            }
        }

        return Transaction::build([
            'version' => $version,
            'asset'   => $assetId,
            'inputs'  => $inputs,
            'outputs' => $outputs,
            'extra'   => bin2hex($memo),
        ]);
    }
}
