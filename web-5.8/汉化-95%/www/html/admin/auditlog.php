<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";
?>

<!-- Title -->
<div class="page-header">
    <h1>审核日志（显示实时数据）</h1>
</div>

<div class="row">
    <div class="col-xs-12 col-lg-6">
      <div class="box" id="domain-frequency">
        <div class="box-header with-border">
          <h3 class="box-title">已放行的数据</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                    <th>域名</th>
                    <th>次数</th>
                    <th>操作</th>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
        <div class="overlay">
          <i class="fa fa-sync fa-spin"></i>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
    <!-- /.col -->

    <div class="col-xs-12 col-lg-6">
      <div class="box" id="ad-frequency">
        <div class="box-header with-border">
          <h3 class="box-title">已吞噬的数据</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                    <th>域名</th>
                    <th>次数</th>
                    <th>操作</th>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
        <div class="overlay">
          <i class="fa fa-sync fa-spin"></i>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
    <!-- /.col -->
</div>
<!-- /.row -->

<script src="scripts/pi-hole/js/utils.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/auditlog.js?v=<?=$cacheVer?>"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
