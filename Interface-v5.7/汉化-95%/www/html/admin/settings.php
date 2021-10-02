<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
require "scripts/pi-hole/php/header.php";
require "scripts/pi-hole/php/savesettings.php";
require_once "scripts/pi-hole/php/FTL.php";
// Reread ini file as things might have been changed
$setupVars = parse_ini_file("/etc/pihole/setupVars.conf");
$piholeFTLConf = piholeFTLConfig();

// Handling of PHP internal errors
$last_error = error_get_last();
if(isset($last_error) && ($last_error["type"] === E_WARNING || $last_error["type"] === E_ERROR))
{
	$error .= "应用设置时发生了一个错误。<br>调试信息：<br>PHP 错误 (".htmlspecialchars($last_error["type"])."): ".htmlspecialchars($last_error["message"])." in ".htmlspecialchars($last_error["file"]).":".htmlspecialchars($last_error["line"]);
}

?>
<style>
	.tooltip-inner {
		max-width: none;
		white-space: nowrap;
	}
</style>

<?php // Check if ad lists should be updated after saving ...
if (isset($_POST["submit"])) {
    if ($_POST["submit"] == "saveupdate") {
        // If that is the case -> refresh to the gravity page and start updating immediately
        ?>
        <meta http-equiv="refresh" content="1;url=gravity.php?go">
    <?php }
} ?>

<?php if (isset($debug)) { ?>
    <div id="alDebug" class="alert alert-warning alert-dismissible fade in" role="alert">
        <button type="button" class="close" data-hide="alert" aria-label="关闭"><span aria-hidden="true">&times;</span>
        </button>
        <h4><i class="icon fa fa-exclamation-triangle"></i> 调试</h4>
        <pre><?php print_r(htmlentities($_POST)); ?></pre>
    </div>
<?php } ?>

<?php if (strlen($success) > 0) { ?>
    <div id="alInfo" class="alert alert-info alert-dismissible fade in" role="alert">
        <button type="button" class="close" data-hide="alert" aria-label="关闭"><span aria-hidden="true">&times;</span>
        </button>
        <h4><i class="icon fa fa-info"></i> 信息</h4>
        <?php echo $success; ?>
    </div>
<?php } ?>

<?php if (strlen($error) > 0) { ?>
    <div id="alError" class="alert alert-danger alert-dismissible fade in" role="alert">
        <button type="button" class="close" data-hide="alert" aria-label="关闭"><span aria-hidden="true">&times;</span>
        </button>
        <h4><i class="icon fa fa-ban"></i> 错误</h4>
        <?php echo $error; ?>
    </div>
<?php } ?>

<?php
if (isset($setupVars["PIHOLE_INTERFACE"])) {
    $piHoleInterface = $setupVars["PIHOLE_INTERFACE"];
} else {
    $piHoleInterface = "unknown";
}
if (isset($setupVars["IPV4_ADDRESS"])) {
    $piHoleIPv4 = $setupVars["IPV4_ADDRESS"];
} else {
    $piHoleIPv4 = "unknown";
}

// DNS settings
$DNSservers = [];
$DNSactive = [];

$i = 1;
while (isset($setupVars["PIHOLE_DNS_" . $i])) {
    if (isinserverlist($setupVars["PIHOLE_DNS_" . $i])) {
        array_push($DNSactive, $setupVars["PIHOLE_DNS_" . $i]);
    } elseif (strpos($setupVars["PIHOLE_DNS_" . $i], ".") !== false) {
        if (!isset($custom1)) {
            $custom1 = $setupVars["PIHOLE_DNS_" . $i];
        } else {
            $custom2 = $setupVars["PIHOLE_DNS_" . $i];
        }
    } elseif (strpos($setupVars["PIHOLE_DNS_" . $i], ":") !== false) {
        if (!isset($custom3)) {
            $custom3 = $setupVars["PIHOLE_DNS_" . $i];
        } else {
            $custom4 = $setupVars["PIHOLE_DNS_" . $i];
        }
    }
    $i++;
}

if (isset($setupVars["DNS_FQDN_REQUIRED"])) {
    if ($setupVars["DNS_FQDN_REQUIRED"]) {
        $DNSrequiresFQDN = true;
    } else {
        $DNSrequiresFQDN = false;
    }
} else {
    $DNSrequiresFQDN = true;
}

if (isset($setupVars["DNS_BOGUS_PRIV"])) {
    if ($setupVars["DNS_BOGUS_PRIV"]) {
        $DNSbogusPriv = true;
    } else {
        $DNSbogusPriv = false;
    }
} else {
    $DNSbogusPriv = true;
}

if (isset($setupVars["DNSSEC"])) {
    if ($setupVars["DNSSEC"]) {
        $DNSSEC = true;
    } else {
        $DNSSEC = false;
    }
} else {
    $DNSSEC = false;
}

if (isset($setupVars["DNSMASQ_LISTENING"])) {
    if ($setupVars["DNSMASQ_LISTENING"] === "single") {
        $DNSinterface = "single";
    } elseif ($setupVars["DNSMASQ_LISTENING"] === "all") {
        $DNSinterface = "all";
    } else {
        $DNSinterface = "local";
    }
} else {
    $DNSinterface = "single";
}
if (isset($setupVars["REV_SERVER"]) && ($setupVars["REV_SERVER"] == 1)) {
    $rev_server = true;
    $rev_server_cidr   = $setupVars["REV_SERVER_CIDR"];
    $rev_server_target = $setupVars["REV_SERVER_TARGET"];
    $rev_server_domain = $setupVars["REV_SERVER_DOMAIN"];
} else {
    $rev_server = false;
}
?>

<?php
// Query logging
if (isset($setupVars["QUERY_LOGGING"])) {
    if ($setupVars["QUERY_LOGGING"] == 1) {
        $piHoleLogging = true;
    } else {
        $piHoleLogging = false;
    }
} else {
    $piHoleLogging = true;
}
?>

<?php
// Excluded domains in API Query Log call
if (isset($setupVars["API_EXCLUDE_DOMAINS"])) {
    $excludedDomains = explode(",", $setupVars["API_EXCLUDE_DOMAINS"]);
} else {
    $excludedDomains = [];
}

// Excluded clients in API Query Log call
if (isset($setupVars["API_EXCLUDE_CLIENTS"])) {
    $excludedClients = explode(",", $setupVars["API_EXCLUDE_CLIENTS"]);
} else {
    $excludedClients = [];
}

// Excluded clients
if (isset($setupVars["API_QUERY_LOG_SHOW"])) {
    $queryLog = $setupVars["API_QUERY_LOG_SHOW"];
} else {
    $queryLog = "all";
}

// Privacy Mode
if (isset($setupVars["API_PRIVACY_MODE"])) {
    $privacyMode = $setupVars["API_PRIVACY_MODE"];
} else {
    $privacyMode = false;
}

?>

<?php
if (isset($_GET['tab']) && in_array($_GET['tab'], array("sysadmin", "dns", "piholedhcp", "api", "privacy", "teleporter"))) {
    $tab = $_GET['tab'];
} else {
    $tab = "sysadmin";
}
?>
<div class="row">
    <div class="col-md-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation"<?php if($tab === "sysadmin"){ ?> class="active"<?php } ?>>
                    <a href="#sysadmin" aria-controls="sysadmin" aria-expanded="<?php echo $tab === "sysadmin" ? "true" : "false"; ?>" role="tab" data-toggle="tab">系统</a>
                </li>
                <li role="presentation"<?php if($tab === "dns"){ ?> class="active"<?php } ?>>
                    <a href="#dns" aria-controls="dns" aria-expanded="<?php echo $tab === "dns" ? "true" : "false"; ?>" role="tab" data-toggle="tab">DNS</a>
                </li>
                <li role="presentation"<?php if($tab === "piholedhcp"){ ?> class="active"<?php } ?>>
                    <a href="#piholedhcp" aria-controls="piholedhcp" aria-expanded="<?php echo $tab === "piholedhcp" ? "true" : "false"; ?>" role="tab" data-toggle="tab">DHCP</a>
                </li>
                <li role="presentation"<?php if($tab === "api"){ ?> class="active"<?php } ?>>
                    <a href="#api" aria-controls="api" aria-expanded="<?php echo $tab === "api" ? "true" : "false"; ?>" role="tab" data-toggle="tab">API / Web界面</a>
                </li>
                <li role="presentation"<?php if($tab === "privacy"){ ?> class="active"<?php } ?>>
                    <a href="#privacy" aria-controls="privacy" aria-expanded="<?php echo $tab === "privacy" ? "true" : "false"; ?>" role="tab" data-toggle="tab">隐私</a>
                </li>
                <li role="presentation"<?php if($tab === "teleporter"){ ?> class="active"<?php } ?>>
                    <a href="#teleporter" aria-controls="teleporter" aria-expanded="<?php echo $tab === "teleporter" ? "true" : "false"; ?>" role="tab" data-toggle="tab">传送器</a>
                </li>
            </ul>
            <div class="tab-content">
                <!-- ######################################################### System admin ######################################################### -->
                <div id="sysadmin" class="tab-pane fade<?php if($tab === "sysadmin"){ ?> in active<?php } ?>">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box">
                                <div class="box-header with-border">
                                    <h3 class="box-title">FTL信息</h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <?php
                                            if ($FTL) {
                                                function get_FTL_data($arg)
                                                {
                                                    global $FTLpid;
                                                    return trim(exec("ps -p " . $FTLpid . " -o " . $arg));
                                                }

                                                $FTLversion = exec("/usr/bin/pihole-FTL version");
                                            ?>
                                            <table class="table table-striped table-bordered nowrap">
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">FTL版本：</th>
                                                        <td><?php echo $FTLversion; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">进程ID（PID）：</th>
                                                        <td><?php echo $FTLpid; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">FTL启动时间：</th>
                                                        <td><?php print_r(get_FTL_data("lstart")); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">用户/群组：</th>
                                                        <td><?php print_r(get_FTL_data("euser")); ?> / <?php print_r(get_FTL_data("egroup")); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">CPU总利用率：</th>
                                                        <td><?php print_r(get_FTL_data("%cpu")); ?>%</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">内存使用率：</th>
                                                        <td><?php print_r(get_FTL_data("%mem")); ?>%</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <span title="Resident memory is the portion of memory occupied by a process that is held in main memory (RAM). The rest of the occupied memory exists in the swap space or file system.">已用内存：</span>
                                                        </th>
                                                        <td><?php echo formatSizeUnits(1e3 * floatval(get_FTL_data("rss"))); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <span title="Size of the DNS domain cache">DNS缓存大小：</span>
                                                        </th>
                                                        <td id="cache-size">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <span title="Number of cache insertions">DNS缓存注入：</span>
                                                        </th>
                                                        <td id="cache-inserted">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <span title="Number of cache entries that had to be removed although they are not expired (increase cache size to reduce this number)">DNS缓存逐出：</span>
                                                        </th>
                                                        <td id="cache-live-freed">&nbsp;</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            另请参阅我们的<a href="https://docs.pi-hole.net/ftldns/dns-cache/" rel="noopener" target="_blank">DNS缓存</a>说明。
                                            <?php } else { ?>
                                            <div>FTL服务离线！</div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-warning">
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <?php if ($piHoleLogging) { ?>
                                                <button type="button" class="btn btn-warning confirm-disablelogging-noflush btn-block">禁用查询请求的日志记录</button>
                                            <?php } else { ?>
                                                <form role="form" method="post">
                                                    <input type="hidden" name="action" value="Enable">
                                                    <input type="hidden" name="field" value="Logging">
                                                    <input type="hidden" name="token" value="<?php echo $token ?>">
                                                    <button type="submit" class="btn btn-success btn-block">启用查询请求的日志记录</button>
                                                </form>
                                            <?php } ?>
                                        </div>
                                        <p class="hidden-md hidden-lg"></p>
                                        <div class="col-md-4">
                                                <button type="button" class="btn btn-warning confirm-flusharp btn-block">清空客户端列表</button>
                                        </div>
                                        <p class="hidden-md hidden-lg"></p>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-warning confirm-restartdns btn-block">重新启动DNS服务器</button>
                                        </div>
                                    </div>
                                    <br/>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger confirm-flushlogs btn-block">清空日志（最近24小时）</button>
                                        </div>
                                        <p class="hidden-md hidden-lg"></p>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger confirm-poweroff btn-block">关闭主机</button>
                                        </div>
                                        <p class="hidden-md hidden-lg"></p>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger confirm-reboot btn-block">重启主机</button>
                                        </div>
                                    </div>

                                    <form role="form" method="post" id="flushlogsform">
                                        <input type="hidden" name="field" value="flushlogs">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="flusharpform">
                                        <input type="hidden" name="field" value="flusharp">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="disablelogsform-noflush">
                                        <input type="hidden" name="field" value="Logging">
                                        <input type="hidden" name="action" value="Disable-noflush">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="poweroffform">
                                        <input type="hidden" name="field" value="poweroff">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="rebootform">
                                        <input type="hidden" name="field" value="reboot">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="restartdnsform">
                                        <input type="hidden" name="field" value="restartdns">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ######################################################### DHCP ######################################################### -->
                <div id="piholedhcp" class="tab-pane fade<?php if($tab === "piholedhcp"){ ?> in active<?php } ?>">
                    <?php
                    // Pi-hole DHCP server
                    if (isset($setupVars["DHCP_ACTIVE"])) {
                        if ($setupVars["DHCP_ACTIVE"] == 1) {
                            $DHCP = true;
                        } else {
                            $DHCP = false;
                        }
                        // Read settings from config file
                        if (isset($setupVars["DHCP_START"])) {
                            $DHCPstart = $setupVars["DHCP_START"];
                        } else {
                            $DHCPstart = "";
                        }
                        if (isset($setupVars["DHCP_END"])) {
                            $DHCPend = $setupVars["DHCP_END"];
                        } else {
                            $DHCPend = "";
                        }
                        if (isset($setupVars["DHCP_ROUTER"])) {
                            $DHCProuter = $setupVars["DHCP_ROUTER"];
                        } else {
                            $DHCProuter = "";
                        }

                        // This setting has been added later, we have to check if it exists
                        if (isset($setupVars["DHCP_LEASETIME"])) {
                            $DHCPleasetime = $setupVars["DHCP_LEASETIME"];
                            if (strlen($DHCPleasetime) < 1) {
                                // Fallback if empty string
                                $DHCPleasetime = 24;
                            }
                        } else {
                            $DHCPleasetime = 24;
                        }
                        if (isset($setupVars["DHCP_IPv6"])) {
                            $DHCPIPv6 = $setupVars["DHCP_IPv6"];
                        } else {
                            $DHCPIPv6 = false;
                        }
                        if (isset($setupVars["DHCP_rapid_commit"])) {
                            $DHCP_rapid_commit = $setupVars["DHCP_rapid_commit"];
                        } else {
                            $DHCP_rapid_commit = false;
                        }

                    } else {
                        $DHCP = false;
                        // Try to guess initial settings
                        if ($piHoleIPv4 !== "unknown") {
                            $DHCPdomain = explode(".", $piHoleIPv4);
                            $DHCPstart = $DHCPdomain[0] . "." . $DHCPdomain[1] . "." . $DHCPdomain[2] . ".201";
                            $DHCPend = $DHCPdomain[0] . "." . $DHCPdomain[1] . "." . $DHCPdomain[2] . ".251";
                            $DHCProuter = $DHCPdomain[0] . "." . $DHCPdomain[1] . "." . $DHCPdomain[2] . ".1";
                        } else {
                            $DHCPstart = "";
                            $DHCPend = "";
                            $DHCProuter = "";
                        }
                        $DHCPleasetime = 24;
                        $DHCPIPv6 = false;
                        $DHCP_rapid_commit = false;
                    }
                    if (isset($setupVars["PIHOLE_DOMAIN"])) {
                        $piHoleDomain = $setupVars["PIHOLE_DOMAIN"];
                    } else {
                        $piHoleDomain = "lan";
                    }
                    ?>
                    <form role="form" method="post">
                        <div class="row">
                            <!-- DHCP Settings Box -->
                            <div class="col-md-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">DHCP设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div><input type="checkbox" name="active" id="DHCPchk" <?php if ($DHCP){ ?>checked<?php } ?>><label for="DHCPchk"><strong>启动DHCP服务器</strong></label></div><br>
                                                <p id="dhcpnotice" <?php if (!$DHCP){ ?>hidden<?php } ?>>使用 Pi-hole的DHCP服务器时，请先禁用路由器中的DHCP功能！</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12">
                                                <label>分配IP地址范围</label>
                                            </div>
                                            <div class="col-xs-12 col-sm-6 col-md-12 col-lg-6">
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">开始地址：</div>
                                                        <input type="text" class="form-control DHCPgroup" name="from" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                               value="<?php echo $DHCPstart; ?>"
                                                               <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-6 col-md-12 col-lg-6">
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">结束地址：</div>
                                                        <input type="text" class="form-control DHCPgroup" name="to" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                               value="<?php echo $DHCPend; ?>"
                                                               <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>路由器（网关）IP地址</label>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">路由器IP地址：</div>
                                                        <input type="text" class="form-control DHCPgroup" name="router" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                               value="<?php echo $DHCProuter; ?>"
                                                               <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Advanced DHCP Settings Box -->
                            <div class="col-md-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">DHCP高级设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>Pi-hole域名描述</label>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">域名：</div>
                                                        <input type="text" class="form-control DHCPgroup" name="domain"
                                                               value="<?php echo $piHoleDomain; ?>"
                                                               <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>DHCP租约时间</label>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">租约时间（H）</div>
                                                        <input type="number" class="form-control DHCPgroup"
                                                               name="leasetime"
                                                               id="leasetime" value="<?php echo $DHCPleasetime; ?>"
                                                               data-mask <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                                <p>提示：0=永久，24小时=一天，168小时=一周，744小时=一月，8760小时=一年</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                              <div><input type="checkbox" name="DHCP_rapid_commit" id="DHCP_rapid_commit" class="DHCPgroup" <?php if ($DHCP_rapid_commit){ ?>checked<?php }; if (!$DHCP){ ?> disabled<?php } ?>>&nbsp;<label for="DHCP_rapid_commit"><strong>启用DHCPv4快速提交（快速地址分配）</strong></label></div>
                                              <div><input type="checkbox" name="useIPv6" id="useIPv6" class="DHCPgroup" <?php if ($DHCPIPv6){ ?>checked<?php }; if (!$DHCP){ ?> disabled<?php } ?>>&nbsp;<label for="useIPv6"><strong>启用IPv6支持（SLAAC + RA）</strong></label></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DHCP Leases Box -->
                        <div class="row">
                            <?php
                            $dhcp_leases = array();
                            if ($DHCP) {
                                // Read leases file
                                $leasesfile = true;
                                $dhcpleases = @fopen('/etc/pihole/dhcp.leases', 'r');
                                if (!is_resource($dhcpleases))
                                    $leasesfile = false;

                                function convertseconds($argument)
                                {
                                    $seconds = round($argument);
                                    if ($seconds < 60) {
                                        return sprintf('%ds', $seconds);
                                    } elseif ($seconds < 3600) {
                                        return sprintf('%dm %ds', ($seconds / 60), ($seconds % 60));
                                    } elseif ($seconds < 86400) {
                                        return sprintf('%dh %dm %ds', ($seconds / 3600 % 24), ($seconds / 60 % 60), ($seconds % 60));
                                    } else {
                                        return sprintf('%dd %dh %dm %ds', ($seconds / 86400), ($seconds / 3600 % 24), ($seconds / 60 % 60), ($seconds % 60));
                                    }
                                }

                                while (!feof($dhcpleases) && $leasesfile) {
                                    $line = explode(" ", trim(fgets($dhcpleases)));
                                    if (count($line) == 5) {
                                        $counter = intval($line[0]);
                                        if ($counter == 0) {
                                            $time = "Infinite";
                                        } elseif ($counter <= 315360000) // 10 years in seconds
                                        {
                                            $time = convertseconds($counter);
                                        } else // Assume time stamp
                                        {
                                            $time = convertseconds($counter - time());
                                        }

                                        if (strpos($line[2], ':') !== false) {
                                            // IPv6 address
                                            $type = 6;
                                        } else {
                                            // IPv4 lease
                                            $type = 4;
                                        }

                                        $host = htmlentities($line[3]);
                                        if ($host == "*") {
                                            $host = "<i>unknown</i>";
                                        }

                                        $clid = $line[4];
                                        if ($clid == "*") {
                                            $clid = "<i>unknown</i>";
                                        }

                                        array_push($dhcp_leases, ["TIME" => $time, "hwaddr" => strtoupper($line[1]), "IP" => $line[2], "host" => $host, "clid" => $clid, "type" => $type]);
                                    }
                                }
                            }

                            readStaticLeasesFile();
                            ?>
                            <div class="col-md-12">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">DHCP服务</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>DHCP客户端列表</label>
                                                <table id="DHCPLeasesTable" class="table table-striped table-bordered nowrap" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>MAC地址</th>
                                                            <th>IP地址</th>
                                                            <th>主机名称</th>
                                                            <td></td>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($dhcp_leases as $lease) { ?>
                                                        <tr data-placement="auto" data-container="body" data-toggle="tooltip"
                                                            title="租约类型： IPv<?php echo $lease["type"]; ?><br/>剩余租约时间：<?php echo $lease["TIME"]; ?><br/>DHCP UID: <?php echo $lease["clid"]; ?>">
                                                            <td id="MAC"><?php echo $lease["hwaddr"]; ?></td>
                                                            <td id="IP" data-order="<?php echo bin2hex(inet_pton($lease["IP"])); ?>"><?php echo $lease["IP"]; ?></td>
                                                            <td id="HOST"><?php echo $lease["host"]; ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-danger btn-xs" id="removedynamic">
                                                                    <span class="fas fas fa-trash-alt"></span>
                                                                </button>
                                                                <button type="button" id="button" class="btn btn-warning btn-xs" data-static="alert">
                                                                    <span class="fas fas fa-file-import"></span>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                                <br>
                                            </div>
                                            <div class="col-md-12">
                                                <label>DHCP静态地址分配</label>
                                                <table id="DHCPStaticLeasesTable" class="table table-striped table-bordered nowrap" width="100%">
                                                    <thead>
                                                    <tr>
                                                        <th>MAC地址</th>
                                                        <th>IP地址</th>
                                                        <th>主机名称</th>
                                                        <td></td>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($dhcp_static_leases as $lease) { ?>
                                                        <tr>
                                                            <td><?php echo $lease["hwaddr"]; ?></td>
                                                            <td data-order="<?php echo bin2hex(inet_pton($lease["IP"])); ?>"><?php echo $lease["IP"]; ?></td>
                                                            <td><?php echo htmlentities($lease["host"]); ?></td>
                                                            <td><?php if (strlen($lease["hwaddr"]) > 0) { ?>
                                                                <button type="submit" class="btn btn-danger btn-xs" name="removestatic"
                                                                        value="<?php echo $lease["hwaddr"]; ?>">
                                                                    <span class="far fa-trash-alt"></span>
                                                                </button>
                                                                <?php } ?>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                    <tfoot style="display: table-row-group">
                                                        <tr>
                                                            <td><input type="text" class="form-group" name="AddMAC" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"></td>
                                                            <td><input type="text" class="form-group" name="AddIP" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"></td>
                                                            <td><input type="text" class="form-group" name="AddHostname" value="" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"></td>
                                                            <td>
                                                                <button type="submit" class="btn btn-success btn-xs" name="addstatic">
                                                                    <span class="fas fa-plus"></span>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                                <p>指定的MAC地址具有强制性且每个MAC地址不允许重复设置。如果只设定主机名称不设定IP地址，则会使用指定的主机名并生成动态IP地址进行分配。如果不设定主机名称，则会采用静态地址分配。</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="field" value="DHCP">
                                <input type="hidden" name="token" value="<?php echo $token ?>">
                                <button type="submit" class="btn btn-primary pull-right">保存</button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- ######################################################### DNS ######################################################### -->
                <div id="dns" class="tab-pane fade<?php if($tab === "dns"){ ?> in active<?php } ?>">
                    <form role="form" method="post">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h1 class="box-title">上游DNS服务器</h1>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th colspan="2">IPv4</th>
                                                        <th colspan="2">IPv6</th>
                                                        <th>名称</th>
                                                    </tr>
                                                    <?php foreach ($DNSserverslist as $key => $value) { ?>
                                                    <tr>
                                                    <?php if (isset($value["v4_1"])) { ?>
                                                        <td title="<?php echo $value["v4_1"]; ?>">
                                                            <div><input type="checkbox" name="DNSserver<?php echo $value["v4_1"]; ?>" id="DNS4server<?php echo $value["v4_1"]; ?>" value="true" <?php if (in_array($value["v4_1"], $DNSactive)){ ?>checked<?php } ?>><label for="DNS4server<?php echo $value["v4_1"]; ?>"></label></div>
                                                        </td>
                                                    <?php } else { ?>
                                                        <td></td>
                                                    <?php } ?>
                                                    <?php if (isset($value["v4_2"])) { ?>
                                                        <td title="<?php echo $value["v4_2"]; ?>">
                                                            <div><input type="checkbox" name="DNSserver<?php echo $value["v4_2"]; ?>" id="DNS4server<?php echo $value["v4_2"]; ?>" value="true" <?php if (in_array($value["v4_2"], $DNSactive)){ ?>checked<?php } ?>><label for="DNS4server<?php echo $value["v4_2"]; ?>"></label></div>
                                                        </td>
                                                    <?php } else { ?>
                                                        <td></td>
                                                    <?php } ?>
                                                    <?php if (isset($value["v6_1"])) { ?>
                                                        <td title="<?php echo $value["v6_1"]; ?>">
                                                            <div><input type="checkbox" name="DNSserver<?php echo $value["v6_1"]; ?>" id="DNS6server<?php echo $value["v6_1"]; ?>" value="true" <?php if (in_array($value["v6_1"], $DNSactive)){ ?>checked<?php } ?>><label for="DNS6server<?php echo $value["v6_1"]; ?>"></label></div>
                                                        </td>
                                                    <?php } else { ?>
                                                        <td></td>
                                                    <?php } ?>
                                                    <?php if (isset($value["v6_2"])) { ?>
                                                        <td title="<?php echo $value["v6_2"]; ?>">
                                                            <div><input type="checkbox" name="DNSserver<?php echo $value["v6_2"]; ?>" id="DNS6server<?php echo $value["v6_2"]; ?>" value="true" <?php if (in_array($value["v6_2"], $DNSactive)){ ?>checked<?php } ?>><label for="DNS6server<?php echo $value["v6_2"]; ?>"></label></div>
                                                        </td>
                                                    <?php } else { ?>
                                                        <td></td>
                                                    <?php } ?>
                                                        <td><?php echo $key; ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                </table>
                                                <p>ECS（扩展客户端子网）定义了递归解析器将部分客户端IP地址信息发送到权威DNS名称服务器的机制。 内容分发网络（CDNs）和延迟敏感的服务在响应来自公共DNS解析器的名称查询时，使用它提供的地理定位响应。 <em>请注意，ECS 可能会导致隐私性降低。</em></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h1 class="box-title">上游DNS服务器</h1>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>自定义 1 (IPv4)</strong>
                                                <div class="row">
                                                    <div class="col-md-1"><div>
                                                        <input type="checkbox" name="custom1" id="custom1" value="Customv4" <?php if (isset($custom1)){ ?>checked<?php } ?>>
                                                        <label for="custom1"></label></div>
                                                    </div>
                                                    <div class="col-md-11">
                                                        <input type="text" name="custom1val" class="form-control" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                                <?php if (isset($custom1)){ ?>value="<?php echo $custom1; ?>"<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>自定义 2 (IPv4)</strong>
                                                <div class="row">
                                                    <div class="col-md-1"><div>
                                                        <input type="checkbox" name="custom2" id="custom2" value="Customv4" <?php if (isset($custom2)){ ?>checked<?php } ?>>
                                                        <label for="custom2"></label></div>
                                                    </div>
                                                    <div class="col-md-11">
                                                        <input type="text" name="custom2val" class="form-control" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                               <?php if (isset($custom2)){ ?>value="<?php echo $custom2; ?>"<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>自定义 3 (IPv6)</strong>
                                                <div class="row">
                                                    <div class="col-md-1"><div>
                                                        <input type="checkbox" name="custom3" id="custom3" value="Customv6" <?php if (isset($custom3)){ ?>checked<?php } ?>>
                                                        <label for="custom3"></label></div>
                                                    </div>
                                                    <div class="col-md-11">
                                                        <input type="text" name="custom3val" class="form-control" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                                <?php if (isset($custom3)){ ?>value="<?php echo $custom3; ?>"<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>自定义 4 (IPv6)</strong>
                                                <div class="row">
                                                    <div class="col-md-1"><div>
                                                        <input type="checkbox" name="custom4" id="custom4" value="Customv6" <?php if (isset($custom4)){ ?>checked<?php } ?>>
                                                        <label for="custom4"></label></div>
                                                    </div>
                                                    <div class="col-md-11">
                                                        <input type="text" name="custom4val" class="form-control" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                               <?php if (isset($custom4)){ ?>value="<?php echo $custom4; ?>"<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h1 class="box-title">接口监听行为</h1>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="form-group">
                                                    <div>
                                                        <input type="radio" name="DNSinterface" id="DNSinterface1" value="local"
                                                                <?php if ($DNSinterface == "local"){ ?>checked<?php } ?>>
                                                        <label for="DNSinterface1"><strong>监听所有接口</strong><br>仅允许来自最多一跳的设备（本地设备）的查询请求</label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="DNSinterface" id="DNSinterface2" value="single"
                                                                <?php if ($DNSinterface == "single"){ ?>checked<?php } ?>>
                                                        <label for="DNSinterface2"><strong>只<?php echo htmlentities($piHoleInterface); ?>接口</strong></label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="DNSinterface" id="DNSinterface3" value="all"
                                                                <?php if ($DNSinterface == "all"){ ?>checked<?php } ?>>
                                                        <label for="DNSinterface3"><strong>监听所有接口，放行所有来源</strong></label>
                                                    </div>
                                                </div>
                                                <p>请注意，最后一个选项不应在直接连接到 Internet 的设备上使用。如果您的 Pi-hole 位于本地网络中，即在路由器后面受到保护，并且尚未将端口 53 转发到此设备，则此选项是安全的。在几乎所有其它情况下，您都必须确保对 Pi-hole 设置正确的防火墙进行保护。</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">DNS高级设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div>
                                                    <input type="checkbox" name="DNSrequiresFQDN" id="DNSrequiresFQDN" title="domain-needed" <?php if ($DNSrequiresFQDN){ ?>checked<?php } ?>>
                                                    <label for="DNSrequiresFQDN"><strong>从不转发non-FQDNs <code>A</code> 和 <code>AAAA</code>类型的查询请求</strong></label>
                                                    <p>当设置了Pi-hole域名并且勾选此框时，这会询问FTL该域名是纯本地的，FTL可以根据<code>/etc/hosts</code>或 DHCP 租约回应查询请求，但不会将该域名的查询请求转发到任何上游服务器。
                                                    如果启用了条件转发，在某些情况下（例如：如果客户端将发送 TLD DNSSEC 查询请求）不勾选此框可能会导致部分 DNS 循环。</p>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="DNSbogusPriv" id="DNSbogusPriv" title="bogus-priv" <?php if ($DNSbogusPriv){ ?>checked<?php } ?>>
                                                    <label for="DNSbogusPriv"><strong>从不向上游发送私有IP范围的反向查询</strong></label>
                                                    <p>在<code>/etc/hosts</code>或DHCP中找不到的私有IP范围（即<code>192.168.0.x/24</code>等）的所有反向查询都用“没有这个域名”回应，而不是转发到上游。受影响的前缀集是<a href="https://tools.ietf.org/html/rfc6303">RFC6303</a>中给出的列表。</p>
                                                    <p><strong>重要提示：</strong>启用这两个选项可能会增加您的隐私性，但如果Pi-hole未用作DHCP服务器，则可能会阻止您访问本地主机。</p>
                                                </div>
                                                <br>
                                                <div>
                                                    <input type="checkbox" name="DNSSEC" id="DNSSEC" <?php if ($DNSSEC){ ?>checked<?php } ?>>
                                                    <label for="DNSSEC"><strong>使用DNSSEC</strong></label>
                                                    <p>验证DNS回应并缓存DNSSEC数据。在转发DNS查询请求时，Pi-hole 会请求验证回应所需的DNSSEC记录。如果域名验证失败或上游不支持 DNSSEC，则此设置可能导致解析域名时出现问题。在启用DNSSEC 时使用支持DNSSEC的上游DNS服务器。请注意，启用DNSSEC 后，您的日志大小可能会显著增加。您可以在
                                                    <a href="https://dnssec.vs.uni-due.de/" rel="noopener" target="_blank">DNSSEC 解析器测试</a>进行测试。</p>
                                                </div>
                                                <br>
                                                <h4>条件转发</h4>
                                                <p>如果未配置为DHCP服务器，Pi-hole通常无法确定本地网络上的设备名称。 因此，客户端统计等统计表将仅显示IP地址。</p>
                                                <p>一种解决方案是配置Pi-hole把这些请求转发到您的DHCP服务器（很可能是您的路由器），但仅限于您家庭网络上的设备。要进行配置，我们需要知道您的 DHCP 服务器的 IP 地址以及哪些地址属于您的本地网络。
												可以参考下面的输入框（如果为空）中显示的提示文本。</p>
                                                <p>1、如果您的本地网络IP地址范围是192.168.0.1-192.168.0.255，在“本地网络范围”中填入<code>192.168.0.0/24</code>。
												<p>2、如果您的本地网络IP地址范围是192.168.47.1-192.168.47.255，在“本地网络范围”中填入<code>192.168.47.0/24</code>，如此类推。<p>
												<p>3、如果您的本地网络IP地址范围更大，则与上述1、2点不同，例如10.8.0.1-10.8.255.255 ，则在“本地网络范围”中填入<code>10.8.0.0/16</code>，而更广泛的IP地址范围，如10.0.0.1-10.255.255.255则在“本地网络范围”中填入<code>10.0.0.0/8</code>。<p>If your local network is 192.168.47.1 - 192.168.47.255, it will
												<p>设置IPv6地址范围与上述设置IPv4地址的方式完全相同。如果您需要任何帮助为您的特定系统设置本地主机名解析，请随时在我们的<a href="https://discourse.pi-hole.net" target="_blank">Discourse论坛</a>上与我们联系。</p>
                                                <p>您还可以指定本地域名（如<code>fritz.box</code>）来确保设备的查询请求从始至终都不会您的网络。本地域名必须与您的DHCP 服务器中指定的域名匹配才能正常工作。您可以在DHCP设置中找到该设置，这是可选的</p>
                                                <p>当“从不转发non-FQDNs”<em>未</em>启用时，启用条件转发也会将所有主机名（即non-FQDNs）转发到路由器。</p>
                                                <div class="form-group">
                                                    <div>
                                                        <input type="checkbox" name="rev_server" id="rev_server" value="rev_server" <?php if(isset($rev_server) && ($rev_server == true)){ ?>checked<?php } ?>>
                                                        <label for="rev_server"><strong>使用条件转发</strong></label>
                                                    </div>
                                                    <div class="input-group">
                                                      <table class="table table-bordered">
                                                        <tr>
                                                          <th>本地网络范围<a href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" target="_blank">（CIDR形式）</a></th>
                                                          <th>DHCP 服务器的 IP 地址（路由器）</th>
                                                          <th>本地域名（可选）</th>
                                                        </tr>
                                                        <tr>
                                                          <td>
                                                            <input type="text" name="rev_server_cidr" placeholder="192.168.0.0/16" class="form-control" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                            <?php if(isset($rev_server_cidr)){ ?>value="<?php echo $rev_server_cidr; ?>"<?php } ?>
                                                            <?php if(!isset($rev_server) || !$rev_server){ ?>disabled<?php } ?>>
                                                          </td>
                                                          <td>
                                                            <input type="text" name="rev_server_target" placeholder="192.168.0.1" class="form-control" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                            <?php if(isset($rev_server_target)){ ?>value="<?php echo $rev_server_target; ?>"<?php } ?>
                                                            <?php if(!isset($rev_server) || !$rev_server){ ?>disabled<?php } ?>>
                                                          </td>
                                                          <td>
                                                            <input type="text" name="rev_server_domain" placeholder="本地域名" class="form-control" data-mask autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                            <?php if(isset($rev_server_domain)){ ?>value="<?php echo $rev_server_domain; ?>"<?php } ?>
                                                            <?php if(!isset($rev_server) || !$rev_server){ ?>disabled<?php } ?>>
                                                          </td>
                                                        </tr>
                                                      </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="field" value="DNS">
                                <input type="hidden" name="token" value="<?php echo $token ?>">
                                <button type="submit" class="btn btn-primary pull-right">保存</button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- ######################################################### API and Web ######################################################### -->
                <?php
                // Administrator email address
                if (isset($setupVars["ADMIN_EMAIL"])) {
                    $adminemail = $setupVars["ADMIN_EMAIL"];
                } else {
                    $adminemail = "";
                }
                ?>
                <div id="api" class="tab-pane fade<?php if($tab === "api"){ ?> in active<?php } ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <form role="form" method="post">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">API设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4>统计表设置</h4>
                                                <p>在统计表中隐藏以下域名或客户端</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-6 col-md-12 col-lg-6">
                                                <div class="form-group">
                                                    <label>域名</label>
                                                    <textarea name="domains" class="form-control" placeholder="每行输入一个域名" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                              rows="4"><?php foreach ($excludedDomains as $domain) {
                                                                             echo $domain . "\n"; }
                                                                       ?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-6 col-md-12 col-lg-6">
                                                <div class="form-group">
                                                    <label>客户端</label>
                                                    <textarea name="clients" class="form-control" placeholder="每行输入一个IP地址或主机名称" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                                                              rows="4"><?php foreach ($excludedClients as $client) {
                                                                             echo $client . "\n"; }
                                                                       ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                            <h4>查询请求日志</h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div>
                                                    <input type="checkbox" name="querylog-permitted" id="querylog-permitted" <?php if($queryLog === "permittedonly" || $queryLog === "all"){ ?>checked<?php } ?>>
                                                    <label for="querylog-permitted"><strong>显示放行的域名</strong></label>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div>
                                                <input type="checkbox" name="querylog-blocked" id="querylog-blocked" <?php if($queryLog === "blockedonly" || $queryLog === "all"){ ?>checked<?php } ?>>
                                                <label for="querylog-blocked"><strong>显示吞噬的域名</strong></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-footer clearfix">
                                        <input type="hidden" name="field" value="API">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                        <button type="button" class="btn btn-primary api-token">显示API令牌</button>
                                        <button type="submit" class="btn btn-primary pull-right">保存</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form role="form" method="post">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Web界面设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4>网页界面外观</h4>
                                                <?php theme_selection(); ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div>
                                                    <input type="checkbox" name="boxedlayout" id="boxedlayout" value="yes" <?php if ($boxedlayout){ ?>checked<?php } ?>>
                                                    <label for="boxedlayout"><strong>使用磁贴式布局（适用于大屏幕）</strong></label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4>管理员E-Mail地址</h4>
                                                <input type="email" class="form-control" name="adminemail" value="<?php echo htmlspecialchars($adminemail); ?>">
                                                <input type="hidden" name="field" value="webUI">
                                                <input type="hidden" name="token" value="<?php echo $token ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-footer clearfix">
                                        <button type="submit" class="btn btn-primary pull-right">保存</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="box box-warning">
                                <div class="box-header with-border">
                                    <h3 class="box-title">样式（自动保存，每个浏览器单独设置）</h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p>复选框和单选按钮</p>
                                        </div>
                                        <div class="col-md-6">
                                            <select id="iCheckStyle">
                                                <option>默认</option>
                                                <option>primary</option>
                                                <option>success</option>
                                                <option>info</option>
                                                <option>warning</option>
                                                <option>danger</option>
                                                <option>turquoise</option>
                                                <option>emerland</option>
                                                <option>peterriver</option>
                                                <option>amethyst</option>
                                                <option>wetasphalt</option>
                                                <option>greensea</option>
                                                <option>nephritis</option>
                                                <option>belizehole</option>
                                                <option>wisteria</option>
                                                <option>midnightblue</option>
                                                <option>sunflower</option>
                                                <option>carrot</option>
                                                <option>alizarin</option>
                                                <option>clouds</option>
                                                <option>concrete</option>
                                                <option>orange</option>
                                                <option>pumpkin</option>
                                                <option>pomegranate</option>
                                                <option>silver</option>
                                                <option>asbestos</option>

                                                <option>材质-红色</option>
                                                <option>材质-粉红色</option>
                                                <option>材质-紫色</option>
                                                <option>材质-深紫色</option>
                                                <option>材质-靛蓝色</option>
                                                <option>材质-蓝色</option>
                                                <option>材质-浅蓝色</option>
                                                <option>材质-青色</option>
                                                <option>材质-蓝绿色</option>
                                                <option>材质-绿色</option>
                                                <option>材质-浅绿色</option>
                                                <option>材质-柠檬色</option>
                                                <option>材质-黄色</option>
                                                <option>材质-琥珀色</option>
                                                <option>材质-橙色</option>
                                                <option>材质-深橙色</option>
                                                <option>材质-棕色</option>
                                                <option>材质-灰色</option>
                                                <option>材质-蓝灰色</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p>CPU温度单位</p>
                                        </div>
                                        <div class="col-md-6">
                                            <select id="tempunit-selector">
                                                <option value="C">°C（摄氏度）</option>
                                                <option value="K">K（绝对温度）</option>
                                                <option value="F">°F（华氏度）</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div>
                                                <input type="checkbox" name="bargraphs" id="bargraphs" value="yes">
                                                <label for="bargraphs"><strong>在运行状态页面上使用新的条形图</strong></label>
                                            </div>
                                        </div>
                                      </div>
                                      <div class="row">
                                        <div class="col-md-12">
                                            <div>
                                                <input type="checkbox" name="colorfulQueryLog" id="colorfulQueryLog" value="no">
                                                <label for="colorfulQueryLog"><strong>多彩查询日志</strong></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ######################################################### Privacy (may be expanded further later on) ######################################################### -->
                <?php
                // Get privacy level from piholeFTL config array
                if (isset($piholeFTLConf["PRIVACYLEVEL"])) {
                    $privacylevel = intval($piholeFTLConf["PRIVACYLEVEL"]);
                } else {
                    $privacylevel = 0;
                }
                ?>
                <div id="privacy" class="tab-pane fade<?php if($tab === "privacy"){ ?> in active<?php } ?>">
                    <div class="row">
                        <div class="col-md-12">
                            <form role="form" method="post">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">隐私设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4>DNS 解析器隐私级别</h4>
                                                <p>指定 DNS 查询是否应该匿名，可用选项有：</p>
                                                <div>
                                                    <input type="radio" name="privacylevel" id="privacylevel_0" value="0" <?php if ($privacylevel === 0){ ?>checked<?php } ?>>
                                                    <label for="privacylevel_0"><strong>显示一切并记录一切</strong></label>
                                                    <p>提供最详尽的统计数据</p>
                                                </div>
                                                <div>
                                                    <input type="radio" name="privacylevel" id="privacylevel_1" value="1" <?php if ($privacylevel === 1){ ?>checked<?php } ?>>
                                                    <label for="privacylevel_1"><strong>隐藏域名：将所有域名显示和存储为“隐藏”</strong></label>
                                                    <p>这将禁用运行状态页面上的域名统计和广告统计</p>
                                                </div>
                                                <div>
                                                    <input type="radio" name="privacylevel" id="privacylevel_2" value="2" <?php if ($privacylevel === 2){ ?>checked<?php } ?>>
                                                    <label for="privacylevel_2"><strong>隐藏域名和客户端：显示和存储所有域名为“隐藏”，所有客户端为“0.0.0.0”</strong></label>
                                                    <p>这将禁用运行状态页面上的所有统计</p>
                                                </div>
                                                <div>
                                                    <input type="radio" name="privacylevel" id="privacylevel_3" value="3" <?php if ($privacylevel === 3){ ?>checked<?php } ?>>
                                                    <label for="privacylevel_3"><strong>匿名模式：这基本上禁用了除实时匿名统计之外的所有内容</strong></label>
                                                    <p>不会保存历史记录到数据库中，查询日志中也不会显示任何内容。此外，也不会有统计数据表。</p>
                                                </div>
                                                <p>可以随时提高隐私级别，而无需重新启动DNS解析器。但是请注意，降低隐私级别时需要重新启动DNS解析器。保存时会自动重新启动。</p>
                                                <?php if($privacylevel > 0 && $piHoleLogging){ ?>
                                                <p class="lookatme">警告：Pi-hole 的查询请求记录功能已启用。尽管运行状态页面将隐藏请求的详细信息，但所有查询请求仍会完全记录到 pihole.log 文件中。</p>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-footer clearfix">
                                        <input type="hidden" name="field" value="privacyLevel">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                        <button type="submit" class="btn btn-primary pull-right">应用</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- ######################################################### Teleporter ######################################################### -->
                <div id="teleporter" class="tab-pane fade<?php if($tab === "teleporter"){ ?> in active<?php } ?>">
                    <div class="row">
                        <?php if (extension_loaded('Phar')) { ?>
                        <form role="form" method="post" id="takeoutform"
                              action="scripts/pi-hole/php/teleporter.php"
                              target="_blank" enctype="multipart/form-data">
                            <input type="hidden" name="token" value="<?php echo $token ?>">
                            <div class="col-lg-6 col-md-12">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">备份</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <p>将您的 Pi-hole 配置（设置&amp;列表）备份为可下载的存档</p>
                                                <button type="submit" class="btn btn-default">备份</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">恢复</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <label>选择恢复内容...</label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div>
                                                    <input type="checkbox" name="whitelist" id="tele_whitelist" value="true" checked>
                                                    <label for="tele_whitelist">白名单（精确）</label>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="regex_whitelist" id="tele_regex_whitelist" value="true" checked>
                                                    <label for="tele_regex_whitelist">白名单（正则表达式/通配符）</label>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="blacklist" id="tele_blacklist" value="true" checked>
                                                    <label for="tele_blacklist">黑名单（精确）</label>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="regexlist" id="tele_regexlist" value="true" checked>
                                                    <label for="tele_regexlist">黑名单（正则表达式/通配符）</label>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="adlist" id="tele_adlist" value="true" checked>
                                                    <label for="tele_adlist">引力场（广告吞噬规则）</label>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div>
                                                    <input type="checkbox" name="client" id="tele_client" value="true" checked>
                                                    <label for="tele_client">客户端</label>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="group" id="tele_group" value="true" checked>
                                                    <label for="tele_group">群组</label>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="auditlog" id="tele_auditlog" value="true" checked>
                                                    <label for="tele_auditlog">审核日志</label>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="staticdhcpleases" id="tele_staticdhcpleases" value="true" checked>
                                                    <label for="tele_staticdhcpleases">DHCP静态地址分配</label>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="localdnsrecords" id="tele_localdnsrecords" value="true" checked>
                                                    <label for="tele_localdnsrecords">本地DNS映射</label>
                                                </div>
                                                <div>
                                                    <input type="checkbox" name="localcnamerecords" id="tele_localcnamerecords" value="true" checked>
                                                    <label for="tele_localcnamerecords">本地CNAME映射</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <label for="zip_file">上传文件</label>
                                                <input type="file" name="zip_file" id="zip_file">
                                                <p class="help-block">仅支持上传 Pi-hole 备份文件。</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div>
                                                    <input type="checkbox" name="flushtables" id="tele_flushtables" value="true" checked>
                                                    <label for="tele_flushtables">覆盖现有数据</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-footer clearfix">
                                        <button type="submit" class="btn btn-default" name="action" value="in">恢复</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php } else { ?>
                        <div class="col-lg-12">
                            <div class="box box-warning">
                                <div class="box-header with-border">
                                    <h3 class="box-title">传送器</h3>
                                </div>
                                <div class="box-body">
                                    <p>未加载PHP扩展<code>Phar</code>模块。如果您想使用 Pi-hole传送器，请确保已安装并加载该模块。</p>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="scripts/vendor/jquery.confirm.min.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/utils.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/settings.js?v=<?=$cacheVer?>"></script>

<?php
require "scripts/pi-hole/php/footer.php";
?>
