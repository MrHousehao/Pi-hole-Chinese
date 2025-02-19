
<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2021 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

require_once('auth.php');

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

require_once('func.php');
require_once('database.php');
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

if ($_POST['action'] == 'delete_message' && isset($_POST['id'])) {
// Delete message identified by ID
    try {

        $stmt = $db->prepare('DELETE FROM message WHERE id=:id');
        if (!$stmt) {
            throw new Exception('在准备消息语句时：' . $db->lastErrorMsg());
        }

        if (!$stmt->bindValue(':id', intval($_POST['id']), SQLITE3_INTEGER)) {
            throw new Exception('将id绑定到消息语句时：' . $db->lastErrorMsg());
        }

        if (!$stmt->execute()) {
            throw new Exception('执行消息语句时：' . $db->lastErrorMsg());
        }


        $reload = true;
        JSON_success();
    } catch (\Exception $ex) {
        JSON_error($ex->getMessage());
    }
} else {
    log_and_die('请求的操作不受支持！');
}
