<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

// Credit: http://stackoverflow.com/a/4694816/2087442
// Modified because of https://github.com/pi-hole/AdminLTE/pull/533
ini_set("pcre.recursion_limit", 1500);
function validDomain($domain_name, &$message = NULL)
{
	if(!preg_match("/^((-|_)*[a-z\d]((-|_)*[a-z\d])*(-|_)*)(\.(-|_)*([a-z\d]((-|_)*[a-z\d])*))*$/i", $domain_name)) {
		if($message !== NULL)
			$message = "it contains invalid characters";
		return false;
	}
	if(!preg_match("/^.{1,253}$/", $domain_name)) {
		if($message !== NULL)
			$message = "its length is invalid";
		return false;
	}
	if(!preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)) {
		if($message !== NULL)
			$message = "at least one label is of invalid length";
		return false;
	}

	// everything is okay
	return true;
}

function validDomainWildcard($domain_name)
{
	// There has to be either no or at most one "*" at the beginning of a line
	$validChars = preg_match("/^((\*\.)?[_a-z\d](-*[_a-z\d])*)(\.([_a-z\d](-*[a-z\d])*))*(\.([_a-z\d])*)*$/i", $domain_name);
	$lengthCheck = preg_match("/^.{1,253}$/", $domain_name);
	$labelLengthCheck = preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name);
	return ( $validChars && $lengthCheck && $labelLengthCheck ); //length of each label
}

function validIP($address){
	if (preg_match('/[.:0]/', $address) && !preg_match('/[1-9a-f]/', $address)) {
		// Test if address contains either `:` or `0` but not 1-9 or a-f
		return false;
	}
	return !filter_var($address, FILTER_VALIDATE_IP) === false;
}

function validCIDRIP($address){
	// This validation strategy has been taken from ../js/groups-common.js
	$isIPv6 = strpos($address, ":") !== false;
	if($isIPv6) {
		// One IPv6 element is 16bit: 0000 - FFFF
		$v6elem = "[0-9A-Fa-f]{1,4}";
		// dnsmasq allows arbitrary prefix-length since https://thekelleys.org.uk/gitweb/?p=dnsmasq.git;a=commit;h=35f93081dc9a52e64ac3b7196ad1f5c1106f8932
		$v6cidr = "([1-9]|[1-9][0-9]|1[01][0-9]|12[0-8])";
		$validator = "/^(((?:$v6elem))((?::$v6elem))*::((?:$v6elem))((?::$v6elem))*|((?:$v6elem))((?::$v6elem)){7})\/$v6cidr$/";
		return preg_match($validator, $address);
	} else {
		// One IPv4 element is 8bit: 0 - 256
		$v4elem = "(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?|0)";
		// dnsmasq allows arbitrary prefix-length
		$allowedv4cidr = "(([1-9]|[12][0-9]|3[0-2]))";
		$validator = "/^$v4elem\.$v4elem\.$v4elem\.$v4elem\/$allowedv4cidr$/";
		return preg_match($validator, $address);
	}
}

function validMAC($mac_addr)
{
  // Accepted input format: 00:01:02:1A:5F:FF (characters may be lower case)
  return !filter_var($mac_addr, FILTER_VALIDATE_MAC) === false;
}

function validEmail($email)
{
	return filter_var($email, FILTER_VALIDATE_EMAIL)
		// Make sure that the email does not contain special characters which
		// may be used to execute shell commands, even though they may be valid
		// in an email address. If the escaped email does not equal the original
		// email, it is not safe to store in setupVars.
		&& escapeshellcmd($email) === $email;
}

function get_ip_type($ip)
{
    return  filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 4 :
           (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 6 :
            0);
}

function checkfile($filename) {
    if(is_readable($filename))
    {
        return $filename;
    }
    else
    {
        // substitute dummy file
        return "/dev/null";
    }
}

// Credit: http://php.net/manual/en/function.hash-equals.php#119576
if(!function_exists('hash_equals')) {
    function hash_equals($known_string, $user_string) {
        $ret = 0;

        if (strlen($known_string) !== strlen($user_string)) {
         $user_string = $known_string;
         $ret = 1;
        }

        $res = $known_string ^ $user_string;

        for ($i = strlen($res) - 1; $i >= 0; --$i) {
         $ret |= ord($res[$i]);
        }

        return !$ret;
   }
}

/**
 * More safely execute a command with pihole shell script.
 *
 * For example,
 *
 *   pihole_execute("-h");
 *
 * would execute command
 *
 *   sudo pihole -h
 *
 * and returns output of that command as a string.
 *
 * @param $argument_string String of arguments to run pihole with.
 * @param $error_on_failure If true, a warning is raised if command execution fails. Defaults to true.
 */
function pihole_execute($argument_string, $error_on_failure = true) {
    $escaped = escapeshellcmd($argument_string);
    $output = null;
    $return_status = -1;
    $command = "sudo pihole " . $escaped;
    exec($command, $output, $return_status);
    if($return_status !== 0)
    {
        trigger_error("Executing {$command} failed.", E_USER_WARNING);
    }
    return $output;
}

// Custom DNS
$customDNSFile = "/etc/pihole/custom.list";

function echoCustomDNSEntries()
{
    $entries = getCustomDNSEntries();

    $data = [];
    foreach ($entries as $entry)
        $data[] = [ $entry->domain, $entry->ip ];

    return [ "data" => $data ];
}

function getCustomDNSEntries()
{
    global $customDNSFile;

    $entries = [];

    $handle = fopen($customDNSFile, "r");
    if ($handle)
    {
        while (($line = fgets($handle)) !== false) {
            $line = str_replace("\r","", $line);
            $line = str_replace("\n","", $line);
            $explodedLine = explode (" ", $line);

            if (count($explodedLine) != 2)
                continue;

            $data = new \stdClass();
            $data->ip = $explodedLine[0];
            $data->domain = $explodedLine[1];
            $entries[] = $data;
        }

        fclose($handle);
    }

    return $entries;
}

function addCustomDNSEntry($ip="", $domain="", $reload="", $json=true)
{
    try
    {
        if(isset($_REQUEST['ip']))
            $ip = trim($_REQUEST['ip']);

        if(isset($_REQUEST['domain']))
            $domain = trim($_REQUEST['domain']);

        if(isset($_REQUEST['reload']))
            $reload = $_REQUEST['reload'];

        if (empty($ip))
            return returnError("IP must be set", $json);

        $ipType = get_ip_type($ip);

        if (!$ipType)
            return returnError("IP must be valid", $json);

        if (empty($domain))
            return returnError("Domain must be set", $json);

        if (!validDomain($domain))
            return returnError("Domain must be valid", $json);

        // Only check for duplicates if adding new records from the web UI (not through Teleporter)
        if(isset($_REQUEST['ip']) || isset($_REQUEST['domain']))
        {
            $existingEntries = getCustomDNSEntries();
            foreach ($existingEntries as $entry)
                if ($entry->domain == $domain && get_ip_type($entry->ip) == $ipType)
                    return returnError("This domain already has a custom DNS entry for an IPv" . $ipType, $json);
        }

        // Add record
        pihole_execute("-a addcustomdns ".$ip." ".$domain." ".$reload);

        return returnSuccess("", $json);
    }
    catch (\Exception $ex)
    {
        return returnError($ex->getMessage(), $json);
    }
}

function deleteCustomDNSEntry()
{
    try
    {
        $ip = !empty($_REQUEST['ip']) ? $_REQUEST['ip']: "";
        $domain = !empty($_REQUEST['domain']) ? $_REQUEST['domain']: "";

        if (empty($ip))
            return returnError("IP must be set");

        if (empty($domain))
            return returnError("Domain must be set");

        $existingEntries = getCustomDNSEntries();

        $found = false;
        foreach ($existingEntries as $entry)
            if ($entry->domain == $domain)
                if ($entry->ip == $ip) {
                    $found = true;
                    break;
                }

        if (!$found)
            return returnError("This domain/ip association does not exist");

        pihole_execute("-a removecustomdns ".$ip." ".$domain);

        return returnSuccess();
    }
    catch (\Exception $ex)
    {
        return returnError($ex->getMessage());
    }
}

function deleteAllCustomDNSEntries($reload="")
{
    try
		{
        if(isset($_REQUEST['reload']))
            $reload = $_REQUEST['reload'];

        $existingEntries = getCustomDNSEntries();
        // passing false to pihole_execute stops pihole from reloading after each enty has been deleted
        foreach ($existingEntries as $entry) {
            pihole_execute("-a removecustomdns ".$entry->ip." ".$entry->domain." ".$reload);
        }

    }
    catch (\Exception $ex)
    {
        return returnError($ex->getMessage());
    }

    return returnSuccess();
}

// CNAME
$customCNAMEFile = "/etc/dnsmasq.d/05-pihole-custom-cname.conf";

function echoCustomCNAMEEntries()
{
    $entries = getCustomCNAMEEntries();

    $data = [];
    foreach ($entries as $entry)
        $data[] = [ $entry->domain, $entry->target ];

    return [ "data" => $data ];
}

function getCustomCNAMEEntries()
{
    global $customCNAMEFile;

    $entries = [];

    if (!file_exists($customCNAMEFile)) return $entries;

    $handle = fopen($customCNAMEFile, "r");
    if ($handle)
    {
        while (($line = fgets($handle)) !== false) {
            $line = str_replace("cname=","", $line);
            $line = str_replace("\r","", $line);
            $line = str_replace("\n","", $line);
            $explodedLine = explode (",", $line);

            if (count($explodedLine) <= 1)
                continue;

            $data = new \stdClass();
            $data->domains = array_slice($explodedLine, 0, -1);
            $data->domain = implode(",", $data->domains);
            $data->target = $explodedLine[count($explodedLine)-1];
            $entries[] = $data;
        }

        fclose($handle);
    }

    return $entries;
}

function addCustomCNAMEEntry($domain="", $target="", $reload="", $json=true)
{
    try
    {
        if(isset($_REQUEST['domain']))
            $domain = $_REQUEST['domain'];

        if(isset($_REQUEST['target']))
            $target = trim($_REQUEST['target']);

        if(isset($_REQUEST['reload']))
            $reload = $_REQUEST['reload'];

        if (empty($domain))
            return returnError("Domain must be set", $json);

        if (empty($target))
            return returnError("Target must be set", $json);

        if (!validDomain($target))
            return returnError("Target must be valid", $json);

        // Check if each submitted domain is valid
        $domains = array_map('trim', explode(",", $domain));
        foreach ($domains as $d) {
            if (!validDomain($d))
                return returnError("Domain '$d' is not valid", $json);
        }

        $existingEntries = getCustomCNAMEEntries();

        // Check if a record for one of the domains already exists
        foreach ($existingEntries as $entry)
            foreach ($domains as $d)
                if (in_array($d, $entry->domains))
                    return returnError("There is already a CNAME record for '$d'", $json);

        pihole_execute("-a addcustomcname ".$domain." ".$target." ".$reload);

        return returnSuccess("", $json);
    }
    catch (\Exception $ex)
    {
        return returnError($ex->getMessage(), $json);
    }
}

function deleteCustomCNAMEEntry()
{
    try
    {
        $target = !empty($_REQUEST['target']) ? $_REQUEST['target']: "";
        $domain = !empty($_REQUEST['domain']) ? $_REQUEST['domain']: "";

        if (empty($target))
            return returnError("Target must be set");

        if (empty($domain))
            return returnError("Domain must be set");

        $existingEntries = getCustomCNAMEEntries();

        $found = false;
        foreach ($existingEntries as $entry)
            if ($entry->domain == $domain)
                if ($entry->target == $target) {
                    $found = true;
                    break;
                }

        if (!$found)
            return returnError("This domain/ip association does not exist");

        pihole_execute("-a removecustomcname ".$domain." ".$target);

        return returnSuccess();
    }
    catch (\Exception $ex)
    {
        return returnError($ex->getMessage());
    }
}

function deleteAllCustomCNAMEEntries($reload="")
{
    try
    {
        if(isset($_REQUEST['reload']))
            $reload = $_REQUEST['reload'];

        $existingEntries = getCustomCNAMEEntries();
        // passing false to pihole_execute stops pihole from reloading after each enty has been deleted
        foreach ($existingEntries as $entry) {
            pihole_execute("-a removecustomcname ".$entry->domain." ".$entry->target." ".$reload);
        }

    }
    catch (\Exception $ex)
    {
        return returnError($ex->getMessage());
    }

    return returnSuccess();
}

function returnSuccess($message = "", $json = true)
{
    if ($json) {
        return [ "success" => true, "message" => $message ];
    } else {
        echo $message."<br>";
        return true;
    }
}

function returnError($message = "", $json = true)
{
    $message = htmlentities($message) ;
    if ($json) {
        return [ "success" => false, "message" => $message ];
    } else {
        echo $message."<br>";
        return false;
    }
}

function getQueryTypeStr($querytype)
{
    $qtypes = ["A (IPv4)", "AAAA (IPv6)", "ANY", "SRV", "SOA", "PTR", "TXT", "NAPTR", "MX", "DS", "RRSIG", "DNSKEY", "NS", "OTHER", "SVCB", "HTTPS"];
    $qtype = intval($querytype);
    if($qtype > 0 && $qtype <= count($qtypes))
        return $qtypes[$qtype-1];
    else
        return "TYPE".($qtype - 100);
}

// Returns an integer representing pihole blocking status
function piholeStatus() {
    // Retrieve DNS Port calling FTL API directly
    $port = callFTLAPI("dns-port");

    // Retrieve FTL status
    $FTLstats = callFTLAPI("stats");

    if (array_key_exists("FTLnotrunning", $port) || array_key_exists("FTLnotrunning", $FTLstats)){
        // FTL is not running
        $ret = -1;
    } elseif (in_array("status enabled", $FTLstats)) {
        // FTL is enabled
        if (intval($port[0]) <= 0) {
            // Port=0; FTL is not listening
            $ret = -1;
        } else {
            // FTL is running on this port
            $ret = intval($port[0]);
        }
    } elseif (in_array("status disabled", $FTLstats)) {
        // FTL is disabled
        $ret = 0;
    } else {
        // Unknown (unexpected) response
        $ret = -2;
    }

    return $ret;
}
?>
