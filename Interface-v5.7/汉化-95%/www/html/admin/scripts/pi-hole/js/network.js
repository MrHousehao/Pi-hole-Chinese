/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license.  */

/* global utils:false */

var tableApi;

var API_STRING = "api_db.php?network";

// How many IPs do we show at most per device?
var MAXIPDISPLAY = 3;

var DAY_IN_SECONDS = 24 * 60 * 60;

function handleAjaxError(xhr, textStatus) {
  if (textStatus === "timeout") {
    alert("服务器发送数据时间过长。");
  } else if (xhr.responseText.indexOf("Connection refused") !== -1) {
    alert("加载数据时出错：连接被拒绝。请确认FTL是否已运行。");
  } else {
    alert("加载数据时发生未知错误。\n" + xhr.responseText);
  }

  $("#network-entries_processing").hide();
  tableApi.clear();
  tableApi.draw();
}

function getTimestamp() {
  return Math.floor(Date.now() / 1000);
}

function valueToHex(c) {
  var hex = Math.round(c).toString(16);
  return hex.length === 1 ? "0" + hex : hex;
}

function rgbToHex(values) {
  return "#" + valueToHex(values[0]) + valueToHex(values[1]) + valueToHex(values[2]);
}

function mixColors(ratio, rgb1, rgb2) {
  return [
    (1 - ratio) * rgb1[0] + ratio * rgb2[0],
    (1 - ratio) * rgb1[1] + ratio * rgb2[1],
    (1 - ratio) * rgb1[2] + ratio * rgb2[2],
  ];
}

function parseColor(input) {
  var match = input.match(/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i);

  if (match) {
    return [match[1], match[2], match[3]];
  }
}

$(function () {
  tableApi = $("#network-entries").DataTable({
    rowCallback: function (row, data) {
      var color;
      var index;
      var maxiter;
      var iconClasses;
      var lastQuery = parseInt(data.lastQuery, 10);
      var diff = getTimestamp() - lastQuery;
      var networkRecent = $(".network-recent").css("background-color");
      var networkOld = $(".network-old").css("background-color");
      var networkOlder = $(".network-older").css("background-color");
      var networkNever = $(".network-never").css("background-color");

      if (lastQuery > 0) {
        if (diff <= DAY_IN_SECONDS) {
          // Last query came in within the last 24 hours
          // Color: light-green to light-yellow
          var ratio = Number(diff) / DAY_IN_SECONDS;
          color = rgbToHex(mixColors(ratio, parseColor(networkRecent), parseColor(networkOld)));
          iconClasses = "fas fa-check";
        } else {
          // Last query was longer than 24 hours ago
          // Color: light-orange
          color = networkOlder;
          iconClasses = "fas fa-question";
        }
      } else {
        // This client has never sent a query to Pi-hole, color light-red
        color = networkNever;
        iconClasses = "fas fa-times";
      }

      // Set determined background color
      $(row).css("background-color", color);
      $("td:eq(7)", row).html('<i class="' + iconClasses + '"></i>');

      // Insert "Never" into Last Query field when we have
      // never seen a query from this device
      if (data.lastQuery === 0) {
        $("td:eq(5)", row).html("Never");
      }

      // Set hostname to "unknown" if not available
      if (!data.name || data.name.length === 0) {
        $("td:eq(3)", row).html("<em>unknown</em>");
      } else {
        var names = [];
        var name = "";
        maxiter = Math.min(data.name.length, MAXIPDISPLAY);
        index = 0;
        for (index = 0; index < maxiter; index++) {
          name = data.name[index];
          if (name.length === 0) continue;
          names.push('<a href="queries.php?client=' + name + '">' + name + "</a>");
        }

        if (data.name.length > MAXIPDISPLAY) {
          // We hit the maximum above, add "..." to symbolize we would
          // have more to show here
          names.push("...");
        }

        maxiter = Math.min(data.ip.length, data.name.length);
        var allnames = [];
        for (index = 0; index < maxiter; index++) {
          name = data.name[index];
          if (name.length > 0) {
            allnames.push(name + " (" + data.ip[index] + ")");
          } else {
            allnames.push("" + data.ip[index] + "的主机名称未知");
          }
        }

        $("td:eq(3)", row).html(names.join("<br>"));
        $("td:eq(3)", row).hover(function () {
          this.title = allnames.join("\n");
        });
      }

      // Set number of queries to localized string (add thousand separators)
      $("td:eq(6)", row).html(data.numQueries.toLocaleString());

      var ips = [];
      maxiter = Math.min(data.ip.length, MAXIPDISPLAY);
      index = 0;
      for (index = 0; index < maxiter; index++) {
        var ip = data.ip[index];
        ips.push('<a href="queries.php?client=' + ip + '">' + ip + "</a>");
      }

      if (data.ip.length > MAXIPDISPLAY) {
        // We hit the maximum above, add "..." to symbolize we would
        // have more to show here
        ips.push("...");
      }

      $("td:eq(0)", row).html(ips.join("<br>"));
      $("td:eq(0)", row).hover(function () {
        this.title = data.ip.join("\n");
      });

      // MAC + Vendor field if available
      if (data.macVendor && data.macVendor.length > 0) {
        $("td:eq(1)", row).html(data.hwaddr + "<br/>" + data.macVendor);
      }

      // Hide mock MAC addresses
      if (data.hwaddr.startsWith("ip-")) {
        $("td:eq(1)", row).text("N/A");
      }
    },
    dom:
      "<'row'<'col-sm-12'f>>" +
      "<'row'<'col-sm-4'l><'col-sm-8'p>>" +
      "<'row'<'col-sm-12'<'table-responsive'tr>>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    ajax: { url: API_STRING, error: handleAjaxError, dataSrc: "network" },
    autoWidth: false,
    processing: true,
    order: [[5, "desc"]],
    columns: [
      { data: "ip", type: "ip-address", width: "5%", render: $.fn.dataTable.render.text() },
      { data: "hwaddr", width: "12%", render: $.fn.dataTable.render.text() },
      { data: "interface", width: "6%", render: $.fn.dataTable.render.text() },
      { data: "name", width: "8%", render: $.fn.dataTable.render.text() },
      {
        data: "firstSeen",
        width: "10%",
        render: function (data, type) {
          if (type === "display") {
            return utils.datetime(data);
          }

          return data;
        },
      },
      {
        data: "lastQuery",
        width: "10%",
        render: function (data, type) {
          if (type === "display") {
            return utils.datetime(data);
          }

          return data;
        },
      },
      { data: "numQueries", width: "10%", render: $.fn.dataTable.render.text() },
      { data: "", width: "7%", orderable: false },
    ],
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "全部"],
    ],
    stateSave: true,
    stateSaveCallback: function (settings, data) {
      utils.stateSaveCallback("network_table", data);
    },
    stateLoadCallback: function () {
      return utils.stateLoadCallback("network_table");
    },
    columnDefs: [
      {
        targets: -1,
        data: null,
        defaultContent: "",
      },
    ],
  });
  // Disable autocorrect in the search box
  var input = document.querySelector("input[type=search]");
  input.setAttribute("autocomplete", "off");
  input.setAttribute("autocorrect", "off");
  input.setAttribute("autocapitalize", "off");
  input.setAttribute("spellcheck", false);
});
