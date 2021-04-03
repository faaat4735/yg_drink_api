<?php

namespace Controller;

use Core\Controller;
class ApiController extends Controller
{

    public function init() {
        return TRUE;
    }

    public function monitorAction () {
        if ($_GET) {
            $sql = 'INSERT INTO t_ocean_monitor SET imei_md5 = ?, oaid = ?, androidid_md5 = ?, mac_md5 = ?, ad_id = ?, params = ?, callback = ?';
            $this->db->exec($sql, $_GET['imei'] ?? '', $_GET['oaid'] ?? '', $_GET['androidid'] ?? '', $_GET['mac'] ?? '', $_GET['adid'] ?? '', json_encode($_GET), $_GET['callback'] ?? '');
        }
        $return = array('code' => '200', 'msg' => '保存成功');
        echo json_encode($return);
        exit;
    }


}