<?php

namespace Thisliu\Mixin\Traits;

use Thisliu\Mixin\Exceptions\TransactionException;
use Thisliu\Mixin\Support\Transaction\BigInteger;
use Thisliu\Mixin\Support\Transaction\Input;
use Thisliu\Mixin\Support\Transaction\Output;

trait TransactionHelper
{
    function prettyString(array $obj): string
    {
        $ret = '';

        foreach ($obj as $v) {
            $ret .= (is_array($v) ? '['.$this->prettyString($v).'] ' : $v.' ');
        }

        return $ret;
    }

    function prettyPrint(array $obj): void
    {
        echo $this->prettyString($obj)."\n";
    }

    function echoBin(string $a): array
    {
        $ret = [];

        $len = strlen($a);

        for ($i = 0; $i < $len; $i++) {
            $ret[] = ord($a[$i]);
        }

        echo implode(' ', $ret)."\n";

        return $ret;
    }

    function encodeNull(): string
    {
        return pack('C', 0xc0);
    }

    function encodeMapLen(int $data): string
    {
        if ($data < 16) {
            return pack('C', 0x80 | $data);
        }

        if ($data < 65536) {
            return pack('C2n', 0xde, $data >> 8, $data);
        }

        return '';
    }

    function encodeString(string $data): string
    {
        $len = strlen($data);

        if ($len < 32) {
            return pack('C', 0xa0 | $len).$data;
        }

        if ($len < 256) {
            return pack('C', 0xd9);
        }

        return '';
    }

    function encodeInt(int $data): string
    {
        if ($data < 32) {
            return pack('C', $data);
        }

        return '';
    }

    function encodeBytes(string $data): string
    {
        $len = strlen($data);

        if ($len < 256) {
            return pack('C2', 0xc4, $len).$data;
        }

        return '';
    }

    function encodeArray(array $data): string
    {
        $ret = '';

        $len = count($data);

        if ($len < 16) {
            $ret .= pack('C', 0x90 | $len);
        }

        for ($i = 0; $i < $len; $i++) {
            $ret .= $this->encodeObject($data[$i]);
        }

        return $ret;
    }

    function encodeObject($data): string
    {
        return match (true) {
            $data instanceof Input || $data instanceof Output => $data->encode(),
            is_string($data) => $this->encodeBytes(hex2bin($data)),
            default => '',
        };
    }

    /**
     * @throws \Thisliu\Mixin\Exceptions\TransactionException
     */
    function encodeExt($obj): string
    {
        if (!$obj instanceof BigInteger) {
            throw new TransactionException('error type');
        }

        $data = $obj->encode();

        $typeId = 0;  // go 代码中写死的
        $len = strlen($data);

        if (\in_array($len, [1, 2, 4, 8, 16])) {
            $format = match ($len) {
                1 => 0xd4,
                2 => 0xd5,
                4 => 0xd6,
                8 => 0xd7,
                16 => 0xd8,
            };

            return pack('C2', $format, $typeId) . $data;
        }

        if ($len < 256) {
            return pack('C3', 0xc7, $len, $typeId).$data;
        }

        if ($len < 65536) {
            return pack('C2nC', 0xc8, $len >> 8, $len, $typeId).$data;  // 没有测试
        }

        return pack('C2nNJC', 0xc9, $len >> 24, $len >> 16, $len >> 8, $len, $typeId).$data;  // 没有测试
    }
}