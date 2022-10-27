<?php
/**
  * ZTE MF286 Api
  * 2022 by Christian Berkman
  *
  * Class File
*/

namespace ZTEMF286;

class Api{

    /**
     * Constants
     */
    const BYTE_TO_GIB = 1073741824; // 1024^3
    const BYTE_TO_MIB = 1048576; // 1024^2

    /**
     * Private properties, accessible through __get()
     */
  	private	$routerIp, // Router's IP
          	$routerPassword, // Password (base64 encoded by constructor)
            $cookiePath, // Path to cookie file
          	$ch, // cURL handler
            $setCmdUrl, // complete url for setting commands set by constructor
            $getCmdUrl; // complete url for getting commands set by constructor
  
  /**
   * Constructor
   * @param string $routerIp Router's IP address
   * @param string $routerPassword Router's password
   * @return void
   */
  public function __construct($routerIp, $cookiePath = __DIR__){
    // Set ip, setOpt, getOpt
    $this->routerIp = $routerIp;
    $this->setUrl = "http://{$this->routerIp}/goform/goform_set_cmd_process";
    $this->getUrl = "http://{$this->routerIp}/goform/goform_get_cmd_process";

    // Start Curl Session
    $this->ch = curl_init();
    curl_setopt_array($this->ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_REFERER => "http://{$this->routerIp}/",
      CURLOPT_URL => $this->setUrl
    ]);
    $this->setCookiePath($cookiePath);
  }

  public function setCookiePath($path){
    // Check if path if writable
    if(!is_writable($path)) throw new \Exception("Cookie path '{$path}' is not writable.");
    
    // Set cookie path and file
    $this->cookiePath = $path;

    // Curl option
    curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookiePath . '/zte-cookie');
  }

  /**
   * Get values using getCmd
   * @param array|string $commands Commands to get
   * @param bool $decode Return decoded JSON string
   * 
   * @return mixed false if failed, string or array on success
   */
  public function getCmd($commands = [], $decode = true){
    // String: no multi data
    if(is_string($commands)){
      $multi = null;
      $cmdString = $commands;
    } 
    // Array: multi data
    if(is_array($commands)){
      $multi = '&multi_data=1';
      $cmdString = implode('%2C', $commands);
    }
    
    // GET request to getUrl with command data
    curl_setopt_array($this->ch, [
      CURLOPT_URL => "{$this->getUrl}?isTest=false&cmd={$cmdString}{$multi}",
      CURLOPT_POST => false
    ]);
    $response = curl_exec($this->ch);
    if($response == false) return null;

    if($decode) return json_decode($response, true);
    else return $response;
  }

  /**
   * Set value using setCmd
   * @param string $command Command to set
   * @param array $postFields
   * 
   * @return array decoded JSON response
   */
  public function setCmd($command, $postFields = [], $decode = true){
    // Make sure postFields is array
    if(!is_array($postFields)) $postFields = [$postFields];
    
    // Add goformId isTest to array
    $postFields['isTest'] = 'false';
    $postFields['goformId'] = $command;
    
    // Glue and implode post strings
    foreach($postFields as $key => $value){
      $postFieldsGlued[] = "{$key}={$value}";
    }
    $postFieldsString = implode('&', $postFieldsGlued);
    
    curl_setopt_array($this->ch, 
      [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFieldsString,
      ]
    );

    $response = curl_exec($this->ch);
    if($response == false) return null;
    
    if($decode) return json_decode($response, true);
    else return $response;
  }
  
  /**
   * Login to the router
   *
   * @return bool
   */
  public function login($routerPassword){
    $this->routerPassword = base64_encode($routerPassword);
    $response = $this->setCmd('LOGIN', ['password' => $this->routerPassword]);
    if($response == null) return false;
    if(!isset($response['result'])) return false;
    return ($response['result'] == "0" ? true : false);
  }

  /**
   * Compute AD parameter to send with some setCmd request
   * Thanks to https://gist.github.com/olekstomek/0d598b7c8e251d403e18689211f23a78
   *
   * @return string
   */
  private function computeAD(){
    $version = $this->getCmd(['wa_inner_version,cr_version']);
    if($version == false) return null;
    $versionString = $version['wa_inner_version'] . $version['cr_version'];
    
    $versionMd5 = md5($versionString);

    $rd = ($this->getCmd(['RD']))['RD'];
    if($rd == false) return null;
    $adString = $versionMd5 . $rd;
    $ad = md5($adString);

    return $ad;    
  }

  /**
   * Disconnect the network
   *
   * @return string
   */
  public function disconnect(){
    $ad = $this->computeAD();    
    
    $result = $this->setCmd('DISCONNECT_NETWORK', ['AD' => $ad, 'notCallback' => 'true']);
    return ( ($result['result'] ?? null) == 'success' ? true : false);
  }

  /**
   * Connect the network
   *
   * @return string
   */
  public function connect(){
    $ad = $this->computeAD();
    
    $result =$this->setCmd('CONNECT_NETWORK', ['AD' => $ad, 'notCallback' => 'true']);
    return ( ($result['result'] ?? null) == 'success' ? true : false);
  }

  /**
   * Restart the router
   *
   * @return bool
   */
  public function restart(){
    $ad = $this->computeAD();    
    
    $result = $this->setCmd('REBOOT_DEVICE', ['AD' => $ad]);
    return ( ($result['result'] ?? null) == 'success' ? true : false);
  }

  /**
   * Returns if WAN is connected
   *
   * @return bool
   */
  public function isWanConnected(){
    // Get network status info
    $params = $this->getCmd(['network_type', 'ppp_status']);

      // Limited Service
      if($params['network_type'] == 'Limited Service') return false;
      
      // Disconnected
      if($params['ppp_status'] == 'ppp_disconnected') return false;

    // WAN seems connected
    return true;        
  }

  /**
   * Report data usage (tx, rx, total) in bytes and GiB (2 decimals)
   *
   * @return array
   */
  public function dataUsage(){
    // Get parameters
    $p = $this->getCmd( ['monthly_rx_bytes', 'monthly_tx_bytes'] );  
    if(!isset($p['monthly_rx_bytes']) || !isset($p['monthly_tx_bytes'])) return false;

    // Return Array
    $r = [];
  
    // rx, tx, total bytes
    $r['rx']['bytes']     = intval($p['monthly_rx_bytes']);
    $r['tx']['bytes']     = intval($p['monthly_tx_bytes']);
    $r['total']['bytes']  = $r['rx']['bytes'] + $r['tx']['bytes'];

    // Return false if total is equal to zero, indicates error
    if($r['total']['bytes'] == 0) return false;

    // Convert bytes to Gib and round off to 2 decimal points
    $r['rx']['GiB']       = round( $r['rx']['bytes'] / $this::BYTE_TO_GIB, 2 );
    $r['tx']['GiB']       = round( $r['tx']['bytes'] / $this::BYTE_TO_GIB, 2 );
    $r['total']['GiB']    = round( $r['total']['bytes'] / $this::BYTE_TO_GIB, 2);

    // Return array
    return $r;
  }

  /**
   * Return realtime rx and tx bytes/s, KiB/s, MiB/s
   * @return array
   */
  public function realtime(){
    $realtime = $this->getCmd(['realtime_rx_bytes', 'realtime_rx_thrpt', 'realtime_tx_bytes', 'realtime_tx_thrpt' ]);
    
    $return = [
      'rx_mib' => round( ($realtime['realtime_rx_thrpt'] / $this::BYTE_TO_MIB), 2),
      'rx_kib' => round( ($realtime['realtime_rx_thrpt'] / 1024), 2),
      'rx_bytes' => $realtime['realtime_rx_thrpt'],
      'tx_mib' => round( ($realtime['realtime_tx_thrpt'] / $this::BYTE_TO_MIB), 2),
      'tx_kib' => round( ($realtime['realtime_tx_thrpt'] / 1024), 2),
      'tx_bytes' => $realtime['realtime_tx_thrpt']
    ];

    return $return;

  }

  /**
   * Return an array of connected devices via LAN and WiFi
   *
   * @return array
   */
  public function connectedDevices(){
    $wifi = $this->getCmd('station_list')['station_list'];
    var_dump($wifi);
    $lan = $this->getCmd('lan_station_list')['lan_station_list'];
    $all = array_merge(
      array_values($wifi), array_values($lan)
    );

    return ['wifi' => array_values($wifi), 'lan' => array_values($lan), 'all' => $all];
  }
  
  /**
   * Magic Getter
   */
  public function __get($property){
  	if(isset($this->$property)) return $this->$property;
  	else return null;
  }

} # class
