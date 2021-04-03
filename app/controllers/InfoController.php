<?php

namespace Controller;

use Core\Controller;

class InfoController extends Controller
{
    /**
     * 首页信息
     * @return array
     */
    public function startAction () {
        $taskClass = new \Core\Task($this->userId);
        $adClass = new \Core\Ad($this->userId);
        // 更新步数
        if (isset($this->inputData['totalWalk'])) {
            $this->model->walk->updateTotal($this->userId, $this->inputData['totalWalk']);
        }
        // 金币
        // 宝箱
        // receiveTime serverTime currentCount award
        // 中间任务
        // 底部任务
        return array('walkInfo' => $taskClass->getInfo('walk'), 'boxInfo' => $taskClass->getInfo('box'), 'taskMid' => $adClass->getInfo('startMid'), 'taskBot' => $adClass->getInfo('startBot'));
    }

    /**
     * 步数奖励任务信息
     * @return array
     */
    public function walkAction () {
        $taskClass = new \Core\Task($this->userId);
        return $taskClass->getInfo('walk');
    }

    /**
     * 大转盘活动信息
     * @return array
     */
    public function lotteryAction () {
        $taskClass = new \Core\Task($this->userId);
        return array('lotteryInfo' => $taskClass->getInfo('lottery'), 'awardRoll' => array('游客20201117 抽到100金币', '游客20221117 抽到200金币', '游客20201119 抽到50金币', '游客20181117 抽到100金币'));
    }

    /**
     * 任务页面信息
     * @return array
     */
    public function taskAction () {
        $taskClass = new \Core\Task($this->userId);
        $adClass = new \Core\Ad($this->userId);
        return array('signInfo' => $taskClass->getInfo('sign'), 'taskTop' => $adClass->getInfo('taskTop'), 'taskDaily' => $adClass->getInfo('taskDaily'), 'taskNewer' => $adClass->getInfo('taskNewer'));
    }

    /**
     * 步数阶段奖励信息
     * @return array
     */
    public function walkStageAction () {
        $taskClass = new \Core\Task($this->userId);
        return array('list' => $taskClass->getInfo('walkStage'));
    }

    /**
     * 喝水打卡活动信息
     * @return array
     */
    public function drinkAction () {
        $taskClass = new \Core\Task($this->userId);
        return array('list' => $taskClass->getInfo('drink'));
    }

    /**
     * 金币明细
     * @return array
     */
    public function goldDetailsAction () {
        return array('list' => $this->model->gold->details($this->userId));
    }

    /**
     * 提现页面信息
     * @return array
     */
    public function withdrawAction () {
        $sql = 'SELECT wechat_unionid, alipay_account FROM t_user WHERE user_id = ?';
        $bindInfo = $this->db->getRow($sql, $this->userId);
        $isLock = 0;
        $withdrawList = array(array('amount' => 0.5, 'gold' => 5000), array('amount' => 50, 'gold' => 500000), array('amount' => 100, 'gold' => 1000000), array('amount' => 150, 'gold' => 1500000));
        $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = ? AND (withdraw_status = "pending" OR withdraw_status = "success")';
        if ($this->db->getOne($sql, $this->userId, 0.5)) {
            $sql = 'SELECT COUNT(*) FROM t_liveness WHERE user_id = ? AND is_receive = 1 AND liveness_date = ?';
            $livenessCount = $this->db->getOne($sql, $this->userId, date('Y-m-d'));
            if ($livenessCount < 6) {
                $isLock = 1;
            }
        }
        return array('isBindWechat' => ($bindInfo && $bindInfo['wechat_unionid']) ? 1 : 0, 'withdrawList' => $withdrawList, 'isBindAlipay' => ($bindInfo && $bindInfo['alipay_account']) ? 1 : 0, 'isLock' => $isLock);
    }

    /**
     * 提现明细
     * @return array
     */
    public function withdrawDetailsAction () {
        $sql = 'SELECT withdraw_amount amount, CASE withdraw_status WHEN "pending" THEN \'审核中\' WHEN "failure" THEN \'审核失败\' ELSE \'审核成功\' END status, "支付宝" method, UNIX_TIMESTAMP(create_time) * 1000 time, withdraw_remark reason FROM t_withdraw WHERE user_id = ? ORDER BY withdraw_id DESC';
        $withdrawList = $this->db->getAll($sql, $this->userId);
        return array('list' => $withdrawList);
    }

    /**
     * 运动赚活动明细
     * @return array
     */
    public function sportAction () {

        $sql = 'SELECT counter, award_min FROM t_award_config WHERE config_type = ?';
        $sportAward = $this->db->getPairs($sql, 'sport');

        $sportInfo = array(1 => array('type' => 'sport', 'count' => 1, 'award' => $sportAward[1], 'status' => 0, 'name' => '轻轻摆臂', 'desc' => '运动1分钟，可消耗20热量'), 2 => array('type' => 'sport', 'count' => 2, 'award' => $sportAward[2], 'status' => 0, 'name' => '慢慢扭头', 'desc' => '运动2分钟，可消耗50热量'), 3 => array('type' => 'sport', 'count' => 3, 'award' => $sportAward[3], 'status' => 0, 'name' => '出门散步', 'desc' => '运动5分钟，可消耗100热量'), 4 => array('type' => 'sport', 'count' => 4, 'award' => $sportAward[4], 'status' => 0, 'name' => '出门跑步', 'desc' => '运动30分钟，可消耗1000热量'), 5 => array('type' => 'sport', 'count' => 5, 'award' => $sportAward[5], 'status' => 0, 'name' => '打篮球', 'desc' => '运动30分钟，可消耗1000热量'), 6 => array('type' => 'sport', 'count' => 6, 'award' => $sportAward[6], 'status' => 0, 'name' => '踢足球', 'desc' => '运动30分钟，可消耗1000热量'));

        $sql = 'SELECT counter, complete_time, is_receive FROM t_activity_sport WHERE user_id = ?  AND sport_date = ? ORDER BY counter';
        $sportStatus = $this->db->getAll($sql, $this->userId, date('Y-m-d'));
        // status 0:未完成 1:去加速 2:可领取 3:已完成
        foreach ($sportStatus as $status) {
            if ($status['is_receive']) {
                $sportInfo[$status['counter']]['status'] = 3;
            } elseif (strtotime($status['complete_time']) <= time()) {
                $sportInfo[$status['counter']]['status'] = 2;
            } else {
                $sportInfo[$status['counter']]['status'] = 1;
                $sportInfo[$status['counter']]['endTime'] = strtotime($status['complete_time']) * 1000;
                $sportInfo[$status['counter']]['serverTime'] = time() * 1000;
            }
        }
        return array('list' => array_values($sportInfo));
//        return array('list' => array(array('name' => '轻轻摆臂', 'desc' => '运动1分钟，可消耗5能量', 'award' => 10, 'status' => 0, 'endTime' => 1231243, 'serverTime' => time() * 1000), array('name' => '轻轻摆臂', 'desc' => '运动1分钟，可消耗5能量', 'award' => 10, 'status' => 0, 'endTime' => 1231243, 'serverTime' => time() * 1000), array('name' => '轻轻摆臂', 'desc' => '运动1分钟，可消耗5能量', 'award' => 10, 'status' => 0, 'endTime' => 1231243, 'serverTime' => time() * 1000), array('name' => '轻轻摆臂', 'desc' => '运动1分钟，可消耗5能量', 'award' => 10, 'status' => 0, 'endTime' => 1231243, 'serverTime' => time() * 1000), array('name' => '轻轻摆臂', 'desc' => '运动1分钟，可消耗5能量', 'award' => 10, 'status' => 0, 'endTime' => 1231243, 'serverTime' => time() * 1000), array('name' => '轻轻摆臂', 'desc' => '运动1分钟，可消耗5能量', 'award' => 10, 'status' => 0, 'endTime' => 1231243, 'serverTime' => time() * 1000)));
    }

    /**
     * 活跃度信息
     * @return array
     */
    public function livenessAction () {
        // 查询已完成的活跃任务情况
        $livenessList = array(1 => array('count' => 1, 'award' => 100, 'status' => 0, 'name' => '签到', 'desc' => '完成当天签到', 'url' => 'task'), 2 => array('count' => 2, 'award' => 150, 'status' => 0, 'name' => '大转盘活动', 'desc' => '参加3次大转盘活动', 'url' => 'lottery'), 3 => array('count' => 3, 'award' => 150, 'status' => 0, 'name' => '喝水打卡', 'desc' => '完成喝水4次', 'url' => 'clockIn'), 4 => array('count' => 4, 'award' => 200, 'status' => 0, 'name' => '领取步数奖励', 'desc' => '领取15个步数奖励红包', 'url' => 'index'), 5 => array('count' => 5, 'award' => 200, 'status' => 0, 'name' => '运动一下', 'desc' => '参与运动赚活动3次', 'url' => 'sport'), 6 => array('count' => 6, 'award' => 200, 'status' => 0, 'name' => '完3000步', 'desc' => '当日达到3000步可领取奖励', 'url' => 'walkStage'));
        $withdrawCount = $this->__withdrawCount();
        if ($withdrawCount >=4) {
            $livenessList[2]['desc'] = '参加10次大转盘活动';
            $livenessList[3]['desc'] = '完成喝水6次';
            $livenessList[4]['desc'] = '领取25个步数奖励红包';
            $livenessList[5]['desc'] = '参与运动赚活动5次';
            $livenessList[6]['desc'] = '当日达到8000步可领取奖励';
        }

        $sql = 'SELECT counter, is_receive FROM t_liveness WHERE user_id = ? AND liveness_date = ? AND is_receive = 1';
        $livenessInfo = $this->db->getPairs($sql, $this->userId, date('Y-m-d'));
        $receiveAward = 0;
        // status 0:去完成 1:去领取 2:已完成
        foreach ($livenessList as $key => &$liveness) {
            if (isset($livenessInfo[$key])) {
                $liveness['status'] = 2;
                $receiveAward += $liveness['award'];
            } elseif ($this->__liveness($key)) {
                $liveness['status'] = 1;
            }
        }
        return array('receiveAward' => $receiveAward, 'list' => array_values($livenessList));
    }

    /**
     * 邀请好友页面
     * @return array
     */
    public function invitedAction () {
        $sql = 'SELECT COUNT(id) FROM t_user_invited WHERE user_id = ?';
        $invitedCount = $this->db->getOne($sql, $this->userId);

        $sql = 'SELECT IFNULL(SUM(gold_amount), 0) FROM t_gold WHERE user_id = ? AND gold_source IN ("do_invite", "do_invite_1", "do_invite_2")';
        $invitedAward = $this->db->getOne($sql, $this->userId);
        return array('invitedCount' => $invitedCount, 'invitedAward' => $invitedAward);
    }
}