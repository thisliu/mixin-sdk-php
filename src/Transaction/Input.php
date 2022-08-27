<?php

namespace Thisliu\Mixin\Transaction;

use Thisliu\Mixin\Traits\TransactionHelper;

class Input
{
    use TransactionHelper;

    public function __construct(public string $hash, public int $index)
    {
    }

    public function encode(): string
    {
        $ret = $this->encodeMapLen(5);  // 一共有5个字段
        $ret .= $this->encodeString('Hash').$this->encodeBytes(hex2bin($this->hash));  // 序列化Hash, 先转为32长度的bytes
        $ret .= $this->encodeString('Index').$this->encodeInt($this->index);
        $ret .= $this->encodeString('Genesis').$this->encodeNull();
        $ret .= $this->encodeString('Deposit').$this->encodeNull();
        $ret .= $this->encodeString('Mint').$this->encodeNull();

        return $ret;
    }
}
