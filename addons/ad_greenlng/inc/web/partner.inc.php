<?php
/**
 * 经纪人管理
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doWebPartner extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_GPC, $_W;
        load()->func('communication');
        $title = '经纪人管理';
        $eid = intval($_GPC['eid']);
        $do = !empty($_GPC['do']) ? $_GPC['do'] : 'display';
        $roles = adgreenlng_partner_role_fetchall();
        if ($do == 'display') {
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 20;
            $start = ($pindex - 1) * $pagesize;
            $where = " WHERE a.uniacid=:uniacid AND a.subuid>0 AND a.subuid=b.uid";
            $params = array(
                ':uniacid' => $_W['uniacid'],
            );

            if (isset($_GPC['nickname']) && $_GPC['nickname']) {
                $nickname = $_GPC['nickname'];
                if (is_numeric($_GPC['nickname'])) {
                    $where .= ' AND b.uid = ' . $_GPC['nickname'];
                } else {
                    $where .= " AND b.nickname LIKE '%" . $_GPC['nickname'] . "%'";
                }
            }
            if (isset($_GPC['realname']) && $_GPC['realname'] != '') {
                $realname = $_GPC['realname'];
                $where .= " AND a.realname LIKE '%" . $_GPC['realname'] . "%'";
            }
            if (isset($_GPC['roleid']) && $_GPC['roleid'] > -99) {
                $roleid = $_GPC['roleid'];
                $where .= ' AND a.roleid = ' . $_GPC['roleid'];
            }
            if (isset($_GPC['phone']) && $_GPC['phone'] != '') {
                $phone = $_GPC['phone'];
                $where .= " AND a.phone LIKE '%" . $_GPC['phone'] . "%'";
            }

            $roles = adgreenlng_partner_role_fetchall();
            //print_r($roles);

            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_partner') . " AS a," . tablename('mc_members') . " AS b {$where}";
            $total = pdo_fetchcolumn($sql, $params);
            if ($total > 0) {
                // $sql = "SELECT DISTINCT(a.subuid),a.id,a.createtime,b.nickname,b.avatar FROM ".tablename('adgreenlng_partner')." AS a,".tablename('mc_members')." AS b {$where} ORDER BY a.createtime DESC LIMIT {$start},{$pagesize}";
                $sql = "SELECT DISTINCT(a.subuid),a.*,b.nickname,b.avatar FROM " . tablename('adgreenlng_partner') . " AS a," . tablename('mc_members') . " AS b {$where} ORDER BY a.id DESC LIMIT {$start},{$pagesize}";
                $list = pdo_fetchall($sql, $params);
                if ($list) {
                    foreach ($list as &$item) {
                        //$item['friend_total'] = $this->get_partner_friend_total($item['id']);
                        $item['show_friendlist'] = false;
                        $role = $roles[$item['roleid']];
                        if ($role['isadmin']) {
                            $item['show_friendlist'] = true;
                        }
                        $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
                        unset($item);
                    }
                }
            }
            $pager = pagination($total, $pindex, $pagesize);
            $sql = 'SELECT * FROM ' . tablename('modules_bindings') . ' WHERE eid = :eid';
            $entry = pdo_fetch($sql, array(':eid' => $eid));
            if (!empty($entry)) {
                $sql_condition = array(
                    ':module' => $entry['module'],
                    ':entry' => $entry['entry'],
                    ':do' => 'customer',
                );
                $sql = 'SELECT * FROM ' . tablename('modules_bindings');
                $sql .= ' WHERE module = :module AND entry = :entry AND do = :do';
                $entry = pdo_fetch($sql, $sql_condition);
                if (!empty($entry)) {
                    $customer_eid = $entry['eid'];
                }
            }
        } else if ($do == 'friendlist') {
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 20;
            $start = ($pindex - 1) * $pagesize;
            $partnerid = $_GPC['partnerid'];
            $partneruid = $_GPC['partneruid'];
            if ($partnerid > 0 && $partneruid > 0) {
                $sql = 'SELECT COUNT(*) FROM ' . tablename('adgreenlng_partner_rel');
                $sql .= ' WHERE `partnerid` = ' . $partnerid . ' AND `uniacid` = ' . $_W['uniacid'];
                $total = pdo_fetchcolumn($sql);
                $partner = mc_fetch($partneruid, array('nickname', 'avatar'));
                $partner['id'] = $partnerid;
                if ($total > 0) {
                    $sql = 'SELECT a.* FROM ' . tablename('adgreenlng_partner') . " AS a," . tablename('adgreenlng_partner_rel') . " AS b";
                    $sql .= ' WHERE a.`id` = b.`subpartnerid` AND b.`partnerid` = ' . $partnerid;
                    $sql .= " LIMIT {$start},{$pagesize}";
                    $list = pdo_fetchall($sql, array(), 'subuid');
                    if ($list) {
                        $infos = mc_fetch(array_keys($list), array('nickname', 'avatar'));
                        foreach ($list as &$item) {
                            $item['avatar'] = tomedia($infos[$item['subuid']]['avatar']);
                            $item['nickname'] = $infos[$item['subuid']]['nickname'];
                            $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
                            unset($item);
                        }
                    }
                }
                $pager = pagination($total, $pindex, $pagesize);
            }
        } else if ($do == 'role') {
            $title = '身份类型';
            $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner_role');
            $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'] . ' ORDER BY displayorder ASC';
            $list = pdo_fetchall($sql, array(), 'id');
            if (!$list) {
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner_role') . ' WHERE uniacid=0';
                $datalist = pdo_fetchall($sql);
                if ($datalist) {
                    foreach ($datalist as $v) {
                        $data = array(
                            'uniacid' => $_W['uniacid'],
                            'title' => $v['title'],
                            'isshow' => $v['isshow'],
                            'isadmin' => $v['isadmin'],
                            'issubadmin' => $v['issubadmin'],
                            'displayorder' => $v['displayorder'],
                        );
                        pdo_insert('adgreenlng_partner_role', $data);
                        $data['id'] = pdo_insertid();
                        $list[$data['id']] = $data;
                    }
                }
            }
            // die(json_encode($_W));
            if (isset($_GPC['ret']) && in_array($_GPC['ret'], array(0, 1))
                && isset($_GPC['dat'])
            ) {
                pdo_update('adgreenlng_partner_role', array('isshow' => $_GPC['ret']), array('id' => $_GPC['dat']));
                echo 'success';
                exit;
            }
            if (checksubmit('submit')) {
                foreach ($_GPC['id'] as $k => $v) {
                    $data = array(
                        'displayorder' => $_GPC['displayorder'][$k],
                        'title' => $_GPC['title'][$k],
                        'isadmin' => (isset($_GPC['isadmin']) && in_array($v, $_GPC['isadmin'])) ? 1 : 0,
                        'issubadmin' => (isset($_GPC['issubadmin']) && in_array($v, $_GPC['issubadmin'])) ? 1 : 0,
                    );
                    pdo_update('adgreenlng_partner_role', $data, array('id' => $v));
                }
                message('更新成功！', referer(), 'success');
            }
        } else if ($do == 'detail') {
            $title = '经纪人信息';
            $partnerid = $_GPC['id'];
            if ($partnerid > 0) {
                $sql = 'SELECT a.*,b.nickname,b.avatar FROM ' . tablename('adgreenlng_partner') . ' AS a,' . tablename('mc_members') . ' AS b';
                $sql .= ' WHERE a.id = ' . $partnerid;
                $sql .= ' AND a.subuid = b.uid';
                $item = pdo_fetch($sql);
                if ($item) {
                    $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
                }
                $sql = 'SELECT `title` FROM ' . tablename('adgreenlng_partner_role');
                $sql .= ' WHERE `id` = ' . $item['roleid'];
                $roletitle = pdo_fetchcolumn($sql);
                $item['roletitle'] = $roletitle;
                $item['friend_total'] = $this->get_partner_friend_total($partnerid);
                $item['commission_total'] = adgreenlng_new_commission_total($item['subuid']);
            }

            if (checksubmit('submit')) {
                $data = array(
                    'realname' => $_GPC['realname'],
                    'phone' => $_GPC['phone'],
                    'roleid' => $_GPC['roleid'],
                );
                $subuid = $_GPC['subuid'];
                pdo_update('adgreenlng_partner', $data, array('subuid' => $subuid));
                message('更新成功！', referer(), 'success');
            }

        } else if ($do == 'delete') {
            $id = intval($_GPC['_id']);
            $sql = "SELECT * FROM " . tablename('adgreenlng_partner') . ' WHERE `id` = ' . $id;
            $item = pdo_fetch($sql);
            if (empty($item)) {
                message('经纪人不存在或已删除', referer(), 'error');
            }
            pdo_delete('adgreenlng_partner', array('id' => $id));
            pdo_delete('adgreenlng_partner_rel', array('partnerid' => $id));
            pdo_delete('adgreenlng_partner_rel', array('subpartnerid' => $id));
            pdo_update('adgreenlng_customer', array('partnerid' => 0), array('partnerid' => $id));
            pdo_update('adgreenlng_customer', array('recommendpid' => 0), array('recommendpid' => $id));
            message('操作成功', referer(), 'success');
        } else if ($do == 'displaycommission') {
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 20;
            $start = ($pindex - 1) * $pagesize;

            $condition = ' WHERE uniacid=:uniacid';
            $params = array(
                ':uniacid' => $_W['uniacid'],
            );
            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_new_commission') . $condition;
            $total = pdo_fetchcolumn($sql, $params);
            if ($total > 0) {
                $sql = "SELECT * FROM " . tablename('adgreenlng_new_commission') . $condition;
                $sql .= " ORDER BY id DESC LIMIT $start,$pagesize";
                $list = pdo_fetchall($sql, $params);
                if ($list) {
                    foreach ($list as &$item) {
                        $item['partner'] = adgreenlng_partner_fetch_by_uid($item['uid']);
                        $item['createtime'] = $item['createtime'] ? date('Y-m-d h:i:s', $item['createtime']) : '';
                        $item['updatetime'] = $item['updatetime'] ? date('Y-m-d h:i:s', $item['updatetime']) : '';
                        if ($item['tidtype'] == 'customerid') {
                            $customer = adgreenlng_customer_fetch_by_id($item['tid']);
                            $house = ad_greenlng_fetch($customer['houseid'], array('name'));
                            $item['house_name'] = $house['name'];
                        }
                        if ($item['tidtype'] == 'ordid') {
                            $order = ad_greenlng_order_fetch($item['tid']);
                            $item['orderno'] = $order['orderno'];
                        }
                        unset($item);
                    }
                    $pager = pagination($total, $pindex, $pagesize);
                }
            }
        } else if ($do == 'putcommission') {
            require IA_ROOT . '/addons/ad_greenlng/WxpayAPI.class.php';
            $setting = uni_setting($_W['uniacid'], array('payment'));
            $pay = $setting['payment'];
            if (empty($pay)) {
                message('请设置开启微信支付', url('profile/payment'), 'error');
            }
            if (empty($this->module['config']['getcash']['wxpay']['mch_appid'])) {
                message('公众号APPID参数未设置', url('profile/module/setting', array('m' => 'ad_greenlng')), 'error');
            }
            if (empty($this->module['config']['getcash']['wxpay']['mchid'])) {
                message('商户号参数未设置', url('profile/module/setting', array('m' => 'ad_greenlng')), 'error');
            }
            if (empty($this->module['config']['getcash']['wxpay']['apiclient_cert'])) {
                message('微信支付证书未设置', url('profile/module/setting', array('m' => 'ad_greenlng')), 'error');
            }
            if (empty($this->module['config']['getcash']['wxpay']['apiclient_key'])) {
                message('微信支付证书密钥未设置', url('profile/module/setting', array('m' => 'ad_greenlng')), 'error');
            }
            if (empty($this->module['config']['getcash']['wxpay']['rootca'])) {
                message('微信支付CA证书', url('profile/module/setting', array('m' => 'ad_greenlng')), 'error');
            }

            $id = intval($_GPC['id']);
            if (empty($id)) {
                message('数据不存在或已删除', referer(), 'error');
            }
            $sql = "SELECT * FROM " . tablename('adgreenlng_new_commission') . " WHERE id=:id";
            $param = array(
                ':id' => $id,
            );
            $item = pdo_fetch($sql, $param);
            if (empty($item)) {
                message('数据不存在或已删除', referer(), 'error');
            }
            if ($item['status'] == 1) {
                message('该提现申请已提现，如需重新提现，请先修改提现状态', referer(), 'error');
            }
            if ($item['status'] == -1) {
                message('该提现申请已被取消，如需重新提现，请先修改提现状态', referer(), 'error');
            }
            $order_no = date('Ymd') . random(6, 1);
            if (!$_W['acid']) { //后台可能存在该参数为空
                $accounts = uni_accounts();
                foreach ($accounts as $k => $v) {
                    $_W['account'] = $v;
                    $_W['acid'] = $_W['account']['acid'];
                    break;
                }
            }
            if (!$_W['uniacid']) { //后台可能存在该参数为空
                $_W['uniacid'] = $item['uniacid'];
            }
            $fans = mc_fansinfo($item['uid']);
            if (!$fans) {
                message('粉丝不存在或已删除', referer(), 'error');
            }
            if (!$fans['follow']) {
                message('粉丝已取消关注，无法操作提现', referer(), 'error');
            }
            $openid = $fans['openid'];
            $check_name = 'NO_CHECK';
            $param = array(
                'mch_appid' => $this->module['config']['getcash']['wxpay']['mch_appid'],
                'mchid' => $this->module['config']['getcash']['wxpay']['mchid'],
                'nonce_str' => random(32),
                'partner_trade_no' => $order_no,
                'openid' => $openid,
                'check_name' => $check_name,
                're_user_name' => '',
                'amount' => $item['money'],
                'desc' => "【{$_W['account']['name']}】提现" . date('Y-m-d'),
                'spbill_create_ip' => CLIENT_IP,
            );
            $extra = array(
                'sign_key' => $pay['wechat']['signkey'],
                'apiclient_cert' => IA_ROOT . DIRECTORY_SEPARATOR . $_W['config']['upload']['attachdir'] . DIRECTORY_SEPARATOR . $this->module['config']['getcash']['wxpay']['apiclient_cert'],
                'apiclient_key' => IA_ROOT . DIRECTORY_SEPARATOR . $_W['config']['upload']['attachdir'] . DIRECTORY_SEPARATOR . $this->module['config']['getcash']['wxpay']['apiclient_key'],
                'rootca' => IA_ROOT . DIRECTORY_SEPARATOR . $_W['config']['upload']['attachdir'] . DIRECTORY_SEPARATOR . $this->module['config']['getcash']['wxpay']['rootca'],
            );
            $ret = WxpayAPI::pay($param, $extra);
            $condition = array(
                'id' => $item['id'],
            );
            WeUtility::logging('trace', 'wxpay result=' . var_export($ret, true));
            if (is_array($ret) && isset($ret['success'])) {
                $data = array(
                    'user_id' => $_W['user']['uid'],
                    'status' => 1,
                    'order_no' => $order_no,
                    'payment_no' => $ret['payment_no'],
                    'reason' => 'ok',
                    'updatetime' => strtotime($ret['payment_time']),
                );
                $result = pdo_update('adgreenlng_new_commission', $data, $condition);
                if ($result !== false) {
                    message('提现成功', referer(), 'success');
                } else {
                    message('数据库更新失败，请记录以下重要数据，以便将来查询问题<hr>' . var_export($data, true), '', 'error');
                }
            } else {
                $data = array(
                    'user_id' => $_W['user']['uid'],
                    'status' => -2,
                    'order_no' => $order_no,
                    'reason' => $ret,
                    'updatetime' => TIMESTAMP,
                );
                $result = pdo_update('adgreenlng_new_commission', $data, $condition);
                if ($result !== false) {
                    message($ret, referer(), 'error');
                } else {
                    message('数据库更新失败，提现失败原因如下<hr>' . var_export($data, true), '', 'error');
                }
            }
        } else if ($do == 'editcommission') {
            $id = intval($_GPC['id']);
            if (empty($id)) {
                message('数据不存在或已删除', referer(), 'error');
            }
            $sql = "SELECT a.*,b.nickname,b.avatar FROM " . tablename('adgreenlng_new_commission') . " AS a," . tablename('mc_members') . " AS b WHERE a.id=:id AND a.uid=b.uid";
            $param = array(
                ':id' => $id,
            );
            $item = pdo_fetch($sql, $param);
            if (empty($item)) {
                message('数据不存在或已删除', referer(), 'error');
            }
            $item['partner'] = adgreenlng_partner_fetch_by_uid($item['uid']);
            if ($item['tidtype'] == 'customerid') {
                $item['customer'] = adgreenlng_customer_fetch_by_id($item['tid']);
            }
            if ($item['tidtype'] == 'ordid') {
                $item['order'] = ad_greenlng_order_fetch($item['tid']);
            }
            $item['user'] = adgreenlng_user_fetch_by_uid($item['user_id']);
            if (checksubmit('submit')) {
                $data = array(
                    'user_id' => $_W['user']['uid'],
                    'money' => $_GPC['money'],
                    'remark' => $_GPC['remark'],
                    'status' => $_GPC['status'],
                    'reason' => $_GPC['reason'],
                    'message' => $_GPC['message'],
                    'updatetime' => TIMESTAMP,
                );
                $condition = array(
                    'id' => $id,
                );
                pdo_update('adgreenlng_new_commission', $data, $condition);
                message('更新成功', wurl('site/entry/displaycommission', array('eid' => $eid)), 'success');
            }
        } else if ($do == 'displaypaylog') {
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 20;
            $start = ($pindex - 1) * $pagesize;
            $condition = ' WHERE `uniacid` = ' . $_W['uniacid'];
            $sql = "SELECT * FROM " . tablename('adgreenlng_cash_apply');
            $sql .= $condition . " ORDER BY `createtime` DESC LIMIT $start, $pagesize";
            $list = pdo_fetchall($sql, array(), 'id');
            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_cash_apply') . $condition;
            $total = pdo_fetchcolumn($sql);
            $pager = pagination($total, $pindex, $pagesize);

            $uids = array();
            foreach ($list as $item) {
                array_push($uids, $item['uid']);
            }
            $members = array();
            if (!empty($uids)) {
                $members = mc_fetch($uids, array('avatar', 'nickname'));
            }
            foreach ($list as &$item) {
                $item['createtime'] = !empty($item['createtime']) ? date('Y-m-d h:i:s', $item['createtime']) : '';
                $item['updatetime'] = !empty($item['updatetime']) ? date('Y-m-d h:i:s', $item['updatetime']) : '';
                if (!empty($members)) {
                    $item['nickname'] = $members[$item['uid']]['nickname'];
                    $item['avatar'] = $members[$item['uid']]['avatar'];
                }
                unset($item);
            }
        } else if ($do == 'deletecommlog') {
            $id = intval($_GPC['_id']);
            $sql = "SELECT * FROM " . tablename('adgreenlng_new_commission') . ' WHERE `id` = ' . $id;
            $item = pdo_fetch($sql, array());
            if (empty($item)) {
                message('数据不存在或已删除', referer(), 'error');
            }
            pdo_delete('adgreenlng_new_commission', array('id' => $id));
            message('删除成功！', referer(), 'success');
        } else if ($do == 'setstatus') {
            $partnerid = intval($_GPC['partnerid']);
            $status = intval($_GPC['status']);
            if ($partnerid > 0 && $status > 0) {
                $data = array('status' => $status);
                pdo_update('adgreenlng_partner', $data, array('id' => $partnerid));
                message('设置成功！', referer(), 'success');
            }
        }
        include $this->template('web/partner');
    }

    private function get_partner_friend_total($partnerid)
    {
        // $sql = "SELECT COUNT(*) FROM ".tablename('adgreenlng_partner')." WHERE uid=:uid AND subuid>0";
        global $_W;
        $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_partner_rel');
        $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'];
        $sql .= ' AND `partnerid` = ' . $partnerid;
        return pdo_fetchcolumn($sql);
    }
}

$obj = new Ad_greenlng_doWebPartner;
$obj->exec();
