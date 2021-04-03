<?php

namespace Core\Task;

use Core\Task;

class Drink extends Task
{
    /**
     *
     * @return array
     */
    protected function _getInfo() {
        $sql = 'SELECT counter count, config_type type, award_min num, 0 receiveStatus FROM t_award_config WHERE config_type = ? ORDER BY counter';
        $taskAward = $this->db->getAll($sql, 'drink');

        $sql = 'SELECT gold_count FROM t_gold WHERE user_id = ? AND gold_source = ? AND change_date = ?';
        $drinkList = $this->db->getColumn($sql, $this->userId, 'drink', date('Y-m-d'));

        $nowHours = date('H');

        foreach ($taskAward as &$taskInfo) {
            if ($nowHours < $taskInfo['count']) {
                if (isset($temp) && ($temp['receiveStatus'] == 1)) {
                    $temp['receiveStatus'] = 3;
                }
                break;
            } else {
                if (in_array($taskInfo['count'], $drinkList)) {
                    $taskInfo['receiveStatus'] = 2;
                } else {
                    $taskInfo['receiveStatus'] = 1;
                }
                $temp = &$taskInfo;
            }
        }
        return $taskAward;
//        return $this->model->walkStage->receiveInfo($this->userId);
    }
}