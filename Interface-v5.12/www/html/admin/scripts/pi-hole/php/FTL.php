<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

const DEFAULT_FTLCONFFILE = "/etc/pihole/pihole-FTL.conf";
const DEFAULT_FTL_IP = "127.0.0.1";
const DEFAULT_FTL_PORT = 4711;

function piholeFTLConfig($piholeFTLConfFile = DEFAULT_FTLCONFFILE, $force = false) {
    static $piholeFTLConfig;

    if (isset($piholeFTLConfig) && !$force) {
        return $piholeFTLConfig;
    }

    if (is_readable($piholeFTLConfFile)) {
        $piholeFTLConfig = parse_ini_file($piholeFTLConfFile);
    } else {
        $piholeFTLConfig = array();
    }

    return $piholeFTLConfig;
}

function connectFTL($address, $port) {
    if ($address == DEFAULT_FTL_IP) {
        $config = piholeFTLConfig();
        // Read port
        $portfileName = isset($config['PORTFILE']) ? $config['PORTFILE'] : '';
        if ($portfileName != '') {
            $portfileContents = file_get_contents($portfileName);
            if (is_numeric($portfileContents)) {
                $port = intval($portfileContents);
            }
        }
    }

    // Open Internet socket connection
    $socket = @fsockopen($address, $port, $errno, $errstr, 1.0);

    return $socket;
}

function sendRequestFTL($requestin, $socket) {
    $request = ">".$requestin;
    fwrite($socket, $request) or die('{"error":"Could not send data to server"}');
}

function getResponseFTL($socket) {
    $response = [];

    $errCount = 0;
    while (true) {
        $out = fgets($socket);
        if ($out == "") {
            $errCount++;
        }

        if ($errCount > 100) {
            // Tried 100 times, but never got proper reply, fail to prevent busy loop
            die('{"error":"Tried 100 times to connect to FTL server, but never got proper reply. Please check Port and logs!"}');
        }

        if (strrpos($out,"---EOM---") !== false) {
            break;
        }

        $out = rtrim($out);
        if (strlen($out) > 0) {
            $response[] = $out;
        }
    }

    return $response;
}

function disconnectFTL($socket) {
  if (is_resource($socket)) {
    fclose($socket);
  }
}

function callFTLAPI($request, $FTL_IP = DEFAULT_FTL_IP, $port = DEFAULT_FTL_PORT) {
    $socket = connectFTL($FTL_IP, $port);

    if (!is_resource($socket)) {
        $data = array("FTLnotrunning" => true);
    } else {
        sendRequestFTL($request, $socket);
        $data = getResponseFTL($socket);
    }
    disconnectFTL($socket);
    return $data;
}
?>
