<?php

namespace Core;

use Core\Controller;

class Task extends Controller
{
    protected $userId;
    protected $className;
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
        $className = '\\Core\\Task\\' . ucfirst($type);
        if (class_exists($className)) {
            $this->className = new $className($this->userId);
        } else {
            $this->type = $type;
            $this->className = $this;
        }
        return $this->className->_getInfo();
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
        $className = '\\Core\\Task\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $data['type'])));
        if (class_exists($className)) {
            $this->className = new $className($this->userId);
            $this->className->type = $data['type'];
        } else {
            $this->type = $data['type'];
            $this->className = $this;
        }
        // 验证金额是否符合规范
        $verifyActivity = $this->className->_verify($data);
        if (TRUE !== $verifyActivity) {
            return $verifyActivity;
        }
        return $this->className->_receiveAward($data);

    }

    protected function _verify($data) {
        // 验证最大次数 可领取时间
        // 移动sql 到model中
        $sql = 'SELECT award_min, award_max FROM t_award_config WHERE config_type = ? AND counter <= ? ORDER BY counter DESC LIMIT 1';
        $awardRange = $this->db->getRow($sql, $this->type, $data['count']);
        if ($awardRange['award_min'] <= $data['num'] && $awardRange['award_max'] >= $data['num']) {
            return TRUE;
        }
        return 301;
    }

    protected function _getInfo() {
        switch ($this->type) {
            case 'drink':
                $return['type'] = 'interior';
                $return['url'] = 'home';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "drink");
                $return['name'] = '完成首次喝水打卡';
                $sql = 'SELECT COUNT(*) FROM t_user_drink WHERE user_id = ? AND create_date = ?';
                if ($this->db->getOne($sql, $this->userId, date('Y-m-d')) >= 1) {
                    $return['status'] = 1;
                    $sql = 'SELECT COUNT(*) FROM t_gold WHERE user_id = ? AND gold_source = ? AND change_date = ?';
                    if ($this->db->getOne($sql, $this->userId, "drink", date('Y-m-d'))){
                        $return['status'] = 2;
                    }
                } else {
                    $return['status'] = 0;
                }
                break;
            case 'drink_total':
                $return['type'] = 'interior';
                $return['url'] = 'home';
                $return['goldInfo'] = array('count' => 1, 'num' => 100,'type' => "drink_total");
                $return['name'] = '累计完成4次喝水';
                $sql = 'SELECT COUNT(*) FROM t_user_drink WHERE user_id = ? AND create_date = ?';
                if ($this->db->getOne($sql, $this->userId, date('Y-m-d')) >= 4) {
                    $return['status'] = 1;
                    $sql = 'SELECT COUNT(*) FROM t_gold WHERE user_id = ? AND gold_source = ? AND change_date = ?';
                    if ($this->db->getOne($sql, $this->userId, "drink_total", date('Y-m-d'))){
                        $return['status'] = 2;
                    }
                } else {
                    $return['status'] = 0;
                }
                break;
            case 'drink_target':
                $return['type'] = 'interior';
                $return['url'] = 'home';
                $return['goldInfo'] = array('count' => 1, 'num' => 500,'type' => "drink_target");
                $return['name'] = '完成今日喝水目标';
                $return['status'] = 0;
                $sql = 'SELECT IFNULL(SUM(drink_quantity), 0) FROM t_user_drink WHERE user_id = ? AND create_date = ?';
                if ($this->db->getOne($sql, $this->userId, date('Y-m-d')) >= 2400) {
                    $return['status'] = 1;
                    $sql = 'SELECT COUNT(*) FROM t_gold WHERE user_id = ? AND gold_source = ? AND change_date = ?';
                    if ($this->db->getOne($sql, $this->userId, "drink_target", date('Y-m-d'))){
                        $return['status'] = 2;
                    }
                } else {
                    $return['status'] = 0;
                }
                break;
            case 'video':
                $return['type'] = 'popup';
                $return['url'] = 'video';
                $return['name'] = '看创意视频';
                $sql = 'SELECT count(gold_id) count, MAX(create_time) maxTime FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $receiveInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d'), 'video');
                if ($receiveInfo['count'] >= 3) {
                    $return['status'] = 2;
                } else {
                    $return['goldInfo'] = array('count' => $receiveInfo['count'] + 1, 'num' => 50,'type' => "video");
                    $return['receiveTime'] = (($receiveInfo['maxTime'] ? strtotime($receiveInfo['maxTime']) + 4 * 60 : time())) * 1000;
                    $return['status'] = 0;
                }
                break;
            case 'wechat':
                $return['type'] = 'popup';
                $return['url'] = 'wechat';
                $return['goldInfo'] = array('count' => 1, 'num' => 200,'type' => "wechat");
                $return['name'] = '绑定微信号';
                $return['status'] = 0;
                $sql = 'SELECT COUNT(*) FROM t_gold WHERE user_id = ? AND gold_source = ? AND create_date = ?';
                if ($this->db->getOne($sql, $this->userId, "wechat", date('Y-m-d'))){
                    $return['status'] = 2;
                } else {
                    $sql = 'SELECT wechat_unionid FROM t_user WHERE user_id = ?';
                    if ($this->db->getOne($sql, $this->userId)){
                        $return['status'] = 1;
                    }
                }
                break;
            case 'sport_ywqz':
                $return['type'] = 'popup';
                $return['url'] = 'sport_ywqz';
                $return['name'] = '仰卧起坐';
                $sql = 'SELECT count(gold_id) count, MAX(create_time) maxTime FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $receiveInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d'), 'sport_ywqz');
                if ($receiveInfo['count'] >= 20) {
                    $return['status'] = 2;
                } else {
                    $return['goldInfo'] = array('count' => $receiveInfo['count'] + 1, 'num' => 10,'type' => "sport_ywqz");
                    $return['receiveTime'] = (($receiveInfo['maxTime'] ? strtotime($receiveInfo['maxTime']) + 1 * 60 : time())) * 1000;
                    $return['status'] = 0;
                }
                break;
            case 'sport_zyz':
                $return['type'] = 'popup';
                $return['url'] = 'sport_zyz';
                $return['name'] = '走一走';
                $sql = 'SELECT count(gold_id) count, MAX(create_time) maxTime FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $receiveInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d'), 'sport_zyz');
                if ($receiveInfo['count'] >= 10) {
                    $return['status'] = 2;
                } else {
                    $return['goldInfo'] = array('count' => $receiveInfo['count'] + 1, 'num' => 20,'type' => "sport_zyz");
                    $return['receiveTime'] = (($receiveInfo['maxTime'] ? strtotime($receiveInfo['maxTime']) + 2 * 60 : time())) * 1000;
                    $return['status'] = 0;
                }
                break;
            case 'sport_pyp':
                $return['type'] = 'popup';
                $return['url'] = 'sport_pyp';
                $return['name'] = '跑一跑';
                $sql = 'SELECT count(gold_id) count, MAX(create_time) maxTime FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $receiveInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d'), 'sport_pyp');
                if ($receiveInfo['count'] >= 8) {
                    $return['status'] = 2;
                } else {
                    $return['goldInfo'] = array('count' => $receiveInfo['count'] + 1, 'num' => 30,'type' => "sport_pyp");
                    $return['receiveTime'] = (($receiveInfo['maxTime'] ? strtotime($receiveInfo['maxTime']) + 3 * 60 : time())) * 1000;
                    $return['status'] = 0;
                }
                break;
            case 'sport_yy':
                $return['type'] = 'popup';
                $return['url'] = 'sport_yy';
                $return['name'] = '游泳';
                $sql = 'SELECT count(gold_id) count, MAX(create_time) maxTime FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $receiveInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d'), 'sport_yy');
                if ($receiveInfo['count'] >= 4) {
                    $return['status'] = 2;
                } else {
                    $return['goldInfo'] = array('count' => $receiveInfo['count'] + 1, 'num' => 40,'type' => "sport_yy");
                    $return['receiveTime'] = (($receiveInfo['maxTime'] ? strtotime($receiveInfo['maxTime']) + 4 * 60 : time())) * 1000;
                    $return['status'] = 0;
                }
                break;
            case 'sport_hwyd':
                $return['type'] = 'popup';
                $return['url'] = 'sport_hwyd';
                $sql = 'SELECT count(gold_id) count, MAX(create_time) maxTime FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $receiveInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d'), 'sport_hwyd');
                if ($receiveInfo['count'] >= 2) {
                    $return['status'] = 2;
                } else {
                    $return['goldInfo'] = array('count' => $receiveInfo['count'] + 1, 'num' => 50,'type' => "sport_hwyd");
                    $return['receiveTime'] = (($receiveInfo['maxTime'] ? strtotime($receiveInfo['maxTime']) + 5 * 60 : time())) * 1000;
                    $return['status'] = 0;
                }
                break;
        }
        $return['serverTime'] = time() * 1000;
        return $return;
    }

    protected function _receiveAward ($data) {
        $this->model->gold->insert(array('user_id' => $this->userId, 'gold_count' => $data['count'], 'gold_amount' => $data['num'], 'gold_source' => $data['type'], 'isDouble' => $data['isDouble'] ?? 0, 'isFive' => $data['isFive'] ?? 0));
        if (in_array($data['type'], array('drink', 'walk', 'walk_stage', 'newer'))) {
            return array();
        }
        return $this->_getInfo();
    }
}