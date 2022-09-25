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

function multiline(input) {
  return input.split(",").join("\n");
}

function renderMessage(data, type, row) {
  // Display and search content
  switch (row.type) {
    case "REGEX":
      return (
        '处理时遇到错误 <a href="groups-domains.php?domainid=' +
        row.blob3 +
        '">' +
        row.blob1 +
        " 正则表达式ID为 " +
        row.blob3 +
        "</a>:<pre>" +
        row.blob2 +
        "</pre>错误信息：<pre>" +
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
        multiline(row.blob2) +
        "</pre>" +
        "FTL 选择了最近的条目 <pre>" +
        row.blob4 +
        "</pre>获取此客户端的群组配置。"
      );

    case "HOSTNAME":
      // eslint-disable-next-line unicorn/no-new-array
      var hint = new Array(row.blob2 + row.message.length + 3).join(" ");
      return (
        "主机名包含无效字符 <code>" +
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
        "客户端" +
        row.message +
        "已受到频率限制（当前设置仅允许" +
        parseInt(row.blob2, 10) +
        "秒内" +
        parseInt(row.blob1, 10) +
        "次的查询请求）"
								 
				   
      );

    case "DNSMASQ_WARN":
      return (
        "<code>dnsmasq</code>核心警告：<pre>" +
        row.message +
        '</pre>查看<a href="https://docs.pi-hole.net/ftldns/dnsmasq_warn/" target="_blank">Pi-hole支持文档</a>以获取更多信息。'
      );

    case "LOAD":
      return (
        "平均负载（15分钟内）超出可处理数量：<strong>" +
        parseFloat(row.blob1).toFixed(1) +
        " &gt; " +
        parseInt(row.blob2, 10) +
        "</strong><br>这可能会降低 DNS 的解析速度，并导致阻塞。"
      );

    case "SHMEM":
      return (
        "RAM 不足（<code>" +
        utils.escapeHtml(row.message) +
        "</code>）预置：<strong>" +
        parseInt(row.blob1, 10) +
        "% 使用</strong><pre>" +
        utils.escapeHtml(row.blob2) +
        "</pre>"
      );

    case "DISK":
      return (
        "磁盘不足（<code>" +
        utils.escapeHtml(row.message) +
        "</code>）预置：<strong>" +
        parseInt(row.blob1, 10) +
        "% 使用</strong><pre>" +
        utils.escapeHtml(row.blob2) +
        "</pre>"
      );

    case "ADLIST":
      return (
        '<a href="groups-adlists.php?adlistid=' +
        parseInt(row.blob1, 10) +
        '">' +
        "引力场 ID 为" +
        parseInt(row.blob1, 10) +
        "</a>在上一次引力场测试中无法访问。" +
        "<pre>" +
        utils.escapeHtml(row.message) +
        "</pre>"
      );

    default:
      return "未知消息类型<pre>" + JSON.stringify(row) + "</pre>";
  }
}

$(function () {
  var ignoreNonfatal = localStorage
    ? localStorage.getItem("hideNonfatalDnsmasqWarnings_chkbox") === "true"
    : false;
  table = $("#messagesTable").DataTable({
    ajax: {
      url: "api_db.php?messages" + (ignoreNonfatal ? "&ignore=DNSMASQ_WARN" : ""),
      data: { token: token },
      type: "POST",
      dataSrc: "messages",
    },
    order: [[0, "asc"]],
    columns: [
      { data: "id", visible: false },
      { data: null, visible: true, width: "15px" },
      { data: "timestamp", width: "8%", render: renderTimestamp },
      { data: "type", width: "8%" },
      { data: "message", orderable: false, render: renderMessage },
      { data: "blob1", visible: false },
      { data: "blob2", visible: false },
      { data: "blob3", visible: false },
      { data: "blob4", visible: false },
      { data: "blob5", visible: false },
      { data: null, width: "22px", orderable: false },
    ],
    columnDefs: [
      {
        targets: 1,
        orderable: false,
        className: "select-checkbox",
        render: function () {
          return "";
        },
      },
      {
        targets: "_all",
        render: $.fn.dataTable.render.text(),
      },
    ],
    drawCallback: function () {
      $('button[id^="deleteMessage_"]').on("click", deleteMessage);

      // Hide buttons if all messages were deleted
      var hasRows = this.api().rows({ filter: "applied" }).data().length > 0;
      $(".datatable-bt").css("visibility", hasRows ? "visible" : "hidden");

      // Remove visible dropdown to prevent orphaning
      $("body > .bootstrap-select.dropdown").remove();
    },
    rowCallback: function (row, data) {
      $(row).attr("data-id", data.id);
      var button =
        '<button type="button" class="btn btn-danger btn-xs" id="deleteMessage_' +
        data.id +
        '" data-del-id="' +
        data.id +
        '">' +
        '<span class="far fa-trash-alt"></span>' +
        "</button>";
      $("td:eq(4)", row).html(button);
    },
    select: {
      style: "multi",
      selector: "td:not(:last-child)",
      info: false,
    },
    buttons: [
      {
        text: '<span class="far fa-square"></span>',
        titleAttr: "全选",
        className: "btn-sm datatable-bt selectAll",
        action: function () {
          table.rows({ page: "current" }).select();
        },
      },
      {
        text: '<span class="far fa-plus-square"></span>',
        titleAttr: "全选",
        className: "btn-sm datatable-bt selectMore",
        action: function () {
          table.rows({ page: "current" }).select();
        },
      },
      {
        extend: "selectNone",
        text: '<span class="far fa-check-square"></span>',
        titleAttr: "取消选择",
        className: "btn-sm datatable-bt removeAll",
      },
      {
        text: '<span class="far fa-trash-alt"></span>',
        titleAttr: "删除所选",
        className: "btn-sm datatable-bt deleteSelected",
        action: function () {
          // For each ".selected" row ...
          var ids = [];
          $("tr.selected").each(function () {
            // ... add the row identified by "data-id".
            ids.push(parseInt($(this).attr("data-id"), 10));
          });
          // Delete all selected rows at once
          delMsg(ids);
        },
      },
    ],
    dom:
      "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
      "<'row'<'col-sm-3'B><'col-sm-9'p>>" +
      "<'row'<'col-sm-12'<'table-responsive'tr>>>" +
      "<'row'<'col-sm-3'B><'col-sm-9'p>>" +
      "<'row'<'col-sm-12'i>>",
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
      var hiddenCols = [0, 5, 6, 7, 8, 9];
      for (var key in hiddenCols) {
        if (Object.prototype.hasOwnProperty.call(hiddenCols, key)) {
          data.columns[hiddenCols[key]].visible = false;
        }
      }

      // Apply loaded state to table
      return data;
    },
  });
  table.on("init select deselect", function () {
    changeButtonStates();
  });
});

// Show only the appropriate buttons
function changeButtonStates() {
  var allRows = table.rows({ filter: "applied" }).data().length;
  var pageLength = table.page.len();
  var selectedRows = table.rows(".selected").data().length;

  if (selectedRows === 0) {
    // Nothing selected
    $(".selectAll").removeClass("hidden");
    $(".selectMore").addClass("hidden");
    $(".removeAll").addClass("hidden");
    $(".deleteSelected").addClass("hidden");
  } else if (selectedRows >= pageLength || selectedRows === allRows) {
    // Whole page is selected (or all available messages were selected)
    $(".selectAll").addClass("hidden");
    $(".selectMore").addClass("hidden");
    $(".removeAll").removeClass("hidden");
    $(".deleteSelected").removeClass("hidden");
  } else {
    // Some rows are selected, but not all
    $(".selectAll").addClass("hidden");
    $(".selectMore").removeClass("hidden");
    $(".removeAll").addClass("hidden");
    $(".deleteSelected").removeClass("hidden");
  }
}

// Remove 'bnt-group' class from container, to avoid grouping
$.fn.dataTable.Buttons.defaults.dom.container.className = "dt-buttons";

function deleteMessage() {
  // Passes the button data-del-id attribute as ID
  var ids = [parseInt($(this).attr("data-del-id"), 10)];
  delMsg(ids);
}

function delMsg(ids) {
  // Check input validity
  if (!Array.isArray(ids)) return;

  // Exploit prevention: Return early for non-numeric IDs
  for (var id in ids) {
    if (Object.hasOwnProperty.call(ids, id) && typeof ids[id] !== "number") return;
  }

  utils.disableAll();
  var idstring = ids.join(", ");
  utils.showAlert("info", "", "删除信息：" + idstring, "中...");

  $.ajax({
    url: "scripts/pi-hole/php/message.php",
    method: "post",
    dataType: "json",
    data: { action: "delete_message", id: JSON.stringify(ids), token: token },
  })
    .done(function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert(
          "success",
          "far fa-trash-alt",
          "成功删除信息：" + idstring,
          ""
        );
        for (var id in ids) {
          if (Object.hasOwnProperty.call(ids, id)) {
            table.row(id).remove().draw(false).ajax.reload(null, false);
          }
        }
      } else {
        utils.showAlert("error", "", "删除信息：" + idstring + "时出错", response.message);
      }

      // Clear selection after deletion
      table.rows().deselect();
      changeButtonStates();
    })
    .done(
      utils.checkMessages // Update icon warnings count
    )
    .fail(function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert("error", "", "删除信息：" + idstring + "时出错", jqXHR.responseText);
      console.log(exception); // eslint-disable-line no-console
    });
}
