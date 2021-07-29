<?php

require_once "MessagePack.php";

$msgpack_array = [
    'compact' => true,
    'schema' => 0,
];

$jsonData = json_encode($msgpack_array);

$message = new MessagePack();
$msgpack = $message->encode($jsonData);

echo $msgpack;
