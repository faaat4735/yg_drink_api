<?php

namespace Model;
use Core\Model;

class UserModel extends Model
{
    /**
     * 根据用户设备号获取用户信息
     * @return bool
     */
    public function getUserInfoByDeviceId ($deviceId) {
        $sql = 'SELECT user_id, access_token accessToken, nickname, headimgurl, invited_code invitedCode FROM t_user WHERE device_id = ?';
        $userInfo = $this->db->getRow($sql, $deviceId);
        if ($userInfo) {
            $this->updateLoginTime($userInfo['user_id']);
            $userInfo['currentGold'] = $this->gold->total($userInfo['user_id'], 'current');
            $userInfo['todayGold'] = $this->gold->todayGold($userInfo['user_id']);
            $userInfo['newerAward'] = 0;
            unset($userInfo['user_id']);
            return $userInfo;
        } else {
            return FALSE;
        }
    }

    /**
     * 创建用户
     * @return bool
     */
    public function createUser ($deviceId, $deviceInfo) {
        $invitedCode = $this->createCode();

        $nickName = '游客' . substr($deviceId, -2) . date('md');//游客+设备号后2位+用户激活日期

        $accessToken = md5($deviceId . time());
        // 插入用户
        $sql = 'INSERT INTO t_user SET user_source = ?, device_id = ?, access_token = ?, nickname = ?, OAID = ?, brand = ?, model = ?, SDKVersion = ?, AndroidId = ?, IMEI = ?, MAC = ?, invited_code = ?';
        $this->db->exec($sql, $_SERVER['HTTP_SOURCE'] ?? '', $deviceId, $accessToken, $nickName, $deviceInfo['OAID'] ?? '', $deviceInfo['brand'] ?? '', $deviceInfo['model'] ?? '', $deviceInfo['SDKVersion'] ?? '', $deviceInfo['AndroidID'] ?? '', $deviceInfo['IMEI'] ?? '', $deviceInfo['MAC'] ?? '', $invitedCode);
        // 返回信息
        $userId = $this->db->lastInsertId();
        $this->updateLoginTime($userId);
        $callbackUrl = $this->callback($deviceInfo['OAID'] ?? '', $deviceInfo['IMEI'] ?? '', $deviceInfo['AndroidID'] ?? '', $deviceInfo['MAC'] ?? '');
        if ($callbackUrl) {
            file_get_contents($callbackUrl);
        }
        $sql = 'SELECT award_min FROM t_award_config WHERE config_type = "newer"';
        $newerAward = $this->db->getOne($sql);
//        $this->gold->insert(array('user_id' => $userId, 'gold_count' => 1, 'gold_amount' => $newerAward, 'gold_source' => 'newer', 'isDouble' => 0));
        return array('accessToken' => $accessToken, 'nickname' => $nickName, 'headimgurl' => '', 'currentGold' => 0, 'invitedCode' => $invitedCode, 'todayGold' => 0, 'newerAward' => $newerAward);
    }

    /**
     * 生成邀请码
     * @return string
     */
    public function createCode() {
//        $createList = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $createList = '0123456789';
        $code = '';
        for($i=0;$i<8;$i++) {
            if ($i == 0) {
                $code .= rand(1, 9);
            } else {
                $code .= $createList{rand(0, 9)};
            }
//            $code .= $createList{rand(0, 33)};
        }
        $sql = 'SELECT COUNT(user_id) FROM t_user WHERE invited_code = ?';
        $isExist = $this->db->getOne($sql, $code);
        if ($isExist) {
            return $this->createCode();
        }
        return $code;
    }

    /**
     * 更新用户登录时间信息
     * @param $userId
     */
    public function updateLoginTime($userId) {
        // 更新用户每日首次登陆时间
        $sql = 'INSERT IGNORE INTO t_user_first_login SET date = ?, user_id = ?';
        $this->db->exec($sql, date('Y-m-d'), $userId);
        // 更新用户最后登陆时间
        $sql = 'UPDATE t_user SET last_login_time = ? WHERE user_id = ?';
        $this->db->exec($sql, date('Y-m-d H:i:s'), $userId);
    }

    /**
     * @param $imei
     * @param $androidid
     * @param $mac
     * @return array
     */
    public function callback ($oaid, $imei, $androidid, $mac) {
        $callback = '';
        if ($oaid) {
            $sql = 'SELECT callback FROM t_ocean_monitor WHERE oaid = ? ORDER BY log_id DESC';
            $callback = $this->db->getOne($sql, $oaid);
        }
        if ($imei) {
            $sql = 'SELECT callback FROM t_ocean_monitor WHERE imei_md5 = ? ORDER BY log_id DESC';
            $callback = $this->db->getOne($sql, md5($imei));
        }
        if ($callback) {
            return $callback;
        }
        if ($androidid) {
            $sql = 'SELECT callback FROM t_ocean_monitor WHERE androidid_md5 = ? ORDER BY log_id DESC';
            $callback = $this->db->getOne($sql, md5($androidid));
        }
        if ($callback) {
            return $callback;
        }
        if ($mac) {
            $sql = 'SELECT callback FROM t_ocean_monitor WHERE mac_md5 = ? ORDER BY log_id DESC';
            $callback = $this->db->getOne($sql, md5(str_replace(':', '', $mac)));
        }
        if ($callback) {
            return $callback;
        }
        return $callback;
    }

}