/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license. */

/* global utils:false */

var table;
var groups = [];
var token = $("#token").text();
var GETDict = {};
var showtype = "all";

function getGroups() {
  $.post(
    "scripts/pi-hole/php/groups.php",
    { action: "get_groups", token: token },
    function (data) {
      groups = data.data;
      initTable();
    },
    "json"
  );
}

$(function () {
  window.location.search
    .substr(1)
    .split("&")
    .forEach(function (item) {
      GETDict[item.split("=")[0]] = item.split("=")[1];
    });

  if ("type" in GETDict && (GETDict.type === "white" || GETDict.type === "black")) {
    showtype = GETDict.type;
  }

  // sync description fields, reset inactive inputs on tab change
  $('a[data-toggle="tab"]').on("shown.bs.tab", function () {
    var tabHref = $(this).attr("href");
    var val;
    if (tabHref === "#tab_domain") {
      val = $("#new_regex_comment").val();
      $("#new_domain_comment").val(val);
      $("#new_regex").val("");
    } else if (tabHref === "#tab_regex") {
      val = $("#new_domain_comment").val();
      $("#new_regex_comment").val(val);
      $("#new_domain").val("");
      $("#wildcard_checkbox").prop("checked", false);
    }
  });

  $("#add2black, #add2white").on("click", addDomain);

  utils.setBsSelectDefaults();
  getGroups();
});

function initTable() {
  table = $("#domainsTable").DataTable({
    ajax: {
      url: "scripts/pi-hole/php/groups.php",
      data: { action: "get_domains", showtype: showtype, token: token },
      type: "POST",
    },
    order: [[0, "asc"]],
    columns: [
      { data: "id", visible: false },
      { data: "domain" },
      { data: "type", searchable: false },
      { data: "enabled", searchable: false },
      { data: "comment" },
      { data: "groups", searchable: false, visible: showtype === "all" },
      { data: null, width: "80px", orderable: false },
    ],
    drawCallback: function () {
      $('button[id^="deleteDomain_"]').on("click", deleteDomain);
      // Remove visible dropdown to prevent orphaning
      $("body > .bootstrap-select.dropdown").remove();
    },
    rowCallback: function (row, data) {
      $(row).attr("data-id", data.id);
      var tooltip =
        "Added: " +
        utils.datetime(data.date_added, false) +
        "\nLast modified: " +
        utils.datetime(data.date_modified, false) +
        "\nDatabase ID: " +
        data.id;
      $("td:eq(0)", row).html(
        '<code id="domain_' +
          data.id +
          '" title="' +
          tooltip +
          '" class="breakall">' +
          data.domain +
          "</code>"
      );

      var whitelistOptions = "";
      if (showtype === "all" || showtype === "white") {
        whitelistOptions =
          '<option value="0"' +
          (data.type === 0 ? " selected" : "") +
          ">确切白名单</option>" +
          '<option value="2"' +
          (data.type === 2 ? " selected" : "") +
          ">正侧表达式白名单</option>";
      }

      var blacklistOptions = "";
      if (showtype === "all" || showtype === "black") {
        blacklistOptions =
          '<option value="1"' +
          (data.type === 1 ? " selected " : " ") +
          ">确切黑名单</option>" +
          '<option value="3"' +
          (data.type === 3 ? " selected" : "") +
          ">正侧表达式黑名单</option>";
      }

      $("td:eq(1)", row).html(
        '<select id="type_' +
          data.id +
          '" class="form-control">' +
          whitelistOptions +
          blacklistOptions +
          "</select>"
      );
      var typeEl = $("#type_" + data.id, row);
      typeEl.on("change", editDomain);

      var disabled = data.enabled === 0;
      $("td:eq(2)", row).html(
        '<input type="checkbox" id="status_' + data.id + '"' + (disabled ? "" : " checked") + ">"
      );
      var statusEl = $("#status_" + data.id, row);
      statusEl.bootstrapToggle({
        on: "启用",
        off: "停用",
        size: "small",
        onstyle: "success",
        width: "80px",
      });
      statusEl.on("change", editDomain);

      $("td:eq(3)", row).html('<input id="comment_' + data.id + '" class="form-control">');
      var commentEl = $("#comment_" + data.id, row);
      commentEl.val(utils.unescapeHtml(data.comment));
      commentEl.on("change", editDomain);

      // Show group assignment field only if in full domain management mode
      if (table.column(5).visible()) {
        $("td:eq(4)", row).empty();
        $("td:eq(4)", row).append(
          '<select class="selectpicker" id="multiselect_' + data.id + '" multiple></select>'
        );
        var selectEl = $("#multiselect_" + data.id, row);
        // Add all known groups
        for (var i = 0; i < groups.length; i++) {
          var dataSub = "";
          if (!groups[i].enabled) {
            dataSub = 'data-subtext="(disabled)"';
          }

          selectEl.append(
            $("<option " + dataSub + "/>")
              .val(groups[i].id)
              .text(groups[i].name)
          );
        }

        // Select assigned groups
        selectEl.val(data.groups);
        // Initialize bootstrap-select
        selectEl
          // fix dropdown if it would stick out right of the viewport
          .on("show.bs.select", function () {
            var winWidth = $(window).width();
            var dropdownEl = $("body > .bootstrap-select.dropdown");
            if (dropdownEl.length > 0) {
              dropdownEl.removeClass("align-right");
              var width = dropdownEl.width();
              var left = dropdownEl.offset().left;
              if (left + width > winWidth) {
                dropdownEl.addClass("align-right");
              }
            }
          })
          .on("changed.bs.select", function () {
            // enable Apply button
            if ($(applyBtn).prop("disabled")) {
              $(applyBtn)
                .addClass("btn-success")
                .prop("disabled", false)
                .on("click", function () {
                  editDomain.call(selectEl);
                });
            }
          })
          .on("hide.bs.select", function () {
            // Restore values if drop-down menu is closed without clicking the Apply button
            if (!$(applyBtn).prop("disabled")) {
              $(this).val(data.groups).selectpicker("refresh");
              $(applyBtn).removeClass("btn-success").prop("disabled", true).off("click");
            }
          })
          .selectpicker()
          .siblings(".dropdown-menu")
          .find(".bs-actionsbox")
          .prepend(
            '<button type="button" id=btn_apply_' +
              data.id +
              ' class="btn btn-block btn-sm" disabled>应用</button>'
          );
      }

      var applyBtn = "#btn_apply_" + data.id;

      // Highlight row (if url parameter "domainid=" is used)
      if ("domainid" in GETDict && data.id === parseInt(GETDict.domainid, 10)) {
        $(row).find("td").addClass("highlight");
      }

      var button =
        '<button type="button" class="btn btn-danger btn-xs" id="deleteDomain_' +
        data.id +
        '">' +
        '<span class="far fa-trash-alt"></span>' +
        "</button>";
      if (table.column(5).visible()) {
        $("td:eq(5)", row).html(button);
      } else {
        $("td:eq(4)", row).html(button);
      }
    },
    dom:
      "<'row'<'col-sm-4'l><'col-sm-8'f>>" +
      "<'row'<'col-sm-12'<'table-responsive'tr>>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "全部"],
    ],
    stateSave: true,
    stateSaveCallback: function (settings, data) {
      utils.stateSaveCallback("groups-domains-table", data);
    },
    stateLoadCallback: function () {
      var data = utils.stateLoadCallback("groups-domains-table");

      // Return if not available
      if (data === null) {
        return null;
      }

      // Reset visibility of ID column
      data.columns[0].visible = false;
      // Show group assignment column only on full page
      data.columns[5].visible = showtype === "all";
      // Apply loaded state to table
      return data;
    },
    initComplete: function () {
      if ("domainid" in GETDict) {
        var pos = table
          .column(0, { order: "current" })
          .data()
          .indexOf(parseInt(GETDict.domainid, 10));
        if (pos >= 0) {
          var page = Math.floor(pos / table.page.info().length);
          table.page(page).draw(false);
        }
      }
    },
  });
  // Disable autocorrect in the search box
  var input = document.querySelector("input[type=search]");
  if (input !== null) {
    input.setAttribute("autocomplete", "off");
    input.setAttribute("autocorrect", "off");
    input.setAttribute("autocapitalize", "off");
    input.setAttribute("spellcheck", false);
  }

  table.on("order.dt", function () {
    var order = table.order();
    if (order[0][0] !== 0 || order[0][1] !== "asc") {
      $("#resetButton").removeClass("hidden");
    } else {
      $("#resetButton").addClass("hidden");
    }
  });
  $("#resetButton").on("click", function () {
    table.order([[0, "asc"]]).draw();
    $("#resetButton").addClass("hidden");
  });
}

function addDomain() {
  var action = this.id;
  var tabHref = $('a[data-toggle="tab"][aria-expanded="true"]').attr("href");
  var wildcardEl = $("#wildcard_checkbox");
  var wildcardChecked = wildcardEl.prop("checked");
  var type;

  // current tab's inputs
  var domainRegex, domainEl, commentEl;
  if (tabHref === "#tab_domain") {
    domainRegex = "域名";
    domainEl = $("#new_domain");
    commentEl = $("#new_domain_comment");
  } else if (tabHref === "#tab_regex") {
    domainRegex = "正侧表达式";
    domainEl = $("#new_regex");
    commentEl = $("#new_regex_comment");
  }

  var domain = utils.escapeHtml(domainEl.val());
  var comment = utils.escapeHtml(commentEl.val());

  utils.disableAll();
  utils.showAlert("info", "", "添加" + domainRegex + "中...", domain);

  if (domain.length > 0) {
    // strip "*." if specified by user in wildcard mode
    if (domainRegex === "域名" && wildcardChecked && domain.startsWith("*.")) {
      domain = domain.substr(2);
    }

    // determine list type
    if (domainRegex === "域名" && action === "add2black" && wildcardChecked) {
      type = "3W";
    } else if (domainRegex === "域名" && action === "add2black" && !wildcardChecked) {
      type = "1";
    } else if (domainRegex === "域名" && action === "add2white" && wildcardChecked) {
      type = "2W";
    } else if (domainRegex === "域名" && action === "add2white" && !wildcardChecked) {
      type = "0";
    } else if (domainRegex === "正侧表达式" && action === "add2black") {
      type = "3";
    } else if (domainRegex === "正侧表达式" && action === "add2white") {
      type = "2";
    }
  } else {
    utils.enableAll();
    utils.showAlert("warning", "", "警告", "请指定一个" + domainRegex);
    return;
  }

  $.ajax({
    url: "scripts/pi-hole/php/groups.php",
    method: "post",
    dataType: "json",
    data: {
      action: "add_domain",
      domain: domain,
      type: type,
      comment: comment,
      token: token,
    },
    success: function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert("success", "fas fa-plus", "成功！", response.message);
        domainEl.val("");
        commentEl.val("");
        wildcardEl.prop("checked", false);
        table.ajax.reload(null, false);
      } else {
        utils.showAlert("error", "", "添加" + domainRegex + "时出错", response.message);
      }
    },
    error: function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert("error", "", "添加" + domainRegex + "时出错", jqXHR.responseText);
      console.log(exception); // eslint-disable-line no-console
    },
  });
}

function editDomain() {
  var elem = $(this).attr("id");
  var tr = $(this).closest("tr");
  var id = tr.attr("data-id");
  var domain = utils.escapeHtml(tr.find("#domain_" + id).text());
  var type = tr.find("#type_" + id).val();
  var status = tr.find("#status_" + id).is(":checked") ? 1 : 0;
  var comment = utils.escapeHtml(tr.find("#comment_" + id).val());

  // Show group assignment field only if in full domain management mode
  // if not included, just use the row data.
  var rowData = table.row(tr).data();
  var groups = table.column(5).visible() ? tr.find("#multiselect_" + id).val() : rowData.groups;

  var domainRegex;
  if (type === "0" || type === "1") {
    domainRegex = "域名";
  } else if (type === "2" || type === "3") {
    domainRegex = "正侧表达式";
  }

  var done = "修改";
  var notDone = "正在修改";
  switch (elem) {
    case "status_" + id:
      if (status === 0) {
        done = "停用";
        notDone = "正在停用";
      } else if (status === 1) {
        done = "启用";
        notDone = "正在启用";
      }

      break;
    case "name_" + id:
      done = "修改名称，";
      notDone = "正在修改名称";
      break;
    case "comment_" + id:
      done = "修改描述，";
      notDone = "正在修改描述";
      break;
    case "type_" + id:
      done = "修改类型，";
      notDone = "正在修改类型";
      break;
    case "multiselect_" + id:
      done = "修改群组，";
      notDone = "正在修改群组";
      break;
    default:
      alert("元素错误或数据id无效！");
      return;
  }

  utils.disableAll();
  utils.showAlert("info", "", "修改" + domainRegex + "中...", name);
  $.ajax({
    url: "scripts/pi-hole/php/groups.php",
    method: "post",
    dataType: "json",
    data: {
      action: "edit_domain",
      id: id,
      type: type,
      comment: comment,
      status: status,
      groups: groups,
      token: token,
    },
    success: function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert(
          "success",
          "fas fa-pencil-alt",
          "已成功" + done + " " + domainRegex + "：",
          domain
        );
        table.ajax.reload(null, false);
      } else
        utils.showAlert(
          "error",
          "",
          "ID为" + id + "的" + domainRegex + "，" + notDone + "的过程中出错",
          response.message
        );
    },
    error: function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert(
        "error",
        "",
        "ID为" + id + "的" + domainRegex + "，" + notDone + "的过程中出错",
        jqXHR.responseText
      );
      console.log(exception); // eslint-disable-line no-console
    },
  });
}

function deleteDomain() {
  var tr = $(this).closest("tr");
  var id = tr.attr("data-id");
  var domain = utils.escapeHtml(tr.find("#domain_" + id).text());
  var type = tr.find("#type_" + id).val();

  var domainRegex;
  if (type === "0" || type === "1") {
    domainRegex = "域名";
  } else if (type === "2" || type === "3") {
    domainRegex = "正侧表达式";
  }

  utils.disableAll();
  utils.showAlert("info", "", "删除 " + domainRegex + "中...", domain);
  $.ajax({
    url: "scripts/pi-hole/php/groups.php",
    method: "post",
    dataType: "json",
    data: { action: "delete_domain", id: id, token: token },
    success: function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert(
          "success",
          "far fa-trash-alt",
          "已删除" + domainRegex,
          domain
        );
        table.row(tr).remove().draw(false).ajax.reload(null, false);
      } else {
        utils.showAlert(
          "error",
          "",
          "删除ID为" + id + "的" + domainRegex + "时出错",
          response.message
        );
      }
    },
    error: function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert(
        "error",
        "",
        "删除ID为" + id + "的" + domainRegex + "时出错",
        jqXHR.responseText
      );
      console.log(exception); // eslint-disable-line no-console
    },
  });
}
