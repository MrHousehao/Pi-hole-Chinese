/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license. */

/* global utils:false */
var table;
var token = $("#token").text();

function renderTimestamp(data, type) {
  // Display and search content
  if (type === "display" || type === "filter") {
    return utils.datetime(data);
  }

  // Sorting content
  return data;
}

function multline(input) {
  return input.split(",").join("\n");
}

function renderMessage(data, type, row) {
  // Display and search content
  switch (row.type) {
    case "REGEX":
      return (
        'Encountered an error when processing <a href="groups-domains.php?domainid=' +
        row.blob3 +
        '">' +
        row.blob1 +
        " regex filter with ID " +
        row.blob3 +
        "</a>:<pre>" +
        row.blob2 +
        "</pre>Error message: <pre>" +
        row.message +
        "</pre>"
      );

    case "SUBNET":
      return (
        "Client <code>" +
        row.message +
        "</code> is managed by " +
        row.blob1 +
        " groups (database IDs [" +
        row.blob3 +
        "]):<pre>" +
        multline(row.blob2) +
        "</pre>" +
        "FTL chose the most recent entry <pre>" +
        row.blob4 +
        "</pre> to get the group configuration for this client."
      );

    case "HOSTNAME":
      // eslint-disable-next-line unicorn/no-new-array
      var hint = new Array(row.blob2 + row.message.length + 3).join(" ");
      return (
        "Hostname contains invalid character <code>" +
        decodeURIComponent(escape(row.blob1))[row.blob2] +
        "</code>:<pre>" +
        hint +
        "&darr;\n" +
        row.message +
        ": " +
        decodeURIComponent(escape(row.blob1)) +
        "\n" +
        hint +
        "&uarr;</pre>"
      );

    case "DNSMASQ_CONFIG":
      return "FTL 无法启动，因为" + row.message;

    case "RATE_LIMIT":
      return (
        "Client " +
        row.message +
        " has been rate-limited (current config allows up to " +
        parseInt(row.blob1, 10) +
        " queries in " +
        parseInt(row.blob2, 10) +
        " seconds)"
      );

    case "DNSMASQ_WARN":
      return (
        "<code>dnsmasq</code>核心警告：<pre>" +
        row.message +
        '</pre>查看<a href="https://docs.pi-hole.net/ftldns/dnsmasq_warn/" target="_blank">Pi-hole支持文档</a>以获取更多信息'
      );

    case "LOAD":
      return (
        "Long-term load (15min avg) larger than number of processors: <strong>" +
        parseFloat(row.blob1).toFixed(1) +
        " &gt; " +
        parseInt(row.blob2, 10) +
        "</strong><br>This may slow down DNS resolution and can cause bottlenecks."
      );

    case "SHMEM":
      return (
        "RAM shortage (<code>" +
        utils.escapeHtml(row.message) +
        "</code>) ahead: <strong>" +
        parseInt(row.blob1, 10) +
        "% used</strong><pre>" +
        utils.escapeHtml(row.blob2) +
        "</pre>"
      );

    case "DISK":
      return (
        "Disk shortage (<code>" +
        utils.escapeHtml(row.message) +
        "</code>) ahead: <strong>" +
        parseInt(row.blob1, 10) +
        "% used</strong><pre>" +
        utils.escapeHtml(row.blob2) +
        "</pre>"
      );

    default:
      return "未知消息类型<pre>" + JSON.stringify(row) + "</pre>";
  }
}

$(function () {
  table = $("#messagesTable").DataTable({
    ajax: {
      url: "api_db.php?messages",
      data: { token: token },
      type: "POST",
      dataSrc: "messages",
    },
    order: [[0, "asc"]],
    columns: [
      { data: "id", visible: false },
      { data: "timestamp", width: "12%", render: renderTimestamp },
      { data: "type", width: "12%" },
      { data: "message", orderable: false, render: renderMessage },
      { data: "blob1", visible: false },
      { data: "blob2", visible: false },
      { data: "blob3", visible: false },
      { data: "blob4", visible: false },
      { data: "blob5", visible: false },
      { data: null, width: "30px", orderable: false },
    ],
    columnDefs: [
      {
        targets: "_all",
        render: $.fn.dataTable.render.text(),
      },
    ],
    drawCallback: function () {
      $('button[id^="deleteMessage_"]').on("click", deleteMessage);
      // Remove visible dropdown to prevent orphaning
      $("body > .bootstrap-select.dropdown").remove();
    },
    rowCallback: function (row, data) {
      $(row).attr("data-id", data.id);
      var button =
        '<button type="button" class="btn btn-danger btn-xs" id="deleteMessage_' +
        data.id +
        '">' +
        '<span class="far fa-trash-alt"></span>' +
        "</button>";
      $("td:eq(3)", row).html(button);
    },
    dom:
      "<'row'<'col-sm-4'l><'col-sm-8'f>>" +
      "<'row'<'col-sm-12'<'table-responsive'tr>>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "全部"],
    ],
    language: {
      emptyTable: "没有发现任何问题。",
    },
    stateSave: true,
    stateDuration: 0,
    stateSaveCallback: function (settings, data) {
      utils.stateSaveCallback("messages-table", data);
    },
    stateLoadCallback: function () {
      var data = utils.stateLoadCallback("messages-table");
      // Return if not available
      if (data === null) {
        return null;
      }

      // Reset visibility of ID and blob columns
      var hiddenCols = [0, 4, 5, 6, 7, 8];
      for (var key in hiddenCols) {
        if (Object.prototype.hasOwnProperty.call(hiddenCols, key)) {
          data.columns[hiddenCols[key]].visible = false;
        }
      }

      // Apply loaded state to table
      return data;
    },
  });
});

function deleteMessage() {
  var tr = $(this).closest("tr");
  var id = tr.attr("data-id");

  utils.disableAll();
  utils.showAlert("info", "", "删除ID为" + parseInt(id, 10), "的信息中...");
  $.ajax({
    url: "scripts/pi-hole/php/message.php",
    method: "post",
    dataType: "json",
    data: { action: "delete_message", id: id, token: token },
    success: function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert("success", "far fa-trash-alt", "成功删除信息 # ", id);
        table.row(tr).remove().draw(false).ajax.reload(null, false);
      } else {
        utils.showAlert(
          "error",
          "",
          "删除ID为" + id + "的信息时出错",
          response.message
        );
      }
    },
    error: function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert(
        "error",
        "",
        "删除ID为" + id + "的信息时出错",
        jqXHR.responseText
      );
      console.log(exception); // eslint-disable-line no-console
    },
  });
}
