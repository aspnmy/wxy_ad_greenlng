<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doWebOrder extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_GPC, $_W;
        $title = '订单管理';
        $eid = intval($_GPC['eid']);
        $do = !empty($_GPC['do']) ? $_GPC['do'] : 'display';
        if ($do == 'display') {
            $pagesize = 25;
            $pindex = max(1, intval($_GPC['page']));
            $start = ($pindex - 1) * $pagesize;

            $param = array();
            $param[':uniacid'] = $_W['uniacid'];
            $where = " WHERE a.uniacid=:uniacid AND a.uid=b.uid";
            $userfield = $_GPC['userfield'];
            if (is_numeric($userfield)) {
                $where .= " AND a.uid=:userfield";
                $param[':userfield'] = intval($userfield);
            } else {
                $where .= " AND b.nickname LIKE '%{$userfield}%'";
            }
            if (isset($_GPC['orderno']) && $_GPC['orderno'] != '') {
                $where .= " AND a.orderno=:orderno";
                $param[':orderno'] = $_GPC['orderno'];
            }
            if (isset($_GPC['status']) && $_GPC['status'] > -99) {
                $where .= " AND a.status=:status";
                $param[':status'] = $_GPC['status'];
            }
            if (isset($_GPC['paytype']) && $_GPC['paytype'] >= 0) {
                $where .= " AND a.paytype=:paytype";
                $param[':paytype'] = intval($_GPC['paytype']);
            }
            if (empty($starttime) || empty($endtime)) {
                $starttime = strtotime('-1 month');
                $endtime = time();
            }
            if (!empty($_GPC['time'])) {
                $starttime = strtotime($_GPC['time']['start']);
                $endtime = strtotime($_GPC['time']['end']) + 86399;
                $where .= " AND a.dateline>=:starttime AND a.dateline <= :endtime ";
                $param[':starttime'] = $starttime;
                $param[':endtime'] = $endtime;
            }
            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_house_order') . " AS a," . tablename('mc_members') . " AS b {$where}";
            //echo $sql;
            $total = pdo_fetchcolumn($sql, $param);
            if ($total > 0) {
                $sql = "SELECT a.*,b.nickname,b.avatar FROM " . tablename('adgreenlng_house_order') . " AS a," . tablename('mc_members') . " AS b";
                $sql .= " {$where} ORDER BY a.ordid DESC LIMIT {$start},{$pagesize}";
                $list = pdo_fetchall($sql, $param);
                if ($list) {
                    $houses = array();
                    $houseids = array();
                    foreach ($list as $v) {
                        $houseids[] = $v['houseid'];
                    }
                    $houseids = array_unique($houseids);
                    if ($houseids) {
                        $sql = "SELECT `id`, `name` FROM " . tablename('adgreenlng_house');
                        $sql .= " WHERE `id` IN (" . implode(',', $houseids) . ")";
                        $houses = pdo_fetchall($sql, array(), 'id');
                    }
                    foreach ($list as &$item) {
                        $item['dateline'] = $item['dateline'] ? date('Y-m-d h:i:s', $item['dateline']) : '';
                        $item['paytime'] = $item['paytime'] ? date('Y-m-d h:i:s', $item['paytime']) : '';
                        $item['housename'] = $houses[$item['houseid']]['name'];
                        unset($item);
                    }
                }
            }
            include $this->template('web/order');
            exit;
        } else if ($do == 'post') {
            $id = intval($_GPC['_id']);
            $item = array();
            $sql = "SELECT `id`, `name` FROM " . tablename('adgreenlng_house');
            $houses = pdo_fetchall($sql, array(), 'id');
            if ($id > 0) {
                $sql = "SELECT * FROM " . tablename('adgreenlng_house_order') . ' WHERE `ordid` = ' . $id;
                $item = pdo_fetch($sql);
                if (empty($item)) {
                    message('订单不存在或已删除', referer(), 'error');
                }
                $item['dateline'] = date('Y-m-d h:i:s', $item['dateline']);
                $item['paytime'] = $item['paytime'] ? date('Y-m-d h:i:s', $item['paytime']) : '';
                //$item['paytime'] = date('Y-m-d h:i:s', $item['paytime']);

                $sql = "SELECT `name` FROM " . tablename('adgreenlng_house') . ' WHERE `id` = ' . $item['houseid'];
                $item['housename'] = pdo_fetchcolumn($sql);
                $sql = "SELECT `uid` FROM " . tablename('adgreenlng_partner') . ' WHERE `level` = 1';
                $sql .= " AND `subuid` = " . $item['uid'];
                $parentuid = pdo_fetchcolumn($sql);
                $uids = array($item['uid']);
                if ($parentuid > 0) {
                    array_push($uids, $parentuid);
                }
                $members = mc_fetch($uids, array('avatar', 'nickname'));
                if (!empty($members)) {
                    $item['nickname'] = $members[$item['uid']]['nickname'];
                    $item['avatar'] = $members[$item['uid']]['avatar'];
                    /*$item['parentuid'] = $parentuid;
                    $item['parentnickname'] = $members[$parentuid]['nickname'];
                    $item['parentavatar'] = $members[$parentuid]['avatar'];*/
                }
            }
            if (checksubmit('submit')) {
                if (empty($_GPC['uid'])) {
                    message('订单用户不能为空，请重新输入！', referer(), 'error');
                }

                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'uid' => $_GPC['uid'],
                    'orderno' => $_GPC['orderno'],
                    'houseid' => $_GPC['houseid'],
                    'paytype' => $_GPC['paytype'],
                    'transid' => $_GPC['transid'],
                    'status' => $_GPC['status'],
                    'amount' => $_GPC['amount'],
                    'remark' => $_GPC['remark'],
                    'dateline' => time(),
                );
                if ($id > 0) {
                    unset($data['uniacid']);
                    unset($data['dateline']);
                    pdo_update('adgreenlng_house_order', $data, array('ordid' => $id));
                } else {
                    pdo_insert('adgreenlng_house_order', $data);
                }
                message('更新成功！', url('site/entry/display', array('eid' => $eid)), 'success');
            }
            include $this->template('web/order');
            exit;
        } else if ($do == 'delete') {
            $id = intval($_GPC['_id']);
            $sql = "SELECT * FROM " . tablename('adgreenlng_house_order') . ' WHERE `ordid` = ' . $id;
            $item = pdo_fetch($sql, array());
            if (empty($item)) {
                message('订单不存在或已删除', referer(), 'error');
            }
            pdo_delete('adgreenlng_house_order', array('ordid' => $id));
            message('删除成功', referer(), 'success');
            exit;
        } else if ($do == 'confirmcommission') {
            $id = intval($_GPC['_id']);
            $sql = "SELECT * FROM " . tablename('adgreenlng_house_order') . ' WHERE `ordid` = ' . $id;
            $item = pdo_fetch($sql, array());
            if (empty($item)) {
                message('订单不存在或已删除', referer(), 'error');
            }
            $sql = "SELECT * FROM " . tablename('adgreenlng_partner') . ' WHERE `subuid` = ' . $item['uid'];
            $partner = pdo_fetch($sql, array());
            if (empty($partner) || $partner['uid'] == 0) {
                message('该用户还不是经纪人或无上线，不需支付佣金！', referer(), 'error');
            }
            $sql = "SELECT * FROM " . tablename('adgreenlng_house') . ' WHERE `id` = ' . $item['houseid'];
            $house = pdo_fetch($sql, array());
            if (empty($house)) {
                message('查不到该订单对应的楼盘，无法支付佣金！', referer(), 'error');
            }
            $sql = "SELECT * FROM " . tablename('adgreenlng_commission_log') . ' WHERE `uid` = ' . $partner['uid'] . ' AND `ordid` = ' . $id;
            $comm_log = pdo_fetch($sql, array());
            if (!empty($comm_log)) {
                message('该订单已支付佣金！', referer(), 'error');
            }
            $data = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $partner['uid'],
                'fee' => $house['commission'],
                'status' => 0,
                'ordid' => $id,
                'createtime' => TIMESTAMP,
                'updatetime' => TIMESTAMP,
            );
            pdo_insert('adgreenlng_commission_log', $data);
            $sql = "SELECT * FROM " . tablename('adgreenlng_commission') . ' WHERE `uid` = ' . $partner['uid'];
            $comm = pdo_fetch($sql, array());
            if (empty($comm)) {
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'uid' => $partner['uid'],
                    'commission' => $house['commission'],
                    'commission_get_total' => 0,
                    'commission_unget_total' => $house['commission'],
                    'createtime' => TIMESTAMP,
                );
                pdo_insert('adgreenlng_commission', $data);
            } else {
                $data = array(
                    'commission' => $comm['commission'] + $house['commission'],
                    'commission_unget_total' => $comm['commission'] + $house['commission'],
                );
                pdo_update('adgreenlng_commission', $data, array('id' => $comm['id']));
            }
            message('更新经纪人佣金成功', referer(), 'success');
        }
    }
}

$obj = new Ad_greenlng_doWebOrder;
$obj->exec();
