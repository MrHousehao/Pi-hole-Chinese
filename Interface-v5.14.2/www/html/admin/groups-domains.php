<?php
/*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2019 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license.
*/

require 'scripts/pi-hole/php/header_authenticated.php';
?>

<!-- Title -->
<div class="page-header">
    <h1>域名管理</h1>
</div>

<!-- Domain Input -->
<div class="row">
    <div class="col-md-12">
        <div class="box" id="add-group">
            <div class="box-header with-border">
                <h3 class="box-title">
                    添加域名或正侧表达式
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="active" role="presentation">
                            <a href="#tab_domain" aria-controls="tab_domain" aria-expanded="true" role="tab" data-toggle="tab">域名</a>
                        </li>
                        <li role="presentation">
                            <a href="#tab_regex" aria-controls="tab_regex" aria-expanded="false" role="tab" data-toggle="tab">正侧表达式</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Domain tab -->
                        <div id="tab_domain" class="tab-pane active fade in">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="new_domain">域名：</label>
                                        <input id="new_domain" type="url" class="form-control active" placeholder="要添加的域名" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off">
                                        <div id="suggest_domains" class="table-responsive no-border"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="new_domain_comment">描述：</label>
                                    <input id="new_domain_comment" type="text" class="form-control" placeholder="描述（可选）">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div>
                                        <input type="checkbox" id="wildcard_checkbox">
                                        <label for="wildcard_checkbox"><strong>以通配符添加域名</strong></label>
                                        <p>如果您想涵盖所有子域名，请勾选此框。输入的域名将在添加时转换为正侧表达式。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- RegEx tab -->
                        <div id="tab_regex" class="tab-pane fade">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="new_regex">正则表达式：</label>
                                        <input id="new_regex" type="text" class="form-control active" placeholder="要添加的正则表达式">
                                    </div>
                                    <div class="form-group">
                                        <strong>提示：</strong>如果需要了解正侧表达式的正确编写规则，请参阅我们提供的
                                        <a href="https://docs.pi-hole.net/ftldns/regex/tutorial" rel="noopener" target="_blank">
                                            正侧表达式编写教程</a>。
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="new_regex_comment">描述：</label>
                                    <input id="new_regex_comment" type="text" class="form-control" placeholder="描述（可选）">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <p><strong>备注：</strong><br>
                        域名或正则表达式将自动分配到默认群组。<br>
                        可以在下面的配置表中选择分配到其他群组（使用<b>群组分配</b>）。
                    </p>
                </div>
                <div class="btn-toolbar pull-right" role="toolbar" aria-label="Toolbar with buttons">
                    <div class="btn-group" role="group" aria-label="Third group">
                        <button type="button" class="btn btn-primary" id="add2black">添加到黑名单</button>
                    </div>
                    <div class="btn-group" role="group" aria-label="Third group">
                        <button type="button" class="btn btn-primary" id="add2white">添加到白名单</button>
                    </div>
                </div>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
</div>

<!-- Domain List -->
<div class="row">
    <div class="col-md-12">
        <div class="box" id="domains-list">
            <div class="box-header with-border">
                <h3 class="box-title">
                    域名配置表
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="domainsTable" class="table table-striped table-bordered" width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th></th>
                            <th>域名/正则表达式</th>
                            <th>类型</th>
                            <th>状态</th>
                            <th>描述</th>
                            <th>群组分配</th>
                            <th>&nbsp;</th>
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

<script src="scripts/vendor/bootstrap-select.min.js?v=<?php echo $cacheVer; ?>"></script>
<script src="scripts/vendor/bootstrap-toggle.min.js?v=<?php echo $cacheVer; ?>"></script>
<script src="scripts/pi-hole/js/groups-domains.js?v=<?php echo $cacheVer; ?>"></script>

<?php
require 'scripts/pi-hole/php/footer.php';
?>
