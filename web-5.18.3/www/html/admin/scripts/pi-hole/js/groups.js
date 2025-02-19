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
  $("#btnAdd").on("click", addGroup);

  table = $("#groupsTable").DataTable({
    ajax: {
      url: "scripts/pi-hole/php/groups.php",
      data: { action: "get_groups", token: token },
      type: "POST",
    },
    order: [[0, "asc"]],
    columns: [
      { data: "id", visible: false },
      { data: null, visible: true, orderable: false, width: "15px" },
      { data: "name" },
      { data: "enabled", searchable: false },
      { data: "description" },
      { data: null, width: "22px", orderable: false },
    ],
    columnDefs: [
      {
        targets: 1,
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
      // Hide buttons if all groups were deleted
      var hasRows = this.api().rows({ filter: "applied" }).data().length > 0;
      $(".datatable-bt").css("visibility", hasRows ? "visible" : "hidden");

      $('button[id^="deleteGroup_"]').on("click", deleteGroup);
    },
    rowCallback: function (row, data) {
      $(row).attr("data-id", data.id);
      var tooltip =
        "添加时间：" +
        utils.datetime(data.date_added, false) +
        "\n上次修改：" +
        utils.datetime(data.date_modified, false) +
        "\n数据库ID：" +
        data.id;
      $("td:eq(1)", row).html(
        '<input id="name_' + data.id + '" title="' + tooltip + '" class="form-control">'
      );
      var nameEl = $("#name_" + data.id, row);
      nameEl.val(utils.unescapeHtml(data.name));
      nameEl.on("change", editGroup);

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
      statusEl.on("change", editGroup);

      $("td:eq(3)", row).html('<input id="desc_' + data.id + '" class="form-control">');
      var desc = data.description !== null ? data.description : "";
      var descEl = $("#desc_" + data.id, row);
      descEl.val(utils.unescapeHtml(desc));
      descEl.on("change", editGroup);

      $("td:eq(4)", row).empty();
      if (data.id !== 0) {
        var button =
          '<button type="button" class="btn btn-danger btn-xs" id="deleteGroup_' +
          data.id +
          '" data-del-id="' +
          data.id +
          '">' +
          '<span class="far fa-trash-alt"></span>' +
          "</button>";
        $("td:eq(4)", row).html(button);
      }
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
          delItems(ids);
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
    stateSave: true,
    stateDuration: 0,
    stateSaveCallback: function (settings, data) {
      utils.stateSaveCallback("groups-table", data);
    },
    stateLoadCallback: function () {
      var data = utils.stateLoadCallback("groups-table");

      // Return if not available
      if (data === null) {
        return null;
      }

      // Reset visibility of ID column
      data.columns[0].visible = false;
      // Apply loaded state to table
      return data;
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

  table.on("init select deselect", function () {
    utils.changeBulkDeleteStates(table);
  });

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
});

// Remove 'bnt-group' class from container, to avoid grouping
$.fn.dataTable.Buttons.defaults.dom.container.className = "dt-buttons";

function deleteGroup() {
  // Passes the button data-del-id attribute as ID
  var ids = [parseInt($(this).attr("data-del-id"), 10)];
  delItems(ids);
}

function delItems(ids) {
  // Check input validity
  if (!Array.isArray(ids)) return;

  var items = "";

  for (var id of ids) {
    // Exploit prevention: Return early for non-numeric IDs
    if (typeof id !== "number") return;

    // List deleted items
    items += "<li><i>" + utils.escapeHtml($("#name_" + id).val()) + "</i></li>";
  }

  utils.disableAll();
  var idstring = ids.join(", ");
  utils.showAlert("info", "", "删除群组", "<ul>" + items + "</ul>中...");

  $.ajax({
    url: "scripts/pi-hole/php/groups.php",
    method: "post",
    dataType: "json",
    data: { action: "delete_group", id: JSON.stringify(ids), token: token },
  })
    .done(function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert(
          "success",
          "far fa-trash-alt",
          "已成功删除群组：",
          "<ul>" + items + "</ul>"
        );
        for (var id in ids) {
          if (Object.hasOwnProperty.call(ids, id)) {
            table.row(id).remove().draw(false).ajax.reload(null, false);
          }
        }
      } else {
        utils.showAlert(
          "error",
          "",
          "删除" + idstring,
          response.message + "群组时出错"
        );
      }

      // Clear selection after deletion
      table.rows().deselect();
      utils.changeBulkDeleteStates(table);
    })
    .fail(function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert(
        "error",
        "",
        "删除" + idstring,
        jqXHR.responseText + "群组时出错"
      );
      console.log(exception); // eslint-disable-line no-console
    });
}

function addGroup() {
  var name = utils.escapeHtml($("#new_name").val());
  var desc = utils.escapeHtml($("#new_desc").val());

  utils.disableAll();
  utils.showAlert("info", "", "添加群组中...", name);

  if (name.length === 0) {
    // enable the ui elements again
    utils.enableAll();
    utils.showAlert("warning", "", "警告", "请指定一个群组名称");
    return;
  }

  $.ajax({
    url: "scripts/pi-hole/php/groups.php",
    method: "post",
    dataType: "json",
    data: { action: "add_group", name: name, desc: desc, token: token },
    success: function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert("success", "fas fa-plus", "成功添加群组", name);
        $("#new_name").val("");
        $("#new_desc").val("");
        table.ajax.reload();
        table.rows().deselect();
        $("#new_name").focus();
      } else {
        utils.showAlert("error", "", "添加新群组时出错", response.message);
      }
    },
    error: function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert("error", "", "添加新群组时出错", jqXHR.responseText);
      console.log(exception); // eslint-disable-line no-console
    },
  });
}

function editGroup() {
  var elem = $(this).attr("id");
  var tr = $(this).closest("tr");
  var id = tr.attr("data-id");
  var name = utils.escapeHtml(tr.find("#name_" + id).val());
  var status = tr.find("#status_" + id).is(":checked") ? 1 : 0;
  var desc = utils.escapeHtml(tr.find("#desc_" + id).val());

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
    case "desc_" + id:
      done = "修改描述，";
      notDone = "正在修改描述";
      break;
    default:
      alert("元素错误或数据id无效！");
      return;
  }

  utils.disableAll();
  utils.showAlert("info", "", "修改群组中...", name);
  $.ajax({
    url: "scripts/pi-hole/php/groups.php",
    method: "post",
    dataType: "json",
    data: {
      action: "edit_group",
      id: id,
      name: name,
      desc: desc,
      status: status,
      token: token,
    },
    success: function (response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert("success", "fas fa-pencil-alt", "成功" + done + " 群组：", name);
      } else {
        utils.showAlert(
          "error",
          "",
          "群组ID为" + id + "在" + notDone + "出错",
          response.message
        );
      }
    },
    error: function (jqXHR, exception) {
      utils.enableAll();
      utils.showAlert(
        "error",
        "",
        "群组ID为" + id + "在" + notDone + "出错",
        jqXHR.responseText
      );
      console.log(exception); // eslint-disable-line no-console
    },
  });
}
