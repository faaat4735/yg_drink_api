<?php

namespace Core\Ad;

use Core\Ad;

class StartBot extends Ad
{
    protected function _getInfo() {
        return array(array('img' => OSS_HOST . 'ad/startBot1.png', 'type' => 'interior', 'url' => 'walkStage'), array('img' => OSS_HOST . 'ad/startBot2.png', 'type' => 'interior', 'url' => 'lottery'), array('img' => OSS_HOST . 'ad/startBot3.png', 'type' => 'interior', 'url' => 'clockIn'));
    }
}