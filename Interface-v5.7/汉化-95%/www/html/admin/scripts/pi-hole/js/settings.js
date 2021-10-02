/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license. */

/* global utils:false */
var token = $("#token").text();

$(function () {
  $("[data-static]").on("click", function () {
    var row = $(this).closest("tr");
    var mac = row.find("#MAC").text();
    var ip = row.find("#IP").text();
    var host = row.find("#HOST").text();
    $('input[name="AddHostname"]').val(host);
    $('input[name="AddIP"]').val(ip);
    $('input[name="AddMAC"]').val(mac);
  });
});
$(".confirm-poweroff").confirm({
  text: "确定要向Pi-hole发送关闭主机指令吗？",
  title: "Confirmation required",
  confirm: function () {
    $("#poweroffform").submit();
  },
  cancel: function () {
    // nothing to do
  },
  confirmButton: "是，关闭主机",
  cancelButton: "不，撤销指令",
  post: true,
  confirmButtonClass: "btn-danger",
  cancelButtonClass: "btn-success",
  dialogClass: "modal-dialog",
});
$(".confirm-reboot").confirm({
  text: "确定要向Pi-hole发送重启主机指令吗？",
  title: "Confirmation required",
  confirm: function () {
    $("#rebootform").submit();
  },
  cancel: function () {
    // nothing to do
  },
  confirmButton: "是，重启主机",
  cancelButton: "不，撤销指令",
  post: true,
  confirmButtonClass: "btn-danger",
  cancelButtonClass: "btn-success",
  dialogClass: "modal-dialog",
});

$(".confirm-restartdns").confirm({
  text: "确定要重新启动DNS服务器吗?",
  title: "Confirmation required",
  confirm: function () {
    $("#restartdnsform").submit();
  },
  cancel: function () {
    // nothing to do
  },
  confirmButton: "是，DNS服务器",
  cancelButton: "不，撤销指令",
  post: true,
  confirmButtonClass: "btn-danger",
  cancelButtonClass: "btn-success",
  dialogClass: "modal-dialog",
});

$(".confirm-flushlogs").confirm({
  text: "确定要清空日志吗？",
  title: "Confirmation required",
  confirm: function () {
    $("#flushlogsform").submit();
  },
  cancel: function () {
    // nothing to do
  },
  confirmButton: "是，清空日志",
  cancelButton: "不，撤销指令",
  post: true,
  confirmButtonClass: "btn-danger",
  cancelButtonClass: "btn-success",
  dialogClass: "modal-dialog",
});

$(".confirm-flusharp").confirm({
  text: "确定要清空客户端列表吗？",
  title: "Confirmation required",
  confirm: function () {
    $("#flusharpform").submit();
  },
  cancel: function () {
    // nothing to do
  },
  confirmButton: "是，清空客户端列表",
  cancelButton: "不，撤销指令",
  post: true,
  confirmButtonClass: "btn-warning",
  cancelButtonClass: "btn-success",
  dialogClass: "modal-dialog",
});

$(".confirm-disablelogging-noflush").confirm({
  text: "确定要禁用查询请求的日志记录吗？",
  title: "Confirmation required",
  confirm: function () {
    $("#disablelogsform-noflush").submit();
  },
  cancel: function () {
    // nothing to do
  },
  confirmButton: "是，禁用日志记录",
  cancelButton: "不，撤销指令",
  post: true,
  confirmButtonClass: "btn-warning",
  cancelButtonClass: "btn-success",
  dialogClass: "modal-dialog",
});

$(".api-token").confirm({
  text: "请确保您周围没有其他人可以扫描您的二维码，凭令牌可以完全访问API。请注意，生成二维码需要一些时间。",
  title: "Confirmation required",
  confirm: function () {
    window.open("scripts/pi-hole/php/api_token.php");
  },
  cancel: function () {
    // nothing to do
  },
  confirmButton: "是，显示API令牌",
  cancelButton: "不，撤销指令",
  post: true,
  confirmButtonClass: "btn-danger",
  cancelButtonClass: "btn-success",
  dialogClass: "modal-dialog",
});

$("#DHCPchk").click(function () {
  $("input.DHCPgroup").prop("disabled", !this.checked);
  $("#dhcpnotice").prop("hidden", !this.checked).addClass("lookatme");
});

function loadCacheInfo() {
  $.getJSON("api.php?getCacheInfo", function (data) {
    if ("FTLnotrunning" in data) {
      return;
    }

    // Fill table with obtained values
    $("#cache-size").text(parseInt(data.cacheinfo["cache-size"], 10));
    $("#cache-inserted").text(parseInt(data.cacheinfo["cache-inserted"], 10));

    // Highlight early cache removals when present
    var cachelivefreed = parseInt(data.cacheinfo["cache-live-freed"], 10);
    $("#cache-live-freed").text(cachelivefreed);
    if (cachelivefreed > 0) {
      $("#cache-live-freed").parent("tr").addClass("lookatme");
    } else {
      $("#cache-live-freed").parent("tr").removeClass("lookatme");
    }

    // Update cache info every 10 seconds
    setTimeout(loadCacheInfo, 10000);
  });
}

var leasetable, staticleasetable;
$(function () {
  if (document.getElementById("DHCPLeasesTable")) {
    leasetable = $("#DHCPLeasesTable").DataTable({
      dom: "<'row'<'col-sm-12'tr>><'row'<'col-sm-6'i><'col-sm-6'f>>",
      columnDefs: [{ bSortable: false, orderable: false, targets: -1 }],
      paging: false,
      scrollCollapse: true,
      scrollY: "200px",
      scrollX: true,
      order: [[2, "asc"]],
      stateSave: true,
      stateSaveCallback: function (settings, data) {
        utils.stateSaveCallback("activeDhcpLeaseTable", data);
      },
      stateLoadCallback: function () {
        return utils.stateLoadCallback("activeDhcpLeaseTable");
      },
    });
  }

  if (document.getElementById("DHCPStaticLeasesTable")) {
    staticleasetable = $("#DHCPStaticLeasesTable").DataTable({
      dom: "<'row'<'col-sm-12'tr>><'row'<'col-sm-12'i>>",
      columnDefs: [{ bSortable: false, orderable: false, targets: -1 }],
      paging: false,
      scrollCollapse: true,
      scrollY: "200px",
      scrollX: true,
      order: [[2, "asc"]],
      stateSave: true,
      stateSaveCallback: function (settings, data) {
        utils.stateSaveCallback("staticDhcpLeaseTable", data);
      },
      stateLoadCallback: function () {
        return utils.stateLoadCallback("staticDhcpLeaseTable");
      },
    });
  }

  //call draw() on each table... they don't render properly with scrollX and scrollY set... ¯\_(ツ)_/¯
  $('a[data-toggle="tab"]').on("shown.bs.tab", function () {
    leasetable.draw();
    staticleasetable.draw();
  });

  loadCacheInfo();
});

// Handle hiding of alerts
$(function () {
  $("[data-hide]").on("click", function () {
    $(this)
      .closest("." + $(this).attr("data-hide"))
      .hide();
  });
});

// DHCP leases tooltips
$(function () {
  $('[data-toggle="tooltip"]').tooltip({ html: true, container: "body" });
});

// Auto dismissal for info notifications
$(function () {
  var alInfo = $("#alInfo");
  if (alInfo.length > 0) {
    alInfo.delay(3000).fadeOut(2000, function () {
      alInfo.hide();
    });
  }

  // Disable autocorrect in the search box
  var input = document.querySelector("input[type=search]");
  input.setAttribute("autocomplete", "off");
  input.setAttribute("autocorrect", "off");
  input.setAttribute("autocapitalize", "off");
  input.setAttribute("spellcheck", false);

  // En-/disable conditional forwarding input fields based
  // on the checkbox state
  $('input[name="rev_server"]').click(function () {
    $('input[name="rev_server_cidr"]').prop("disabled", !this.checked);
    $('input[name="rev_server_target"]').prop("disabled", !this.checked);
    $('input[name="rev_server_domain"]').prop("disabled", !this.checked);
  });
});

// Change "?tab=" parameter in URL for save and reload
$(".nav-tabs a").on("shown.bs.tab", function (e) {
  var tab = e.target.hash.substring(1);
  window.history.pushState("", "", "?tab=" + tab);
  if (tab === "piholedhcp") {
    window.location.reload();
  }

  window.scrollTo(0, 0);
});

// Bar/Smooth chart toggle
$(function () {
  var bargraphs = $("#bargraphs");
  var chkboxData = localStorage.getItem("barchart_chkbox");

  if (chkboxData !== null) {
    // Restore checkbox state
    bargraphs.prop("checked", chkboxData === "true");
  } else {
    // Initialize checkbox
    bargraphs.prop("checked", true);
    localStorage.setItem("barchart_chkbox", true);
  }

  bargraphs.click(function () {
    localStorage.setItem("barchart_chkbox", bargraphs.prop("checked"));
  });
});

$(function () {
  var colorfulQueryLog = $("#colorfulQueryLog");
  var chkboxData = localStorage.getItem("colorfulQueryLog_chkbox");

  if (chkboxData !== null) {
    // Restore checkbox state
    colorfulQueryLog.prop("checked", chkboxData === "true");
  } else {
    // Initialize checkbox
    colorfulQueryLog.prop("checked", false);
    localStorage.setItem("colorfulQueryLog_chkbox", false);
  }

  colorfulQueryLog.click(function () {
    localStorage.setItem("colorfulQueryLog_chkbox", colorfulQueryLog.prop("checked"));
  });
});

// Delete dynamic DHCP lease
$('button[id="removedynamic"]').on("click", function () {
  var tr = $(this).closest("tr");
  var ipaddr = utils.escapeHtml(tr.children("#IP").text());
  var name = utils.escapeHtml(tr.children("#HOST").text());
  var ipname = name + " (" + ipaddr + ")";

  utils.disableAll();
  utils.showAlert("info", "", "DHCP静态地址分配删除中...", ipname);
  $.ajax({
    url: "api.php",
    method: "get",
    dataType: "json",
    data: {
      delete_lease: ipaddr,
      token: token,
    },
    success: function (response) {
      utils.enableAll();
      if (response.delete_lease.startsWith("OK")) {
        utils.showAlert(
          "success",
          "far fa-trash-alt",
          "已成功删除，DHCP静态地址分配：",
          ipname
        );
        // Remove column on success
        tr.remove();
        // We have to hide the tooltips explicitly or they will stay there forever as
        // the onmouseout event does not fire when the element is already gone
        $.each($(".tooltip"), function () {
          $(this).remove();
        });
      } else {
        utils.showAlert("error", "删除DHCP静态地址分配" + ipname, response + "时出错");
      }
    },
    error: function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert("error", "删除DHCP静态地址分配" + ipname, jqXHR.responseText + "时出错");
      console.log(exception); // eslint-disable-line no-console
    },
  });
});
