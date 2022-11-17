<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2021 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license.
*/

require_once 'auth.php';
require_once 'func.php';
require_once 'database.php';

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

if ($_POST['action'] == 'delete_network_entry' && isset($_POST['id'])) {
    // Delete netwwork and network_addresses table entry identified by ID
    try {
        $stmt = $db->prepare('DELETE FROM network_addresses WHERE network_id=:id');
        if (!$stmt) {
            throw new Exception('在准备消息语句时：'.$db->lastErrorMsg());
        }

        if (!$stmt->bindValue(':id', intval($_POST['id']), SQLITE3_INTEGER)) {
            throw new Exception('将id绑定到消息语句时：'.$db->lastErrorMsg());
        }

        if (!$stmt->execute()) {
            throw new Exception('执行消息语句时：'.$db->lastErrorMsg());
        }

        $stmt = $db->prepare('DELETE FROM network WHERE id=:id');
        if (!$stmt) {
            throw new Exception('在准备消息语句时：'.$db->lastErrorMsg());
        }

        if (!$stmt->bindValue(':id', intval($_POST['id']), SQLITE3_INTEGER)) {
            throw new Exception('将id绑定到消息语句时：'.$db->lastErrorMsg());
        }

        if (!$stmt->execute()) {
            throw new Exception('执行消息语句时：'.$db->lastErrorMsg());
        }

        $reload = true;
        JSON_success();
    } catch (\Exception $ex) {
        JSON_error($ex->getMessage());
    }
} else {
    log_and_die('不支持请求的操作！');
}
