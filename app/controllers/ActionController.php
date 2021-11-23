<?php

namespace Controller;

use Core\Controller;

class ActionController extends Controller
{
    public function drinkAction () {
        $drinkOnce = 200;
        $sql = 'INSERT INTO t_user_drink SET user_id = ?, drink_type = ?, create_date = ?, drink_quantity = ?';
        $this->db->exec($sql, $this->userId, 1, date('Y-m-d'), $drinkOnce);
        $sql = 'SELECT total_id, drink_total, drink_target, is_reach FROM t_user_drink_total WHERE user_id = ? AND create_date = ?';
        $totalInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d'));
        if ($totalInfo) {
            $sql = 'UPDATE t_user_drink_total SET drink_total = ?';
            if (!$totalInfo['is_reach'] && ($totalInfo['drink_total'] + $drinkOnce >= $totalInfo['drink_target'])) {
                $sql .= ', is_reach = 1';
            }
            $sql .= ' WHERE total_id = ?';
            $this->db->exec($sql, $totalInfo['drink_total'] + $drinkOnce, $totalInfo['total_id']);
        } else {
            $sql = 'SELECT drink_target FROM t_user WHERE user_id = ?';
            $drinkTarget = $this->db->getOne($sql, $this->userId);
            $sql = 'INSERT INTO t_user_drink_total SET user_id = ?, drink_total = ?, drink_target = ?, create_date = ?';
            $this->db->exec($sql, $this->userId, $drinkOnce, $drinkTarget, date('Y-m-d'));
        }
        return array();
    }


    /**
     * 用户反馈
     * @return array|int
     */
    public function feedbackAction () {
        if (!isset($this->inputData['content']) || !$this->inputData['content']) {
            return 202;
        }
        //判断多次提交需要超过多久
        $sql = 'SELECT create_time FROM t_user_feedback WHERE user_id = ? ORDER BY feedback_id DESC';
        $lastUpload = $this->db->getOne($sql, $this->userId);
        if ($lastUpload && (time() - strtotime($lastUpload) < 600)) {
            return 401;
        }

        $sql = 'INSERT INTO t_user_feedback SET user_id = :user_id, content = :content, phone = :phone';
        $this->db->exec($sql, array(
            'user_id' => $this->userId,
            'content' => $this->inputData['content'],
            'phone' => $this->inputData['phone'] ?? 0
        ));
        return array();
    }

    /**
     * 绑定微信
     * @return array|int
     */
    //todo
    public function wechatAction () {
        if (!isset($this->inputData['unionid'])) {
            return 202;
        }
        $sql = 'SELECT wechat_unionid FROM t_user WHERE user_id = ?';
        $wechatInfo = $this->db->getOne($sql, $this->userId);
        if ($wechatInfo) {
            return 304;
        }
        $sql = 'SELECT COUNT(*) FROM t_user WHERE wechat_unionid = ?';
        $unionInfo = $this->db->getOne($sql, $this->inputData['unionid']);
        if ($unionInfo) {
            return 305;
        }
        $sql = 'SELECT COUNT(*) FROM t_user_wechat_cancel WHERE wechat_unionid = ?';
        $unionInfo = $this->db->getOne($sql, $this->inputData['unionid']);
        if ($unionInfo) {
            return 305;
        }
        $sql = 'UPDATE t_user SET wechat_openid = ?, nickname = ?, language = ?, sex = ?, province = ?, city = ?, country = ?, headimgurl = ?, wechat_unionid = ? WHERE user_id = ?';
        $this->db->exec($sql, $this->inputData['openid'] ?? '', $this->inputData['nickname'] ?? '', $this->inputData['language'] ?? '', $this->inputData['sex'] ?? 0, $this->inputData['province'] ?? '', $this->inputData['city'] ?? '', $this->inputData['country'] ?? '', $this->inputData['headimgurl'] ?? '', $this->inputData['unionid'], $this->userId);
        $return = array();
        $sql = 'SELECT * FROM t_gold WHERE gold_source = ? AND user_id = ?';
        $awardInfo = $this->db->getOne($sql, 'wechat', $this->userId);

        if (!$awardInfo) {
            $sql = 'SELECT activity_award FROM t_activity WHERE activity_type = "wechat"';
            $gold = $this->db->getOne($sql);
            $this->model->gold->insert(array('user_id' => $this->userId, 'gold_amount' => $gold, 'gold_source' => 'wechat', 'gold_count' => '1'));
            $return = array('count' => 1, 'num' => $gold, 'type' => 'wechat');
        }

        return $return;
    }


    /**
     * 申请提现
     * @return array|int
     */
    // todo
    public function requestWithdrawAction () {
        if (!isset($this->inputData['amount']) || !in_array($this->inputData['amount'], array(0.5, 50, 100, 150))) {
            return 202;
        }
        $sql = 'SELECT wechat_unionid, wechat_openid, user_status, alipay_account, alipay_name FROM t_user WHERE user_id = ?';
        $payInfo = $this->db->getRow($sql, $this->userId);
        if (!$payInfo['user_status']) {
            return 310;
        }
        // 20201217 微信提现转支付宝提现
        if ($_SERVER['HTTP_VERSION_CODE'] <= 1.3) {
            if (!$payInfo['alipay_account']) {
                return 316;
            }
            //todo 添加支付宝实名认证
            $payMethod = 'alipay';
            $payAccount = $payInfo['alipay_account'];
            $payName = $payInfo['alipay_name'];
        } elseif (!isset($this->inputData['method']) && !in_array($this->inputData['method'], array('alipay', 'wechat'))) {
            return 202;
        } else {
            switch ($this->inputData['method']) {
                case 'alipay':
                    if (!$payInfo['alipay_account']) {
                        return 316;
                    }
                    $payMethod = 'alipay';
                    $payAccount = $payInfo['alipay_account'];
                    $payName = $payInfo['alipay_name'];
                    break;
                case 'wechat':
//                    return 324;
                    if (!$payInfo['wechat_unionid']) {
                        return 311;
                    }
                    $payMethod = 'wechat';
                    $payAccount = $payInfo['wechat_openid'];
                    $payName = '';
                    break;
            }
        }

        $withdrawalGold = $this->inputData['amount'] * 10000;
        $currentGold = $this->model->gold->total($this->userId, 'current');
        if ($currentGold < $withdrawalGold) {
            return 312;
        }
        if (0.5 == $this->inputData['amount']) {
            $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = ? AND (withdraw_status = "pending" OR withdraw_status = "success")';
            if ($this->db->getOne($sql, $this->userId, 0.5)) {
                if ($_SERVER['HTTP_VERSION_CODE'] <= 1.3) {
                    return 313;
                } else {
                    $sql = 'SELECT COUNT(*) FROM t_liveness WHERE user_id = ? AND is_receive = 1 AND liveness_date = ?';
                    $livenessCount = $this->db->getOne($sql, $this->userId, date('Y-m-d'));
                    if ($livenessCount < 6) {
                        return 323;
                    }
                    $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = ? AND (withdraw_status = "pending" OR withdraw_status = "success") AND create_time >= ?';
                    if ($todayCount = $this->db->getOne($sql, $this->userId, 0.5, date('Y-m-d 00:00:00'))) {
                        if ($todayCount <= 1) {
                            $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = ? AND (withdraw_status = "pending" OR withdraw_status = "success") AND create_time < ?';
                            if ($this->db->getOne($sql, $this->userId, 0.5, date('Y-m-d 00:00:00'))) {
                                return 325;
                            }
                        } else {
                            return 325;
                        }
                    }
                }
            }
        } elseif (5 == $this->inputData['amount']) {
            $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = ? AND (withdraw_status = "pending" OR withdraw_status = "success")';
            if ($this->db->getOne($sql, $this->userId, 5)) {
                return 313;
            }
            if (date('H') > 12 ) {
                return 327;
            }
        }
        //todo 高并发多次插入记录问题 加锁解决
//        $sql = 'INSERT INTO t_withdraw (user_id, withdraw_amount, withdraw_gold, withdraw_status, withdraw_method, wechat_openid) SELECT :user_id, :withdraw_amount,:withdraw_gold, :withdraw_status, :withdraw_method, :wechat_openid FROM DUAL WHERE NOT EXISTS (SELECT withdraw_id FROM t_withdraw WHERE user_id = :user_id AND withdraw_amount = :withdraw_amount AND withdraw_status = :withdraw_status)';
//        $this->db->exec($sql, array('user_id' => $this->userId, 'withdraw_amount' => $this->inputData['amount'], 'withdraw_gold' => $withdrawalGold, 'withdraw_method' => 'wechat', 'withdraw_status' => 'pending', 'wechat_openid' => $payInfo['wechat_openid']));
        $sql = 'INSERT INTO t_withdraw SET user_id = :user_id, withdraw_amount = :withdraw_amount, withdraw_gold = :withdraw_gold, withdraw_status = :withdraw_status, withdraw_account = :withdraw_account, withdraw_name = :withdraw_name, withdraw_method = :withdraw_method';
        $this->db->exec($sql, array('user_id' => $this->userId, 'withdraw_amount' => $this->inputData['amount'], 'withdraw_gold' => $withdrawalGold, 'withdraw_status' => 'pending', 'withdraw_account' => $payAccount, 'withdraw_name' => $payName, 'withdraw_method' => $payMethod));
        return array();
    }

    /**
     * 领取奖励
     */
    public function awardAction () {
        if (!isset($this->inputData['count']) || !isset($this->inputData['num']) || !isset($this->inputData['type'])) {
            return 202;
        }
        $taskClass = new \Core\Task($this->userId);
        return $taskClass->receiveAward($this->inputData);
    }
}