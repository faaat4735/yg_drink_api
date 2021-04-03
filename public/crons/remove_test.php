<?php

// 发放邀请的奖励
// 每日1：00执行一次
require_once __DIR__ . '/../init.inc.php';

$db = \Core\Db::getDbInstance();

if (DEBUG_MODE) {
    $sql = 'DELETE FROM t_user WHERE device_id = "e132ec091abc3eb8a43e03cf60305723"';
    $db->exec($sql);
}

echo 'done';