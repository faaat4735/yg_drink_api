<?php 
namespace Admin;

use Core\Controller;

Class WithdrawController extends Controller {
    /**
     * 获取提现列表
     * @return array
     */
    public function listAction () {
        $whereArr = array('1 = 1');
        $dataArr = array();

        if (isset($_POST['userId']) && $_POST['userId']) {
            $whereArr[] = 'w.user_id = :user_id';
            $dataArr['user_id'] = $_POST['userId'];
        }
        
        if (isset($_POST['status']) && $_POST['status']) {
            $whereArr[] = 'w.withdraw_status = :withdraw_status';
            $dataArr['withdraw_status'] = $_POST['status'];
        }
        $where = 'WHERE ' . implode(' AND ', $whereArr);
        $sql = "SELECT COUNT(*) FROM t_withdraw w " . $where;
        $totalCount = $this->db->getOne($sql, $dataArr);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT w.*, u.create_time user_time FROM t_withdraw w LEFT JOIN t_user u USING(user_id) $where ORDER BY w.withdraw_id DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql, $dataArr);
        }
        foreach ($list as &$info) {
            $sql = 'SELECT COUNT(withdraw_id) count, IFNULL(SUM(withdraw_amount), 0) total FROM t_withdraw WHERE user_id = ? AND withdraw_status = "success"';
            $info = array_merge($info, $this->db->getRow($sql, $info['user_id']));
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }

    /**
     * 处理提现请求
     * @return array
     * @throws \Exception
     */
    public function actionAction () {
        if (isset($_POST['action']) && (isset($_POST['withdraw_id']) || isset($_POST['ids']))) {
            switch ($_POST['action']) {
                case 'failed' :
                    if (isset($_POST['ids'])) {
                        $return = '';
                        if (($_POST['ids']) && is_array($_POST['ids'])) {
                            $sql = 'UPDATE t_withdraw SET withdraw_status = "failure", withdraw_remark = "", change_time = ? WHERE withdraw_id IN (' . implode(', ', $_POST['ids']) . ') AND withdraw_status = "pending"';
                            $return = $this->db->exec($sql, date('Y-m-d H:i:s'));
                        }
                    } else {
                        $return = $this->model->withdraw->updateStatus(array('withdraw_status' => 'failure', 'withdraw_remark' => $_POST['withdraw_remark'] ?? '', 'withdraw_id' => $_POST['withdraw_id']));
                    }
                    break;
                case 'success':
                    $sql = 'SELECT * FROM t_withdraw WHERE withdraw_id = ?';
                    $payInfo = $this->db->getRow($sql, $_POST['withdraw_id']);
                    switch ($payInfo['withdraw_method']) {
                        case 'alipay':
                            $returnStatus = TRUE;
                            break;
                        case 'wechat':
                            $wechatPay = new \Core\Wxpay();
                            $returnStatus = $wechatPay->transfer($payInfo['withdraw_amount'], $payInfo['wechat_openid']);
                            break;
                    }
                    if (TRUE === $returnStatus) {
                        $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ?';
                        $withdrawCount = $this->db->getOne($sql, $payInfo['user_id']) ?: 0;
                        $this->model->gold->insert(array('user_id' => $payInfo['user_id'], 'gold_amount' => 0 - $payInfo['withdraw_gold'], 'gold_source' => "withdraw", 'gold_count' => $withdrawCount + 1));
                        $return = $this->model->withdraw->updateStatus(array('withdraw_status' => 'success', 'withdraw_id' => $_POST['withdraw_id']));
                    } else {
                        //to do failure reason from api return
                        $return = $this->model->withdraw->updateStatus(array('withdraw_status' => 'failure', 'withdraw_remark' => $returnStatus, 'withdraw_id' => $_POST['withdraw_id']));
                    }
                    break;
            }
            if ($return) {
                return array();
            } else {
                throw new \Exception("Operation failure");
            }
        }
        throw new \Exception("Error Request");
    }
}