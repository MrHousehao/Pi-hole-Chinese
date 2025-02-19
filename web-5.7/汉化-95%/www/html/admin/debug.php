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
    <h1>生成调试日志</h1>
</div>
<div>
    <input type="checkbox" id="upload">
    <label for="upload">传调试日志并在完成后提供令牌</label>
</div>
<p>单击此按钮后，如果网络（互联网）连接正常，系统将生成调试日志并自动上传。</p>
<button type="button" id="debugBtn" class="btn btn-lg btn-primary btn-block">生成调试日志</button>
<pre id="output" style="width: 100%; height: 100%;" hidden></pre>

<script src="scripts/pi-hole/js/debug.js?v=<?=$cacheVer?>"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
