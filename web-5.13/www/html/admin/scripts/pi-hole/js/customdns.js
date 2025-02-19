/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license. */

/* global utils:false */

var table;
var token = $("#token").text();

$(function () {
  $("#btnAdd").on("click", addCustomDNS);

  table = $("#customDNSTable").DataTable({
    ajax: {
      url: "scripts/pi-hole/php/customdns.php",
      data: { action: "get", token: token },
      type: "POST",
    },
    columns: [{}, { type: "ip-address" }, { orderable: false, searchable: false }],
    columnDefs: [
      {
        targets: 2,
        render: function (data, type, row) {
          return (
            '<button type="button" class="btn btn-danger btn-xs deleteCustomDNS" data-domain=\'' +
            row[0] +
            "' data-ip='" +
            row[1] +
            "'>" +
            '<span class="far fa-trash-alt"></span>' +
            "</button>"
          );
        },
      },
      {
        targets: "_all",
        render: $.fn.dataTable.render.text(),
      },
    ],
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "全部"],
    ],
    order: [[0, "asc"]],
    stateSave: true,
    stateDuration: 0,
    stateSaveCallback: function (settings, data) {
      utils.stateSaveCallback("LocalDNSTable", data);
    },
    stateLoadCallback: function () {
      return utils.stateLoadCallback("LocalDNSTable");
    },
    drawCallback: function () {
      $(".deleteCustomDNS").on("click", deleteCustomDNS);
    },
  });
  // Disable autocorrect in the search box
  var input = document.querySelector("input[type=search]");
  input.setAttribute("autocomplete", "off");
  input.setAttribute("autocorrect", "off");
  input.setAttribute("autocapitalize", "off");
  input.setAttribute("spellcheck", false);
});

function addCustomDNS() {
  var ip = utils.escapeHtml($("#ip").val());
  var domain = utils.escapeHtml($("#domain").val());

  utils.disableAll();
  utils.showAlert("info", "", "添加自定义 DNS 映射中...", "");

  $.ajax({
    url: "scripts/pi-hole/php/customdns.php",
    method: "post",
    dataType: "json",
    data: { action: "add", ip: ip, domain: domain, token: token },
    success: function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert("success", "far fa-check-circle", "已添加自定义 DNS 映射", domain + ": " + ip);

        // Clean up field values and reload table data
        $("#domain").val("");
        $("#ip").val("");
        table.ajax.reload();
      } else {
        utils.showAlert("error", "fas fa-times", "失败！出了一点问题", response.message);
      }
    },
    error: function () {
      utils.enableAll();
      utils.showAlert("error", "fas fa-times", "添加自定义 DNS 映射时出错", "");
    },
  });
}

function deleteCustomDNS() {
  var ip = $(this).attr("data-ip");
  var domain = $(this).attr("data-domain");

  utils.disableAll();
  utils.showAlert("info", "", "删除自定义 DNS 映射中...", "");

  $.ajax({
    url: "scripts/pi-hole/php/customdns.php",
    method: "post",
    dataType: "json",
    data: { action: "delete", domain: domain, ip: ip, token: token },
    success: function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert("success", "far fa-check-circle", "已删除自定义 DNS 映射", domain + ": " + ip);
        table.ajax.reload();
      } else {
        utils.showAlert("error", "fas fa-times", "失败！出了一点问题", response.message);
      }
    },
    error: function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert("error", "fas fa-times", "删除自定义 DNS 映射时出错", "");
      console.log(exception); // eslint-disable-line no-console
    },
  });
}
