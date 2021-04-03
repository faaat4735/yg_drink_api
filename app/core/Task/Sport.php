<?php

namespace Core\Task;

use Core\Task;

class Sport extends Task
{
    protected function _receiveAward($data) {

        $this->model->gold->insert(array('user_id' => $this->userId, 'gold_count' => $data['count'], 'gold_amount' => $data['num'], 'gold_source' => $data['type'], 'isDouble' => $data['isDouble'] ?? 0));
        $sql = 'UPDATE t_activity_sport SET is_receive = 1 WHERE user_id = ? AND counter = ? AND sport_date = ?';
        $this->db->exec($sql, $this->userId, $data['count'], date('Y-m-d'));

        return array();
    }
}