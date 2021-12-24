<?php

    require_once "func.php";

    require_once('auth.php');

    // Authentication checks
    if (isset($_POST['token'])) {
        check_cors();
        check_csrf($_POST['token']);
    } else {
        log_and_die('不允许（登录无效或已过期，请重新登录Pi-hole）！');
    }


    switch ($_POST['action'])
    {
        case 'get':     echo json_encode(echoCustomDNSEntries()); break;
        case 'add':     echo json_encode(addCustomDNSEntry());    break;
        case 'delete':  echo json_encode(deleteCustomDNSEntry()); break;
        default:
            die("错误操作");
    }


?>
