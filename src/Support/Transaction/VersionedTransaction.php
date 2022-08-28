<?php

namespace Thisliu\Mixin\Support\Transaction;

class VersionedTransaction extends SignedTransaction
{
    public $badGenesis;

    public function __construct(public SignedTransaction $signedTransaction)
    {
        parent::__construct($signedTransaction);

        $this->version = $signedTransaction->version;
        $this->asset = $signedTransaction->asset;
        $this->inputs = $signedTransaction->inputs;
        $this->outputs = $signedTransaction->outputs;
        $this->extra = $signedTransaction->extra;
        $this->signatures = $signedTransaction->signatures;
    }

    public function Marshal(): string
    {
        return match ($this->version) {
            0 => $this->CompressMsgpackMarshalPanic($this->badGenesis),
            0x01 => $this->CompressMsgpackMarshalPanic($this->signedTransaction),
            default => ''
        };
    }

    public function CompressMsgpackMarshalPanic($transaction): string
    {
        if ($transaction instanceof SignedTransaction) {
            $payload = $transaction->encode();
        }

        return $payload ?? '';
    }
}
