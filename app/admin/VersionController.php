<?php

namespace Admin;

use Core\Controller;

Class VersionController extends Controller {
    
    public function adListAction () {
        $sql = "SELECT COUNT(*) FROM t_version_ad";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_version_ad ORDER BY version_id DESC, app_name DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function adDetailAction () {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'change':
                    $sql = 'UPDATE t_version_ad SET ad_status = NOT(ad_status) WHERE version_id = ? AND app_name = ?';
                    $return = $this->db->exec($sql, $_POST['version_id'], $_POST['app_name']);
                    break;
                case 'add' :
                    $sql = 'INSERT INTO t_version_ad SET ad_status = ?, version_id = ?, app_name = ?';
                    $return = $this->db->exec($sql, $_POST['ad_status'], $_POST['version_id'], $_POST['app_name']);
                    break;
            }
            if ($return) {
                return array();
            } else {
                throw new \Exception("Operation failure");
            }
        }
        throw new \Exception("错误操作");
    }
}