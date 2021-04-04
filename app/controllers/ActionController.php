<?php

namespace Controller;

use Core\Controller;

class ActionController extends Controller
{
    public function drinkAction () {
        $drinkOnce = 200;
        $sql = 'INSERT INTO t_user_drink SET user_id = ?, drink_type = ?, create_date = ?, drink_quantity = ?';
        $this->db->exec($sql, $this->userId, 1, date('Y-m-d'), $drinkOnce);
        $sql = 'SELECT total_id, drink_total, drink_target, is_reach FROM t_user_drink_total WHERE user_id = ? AND create_date = ?';
        $totalInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d'));
        if ($totalInfo) {
            $sql = 'UPDATE t_user_drink_total SET drink_total = ?';
            if (!$totalInfo['is_reach'] && ($totalInfo['drink_total'] + $drinkOnce >= $totalInfo['drink_target'])) {
                $sql .= ', is_reach = 1';
            }
            $sql .= ' WHERE total_id = ?';
            $this->db->exec($sql, $totalInfo['drink_total'] + $drinkOnce, $totalInfo['total_id']);
        } else {
            $sql = 'SELECT drink_target FROM t_user WHERE user_id = ?';
            $drinkTarget = $this->db->getOne($sql, $this->userId);
            $sql = 'INSERT INTO t_user_drink_total SET user_id = ?, drink_total = ?, drink_target = ?, create_date = ?';
            $this->db->exec($sql, $this->userId, $drinkOnce, $drinkTarget, date('Y-m-d'));
        }
        return array();
    }
}