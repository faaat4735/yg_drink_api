<?php

namespace Core\Task;

use Core\Task;

class Walk extends Task
{
    protected function _getInfo() {
        // receiveTime 获取最远 5次（可设置）领取时间 + 10分钟
        // restCount 剩余可领取次数
        // walkCount 当前总步数
        // list 可领取的金币列表
        return $this->model->walk->receiveInfo($this->userId);
    }
}