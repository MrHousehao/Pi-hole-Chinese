<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";
?>

<!-- Sourceing CSS colors from stylesheet to be used in JS code -->
<span class="queries-permitted"></span>
<span class="queries-blocked"></span>

<!-- Title -->
<div class="page-header">
    <h1>从Pi-hole数据库调阅统计表信息</h1>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title">
          选择时间范围
        </h3>
      </div>
      <div class="box-body">
        <div class="row">
          <div class="form-group col-md-12">
            <div class="input-group">
              <div class="input-group-addon">
                <i class="far fa-clock"></i>
              </div>
              <input type="button" class="form-control pull-right" id="querytime" value="点击选择日期和时间范围">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="timeoutWarning" class="alert alert-warning alert-dismissible fade in" role="alert" hidden>
    根据选择的时间范围，当Pi-hole检索大量数据时，请求可能会超时。<br/><span id="err"></span>
</div>

<?php
if($boxedlayout)
{
	$tablelayout = "col-md-6";
}
else
{
	$tablelayout = "col-md-6 col-lg-4";
}
?>
<div class="row">
    <div class="<?php echo $tablelayout; ?>">
      <div class="box" id="domain-frequency">
        <div class="box-header with-border">
          <h3 class="box-title">放行域名统计</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                    <th>域名</th>
                    <th>次数</th>
                    <th>比例</th>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
        <div class="overlay" hidden>
          <i class="fa fa-sync fa-spin"></i>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
    <!-- /.col -->
    <div class="<?php echo $tablelayout; ?>">
      <div class="box" id="ad-frequency">
        <div class="box-header with-border">
          <h3 class="box-title">吞噬域名统计</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                    <th>域名</th>
                    <th>次数</th>
                    <th>比例</th>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
        <div class="overlay" hidden>
          <i class="fa fa-sync fa-spin"></i>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
    <!-- /.col -->
    <div class="<?php echo $tablelayout; ?>">
      <div class="box" id="client-frequency">
        <div class="box-header with-border">
          <h3 class="box-title">客户端统计</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                    <th>客户端</th>
                    <th>次数</th>
                    <th>比例</th>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
        <div class="overlay" hidden>
          <i class="fa fa-sync fa-spin"></i>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
    <!-- /.col -->
</div>

<script src="scripts/vendor/daterangepicker.min.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/utils.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/db_lists.js?v=<?=$cacheVer?>"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
