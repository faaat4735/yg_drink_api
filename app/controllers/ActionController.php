<?php

namespace Controller;

use Core\Controller;

class ActionController extends Controller
{
    public function drinkAction () {
        $sql = 'INSERT INTO t_user_drink SET user_id = ?, drink_type = ?, create_date = ?, quantity = ?';
        $this->db->exec($sql, $this->userId, 1, date('Y-m-d'), 200);
        return array();
    }
}