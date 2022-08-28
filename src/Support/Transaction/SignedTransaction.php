<?php

namespace Thisliu\Mixin\Support\Transaction;

use Thisliu\Mixin\Traits\TransactionHelper;

class SignedTransaction extends Transaction
{
    use TransactionHelper;

    public $signatures;

    public function __construct(public Transaction $transaction)
    {
        $this->version     = $transaction->version;
        $this->asset       = $transaction->asset;
        $this->inputs      = $transaction->inputs;
        $this->outputs     = $transaction->outputs;
        $this->extra       = $transaction->extra;
    }

    public function encode(): string
    {
        // 按字段的顺序
        $ret = $this->encodeMapLen(6);
        $ret .= $this->encodeString('Version').$this->encodeInt($this->version);  // 序列化Version字段
        $ret .= $this->encodeString('Asset').$this->encodeBytes(hex2bin($this->asset));  // 序列化Asset, 先转为32长度的bytes
        $ret .= $this->encodeString('Inputs').$this->encodeArray($this->inputs);
        $ret .= $this->encodeString('Outputs').$this->encodeArray($this->outputs);
        $ret .= $this->encodeString('Extra').$this->encodeBytes($this->extra);  // Extra 已经被序列为bytes

        if ($this->signatures == null) {
            $ret .= $this->encodeString('Signatures').$this->encodeNull();
        }

        return $ret;
    }
}
