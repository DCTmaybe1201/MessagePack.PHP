# MessagePack.PHP

 
[MessagePack.prg](https://msgpack.org/)

spec document: https://github.com/msgpack/msgpack/blob/master/spec.md

``` php
$msgpack_array = [
    'compact' => true,
    'schema' => 0,
];

$jsonData = json_encode($msgpack_array);

// json type to MessagePack
$message = new MessagePack();
$msgpack = $message->encode($jsonData);
// or encode array directly
$msgpack = $message->encode($msgpack_array);
```

You can change msgpack_array in the file `index.php`.

clone this repo and execute
```
php index.php
```
