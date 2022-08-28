<?php

namespace Thisliu\Mixin\Support\Transaction;

use Thisliu\Mixin\Traits\TransactionHelper;

class Input
{
    use TransactionHelper;

    public function __construct(public string $hash, public int $index)
    {
    }

    public function encode(): string
    {
        $ret = $this->encodeMapLen(5);
        // 序列化 Hash, 先转为 32 长度的 bytes
        $ret .= $this->encodeString('Hash').$this->encodeBytes(hex2bin($this->hash));
        $ret .= $this->encodeString('Index').$this->encodeInt($this->index);
        $ret .= $this->encodeString('Genesis').$this->encodeNull();
        $ret .= $this->encodeString('Deposit').$this->encodeNull();
        $ret .= $this->encodeString('Mint').$this->encodeNull();

        return $ret;
    }
}
