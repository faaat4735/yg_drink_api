<?php
namespace Admin;

use Core\Controller;

Class UserController extends Controller {

    /**
     * 获取用户列表
     * @return array
     */
    public function listAction () {
        $whereArr = array();
        $dataArr = array();
        
        if (isset($_POST['user_id']) && $_POST['user_id']) {
            $whereArr[] = 'user_id = :user_id';
            $dataArr['user_id'] = $_POST['user_id'];
        }
        if (isset($_POST['invited_code']) && $_POST['invited_code']) {
            $whereArr[] = 'invited_code = :invited_code';
            $dataArr['invited_code'] = $_POST['invited_code'];
        }
        if (!$whereArr) {
            $whereArr[] = 'create_time > :create_time';
            $dataArr['create_time'] = date('Y-m-d', strtotime('-3 days'));
        }
        $where = 'WHERE ' . implode(' AND ', $whereArr);

        $sql = "SELECT COUNT(*) FROM t_user " . $where;
        $totalCount = $this->db->getOne($sql, $dataArr);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_user $where ORDER BY user_id DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql, $dataArr);
            foreach ($list as &$userInfo) {
                $userInfo = array_merge($userInfo, $this->model->gold->total($userInfo['user_id']));
            }
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }

    /**
     * 用户金币明细
     * @return array
     */
    public function goldAction () {
        if (isset($_POST['id'])) {
            $totalCount = $this->model->gold->goldTotal($_POST['id']);
            $configInfo = array();
            if ($totalCount) {
                $configInfo = $this->model->gold->goldDetails($_POST['id'], $this->page);
            }
            
            return array(
                'totalCount' => (int) $totalCount,
                'list' => $configInfo
            );
        }
        $return = array('status' => 'error', 'data' => '', 'msg' => '无效参数');
        echo json_encode($return);;exit;
    }

    /**
     * 用户反馈
     * @return array
     */
    public function feedbackAction () {
        $sql = "SELECT COUNT(*) FROM t_user_feedback";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT f.*, u.nickname FROM t_user_feedback f
                LEFT JOIN t_user u USING(user_id)
                ORDER BY f.feedback_id DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
}