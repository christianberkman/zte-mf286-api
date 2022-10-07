# ZTE MF286 API
API For ZTE MF284 4G Router, possible for related types as well


# Install and Usage
```
composer require christianberkman/zte-mf286-api
```

```
<?php
    $zteApi = new ZTEMF286\Api('192.168.1.1');
    $login = $zteApi->login('password'); // returns boolean
```

# Public functions
## login(string $routerPassword)  
Login to the router using provided password, returns boolean.

## getCmd(array $commands, bool $decode = true)
Return parameters given in `$commands` (see Get Commands.md) as array
Set `$decode` to true to decode json response into an array, false to return the response as a string. Returns null if failed.

## setCmd(string $command, array $postFields, bool $decode = true)
Post `$command` including `$postFields`. 
Set `$decode` to true to decode json response into an array, false to return the response as a string. Returns null if failed.

## isWanConnected()
Returns if WAN is connected, returns boolean

## connect()
Attempt to connect the network, returns boolean

## reconnect()
Attempt to disconnect, returns boolean

## restart()
Attempt to restart the router, returns boolean

## dataUsage()
Report the datausage as an array, returns false if failed
```
Array
(
    [rx] => Array
        (
            [bytes] => 116867051568
            [GiB] => 108.84
        )

    [tx] => Array
        (
            [bytes] => 17771215964
            [GiB] => 16.55
        )

    [total] => Array
        (
            [bytes] => 134638267532
            [GiB] => 125.39
        )

)
```