<?php

namespace Core;

use Core\Controller;

class Task extends Controller
{
    protected $userId;
    protected $type;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * 获取任务信息
     * @param $type
     * @return array
     */
    public function getInfo ($type) {
        $this->type = $type;
        return $this->_getInfo();
    }

    /**
     * 领取奖励
     */
    public function receiveAward ($data) {
        // 验证是否领取过
        $verifyGold = $this->model->gold->verify($this->userId, $data);
        if (TRUE !== $verifyGold) {
            return $verifyGold;
        }
        // 领取
        $this->type = $data['type'];
        // 验证金额是否符合规范
        $verifyActivity = $this->_verify($data);
        if (TRUE !== $verifyActivity) {
            return $verifyActivity;
        }
        return $this->_receiveAward($data);

    }

    protected function _verify($data) {
        // 验证最大次数 可领取时间
        // 移动sql 到model中
        $sql = 'SELECT activity_award FROM t_activity WHERE activity_type = ?';
        $award = $this->db->getOne($sql, $this->type);
        if ($award == $data['num']) {
            return TRUE;
        }
        return 301;
    }

    protected function _getInfo() {
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $taskInfo = $this->db->getRow($sql, $this->type);
        switch ($this->type) {
            case 'drink':
            case 'drink_total':
            case 'drink_target':
                $return['type'] = 'interior';
                $return['url'] = 'home';
                $return['goldInfo'] = array('count' => 1, 'num' => $taskInfo['activity_award'],'type' => $this->type);
                $return['name'] = $taskInfo['activity_name'];
                $isTrue = FALSE;
                switch ($this->type) {
                    case 'drink':
                        $sql = 'SELECT COUNT(*) FROM t_user_drink WHERE user_id = ? AND create_date = ?';
                        $isTrue = $this->db->getOne($sql, $this->userId, date('Y-m-d')) >= 1;
                        break;
                    case 'drink_total':
                        $sql = 'SELECT COUNT(*) FROM t_user_drink WHERE user_id = ? AND create_date = ?';
                        $isTrue = $this->db->getOne($sql, $this->userId, date('Y-m-d')) >= 4;
                        break;
                    case 'drink_target':
                        $sql = 'SELECT IFNULL(SUM(drink_quantity), 0) FROM t_user_drink WHERE user_id = ? AND create_date = ?';
                        $isTrue = $this->db->getOne($sql, $this->userId, date('Y-m-d')) >= 2400;
                        break;
                }
                if ($isTrue) {
                    $return['status'] = 1;
                    $sql = 'SELECT COUNT(*) FROM t_gold WHERE user_id = ? AND gold_source = ? AND change_date = ?';
                    if ($this->db->getOne($sql, $this->userId, $this->type, date('Y-m-d'))){
                        $return['status'] = 2;
                    }
                } else {
                    $return['status'] = 0;
                }
                break;
            case 'wechat':
                $return['type'] = 'interior';
                $return['url'] = 'wechat';
                $return['goldInfo'] = array('count' => 1, 'num' => 200,'type' => "wechat");
                $return['name'] = '绑定微信号';
                $return['status'] = 0;
                $sql = 'SELECT COUNT(*) FROM t_gold WHERE user_id = ? AND gold_source = ? AND change_date = ?';
                if ($this->db->getOne($sql, $this->userId, "wechat", date('Y-m-d'))){
                    $return['status'] = 2;
                } else {
                    $sql = 'SELECT wechat_unionid FROM t_user WHERE user_id = ?';
                    if ($this->db->getOne($sql, $this->userId)){
                        $return['status'] = 1;
                    }
                }
                break;
            default:
                $return['type'] = 'popup';
                $return['url'] = $this->type;
                $return['name'] = $taskInfo['activity_name'];
                $sql = 'SELECT count(gold_id) count, MAX(create_time) maxTime FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $receiveInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d'), $this->type);
                if ($receiveInfo['count'] >= $taskInfo['activity_max']) {
                    $return['goldInfo'] = array('count' => 0, 'num' => $taskInfo['activity_award'],'type' => "");
                    $return['status'] = 2;
                } else {
                    $return['goldInfo'] = array('count' => $receiveInfo['count'] + 1, 'num' => $taskInfo['activity_award'],'type' => $this->type);
                    $return['receiveTime'] = (($receiveInfo['maxTime'] ? strtotime($receiveInfo['maxTime']) + $taskInfo['activity_duration'] * 60 : time())) * 1000;
                    $return['status'] = 0;
                }
        }
        $return['serverTime'] = time() * 1000;
        return $return;
    }

    protected function _receiveAward ($data) {
        $this->model->gold->insert(array('user_id' => $this->userId, 'gold_count' => $data['count'], 'gold_amount' => $data['num'], 'gold_source' => $data['type'], 'isDouble' => $data['isDouble'] ?? 0));
        return $this->_getInfo();
    }
}
