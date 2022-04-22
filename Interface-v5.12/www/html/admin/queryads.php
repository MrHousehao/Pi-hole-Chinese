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
    <h1>定位吞噬域名在引力场的位置</h1>
</div>
<div class="row">
  <div class="col-md-12">
    <div class="box">
      <div class="box-body">
        <!-- domain-search-block - Single search field mobile/desktop -->
        <div id="domain-search-block" class="input-group">
          <input id="domain" type="url" class="form-control" placeholder="查找的域名（example.com 或 sub.example.com）" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off">
          <input id="quiet" type="hidden" value="no">
          <span class="input-group-btn">
            <button type="button" id="btnSearch" class="btn btn-default">搜索部分匹配</button>
            <button type="button" id="btnSearchExact" class="btn btn-default">搜索精确匹配</button>
          </span>
        </div>
        <!-- /domain-search-block -->
      </div>
    </div>
  </div>
</div>

<pre id="output" style="width: 100%; height: 100%;" hidden></pre>

<script src="scripts/pi-hole/js/queryads.js?v=<?=$cacheVer?>"></script>
<?php
    require "scripts/pi-hole/php/footer.php";
?>
