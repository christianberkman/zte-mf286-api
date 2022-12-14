<?php
/**
 * ZTE MF286 Api
 * 2022 by Christian Berkman
 * 
 * Example file
 */

require_once( __DIR__ .'/vendor/autoload.php' ); # composer
#require_once( __DIR__ .'/src/Api.php' ); # direct

$zteApi = new ZTEMF286\Api('192.168.1.1');

// Login
$login = $zteApi->login('password');
if($login) echo 'Login successfull' . PHP_EOL;
else exit('Login unsuccessfull'. PHP_EOL);

// Report data usage
$dataUsage = $zteApi->dataUsage();
if(!$dataUsage) exit('Could not report data usage' . PHP_EOL);
echo 'Data usage (received): '. $dataUsage['rx']['GiB'] . ' GiB'. PHP_EOL;
echo 'Data usage (sent): '. $dataUsage['tx']['GiB'] . ' GiB'. PHP_EOL;
echo 'Data usage (total): '. $dataUsage['total']['GiB'] . ' GiB'. PHP_EOL;
