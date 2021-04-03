<?php

namespace Core;

class Model
{
    public function __get($name) {
        if (!isset($this->$name)) {
            switch ($name) {
                case 'db':
                    $temp = Db::getDbInstance();
                    break;
                default:
                    $className = 'Model\\' . ucfirst($name) . 'Model';
                    $temp = new $className();
                    //todo 验证类存在
            }
            $this->$name = $temp;
        }
        return $this->$name;
    }
}