<?php

namespace Model;
use Core\Model;

class ActivityModel extends Model
{
    public function info ($type) {
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        return $this->db->getRow($sql, $type);
    }
}