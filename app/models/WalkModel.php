<?php

namespace Model;
use Core\Model;

class WalkModel extends Model
{
    //领取奖励条件步数
    protected $rewardCounter = 50;
    //限制领取步数奖励间隔(分钟)
    protected $walkAwardLimitTime = 10;
    //限制领取步数奖励个数
    protected $walkAwardLimitCount = 5;

    /**
     * 更新用户总步数
     * @param $userId
     * @param $totalWalk
     */
    public function updateTotal ($userId, $totalWalk) {
        // 获取是否有今天的步数
        $sql = 'SELECT walk_id, total_walk FROM t_walk WHERE user_id = ? AND walk_date = ?';
        $walkInfo = $this->db->getRow($sql, $userId, date('Y-m-d'));
        if ($walkInfo) {
            // 有今天的步数，在更新的步数大于当前步数时则更新
            if ($walkInfo['total_walk'] < $totalWalk) {
                $sql = 'UPDATE t_walk SET total_walk = ? WHERE walk_id = ?';
                $this->db->exec($sql, $totalWalk, $walkInfo['walk_id']);
            }
        } else {
            // 没有今天的步数则插入记录
            $sql = 'INSERT INTO t_walk SET total_walk = ?, user_id = ?, walk_date = ?';
            $this->db->exec($sql, $totalWalk, $userId, date('Y-m-d'));
        }
    }


    // receiveInfo 获取最远 5次（可设置）领取时间 + 10分钟
    public function receiveInfo ($userId) {
        // 获取间隔时间内用户领取的金币奖励次数和最老领取时间
        $startTime = strtotime('-' . $this->walkAwardLimitTime . ' minutes');
        $sql = 'SELECT COUNT(gold_id) walkCount, MIN(create_time) minTime FROM t_gold WHERE user_id = ? AND gold_source = "walk" AND create_time >= ? ORDER BY gold_id DESC LIMIT ' . $this->walkAwardLimitCount;
        $walkInfo = $this->db->getRow($sql, $userId, date('Y-m-d H:i:s', $startTime));
        // 获取用户当前总步数
        $return = array();
        $sql = 'SELECT total_walk FROM t_walk WHERE user_id = ? AND walk_date = ?';
        // 返回 总步数
        $return['walkCount'] = (int) ($this->db->getOne($sql, $userId, date('Y-m-d')) ?: 0);
        // 获取用户当前领取的奖励的次数信息
        $sql = 'SELECT gold_count FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
        $receiveWalk = $this->db->getColumn($sql, $userId, date('Y-m-d'), 'walk');
        // 返回 剩余可以领取的次数
        $maxReceiveCount = floor($return['walkCount'] / $this->rewardCounter);
        $return['restCount'] = $maxReceiveCount - count($receiveWalk);
        // 判断已领取的次数是否超过设定的上限
        if ($this->walkAwardLimitCount <= $walkInfo['walkCount']) {
            $return['list'] = array();
            // 返回下次可以领取的时间
            $return['receiveTime'] = strtotime('+' . $this->walkAwardLimitTime . ' minutes', strtotime($walkInfo['minTime'])) * 1000;
        } else {
            // 返回 用户当前可以领取的金币信息
            $return['list'] = array();
            $listCountArr = array_slice(array_diff($maxReceiveCount ? range(1, min(count($receiveWalk) + $this->walkAwardLimitCount, $maxReceiveCount)) : array(), $receiveWalk), 0, $this->walkAwardLimitCount - $walkInfo['walkCount']);
            foreach ($listCountArr as $listCount) {
                $sql = 'SELECT award_min, award_max FROM t_award_config WHERE config_type = ? AND counter <= ? ORDER BY counter DESC LIMIT 1';
                $awardRange = $this->db->getRow($sql, 'walk', $listCount);
                $return['list'][] = array('count' => $listCount, 'num' => rand($awardRange['award_min'], $awardRange['award_max']), 'type' => 'walk');
            }
            $return['receiveTime'] = time() * 1000;
        }
        $return['serverTime'] = time() * 1000;
        return $return;
    }
}