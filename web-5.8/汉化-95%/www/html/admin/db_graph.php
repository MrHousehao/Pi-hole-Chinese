<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";
?>

<div class="page-header">
    <h1>从Pi-hole数据库调阅吞噬数据图形统计信息</h1>
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

<div class="row">
  <div class="col-md-12">
    <div id="timeoutWarning" class="alert alert-warning alert-dismissible fade in" role="alert" hidden>
        根据选择的时间范围，当Pi-hole检索大量数据时，请求可能会超时。<br/><span id="err"></span>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="box" id="queries-over-time">
      <div class="box-header with-border">
        <h3 class="box-title">
          所选时间范围的吞噬数据统计
        </h3>
      </div>
      <div class="box-body">
        <div class="row">
          <div class="col-md-12">
            <div class="chart">
              <canvas id="queryOverTimeChart" width="800" height="250"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="overlay" hidden>
        <i class="fa fa-sync fa-spin"></i>
      </div>
    </div>
  </div>
</div>

<script src="scripts/vendor/daterangepicker.min.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/utils.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/db_graph.js?v=<?=$cacheVer?>"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
