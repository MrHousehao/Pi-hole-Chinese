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
    <h1>生成调试日志</h1>
</div>
<div class="box">
    <div class="box-header with-border"><h1 class="box-title">选项：</h1></div>
    <div class="box-body">
        <div>
            <input type="checkbox" id="dbcheck">
            <label for="dbcheck"><strong>执行数据库完整性检查。</strong>
                <br class="hidden-md hidden-lg"><span class="text-red">这可能会增加几分钟的调试日志生成时间。</span>
            </label>
        </div>
        <div>
            <input type="checkbox" id="upload">
            <label for="upload"><strong>上传调试日志并在完成后提供令牌。</strong>
                <br class="hidden-md hidden-lg"><span>完成后，URL 令牌将显示在报告末尾。</span>
            </label>
        </div>
    </div>
</div>
<br>
<p>单击此按钮后，如果网络（互联网）连接正常，系统将生成调试日志并自动上传。</p>
<button type="button" id="debugBtn" class="btn btn-lg btn-primary btn-block">生成调试日志</button>
<pre id="output" style="width: 100%; height: 100%;" hidden></pre>

<script src="<?php echo fileversion('scripts/pi-hole/js/debug.js'); ?>"></script>

<?php
require 'scripts/pi-hole/php/footer.php';
?>
