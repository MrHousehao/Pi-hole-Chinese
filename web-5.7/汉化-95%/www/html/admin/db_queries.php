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
    <h1>从Pi-hole数据库调阅全局统计信息</h1>
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
        <label>查询请求状态：</label>
    </div>
    <div class="form-group">
        <div class="col-md-3">
            <div><input type="checkbox" id="type_forwarded" checked><label for="type_forwarded">放行：已转发</label><br></div>
            <div><input type="checkbox" id="type_cached" checked><label for="type_cached">放行：缓存</label></div>
            <div><input type="checkbox" id="type_retried" checked><label for="type_retried">放行：重试</label></div>
        </div>
        <div class="col-md-3">
            <div><input type="checkbox" id="type_gravity" checked><label for="type_gravity">吞噬：引力场</label><br></div>
            <div><input type="checkbox" id="type_external" checked><label for="type_external">吞噬：外部</label></div>
            <div><input type="checkbox" id="type_dbbusy" checked><label for="type_dbbusy">吞噬：数据库繁忙</label></div>
        </div>
        <div class="col-md-3">
            <div><input type="checkbox" id="type_blacklist" checked><label for="type_blacklist">吞噬：确切黑名单</label><br></div>
            <div><input type="checkbox" id="type_regex" checked><label for="type_regex">吞噬：正侧表达式黑名单</label></div>
        </div>
        <div class="col-md-3">
            <div><input type="checkbox" id="type_gravity_CNAME" checked><label for="type_gravity_CNAME">吞噬：引力场（CNAME）</label><br></div>
            <div><input type="checkbox" id="type_blacklist_CNAME" checked><label for="type_blacklist_CNAME">吞噬：确切黑名单（CNAME）</label><br></div>
            <div><input type="checkbox" id="type_regex_CNAME" checked><label for="type_regex_CNAME">吞噬：正侧表达式黑名单（CNAME）</label></div>
        </div>
    </div>
</div>

<div id="timeoutWarning" class="alert alert-warning alert-dismissible fade in" role="alert" hidden>
    根据选择的时间范围，当Pi-hole检索大量数据时，请求可能会超时。<br/><span id="err"></span>
</div>

<!-- Small boxes (Stat box) -->
<div class="row">
    <div class="col-lg-3 col-xs-12">
        <!-- small box -->
        <div class="small-box bg-aqua no-user-select">
            <div class="inner">
                <h3 class="statistic" id="ads_blocked_exact">---</h3>
                <p>已吞噬的数据</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-paper"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-12">
        <!-- small box -->
        <div class="small-box bg-aqua no-user-select">
            <div class="inner">
                <h3 class="statistic" id="ads_wildcard_blocked">---</h3>
                <p>已吞噬的数据（通配符）</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-paper"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-12">
        <!-- small box -->
        <div class="small-box bg-green no-user-select">
            <div class="inner">
                <h3 class="statistic" id="dns_queries">---</h3>
                <p>总查询请求数据</p>
            </div>
            <div class="icon">
                <i class="fas fa-globe-americas"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-12">
        <!-- small box -->
        <div class="small-box bg-yellow no-user-select">
            <div class="inner">
                <h3 class="statistic" id="ads_percentage_today">---</h3>
                <p>吞噬比例</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
</div>

<!-- Alert Modal -->
<div id="alertModal" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <span class="fa-stack fa-2x" style="margin-bottom: 10px">
                        <div class="alProcessing">
                            <i class="fa-stack-2x alSpinner"></i>
                        </div>
                        <div class="alSuccess" style="display: none">
                            <i class="fa fa-circle fa-stack-2x text-green"></i>
                            <i class="fa fa-check fa-stack-1x fa-inverse"></i>
                        </div>
                        <div class="alFailure" style="display: none">
                            <i class="fa fa-circle fa-stack-2x text-red"></i>
                            <i class="fa fa-times fa-stack-1x fa-inverse"></i>
                        </div>
                    </span>
                    <div class="alProcessing">正在添加 <span id="alDomain"></span> 到 <span id="alList"></span>...</div>
                    <div class="alSuccess text-bold text-green" style="display: none"><span id="alDomain"></span> 已成功添加到 <span id="alList"></span></div>
                    <div class="alFailure text-bold text-red" style="display: none">
                        <span id="alNetErr">超时或网络连接错误！</span>
                        <span id="alCustomErr"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
      <div class="box" id="recent-queries">
        <div class="box-header with-border">
          <h3 class="box-title">近期数据</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <table id="all-queries" class="table table-striped table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>类型</th>
                        <th>域名</th>
                        <th>客户端</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>时间</th>
                        <th>类型</th>
                        <th>域名</th>
                        <th>客户端</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
</div>
<!-- /.row -->
<script src="scripts/pi-hole/js/ip-address-sorting.js?v=<?=$cacheVer?>"></script>
<script src="scripts/vendor/daterangepicker.min.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/utils.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/db_queries.js?v=<?=$cacheVer?>"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
