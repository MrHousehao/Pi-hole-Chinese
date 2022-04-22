<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";
?>

<div class="row">
    <div class="col-md-12">
      <div class="box" id="network-details">
        <div class="box-header with-border">
          <h3 class="box-title">客户端概览（Pi-hole使用情况）</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <table id="network-entries" class="table table-striped table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>IP地址</th>
                        <th>硬件地址</th>
                        <th>接口</th>
                        <th>主机名称</th>
                        <th>首次连接时间</th>
                        <th>最近连接时间</th>
                        <th>查询请求数量</th>
                        <th>连接Pi-hole</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>IP地址</th>
                        <th>硬件地址</th>
                        <th>接口</th>
                        <th>主机名称</th>
                        <th>首次连接时间</th>
                        <th>最近连接时间</th>
                        <th>查询请求数量</th>
                        <th>连接Pi-hole</th>
                        <th>操作</th>
                    </tr>
                </tfoot>
            </table>
            <label>背景颜色：该设备最近的查询请求...</label>
        <table width="100%">
          <tr class="text-center">
            <td class="network-recent" width="15%">刚刚</td>
            <td class="network-gradient" width="30%">...   到   ...</td>
            <td class="network-old" width="15%">24小时内</td>
            <td class="network-older" width="20%">&gt; 24小时</td>
            <td class="network-never" width="20%">设备未连接Pi-hole</td>
          </tr>
        </table>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
</div>
<!-- /.row -->

<script src="scripts/pi-hole/js/ip-address-sorting.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/network.js?v=<?=$cacheVer?>"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
