<?php

namespace Controller;

use Core\Controller;

class InfoController extends Controller
{
    /**
     * 首页信息
     * @return array
     */
    public function homeAction () {
        return array('current' => 1200, 'target' => 2400, 'list' => array(array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200), array('time' => time() * 1000, 'drinkType' => '水', 'quantity' => 200)));
    }
}