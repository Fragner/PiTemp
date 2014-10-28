<?php
/**
File:	httpPost2vz.php
Author:	frama

send a value via curl Post to the middleware from the volkszaehler
*/

checkIniFile();
if ($argc > 1) {
		$debug = 1;
		echo "debug is active!\n";
		$count = $argc - 1;
		echo "started with '$count' parameter\n\n";
		actualTemp2vz($argv[1]);
} else {
		$debug = 0;
		//echo "started withOUT parameter\n";
}

/**
*/	
function checkIniFile (){
	global $UUID, $vzport, $vzserver, $vzpath;
	global $ini_file,  $logdatei;
	$file = dirname(__FILE__).'/httpPost2vz.ini';
//	echo "file: ".$file."\n";

//	$ini_file = parse_ini_file("httpPost2vz.ini", TRUE);
	$ini_file = parse_ini_file($file, TRUE);
    	$logdatei = $ini_file["allgemein"]["logdatei"];

	$vzpath = $ini_file["vz"]["vzpath"];
	$vzserver = $ini_file["vz"]["vzserver"];
	$vzport = $ini_file["vz"]["vzport"];
    	$UUID = $ini_file["vz"]["UUID"];         //Zielkanal im VZ
	
    if (empty($logdatei)) {
		echo "ini-Parameter logdatei muss gefüllt werden!";
		exit;
	}
	if (empty($vzpath)) {
		echo "ini-Parameter vzpath muss gefüllt werden!";
		exit;
	}
	if (empty($vzserver)) {
		echo "ini-Parameter vzserver muss gefüllt werden!";
		exit;
	}
	if (empty($vzport)) {
		echo "ini-Parameter vzport muss gefüllt werden!";
		exit;
	}
}

/**
* send actual temp via curl Post to the middleware from the volkszaehler
*/
function  actualTemp2vz ($val) {
	global $UUID, $debug;

	if (isset($UUID) && !empty($UUID)){
		$output = httpPost($UUID, $val);
		if ($debug) echo $output."\n";
	} else {
		echo "UUID isn't setted!\n";
	}
}


/* */
function httpPost($uuid, $val) {
	global $vzport, $vzserver, $vzpath;
	global $time, $debug;
	if (isset($uuid) && !empty($uuid)){
		$time = time() * 1000;
		$url = "http://".$vzserver.$vzport."/".$vzpath."/data/".$uuid.".json?ts=".$time."&value=".$val;

		if ($debug) {
			echo $url;
		} else {
			;
		}
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_POST,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array());
			curl_setopt($ch, CURLOPT_USERAGENT, "PiTemp - httpPost2vz");
			$output=curl_exec($ch);
			curl_close($ch);
			return $output;

	} else {
		echo "uuid isn't setted!\n";
	}
}
/*
example from s0vz.c
sprintf(url, "http://%s:%d/%s/data/%s.json?ts=%llu", vzserver, vzport, vzpath, vzuuid, unixtime()); 
*/
?>
