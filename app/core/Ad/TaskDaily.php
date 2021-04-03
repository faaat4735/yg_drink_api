<?php

namespace Core\Ad;

use Core\Ad;

class TaskDaily extends Ad
{
    protected function _getInfo() {
        return array(array('name' => '步数累计任务', 'desc' => '让每一步更有价值', 'type' => 'interior', 'url' => 'walkStage', 'award' => 1000), array('name' => '每日打卡', 'desc' => '一天8杯水，健康靠积累', 'type' => 'interior', 'url' => 'clockIn', 'award' => 1500), array('name' => '运动一下', 'desc' => '出门运动运动，强身健体', 'type' => 'interior', 'url' => 'sport', 'award' => 1800), array('name' => '邀请好友', 'desc' => '和好友一起走路赚钱', 'type' => 'interior', 'url' => 'invited', 'award' => 5000));
    }
}