<?php

namespace Thisliu\Mixin\Transaction;

use Thisliu\Mixin\Traits\TransactionHelper;

class Output
{
    use TransactionHelper;

    public function __construct(
        public BigInteger $amount,
        public array $keys,
        public string $script,
        public string $mask,
        public int $type = 0
    ){
    }

    /**
     * @throws \Thisliu\Mixin\Exceptions\TransactionException
     */
    public function encode(): string
    {
        $ret = $this->encodeMapLen(5);  // 一共有5个字段, 对应的Withdrawal 被msgpack标记为不传递
        $ret .= $this->encodeString('Type').$this->encodeInt($this->type);
        $ret .= $this->encodeString('Amount').$this->encodeExt($this->amount);
        $ret .= $this->encodeString('Keys').$this->encodeArray($this->keys);
        $ret .= $this->encodeString('Script').$this->encodeBytes(hex2bin($this->script));
        $ret .= $this->encodeString('Mask').$this->encodeBytes(hex2bin($this->mask));

        return $ret;
    }
}
