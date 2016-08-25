<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileMyorder extends Ad_greenlngModuleSite
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
        $status = intval($_GPC['status']) >= 0 ? intval($_GPC['status']) : 0;
        $ordid = intval($_GPC['ordid']);
        $act = $_GPC['act'] ? $_GPC['act'] : 'list';
        $acts = array('list', 'detail', 'cancel', 'delete');
        if (!in_array($act, $acts)) {
            message('非法请求！(' . $act . ')');
        }

        //订单列表
        if ($act == 'list') {
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 10;
            $start = ($pindex - 1) * $pagesize;
            $param = array();
            $param[':uid'] = $_W['member']['uid'];
            $param[':uniacid'] = $_W['uniacid'];
            $where = ' WHERE a.uid=:uid AND a.houseid=b.id AND a.uniacid=:uniacid';
            if (!isset($_GPC['status'])) {
                $_GPC['status'] = 0;
            }
            if ($status == 0) {
                $where .= ' AND a.status<=:status';
                $param[':status'] = $status;
            } else {
                $where .= ' AND a.status=:status';
                $param[':status'] = $status;
            }
            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_house_order') . " AS a," . tablename('adgreenlng_house') . " AS b {$where}";
            $total = pdo_fetchcolumn($sql, $param);
            if ($total > 0) {
                $sql = "SELECT a.*,b.id AS house_id,b.name AS house_name FROM " . tablename('adgreenlng_house_order') . " AS a," . tablename('adgreenlng_house') . " AS b {$where} ORDER BY a.ordid DESC LIMIT {$start},{$pagesize}";
                $list = pdo_fetchall($sql, $param);
                if ($list) {
                    foreach ($list as &$item) {
                        $item['dateline'] = $item['dateline'] ? date('Y-m-d h:i:s', $item['dateline']) : '';
                        $item['paytime'] = $item['paytime'] ? date('Y-m-d h:i:s', $item['paytime']) : '';
                    }
                    //print_r($list);
                }
                $pager = pagination($total, $pindex, $pagesize);
            }
        }

        //订单详情
        if ($act == 'detail') {
            if (empty($ordid)) {
                message('非法参数', $this->createMobileUrl('home'), 'error');
            }
            $order = ad_greenlng_order_fetch($ordid);
            if (empty($order)) {
                message('订单不存在或已删除', $this->createMobileUrl('myorder', array('status' => $status)), 'error');
            }
            $order['dateline'] = $order['dateline'] ? date('Y-m-d h:i:s', $order['dateline']) : '';
            $order['paytime'] = $order['paytime'] ? date('Y-m-d h:i:s', $order['paytime']) : '';
            $order['house'] = ad_greenlng_fetch($order['houseid']);
        }

        //取消订单
        if ($act == 'cancel') {
            if (empty($ordid)) {
                message('非法参数', $this->createMobileUrl('home'), 'error');
            }
            $order = ad_greenlng_order_fetch($ordid);
            if (empty($order)) {
                message('订单不存在或已删除', $this->createMobileUrl('myorder', array('status' => $status)), 'error');
            }
            if ($order['status'] >= 1) {
                message('订单状态无法取消', referer(), 'error');
            }
            $ret = pdo_update('adgreenlng_house_order', array('status' => '-1'), array('ordid' => $ordid));
            if ($ret !== false) {
                message('订单取消成功', referer(), 'success');
            } else {
                message('订单取消失败，请稍后重试', referer(), 'error');
            }
        }

        //删除订单
        if ($act == 'delete') {
            if (empty($ordid)) {
                message('非法参数', $this->createMobileUrl('home'), 'error');
            }
            $order = ad_greenlng_order_fetch($ordid);
            if (empty($order)) {
                message('订单不存在或已删除', $this->createMobileUrl('myorder', array('status' => $status)), 'error');
            }
            if ($order['status'] != -1) {
                message('订单无法删除', referer(), 'error');
            }
            $ret = pdo_delete('adgreenlng_house_order', array('ordid' => $ordid));
            if ($ret !== false) {
                message('订单删除成功', $this->createMobileUrl('myorder'), 'success');
            } else {
                message('订单删除失败，请稍后重试', referer(), 'error');
            }

        }
        include $this->template('myorder-' . $act);
    }
}

$obj = new Ad_greenlng_doMobileMyorder;
$obj->exec();
