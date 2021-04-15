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


    /**
     * 用户反馈
     * @return array|int
     */
    public function feedbackAction () {
        if (!isset($this->inputData['content']) && $this->inputData['content']) {
            return 202;
        }
        //判断多次提交需要超过多久
        $sql = 'SELECT create_time FROM t_user_feedback WHERE user_id = ? ORDER BY feedback_id DESC';
        $lastUpload = $this->db->getOne($sql, $this->userId);
        if ($lastUpload && (time() - strtotime($lastUpload) < 600)) {
            return 314;
        }

        $sql = 'INSERT INTO t_user_feedback SET user_id = :user_id, content = :content, phone = :phone';
        $this->db->exec($sql, array(
            'user_id' => $this->userId,
            'content' => $this->inputData['content'],
            'phone' => $this->inputData['phone'] ?? 0
        ));
        return array();
    }
}