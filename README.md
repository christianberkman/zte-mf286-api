# ZTE MF286 API
API For ZTE MF284 4G Router, possible for related types as well


# Install and Usage
```
composer require christianberkman/zte-mf286-api
```

```
<?php
    $zteApi = new ZTEMF286\Api('192.168.1.1', '/path/to/cookie');
    $login = $zteApi->login('password'); // returns boolean
```

# Public functions
* [constructor](#constructorsting-routerip-string-cookiepath--dir)
* [setCookiePath]([#setCookiePathstring-path](#setcookiepathstring-path))
* [login]([#loginstring-path](#loginstring-routerpassword))
* [getCMd](#getcmdarray-commands-bool-decode--true)
* [setCMd](#setcmdstring-command-array-postfields-bool-decode--true)
* [isWanConnected](#iswanconnected)
* [connect](#connect)
* [reconnect](#reconnect)
* [restart](#restart)
* [dataUsage](#datausage)
* [realtime](#realtime)
* [connectedDevices](#connecteddevices)

## constructor(sting $routerIp, string $cookiePath = __DIR__)
Construct the class and set the router's IP, optional cookie path

## setCookiePath(string $path)
Checks if the path is writeable and sets the cookie path if true. Cookie filename is `zte-cookie`.

## login(string $routerPassword)  
Login to the router using provided password, returns boolean.

## getCmd(array $commands, bool $decode = true)
Return parameters given in `$commands` (see (get-commands.md)[https://github.com/christianberkman/zte-mf286-api/blob/main/get-commands.md]) as array
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
[rx] => Array   
    [bytes] => 116867051568
    [GiB] => 108.84
[tx] => Array
    [bytes] => 17771215964
    [GiB] => 16.55
[total] => Array
    [bytes] => 134638267532
    [GiB] => 125.39
```

## realtime()
Return realtime rx and tx bytes/s, KiB/s, MiB/s. Often 0 is returned as is the upload/download monitor in the modem's interface
```
    [rx_mib] => 0
    [rx_kib] => 0.34
    [rx_bytes] => 348
    [tx_mib] => 0
    [tx_kib] => 0.18
    [tx_bytes] => 188
```
## connectedDevices()
Return an array of connected devices
```
[wifi]
    [0]
        [mac_addr] => 1C:F2:9A:56:09:73
        [hostname] => Google-Nest-Mini
        [ip_addr] => 192.168.1.161
        [addr_type] => 2
        [ssid_index] => 0
    [..]
[lan]
    [..]
[all]
    [..] // wifi and lan combined
```
