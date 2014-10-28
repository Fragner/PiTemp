<?php
/**
File:	curl2vz.php
Author:	frama

send a value via curl Post to the middleware from the volkszaehler
*/

class curl2vz {
	private $vzport, $vzserver, $vzpath;
	private $ini_file, $UUID, $logdatei;
	private $debug = 0;
	
	
	/**
	 *  Singleton Interface
	 */
	public static $instance;
	public static function getInstance()
	{
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/*
	 * Constructor
	 */
	public function __construct($inifile=null)
	{
		/*
			check if script is running under windows
			--> no socket and query 
		*/		
		if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
			$this->bWindows = 1;
			echo "__construct This is a server using Windows!\n";
		} else {
			if ($this->debug) echo 'This is a server not using Windows!\n';
			$this->bWindows = 0;				
		}
		
		if (is_null($inifile)) {
			$this->checkIniFile("curl2vz.ini");
		} else {
			$this->checkIniFile($inifile);
		}
	}
	

	/**
	*/	
	private function checkIniFile ($infile){
		if ($this->debug) echo "inifile: ".$infile."\n";
		$this->ini_file = parse_ini_file($infile, TRUE);
		$this->logdatei = $this->ini_file["allgemein"]["logdatei"];
		
		$this->UUID = $this->ini_file["vz"]["UUID"];
		$this->vzpath = $this->ini_file["vz"]["vzpath"];
		$this->vzserver = $this->ini_file["vz"]["vzserver"];
		$this->vzport = $this->ini_file["vz"]["vzport"];
		
		if (empty($this->logdatei)) {
			echo "ini-Parameter logdatei muss gefüllt werden!";
			exit;
		}
		if (empty($this->vzpath)) {
			echo "ini-Parameter vzpath muss gefüllt werden!";
			exit;
		}
		if (empty($this->vzserver)) {
			echo "ini-Parameter vzserver muss gefüllt werden!";
			exit;
		}
		if (empty($this->vzport)) {
			echo "ini-Parameter vzport muss gefüllt werden!";
			exit;
		}
		if (empty($this->UUID)) {
			echo "ini-Parameter UUID muss gefüllt werden!";
			exit;
		}
		else {
			if ($this->debug) echo "UUID: ".$this->UUID."\n";
		}				
	}

	/**
	* send actual value via curl Post to the middleware from the volkszaehler
	*/
	public function  actualValue2vz ($val) {
		
		if (isset($this->UUID) && !empty($this->UUID)){	
			$output = $this->httpPost($this->UUID, $val);
			if ($this->debug) echo "UUID: ".$output."\n";
			
		} else {
			echo $val." -UUID zzz isn't setted!\n";
		}	
	}

	/* */
	private function httpPost($uuid, $val) {
		if (isset($uuid) && !empty($uuid)){	
			$time = time() * 1000;
			$url = "http://".$this->vzserver.$this->vzport."/".$this->vzpath."/data/".$uuid.".json?ts=".$time."&value=".$val;	
		
			if ($this->debug) {
				echo $url;
			} else {		
				$ch = curl_init();	 
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_POST,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, array());
				curl_setopt($ch, CURLOPT_USERAGENT, "PiTemp Raspberry"); 
				$output=curl_exec($ch);
				curl_close($ch);
				return $output; 
			}
		} else {
			echo "uuid isn't setted!\n";	
		}	
	}
}

?>
