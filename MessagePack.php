<?php

class MessagePack
{
    const DATATYPE_BINARY = 2;
    const DATATYPE_HEXADECIMAL = 16;

    const BIN_POWER_4 = 16;
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
        $arrayData = is_array($data) ? $data : json_decode($data, true);

        if (!is_array($arrayData)) {
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

        $msgPackData .= strtoupper($this->getMapPrefix(count($data)));
        foreach ($data as $key => $value) {
            $msgPackData .= $this->parseTypes($key);
            $msgPackData .= $this->parseTypes($value);
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
                return strtoupper($this->packNull($data));
                break;

            case 'boolean':
                return strtoupper($this->packBoolean($data));
                break;

            case 'integer':
                return strtoupper($this->packInteger($data));
                break;

            case 'double':
                return strtoupper($this->packFloat($data));
                break;

            case 'string':
                return strtoupper($this->getStrPrefix($data)) . $data;
                break;

            case 'array':
                return $this->parseArray($data);
                break;

            default:
                error_log('undefined case: ' . gettype($data), 0);
                break;
        }
    }

    /**
     *  各式型別轉換成 msgpack 資料結構 回傳16進位表示
     **/
    public function packNull($data)
    {
        if (!is_null($data)) {
            return false;
        }

        $formatNil = "0b11000000";

        return base_convert($formatNil,
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    public function packBoolean($data)
    {
        if (!is_bool($data)) {
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
        if (!is_integer($data)) {
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

    public function packFloat($data)
    {
        // TODO
    }

    public function getStrPrefix($data)
    {
        if (!is_string($data)) {
            return false;
        }

        // 中文字串的情境在研究一下，不確定能不能直接換 mb_strlen
        if (strlen($data) < self::BIN_POWER_5) {
            return $this->formatFixstr($data);
        }

        if (strlen($data) < self::BIN_POWER_8) {
            return $this->formatStr8($data);
        }

        if (strlen($data) < self::BIN_POWER_16) {
            return $this->formatStr16($data);
        }

        if (strlen($data) < self::BIN_POWER_32) {
            return $this->formatStr32($data);
        }
    }

    public function getMapPrefix($length)
    {
        if (!is_integer($length)) {
            return false;
        }

        if ($length < self::BIN_POWER_4) {
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
        // MEMO 進來的數字小於16只會回傳一位數
        $prefix = ($data < self::BIN_POWER_4) ? "0" : "";
        return $prefix . base_convert(sprintf("%08b", $data),
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
        $prefix = "d0";

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
        $prefix = "d1";

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
        $prefix = "d2";

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
        $prefix = "d3";

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
        $prefix = "d9";

        return $prefix . base_convert(sprintf("%08b", strlen($data)),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatStr16($data)
    {
        $prefix = "da";

        return $prefix . base_convert(sprintf("%016b", strlen($data)),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatStr32($data)
    {
        $prefix = "db";

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
        $prefix = "de";

        return $prefix . base_convert(sprintf("%016b", $length),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }

    private function formatMap32($length)
    {
        $prefix = "df";

        return $prefix . base_convert(sprintf("%032b", $length),
            self::DATATYPE_BINARY, self::DATATYPE_HEXADECIMAL);
    }
}
