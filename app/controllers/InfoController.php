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
        return array('current' => array_sum(array_column($list, 'quantity')), 'target' => $target, 'list' => $list);
//        return array('current' => 1200, 'target' => 2400, 'list' => array(array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200)));
    }

    public function historyAction () {
        $sql = 'SELECT IFNULL(SUM(drink_quantity), 0) total, COUNT(DISTINCT create_date) total_days, COUNT(drink_id) drinkCount FROM t_user_drink WHERE user_id = ?';
        $drinkInfo = $this->db->getRow($sql, $this->userId);
        $sql = 'SELECT COUNT(total_id) FROM t_user_drink_total WHERE user_id = ? AND is_reach = ?';
        $reachCount = $this->db->getOne($sql, $this->userId, 1);
        $sql = 'SELECT create_date, drink_total FROM t_user_drink_total WHERE user_id = ? AND create_date <= ? AND create_date >= ?';
        $listInfo = $this->db->getPairs($sql, $this->userId, date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-7 day')));
        var_dump($listInfo);
        $dateList = array(date('Y-m-d', strtotime('-7 day')), date('Y-m-d', strtotime('-6 day')), date('Y-m-d', strtotime('-5 day')), date('Y-m-d', strtotime('-4 day')), date('Y-m-d', strtotime('-3 day')), date('Y-m-d', strtotime('-2 day')), date('Y-m-d', strtotime('-1 day')));
        var_dump($dateList);

        foreach ($dateList as $date) {
            if (isset($listInfo[$date])) {
                $list[] = array('date' => substr($date,5 ), 'quantity' => $listInfo[$date]);
            } else {
                $list[] = array('date' => substr($date,5 ), 'quantity' => 0);
            }
        }
        return array('total' => $drinkInfo['total'], 'perDay' => $drinkInfo['total_days'] ? floor($drinkInfo['total'] / $drinkInfo['total_days']) : 0, 'reachCount' => $reachCount, 'drinkCount' => $drinkInfo['drinkCount'], 'list' => $list);
    }
}