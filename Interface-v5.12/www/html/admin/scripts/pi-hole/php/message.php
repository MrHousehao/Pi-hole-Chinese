<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2021 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

require_once('auth.php');
require_once('func.php');
require_once('database.php');

// Authentication checks
if (!isset($api)) {
    if (isset($_POST['token'])) {
        check_cors();
        check_csrf($_POST['token']);
    } else {
        log_and_die('不允许（登录无效或已过期，请重新登录Pi-hole）！');
    }
}

$reload = false;

$QueriesDB = getQueriesDBFilename();
$db = SQLite3_connect($QueriesDB, SQLITE3_OPEN_READWRITE);

function JSON_success($message = null)
{
    header('Content-type: application/json');
    echo json_encode(array('success' => true, 'message' => $message));
}

function JSON_error($message = null)
{
    header('Content-type: application/json');
    $response = array('success' => false, 'message' => $message);
    if (isset($_POST['action'])) {
        array_push($response, array('action' => $_POST['action']));
    }
    echo json_encode($response);
}

// Delete message identified by IDs
if ($_POST['action'] == 'delete_message' && isset($_POST['id'])) {
    try {
        $ids = json_decode($_POST['id']);
        if(!is_array($ids))
            throw new Exception('Invalid payload: id is not an array');
        // Explot prevention: Ensure all entries in the ID array are integers
        foreach($ids as $value) {
            if (!is_numeric($value))
                throw new Exception('负载无效：id包含非数字');
        }
        $stmt = $db->prepare('DELETE FROM message WHERE id IN ('.implode(",",$ids).')');
        if (!$stmt)
            throw new Exception('在准备消息语句时：' . $db->lastErrorMsg());

        if (!$stmt->execute())
            throw new Exception('执行消息语句时：' . $db->lastErrorMsg());

        $reload = true;
        JSON_success();
    } catch (\Exception $ex) {
        JSON_error($ex->getMessage());
    }
} else {
    log_and_die('不支持请求的操作！');
}
