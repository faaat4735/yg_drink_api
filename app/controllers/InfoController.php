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
        $target = $this->db->getOne($sql, $this->user_id);
        $sql = 'SELECT drink_quantity quantity, UNIX_TIMESTAMP(create_time) * 1000 time, drink_type_name drinkType FROM t_user_drink LEFT JOIN t_drink_type USING(drink_type) WHERE user_id = ? AND create_date = ?';
        $list = $this->db->getAll($sql, $this->userId, date("Y-m-d"));
        return array('current' => array_sum(array_column($list, 'quantity')), 'target' => $target, 'list' => $list);
//        return array('current' => 1200, 'target' => 2400, 'list' => array(array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200)));
    }
}