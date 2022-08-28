<?php

namespace Thisliu\Mixin\Support\Transaction;

use Thisliu\Mixin\Exceptions\TransactionException;

class BigInteger
{
    // amd64 平台
    // const max_base = 10 + ('z' - 'a' + 1) + ('Z' - 'A' + 1)
    // const max_base_small = 10 + ('z' - 'a' + 1);
    public const MAX_BASE = 62;
    public const SMALL_BASE = 36;

    public string $abs;
    public bool $neg = false;
    public string $natString;
    public array $nat = [];

    /**
     * @throws \Thisliu\Mixin\Exceptions\TransactionException
     */
    public function __construct(string $i)
    {
        $this->abs = $i;

        if ($i[0] == '-') {  // 如果是负数
            $this->abs = substr($i, 1, strlen($i) - 1);
            $this->neg = true;
        }

        $tmp = $this->abs;

        // go的库中没有精确到8位以后, 所以这里需要手动处理一下
        if (strstr($tmp, '.') > 0) {  // 说明存在小数
            // $tmp = strval(round(floatval($tmp), 8));
            $tmp = number_format(floatval($tmp), 8, '.', '');
        }

        $this->natString = bcmul($tmp, '100000000');
        $this->parseNat();
    }

    /**
     * @throws \Thisliu\Mixin\Exceptions\TransactionException
     */
    public function parseNat(): void
    {
        // If fracOk is set, a period followed by a fractional part is permitted.
        // The result value is computed as if there were no period present; and the count value is used to determine the fractional part.
        $fracOk = false;

        // 如果 fracOk 为true, 则 $b 必须为 0, 2, 8, 10, 16
        // 如果 $b 为0, 则根据abs字符串的前缀来判断, 如 0b 为 2
        $b = 10;  // 默认10进制

        $count = 0;

        $b1 = strval($b);
        [$bn, $n] = self::maxPow($b);  // 获取小于系统位数的最大位
        $di = '0';  // 0 <= di < b**i < bn
        $i = 0; // 0 <= i < n

        foreach (str_split($this->natString) as $ch) {
            if ($ch === '.' && $fracOk) {
                $fracOk = false;
                continue;
            }

            $d1 = match (true) {
                '0' <= $ch && $ch <= '9' => $ch - '0',
                'a' <= $ch && $ch <= 'z' => $ch - 'a' + 10,
                'A' <= $ch && $ch <= 'Z' && $b <= self::SMALL_BASE => $ch - 'A' + 10,
                'A' <= $ch && $ch <= 'Z' => $ch - 'A' + self::MAX_BASE,
                default => self::MAX_BASE + 1,
            };

            if ($d1 >= $b1) {
                // ch 不是任何一个数字, 说明是无效的abs
                break;
            }

            $count++;

            $di = bcadd(bcmul($di, $b1), strval($d1));
            $i++;

            if ($i == $n) {
                $this->nat = $this->mulAddWW($this->nat, $bn, $di);
                $di = '0';
                $i = 0;
            }
        }

        if ($count == 0) {
            throw new TransactionException('number has no digits');
        }

        if ($i > 0) {
            // prettyPrint(["-----", $this->nat, $b1, $i, $di]);
            $this->nat = $this->mulAddWW($this->nat, bcpow($b1, strval($i)), $di);
        }
    }

    // 求出基于当前的进制, 在小于当前系统最大位数时的最大次方
    public static function maxPow(int $base): array
    {
        $p = strval($base);
        $n = 1;

        for ($max = (bcpow('2', strval(PHP_INT_SIZE * 8)) - 1) / $base; $p <= $max;) {
            $p = bcmul($p, strval($base));
            $n++;
        }

        return [$p, $n];
    }

    public function encode(): string
    {
        // 转成 big int 的二进制, 大端序
        $ret = '';
        foreach ($this->nat as $nat) {
            $ret = $this->encodeNat($nat).$ret;
        }

        return $ret;
    }

    private function mulAddWW(array $x, string $y, string $r): array
    {
        $m = count($x);

        if ($m == 0 || $y == 0) {
            return [$r];
        }

        $ret = [];

        // print_r("========\n");
        // prettyPrint([$ret, $x, $y, $r, $m]);
        $ret[$m] = $this->mulAddVWW($ret, $x, $y, $r);

        // prettyPrint($ret);
        return $ret;
    }

    // From math/big/arith_arm64.s mulAddVWW
    // y: 进制
    // r: 偏移
    // x = [a1, a2 ...]
    // c = a1*2^(64*0)*y + a2*2^(64*1)*y + ... an*2^(64*(n-1))*y + r
    private function mulAddVWW(array &$z, array $x, string $y, string $r): string
    {
        foreach ($x as $key => $v) {
            $c       = bcadd(bcmul($v, $y), $r);
            $tmp     = bcdiv($c, bcpow('2', strval(PHP_INT_SIZE * 8)));
            $r       = bcadd($r, $tmp);
            $z[$key] = bcsub($c, bcmul($tmp, bcpow('2', strval(PHP_INT_SIZE * 8))));
        }

        return $tmp ?? '0';
    }

    private function encodeNat(string $nat): string
    {
        $len   = strlen($nat);
        $index = $len * PHP_INT_SIZE;
        $buf   = [];

        for ($j = 0; $j < PHP_INT_SIZE; $j++) {
            $index--;
            // 取最低8位,高位舍去,转为数字, go语法
            // $buf[$index] = $nat - ($nat >> 8) * 256;
            // $nat >>= 8;
            $tmp         = bcdiv($nat, '256');
            $buf[$index] = bcsub($nat, bcmul($tmp, '256'));
            $nat         = $tmp;
        }

        while ($index < ($len * PHP_INT_SIZE) && $buf[$index] == 0) {
            $index++;
        }

        // 取起始位到最高位的数组
        $maxKey = $len * PHP_INT_SIZE - 1;
        $ret     = [];

        if ($maxKey >= $index) {
            foreach (range($index, $maxKey) as $v) {
                $ret[] = $buf[$v];
            }
        }

        return pack('C'.($maxKey - $index + 1), ...$ret);
    }
}
