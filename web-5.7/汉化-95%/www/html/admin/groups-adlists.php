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
    <h1>引力场管理（广告吞噬规则管理）</h1>
</div>

<!-- Domain Input -->
<div class="row">
    <div class="col-md-12">
        <div class="box" id="add-group">
            <!-- /.box-header -->
            <div class="box-header with-border">
                <h3 class="box-title">
                    添加引力场
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="new_address">地址：</label>
                        <input id="new_address" type="text" class="form-control" placeholder="URL或以空格分隔的多个URL" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="new_comment">描述：</label>
                        <input id="new_comment" type="text" class="form-control" placeholder="引力场描述（可选）">
                    </div>
                </div>
            </div>
            <div class="box-footer clearfix">
                <strong>提示：</strong>
                <ol>
                    <li>请在配置引力场后，在终端运行<code>pihole -g</code>或在线更新<a href="gravity.php">引力场</a>。</li>
                    <li>用空格分隔<i>每一个</i>URL，可以添加多个引力场</li>
                    <li>点击第一列中的图标可以显示该引力场的相关信息。图标对应于引力场的运行状况。</li>
                </ol>
                <button type="button" id="btnAdd" class="btn btn-primary pull-right">添加</button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="box" id="adlists-list">
            <div class="box-header with-border">
                <h3 class="box-title">
                    引力场配置表
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="adlistsTable" class="table table-striped table-bordered" width="100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th></th>
                        <th>地址</th>
                        <th>状态</th>
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
<script src="scripts/pi-hole/js/utils.js?v=<?=$cacheVer?>"></script>
<script src="scripts/pi-hole/js/groups-adlists.js?v=<?=$cacheVer?>"></script>

<?php
require "scripts/pi-hole/php/footer.php";
?>
