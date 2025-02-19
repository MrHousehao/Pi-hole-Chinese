    <!-- Left side column. contains the logo and sidebar -->
    <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel">
                <div class="pull-left image">
                    <img src="img/logo.svg" alt="Pi-hole logo">
                </div>
                <div class="pull-left info">
                    <p>状态</p>
                    <?php
                    $pistatus = piholeStatus();
                    if ($pistatus == 53) {
                        echo '<span id="status"><i class="fa fa-w fa-circle text-green-light"></i>激活</span>';
                    } elseif ($pistatus == 0) {
                        echo '<span id="status"><i class="fa fa-w fa-circle text-red"></i>离线</span>';
                    } elseif ($pistatus == -1) {
                        echo '<span id="status"><i class="fa fa-w fa-circle text-red"></i> DNS 服务器未运行</span>';
                    } elseif ($pistatus == -2) {
                        echo '<span id="status"><i class="fa fa-w fa-circle text-red"></i>未知</span>';
                    } else {
                        echo '<span id="status"><i class="fa fa-w fa-circle text-orange"></i> DNS 服务器端口'.$pistatus.'</span>';
                    }
                    ?>
                    <br/>
                    <?php
                    echo '<span title="检测到 '.$nproc.' 个核心"><i class="fa fa-w fa-circle ';
                    if ($loaddata[0] > $nproc) {
                        echo 'text-red';
                    } else {
                        echo 'text-green-light';
                    }
                    echo '"></i>负载：&nbsp;&nbsp;'.$loaddata[0].'&nbsp;&nbsp;'.$loaddata[1].'&nbsp;&nbsp;'.$loaddata[2].'</span>';
                    ?>
                    <br/>
                    <?php
                    echo '<span><i class="fa fa-w fa-circle ';
                    if ($memory_usage > 0.75 || $memory_usage < 0.0) {
                        echo 'text-red';
                    } else {
                        echo 'text-green-light';
                    }
                    if ($memory_usage > 0.0) {
                        echo '"></i>内存使用：&nbsp;&nbsp;'.sprintf('%.1f', 100.0 * $memory_usage).'&thinsp;%</span>';
                    } else {
                        echo '"></i>内存使用：&nbsp;&nbsp; 无数据</span>';
                    }
                    ?>
                    <br/>
                    <?php
                    if ($celsius >= -273.15) {
                        // Only show temp info if any data is available -->
                        $tempcolor = 'text-vivid-blue';
                        if (isset($temperaturelimit) && $celsius > $temperaturelimit) {
                            $tempcolor = 'text-red';
                        }
                        echo '<span id="temperature"><i class="fa fa-w fa-fire '.$tempcolor.'" style="width: 1em !important"></i> ';
                        echo '温度：&nbsp;<span id="rawtemp" hidden>'.$celsius.'</span>';
                        echo '<span id="tempunit" hidden>'.$temperatureunit.'</span>';
                        echo '<span id="tempdisplay"></span></span>';
                    }
                    ?>
                </div>
            </div>
            <!-- sidebar menu: : style can be found in sidebar.less -->
            <ul class="sidebar-menu" data-widget="tree">
                <li class="header text-uppercase">导航栏</li>
                <!-- Home Page -->
                <li class="menu-main<?php if ($scriptname === 'index.php') { ?> active<?php } ?>">
                    <a href="index.php">
                        <i class="fa fa-fw menu-icon fa-home"></i> <span>运行状态</span>
                    </a>
                </li>

                <li class="header text-uppercase">分析</li>
                <!-- Query Log -->
                <li class="menu-analysis<?php if ($scriptname === 'queries.php') { ?> active<?php } ?>">
                    <a href="queries.php">
                        <i class="fa fa-fw menu-icon fa-file-alt"></i> <span>查询请求日志</span>
                    </a>
                </li>
                <!-- Long-term database -->
                <li class="menu-analysis treeview<?php if ($scriptname === 'db_queries.php' || $scriptname === 'db_lists.php' || $scriptname === 'db_graph.php') { ?> active<?php } ?>">
                    <a href="#">
                        <i class="fa fa-fw menu-icon fa-history"></i> <span>数据库</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php if ($scriptname === 'db_graph.php') { ?> active<?php } ?>">
                            <a href="db_graph.php">
                                <i class="fa fa-fw menu-icon fa-chart-bar"></i>图形统计
                            </a>
                        </li>
                        <li class="<?php if ($scriptname === 'db_queries.php') { ?> active<?php } ?>">
                            <a href="db_queries.php">
                                <i class="fa fa-fw menu-icon fa-file-alt"></i>查询请求日志
                            </a>
                        </li>
                        <li class="<?php if ($scriptname === 'db_lists.php') { ?> active<?php } ?>">
                            <a href="db_lists.php">
                                <i class="fa fa-fw menu-icon fa-list"></i>统计表
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="header text-uppercase">群组管理</li>
                <!-- Group Management -->
                <li class="menu-group<?php if ($scriptname === 'groups.php') { ?> active<?php } ?>">
                    <a href="groups.php">
                        <i class="fa fa-fw menu-icon fa-user-friends"></i> <span>群组</span>
                    </a>
                </li>
                <li class="menu-group<?php if ($scriptname === 'groups-clients.php') { ?> active<?php } ?>">
                    <a href="groups-clients.php">
                        <i class="fa fa-fw menu-icon fa-laptop"></i> <span>客户端</span>
                    </a>
                </li>
                <li class="menu-group<?php if ($scriptname === 'groups-domains.php') { ?> active<?php } ?>">
                    <a href="groups-domains.php">
                        <i class="fa fa-fw menu-icon fa-list"></i> <span>域名</span>
                    </a>
                </li>
                <li class="menu-group<?php if ($scriptname === 'groups-adlists.php') { ?> active<?php } ?>">
                    <a href="groups-adlists.php">
                        <i class="fa fa-fw menu-icon fa-shield-alt"></i> <span>引力场</span>
                    </a>
                </li>

                <li class="header text-uppercase">DNS 控制</li>
                <!-- Local DNS Records -->
                <!-- Enable/Disable Blocking -->
                <li id="pihole-disable" class="menu-dns treeview"<?php if ($pistatus == '0') { ?> hidden<?php } ?>>
                    <a href="#">
                        <i class="fa fa-fw menu-icon fa-stop"></i> <span>停用&nbsp;&nbsp;&nbsp;<span id="flip-status-disable"></span></span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li>
                            <a href="#" id="pihole-disable-indefinitely">
                                <i class="fa fa-fw menu-icon fa-infinity"></i> 永久
                            </a>
                        </li>
                        <li>
                            <a href="#" id="pihole-disable-10s">
                                <i class="fa fa-fw menu-icon fa-clock"></i>10 秒
                            </a>
                        </li>
                        <li>
                            <a href="#" id="pihole-disable-30s">
                                <i class="fa fa-fw menu-icon fa-clock"></i>30 秒
                            </a>
                        </li>
                        <li>
                            <a href="#" id="pihole-disable-5m">
                                <i class="fa fa-fw menu-icon fas fa-clock"></i>5 分钟
                            </a>
                        </li>
                        <li>
                            <a href="#" id="pihole-disable-cst" data-toggle="modal" data-target="#customDisableModal">
                                <i class="fa fa-fw menu-icon fa-user-clock"></i>自定义时间
                            </a>
                        </li>
                    </ul>
                    <!-- <a href="#" id="flip-status"><i class="fa fa-stop"></i> <span>Disable</span></a> -->
                </li>
                <li id="pihole-enable" class="menu-dns treeview"<?php if (!in_array($pistatus, array('0', '-1', '-2'))) { ?> hidden<?php } ?>>
                    <a href="#">
                        <i class="fa fa-fw menu-icon fa-play"></i>
                        <span id="enableLabel">启用&nbsp;&nbsp;&nbsp;
                            <span id="flip-status-enable"></span>
                        </span>
                    </a>
                </li>
                <li class="menu-dns treeview <?php if (in_array($scriptname, array('dns_records.php', 'cname_records.php'))) { ?>active<?php } ?>">
                    <a href="#">
                        <i class="fa fa-fw menu-icon fa-address-book"></i> <span>本地 DNS 映射</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php if ($scriptname === 'dns_records.php') { ?> active<?php } ?>">
                            <a href="dns_records.php">
                                <i class="fa fa-fw menu-icon fa-address-book"></i>DNS 映射
                            </a>
                        </li>
                        <li class="<?php if ($scriptname === 'cname_records.php') { ?> active<?php } ?>">
                            <a href="cname_records.php">
                                <i class="fa fa-fw menu-icon fa-address-book"></i> CNAME 映射
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="header text-uppercase">系统</li>
                <!-- Tools -->
                <li class="menu-system treeview<?php if (in_array($scriptname, array('messages.php', 'gravity.php', 'queryads.php', 'auditlog.php', 'taillog.php', 'taillog-FTL.php', 'debug.php', 'network.php'))) { ?> active<?php } ?>">
                    <a href="#">
                        <i class="fa fa-fw menu-icon fa-tools"></i> <span>工具</span>
                        <span class="warning-count hidden"></span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <!-- Pi-hole diagnosis -->
                        <li class="<?php if ($scriptname === 'messages.php') { ?> active<?php } ?>">
                            <a href="messages.php">
                                <i class="fa fa-fw menu-icon fa-file-medical-alt"></i> Pi-hole 诊断
                                <span class="pull-right-container warning-count hidden"></span>
                            </a>
                        </li>
                        <!-- Run gravity.sh -->
                        <li class="<?php if ($scriptname === 'gravity.php') { ?> active<?php } ?>">
                            <a href="gravity.php">
                                <i class="fa fa-fw menu-icon fa-arrow-circle-down"></i> 更新引力场
                            </a>
                        </li>
                        <!-- Query Lists -->
                        <li class="<?php if ($scriptname === 'queryads.php') { ?> active<?php } ?>">
                            <a href="queryads.php">
                                <i class="fa fa-fw menu-icon fa-search"></i> 引力场定位
                            </a>
                        </li>
                        <!-- Audit log -->
                        <li class="<?php if ($scriptname === 'auditlog.php') { ?> active<?php } ?>">
                            <a href="auditlog.php">
                                <i class="fa fa-fw menu-icon fa-balance-scale"></i> 审核日志
                            </a>
                        </li>
                        <!-- Tail pihole.log -->
                        <li class="<?php if ($scriptname === 'taillog.php') { ?> active<?php } ?>">
                            <a href="taillog.php">
                                <svg class="svg-inline--fa fa-fw menu-icon" style="height: 1.25em"><use xlink:href="img/pihole_icon.svg#pihole-svg-logo"/></svg>
                                实时显示 pihole.log
                            </a>
                        </li>
                        <!-- Tail FTL.log -->
                        <li class="<?php if ($scriptname === 'taillog-FTL.php') { ?> active<?php } ?>">
                            <a href="taillog-FTL.php">
                                <svg class="svg-inline--fa fa-fw menu-icon" style="height: 1.25em"><use xlink:href="img/pihole_icon.svg#pihole-svg-logo"/></svg>
                                实时显示 FTL.log
                            </a>
                        </li>
                        <!-- Generate debug log -->
                        <li class="<?php if ($scriptname === 'debug.php') { ?> active<?php } ?>">
                            <a href="debug.php">
                                <i class="fa fa-fw menu-icon fa-ambulance"></i>生成调试日志
                            </a>
                        </li>
                        <!-- Network -->
                        <li class="<?php if ($scriptname === 'network.php') { ?> active<?php } ?>">
                            <a href="network.php">
                                <i class="fa fa-fw menu-icon fa-network-wired"></i>客户端概览
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Settings -->
                <li class="menu-system<?php if ($scriptname === 'settings.php') { ?> active<?php } ?>">
                    <a href="settings.php">
                        <i class="fa fa-fw menu-icon fa-cog"></i> <span>设置</span>
                    </a>
                </li>

                <!-- Donate button -->
                <li class="header text-uppercase">赞助</li>
                <li class="menu-donate">
                    <a href="https://pi-hole.net/donate/" target="_blank">
                        <i class="fas fa-fw menu-icon fa-donate"></i> <span>赞助</span>
                    </a>
                </li>
            </ul>
        </section>
        <!-- /.sidebar -->
    </aside>
