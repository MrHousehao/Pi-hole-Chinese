<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2019 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";
?>

<!-- Title -->
<div class="page-header">
    <h1>客户端群组管理</h1>
</div>

<!-- Domain Input -->
<div class="row">
    <div class="col-md-12">
        <div class="box" id="add-client">
            <!-- /.box-header -->
            <div class="box-header with-border">
                <h3 class="box-title">
                    添加客户端
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="select">已知客户端：</label>
                        <select id="select" class="form-control" placeholder="">
                            <option disabled selected>正在加载...</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="new_comment">描述：</label>
                        <input id="new_comment" type="text" class="form-control" placeholder="客户端描述（可选）">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p>您可以在上面输入客户端的信息来添加自定义客户端或查找已知的客户端按<kbd>&#x23CE;</kbd>键选择。</p>
                        <p>客户端可通过IP地址（支持IPv4和IPv6）、
                           IP子网（CIDR表示法，如：<code>192.168.2.0/24</code>)、MAC地址（如：<code>12:34:56:78:9A:BC</code>）、主机名（如：<code>localhost</code>）或通过所连接的接口（以冒号开头，如：<code>:eth0</code>）的信息进行添加。</p>
                        <p>请注意，以IP地址（包括子网范围）配置的客户端识别优先级高于MAC地址、主机名或接口识别，主机名或接口识别的优先级在其之后。此外，MAC地址识别仅适用于距离Pi-hole较远的网络节点设备。</p>
                    </div>
                </div>
            </div>
            <div class="box-footer clearfix">
                <button type="button" id="btnAdd" class="btn btn-primary pull-right">添加</button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="box" id="clients-list">
            <div class="box-header with-border">
                <h3 class="box-title">
                    客户端配置表
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="clientsTable" class="table table-striped table-bordered" width="100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th title="Acceptable values are: IP address, subnet (CIDR notation), MAC address (AA:BB:CC:DD:EE:FF format) or host names.">客户端</th>
                        <th>描述</th>
                        <th>群组分配</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                </table>
                <button type="button" id="resetButton" class="btn btn-default btn-sm text-red hidden">重新排序</button>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
</div>

<script src="scripts/vendor/bootstrap-select.min.js?v=<?=$cacheVer?>"></script>
<script src="scripts/vendor/bootstrap-toggle.min.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/ip-address-sorting.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/utils.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/groups-clients.js?v=<?=$cacheVer?>"></script>

<?php
require "scripts/pi-hole/php/footer.php";
?>
