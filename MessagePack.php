<?php

class MessagePack
{
    const DATATYPE_BINARY = 2;
    const DATATYPE_HEXADECIMAL = 16;

    const BIN_POWER_5 = 32;
    const BIN_POWER_8 = 256;
    const BIN_POWER_16 = 65536;
    const BIN_POWER_32 = 4294967296;

    public function __construct()
    {}

    // json to msgpack
    public function encode($data)
    {
        // decode to array
        $arrayData = $this->isArray($data) ? $data : json_decode($data, true);

        if (!$this->isArray($arrayData)) {
            error_log('invalid data type.', 0);
            return false;
        }

        if (empty($arrayData)) {
            return $this->packNull(null);
        }

        return $this->parseArray($arrayData);
    }

    // msgpack to json
    public function decode($data)
    {}

    private function parseArray(&$data)
    {
        $msgPackData = "";

        $msgPackData .= $this->getMapPrefix(count($data));
        foreach ($data as $key => $value) {
            $msgPackData .= parseTypes($key);
            $msgPackData .= parseTypes($value);
        }

        return $msgPackData;
    }

    /**
     *  檢查資料格式
     **/

    private function parseTypes($data)
    {
        switch (gettype($data)) {
            case 'NULL':
                return $this->packNull($data);
                break;

            case 'boolean':
                return $this->packBoolean($data);
                break;

            case 'integer':
                return $this->packInteger($data);
                break;

            case 'string':
                return $this->packString($data);
                break;

            case 'array':
                return $this->parseArray($data);
                break;

            default:
                error_log('undefined case: ' . gettype($data), 0);
                break;
        }
    }

    private function isNull($data): bool
    {
        return is_null($data);
    }

    private function isBoolean($data): bool
    {
        return is_bool($data);
    }

    private function isInteger($data): bool
    {
        return is_int($data);
    }

    private function isString($data): bool
    {
        return is_string($data);
    }

    private function isArray($data): bool
    {
        return is_array($data);
    }

    /**
     *  各式型別轉換成 msgpack 資料結構 回傳16進位表示
     **/
    public function packNull($data)
    {
        if (!$this->isNull($data)) {
            return false;
        }

        $formatNil = "0b11000000";

        return base_convert($formatNil,
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    public function packBoolean($data)
    {
        if (!$this->isBoolean($data)) {
            return false;
        }

        $formatFalse = "0b11000010";
        $formatTrue = "0b11000011";

        $formatBool = ($data) ? $formatTrue : $formatFalse;
        return base_convert($formatBool,
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    public function packInteger($data)
    {
        if (!$this->isInteger($data)) {
            return false;
        }

        // 檢查數字大小來判斷要組哪一種
        if (0 <= $data && $data < self::BIN_POWER_8 / 2) {
            return $this->formatFixintPositive($data);
        }

        if (-self::BIN_POWER_5 <= $data && $data < 0) {
            return $this->formatFixintNegative($data);
        }

        if (-(self::BIN_POWER_8 / 2) <= $data && $data < self::BIN_POWER_8 / 2) {
            return $this->formatInt8($data);
        }

        if (-(self::BIN_POWER_16 / 2) <= $data && $data < self::BIN_POWER_16 / 2) {
            return $this->formatInt16($data);
        }

        if (-(self::BIN_POWER_32 / 2) <= $data && $data < self::BIN_POWER_32 / 2) {
            return $this->formatInt32($data);
        }

        if (PHP_INT_MIN <= $data && $data <= PHP_INT_MAX) {
            return $this->formatInt64($data);
        }
    }

    public function packString($data)
    {
        if (!$this->isString($data)) {
            return false;
        }

        // 中文字串的情境在研究一下，不確定能不能直接換 mb_strlen
        if (strlen($data) < self::BIN_POWER_5) {
            $length = $this->formatFixstr($data);
        }

        if (strlen($data) < self::BIN_POWER_8) {
            $length = $this->formatStr8($data);
        }

        if (strlen($data) < self::BIN_POWER_16) {
            $length = $this->formatStr16($data);
        }

        if (strlen($data) < self::BIN_POWER_32) {
            $length = $this->formatStr32($data);
        }

        // 字面呈現可以長得跟官網一樣，但實際傳送要怎樣才不會全部被當成字串??
        return $length . $data;
    }

    public function getMapPrefix($length)
    {
        if ($length < 16) {
            return $this->formatFixmap($length);
        }

        if ($length < self::BIN_POWER_16) {
            return $this->formatMap16($length);
        }

        if ($length < self::BIN_POWER_32) {
            return $this->formatMap32($length);
        }
    }

    /**
     *  messagepack formats integer
     **/
    private function formatFixintPositive($data)
    {
        return base_convert(sprintf("%08b", $data),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatFixintNegative($data)
    {
        $neg2pos = self::BIN_POWER_5 - abs($data);
        return base_convert("111" . sprintf("%05b", $neg2pos),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatInt8($data)
    {
        $prefix = "0xd0";

        if (0 <= $data) {
            return $prefix . base_convert(sprintf("%08b", $data),
                self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
        } else {
            $neg2pos = self::BIN_POWER_8 - abs($data);
            return $prefix . base_convert(sprintf("%08b", $neg2pos),
                self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
        }
    }

    private function formatInt16($data)
    {
        $prefix = "0xd1";

        if (0 <= $data) {
            return $prefix . base_convert(sprintf("%016b", $data),
                self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
        } else {
            $neg2pos = self::BIN_POWER_16 - abs($data);
            return $prefix . base_convert(sprintf("%016b", $neg2pos),
                self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
        }
    }

    private function formatInt32($data)
    {
        $prefix = "0xd2";

        if (0 <= $data) {
            return $prefix . base_convert(sprintf("%032b", $data),
                self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
        } else {
            $neg2pos = self::BIN_POWER_32 - abs($data);
            return $prefix . base_convert(sprintf("%032b", $neg2pos),
                self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
        }
    }

    private function formatInt64($data)
    {
        $prefix = "0xd3";

        return $prefix . base_convert(sprintf("%064b", $data),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    /**
     *  messagepack formats string
     **/
    private function formatFixstr($data)
    {
        return base_convert("101" . sprintf("%05b", strlen($data)),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatStr8($data)
    {
        $prefix = "0xd9";

        return $prefix . base_convert(sprintf("%08b", strlen($data)),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatStr16($data)
    {
        $prefix = "0xda";

        return $prefix . base_convert(sprintf("%016b", strlen($data)),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatStr32($data)
    {
        $prefix = "0xdb";

        return $prefix . base_convert(sprintf("%032b", strlen($data)),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    /**
     *  messagepack formats map
     **/
    private function formatFixmap($length)
    {
        return base_convert("1000" . sprintf("%04b", $length),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatMap16($length)
    {
        $prefix = "0xde";

        return $prefix . base_convert(sprintf("%016b", $length),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatMap32($length)
    {
        $prefix = "0xdf";

        return $prefix . base_convert(sprintf("%032b", $length),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }
}
