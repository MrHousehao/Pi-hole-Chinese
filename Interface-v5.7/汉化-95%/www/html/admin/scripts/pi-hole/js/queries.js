/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license.  */

/* global moment:false, utils:false */

var tableApi;
var tableFilters = [];

var replyTypes = [
  "N/A",
  "NODATA",
  "NXDOMAIN",
  "CNAME",
  "IP",
  "DOMAIN",
  "RRNAME",
  "SERVFAIL",
  "REFUSED",
  "NOTIMP",
  "upstream error",
  "DNSSEC",
  "NONE",
  "BLOB",
];
var colTypes = ["时间","查询类型","域名","客户端","状态","响应类型"];

function handleAjaxError(xhr, textStatus) {
  if (textStatus === "timeout") {
    alert("服务器发送数据时间过长。");
  } else if (xhr.responseText.indexOf("Connection refused") !== -1) {
    alert("加载数据时出错：连接被拒绝。请确认FTL是否已运行。");
  } else {
    alert("加载数据时发生未知错误。\n" + xhr.responseText);
  }

  $("#all-queries_processing").hide();
  tableApi.clear();
  tableApi.draw();
}

$(function () {
  // Do we want to filter queries?
  var GETDict = {};
  window.location.search
    .substr(1)
    .split("&")
    .forEach(function (item) {
      GETDict[item.split("=")[0]] = item.split("=")[1];
    });

  var APIstring = "api.php?getAllQueries";

  if ("from" in GETDict && "until" in GETDict) {
    APIstring += "&from=" + GETDict.from;
    APIstring += "&until=" + GETDict.until;
  } else if ("client" in GETDict) {
    APIstring += "&client=" + GETDict.client;
  } else if ("domain" in GETDict) {
    APIstring += "&domain=" + GETDict.domain;
  } else if ("querytype" in GETDict) {
    APIstring += "&querytype=" + GETDict.querytype;
  } else if ("forwarddest" in GETDict) {
    APIstring += "&forwarddest=" + GETDict.forwarddest;
  }
  // If we don't ask filtering and also not for all queries, just request the most recent 100 queries
  else if (!("all" in GETDict)) {
    APIstring += "=100";
  }

  if ("type" in GETDict) {
    APIstring += "&type=" + GETDict.type;
  }

  tableApi = $("#all-queries").DataTable({
    rowCallback: function (row, data) {
      var replyid = parseInt(data[5], 10);
      // DNSSEC status
      var dnssecStatus;
      var ede = data[11] ? data[11] : "";
      switch (data[6]) {
        case "1":
          dnssecStatus = '<br><span class="text-green">SECURE';
          break;
        case "2":
          dnssecStatus = '<br><span class="text-orange">INSECURE';
          break;
        case "3":
          dnssecStatus = '<br><span class="text-red">BOGUS';
          break;
        case "4":
          dnssecStatus = '<br><span class="text-red">ABANDONED';
          break;
        case "5":
          dnssecStatus = '<br><span class="text-orange">UNKNOWN';
          break;
        default:
          // No DNSSEC
          dnssecStatus = "";
      }

      if (dnssecStatus.length > 0) {
        if (ede.length > 0) dnssecStatus += " (" + ede + ")";
        else if (replyid === 7) dnssecStatus += " (refused upstream)";
        dnssecStatus += "</span>";
      }

      // Query status
      var fieldtext,
        buttontext = "",
        blocked = false,
        isCNAME = false,
        regexLink = false;

      switch (data[4]) {
        case "1":
          fieldtext = "<span class='text-red'>吞噬（引力场）</span>";
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          blocked = true;
          break;
        case "2":
          fieldtext =
            replyid === 0
              ? "<span class='text-green'>OK</span>，转发至"
              : "<span class='text-green'>OK</span>，回应自";
          fieldtext +=
            "<br class='hidden-lg'>" +
            (data.length > 10 && data[10] !== "N/A" ? data[10] : "") +
            dnssecStatus;
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-red"><i class="fa fa-ban"></i> 添加到黑名单</button>';
          break;
        case "3":
          fieldtext =
            "<span class='text-green'>OK</span> <br class='hidden-lg'>（缓存）" + dnssecStatus;
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-red"><i class="fa fa-ban"></i> 添加到黑名单</button>';
          break;
        case "4":
          fieldtext = "<span class='text-red'>吞噬<br class='hidden-lg'>（正则表达式黑名单）";
          blocked = true;
          if (data.length > 9 && data[9] > 0) {
            regexLink = true;
          }

          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          break;
        case "5":
          fieldtext = "<span class='text-red'>吞噬<br class='hidden-lg'>（确切黑名单）";
          blocked = true;
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          break;
        case "6":
          fieldtext = "<span class='text-red'>吞噬<br class='hidden-lg'>（外部，IP）";
          blocked = true;
          buttontext = "";
          break;
        case "7":
          fieldtext =
            "<span class='text-red'>吞噬<br class='hidden-lg'>（外部，NULL）</span>";
          blocked = true;
          buttontext = "";
          break;
        case "8":
          fieldtext =
            "<span class='text-red'>吞噬<br class='hidden-lg'>（外部，NXRA）</span>";
          blocked = true;
          buttontext = "";
          break;
        case "9":
          fieldtext = "<span class='text-red'>吞噬（引力场，CNAME）</span>";
          blocked = true;
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          isCNAME = true;
          break;
        case "10":
          fieldtext =
            "<span class='text-red'>吞噬<br class='hidden-lg'>（正则表达式黑名单，CNAME）</span>";

          if (data.length > 9 && data[9] > 0) {
            regexLink = true;
          }

          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          isCNAME = true;
          break;
        case "11":
          fieldtext =
            "<span class='text-red'>吞噬<br class='hidden-lg'>（确切黑名单，CNAME）</span>";
          blocked = true;
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          isCNAME = true;
          break;
        case "12":
          fieldtext = "<span class='text-green'>重试</span>";
          break;
        case "13":
          fieldtext = "<span class='text-green'>重试</span> <br class='hidden-lg'>（忽略）";
          break;
        case "14":
          fieldtext =
            "<span class='text-green'>OK</span> <br class='hidden-lg'>（已转发）" +
            dnssecStatus;
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-red"><i class="fa fa-ban"></i> 添加到黑名单</button>';
          break;
        case "15":
          fieldtext =
            "<span class='text-orange'>吞噬<br class='hidden-lg'>（数据库正忙）</span>";
          blocked = true;
          break;
        default:
          fieldtext = "未知 (" + parseInt(data[4], 10) + ")";
      }

      // Add EDE here if available and not included in dnssecStatus
      if (ede.length > 0 && dnssecStatus.length === 0) fieldtext += " (" + ede + ")";

      // Cannot block internal queries of this type
      if ((data[1] === "DNSKEY" || data[1] === "DS") && data[3] === "pi.hole") buttontext = "";

      fieldtext += '<input type="hidden" name="id" value="' + parseInt(data[4], 10) + '">';

      $(row).addClass(blocked === true ? "blocked-row" : "allowed-row");
      if (localStorage.getItem("colorfulQueryLog_chkbox") === "true") {
        $(row).addClass(blocked === true ? "text-red" : "text-green");
      }

      $("td:eq(4)", row).html(fieldtext);
      $("td:eq(6)", row).html(buttontext);

      if (regexLink) {
        $("td:eq(4)", row).hover(
          function () {
            this.title = "Click to show matching regex filter";
            this.style.color = "#72afd2";
          },
          function () {
            this.style.color = "";
          }
        );
        $("td:eq(4)", row).off(); // Release any possible previous onClick event handlers
        $("td:eq(4)", row).click(function () {
          var newTab = window.open("groups-domains.php?domainid=" + data[9], "_blank");
          if (newTab) {
            newTab.focus();
          }
        });
        $("td:eq(4)", row).addClass("text-underline pointer");
      }

      // Substitute domain by "." if empty
      var domain = data[2];
      if (domain.length === 0) {
        domain = ".";
      }

      if (isCNAME) {
        var CNAMEDomain = data[8];
        // Add domain in CNAME chain causing the query to have been blocked
        $("td:eq(2)", row).text(domain + "\n（吞噬" + CNAMEDomain + "）");
      } else {
        $("td:eq(2)", row).text(domain);
      }

      // Check for existence of sixth column and display only if not Pi-holed
      var replytext =
        replyid >= 0 && replyid < replyTypes.length ? replyTypes[replyid] : "? (" + replyid + ")";

      replytext += '<input type="hidden" name="id" value="' + replyid + '">';

      $("td:eq(5)", row).html(replytext);

      // Show response time only when reply is not N/A
      if (data.length > 7 && replyid !== 0) {
        var content = $("td:eq(5)", row).html();
        $("td:eq(5)", row).html(content + " (" + (0.1 * data[7]).toFixed(1) + "ms)");
      }
    },
    dom:
      "<'row'<'col-sm-12'f>>" +
      "<'row'<'col-sm-4'l><'col-sm-8'p>>" +
      "<'row'<'col-sm-12'<'table-responsive'tr>>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    ajax: {
      url: APIstring,
      error: handleAjaxError,
      dataSrc: function (data) {
        var dataIndex = 0;
        return data.data.map(function (x) {
          x[0] = x[0] * 1e6 + dataIndex++;
          var dnssec = x[5];
          var reply = x[6];
          x[5] = reply;
          x[6] = dnssec;
          return x;
        });
      },
    },
    autoWidth: false,
    processing: true,
    order: [[0, "desc"]],
    columns: [
      {
        width: "10%",
        render: function (data, type) {
          if (type === "display") {
            return moment
              .unix(Math.floor(data / 1e6))
              .format("Y-MM-DD [<br class='hidden-lg'>]HH:mm:ss z");
          }

          return data;
        },
      },
      { width: "8%" },
      { width: "25%", render: $.fn.dataTable.render.text() },
      { width: "15%", type: "ip-address", render: $.fn.dataTable.render.text() },
      { width: "20%", orderData: 4 },
      { width: "12%", orderData: 5 },
      { width: "10%", orderData: 4 },
    ],
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "全部"],
    ],
    stateSave: true,
    stateSaveCallback: function (settings, data) {
      utils.stateSaveCallback("query_log_table", data);
    },
    stateLoadCallback: function () {
      return utils.stateLoadCallback("query_log_table");
    },
    columnDefs: [
      {
        targets: -1,
        data: null,
        defaultContent: "",
      },
    ],
    initComplete: function () {
      var api = this.api();

      // Query type IPv4 / IPv6
      api
        .$("td:eq(1)")
        .click(function (event) {
          addColumnFilter(event, 1, this.textContent);
        })
        .hover(
          function () {
            $(this).addClass("pointer").attr("title", tooltipText(1, this.textContent));
          },
          function () {
            $(this).removeClass("pointer");
          }
        );

      // Domain
      api
        .$("td:eq(2)")
        .click(function (event) {
          addColumnFilter(event, 2, this.textContent.split("\n")[0]);
        })
        .hover(
          function () {
            $(this).addClass("pointer").attr("title", tooltipText(2, this.textContent));
          },
          function () {
            $(this).removeClass("pointer");
          }
        );

      // Client
      api
        .$("td:eq(3)")
        .click(function (event) {
          addColumnFilter(event, 3, this.textContent);
        })
        .hover(
          function () {
            $(this).addClass("pointer").attr("title", tooltipText(3, this.textContent));
          },
          function () {
            $(this).removeClass("pointer");
          }
        );

      // Status
      api
        .$("td:eq(4)")
        .click(function (event) {
          var id = this.children.id.value;
          var text = this.textContent;
          addColumnFilter(event, 4, id + "#" + text);
        })
        .hover(
          function () {
            $(this).addClass("pointer").attr("title", tooltipText(4, this.textContent));
          },
          function () {
            $(this).removeClass("pointer");
          }
        );

      // Reply type
      api
        .$("td:eq(5)")
        .click(function (event) {
          var id = this.children.id.value;
          var text = this.textContent.split(" ")[0];
          addColumnFilter(event, 5, id + "#" + text);
        })
        .hover(
          function () {
            $(this).addClass("pointer").attr("title", tooltipText(5, this.textContent));
          },
          function () {
            $(this).removeClass("pointer");
          }
        );

      // Disable autocorrect in the search box
      var input = $("input[type=search]");
      if (input !== null) {
        input.attr("autocomplete", "off");
        input.attr("autocorrect", "off");
        input.attr("autocapitalize", "off");
        input.attr("spellcheck", false);
        input.attr("placeholder", "类型/域名/客户端");
      }
    },
  });

  resetColumnsFilters();

  $("#all-queries tbody").on("click", "button", function () {
    var data = tableApi.row($(this).parents("tr")).data();
    if (data[4] === "2" || data[4] === "3") {
      utils.addFromQueryLog(data[2], "black");
    } else {
      utils.addFromQueryLog(data[2], "white");
    }
  });

  $("#resetButton").click(function () {
    tableApi.search("");
    resetColumnsFilters();
  });
});

function tooltipText(index, text) {
  if (index === 5) {
    // Strip reply time from tooltip text
    text = text.split(" ")[0];
  }

  if (index in tableFilters && tableFilters[index].length > 0) {
    return "点击从筛选器移除" + colTypes[index] + ' "' + text + '"。';
  }

  return "点击增加" + colTypes[index] + ' "' + text + '"到筛选器。';
}

function addColumnFilter(event, colID, filterstring) {
  // If the below modifier keys are held down, do nothing
  if (event.ctrlKey || event.metaKey || event.altKey) {
    return;
  }

  if (tableFilters[colID] === filterstring) {
    filterstring = "";
  }

  tableFilters[colID] = filterstring;

  applyColumnFiltering();
}

function resetColumnsFilters() {
  tableFilters.forEach(function (value, index) {
    tableFilters[index] = "";
  });

  // Clear filter reset button
  applyColumnFiltering();
}

function applyColumnFiltering() {
  var showReset = false;
  tableFilters.forEach(function (value, index) {
    // Prepare regex filter string
    var regex = "";

    // Split filter string if we received a combined ID#Name column
    var valArr = value.split("#");
    if (valArr.length > 0) {
      value = valArr[0];
    }

    if (value.length > 0) {
      // Exact matching
      regex = "^" + value + "$";

      // Add background color
      tableApi.$("td:eq(" + index + ")").addClass("highlight");

      // Remember to show reset button
      showReset = true;
    } else {
      // Clear background color
      tableApi.$("td:eq(" + index + ")").removeClass("highlight");
    }

    // Apply filtering on this column (regex may be empty -> no filtering)
    tableApi.column(index).search(regex, true, true);
  });

  if (showReset) {
    $("#resetButton").removeClass("hidden");
  } else {
    $("#resetButton").addClass("hidden");
  }

  // Trigger table update
  tableApi.draw();
}
