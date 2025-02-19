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
    <h1>本地 CNAME 映射</h1>
    <small>在此页面，您可以添加 CNAME 映射。</small>
</div>

<!-- Domain Input -->
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <!-- /.box-header -->
            <div class="box-header with-border">
                <h3 class="box-title">
                    添加 CNAME 映射
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
                        <label for="target">目标域名：</label>
                        <input id="target" type="url" class="form-control" placeholder="关联目标域名" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off">
                    </div>
                </div>
            </div>
            <div class="box-footer clearfix">
                <strong>备注：</strong>
                <p>添加的<code>CNAME</code>目标域名必须是Pi-hole缓存中已有的域名或对其具有权威性的域名。这是<code>CNAME</code> 映射的普遍限制。</p>
              <p>这是因为 Pi-hole 在提供<code>CNAME</code> 回应时不会向上游发送额外的查询请求。因此，如果您设置了一个位置的目标，则对客户端的回应可能不完整。Pi-hole 只返回查询请求时的已知信息。 这会导致对<code>CNAME</code>目标的某些限制，
                例如：只有<i>激活</i>的 DHCP 静态地址分配可以作为目标，但只用 DHCP <i>静态地址分配</i>是不够的，因为它们（还）不是有效的 DNS 映射。</p>
                <p>此外，你无法成功访问<code>CNAME</code> 的外部域名（<code>bing.com</code> 到 <code>google.com</code>），因为当目标服务器不是请求的域名提供内容时，这可能会导致出现无效 SSL 证书错误。</p>
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
                    本地 CNAME 映射配置表
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="customCNAMETable" class="table table-striped table-bordered" width="100%">
                    <thead>
                        <tr>
                            <th>域名</th>
                            <th>目标</th>
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

<script src="<?php echo fileversion('scripts/pi-hole/js/customcname.js'); ?>"></script>

<?php
require 'scripts/pi-hole/php/footer.php';
?>
