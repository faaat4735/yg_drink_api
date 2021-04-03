<?php

namespace Core\Task;

use Core\Task;

class Sign extends Task
{
    protected function _getInfo() {


        $sql = 'SELECT counter count, config_type type, award_min num FROM t_award_config WHERE config_type = ? ORDER BY counter';
        $awardList = $this->db->getAll($sql, 'sign');

        $sql = 'SELECT COUNT(gold_id) FROM t_gold WHERE user_id = ? AND gold_source = ?';
        $totalSign = $this->db->getOne($sql, $this->userId, 'sign') ?: 0;
//        $this->model->gold->totalSign($this->userId);
        $sql = 'SELECT gold_id FROM t_gold WHERE user_id = ? AND gold_source = ? AND change_date = ?';
        $isTodaySign = $this->db->getOne($sql, $this->userId, 'sign', date('Y-m-d'));
        $currentDays = (($totalSign + ($isTodaySign ? 0 : 1)) % 7) ?: 7;

//        $sql = 'SELECT * FROM t_gold WHERE user_id = ?  AND gold_source = ? ORDER BY gold_id DESC LIMIT ' . ($currentDays - ($isTodaySign ? 0 : 1));
//        $receiveList = $this->db->getAll($sql, $this->userId, 'sign');

        $awardList[$currentDays-1] = array_merge($awardList[$currentDays-1], array('isReceive' => $isTodaySign ? 1 : 0));
        return array('totalSign' => $totalSign, 'currentDays' => $currentDays, 'signList' => $awardList);

    }
}