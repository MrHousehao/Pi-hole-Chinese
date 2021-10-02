<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

require "password.php";
require "auth.php"; // Also imports func.php
require "database.php";
require "savesettings.php";

if (php_sapi_name() !== "cli") {
	if(!$auth) die("未授权");
	check_csrf(isset($_POST["token"]) ? $_POST["token"] : "");
}

$db = SQLite3_connect(getGravityDBFilename(), SQLITE3_OPEN_READWRITE);

$flushed_tables = array();

function archive_add_file($path,$name,$subdir="")
{
	global $archive;
	if(file_exists($path.$name))
		$archive[$subdir.$name] = file_get_contents($path.$name);
}

/**
 * Add the contents of a table to the archive
 *
 * @param $name string The name of the file in the archive to save the table to
 * @param $table string The table to export
 * @param $type integer Type of domains to store
 */
function archive_add_table($name, $table, $type=-1)
{
	global $archive, $db;

	if($type > -1)
	{
		$querystr = "SELECT * FROM \"$table\" WHERE type = $type;";
	}
	else
	{
		$querystr = "SELECT * FROM \"$table\";";
	}
	$results = $db->query($querystr);

	// Return early without creating a file if the
	// requested table cannot be accessed
	if(is_null($results))
		return;

	$content = array();
	while ($row = $results->fetchArray(SQLITE3_ASSOC))
	{
		array_push($content, $row);
	}

	$archive[$name] = json_encode($content);
}

/**
 * Restore the contents of a table from an uploaded archive
 *
 * @param $file object The file in the archive to restore the table from
 * @param $table string The table to import
 * @param $flush boolean Whether to flush the table before importing the archived data
 * @return integer Number of restored rows
 */
function archive_restore_table($file, $table, $flush=false)
{
	global $db, $flushed_tables;

	$json_string = file_get_contents($file);
	// Return early if we cannot extract the JSON string
	if(is_null($json_string))
		return 0;

	$contents = json_decode($json_string, true);
	// Return early if we cannot decode the JSON string
	if(is_null($contents))
		return 0;

	// Flush table if requested, only flush each table once
	if($flush && !in_array($table, $flushed_tables))
	{
		$db->exec("DELETE FROM \"".$table."\"");
		array_push($flushed_tables, $table);
	}

	// Prepare fields depending on the table we restore to
	if($table === "adlist")
	{
		$sql  = "INSERT OR IGNORE INTO adlist";
		$sql  .= " (id,address,enabled,date_added,comment)";
		$sql  .= " VALUES (:id,:address,:enabled,:date_added,:comment);";
	}
	elseif($table === "domain_audit")
	{
		$sql  = "INSERT OR IGNORE INTO domain_audit";
		$sql  .= " (id,domain,date_added)";
		$sql  .= " VALUES (:id,:domain,:date_added);";
	}
	elseif($table === "domainlist")
	{
		$sql  = "INSERT OR IGNORE INTO domainlist";
		$sql  .= " (id,domain,enabled,date_added,comment,type)";
		$sql  .= " VALUES (:id,:domain,:enabled,:date_added,:comment,:type);";
	}
	elseif($table === "group")
	{
		$sql  = "INSERT OR IGNORE INTO \"group\"";
		$sql  .= " (id,name,date_added,description)";
		$sql  .= " VALUES (:id,:name,:date_added,:description);";
	}
	elseif($table === "client")
	{
		$sql  = "INSERT OR IGNORE INTO client";
		$sql  .= " (id,ip,date_added,comment)";
		$sql  .= " VALUES (:id,:ip,:date_added,:comment);";
	}
	elseif($table === "domainlist_by_group")
	{
		$sql  = "INSERT OR IGNORE INTO domainlist_by_group";
		$sql  .= " (domainlist_id,group_id)";
		$sql  .= " VALUES (:domainlist_id,:group_id);";
	}
	elseif($table === "client_by_group")
	{
		$sql  = "INSERT OR IGNORE INTO client_by_group";
		$sql  .= " (client_id,group_id)";
		$sql  .= " VALUES (:client_id,:group_id);";
	}
	elseif($table === "adlist_by_group")
	{
		$sql  = "INSERT OR IGNORE INTO adlist_by_group";
		$sql  .= " (adlist_id,group_id)";
		$sql  .= " VALUES (:adlist_id,:group_id);";
	}
	else
	{
		if($table === "whitelist")
			$type = 0;
		elseif($table === "blacklist")
			$type = 1;
		elseif($table === "regex_whitelist")
			$type = 2;
		elseif($table === "regex_blacklist")
			$type = 3;

		$sql  = "INSERT OR IGNORE INTO domainlist";
		$sql  .= " (id,domain,enabled,date_added,comment,type)";
		$sql  .= " VALUES (:id,:domain,:enabled,:date_added,:comment,$type);";
		$field = "domain";
	}

	// Prepare SQLite statement
	$stmt = $db->prepare($sql);

	// Return early if we fail to prepare the SQLite statement
	if(!$stmt)
	{
		echo "Failed to prepare statement for ".$table." table.";
		echo $sql;
		return 0;
	}

	// Loop over rows and inject the entries into the database
	$num = 0;
	foreach($contents as $row)
	{
		// Limit max length for a domain entry to 253 chars
		if(isset($field) && strlen($row[$field]) > 253)
			continue;

		// Bind properties from JSON data
		// Note that only defined above are actually used
		// so even maliciously modified Teleporter files
		// cannot be dangerous in any way
		foreach($row as $key => $value) {
			$type = gettype($value);
			$sqltype=NULL;
			switch($type) {
				case "integer":
					$sqltype = SQLITE3_INTEGER;
				break;
				case "string":
					$sqltype = SQLITE3_TEXT;
				break;
				case "NULL":
					$sqltype = SQLITE3_NULL;
				break;
				default:
					$sqltype = "UNK";
			}
			$stmt->bindValue(":".$key, htmlentities($value), $sqltype);
		}

		if($stmt->execute() && $stmt->reset() && $stmt->clear())
			$num++;
		else
		{
			$stmt->close();
			return $num;
		}
	}

	// Close database connection and return number or processed rows
	$stmt->close();
	return $num;
}

/**
 * Create table rows from an uploaded archive file
 *
 * @param $file object The file in the archive to import
 * @param $table string The target table
 * @param $flush boolean Whether to flush the table before importing the archived data
 * @param $wildcardstyle boolean Whether to format the input domains in legacy wildcard notation
 * @return integer Number of processed rows from the imported file
 */
function archive_insert_into_table($file, $table, $flush=false, $wildcardstyle=false)
{
	global $db;

	$domains = array_filter(explode("\n",file_get_contents($file)));
	// Return early if we cannot extract the lines in the file
	if(is_null($domains))
		return 0;

	// Generate comment
	$prefix = "phar:///tmp/";
	if (substr($file, 0, strlen($prefix)) == $prefix) {
		$file = substr($file, strlen($prefix));
	}
	$comment = "Imported from ".$file;

	// Determine table and type to import to
	$type = null;
	if($table === "whitelist") {
		$table = "domainlist";
		$type = ListType::whitelist;
	} else if($table === "blacklist") {
		$table = "domainlist";
		$type = ListType::blacklist;
	} else if($table === "regex_blacklist") {
		$table = "domainlist";
		$type = ListType::regex_blacklist;
	} else if($table === "domain_audit") {
		$table = "domain_audit";
		$type = -1; // -1 -> not used inside add_to_table()
	} else if($table === "adlist") {
		$table = "adlist";
		$type = -1; // -1 -> not used inside add_to_table()
	}

	// Flush table if requested
	if($flush) {
		flush_table($table, $type);
	}

	// Add domains to requested table
	return add_to_table($db, $table, $domains, $comment, $wildcardstyle, true, $type);
}

/**
 * Flush table if requested. This subroutine flushes each table only once
 *
 * @param $table string The target table
 * @param $type integer Type of item to flush in table (applies only to domainlist table)
 */
function flush_table($table, $type=null)
{
	global $db, $flushed_tables;

	if(!in_array($table, $flushed_tables))
	{
		if($type !== null) {
			$sql = "DELETE FROM \"".$table."\" WHERE type = ".$type;
			array_push($flushed_tables, $table.$type);
		} else {
			$sql = "DELETE FROM \"".$table."\"";
			array_push($flushed_tables, $table);
		}
		$db->exec($sql);
	}
}

function archive_add_directory($path,$subdir="")
{
	if($dir = opendir($path))
	{
		while(false !== ($entry = readdir($dir)))
		{
			if($entry !== "." && $entry !== "..")
			{
				archive_add_file($path,$entry,$subdir);
			}
		}
		closedir($dir);
	}
}

function limit_length(&$item, $key)
{
	// limit max length for a domain entry to 253 chars
	// return only a part of the string if it is longer
	$item = substr($item, 0, 253);
}

function process_file($contents)
{
	$domains = array_filter(explode("\n",$contents));
	// Walk array and apply a max string length
	// function to every member of the array of domains
	array_walk($domains, "limit_length");
	return $domains;
}

if(isset($_POST["action"]))
{
	if($_FILES["zip_file"]["name"] && $_POST["action"] == "in")
	{
		$filename = $_FILES["zip_file"]["name"];
		$source = $_FILES["zip_file"]["tmp_name"];
		$type = mime_content_type($source);

		$name = explode(".", $filename);
		$accepted_types = array('application/gzip', 'application/tar', 'application/x-compressed', 'application/x-gzip');
		$okay = false;
		foreach($accepted_types as $mime_type) {
			if($mime_type == $type) {
				$okay = true;
				break;
			}
		}

		$continue = strtolower($name[1]) == 'tar' && strtolower($name[2]) == 'gz' ? true : false;
		if(!$continue || !$okay) {
			die("您尝试上传的文件不是.tar.gz文件（文件名：".htmlentities($filename)."，文件类型：".htmlentities($type)."）。请再试一次。");
		}

		$fullfilename = sys_get_temp_dir()."/".$filename;
		if(!move_uploaded_file($source, $fullfilename))
		{
			die("Failed moving ".htmlentities($source)." to ".htmlentities($fullfilename));
		}

		$archive = new PharData($fullfilename);

		$importedsomething = false;

		$flushtables = isset($_POST["flushtables"]);

		foreach(new RecursiveIteratorIterator($archive) as $file)
		{
			if(isset($_POST["blacklist"]) && $file->getFilename() === "blacklist.txt")
			{
				$num = archive_insert_into_table($file, "blacklist", $flushtables);
				echo "已处理黑名单（确切）（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["whitelist"]) && $file->getFilename() === "whitelist.txt")
			{
				$num = archive_insert_into_table($file, "whitelist", $flushtables);
				echo "已处理白名单（确切）（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["regexlist"]) && $file->getFilename() === "regex.list")
			{
				$num = archive_insert_into_table($file, "regex_blacklist", $flushtables);
				echo "已处理黑名单（正侧表达式）（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			// Also try to import legacy wildcard list if found
			if(isset($_POST["regexlist"]) && $file->getFilename() === "wildcardblocking.txt")
			{
				$num = archive_insert_into_table($file, "regex_blacklist", $flushtables, true);
				echo "已处理黑名单（正侧表达式/通配符）（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["auditlog"]) && $file->getFilename() === "auditlog.list")
			{
				$num = archive_insert_into_table($file, "domain_audit", $flushtables);
				echo "已处理审核日志 (".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["adlist"]) && $file->getFilename() === "adlists.list")
			{
				$num = archive_insert_into_table($file, "adlist", $flushtables);
				echo "已处理引力场(".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["blacklist"]) && $file->getFilename() === "blacklist.exact.json")
			{
				$num = archive_restore_table($file, "blacklist", $flushtables);
				echo "已处理黑名单（确切）（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["regexlist"]) && $file->getFilename() === "blacklist.regex.json")
			{
				$num = archive_restore_table($file, "regex_blacklist", $flushtables);
				echo "已处理黑名单（正侧表达式）（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["whitelist"]) && $file->getFilename() === "whitelist.exact.json")
			{
				$num = archive_restore_table($file, "whitelist", $flushtables);
				echo "已处理白名单（确切）（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["regex_whitelist"]) && $file->getFilename() === "whitelist.regex.json")
			{
				$num = archive_restore_table($file, "regex_whitelist", $flushtables);
				echo "已处理白名单（正侧表达式）（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["adlist"]) && $file->getFilename() === "adlist.json")
			{
				$num = archive_restore_table($file, "adlist", $flushtables);
				echo "已处理引力场(".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["auditlog"]) && $file->getFilename() === "domain_audit.json")
			{
				$num = archive_restore_table($file, "domain_audit", $flushtables);
				echo "已处理的域名审查 (".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["group"]) && $file->getFilename() === "group.json")
			{
				$num = archive_restore_table($file, "group", $flushtables);
				echo "已处理群组（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["client"]) && $file->getFilename() === "client.json")
			{
				$num = archive_restore_table($file, "client", $flushtables);
				echo "已处理客户端（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["client"]) && $file->getFilename() === "client_by_group.json")
			{
				$num = archive_restore_table($file, "client_by_group", $flushtables);
				echo "已处理客户端群组分配（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if((isset($_POST["whitelist"]) || isset($_POST["regex_whitelist"]) ||
				isset($_POST["blacklist"]) || isset($_POST["regex_blacklist"])) &&
				$file->getFilename() === "domainlist_by_group.json")
			{
				$num = archive_restore_table($file, "domainlist_by_group", $flushtables);
				echo "已处理黑名单/白名单群组分配（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["adlist"]) && $file->getFilename() === "adlist_by_group.json")
			{
				$num = archive_restore_table($file, "adlist_by_group", $flushtables);
				echo "已处理引力场群组分配（".$num."条配置）<br>\n";
				$importedsomething = true;
			}

			if(isset($_POST["staticdhcpleases"]) && $file->getFilename() === "04-pihole-static-dhcp.conf")
			{
				if($flushtables) {
					$local_file = @fopen("/etc/dnsmasq.d/04-pihole-static-dhcp.conf", "r+");
					if ($local_file !== false) {
						ftruncate($local_file, 0);
						fclose($local_file);
					}
				}
				$num = 0;
				$staticdhcpleases = process_file(file_get_contents($file));
				foreach($staticdhcpleases as $lease) {
					list($mac,$ip,$hostname) = explode(",",$lease);
					$mac = formatMAC($mac);
					if(addStaticDHCPLease($mac,$ip,$hostname))
						$num++;
				}

				readStaticLeasesFile();
				echo "已处理静态DHCP地址分配（".$num."条配置）<br>\n";
				if($num > 0) {
					$importedsomething = true;
				}
			}

			if(isset($_POST["localdnsrecords"]) && $file->getFilename() === "custom.list")
			{
				if($flushtables) {
					// Defined in func.php included via auth.php
					deleteAllCustomDNSEntries();
				}
				$num = 0;
				$localdnsrecords = process_file(file_get_contents($file));
				foreach($localdnsrecords as $record) {
					list($ip,$domain) = explode(" ",$record);
					if(addCustomDNSEntry($ip, $domain, false))
						$num++;
				}

				echo "已处理本地 DNS 记录（".$num."条配置）<br>\n";
				if($num > 0) {
					$importedsomething = true;
				}
			}

			if(isset($_POST["localcnamerecords"]) && $file->getFilename() === "05-pihole-custom-cname.conf")
			{
				if($flushtables) {
					// Defined in func.php included via auth.php
					deleteAllCustomCNAMEEntries();
				}

				$num = 0;
				$localcnamerecords = process_file(file_get_contents($file));
				foreach($localcnamerecords as $record) {
					$line = str_replace("cname=","", $record);
					$line = str_replace("\r","", $line);
					$line = str_replace("\n","", $line);
					$explodedLine = explode (",", $line);

					$domain = implode(",", array_slice($explodedLine, 0, -1));
					$target = $explodedLine[count($explodedLine)-1];

					if(addCustomCNAMEEntry($domain, $target, false))
						$num++;
				}

				echo "已处理本地 CNAME 记录（".$num."条配置）<br>\n";
				if($num > 0) {
					$importedsomething = true;
				}
			}
		}

		if($importedsomething)
		{
			pihole_execute("重新启动DNS服务器并重新加载");
		}

		unlink($fullfilename);
		echo "OK";
	}
	else
	{
		die("无文件传输或参数错误。");
	}
}
else
{
	$hostname = gethostname() ? str_replace(".", "_", gethostname())."-" : "";
	$tarname = "pi-hole-".$hostname."teleporter_".date("Y-m-d_H-i-s").".tar";
	$filename = $tarname.".gz";
	$archive_file_name = sys_get_temp_dir() ."/". $tarname;
	$archive = new PharData($archive_file_name);

	if ($archive->isWritable() !== TRUE) {
		exit("cannot open/create ".htmlentities($archive_file_name)."<br>\nPHP user: ".exec('whoami')."\n");
	}

	archive_add_table("whitelist.exact.json", "domainlist", ListType::whitelist);
	archive_add_table("whitelist.regex.json", "domainlist", ListType::regex_whitelist);
	archive_add_table("blacklist.exact.json", "domainlist", ListType::blacklist);
	archive_add_table("blacklist.regex.json", "domainlist", ListType::regex_blacklist);
	archive_add_table("adlist.json", "adlist");
	archive_add_table("domain_audit.json", "domain_audit");
	archive_add_table("group.json", "group");
	archive_add_table("client.json", "client");

	// Group linking tables
	archive_add_table("domainlist_by_group.json", "domainlist_by_group");
	archive_add_table("adlist_by_group.json", "adlist_by_group");
	archive_add_table("client_by_group.json", "client_by_group");

	archive_add_file("/etc/pihole/","setupVars.conf");
	archive_add_file("/etc/pihole/","dhcp.leases");
	archive_add_file("/etc/pihole/","custom.list");
	archive_add_file("/etc/pihole/","pihole-FTL.conf");
	archive_add_file("/etc/","hosts","etc/");
	archive_add_directory("/etc/dnsmasq.d/","dnsmasq.d/");

	$archive->compress(Phar::GZ); // Creates a gziped copy
	unlink($archive_file_name); // Unlink original tar file as it is not needed anymore
	$archive_file_name .= ".gz"; // Append ".gz" extension to ".tar"

	header("Content-type: application/gzip");
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=".$filename);
	header("Content-length: " . filesize($archive_file_name));
	header("Pragma: no-cache");
	header("Expires: 0");
	if(ob_get_length() > 0) ob_end_clean();
	readfile($archive_file_name);
	ignore_user_abort(true);
	unlink($archive_file_name);
	exit;
}

?>
