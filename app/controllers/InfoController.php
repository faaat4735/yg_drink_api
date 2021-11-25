<?php

namespace Controller;

use Core\Controller;

class InfoController extends Controller
{
    /**
     * 首页信息
     * @return array
     */
    public function homeAction () {
        $sql = 'SELECT drink_target FROM t_user WHERE user_id = ?';
        $target = $this->db->getOne($sql, $this->userId);
        $sql = 'SELECT drink_quantity quantity, UNIX_TIMESTAMP(create_time) * 1000 time, drink_type_name drinkType FROM t_user_drink LEFT JOIN t_drink_type USING(drink_type) WHERE user_id = ? AND create_date = ? ORDER BY drink_id DESC';
        $list = $this->db->getAll($sql, $this->userId, date("Y-m-d"));
        return array('current' => array_sum(array_column($list, 'quantity')), 'target' => $target, 'list' => $list, 'gold' => array(array("count" => 1, "num" => 40, "type" => "walk"), array("count" => 3, "num" => 20, "type" => "walk"), array("count" => 4, "num" => 30, "type" => "walk")));
//        return array('current' => 1200, 'target' => 2400, 'list' => array(array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200)));
    }

    public function historyAction () {
        $sql = 'SELECT UNIX_TIMESTAMP(create_time) FROM t_user WHERE user_id = ?';
        $firstDayTimeStamp = $this->db->getOne($sql, $this->userId);
        $firstDay = date('Y-m-d', $firstDayTimeStamp);
        $take = floor((time()-$firstDayTimeStamp)/86400);
        $sql = 'SELECT IFNULL(SUM(drink_quantity), 0) total, COUNT(DISTINCT create_date) total_days, COUNT(drink_id) drinkCount FROM t_user_drink WHERE user_id = ?';
        $drinkInfo = $this->db->getRow($sql, $this->userId);
        $sql = 'SELECT COUNT(total_id) FROM t_user_drink_total WHERE user_id = ? AND is_reach = ?';
        $reachCount = $this->db->getOne($sql, $this->userId, 1);
        $sql = 'SELECT create_date, drink_total FROM t_user_drink_total WHERE user_id = ? AND create_date <= ? AND create_date >= ?';
        $listInfo = $this->db->getPairs($sql, $this->userId, date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-7 day')));
        $dateList = array(date('Y-m-d', strtotime('-7 day')), date('Y-m-d', strtotime('-6 day')), date('Y-m-d', strtotime('-5 day')), date('Y-m-d', strtotime('-4 day')), date('Y-m-d', strtotime('-3 day')), date('Y-m-d', strtotime('-2 day')), date('Y-m-d', strtotime('-1 day')));

        foreach ($dateList as $date) {
            if (isset($listInfo[$date])) {
                $list[] = array('date' => substr($date,5 ), 'quantity' => $listInfo[$date]);
            } else {
                $list[] = array('date' => substr($date,5 ), 'quantity' => 0);
            }
        }
        return array('total' => $drinkInfo['total'], 'perDay' => $drinkInfo['total_days'] ? floor($drinkInfo['total'] / $drinkInfo['total_days']) : 0, 'reachCount' => $reachCount, 'drinkCount' => $drinkInfo['drinkCount'], 'first' => $firstDay, 'take' => $take, 'list' => $list);
    }

    public function taskAction () {
        $taskClass = new \Core\Task($this->userId);
        return array('sign' => $this->_sign(), 'task' => array($taskClass->getInfo('video'), $taskClass->getInfo('drink'), $taskClass->getInfo('drink_total'), $taskClass->getInfo('drink_target'), $taskClass->getInfo('wechat'), $taskClass->getInfo('sport_ywqz'), $taskClass->getInfo('sport_zyz'), $taskClass->getInfo('sport_pyp'), $taskClass->getInfo('sport_yy'), $taskClass->getInfo('sport_hwyd')));
    }

    /**
     * 提现页面信息
     * @return array
     */
    public function withdrawAction () {
        $sql = 'SELECT wechat_unionid FROM t_user WHERE user_id = ?';
        $bindInfo = $this->db->getRow($sql, $this->userId);
        return array('isBindWechat' => ($bindInfo && $bindInfo['wechat_unionid']) ? 1 : 0);
//        $isLock = 0;
//        $withdrawList = array(array('amount' => 0.5, 'gold' => 5000), array('amount' => 50, 'gold' => 500000), array('amount' => 100, 'gold' => 1000000), array('amount' => 150, 'gold' => 1500000));
//        $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = ? AND (withdraw_status = "pending" OR withdraw_status = "success")';
//        if ($this->db->getOne($sql, $this->userId, 0.5)) {
//            $sql = 'SELECT COUNT(*) FROM t_liveness WHERE user_id = ? AND is_receive = 1 AND liveness_date = ?';
//            $livenessCount = $this->db->getOne($sql, $this->userId, date('Y-m-d'));
//            if ($livenessCount < 6) {
//                $isLock = 1;
//            }
//        }
//        return array('isBindWechat' => ($bindInfo && $bindInfo['wechat_unionid']) ? 1 : 0, 'withdrawList' => $withdrawList, 'isBindAlipay' => ($bindInfo && $bindInfo['alipay_account']) ? 1 : 0, 'isLock' => $isLock);
    }

    private function _sign() {
        $sql = 'SELECT IFNULL(COUNT(gold_id), 0) FROM t_gold WHERE user_id = ? AND gold_source = "sign"';
        $signTotal = $this->db->getOne($sql, $this->userId);
        $sql = 'SELECT COUNT(gold_id) FROM t_gold WHERE user_id = ? AND gold_source = "sign" AND change_date = ?';
        $isReceive = $this->db->getOne($sql, $this->userId, date("Y-m-d"));
        $today = ($signTotal % 7) + ($isReceive ? 0 : 1);
        return array('total' => $signTotal, 'tomorrow' => $this->signList[$today]['num'], "today" => $today, "isReceive" => $isReceive ? 1 : 0, 'list' =>  $this->signList);
    }
}