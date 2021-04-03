<?php

namespace Core\Task;

use Core\Task;

class Box extends Task
{
    protected function _getInfo() {
        $activityInfo = $this->model->activity->info('box');
        $activityReceiveInfo = $this->model->gold->receiveGold($this->userId, 'box');
        $return = array();
        if ($activityInfo['activity_max'] > $activityReceiveInfo['count']) {
            $sql = 'SELECT award_min, award_max FROM t_award_config WHERE config_type = ? AND counter <= ? ORDER BY counter DESC LIMIT 1';
            $awardRange = $this->db->getRow($sql, 'box', $activityReceiveInfo['count'] + 1);
            $return = array('count' => $activityReceiveInfo['count'] + 1, 'num' => rand($awardRange['award_min'], $awardRange['award_max']),'type' => 'box', 'receiveTime' => (($activityReceiveInfo['maxTime'] ? strtotime($activityReceiveInfo['maxTime']) + $activityInfo['activity_duration'] * 60 : time())) * 1000);
        }
        $return['maxReceive'] = $activityInfo['activity_max'];
        $return['currentReceive'] = $activityReceiveInfo['count'];
        $return['serverTime'] = time() * 1000;
        return $return;
    }
}