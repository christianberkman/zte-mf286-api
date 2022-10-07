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
Login to the router using provided password, returns boolean

## getCmd(array $commands, bool $decode)
Return parameters given in `$commands` (see Get Commands.md) as  array
`$decode` to true to decode json to array

## setCmd(string $command, array $postFields)
Post `$command` including `$postFields`. 

## detectWanDown()
Returns boolean TRUE if WAN is disconnected

## connect()
Attempt to connect the network, returns boolean

## reconnect()
Attempt to disconnect, returns boolean