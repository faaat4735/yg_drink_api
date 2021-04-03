<?php

namespace Core\Ad;

use Core\Ad;

class TaskTop extends Ad
{
    protected function _getInfo() {
        $taskClass = new \Core\Task($this->userId);
        return array(array('type' => 'task', 'url' => 'video_1', 'taskInfo' => $taskClass->getInfo('video_1'), 'name' => '看视频领金币'), array('type' => 'task', 'url' => 'video_2', 'taskInfo' => $taskClass->getInfo('video_2'), 'name' => '看视频领金币'), array('type' => 'task', 'url' => 'video_3', 'taskInfo' => $taskClass->getInfo('video_3'), 'name' => '看视频领金币'), array('img' => OSS_HOST . 'ad/taskTop1.png', 'type' => 'interior', 'url' => 'lottery', 'name' => '幸运大转盘'));
    }
}