/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license. */

var timeleft = 60;
var status = -1;
var reloadMsg =
  "FTL 已重新启动：<a href='settings.php' class='btn btn-sm btn-primary'>重新加载 FTL 信息。</a>";
var warningMsg = "FTL 在" + timeleft + "秒后无法重新加载。";
var counterMsg = "FTL 正在重新加载：";

var reloadTimer = setInterval(function () {
  $.getJSON("api.php?dns-port", function (data) {
    if ("FTLnotrunning" in data) {
      return;
    }

    status = data["dns-port"];
  });

  if (timeleft <= 0 || status >= 0) {
    clearInterval(reloadTimer);
    if (status < 0) {
      // FTL was not restarted in 60 seconds. Show warning message
      document.getElementById("restart-countdown").innerHTML = warningMsg;
    } else {
      // FTL restartd.
      document.getElementById("restart-countdown").innerHTML = reloadMsg;
    }
  } else {
    document.getElementById("restart-countdown").innerHTML =
      counterMsg + "剩余" + timeleft + "秒...";
  }

  timeleft -= 1;
}, 1000);
