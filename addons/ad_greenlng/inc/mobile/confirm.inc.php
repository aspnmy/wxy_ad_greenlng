<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileConfirm extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
        $this->checkauth();
    }

    public function exec()
    {
        global $_W, $_GPC;
        $_share = $this->_share;
        $id = intval($_GPC['id']);
        $house = ad_greenlng_fetch($id);
        if (!$house) {
            message('数据不存在或已删除', referer(), 'error');
        }
        $sql = "SELECT `ordid` FROM " . tablename('adgreenlng_house_order') . " WHERE `uid`=:uid AND `houseid`=:id AND `status`=0";
        $params = array(
            ':uid' => $_W['member']['uid'],
            ':id' => $house['id'],
        );
        $ordid = pdo_fetchcolumn($sql, $params);
        if ($ordid) {
            message('您有未支付的订单', $this->createMobileUrl('myorder', array('act' => 'detail', 'ordid' => $ordid)), 'warning');
        }

        if (checksubmit('submit')) {
            $realname = trim($_GPC['realname']);
            $mobile = trim($_GPC['mobile']);
            $remark = trim($_GPC['remark']);
            if (empty($realname)) {
                message('请输入姓名', referer(), 'error');
            }
            if (empty($mobile)) {
                message('请输入手机号', referer(), 'error');
            }
            if (!preg_match(REGULAR_MOBILE, $mobile)) {
                message('手机号格式不正确', referer(), 'error');
            }
            $data = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'orderno' => date('ymd') . random(6, 1),
                'houseid' => $house['id'],
                'paytype' => 0,
                'transid' => '',
                'status' => 0,
                'amount' => $house['deposit'],
                'realname' => $realname,
                'mobile' => $mobile,
                'remark' => $remark,
                'dateline' => TIMESTAMP,
            );
            pdo_insert('adgreenlng_house_order', $data);
            $new_id = pdo_insertid();
            if (!$new_id) {
                message('系统错误，请稍后重试', referer(), 'error');
            }
            message('订单创建成功，即将跳转到付款页面...', $this->createMobileUrl('pay', array('ordid' => $new_id)), 'success');
        }

        include $this->template('confirm');
    }
}

$obj = new Ad_greenlng_doMobileConfirm;
$obj->exec();
