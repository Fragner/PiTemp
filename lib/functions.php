<?php

/**
 * Merges the application and the local config and returns it.
 */
$m_temp;
function getConfig()
{
	$config = include dirname(__FILE__) . '/../config.php';
	if (file_exists(dirname(__FILE__) . '/../local.config.php')) {
		// Load the local config an merge
		$localConfig = include dirname(__FILE__) . '/../local.config.php';
		$config = array_merge($config, $localConfig);
	}
	
	return $config;
}

/**
 * Calculates the average of the given values
 */
function average($values) {
	$total = 0;
	$count = 0;
	foreach ($values as $temperature) {
		$total += $temperature;
		$count++;
	}
	
	return number_format(round($total / $count, 1), 1);
}

/**
 * @return the minimum value in an array of integers.
 */
function getMin($values) {
	$lowest = NULL;
	foreach ($values as $temperature) {
		$value = $temperature;
		if ($lowest === NULL || $value < $lowest) {
			$lowest = $value;
		}
	}
	return number_format($lowest, 1);
}

/**
 * @return the maximum value in an array of integers
 */
function getMax($values) {
	$highest = NULL;
	foreach ($values as $temperature) {
		$value = $temperature;
		if ($highest === NULL || $value > $highest) {
			$highest = $value;
		}
	}
	return number_format($highest, 1);
}

/**
 * Sends a notification email to the email address defined 
 * in the configuration file.
 * If no email address is defined, this method files silently.
 * @param $temp The measured temperature
 * @param $addresses The addresses you want to send an email to.
 */
function sendEmailNotification($temp, $addresses = array())
{
	global $m_temp;
	$m_temp = $temp;
	$to = join(",", $addresses);
	$subject = "PiTemp Notification ".$temp."C";
	$message = createNotificationText($temp);
	$headers = "From: PiTemp <notification@pitemp.local> \r\n";
	
	// Send the mail!
	//mail($to, $subject, $message, $headers);
	
	//send mail with attachement
	$attachement = topToLog();	
	sendMailWithAttachement($to, $subject, $message, $headers, $attachement);
}

/**
 * Sends a notification over pushover.net to the user 
 * that is defined in the configuration file. 
 * This method uses a small script on our server to "hide" our
 * application key. 
 * The script used on our server is also available on github if you 
 * want to review it. 
 * @todo Add Github URL to server script
 * @param $temp The measured temperature
 * @param $userKey The Pushover User key.
 */
function sendPushoverNotification($temp, $userKey)
{
	// Get the url to the PiTemp Server
	$config = getConfig();
	$pitempServer = $config['pi_temp_server'];
	
	// Initialize cURL for the request
	$curl = curl_init();
	
	// Set the options
	curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_URL => $pitempServer . "?temp=" . $temp . "&userkey=" . $userKey
	));
	
	// Send the request
	$response = curl_exec($curl);
	
	// @todo Add some sort of log to log failed attempts.
	
	// Close the request
	curl_close($curl);
}

/**
 * Sends a notification via pushbullet.com to the device
 * that is defined in the configuration file. 
 * @param $temp The measured temperature
 * @param $deviceid The Pushbullet device ID.
 * @param $apikey The Pushbullet API key.
 */
function sendPushbulletNotification($temp, $deviceid, $apikey)
// some code snippets from https://github.com/fodawim/PushBullet-API-PHP/blob/master/pushbullet.php
{
	$post_data = array(
                    'device_id' => $deviceid,
                    'type' => 'note',
                    'title' => 'PiTemp notification',
                    'body' => createNotificationText($temp) 
	);
					
	$post_data_string = http_build_query($post_data);

	$ch = curl_init('https://www.pushbullet.com/api/pushes');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $apikey . ":");
	curl_setopt($ch, CURLOPT_POST, count($post_data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_string);
	$response = curl_exec($ch);
	// Close the request
	curl_close($ch);
}

/**
 * Gets the local IP address (hopefully)
 * works with German console language
 * @todo Implement independent from language
 * @param $interface The network interface
*/
function getServerAddress($interface) 
{
    // Parse the result of ifconfig to get the ip address
    $ifconfig = shell_exec('/sbin/ifconfig ' . $interface);
    preg_match('/inet [A-Za-z]*?:([\d\.]+)/', $ifconfig, $match);
    return isset($match[1]) ? $match[1] : '';
}

/**
 * Creates the notification text
 * @param float $temp The temperature in degree C. 
 */
function createNotificationText($temp)
{
	$config = getConfig();
	$notifiactionConfig = $config['notification'];
	
	$message = "Your Raspberry Pi's temperature raised above your defined maximum temperature. \n\n";
	
	// Add Temp
	$message .= "Current temperature: " . $temp . "C\r\n";
	
	// Add (optional) hostname
	if ($notifiactionConfig['show_hostname']) {
		$message .= "Hostname: " . gethostname() . "\r\n";
	}
	
	// Add (optional) IP address
	if ($notifiactionConfig['show_ip_address']) {
		$message .= "IP Address: " . getServerAddress('eth0') . "\r\n";
	}

	$message .= "\r\n Greetings from your friendly server:-)\r\n";
	return $message;
}
/**

	http://eureka.ykyuen.info/2010/02/16/php-send-attachmemt-with-php-mail/
*/
function sendMailWithAttachement($mail_to, $subject, $message, $headers, $attachement)
{
/* Email Details */
  $from_mail = "frama1038@gmail.com";
  $from_name = "pi@raspberry";
  $replyto = "frama1038@gmail.com";
  $files = "top.txt";
  $path = "/var/log/myLogs/";
  
  /*****************/
  	$filename  = $files;
	$file      = $path.$filename;
	$file_size = filesize($file);
	$handle    = fopen($file, "r");
	$content   = fread($handle, $file_size);
	fclose($handle);

	$content = chunk_split(base64_encode($content));
	$uid     = md5(uniqid(time()));
	$name    = basename($file);				
	$header    = "From: " . $from_name . " <" . $from_mail . ">\n";
	$header .= "Reply-To: " . $replyto . "\n";
	$header .= "MIME-Version: 1.0\n";
	$header .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"\n\n";
	
	$emessage = "--" . $uid . "\n";
	$emessage .= "Content-type:text/html; charset=iso-8859-1\n";
	$emessage .= "Content-Transfer-Encoding: 7bit\n\n";
	$emessage .= $message . "\n\n";
	$emessage .= "--" . $uid . "\n";
	$emessage .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"\n"; // use different content types here
	$emessage .= "Content-Transfer-Encoding: base64\n";
	$emessage .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\n\n";
	$emessage .= $content . "\n\n";
	$emessage .= "--" . $uid . "--";
	     
  /**
  send mail and check if it could sent
	*/   
  if (mail($mail_to, $subject, $emessage, $header)) {  
	global $m_temp;
	$date =date("Y-m-d H:i:s");
	$text = $date." - PiTemp:= $m_temp - mail couldn't sent!\n";
	$logfile = '/var/log/myLogs/PiTempAboveLimit';
	$handle = @fopen($logfile, 'a') ;
	fwrite($handle, $text);
	fclose($handle);
  } 	
}

/**
	write the summary of 'top' to a file and returns the path to the writen file
	@return path of the writen file
*/
function topToLog()
{
	$filename="/var/log/myLogs/top.txt";
	$date =date("Y-m-d H:i:s");
	$datei = fopen($filename, "w+");
	fwrite ($datei, $date. "\n");
	fclose ($datei);
	exec("top -n1 -b >  $filename");
	return $filename;
}