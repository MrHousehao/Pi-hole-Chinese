<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";

$showing = "";

if(isset($setupVars["API_QUERY_LOG_SHOW"]))
{
	if($setupVars["API_QUERY_LOG_SHOW"] === "all")
	{
		$showing = "显示";
	}
	elseif($setupVars["API_QUERY_LOG_SHOW"] === "permittedonly")
	{
		$showing = "显示已放行";
	}
	elseif($setupVars["API_QUERY_LOG_SHOW"] === "blockedonly")
	{
		$showing = "显示已吞噬";
	}
	elseif($setupVars["API_QUERY_LOG_SHOW"] === "nothing")
	{
		$showing = "不显示查询（由于相关设置）";
	}
}
else if(isset($_GET["type"]) && $_GET["type"] === "blocked")
{
	$showing = "显示已吞噬";
}
else
{
	// If filter variable is not set, we
	// automatically show all queries
	$showing = "显示";
}

$showall = false;
if(isset($_GET["all"]))
{
	$showing .= "Pi-hole日志中的所有查询";
}
else if(isset($_GET["client"]))
{
	$showing .= "查询请求的客户端".htmlentities($_GET["client"]);
}
else if(isset($_GET["forwarddest"]))
{
	if($_GET["forwarddest"] === "blocklist")
		$showing .= "查询请求的回应来自引力场";
	elseif($_GET["forwarddest"] === "cache")
		$showing .= "查询请求的回应来自缓存";
	else
		$showing .= "查询请求的回应来上游服务器".htmlentities($_GET["forwarddest"]);
}
else if(isset($_GET["querytype"]))
{
	$showing .= " 类型 ".getQueryTypeStr($_GET["querytype"])." 查询请求";
}
else if(isset($_GET["domain"]))
{
	$showing .= "查询的域名".htmlentities($_GET["domain"]);
}
else if(isset($_GET["from"]) || isset($_GET["until"]))
{
	$showing .= "指定时间段查询";
}
else
{
	$showing .= "最多显示100个查询请求";
	$showall = true;
}

if(isset($setupVars["API_PRIVACY_MODE"]))
{
	if($setupVars["API_PRIVACY_MODE"])
	{
		// Overwrite string from above
		$showing .= ", 隐私模式开启";
	}
}

if(strlen($showing) > 0)
{
	$showing = "(".$showing.")";
	if($showall)
		$showing .= ", <a href=\"?all\">显示全部</a>";
}
?>

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
                    <div class="alProcessing">添加<span id="alDomain"></span>到<span id="alList"></span>...</div>
                    <div class="alSuccess text-bold text-green" style="display: none"><span id="alDomain"></span> 已成功添加到<span id="alList"></span></div>
                    <div class="alFailure text-bold text-red" style="display: none">
                        <span id="alNetErr">超时或网络连接错误！</span>
                        <span id="alCustomErr"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
      <div class="box" id="recent-queries">
        <div class="box-header with-border">
          <h3 class="box-title">最近查询<?php echo $showing; ?></h3>
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
                        <th>响应时间</th>
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
                        <th>响应时间</th>
                        <th>操作</th>
                    </tr>
                </tfoot>
            </table>
            <p><strong>筛选器选项：</strong></p>
            <ul>
                <li>点击列表中的字段在筛选器中添加/删除该字段进行筛选</li>
                <li>计算机：按住<kbd>Ctrl</kbd>、<kbd>Alt</kbd>或<kbd>&#8984;</kbd>允许高亮显示以复制到剪贴板</li>
                <li>手机：长按可突出显示文本并允许复制到剪贴板
            </ul><br/><button type="button" id="resetButton" class="btn btn-default btn-sm text-red hidden">清除筛选器</button>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
</div>
<!-- /.row -->
<script src="scripts/pi-hole/js/ip-address-sorting.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/utils.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/queries.js?v=<?=$cacheVer?>"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
