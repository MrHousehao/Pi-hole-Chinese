/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license. */

/* global moment:false, utils:false */

var start__ = moment().subtract(6, "days");
var from = moment(start__).utc().valueOf() / 1000;
var end__ = moment();
var until = moment(end__).utc().valueOf() / 1000;
var instantquery = false;
var daterange;

var timeoutWarning = $("#timeoutWarning");

var dateformat = "YYYY年MM月DD日HH:mm";

// Do we want to filter queries?
var GETDict = {};
window.location.search
  .substr(1)
  .split("&")
  .forEach(function (item) {
    GETDict[item.split("=")[0]] = item.split("=")[1];
  });

if ("from" in GETDict && "until" in GETDict) {
  from = parseInt(GETDict.from, 10);
  until = parseInt(GETDict.until, 10);
  start__ = moment(1000 * from);
  end__ = moment(1000 * until);
  instantquery = true;
}

$(function () {
  daterange = $("#querytime").daterangepicker(
    {
      timePicker: true,
      timePickerIncrement: 15,
      timePicker24Hour: true,
      locale: { format: dateformat },
      startDate: start__,
      endDate: end__,
      ranges: {
        今天: [moment().startOf("day"), moment()],
        昨天: [
          moment().subtract(1, "days").startOf("day"),
          moment().subtract(1, "days").endOf("day"),
        ],
        "最近7天": [moment().subtract(6, "days"), moment()],
        "最近30天": [moment().subtract(29, "days"), moment()],
        "本月": [moment().startOf("month"), moment()],
        "上月": [
          moment().subtract(1, "month").startOf("month"),
          moment().subtract(1, "month").endOf("month"),
        ],
        "今年": [moment().startOf("year"), moment()],
        "全部": [moment(0), moment()],
      },
      opens: "center",
      showDropdowns: true,
      autoUpdateInput: false,
    },
    function (startt, endt) {
      from = moment(startt).utc().valueOf() / 1000;
      until = moment(endt).utc().valueOf() / 1000;
    }
  );
});

var tableApi, statistics;

function handleAjaxError(xhr, textStatus) {
  if (textStatus === "timeout") {
    alert("服务器发送数据时间过长。");
  } else if (xhr.responseText.indexOf("Connection refused") !== -1) {
    alert("加载数据时发生错误：连接被拒绝。请确认FTL是否已运行。");
  } else {
    alert(
      "FTL\n" +
        xhr.responseText +
        "\n有关详细信息，请检查服务器的日志文件（/var/log/lighttpd/error.log，当您使用默认的Pi-hole web服务器时）。您可能需要增加Pi-hole的可用内存，以防请求大量数据。"
    );
  }

  $("#all-queries_processing").hide();
  tableApi.clear();
  tableApi.draw();
}

function getQueryTypes() {
  var queryType = [];
  if ($("#type_gravity").prop("checked")) {
    queryType.push(1);
  }

  if ($("#type_forwarded").prop("checked")) {
    queryType.push([2, 14]);
  }

  if ($("#type_cached").prop("checked")) {
    queryType.push(3);
  }

  if ($("#type_regex").prop("checked")) {
    queryType.push(4);
  }

  if ($("#type_blacklist").prop("checked")) {
    queryType.push(5);
  }

  if ($("#type_external").prop("checked")) {
    // Multiple IDs correspond to this status
    // We request queries with all of them
    queryType.push([6, 7, 8]);
  }

  if ($("#type_gravity_CNAME").prop("checked")) {
    queryType.push(9);
  }

  if ($("#type_regex_CNAME").prop("checked")) {
    queryType.push(10);
  }

  if ($("#type_blacklist_CNAME").prop("checked")) {
    queryType.push(11);
  }

  if ($("#type_retried").prop("checked")) {
    // Multiple IDs correspond to this status
    // We request queries with all of them
    queryType.push([12, 13]);
  }

  // 14 is defined above

  if ($("#type_dbbusy").prop("checked")) {
    queryType.push(15);
  }

  return queryType.join(",");
}

var reloadCallback = function () {
  timeoutWarning.hide();
  statistics = [0, 0, 0, 0];
  var data = tableApi.rows().data();
  for (var i = 0; i < data.length; i++) {
    statistics[0]++; // TOTAL query
    if (data[i][4] === 1 || (data[i][4] > 4 && ![10, 12, 13, 14].includes(data[i][4]))) {
      statistics[2]++; // EXACT blocked
    } else if (data[i][4] === 3) {
      statistics[1]++; // CACHE query
    } else if (data[i][4] === 4 || data[i][4] === 10) {
      statistics[3]++; // REGEX blocked
    }
  }

  $("h3#dns_queries").text(statistics[0].toLocaleString());
  $("h3#ads_blocked_exact").text(statistics[2].toLocaleString());
  $("h3#ads_wildcard_blocked").text(statistics[3].toLocaleString());

  var percent = 0;
  if (statistics[2] + statistics[3] > 0) {
    percent = (100 * (statistics[2] + statistics[3])) / statistics[0];
  }

  $("h3#ads_percentage_today").text(parseFloat(percent).toFixed(1).toLocaleString() + " %");
};

function refreshTableData() {
  timeoutWarning.show();
  var APIstring = "api_db.php?getAllQueries&from=" + from + "&until=" + until;
  // Check if query type filtering is enabled
  var queryType = getQueryTypes();
  if (queryType !== "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15") {
    APIstring += "&types=" + queryType;
  }

  statistics = [0, 0, 0];
  tableApi.ajax.url(APIstring).load(reloadCallback);
}

$(function () {
  var APIstring = instantquery
    ? "api_db.php?getAllQueries&from=" + from + "&until=" + until
    : "api_db.php?getAllQueries=empty";

  // Check if query type filtering is enabled
  var queryType = getQueryTypes();
  if (queryType !== 63) {
    // 63 (0b00111111) = all possible query types are selected
    APIstring += "&types=" + queryType;
  }

  tableApi = $("#all-queries").DataTable({
    rowCallback: function (row, data) {
      var fieldtext,
        buttontext = "",
        blocked = true;
      switch (data[4]) {
        case 1:
          fieldtext = "<span class='text-red'>吞噬（引力场）</span>";
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          blocked = true;
          break;
        case 2:
          fieldtext =
            "<span class='text-green'>OK</span>（转发至 <br class='hidden-lg'>" +
            (data.length > 5 && data[5] !== "N/A" ? data[5] : "") +
            ")";
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-red"><i class="fa fa-ban"></i> 添加到黑名单</button>';
          break;
        case 3:
          fieldtext = "<span class='text-green'>OK</span> <br class='hidden-lg'>（缓存）";
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-red"><i class="fa fa-ban"></i> 添加到黑名单</button>';
          break;
        case 4:
          fieldtext = "<span class='text-red'>吞噬<br class='hidden-lg'>（正则表达式黑名单）";
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          blocked = true;
          break;
        case 5:
          fieldtext = "<span class='text-red'>吞噬<br class='hidden-lg'>（确切黑名单）";
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          blocked = true;
          break;
        case 6:
          fieldtext = "<span class='text-red'>吞噬<br class='hidden-lg'>（外部，IP）";
          blocked = true;
          break;
        case 7:
          fieldtext =
            "<span class='text-red'>吞噬<br class='hidden-lg'>（外部，NULL）</span>";
          blocked = true;
          break;
        case 8:
          fieldtext =
            "<span class='text-red'>吞噬<br class='hidden-lg'>（外部，NXRA）</span>";
          blocked = true;
          break;
        case 9:
          fieldtext = "<span class='text-red'>吞噬（引力场，CNAME）</span>";
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          blocked = true;
          break;
        case 10:
          fieldtext =
            "<span class='text-red'>吞噬<br class='hidden-lg'>（正则表达式黑名单，CNAME）</span>";
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          blocked = true;
          break;
        case 11:
          fieldtext =
            "<span class='text-red'>吞噬<br class='hidden-lg'>（确切黑名单，CNAME）</span>";
          blocked = true;
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-green"><i class="fas fa-check"></i> 添加到白名单</button>';
          blocked = true;
          break;
        case 12:
          fieldtext = "<span class='text-green'>重试</span>";
          break;
        case 13:
          fieldtext = "<span class='text-green'>重试</span> <br class='hidden-lg'>（忽略）";
          break;
        case 14:
          fieldtext =
            "<span class='text-green'>OK</span> <br class='hidden-lg'>（已转发）";
          buttontext =
            '<button type="button" class="btn btn-default btn-sm text-red"><i class="fa fa-ban"></i> 添加到黑名单</button>';
          break;
        case 15:
          fieldtext =
            "<span class='text-orange'>吞噬<br class='hidden-lg'>（数据库正忙）</span>";
          blocked = true;
          break;
        default:
          fieldtext = "未知";
      }

      $(row).addClass(blocked === true ? "blocked-row" : "allowed-row");
      if (localStorage.getItem("colorfulQueryLog_chkbox") === "true") {
        $(row).addClass(blocked === true ? "text-red" : "text-green");
      }

      $("td:eq(4)", row).html(fieldtext);
      $("td:eq(5)", row).html(buttontext);

      // Substitute domain by "." if empty
      var domain = data[2];
      if (domain.length === 0) {
        domain = ".";
      }

      $("td:eq(2)", row).text(domain);
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
          return x;
        });
      },
    },
    autoWidth: false,
    processing: true,
    deferRender: true,
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
      { width: "10%" },
      { width: "25%", render: $.fn.dataTable.render.text() },
      { width: "15%", type: "ip-address", render: $.fn.dataTable.render.text() },
      { width: "20%" },
      { width: "10%" },
    ],
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "全部"],
    ],
    columnDefs: [
      {
        targets: -1,
        data: null,
        defaultContent: "",
      },
    ],
    initComplete: reloadCallback,
  });
  $("#all-queries tbody").on("click", "button", function () {
    var data = tableApi.row($(this).parents("tr")).data();
    if ([1, 4, 5, 9, 10, 11].indexOf(data[4]) !== -1) {
      utils.addFromQueryLog(data[2], "white");
    } else {
      utils.addFromQueryLog(data[2], "black");
    }
  });

  if (instantquery) {
    daterange.val(start__.format(dateformat) + " - " + end__.format(dateformat));
  }
});

$("#querytime").on("apply.daterangepicker", function (ev, picker) {
  $(this).val(picker.startDate.format(dateformat) + " 到 " + picker.endDate.format(dateformat));
  refreshTableData();
});
