<?php

namespace Core;

class Controller
{
    protected $inputData;
    protected $userId;
    protected $page;
    protected $limitStart = 0;
    protected $limitCount = 10;
    protected $withdrawList = array(0.5, 50, 80, 100, 150);

    public function __construct()
    {
        $this->inputData = json_decode(file_get_contents("php://input"), TRUE);
        if (isset($_POST['pageSize'])) {
            $this->limitCount = $_POST['pageSize'];
            if (isset($_POST['pageNo'])) {
                $this->limitStart = ($_POST['pageNo'] - 1) * $_POST['pageSize'];
            }
        }
        $this->page = $this->limitStart . ', ' . $this->limitCount;
    }

    public function init () {
        $userId = $this->checkHeader();
        if (!$userId) {
            return 203;
        }
        return TRUE;
    }

    public function __get ($name) {
        if (!isset($this->$name)) {
            switch ($name) {
                case 'db':
                    $temp = Db::getDbInstance();
                    break;
                case 'model':
                    $temp = new Model();
                    break;
                default:
                    //todo 报错
            }
            $this->$name = $temp;
        }
        return $this->$name;
    }

    /**
     * 检查头部
     * @param bool $checkToken
     * @return bool
     */
    public function checkHeader($checkToken = TRUE) {
        // HTTP_VERSION_CODE
        // HTTP_SOURCE
        // HTTP_TIME
        // HTTP_SECRET
        // 加密 md5(时间戳 + 'ygsecert1007') 前8位
        if (!isset($_SERVER['HTTP_VERSION_CODE']) || !isset($_SERVER['HTTP_SOURCE']) || !isset($_SERVER['HTTP_TIME']) || !isset($_SERVER['HTTP_SECRET'])) {
            return FALSE;
        }
        // 验证时间戳在当前时间的误差范围内 todo
        if ($_SERVER['HTTP_SECRET'] !== substr(md5($_SERVER['HTTP_TIME'] . 'ygsecert1007'), 0,8)) {
            return FALSE;
        }
        if ($checkToken) {
            if (!isset($_SERVER['HTTP_ACCESS_TOKEN'])) {
                return FALSE;
            }
            // 检查token合法
            $sql = 'SELECT user_id FROM t_user WHERE access_token = ?';
            $this->userId = $this->db->getOne($sql, $_SERVER['HTTP_ACCESS_TOKEN']);
        }
        return TRUE;
    }

    /**
     * 是否能领取活跃度
     * @param $counter
     * @return bool
     */
    public function __liveness($counter) {
        switch ($counter) {
            // 签到1次
            case 1:
                $sql = 'SELECT gold_id FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                if ($this->db->getOne($sql, $this->userId, date('Y-m-d'), 'sign')) {
                    return TRUE;
                }
                break;
            // 大转盘3次
            case 2:
                $sql = 'SELECT COUNT(gold_id) FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $taskCount = $this->db->getOne($sql, $this->userId, date('Y-m-d'), 'lottery');
                if ($this->__withdrawCount() >= 4) {
                    if ($taskCount >= 10) {
                        return TRUE;
                    }
                } elseif ($taskCount >= 3) {
                    return TRUE;
                }
                break;
            // 喝水4次
            case 3:
                $sql = 'SELECT COUNT(gold_id) FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $taskCount = $this->db->getOne($sql, $this->userId, date('Y-m-d'), 'drink');
                if ($this->__withdrawCount() >= 4) {
                    if ($taskCount >= 6) {
                        return TRUE;
                    }
                } elseif ($taskCount >= 4) {
                    return TRUE;
                }
                break;
            // 领取15次步数奖励
            case 4:
                $sql = 'SELECT COUNT(gold_id) FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $taskCount = $this->db->getOne($sql, $this->userId, date('Y-m-d'), 'walk');
                if ($this->__withdrawCount() >= 4) {
                    if ($taskCount >= 25) {
                        return TRUE;
                    }
                } elseif ($taskCount >= 15) {
                    return TRUE;
                }
                break;
            // 运动赚3次
            case 5:
                $sql = 'SELECT COUNT(gold_id) FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ?';
                $taskCount = $this->db->getOne($sql, $this->userId, date('Y-m-d'), 'sport');
                if ($this->__withdrawCount() >= 4) {
                    if ($taskCount >= 5) {
                        return TRUE;
                    }
                } elseif ($taskCount >= 3) {
                    return TRUE;
                }
                break;
            // 完成3000步
            case 6:
                $sql = 'SELECT COUNT(gold_id) FROM t_gold WHERE user_id = ? AND change_date = ? AND gold_source = ? AND gold_count = ?';
                if ($this->__withdrawCount() >= 4) {
                    if ($this->db->getOne($sql, $this->userId, date('Y-m-d'), 'walk_stage', 8000)) {
                        return TRUE;
                    }
                } elseif ($this->db->getOne($sql, $this->userId, date('Y-m-d'), 'walk_stage', 3000)) {
                    return TRUE;
                }
                break;
        }
        return FALSE;
    }

    public function __withdrawCount () {
        $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_status = ? AND withdraw_amount = ?';
        return $this->db->getOne($sql, $this->userId, 'success', 0.5);
    }
}