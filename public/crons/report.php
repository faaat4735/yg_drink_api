<?php

//统计昨日以前的相关数据
//每日1：00执行一次
require_once __DIR__ . '/../init.inc.php';

$db = \Core\Db::getDbInstance();

$model = new \Core\Model();

$variableName = 'report_daily';
$sql = 'SELECT variable_value FROM t_variable WHERE variable_name = ?';
$reportDaily = $db->getOne($sql, $variableName) ?: '2020-12-31';
$todayDate = date('Y-m-d 00:00:00');

while (true) {
    if (strtotime($todayDate) == strtotime($reportDaily . ' 00:00:00')) {
        break;
    }
    $start = $reportDaily . ' 00:00:00';
    $end = $reportDaily . ' 23:59:59';
    $sql = 'SELECT COUNT(*) count, SUM(withdraw_amount) sum FROM t_withdraw WHERE change_time >= ? AND change_time < ? AND withdraw_status = "success"';
    $withInfo = $db->getRow($sql, $start, $end);
    
    $sql = 'SELECT COUNT(*) FROM t_user WHERE create_time >= ? AND create_time < ?';
    $newUser = $db->getOne($sql, $start, $end);

    $sql = 'SELECT IFNULL(SUM(gold_amount), 0) FROM t_gold WHERE change_date = ? AND gold_amount > 0';
    $newGold = $db->getOne($sql, $reportDaily);
    
    $sql = 'SELECT COUNT(user_id) FROM t_user_first_login WHERE date = ?';
    $loginUser = $db->getOne($sql, $reportDaily);

    $sql = 'REPLACE INTO t_report SET withdraw_value = :withdraw_value, 
        withdraw_count = :withdraw_count, 
        new_user = :new_user, 
        new_gold = :new_gold, 
        login_user = :login_user,
        report_date = :report_date';
    $db->exec($sql, array('withdraw_value' => $withInfo['sum'] ?: 0,
        'withdraw_count' => $withInfo['count'],
        'new_user' => $newUser,
        'new_gold' => $newGold,
        'login_user' => $loginUser,
        'report_date' => $reportDaily
    ));
    $reportDaily = date('Y-m-d', strtotime('+1 day', strtotime($reportDaily)));
}
$sql = 'REPLACE INTO t_variable SET variable_name = ?, variable_value = ?';
$db->exec($sql, $variableName, $reportDaily);
echo 'done';