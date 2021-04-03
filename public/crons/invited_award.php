<?php

// 发放邀请的奖励
// 每日1：00执行一次
require_once __DIR__ . '/../init.inc.php';

$db = \Core\Db::getDbInstance();
$model = new \Core\Model();

$sql = 'SELECT award_min FROM t_award_config WHERE config_type = "do_invite_1"';
$invitedAward1 = $db->getOne($sql);
$sql = 'SELECT award_min FROM t_award_config WHERE config_type = "do_invite_2"';
$invitedAward2 = $db->getOne($sql);
// 发放前天邀请的奖励
$sql = 'SELECT id, user_id, invited_id FROM t_user_invited WHERE create_time >= ? AND create_time < ? AND invited_days = 0';
$invited1 = $db->getAll($sql, date('Y-m-d 00:00:00', strtotime('-2 day')), date('Y-m-d 00:00:00', strtotime('-1 day')));
foreach ($invited1 as $info1) {
    $sql = 'SELECT total_walk FROM t_walk WHERE user_id = ? AND walk_date = ?';
    $userTotalWalk = $db->getOne($sql, $info1['invited_id'], date('Y-m-d', strtotime('-1 day')));
    if ($userTotalWalk >= 500) {
        // 插入奖励10000
        $sql = 'SELECT COUNT(gold_id) FROM t_gold WHERE user_id = ? AND gold_source = "do_invite_1"';
        $userInvitedCount = $db->getOne($sql, $info1['user_id']);
        $model->gold->insert(array('user_id' => $info1['user_id'], 'gold_amount' => $invitedAward1, 'gold_source' => 'do_invite_1', 'gold_count' => $userInvitedCount + 1));
        // 更新邀请第一天成功
        $sql = 'UPDATE t_user_invited SET invited_days = 1 WHERE id = ?';
        $db->exec($sql, $info1['id']);
    }
}
// 发放3天前邀请的奖励
$sql = 'SELECT id, user_id, invited_id FROM t_user_invited WHERE create_time >= ? AND create_time < ? AND invited_days = 1';
$invited1 = $db->getAll($sql, date('Y-m-d 00:00:00', strtotime('-3 day')), date('Y-m-d 00:00:00', strtotime('-2 day')));
foreach ($invited1 as $info1) {
    $sql = 'SELECT total_walk FROM t_walk WHERE user_id = ? AND walk_date = ?';
    $userTotalWalk = $db->getOne($sql, $info1['invited_id'], date('Y-m-d', strtotime('-1 day')));
    if ($userTotalWalk >= 500) {
        // 插入奖励15000
        $sql = 'SELECT COUNT(gold_id) FROM t_gold WHERE user_id = ? AND gold_source = "do_invite_2"';
        $userInvitedCount = $db->getOne($sql, $info1['user_id']);
        $model->gold->insert(array('user_id' => $info1['user_id'], 'gold_amount' => $invitedAward2, 'gold_source' => 'do_invite_2', 'gold_count' => $userInvitedCount + 1));
        // 更新邀请第一天成功
        $sql = 'UPDATE t_user_invited SET invited_days = 2 WHERE id = ?';
        $db->exec($sql, $info1['id']);
    }
}

echo 'done';