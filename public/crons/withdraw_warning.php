<?php

require_once __DIR__ . '/../init.inc.php';

$endHour = date('H');

if ($endHour > 0 && $endHour < 8) {
    exit;
}

//$startHour = date('H:00', strtotime('-' . $last . ' hours'));
//$endHour = date('H:00');
//$startTime = date('Y-m-d H:00:00', strtotime('-' . $last . ' hour'));
//$endTime = date('Y-m-d H:00:00');

$html = '<html lang="en">
<head>
<style>
    table,table tr th, table tr td { border:1px solid #000000; }
    table { width: 200px; min-height: 25px; line-height: 25px; text-align: center; border-collapse: collapse;}
</style>
</head>
<body>';
$str = '<table>
    <tr><th>待处理提现笔数</th><th>待处理提现笔数金额</th></tr>
    <tr><td>%d</td><td>%f</td></tr>
</table>';
$db = \Core\Db::getDbInstance();

$sql = 'SELECT COUNT(withdraw_id) FROM t_withdraw WHERE withdraw_status = "success" AND change_time > ? AND withdraw_amount = 5';
$total5 = $db->getOne($sql, date('Y-m-d 00:00:00'));
if ($total5) {
    $sql = 'SELECT COUNT(withdraw_id) count, SUM(withdraw_amount) sum FROM t_withdraw WHERE withdraw_status = "pending" AND withdraw_amount != 5';
} else {
    $sql = 'SELECT COUNT(withdraw_id) count, SUM(withdraw_amount) sum FROM t_withdraw WHERE withdraw_status = "pending"';
}

$total = $db->getRow($sql);
if (!$total['count']) {
    exit;
}

$html .= sprintf($str, $total['count'] ?: 0, $total['sum'] ?: 0);

$html .= '</body>
</html>';
$mail = new \Core\Mail();
$mail->send(array('zjf580@163.com'), (ENV_PRODUCTION ? '' : '测试-') . '未处理提现定时提现邮件', $html);
echo 'done' . PHP_EOL;

