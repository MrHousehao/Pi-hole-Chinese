<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

$piholeFTLConfFile = "/etc/pihole/pihole-FTL.conf";

function piholeFTLConfig()
{
	static $piholeFTLConfig;
	global $piholeFTLConfFile;

	if(isset($piholeFTLConfig))
	{
		return $piholeFTLConfig;
	}

	if(is_readable($piholeFTLConfFile))
	{
		$piholeFTLConfig = parse_ini_file($piholeFTLConfFile);
	}
	else
	{
		$piholeFTLConfig = array();
	}

	return $piholeFTLConfig;
}

function connectFTL($address, $port=4711)
{
	if($address == "127.0.0.1")
	{
		$config = piholeFTLConfig();
		// Read port
		$portfileName = isset($config['PORTFILE']) ? $config['PORTFILE'] : '';
		if ($portfileName != '')
		{
			$portfileContents = file_get_contents($portfileName);
			if(is_numeric($portfileContents))
				$port = intval($portfileContents);
		}
	}

	// Open Internet socket connection
	$socket = @fsockopen($address, $port, $errno, $errstr, 1.0);

	return $socket;
}

function sendRequestFTL($requestin)
{
	global $socket;

	$request = ">".$requestin;
	fwrite($socket, $request) or die('{"错误":"无法向服务器发送数据"}');
}

function getResponseFTL()
{
	global $socket;

	$response = [];

	$errCount = 0;
	while(true)
	{
		$out = fgets($socket);
		if ($out == "") $errCount++;
		if ($errCount > 100) {
			// Tried 100 times, but never got proper reply, fail to prevent busy loop
			die('{"错误":"尝试了 100 次连接到 FTL 服务器，但没有得到正确的答复。 请检查端口和日志！"}');
		}
		if(strrpos($out,"---EOM---") !== false)
			break;

		$out = rtrim($out);
		if(strlen($out) > 0)
			$response[] = $out;
	}

	return $response;
}

function disconnectFTL()
{
	global $socket;
	fclose($socket);
}
?>
