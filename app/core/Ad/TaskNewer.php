<?php

namespace Core\Ad;

use Core\Ad;

class TaskNewer extends Ad
{
    protected function _getInfo() {
        $sql = 'SELECT award_min FROM t_award_config WHERE config_type = "wechat"';
        $wechatAward = $this->db->getOne($sql);
        $sql = 'SELECT wechat_unionid FROM t_user WHERE user_id = ?';
        $isBindWechat = $this->db->getOne($sql, $this->userId) ? 1 : 0;
        $sql = 'SELECT id FROM t_user_invited WHERE invited_id = ?';
        $isBindInvited = $this->db->getOne($sql, $this->userId) ? 1 : 0;
        return array(array('name' => '绑定微信', 'desc' => '让每一步更有价值', 'type' => 'task', 'url' => 'wechat', 'award' => $wechatAward, 'isComplete' => $isBindWechat), array('name' => '填写邀请码', 'desc' => '让每一步更有价值', 'type' => 'task', 'url' => 'invitedCode', 'award' => 500, 'isComplete' => $isBindInvited));
    }
}