<?php

namespace Controller;

use Core\Controller;

class ActionController extends Controller
{
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

    /**
     * 绑定微信
     * @return array|int
     */
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
            $sql = 'SELECT award_min FROM t_award_config WHERE config_type = "wechat"';
            $gold = $this->db->getOne($sql);
            $this->model->gold->insert(array('user_id' => $this->userId, 'gold_amount' => $gold, 'gold_source' => 'wechat', 'gold_count' => '1'));
            $return['award'] = $gold;
        }

        return $return;
    }

    /**
     * 绑定邀请码
     * @return array|int
     */
    public function invitedAction () {
        if (!isset($this->inputData['invitedCode'])) {
            return 202;
        }
        // 您已填写过邀请码
        $sql = 'SELECT COUNT(*) FROM t_user_invited WHERE invited_id = ?';
        $invitedInfo = $this->db->getOne($sql, $this->userId);
        if ($invitedInfo) {
            return 306;
        }
        // 邀请码无效，请重新输入
        $sql = 'SELECT user_id, create_time FROM t_user WHERE invited_code = ?';
        $userInfo = $this->db->getRow($sql, $this->inputData['invitedCode']);
        if (!$userInfo) {
            return 307;
        }
        // 验证码无效，请填写比您先注册的用户的邀请码
        $sql = 'SELECT create_time, wechat_unionid FROM t_user WHERE user_id = ?';
        $invitedUserInfo = $this->db->getRow($sql, $this->userId);
        if (strtotime($invitedUserInfo['create_time']) <= strtotime($userInfo['create_time'])) {
            return 308;
        }
        // 请先绑定微信后，再填写邀请码
        if (!$invitedUserInfo['wechat_unionid']) {
            return 309;
        }
        $sql = 'INSERT INTO t_user_invited SET user_id = ?, invited_id = ?';
        $this->db->exec($sql, $userInfo['user_id'], $this->userId);

        $return = array();
        $sql = 'SELECT * FROM t_gold WHERE gold_source = ? AND user_id = ?';
        $awardInfo = $this->db->getOne($sql, 'invited', $this->userId);
        if (!$awardInfo) {
            $sql = 'SELECT award_min FROM t_award_config WHERE config_type = "invited"';
            $invitedGold = $this->db->getOne($sql);
            $this->model->gold->insert(array('user_id' => $this->userId, 'gold_amount' => $invitedGold, 'gold_source' => 'invited', 'gold_count' => '1'));
            $return['award'] = $invitedGold;

            $sql = 'SELECT award_min FROM t_award_config WHERE config_type = "do_invite"';
            $gold = $this->db->getOne($sql);
            $sql = 'SELECT IFNULL(COUNT(id), 0) FROM t_user_invited WHERE user_id = ?';
            $userInvitedCount = $this->db->getOne($sql, $userInfo['user_id']);
            $this->model->gold->insert(array('user_id' => $userInfo['user_id'], 'gold_amount' => $gold, 'gold_source' => 'do_invite', 'gold_count' => $userInvitedCount + 1));
        }
        return $return;
    }

    /**
     * 申请提现
     * @return array|int
     */
    public function requestWithdrawAction () {
        if (!isset($this->inputData['amount']) || !in_array($this->inputData['amount'], array(0.5, 5, 50, 100, 150))) {
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
                    return 324;
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
     * 用户反馈
     * @return array|int
     */
    public function feedbackAction () {
        if (!isset($this->inputData['content']) && $this->inputData['content']) {
            return 202;
        }
        //判断多次提交需要超过多久
        $sql = 'SELECT create_time FROM t_user_feedback WHERE user_id = ? ORDER BY feedback_id DESC';
        $lastUpload = $this->db->getOne($sql, $this->userId);
        if ($lastUpload && (time() - strtotime($lastUpload) < 600)) {
            return 314;
        }

        $image1 = $image2 = $image3 = FALSE;
        foreach (array('image1', 'image2', 'image3') as $image) {
            if (isset($this->inputData[$image]) && $this->inputData[$image]) {
                $$image = $this->uploadImage($this->inputData[$image]);
                if (FALSE === $$image) {
                    continue;
                }
            }
        }

        $sql = 'INSERT INTO t_user_feedback SET user_id = :user_id, content = :content, phone = :phone, image_1 = :image_1, image_2 = :image_2, image_3 = :image_3';
        $this->db->exec($sql, array(
            'user_id' => $this->userId,
            'content' => $this->inputData['content'],
            'phone' => $this->inputData['phone'] ?? 0,
            'image_1' => $image1 ?: '',
            'image_2' => $image2 ?: '',
            'image_3' => $image3 ?: '',
        ));
        return array();
    }

    /**
     * 绑定支付宝账号
     * @return array|int
     */
    public function alipayAction () {
        if (!isset($this->inputData['name']) || !isset($this->inputData['account'])) {
            return 202;
        }
        $sql = 'SELECT alipay_account FROM t_user WHERE user_id = ?';
        $aliInfo = $this->db->getOne($sql, $this->userId);
        if ($aliInfo) {
            return 317;
        }
        $sql = 'SELECT user_id FROM t_user WHERE alipay_account = ?';
        $bindInfo = $this->db->getOne($sql, $this->inputData['account']);
        if ($bindInfo) {
            return 318;
        }
        if (!preg_match("/^1[34578]{1}\d{9}$/", $this->inputData['account']) && !preg_match("/^[a-zA-Z][a-zA-z0-9-]*[@]([a-zA-Z0-9]*[.]){1,3}[a-zA-Z]*/", $this->inputData['account'])) {
            return 326;
        }
        $sql = 'UPDATE t_user SET alipay_account = ?, alipay_name = ? WHERE user_id = ?';
        $this->db->exec($sql, $this->inputData['account'], $this->inputData['name'], $this->userId);
        return array();
    }

    /**
     * 解绑微信
     * @return array|int
     */
    public function cancelWechatAction () {
        $sql = 'SELECT wechat_unionid, device_id FROM t_user WHERE user_id = ?';
        $wechatInfo = $this->db->getRow($sql, $this->userId);
        if (!$wechatInfo['wechat_unionid']) {
            return 202;
        }
        $sql = 'UPDATE t_user SET nickname = ?, headimgurl = "", wechat_unionid = "" WHERE user_id = ?';
        $this->db->exec($sql, '游客' . substr($wechatInfo['device_id'], -2) . date('md'), $this->userId);
        $sql = 'INSERT INTO t_user_wechat_cancel SET user_id = ?, wechat_unionid = ?';
        $this->db->exec($sql, $this->userId, $wechatInfo['wechat_unionid']);
        return array();
    }

    /**
     * 运动赚活动 开始运动
     * @return array
     */
    public function sportStartAction () {
        // 传入参数 第几个运动
        $sql = 'SELECT counter, award_min FROM t_award_config WHERE config_type = ?';
        $sportAward = $this->db->getPairs($sql, 'sport');
        $sportInfo = array(1 => array('name' => '轻轻摆臂', 'desc' => '运动1分钟，可消耗20热量', 'time' => 1), 2 => array('name' => '慢慢扭头', 'desc' => '运动2分钟，可消耗50热量', 'time' => 2), 3 => array('name' => '出门散步', 'desc' => '运动5分钟，可消耗100热量', 'time' => 5), 4 => array('name' => '出门跑步', 'desc' => '运动30分钟，可消耗1000热量', 'time' => 30), 5 => array('name' => '打篮球', 'desc' => '运动30分钟，可消耗1000热量', 'time' => 30), 6 => array('name' => '踢足球', 'desc' => '运动30分钟，可消耗1000热量', 'time' => 30));
        if (!isset($this->inputData['count']) || !in_array($this->inputData['count'], array_keys($sportInfo))) {
            return 202;
        }
        // 判断是否有其他的未结束的
        $sql = 'SELECT COUNT(sport_id) FROM t_activity_sport WHERE is_receive = 0 AND user_id = ? AND sport_date = ?';
        if ($this->db->getOne($sql, $this->userId, date('Y-m-d'))) {
            return 319;
        }
        // 判断当前运动是否可开始
        $sql = 'SELECT COUNT(sport_id) FROM t_activity_sport WHERE user_id = ? AND sport_date = ? AND counter = ?';
        if ($this->db->getOne($sql, $this->userId, date('Y-m-d'), $this->inputData['count'])) {
            return 320;
        }
        // 开始运动 返回信息
        $sql = 'INSERT INTO t_activity_sport SET user_id = ?, sport_date = ?, counter = ?, complete_time = ?';
        $endTime = date('Y-m-d H:i:s', strtotime('+ ' . $sportInfo[$this->inputData['count']]['time'] . 'minute'));
        $this->db->exec($sql, $this->userId, date('Y-m-d'), $this->inputData['count'], $endTime);
        return array('type' => 'sport', 'name' => $sportInfo[$this->inputData['count']]['name'], 'desc' => $sportInfo[$this->inputData['count']]['desc'], 'award' => $sportAward[$this->inputData['count']], 'status' => 1, 'endTime' => strtotime($endTime) * 1000, 'serverTime' => time() * 1000, 'count' => $this->inputData['count']);
    }

    /**
     * 运动赚活动加速
     * @return array
     */
    public function sportSpeedAction () {
        // 传入参数 第几个运动
        $sql = 'SELECT counter, award_min FROM t_award_config WHERE config_type = ?';
        $sportAward = $this->db->getPairs($sql, 'sport');
        $sportInfo = array(1 => array('name' => '轻轻摆臂', 'desc' => '运动1分钟，可消耗20热量', 'time' => 1), 2 => array('name' => '慢慢扭头', 'desc' => '运动2分钟，可消耗50热量', 'time' => 2), 3 => array('name' => '出门散步', 'desc' => '运动5分钟，可消耗100热量', 'time' => 5), 4 => array('name' => '出门跑步', 'desc' => '运动30分钟，可消耗1000热量', 'time' => 30), 5 => array('name' => '打篮球', 'desc' => '运动30分钟，可消耗1000热量', 'time' => 30), 6 => array('name' => '踢足球', 'desc' => '运动30分钟，可消耗1000热量', 'time' => 30));
        if (!isset($this->inputData['count']) || !in_array($this->inputData['count'], array_keys($sportInfo))) {
            return 202;
        }
        // 判断当前运动是否可以加速
        $sql = 'SELECT * FROM t_activity_sport WHERE user_id = ? AND sport_date = ? AND counter = ?';
        $sport = $this->db->getRow($sql, $this->userId, date('Y-m-d'), $this->inputData['count']);
        if (!$sport || strtotime($sport['complete_time']) <= time()) {
            return 321;
        }
        $sql = 'UPDATE t_activity_sport SET complete_time = ? WHERE sport_id = ?';
        $this->db->exec($sql, date('Y-m-d H:i:s'), $sport['sport_id']);
        return array('type' => 'sport', 'name' => $sportInfo[$this->inputData['count']]['name'], 'desc' => $sportInfo[$this->inputData['count']]['desc'], 'award' => $sportAward[$this->inputData['count']], 'status' => 2, 'count' => $this->inputData['count']);
    }

    /**
     * 领取活跃度
     * @return array|int
     */
    public function livenessAwardAction () {
//        $livenessList = array(1 => array('count' => 1, 'award' => 50, 'status' => 0, 'name' => '签到', 'desc' => '完成当天签到', 'url' => 'task'), 2 => array('count' => 2, 'award' => 50, 'status' => 0, 'name' => '大转盘活动', 'desc' => '参加3次大转盘活动', 'url' => 'lottery'), 3 => array('count' => 3, 'award' => 200, 'status' => 0, 'name' => '领取步数奖励', 'desc' => '领取15个步数奖励红包', 'url' => 'index'), 4 => array('count' => 4, 'award' => 200, 'status' => 0, 'name' => '喝水打卡', 'desc' => '完成喝水4次', 'url' => 'clockIn'), 5 => array('count' => 5, 'award' => 200, 'status' => 0, 'name' => '运动一下', 'desc' => '参与运动赚活动3次', 'url' => 'sport'), 6 => array('count' => 6, 'award' => 300, 'status' => 0, 'name' => '完成8000步', 'desc' => '当日达到8000步可领取奖励', 'url' => 'walkStage'));
        if (!isset($this->inputData['count']) || !in_array($this->inputData['count'], array(1, 2, 3, 4, 5, 6))) {
            return 202;
        }
        if ($this->__liveness($this->inputData['count'])) {
            $sql = 'INSERT INTO t_liveness (user_id, counter, liveness_date, is_receive) SELECT :user_id, :counter, :liveness_date, 1 FROM DUAL WHERE NOT EXISTS (SELECT liveness_id FROM t_liveness WHERE user_id = :user_id AND counter = :counter AND liveness_date = :liveness_date AND is_receive = 1)';
            $this->db->exec($sql, array('user_id' => $this->userId, 'counter' => $this->inputData['count'], 'liveness_date' => date('Y-m-d')));
            return array();
        }
        return 322;
    }

    /**
     * 保存用户上传图片
     * @param $code base64
     * @return bool|string
     */
    protected function uploadImage ($code) {
//        strlen($code);
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $code, $result)){
            $ext = strtolower($result[2]);
//            if (!in_array($ext, array('jpg','jpeg', 'png', 'gif', 'bmp'))) {
//                return new ApiReturn('', 313,'上传图片格式不正确');
//            }

            $length = strlen($code) - strlen($result[1]);
            $size = $length - $length / 4;
            if ($size > 1024 * 1024) {
                return FALSE;
            }
            $saveFile = (ENV_PRODUCTION ? '' : 'test-') . substr(md5(substr($code, 20)), 10) . time() . '.' . strtolower($ext);

            file_put_contents('/tmp/' . $saveFile, base64_decode(str_replace($result[1], '', $code)));

            $oss = new \Core\Oss();
            $uploadReturn = $oss->upload('upload/' . $saveFile, '/tmp/' . $saveFile);
            if ($uploadReturn !== TRUE) {
                return FALSE;
            }
            return 'upload/' . $saveFile;

//            $saveFile = date('Ymd') . '/';
//            if (!is_dir(UPLOAD_IMAGE_DIR . $saveFile)) {
//                $a = mkdir(UPLOAD_IMAGE_DIR . $saveFile, 0755, true);
//            }
//
//            if (file_put_contents(UPLOAD_IMAGE_DIR . $saveFile, base64_decode(str_replace($result[1], '', $code)))) {
//                return 'upload/image/' . $saveFile;
//            } else {
//                return new ApiReturn('', 314,'上传失败');
//            }
        }else{
            return FALSE;
        }
    }

}