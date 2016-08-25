<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileCommissionlog extends Ad_greenlngModuleSite
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
        if (!isset($_GPC['daterange'])) {
            $_GPC['daterange'] = array(
                'start' => date('Y-m-d', time() - 60 * 60 * 24 * 30),
                'end' => date('Y-m-d'),
            );
        }
        $starttime = strtotime($_GPC['daterange']['start']);
        $endtime = strtotime($_GPC['daterange']['end']) + 86399;
        $pindex = max(1, intval($_GPC['page']));
        $pagesize = 10;
        $start = ($pindex - 1) * $pagesize;
        $condition = ' WHERE uid=:uid AND createtime>=:starttime AND createtime<=:endtime';
        $params = array(
            ':uid' => $_W['member']['uid'],
            ':starttime' => $starttime,
            ':endtime' => $endtime,
        );
        $customerid = intval($_GPC['customerid']);
        if ($customerid > 0) {
            $condition .= ' AND tid=:tid AND tidtype=:tidtype';
            $params[':tid'] = $customerid;
            $params[':tidtype'] = 'customerid';
        }
        if (isset($_GPC['status'])) {
            $condition .= ' AND status=:status';
            $params[':status'] = $_GPC['status'];
        }
        $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_new_commission') . $condition;
        //echo $sql; print_r($params);
        $total = pdo_fetchcolumn($sql, $params);
        if ($total > 0) {
            $sql = "SELECT * FROM " . tablename('adgreenlng_new_commission') . $condition;
            $sql .= " ORDER BY id DESC LIMIT $start,$pagesize";
            $list = pdo_fetchall($sql, $params);
            if ($list) {
                foreach ($list as &$item) {
                    if ($item['tidtype'] == 'customerid') {
                        $customer = adgreenlng_customer_fetch_by_id($item['tid']);
                        $item['customer_realname'] = $customer['realname'];
                        $house = ad_greenlng_fetch($customer['houseid'], array('name'));
                        $item['house_name'] = $house['name'];
                    }
                    if ($item['tidtype'] == 'ordid') {
                        $order = ad_greenlng_order_fetch($item['tid']);
                        $item['orderno'] = $order['orderno'] ? '***' . substr($order['orderno'], -4) : '';
                    }
                    unset($item);
                }
                $pager = pagination($total, $pindex, $pagesize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
            }
        }
        $member = mc_fetch($_W['member']['uid'], array('avatar', 'nickname'));
        $params = array(
            ':uid' => $_W['member']['uid'],
        );
        $commission_total = array(
            'yes' => pdo_fetchcolumn("SELECT SUM(money) FROM " . tablename('adgreenlng_new_commission') . " WHERE uid=:uid AND status=1", $params),
            'no' => pdo_fetchcolumn("SELECT SUM(money) FROM " . tablename('adgreenlng_new_commission') . " WHERE uid=:uid AND status=0", $params),
        );
        $commission_total['yes'] = $commission_total['yes'] > 0 ? $commission_total['yes'] : '0.00';
        $commission_total['no'] = $commission_total['no'] > 0 ? $commission_total['no'] : '0.00';
        include $this->template('commissionlog');
    }
}

$obj = new Ad_greenlng_doMobileCommissionlog;
$obj->exec();
