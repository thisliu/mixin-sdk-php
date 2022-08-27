<?php

namespace Thisliu\Mixin\Transaction;

use Thisliu\Mixin\Exceptions\InvalidArgumentException;
use Thisliu\Mixin\Exceptions\VersionException;

class Transaction
{
    public int $version = 0x01;

    public string $asset;

    public array $inputs = [];

    public array $outputs = [];

    public string $extra;

    public static function NewTransaction(string $asset): self
    {
        $ret = new self();
        $ret->asset = $asset;

        return $ret;
    }

    public function AddInput(Input $input): void
    {
        $this->inputs[] = $input;
    }

    /**
     * @throws \Thisliu\Mixin\Exceptions\TransactionException
     */
    public function AddOutput(Output $output): void
    {
        if (is_string($output->amount) || is_int($output->amount)) {
            $output->amount = new BigInteger((string)($output->amount));
        }

        $this->outputs[] = $output;
    }

    /**
     * @throws \Thisliu\Mixin\Exceptions\VersionException
     */
    public function AsLatestVersion(): VersionedTransaction
    {
        if ($this->version != 0x01) {
            throw new VersionException("version: {$this->version} is not support");
        }

        return new VersionedTransaction(new SignedTransaction($this));
    }

    /**
     * @throws \Thisliu\Mixin\Exceptions\InvalidArgumentException
     * @throws \Exception
     */
    public static function build(array $input_object): string
    {
        $tx = Transaction::NewTransaction($input_object['asset']);

        // fill up inputObject
        foreach ($input_object['inputs'] as $v) {
            if (!empty($input->genesis)) {
                throw new InvalidArgumentException("invalid input with Genesis, it's not needed in this function");
            }

            if (!empty($input->deposit)) {
                throw new InvalidArgumentException("invalid input with Deposit, it's not needed in this function");
            }

            if (!empty($input->mint)) {
                throw new InvalidArgumentException("invalid input with Mint, it's not needed in this function");
            }

            $tx->AddInput($v);
        }

        // fill up outputObject
        foreach ($input_object['outputs'] as $v) {
            if (strlen($v->mask) > 0) {
                $tx->AddOutput($v);
            }
        }

        // 16进制解码为bytes
        $extra = hex2bin($input_object['extra']);
        $tx->extra = $extra;

        $signed = $tx->AsLatestVersion();

        return bin2hex($signed->Marshal());
    }
}
