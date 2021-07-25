<?php

class MessagePack
{

    public function __construct()
    {}

    // json to msgpack
    public function encode($data)
    {
        if (!$this->valid($data)) {
            error_log('not json type data.', 0);
            return false;
        }
        // decode to array
        $arrayData = is_array($data) ? $data : json_decode($data, true);
        // TODO
    }

    // msgpack to json
    public function decode($data)
    {}

    /**
     *  檢查資料格式
     **/
    private function valid($data)
    {
        // 看能不能解析成陣列
        $jsonData = json_decode($data, true);
        if ($this->isNull($jsonData) ||
            $this->isBoolean($jsonData) ||
            $this->isInteger($jsonData)) {
            return false;
        }

        return true;
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

    /**
     *  轉換資料結構
     **/
    public function packNull($data)
    {}
    public function packInteger($data)
    {}
    public function packBoolean($data)
    {}
    public function packString($data)
    {}
}
