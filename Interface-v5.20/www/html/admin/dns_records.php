<?php
/*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license.
*/

require 'scripts/pi-hole/php/header_authenticated.php';
?>

<!-- Title -->
<div class="page-header">
    <h1>本地 DNS 映射 [A/AAAA]</h1>
    <small>在本页面中，您可以添加域名到 IP 地址的映射。</small>
</div>

<!-- Domain Input -->
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <!-- /.box-header -->
            <div class="box-header with-border">
                <h3 class="box-title">
                    添加新的域名到 IP 地址的映射
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="domain">域名：</label>
                        <input id="domain" type="url" class="form-control" placeholder="域名或逗号分隔的域名列表" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="ip">目标 IP 地址：</label>
                        <input id="ip" type="text" class="form-control" placeholder="关联目标 IP 地址" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off">
                    </div>
                </div>
            </div>
            <div class="box-footer clearfix">
                <strong>备注：</strong>
                <p>本地定义的 DNS 映射的调阅顺序是：</p>
                <ol>
                  <li>设备的主机名和<code>pi.hole</code></li>
                  <li>在文件<code>/etc/dnsmasq.d/</code>中的配置</li>
                  <li>读取<code>/etc/hosts</code>中的配置</li>
                  <li>从“本地（自定义）DNS” 列表中读取（文件位置<code>/etc/pihole/custom.list</code>）</li>
                </ol>
                <p>多条相同域名的映射设置，只会按顺序调阅第一条域名到 IP 地址映射设置。</p>
                <button type="button" id="btnAdd" class="btn btn-primary pull-right">添加</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="box" id="recent-queries">
            <div class="box-header with-border">
                <h3 class="box-title">
                    本地 DNS 映射配置表
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="customDNSTable" class="table table-striped table-bordered" width="100%">
                    <thead>
                    <tr>
                        <th>域名</th>
                        <th>目标 IP 地址</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                </table>
                <button type="button" id="resetButton" class="btn btn-default btn-sm text-red hidden">清除筛选器</button>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
</div>

<script src="<?php echo fileversion('scripts/pi-hole/js/ip-address-sorting.js'); ?>"></script>
<script src="<?php echo fileversion('scripts/pi-hole/js/customdns.js'); ?>"></script>

<?php
require 'scripts/pi-hole/php/footer.php';
?>
