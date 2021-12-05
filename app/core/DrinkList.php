<?php


namespace Core;

use Core\Controller;

class DrinkList extends Controller
{
    private $userId;
    private $awardLimitTime = 5;
    private $awardLimitCount = 5;
    private $awardRange = array('award_min' => 10, 'award_max' => 50);

    public function __construct($userId)
    {
        $this->userId =  $userId;
    }

    /**
     * @return mixed
     */
    public function getReceiveTime () {
        $sql = "SELECT IFNULL(COUNT(gold_id), 0) walkCount, MIN(create_time) minTime FROM t_gold WHERE user_id = ? AND change_date = ? AND crrate_time >= ?";
        $awardInfo = $this->db->getRow($sql, $this->userId, date("Y-m-d"), date("Y-m-d H:i:s", strtotime('-' . $this->awardLimitTime . ' minutes')));
        if ($awardInfo['walkCount'] >= $this->awardLimitCount) {
            $return['list'] = array();
            $return['receiveTime'] = strtotime('+' . $this->awardLimitTime . ' minutes', strtotime($awardInfo['minTime'])) * 1000;
        } else {
            $sql = "SELECT IFNULL(COUNT(gold_id), 0) FROM t_gold WHERE user_id = ? AND change_date = ?";
            $alreadyReveive = $this->db->getOne($sql, $this->userId, date("Y-m-d"));
            $sql = "SELECT IFNULL(COUNT(drink_id), 0) FROM t_user_drink WHERE user_id = ? AND change_date = ?";
            $maxReceive = $this->db->getOne($sql, $this->userId, date("Y-m-d"));
            $receiveCount = min($this->awardLimitCount-$awardInfo['walkCount'], $maxReceive - $alreadyReveive);
            while ($receiveCount) {
                $return['list'][] = array('num' => rand($this->awardRange['award_min'], $this->awardRange['award_max']), 'type' => 'drink');;
                $receiveCount--;
            }
            $return['receiveTime'] = time() * 1000;
        }
        $return['serverTime'] = time() * 1000;
        return $return;
    }
}