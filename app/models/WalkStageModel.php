<?php

namespace Model;
use Core\Model;

class WalkStageModel extends Model
{
    /**
     * @param $userId
     * @return array
     */
    public function receiveInfo ($userId) {

        $sql = 'SELECT counter count, config_type type, award_min num FROM t_award_config WHERE config_type = ? ORDER BY counter';
        $taskAward = $this->db->getAll($sql, 'walk_stage');

        $sql = 'SELECT total_walk FROM t_walk WHERE user_id = ? AND walk_date = ?';
        $todayWalk = $this->db->getOne($sql, $userId, date('Y-m-d')) ?: 0;

        $sql = 'SELECT gold_count FROM t_gold WHERE user_id = ? AND gold_source = ? AND change_date = ?';
        $walkAwardList = $this->db->getColumn($sql, $userId, 'walk_stage', date('Y-m-d'));
        foreach ($taskAward as &$taskInfo) {
            if ($todayWalk < $taskInfo['count']) {
                $taskInfo['receiveStatus'] = 0;
            } elseif (in_array($taskInfo['count'], $walkAwardList)) {
                $taskInfo['receiveStatus'] = 1;
            } else {
                $taskInfo['receiveStatus'] = 2;
            }
        }
        return $taskAward;
    }

}