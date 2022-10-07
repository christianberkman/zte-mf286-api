<?php
/**
  * Zte4gRouterApi
  * 2022 by Christian Berkman
  *
  * Main Class File
*/

namespace ZTEMF286;

class Api{

    /**
     * Constants
     */
    const BYTE_TO_GIB = 1073741824; // 1024^3

    /**
     * Private properties, accessible through __get()
     */
  	private	$routerIp, // Router's IP
          	$routerPassword, // Password (base64 encoded by constructor)
          	$ch, // cURL handler
            $setCmdUrl, // complete url for setting commands set by constructor
            $getCmdUrl; // complete url for getting commands set by constructor
  
  /**
   * Constructor
   * @param string $routerIp Router's IP address
   * @param string $routerPassword Router's password
   * @return void
   */
  public function __construct($routerIp){
    // Set ip, password, setOpt, getOpt
    $this->routerIp = $routerIp;
    $this->setUrl = "http://{$this->routerIp}/goform/goform_set_cmd_process";
    $this->getUrl = "http://{$this->routerIp}/goform/goform_get_cmd_process";

    // Start Curl Session
    $this->ch = curl_init();  
  }

  /**
   * Get values using getCmd
   * @param array|string $commands Commands to get
   * @param bool $decode Return decoded JSON string
   * 
   * @return array decoded JSON response
   */
  public function getCmd($commands = [], $decode = true){
    // Force array
    if(is_string($commands)) $commands = [$commands];
    if(!is_array($commands)) throw new Exception('Commands should be string or array');
    
    // Compile cmd string
    $cmdString = implode('%2C', $commands);
    
    // GET request to getUrl with command data
    curl_setopt_array($this->ch, [
      CURLOPT_URL => "{$this->getUrl}?multi_data=1&isTest=false&cmd={$cmdString}",
      CURLOPT_POST => false
    ]);
    $response = curl_exec($this->ch);

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
  public function setCmd($command, $postFields = []){
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
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => 'zte-cookie',
        CURLOPT_REFERER => "http://{$this->routerIp}/",
        CURLOPT_URL => $this->setUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFieldsString,
      ]
    );

    $response = curl_exec($this->ch);
    return json_decode($response, true);
  }
  
  /**
   * Login to the router
   *
   * @return bool
   */
  public function login($routerPassword){
    $this->routerPassword = base64_encode($routerPassword);
    $reponse = $this->setCmd('LOGIN', ['password' => $this->routerPassword]);
    if($reponse['result'] == "0") return true;
    else return false;
  }

  /**
   * Compute AD parameter to send with some setCmd request
   * Thanks to https://gist.github.com/olekstomek/0d598b7c8e251d403e18689211f23a78
   *
   * @return string
   */
  private function computeAD(){
    $version = $this->getCmd(['wa_inner_version,cr_version']);
    $versionString = $version['wa_inner_version'] . $version['cr_version'];
    
    $versionMd5 = md5($versionString);

    $rd = ($this->getCmd(['RD']))['RD'];
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
    return $result['result'];
  }

  /**
   * Connect the network
   *
   * @return string
   */
  public function connect(){
    $ad = $this->computeAD();
    
    $result =$this->setCmd('CONNECT_NETWORK', ['AD' => $ad, 'notCallback' => 'true']);
    return $result['result'];
  }

  /**
   * Restart the router
   *
   * @return bool
   */
  public function restart(){
    $ad = $this->computeAD();    
    
    $result = $this->setCmd('REBOOT_DEVICE', ['AD' => $ad]);
    return ($result['result'] == 'success' ? true : false);
  }

  /**
   * Attempt to detect if the WAN is down
   *
   * @return bool
   */
  public function detectWanDown(){
    // Get network status info
    $params = $this->getCmd(['network_type', 'ppp_status']);

      // Limited Service
      if($params['network_type'] == 'Limited Service') return true;
      
      // Disconnected
      if($params['ppp_status'] == 'ppp_disconnected') return true;

    // All Ok?
    return false;        
  }

  /**
   * Report data usage (tx, rx, total) in bytes and GiB (2 decimals)
   *
   * @return array
   */
  public function dataUsage(){
    // Get parameters
    $p = $this->getCmd( ['monthly_rx_bytes', 'monthly_tx_bytes'] );  
  
    // Return Array
    $r = [];
  
    // rx, tx, total bytes
    $cmdArray = $this->cmdArray;
    $r['rx']['bytes']     = $p['monthly_rx_bytes'];
    $r['tx']['bytes']     = $p['monthly_tx_bytes'];
    $r['total']['bytes']  = $r['rx']['bytes'] + $r['tx']['bytes'];

    // Convert bytes to Gib and round off to 2 decimal points
    $r['rx']['GiB']       = round( $r['rx']['bytes'] / $this::BYTE_TO_GIB, 2 );
    $r['tx']['GiB']       = round( $r['tx']['bytes'] / $this::BYTE_TO_GIB, 2 );
    $r['total']['GiB']    = round( $r['total']['bytes'] / $this::BYTE_TO_GIB, 2);

    // Return array
    return $r;
  }
  
  /**
   * Magic Getter
   */
  public function __get($property){
  	if(isset($this->$property)) return $this->$property;
  	else return null;
  }

} # class
