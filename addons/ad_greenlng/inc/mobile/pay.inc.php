<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobilePay extends Ad_greenlngModuleSite
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
        $ordid = intval($_GPC['ordid']);
        if (empty($ordid)) {
            message('非法参数', $this->createMobileUrl('home'), 'error');
        }
        $order = ad_greenlng_order_fetch($ordid);
        if (empty($order)) {
            message('订单不存在或已删除', $this->createMobileUrl('home'), 'error');
        }
        if ($order['status'] != 0) {
            message('抱歉，您的订单已付款或已关闭', $this->createMobileUrl('myorder'), 'error');
        }
        $house = ad_greenlng_fetch($order['houseid']);
        $params['tid'] = $ordid;
        $params['user'] = $_W['fans']['from_user'];
        $params['fee'] = $order['amount'];
        $params['title'] = $house['name'] . '（订金）';
        $params['ordersn'] = $order['orderno'];
        $params['virtual'] = false;
        include $this->template('pay');
    }
}

$obj = new Ad_greenlng_doMobilePay;
$obj->exec();