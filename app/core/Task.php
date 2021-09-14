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
                $return['status'] = 0;
                break;
            case 'drink_total':
                $return['type'] = 'interior';
                $return['url'] = 'home';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "drink_total");
                $return['name'] = '累计完成4次喝水(0/4)';
                $return['status'] = 0;
                break;
            case 'drink_target':
                $return['type'] = 'interior';
                $return['url'] = 'home';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "drink_target");
                $return['name'] = '完成今日喝水目标';
                $return['status'] = 0;
                break;
            case 'video':
                $return['type'] = 'popup';
                $return['url'] = 'video';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "video");
                $return['name'] = '看创意视频(0/3)';
                $return['receiveTime'] = time() * 1000;
                $return['status'] = 0;
                break;
            case 'wechat':
                $return['type'] = 'popup';
                $return['url'] = 'wechat';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "wechat");
                $return['name'] = '绑定微信号';
                $return['status'] = 0;
                break;
            case 'sport_ywqz':
                $return['type'] = 'popup';
                $return['url'] = 'sport_ywqz';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "sport_ywqz");
                $return['name'] = '仰卧起坐(0/20)';
                $return['receiveTime'] = time() * 1000;
                $return['status'] = 0;
                break;
            case 'sport_zyz':
                $return['type'] = 'popup';
                $return['url'] = 'sport_zyz';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "sport_zyz");
                $return['name'] = '走一走(0/10)';
                $return['receiveTime'] = time() * 1000;
                $return['status'] = 0;
                break;
            case 'sport_pyp':
                $return['type'] = 'popup';
                $return['url'] = 'sport_pyp';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "sport_pyp");
                $return['name'] = '跑一跑(0/8)';
                $return['receiveTime'] = time() * 1000;
                $return['status'] = 0;
                break;
            case 'sport_yy':
                $return['type'] = 'popup';
                $return['url'] = 'sport_yy';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "sport_yy");
                $return['name'] = '游泳(0/4)';
                $return['receiveTime'] = time() * 1000;
                $return['status'] = 0;
                break;
            case 'sport_hwyd':
                $return['type'] = 'popup';
                $return['url'] = 'sport_hwyd';
                $return['goldInfo'] = array('count' => 1, 'num' => 20,'type' => "sport_hwyd");
                $return['name'] = '户外运动(0/2)';
                $return['receiveTime'] = time() * 1000;
                $return['status'] = 0;
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