<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

require_once("func.php");

if(!in_array(basename($_SERVER['SCRIPT_FILENAME']), ["settings.php", "teleporter.php"], true))
{
	die("禁止直接访问此脚本！");
}

// Check for existence of variable
// and test it only if it exists
function istrue(&$argument) {
	if(isset($argument))
	{
		if($argument)
		{
			return true;
		}
	}
	return false;
}

function formatMAC($mac_addr)
{
	preg_match("/([0-9a-fA-F]{2}[:]){5}([0-9a-fA-F]{2})/", $mac_addr, $matches);
	if(count($matches) > 0)
		return $matches[0];
	return null;
}

$dhcp_static_leases = array();
function readStaticLeasesFile($origin_file="/etc/dnsmasq.d/04-pihole-static-dhcp.conf")
{
	global $dhcp_static_leases;
	$dhcp_static_leases = array();
	if(!file_exists($origin_file) || !is_readable($origin_file))
		return false;

	$dhcpstatic = @fopen($origin_file, 'r');
	if(!is_resource($dhcpstatic))
		return false;

	while(!feof($dhcpstatic))
	{
		// Remove any possibly existing variable with this name
		$mac = ""; $one = ""; $two = "";
		sscanf(trim(fgets($dhcpstatic)),"dhcp-host=%[^,],%[^,],%[^,]",$mac,$one,$two);
		if(strlen($mac) > 0 && validMAC($mac))
		{
			if(validIP($one) && strlen($two) == 0)
				// dhcp-host=mac,IP - no HOST
				array_push($dhcp_static_leases,["hwaddr"=>$mac, "IP"=>$one, "host"=>""]);
			elseif(strlen($two) == 0)
				// dhcp-host=mac,hostname - no IP
				array_push($dhcp_static_leases,["hwaddr"=>$mac, "IP"=>"", "host"=>$one]);
			else
				// dhcp-host=mac,IP,hostname
				array_push($dhcp_static_leases,["hwaddr"=>$mac, "IP"=>$one, "host"=>$two]);
		}
		else if(validIP($one) && validDomain($mac))
		{
			// dhcp-host=hostname,IP - no MAC
			array_push($dhcp_static_leases,["hwaddr"=>"", "IP"=>$one, "host"=>$mac]);
		}
	}
	return true;
}

function isequal(&$argument, &$compareto) {
	if(isset($argument))
	{
		if($argument === $compareto)
		{
			return true;
		}
	}
	return false;
}

function isinserverlist($addr) {
	global $DNSserverslist;
	foreach ($DNSserverslist as $key => $value) {
		if (isequal($value['v4_1'],$addr) || isequal($value['v4_2'],$addr))
			return true;
		if (isequal($value['v6_1'],$addr) || isequal($value['v6_2'],$addr))
			return true;
	}
	return false;
}

$DNSserverslist = [];
function readDNSserversList()
{
	// Reset list
	$list = [];
	$handle = @fopen("/etc/pihole/dns-servers.conf", "r");
	if ($handle)
	{
		while (($line = fgets($handle)) !== false)
		{
			$line = rtrim($line);
			$line = explode(';', $line);
			$name = $line[0];
			$values = [];
			if (!empty($line[1]) && validIP($line[1])) {
				$values["v4_1"] = $line[1];
			}
			if (!empty($line[2]) && validIP($line[2])) {
				$values["v4_2"] = $line[2];
			}
			if (!empty($line[3]) && validIP($line[3])) {
				$values["v6_1"] = $line[3];
			}
			if (!empty($line[4]) && validIP($line[4])) {
				$values["v6_2"] = $line[4];
			}
            $list[$name] = $values;
		}
		fclose($handle);
	}
	return $list;
}

require_once("database.php");

function addStaticDHCPLease($mac, $ip, $hostname) {
	global $error, $success, $dhcp_static_leases;

	try {
		if(!validMAC($mac))
		{
			throw new Exception("MAC地址 (".htmlspecialchars($mac).") 无效！<br>", 0);
		}
		$mac = strtoupper($mac);

		if(!validIP($ip) && strlen($ip) > 0)
		{
			throw new Exception("IP地址 (".htmlspecialchars($ip).") 无效！<br>", 1);
		}

		if(!validDomain($hostname) && strlen($hostname) > 0)
		{
			throw new Exception("主机名称 (".htmlspecialchars($hostname).") 无效！<br>", 2);
		}

		if(strlen($hostname) == 0 && strlen($ip) == 0)
		{
			throw new Exception("您不能既不输入IP地址又不输入主机名称！<br>", 3);
		}

		if(strlen($hostname) == 0)
			$hostname = "nohost";

		if(strlen($ip) == 0)
			$ip = "noip";

		// Test if this lease is already included
		readStaticLeasesFile();

		foreach($dhcp_static_leases as $lease) {
			if($lease["hwaddr"] === $mac)
			{
				throw new Exception("MAC地址 (".htmlspecialchars($mac).") 的静态地址已被分配！<br>", 4);
			}
			if($ip !== "noip" && $lease["IP"] === $ip)
			{
				throw new Exception("IP地址 (".htmlspecialchars($ip).") 的静态地址已被分配！<br>", 5);
			}
			if($lease["host"] === $hostname)
			{
				throw new Exception("主机名称 (".htmlspecialchars($hostname).") 的静态地址已被分配！<br>", 6);
			}
		}

		pihole_execute("-a addstaticdhcp ".$mac." ".$ip." ".$hostname);
		$success .= "已添加新的静态地址分配";
		return true;
	} catch(Exception $exception) {
		$error .= $exception->getMessage();
		return false;
	}
}

	// Read available DNS server list
	$DNSserverslist = readDNSserversList();

	$error = "";
	$success = "";

	if(isset($_POST["field"]))
	{
		// Handle CSRF
		check_csrf(isset($_POST["token"]) ? $_POST["token"] : "");

		// Process request
		switch ($_POST["field"]) {
			// Set DNS server
			case "DNS":

				$DNSservers = [];
				// Add selected predefined servers to list
				foreach ($DNSserverslist as $key => $value)
				{
					foreach(["v4_1", "v4_2", "v6_1", "v6_2"] as $type)
					{
						if(@array_key_exists("DNSserver".str_replace(".","_",$value[$type]),$_POST))
						{
							array_push($DNSservers,$value[$type]);
						}
					}
				}

				// Test custom server fields
				for($i=1;$i<=4;$i++)
				{
					if(array_key_exists("custom".$i,$_POST))
					{
						$exploded = explode("#", $_POST["custom".$i."val"], 2);
						$IP = trim($exploded[0]);

						if(!validIP($IP))
						{
							$error .= "IP地址 (".htmlspecialchars($IP).") 无效！<br>";
						}
						else
						{
							if(count($exploded) > 1)
							{
								$port = trim($exploded[1]);
								if(!is_numeric($port))
								{
									$error .= "端口 (".htmlspecialchars($port).") 无效！<br>";
								}
								else
								{
									$IP .= "#".$port;
								}
							}

							array_push($DNSservers,$IP);
						}
					}
				}
				$DNSservercount = count($DNSservers);

				// Check if at least one DNS server has been added
				if($DNSservercount < 1)
				{
					$error .= "未选择 DNS 服务器。<br>";
				}

				// Check if domain-needed is requested
				if(isset($_POST["DNSrequiresFQDN"]))
				{
					$extra = "domain-needed ";
				}
				else
				{
					$extra = "domain-not-needed ";
				}

				// Check if domain-needed is requested
				if(isset($_POST["DNSbogusPriv"]))
				{
					$extra .= "bogus-priv ";
				}
				else
				{
					$extra .= "no-bogus-priv ";
				}

				// Check if DNSSEC is requested
				if(isset($_POST["DNSSEC"]))
				{
					$extra .= "dnssec";
				}
				else
				{
					$extra .= "no-dnssec";
				}

				// Check if rev-server is requested
				if(isset($_POST["rev_server"]))
				{
					// Validate CIDR IP
					$cidr = trim($_POST["rev_server_cidr"]);
					if (!validCIDRIP($cidr))
					{
						$error .= "条件转发子网 (\"".htmlspecialchars($cidr)."\") 无效<br>".
						          "此字段要求本地子网使用CIDR表示法（例如：192.168.0.0/16）。<br>";
					}

					// Validate target IP
					$target = trim($_POST["rev_server_target"]);
					if (!validIP($target))
					{
						$error .= "条件转发目标IP地址 (\"".htmlspecialchars($target)."\") 无效！<br>";
					}

					// Validate conditional forwarding domain name (empty is okay)
					$domain = trim($_POST["rev_server_domain"]);
					if(strlen($domain) > 0 && !validDomain($domain))
					{
						$error .= "条件转发域名 (\"".htmlspecialchars($domain)."\") 无效！<br>";
					}

					if(!$error)
					{
						$extra .= " rev-server ".$cidr." ".$target." ".$domain;
					}
				}

				// Check if DNSinterface is set
				if(isset($_POST["DNSinterface"]))
				{
					if($_POST["DNSinterface"] === "single")
					{
						$DNSinterface = "single";
					}
					elseif($_POST["DNSinterface"] === "all")
					{
						$DNSinterface = "all";
					}
					else
					{
						$DNSinterface = "local";
					}
				}
				else
				{
					// Fallback
					$DNSinterface = "local";
				}
				pihole_execute("-a -i ".$DNSinterface." -web");

				// If there has been no error we can save the new DNS server IPs
				if(!strlen($error))
				{
					$IPs = implode (",", $DNSservers);
					$return = pihole_execute("-a setdns \"".$IPs."\" ".$extra);
					$success .= htmlspecialchars(end($return))."<br>";
					$success .= "DNS 设置已更新（使用".$DNSservercount."个DNS服务器）";
				}
				else
				{
					$error .= "已恢复为之前保存的设置";
				}

				break;

			// Set query logging
			case "Logging":

				if($_POST["action"] === "Disable")
				{
					pihole_execute("-l off");
					$success .= "日志记录已被禁用，日志已被清空";
				}
				elseif($_POST["action"] === "Disable-noflush")
				{
					pihole_execute("-l off noflush");
					$success .= "日志记录已被禁用，您的日志<strong>未</strong>被清空";
				}
				else
				{
					pihole_execute("-l on");
					$success .= "已启用日志记录";
				}

				break;

			// Set domains to be excluded from being shown in Top Domains (or Ads) and Top Clients
			case "API":

				// Explode the contents of the textareas into PHP arrays
				// \n (Unix) and \r\n (Win) will be considered as newline
				// array_filter( ... ) will remove any empty lines
				$domains = array_filter(preg_split('/\r\n|[\r\n]/', $_POST["domains"]));
				$clients = array_filter(preg_split('/\r\n|[\r\n]/', $_POST["clients"]));

				$domainlist = "";
				$first = true;
				foreach($domains as $domain)
				{
					if(!validDomainWildcard($domain) || validIP($domain))
					{
						$error .= "域名".htmlspecialchars($domain)." 无效（只使用域名）！<br>";
					}
					if(!$first)
					{
						$domainlist .= ",";
					}
					else
					{
						$first = false;
					}
					$domainlist .= $domain;
				}

				$clientlist = "";
				$first = true;
				foreach($clients as $client)
				{
					if(!validDomainWildcard($client) && !validIP($client))
					{
						$error .= "客户端".htmlspecialchars($client)." 无效（只使用主机名称和 IP 地址）！<br>";
					}
					if(!$first)
					{
						$clientlist .= ",";
					}
					else
					{
						$first = false;
					}
					$clientlist .= $client;
				}

				// Set Top Lists options
				if(!strlen($error))
				{
					// All entries are okay
					pihole_execute("-a setexcludedomains ".$domainlist);
					pihole_execute("-a setexcludeclients ".$clientlist);
					$success .= "API 设置已更新<br>";
				}
				else
				{
					$error .= "已恢复为之前保存的设置";
				}

				// Set query log options
				if(isset($_POST["querylog-permitted"]) && isset($_POST["querylog-blocked"]))
				{
					pihole_execute("-a setquerylog all");
					if(!isset($_POST["privacyMode"]))
					{
						$success .= "所有记录将在查询请求日志中显示";
					}
					else
					{
						$success .= "只有吞噬的域名在查询请求日志中显示";
					}
				}
				elseif(isset($_POST["querylog-permitted"]))
				{
					pihole_execute("-a setquerylog permittedonly");
					if(!isset($_POST["privacyMode"]))
					{
						$success .= "只有放行的域名在查询请求日志中显示";
					}
					else
					{
						$success .= "查询请求日志中不显示任何记录";
					}
				}
				elseif(isset($_POST["querylog-blocked"]))
				{
					pihole_execute("-a setquerylog blockedonly");
					$success .= "只有吞噬的域名在查询请求日志中显示";
				}
				else
				{
					pihole_execute("-a setquerylog nothing");
					$success .= "查询日志中不显示任何记录";
				}


				if(isset($_POST["privacyMode"]))
				{
					pihole_execute("-a privacymode true");
					$success .= "（隐私模式开启）";
				}
				else
				{
					pihole_execute("-a privacymode false");
				}

				break;

			case "webUI":
				$adminemail = trim($_POST["adminemail"]);
				if(strlen($adminemail) == 0 || !isset($adminemail))
				{
					$adminemail = '';
				}
				if(strlen($adminemail) > 0 && !validEmail($adminemail))
				{
					$error .= "管理员E-Mail地址(".htmlspecialchars($adminemail).") 无效！<br>";
				}
				else
				{
					pihole_execute('-a -e \''.$adminemail.'\'');
				}
				if(isset($_POST["boxedlayout"]))
				{
					pihole_execute('-a layout boxed');
				}
				else
				{
					pihole_execute('-a layout traditional');
				}
				if(isset($_POST["webtheme"]))
				{
					global $available_themes;
					if(array_key_exists($_POST["webtheme"], $available_themes))
						exec('sudo pihole -a theme '.$_POST["webtheme"]);
				}
				$success .= "网站UI 设置已更新";
				break;

			case "poweroff":
				pihole_execute("-a poweroff");
				$success = "主机将在 5 秒后关机...";
				break;

			case "reboot":
				pihole_execute("-a reboot");
				$success = "主机将在 5 秒后重启...";
				break;

			case "restartdns":
				pihole_execute("-a restartdns");
				$success = "DNS 服务器已重新启动";
				break;

			case "flushlogs":
				pihole_execute("-f");
				$success = "Pi-hole 日志文件已清空";
				break;

			case "DHCP":

				if(isset($_POST["addstatic"]))
				{
					$mac = trim($_POST["AddMAC"]);
					$ip = trim($_POST["AddIP"]);
					$hostname = trim($_POST["AddHostname"]);

					addStaticDHCPLease($mac, $ip, $hostname);
					break;
				}

				if(isset($_POST["removestatic"]))
				{
					$mac = $_POST["removestatic"];
					if(!validMAC($mac))
					{
						$error .= "MAC地址 (".htmlspecialchars($mac).") 无效！<br>";
					}
					$mac = strtoupper($mac);

					if(!strlen($error))
					{
						pihole_execute("-a removestaticdhcp ".$mac);
						$success .= "MAC地址 ".htmlspecialchars($mac)." 的静态地址分配已删除";
					}
					break;
				}

				if(isset($_POST["active"]))
				{
					// Validate from IP
					$from = $_POST["from"];
					if (!validIP($from))
					{
						$error .= "开始IP地址 (".htmlspecialchars($from).") 无效！<br>";
					}

					// Validate to IP
					$to = $_POST["to"];
					if (!validIP($to))
					{
						$error .= "结束IP地址 (".htmlspecialchars($to).") 无效！<br>";
					}

					// Validate router IP
					$router = $_POST["router"];
					if (!validIP($router))
					{
						$error .= "路由器IP地址 (".htmlspecialchars($router).") 无效！<br>";
					}

					$domain = $_POST["domain"];

					// Validate Domain name
					if(!validDomain($domain))
					{
						$error .= "域名 ".htmlspecialchars($domain)." 无效！<br>";
					}

					$leasetime = $_POST["leasetime"];

					// Validate Lease time length
					if(!is_numeric($leasetime) || intval($leasetime) < 0)
					{
						$error .= "地址租期 ".htmlspecialchars($leasetime)." 无效！<br>";
					}

					if(isset($_POST["useIPv6"]))
					{
						$ipv6 = "true";
						$type = "(IPv4 + IPv6)";
					}
					else
					{
						$ipv6 = "false";
						$type = "(IPv4)";
					}

					if(isset($_POST["DHCP_rapid_commit"]))
					{
						$rapidcommit = "true";
					}
					else
					{
						$rapidcommit = "false";
					}

					if(!strlen($error))
					{
						pihole_execute("-a enabledhcp ".$from." ".$to." ".$router." ".$leasetime." ".$domain." ".$ipv6." ".$rapidcommit);
						$success .= "DHCP 服务器已激活".htmlspecialchars($type);
					}
				}
				else
				{
					pihole_execute("-a disabledhcp");
					$success = "DHCP 服务器已关闭";
				}

				break;

			case "privacyLevel":
				$level = intval($_POST["privacylevel"]);
				if($level >= 0 && $level <= 4)
				{
					// Check if privacylevel is already set
					if (isset($piholeFTLConf["PRIVACYLEVEL"])) {
						$privacylevel = intval($piholeFTLConf["PRIVACYLEVEL"]);
					} else {
						$privacylevel = 0;
					}

					// Store privacy level
					pihole_execute("-a privacylevel ".$level);

					if($privacylevel > $level)
					{
						pihole_execute("-a restartdns");
						$success .= "隐私级别已降低，DNS 服务器已重新启动";
					}
					elseif($privacylevel < $level)
					{
						$success .= "隐私级别已提升";
					}
					else
					{
						$success .= "隐私级别未改变";
					}
				}
				else
				{
					$error .= "无效的隐私级别 (".$level.")!";
				}
				break;
			// Flush network table
			case "flusharp":
				$output = pihole_execute("arpflush quiet");
				$error = "";
				if(is_array($output))
				{
					$error = implode("<br>", $output);
				}
				if(strlen($error) == 0)
				{
					$success .= "客户端列表已清空";
				}
				break;

			default:
				// Option not found
				$debug = true;
				break;
		}
	}

	// Credit: http://stackoverflow.com/a/5501447/2087442
	function formatSizeUnits($bytes)
	{
		if ($bytes >= 1073741824)
		{
			$bytes = number_format($bytes / 1073741824, 2) . ' GB';
		}
		elseif ($bytes >= 1048576)
		{
			$bytes = number_format($bytes / 1048576, 2) . ' MB';
		}
		elseif ($bytes >= 1024)
		{
			$bytes = number_format($bytes / 1024, 2) . ' kB';
		}
		elseif ($bytes > 1)
		{
			$bytes = $bytes . ' bytes';
		}
		elseif ($bytes == 1)
		{
			$bytes = $bytes . ' byte';
		}
		else
		{
			$bytes = '0 bytes';
		}

		return $bytes;
	}
?>
