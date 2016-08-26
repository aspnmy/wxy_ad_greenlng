<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobilePartner extends Ad_greenlngModuleSite
{
    private $act;
    private $uid = 0;
    private $partner = array();
    private $from_member = array();

    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_W, $_GPC, $do;
        $_share = $this->_share;
        $this->act = in_array($_GPC['act'], array(
            'display', 'recommendcustomer', 'myteam', 'mycustomer',
            'distribution', 'explain', 'regist', 'setting',
            'stat', 'customer-list',
        )) ? $_GPC['act'] : 'display';
        $method = "do_" . $this->act;
        if (!method_exists($this, $method)) {
            message('非法请求', referer(), 'error');
        }

        //初始化uid
        $this->uid = $_W['member']['uid'];

        //初始化经纪人数据
        $this->init_partner();

        //初始化分享人
        $fromid = isset($_GPC['fromid']) ? intval($_GPC['fromid']) : $_GPC['__fromid'];
        if ($fromid > 0 && $fromid != $this->uid) {
            $this->from_member = mc_fetch($fromid, array('uid', 'avatar', 'nickname'));
            if ($this->from_member) {
                $this->from_member['partner'] = adgreenlng_partner_fetch_by_uid($fromid);
            }
        }

        //do_xxx
        $this->$method();
    }

    private function init_partner()
    {
        global $_W;
        if ($this->uid) {
            $this->partner = adgreenlng_partner_fetch_by_uid($this->uid);
            if ($this->partner) {
                $this->partner['yes'] = true;
                //获取经纪人角色
                if ($this->partner['roleid']) {
                    $this->partner['role'] = adgreenlng_partner_role_fetch_by_id($this->partner['roleid']);
                } else {
                    $this->partner['yes'] = false; //没有身份角色时，不是合法经纪人
                }
                if ($this->partner['yes']) {
                    //获取经纪人管理的楼盘
                    $this->partner['house'] = adgreenlng_partner_house_fetch_by_id($this->partner['id']);
                    //判断当前经纪人是否是业务员
                    $this->partner['is_subpartner'] = false;
                    $row = adgreenlng_partner_rel_fetch_by_subpartnerid($this->partner['id']);
                    if ($row && $this->partner['role']['issubadmin']) {
                        $this->partner['is_subpartner'] = true;
                        $this->partner['parent_partner'] = array(   //记录属于哪个项目经理
                            'id' => $row['id'],
                            'partnerid' => $row['partnerid'],
                            'createtime' => $row['createtime'],
                        );
                    }
                }
            }
        } else {
            $this->partner = array(
                'yes' => false,
                'role' => array(),
                'house' => array(),
                'is_subpartner' => false,
                'parent_partner' => array(),
            );
        }
        $creditnames = pdo_fetchcolumn("SELECT creditnames FROM " . tablename('uni_settings') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
        $creditnames = iunserializer($creditnames);
        $this->partner['credit']['type'] = $this->module['config']['credit']['type'] ? $this->module['config']['credit']['type'] : 'credit1';
        $this->partner['credit']['title'] = $creditnames[$this->partner['credit']['type']]['title'];
        $this->partner['commission']['nopay'] = 0;
        //print_r($_W);
        if ($this->uid) {
            $row = mc_credit_fetch($this->uid);
            $this->partner['credit']['total'] = adgreenlng_format_price($row[$this->partner['credit']['type']]);
            $value = pdo_fetchcolumn("SELECT SUM(money) FROM " . tablename('adgreenlng_new_commission') . " WHERE uid=:uid AND status=0", array(':uid' => $this->uid));
            $this->partner['commission']['nopay'] = $value > 0 ? $value : 0;
        }

        //print_r($this->partner);
    }

    private function do_display()
    {
        global $_W, $_GPC, $do;
        $title = '经纪人';
        $slides = $this->module['config']['partner']['slide'];
        $house_list = $this->get_recommend_house();
        include $this->template('partner');
    }

    /**
     * @return array
     */
    private function get_recommend_house()
    {
        global $_W, $_GPC;
        $filter = array(
            'uniacid' => $_W['uniacid'],
            'recommend' => 1,
        );
        $list = ad_greenlng_fetchall($filter, '', 0, -1);
        if ($list) {
            foreach ($list as &$item) {
                ad_greenlng_set($item);
            }
        }
        //print_r($list);
        return $list;
    }

    private function do_recommendcustomer()
    {
        global $_W, $_GPC, $do;
        $title = '经纪人';
        $this->checkauth();
        $this->check_partner_status();
        $houseid = intval($_GPC['houseid']);
        $house_list = $this->get_recommend_house();
        if (checksubmit('submit')) {
            $enterprisename = trim($_GPC['enterprisename']);//客户企业名
            $realname = trim($_GPC['realname']);
            $mobile = trim($_GPC['mobile']);
            $remark = $_GPC['remark'];
            if ($enterprisename == '') {
                message('请输入推荐客户企业名！', referer(), 'warning');
            }
            if ($realname == '') {
                message('请输入推荐客户姓名！', referer(), 'warning');
            }
            if ($mobile == '') {
                message('请输入推荐客户电话！', referer(), 'warning');
            }
            if ($houseid < 1) {
                message('请选择推荐报价！', referer(), 'warning');
            }
            if (($this->partner['role']['isadmin'] || $this->partner['is_subpartner']) && $this->module['config']['base']['repeat_mobile']) {
            } else {
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_customer');
                $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'];
                $sql .= ' AND `enterprisename` = ' . $enterprisename;
                $customer = pdo_fetch($sql);
                if ($customer) {
                    message('该客户企业名已经被登记，请勿重复推荐！', referer(), 'error');
                }
                $sql = 'SELECT `id` FROM ' . tablename('adgreenlng_customer_status');
                $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'];
                $sql .= ' ORDER BY `displayorder` ASC';
                $sql .= ' LIMIT 1';
                $statusid = pdo_fetchcolumn($sql);
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'partnerid' => 0,
                    'realname' => $realname,
                    'mobile' => $mobile,
                    'enterprisename' => $enterprisename,//企业名称
                    'houseid' => $houseid,
                    'remark' => $remark,
                    'dateline' => TIMESTAMP,
                    'recommendpid' => $this->partner['id'],//推荐人ID:经纪人ID
                    'laststatusid' => $statusid,
                );
                pdo_insert('adgreenlng_customer', $data);
                $id = pdo_insertid();
                if ($id > 0) {
                    $data = array(
                        'uniacid' => $_W['uniacid'],
                        'customerid' => $id,
                        'statusid' => $statusid,
                        'partnerid' => $this->partner['id'],//推荐人ID:经纪人ID
                        'remark' => $remark,
                        'dateline' => TIMESTAMP,
                    );
                    pdo_insert('adgreenlng_customer_trace', $data);

                    // 提交成功以后给项目经理发消息
                    $sql = 'SELECT `id` FROM ' . tablename('adgreenlng_partner_role') . " WHERE `title` = '项目经理'";
                    $sql .= ' AND `uniacid` = :uniacid';
                    $params = array(
                        ':uniacid' => $_W['uniacid'],
                    );
                    $manager_id = pdo_fetch($sql, $params);
                    if ($manager_id > 0) {
                        $house = array();
                        foreach ($house_list as $v) {
                            if ($v['id'] == $houseid) {
                                $house = $v;
                                break;
                            }
                        }

                        $sql = 'SELECT `p`.* FROM ' . tablename('adgreenlng_partner') . ' AS `p`, ' . tablename('adgreenlng_partner_house_ref') . ' AS `phr` ';
                        $sql .= ' WHERE `p`.`uniacid` = :uniacid AND `p`.`roleid` = ' . $manager_id['id'] . ' AND `p`.`id` = `phr`.`partnerid`';
                        $sql .= ' AND `p`.`status` = 1 AND `phr`.`houseid` = :houseid';
                        $params = array(
                            ':uniacid' => $_W['uniacid'],
                            ':houseid' => $houseid,
                        );
                        $managers = pdo_fetchall($sql, $params);
                        if (!empty($managers) && !empty($house)) {
                            $housename = $house['name'];
                            $url = $_W['siteroot'] . 'app/' . $this->createMobileUrl('partner', array('act' => 'mycustomer', 'realname' => $realname)); //我的客户链接
                            $recommendtime = date('Y-m-d H:i', TIMESTAMP);
                            foreach ($managers as $recommend_partner) {
                                if ($recommend_partner) {
                                    //获取openid
                                    $fans = mc_fansinfo($recommend_partner['subuid']);
                                    $recommend_fansid = $fans['fanid'];
                                    $receivername = $recommend_partner['realname'];
                                    if ($_W['account']['level'] == 4) { //服务号（已认证）
                                        if ($this->module['config']['partner']['recommend_customer']['template_id']
                                            && $this->module['config']['partner']['recommend_customer']['template_content']
                                            && $this->module['config']['partner']['recommend_customer']['template_variable']
                                        ) {
                                            $vars = array(
                                                '{housename}' => $housename,
                                                '{recommendname}' => $this->partner['realname'],
                                                '{customername}' => $realname,
                                                '{recommendtime}' => $recommendtime,
                                            );
                                            $message_info = array(
                                                'template_id' => $this->module['config']['partner']['recommend_customer']['template_id'],
                                                'template_content' => $this->module['config']['partner']['recommend_customer']['template_content'],
                                                'template_variable' => $this->module['config']['partner']['recommend_customer']['template_variable'],
                                                'uniacid' => $customer['uniacid'],
                                                'receiver_uid' => $recommend_partner['subuid'],
                                                'url' => $url,
                                                'vars' => $vars,
                                                'openid' => $fans['openid'],
                                            );
                                            $this->sendTemplateMessage($message_info);
                                        } else {
                                            //没有配置模板消息发送客服消息
                                            $this->sendRecommendNotice($fans['openid'], $url, $housename, $this->partner['realname'], $realname, $recommendtime);
                                        }
                                    } else {
                                        if ($_W['account']['level'] == 3) { //订阅号（已认证）
                                            $this->sendRecommendNotice($fans['openid'], $url, $housename, $this->partner['realname'], $realname, $recommendtime);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    message('提交成功！', $this->createMobileUrl('partner', array('act' => 'display')), 'success');
                } else {
                    message('提交失败，请联系系统管理员处理！', referer(), 'warning');
                }
            }
            include $this->template('partner-recommendcustomer');
        }

        private
        function do_myteam()
        {
            global $_W, $_GPC, $do;
            $title = '经纪人';
            $this->checkauth();
            $this->check_partner_status();
            $op = trim($_GPC['op']) ? trim($_GPC['op']) : 'list';
            if (!$this->partner['role']['isadmin']) {
                message('您没有权限操作！', referer(), 'warning');
            }
            if ($op == 'list') {
                $pindex = max(1, intval($_GPC['page']));
                $pagesize = 5;
                $start = ($pindex - 1) * $pagesize;
                $condition = ' WHERE a.uniacid=:uniacid AND a.partnerid=:partnerid AND a.subpartnerid=b.id';
                $params = array(
                    ':uniacid' => $_W['uniacid'],
                    ':partnerid' => $this->partner['id'],
                );
                $sql = 'SELECT COUNT(*) FROM ' . tablename('adgreenlng_partner_rel') . ' AS a,' . tablename('adgreenlng_partner') . ' AS b' . $condition;
                $total = pdo_fetchcolumn($sql, $params);
                if ($total > 0) {
                    $sql = 'SELECT a.createtime,b.id,b.realname,b.phone,b.status FROM ';
                    $sql .= tablename('adgreenlng_partner_rel') . ' AS a,' . tablename('adgreenlng_partner');
                    $sql .= ' AS b' . $condition . " LIMIT $start,$pagesize";
                    $list = pdo_fetchall($sql, $params, 'id');
                    $pager = pagination($total, $pindex, $pagesize);
                }
            } elseif ($op == 'stat') {
                /*业绩统计*/
                $partnerid = trim($_GPC['partnerid']);
                if (!empty($partnerid)) {
                    $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner');
                    $sql .= ' WHERE `id` = ' . $partnerid;
                    $partner = pdo_fetch($sql);
                    if (!empty($partner)) {
                        $partnername = $partner['realname'];
                        $sqlcondition = ' WHERE 1';
                        $starttime = trim($_GPC['daterange']['start']);
                        if ($starttime) {
                            $sqlcondition .= ' AND `dateline` >= ' . strtotime($starttime);
                        }
                        $endtime = trim($_GPC['daterange']['end']);
                        if ($endtime) {
                            $endtime = strtotime($endtime) + 86400;
                            $sqlcondition .= ' AND `dateline` < ' . $endtime;
                        }

                        $sql = 'SELECT `id`, `title` FROM ' . tablename('adgreenlng_customer_status');
                        $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'];
                        $sql .= ' ORDER BY `displayorder` ASC';
                        $status = pdo_fetchall($sql, array(), 'id');

                        $sql = 'SELECT COUNT(1) AS `count`, `laststatusid` FROM ' . tablename('adgreenlng_customer');
                        $sql .= $sqlcondition;
                        $sql .= ' AND `partnerid` = ' . $partner['id'];
                        $sql .= ' GROUP BY `laststatusid`';
                        $stat = pdo_fetchall($sql, array(), 'laststatusid');
                        foreach ($status as $k => &$v) {
                            $v['count'] = ($stat[$k]['count'] > 0) ? ($stat[$k]['count'] > 0) : 0;
                            unset($v);
                        }
                    }
                }
            } elseif ($op == 'customer-list') {
                $pindex = max(1, intval($_GPC['page']));
                $pagesize = 5;
                $start = ($pindex - 1) * $pagesize;
                $partnerid = intval($_GPC['partnerid']);
                $partnername = trim($_GPC['partnername']);
                $statusid = intval($_GPC['statusid']);
                $statustitle = trim($_GPC['statustitle']);
                if ($partnerid > 0) {
                    $list = array();
                    $params = array();
                    $condition = " WHERE a.partnerid = :partnerid";
                    $params[':partnerid'] = $partnerid;
                    $condition .= ' AND a.houseid = b.id';

                    if ($statusid > 0) {
                        $condition .= " AND a.laststatusid=:laststatusid";
                        $params[':laststatusid'] = $statusid;
                    }

                    $starttime = trim($_GPC['daterange']['start']);
                    if ($starttime) {
                        $condition .= ' AND `a`.`dateline` >= ' . strtotime($starttime);
                    }
                    $endtime = trim($_GPC['daterange']['end']);
                    if ($endtime) {
                        $endtime = strtotime($endtime) + 86400;
                        $condition .= ' AND `a`.`dateline` < ' . $endtime;
                    }

                    $sql = "SELECT COUNT(1) FROM " . tablename('adgreenlng_customer') . " AS a," . tablename('adgreenlng_house') . " AS b " . $condition;
                    $total = pdo_fetchcolumn($sql, $params);
                    if ($total > 0) {
                        $sql = "SELECT a.*,b.name AS house_name FROM " . tablename('adgreenlng_customer') . " AS a," . tablename('adgreenlng_house') . " AS b " . $condition . " ORDER BY a.id DESC LIMIT $start,$pagesize";
                        $list = pdo_fetchall($sql, $params);
                        if ($list) {
                            foreach ($list as &$item) {
                                // 增加推荐信息
                                $item['recommendtime'] = date('Y-m-d H:i:s', $item['dateline']);
                                $sql = 'SELECT `realname` FROM ' . tablename('adgreenlng_partner');
                                $sql .= ' WHERE `id` = ' . $item['recommendpid'];
                                $recommender = pdo_fetch($sql);
                                if (!empty($recommender)) {
                                    $item['recommender'] = $recommender['realname'];
                                }
                                unset($item);
                            }
                        }
                        $pager = pagination($total, $pindex, $pagesize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
                    }
                }
            } elseif ($op == 'setstatus') {
                $partnerid = intval($_GPC['partnerid']);
                $status = intval($_GPC['status']);
                if ($partnerid > 0 && $status > 0) {
                    $data = array('status' => $status);
                    pdo_update('adgreenlng_partner', $data, array('id' => $partnerid));
                }
                message('设置状态成功！', referer(), 'success');
            } elseif ($op == 'delete') {
                $partnerid = intval($_GPC['partnerid']);
                if (!$partnerid) {
                    message('非法参数！', referer(), 'error');
                }
                //是否是项目经理
                if (!$this->partner['role']['isadmin']) {
                    message('您没有权限操作！', referer(), 'warning');
                }
                //上级项目经理是否一致
                $row = adgreenlng_partner_rel_fetch_by_subpartnerid($partnerid);
                if ($row['partnerid'] != $this->partner['id']) {
                    message('您没有权限操作！', referer(), 'warning');
                }
                $condition = array(
                    'partnerid' => $this->partner['id'],
                    'subpartnerid' => $partnerid,
                );
                pdo_delete('adgreenlng_partner_rel', $condition);
                pdo_delete('adgreenlng_partner', array('id' => $partnerid));
                pdo_update('adgreenlng_customer', array('partnerid' => 0), array('partnerid' => $partnerid));
                pdo_update('adgreenlng_customer', array('recommendpid' => 0), array('recommendpid' => $partnerid));
                message('操作成功！', referer(), 'success');
            } elseif ($op == 'invitation') {
                $_share['link'] = $_W['siteroot'] . 'app/' . $this->createMobileUrl('partner', array('act' => 'regist', 'fromid' => $this->partner['subuid']));
                //修复数据库中的错误记录
                if ($this->partner['invite_qrcode'] != '') {
                    if (IMS_VERSION == 0.6) {
                        $tmp = ATTACHMENT_ROOT . '/' . $this->partner['invite_qrcode']; //微擎0.6 ATTACHMENT_ROOT 路径最后未以‘/’结尾
                    } else {
                        $tmp = ATTACHMENT_ROOT . $this->partner['invite_qrcode'];
                    }
                    if (!file_exists($tmp)) {
                        $this->partner['invite_qrcode'] = '';
                    }
                }
                $path = "images/{$_W['uniacid']}/" . date('Y/m');
                if (IMS_VERSION == 0.6) {
                    $filename = file_random_name(ATTACHMENT_ROOT . '/' . $path, 'png');
                    $qrcode_file = ATTACHMENT_ROOT . '/' . $path . '/' . $filename;
                    $allpath = ATTACHMENT_ROOT . '/' . $path;
                } else {
                    $filename = file_random_name(ATTACHMENT_ROOT . $path, 'png');
                    $qrcode_file = ATTACHMENT_ROOT . $path . '/' . $filename;
                    $allpath = ATTACHMENT_ROOT . $path;
                }
                mkdirs($allpath);
                if ($this->partner['invite_qrcode'] == '') {
                    adgreenlng_qrcode_png($_share['link'], $qrcode_file);
                    pdo_update('adgreenlng_partner', array(
                        'invite_qrcode' => $path . '/' . $filename,
                    ), array(
                        'id' => $this->partner['id'],
                    ));
                    if (IMS_VERSION == 0.7) {
                        $invite_qrcode_url = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/' . $path . '/' . $filename;
                    } else {
                        $invite_qrcode_url = tomedia($path . '/' . $filename);
                    }
                } else {
                    if (IMS_VERSION == 0.7) {
                        $invite_qrcode_url = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/' . $this->partner['invite_qrcode'];
                    } else {
                        $invite_qrcode_url = tomedia($this->partner['invite_qrcode']);
                    }
                }
            }
            include $this->template('partner-myteam');
        }

        private
        function do_mycustomer()
        {
            global $_W, $_GPC, $do;
            $title = '经纪人';
            $this->checkauth();
            $this->check_partner_status();
            $op = $_GPC['op'] ? $_GPC['op'] : 'display';
            // if ($op == 'display') {
            if ($op == 'display' || $op == 'list') {
                $pindex = max(1, intval($_GPC['page']));
                $pagesize = 5;
                $start = ($pindex - 1) * $pagesize;
                $realname = trim($_GPC['realname']);
                $statusid = intval($_GPC['statusid']);
                $statustitle = '全部';
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_customer_status');
                $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'];
                $all_status = pdo_fetchall($sql, array(), 'id');
                if ($all_status && $statusid) {
                    foreach ($all_status as $v) {
                        if ($v['id'] == $statusid) {
                            $statustitle = $v['title'];
                        }
                    }
                }

                $list = array();
                $params = array();
                $condition = ' WHERE a.houseid=b.id';

                if ($this->partner['role']['isadmin'] || $this->partner['is_subpartner']) { //项目经理和业务员
                    //根据推荐人id获取推荐客户
                    $condition .= ' AND (a.partnerid=:partnerid';
                    //$condition .= ' OR a.recommendpid=:recommendpid';
                    //$params[':recommendpid'] = $this->partner['id'];
                    $params[':partnerid'] = $this->partner['id'];
                    if ($this->partner['role']['isadmin']) {
                        //根据楼盘id获取推荐客户
                        $houseids = array_keys($this->partner['house']);
                        if ($houseids) {
                            $houseids = implode(',', $houseids);
                            $condition .= " OR a.houseid IN($houseids)";
                        }
                    }
                    $condition .= ')';
                } else {    //大众经纪人
                    $condition .= ' AND a.recommendpid=:recommendpid';
                    $params[':recommendpid'] = $this->partner['id'];
                }

                //过滤状态
                if ($statusid > 0) {
                    $condition .= " AND a.laststatusid=:laststatusid";
                    $params[':laststatusid'] = $statusid;
                }

                //过滤姓名
                if ($realname != '') {
                    $condition .= " AND a.realname LIKE '%$realname%'";
                }

                $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_customer') . " AS a," . tablename('adgreenlng_house') . " AS b " . $condition;
                //echo $sql;var_dump($params);
                $total = pdo_fetchcolumn($sql, $params);
                if ($total > 0) {
                    $sql = "SELECT a.*,b.name AS house_name FROM " . tablename('adgreenlng_customer') . " AS a," . tablename('adgreenlng_house') . " AS b " . $condition . " ORDER BY a.id DESC LIMIT $start,$pagesize";
                    $list = pdo_fetchall($sql, $params);
                    if ($list) {
                        foreach ($list as &$item) {
                            $item['partner_realname'] = '';
                            if ($item['partnerid']) {
                                $partner = adgreenlng_partner_fetch_by_id($item['partnerid']);
                                $item['partner_realname'] = $partner['realname'];
                            }
                            // 增加推荐信息
                            $item['recommendtime'] = date('Y-m-d H:i:s', $item['dateline']);
                            $sql = 'SELECT `realname` FROM ' . tablename('adgreenlng_partner');
                            $sql .= ' WHERE `id` = ' . $item['recommendpid'];
                            $recommender = pdo_fetch($sql);
                            if (!empty($recommender)) {
                                $item['recommender'] = $recommender['realname'];
                            }
                            unset($item);
                        }
                    }
                    $pager = pagination($total, $pindex, $pagesize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));

                }
                include $this->template('partner-mycustomer');
            } elseif ($op == 'setstatus') {
                $status = intval($_GPC['status']);
                $customerid = intval($_GPC['customerid']);
                if (!isset($_GPC['status']) || !in_array($status, array(-1, 0)) || $customerid < 1) {
                    message('参数错误！', referer(), 'error');
                }
                $ret = pdo_update('adgreenlng_customer', array('status' => $status), array('id' => $customerid));
                if ($ret !== false) {
                    message('设置成功！', referer(), 'success');
                } else {
                    message('设置失败，请重试！', referer(), 'error');
                }
            }
        }

        private
        function do_distribution()
        {
            global $_W, $_GPC, $do;
            $title = '经纪人';
            $this->checkauth();
            $this->check_partner_status();
            $op = $_GPC['op'] ? $_GPC['op'] : 'distribut';
            if ($op == 'distribut') { //分配客户
                if (!$this->partner['role']['isadmin']) {
                    message('您没有权限操作！', referer(), 'warning');
                }
                $id = intval($_GPC['id']);
                if (empty($id)) {
                    message('非法请求！', referer(), 'error');
                }
                $sql = 'SELECT a.*, b.`name` as `housename` FROM ' . tablename('adgreenlng_customer') . ' AS a, ' . tablename('adgreenlng_house') . ' AS b';
                $sql .= ' WHERE a.id=:id AND a.houseid=b.id';
                $params = array(
                    ':id' => $id,
                );
                $customer = pdo_fetch($sql, $params);
                if (empty($customer)) {
                    message('客户不存在或已删除！', referer(), 'error');
                }
                $customer['partner'] = adgreenlng_partner_fetch_by_id($customer['partnerid']);


                //获取业务员
                $pindex = max(1, intval($_GPC['page']));
                $pagesize = 20;
                $start = ($pindex - 1) * $pagesize;
                $condition = ' WHERE a.partnerid=:partnerid AND a.subpartnerid=b.id AND b.status=1';
                $params = array(
                    ':partnerid' => $this->partner['id'],
                );
                $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_partner_rel') . " AS a," . tablename('adgreenlng_partner') . " AS b" . $condition;
                $total = pdo_fetchcolumn($sql, $params);
                if ($total > 0) {
                    $sql = "SELECT b.id,b.realname,b.customer_total FROM " . tablename('adgreenlng_partner_rel') . " AS a," . tablename('adgreenlng_partner') . " AS b" . $condition;
                    $list = pdo_fetchall($sql, $params, 'id');
                }
            } elseif ($op == 'changepartner') {   //重新分配客户
                if (!$this->partner['role']['isadmin']) {
                    message('您没有权限操作！', referer(), 'warning');
                }
                $customerid = $_GPC['customerid'];
                $partnerid = $_GPC['partnerid'];
                if ($customerid > 0 && $partnerid > 0) {
                    $partner = adgreenlng_partner_fetch_by_id($partnerid);
                    if (!$partner) {
                        message('经纪人不存在或已删除！', referer(), 'error');
                    }
                    $customer = adgreenlng_customer_fetch_by_id($customerid);
                    if (!$customer) {
                        message('客户不存在或已删除！', referer(), 'error');
                    }
                    if ($customer['partnerid'] == $partnerid) {
                        message('操作成功！', $this->createMobileUrl('partner', array('act' => 'mycustomer')), 'success');
                    }
                    $ret = pdo_update('adgreenlng_customer', array('partnerid' => $partnerid), array('id' => $customerid));
                    if ($ret !== false) {
                        //给当前经纪人增加客户数
                        $data = array(
                            'customer_total' => $partner['customer_total'] + 1,
                        );
                        $condition = array(
                            'id' => $partnerid,
                        );
                        pdo_update('adgreenlng_partner', $data, $condition);

                        if ($customer['partnerid']) {
                            //给原经纪人减少客户数
                            $from_partner = adgreenlng_partner_fetch_by_id($customer['partnerid']);
                            if ($from_partner) {
                                $data = array(
                                    'customer_total' => $from_partner['customer_total'] - 1,
                                );
                                $condition = array(
                                    'id' => $customer['partnerid'],
                                );
                                pdo_update('adgreenlng_partner', $data, $condition);
                            }
                        }

                        // 给新经纪人发送通知
                        $sql = 'SELECT `name` FROM ' . tablename('adgreenlng_house');
                        $sql .= ' WHERE `id` = :houseid';
                        $params = array(
                            ':houseid' => $customer['houseid'],
                        );
                        $house = pdo_fetch($sql, $params);
                        if (!empty($house)) {
                            $housename = $house['name'];
                            $fans = mc_fansinfo($partner['subuid']);
                            $customername = $customer['realname'];
                            $url = $_W['siteroot'] . 'app/' . $this->createMobileUrl('partner', array('act' => 'mycustomer', 'realname' => $customername)); //我的客户链接
                            $updatetime = date('Y-m-d H:i', TIMESTAMP);
                            if ($_W['account']['level'] == 4) { //服务号（已认证）
                                if ($this->module['config']['partner']['distribution_customer']['template_id']
                                    && $this->module['config']['partner']['distribution_customer']['template_content']
                                    && $this->module['config']['partner']['distribution_customer']['template_variable']
                                ) {
                                    $vars = array(
                                        '{housename}' => $housename,
                                        '{customername}' => $customername,
                                        '{update_time}' => $updatetime,
                                    );
                                    $message_info = array(
                                        'template_id' => $this->module['config']['partner']['distribution_customer']['template_id'],
                                        'template_content' => $this->module['config']['partner']['distribution_customer']['template_content'],
                                        'template_variable' => $this->module['config']['partner']['distribution_customer']['template_variable'],
                                        'uniacid' => $customer['uniacid'],
                                        'receiver_uid' => $partner['subuid'],
                                        'url' => $url,
                                        'vars' => $vars,
                                        'openid' => $fans['openid'],
                                    );
                                    $this->sendTemplateMessage($message_info);
                                } else {
                                    //没有配置模板消息发送客服消息
                                    $this->sendDistributeNotice($fans['openid'], $url, $housename, $customername, $updatetime);
                                }
                            } else {
                                if ($_W['account']['level'] == 3) { //订阅号（已认证）
                                    $this->sendDistributeNotice($fans['openid'], $url, $housename, $customername, $updatetime);
                                }
                            }
                        }
                    }
                }
                message('操作成功！', $this->createMobileUrl('partner', array('act' => 'mycustomer')), 'success');
            } else if ($op == 'setup') { //展示客户状态
                $id = intval($_GPC['id']);
                if (empty($id)) {
                    message('非法请求', referer(), 'error');
                }
                $customer = adgreenlng_customer_fetch_by_id($id);
                if (empty($customer)) {
                    message('客户不存在或已删除', referer(), 'error');
                }
                $customer['partner'] = adgreenlng_partner_fetch_by_id($customer['partnerid']);
                $customer['house'] = ad_greenlng_fetch($customer['houseid']);

                //获取所有客户状态
                $filter = array(
                    'uniacid' => $_W['uniacid'],
                );
                $customer_status = adgreenlng_customer_status_fetchall($filter, '', 0, -1, 'id');
                //print_r($customer_status);
                $customer['status_title'] = $customer_status[$customer['laststatusid']]['title'];

                $sql = 'SELECT a.*, b.`realname` FROM ' . tablename('adgreenlng_customer_trace') . ' AS a, ' . tablename('adgreenlng_partner') . ' AS b';
                $sql .= ' WHERE a.customerid=:customerid';
                $sql .= ' AND a.partnerid=b.id';
                $sql .= ' ORDER BY a.statusid ASC';
                $params = array(
                    ':customerid' => $id,
                );
                $list = pdo_fetchall($sql, $params, 'statusid');
                foreach ($customer_status as $statusid => $v) {
                    if (!isset($list[$statusid])) {
                        $list[$statusid]['id'] = 0;
                        $list[$statusid]['customerid'] = $customer['id'];
                        $list[$statusid]['statusid'] = $statusid;
                        $list[$statusid]['partnerid'] = 0;
                        $list[$statusid]['remark'] = '';
                    } else {
                        $list[$statusid]['money'] = adgreenlng_format_price($list[$statusid]['money']);
                    }
                    $list[$statusid]['title'] = $v['title'];
                }
                ksort($list);
                //print_r($list);

                //$credits = adgreenlng_get_credits();
            } else if ($op == 'setremark') {   //设置客户状态
                $id = intval($_GPC['id']);
                $customerid = intval($_GPC['customerid']);
                $statusid = intval($_GPC['statusid']);
                $remark = $_GPC['remark'];
                $money = $_GPC['money'];

                // 设置cookie，防止点返回时重复提交
                $temp = $id . $customerid . $statusid . $remark . $money;
                $set_remark_key = 'setremark_' . md5($temp);
                if ($_COOKIE[$set_remark_key] != '') {
                    setcookie($set_remark_key, '', TIMESTAMP - 1);
                    $url = $_W['siteroot'] . 'app/' . $this->createMobileUrl('partner', array('act' => 'mycustomer')); //我的客户链接
                    header('Location: ' . $url);
                    return;
                }

                $customer = adgreenlng_customer_fetch_by_id($customerid);
                if (!$customer) {
                    message('客户不存在或已删除', referer(), 'error');
                }

                //是否有权限管理该客户
                if ($this->partner['role']['isadmin']) {    //项目经理
                    $sql = "SELECT * FROM " . tablename('adgreenlng_partner_house_ref') . " WHERE partnerid=:partnerid AND houseid=:houseid";
                    $params = array(
                        ':partnerid' => $this->partner['id'],
                        ':houseid' => $customer['houseid'],
                    );
                    $house_ref = pdo_fetch($sql, $params);
                    //即不是楼盘项目经理，也不是客户经纪人
                    if (!$house_ref && $customer['partnerid'] != $this->partner['id']) {
                        message('您没有权限操作(-1)！', referer(), 'warning');
                    }
                } else {
                    if ($this->partner['is_subpartner']) {  //业务员
                        if ($customer['partnerid'] != $this->partner['id']) {
                            message('您没有权限操作(-2)！', referer(), 'warning');
                        }
                    } else {
                        message('您没有权限操作(-3)！', referer(), 'warning');
                    }
                }

                //设置客户状态
                $trace = array();
                if ($id) {
                    $trace = adgreenlng_customer_trace_fetch($id);
                } else {
                    $filter = array(
                        'customerid' => $customerid,
                        'statusid' => $statusid,
                        'partnerid' => $this->partner['id'],
                    );
                    $res = adgreenlng_customer_trace_fetchall($filter, '', 0, 1);
                    if ($res) {
                        $trace = $res[0];
                    }
                }
                if ($trace) {
                    $data = array(
                        'remark' => $remark,
                        'money' => $money,
                        'dateline' => TIMESTAMP,
                    );
                    $condition = array(
                        'id' => $trace['id'],
                    );
                    pdo_update('adgreenlng_customer_trace', $data, $condition);
                } else {
                    $data = array(
                        'uniacid' => $_W['uniacid'],
                        'customerid' => $customerid,
                        'statusid' => $statusid,
                        'partnerid' => $this->partner['id'],
                        'remark' => $remark,
                        'money' => $money,
                        'dateline' => TIMESTAMP,
                    );
                    pdo_insert('adgreenlng_customer_trace', $data);
                }

                //记录最新状态
                pdo_update('adgreenlng_customer', array('laststatusid' => $statusid), array('id' => $customerid));
                $customer['laststatusid'] = $statusid;

                //记录佣金
                if ($money > 0) {
                    $partner = adgreenlng_partner_fetch_by_id($customer['recommendpid']);
                    adgreenlng_partner_add_commission($partner['subuid'], $customer['id'], 'customerid', $money);
                }

                //获取操作人姓名
                $sql = 'SELECT `realname` FROM ' . tablename('adgreenlng_partner');
                $sql .= ' WHERE `id` = ' . $this->partner['id'];
                $changername = pdo_fetchcolumn($sql);

                //获取状态标题
                $sql = 'SELECT `title` FROM ' . tablename('adgreenlng_customer_status');
                $sql .= ' WHERE `id` = ' . $statusid;
                $status_title = pdo_fetchcolumn($sql);

                // 设置Cookie防止返回时重复提交
                setcookie($set_remark_key, '1', time() + (7 * 24 * 60 * 60));

                //给客户的推荐经纪人发送通知消息
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner');
                $sql .= ' WHERE `id` = ' . $customer['recommendpid'];
                $recommend_partner = pdo_fetch($sql);
                if ($recommend_partner) {
                    //获取openid
                    $fans = mc_fansinfo($recommend_partner['subuid']);
                    $recommend_fansid = $fans['fanid'];
                    $receivername = $recommend_partner['realname'];
                    $customername = $customer['realname'];
                    $url = $_W['siteroot'] . 'app/' . $this->createMobileUrl('partner', array('act' => 'mycustomer')); //我的客户链接
                    if ($_W['account']['level'] == 4) { //服务号（已认证）
                        if ($this->module['config']['partner']['customer']['template_id']
                            && $this->module['config']['partner']['customer']['template_content']
                            && $this->module['config']['partner']['customer']['template_variable']
                        ) {
                            $vars = array(
                                '{changername}' => $changername,
                                '{receivername}' => $receivername,
                                '{customername}' => $customername,
                                '{status}' => $status_title,
                                '{remark}' => $remark,
                                '{money}' => $money,
                                '{update_time}' => date('Y-m-d H:i', TIMESTAMP),
                            );
                            $message_info = array(
                                'template_id' => $this->module['config']['partner']['customer']['template_id'],
                                'template_content' => $this->module['config']['partner']['customer']['template_content'],
                                'template_variable' => $this->module['config']['partner']['customer']['template_variable'],
                                'uniacid' => $customer['uniacid'],
                                'receiver_uid' => $recommend_partner['subuid'],
                                'url' => $url,
                                'vars' => $vars,
                                'openid' => $fans['openid'],
                            );
                            $this->sendTemplateMessage($message_info);
                        } else {
                            //没有配置模板消息发送客服消息
                            $this->sendCustomerStatusNotice($fans['openid'], $changername, $receivername, $customername, $status_title, $url, TIMESTAMP, $remark, $money);
                        }
                    } else {
                        if ($_W['account']['level'] == 3) { //订阅号（已认证）
                            $this->sendCustomerStatusNotice($fans['openid'], $changername, $receivername, $customername, $status_title, $url, TIMESTAMP, $remark, $money);
                        }
                    }
                } else {
                    WeUtility::logging("warning", "模板消息发送失败：未找到推荐经纪人数据, partnerid=" . $customer['recommendpid']);
                }

                //当客户的所属经纪人是业务员时，给该业务员的项目经理发送通知消息
                // if ($customer['partnerid'] > 0 && $customer['partnerid'] != $customer['recommendpid']) {
                if ($customer['partnerid'] > 0) {
                    $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner');
                    $sql .= ' WHERE `id` = ' . $customer['partnerid'];
                    $partner = pdo_fetch($sql);
                    // if ($partner) {
                    if ($partner && $recommend_partner['subuid'] != $partner['subuid']) {
                        if ($partner['roleid']) {
                            $partner['role'] = adgreenlng_partner_role_fetch_by_id($partner['roleid']);
                        }
                        if ($partner['role']) {
                            $is_subpartner = false;
                            $row = adgreenlng_partner_rel_fetch_by_subpartnerid($partner['id']);
                            if ($row && $partner['role']['issubadmin']) {
                                $is_subpartner = true;
                                $partner['parent_partner'] = array(   //记录属于哪个项目经理
                                    'id' => $row['id'],
                                    'partnerid' => $row['partnerid'],
                                    'createtime' => $row['createtime'],
                                );
                            }
                            if ($is_subpartner) {
                                //获取项目经理经纪人
                                $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner');
                                $sql .= ' WHERE `id` = ' . $partner['parent_partner']['partnerid'];
                                $project_partner = pdo_fetch($sql);
                                $receivername = $project_partner['realname'];
                                $customername = $customer['realname'];
                                $fans = mc_fansinfo($project_partner['subuid']);
                                // 防止项目经理和推荐人是同一人时收到两条消息
                                if ($fans['fanid'] > 0 && $recommend_fansid > 0 && $fans['fanid'] != $recommend_fansid) {
                                    $url = $_W['siteroot'] . 'app/' . $this->createMobileUrl('partner', array('act' => 'mycustomer')); //我的客户链接
                                    if ($_W['account']['level'] == 4) { //服务号
                                        if ($this->module['config']['partner']['customer']['template_id']
                                            && $this->module['config']['partner']['customer']['template_content']
                                            && $this->module['config']['partner']['customer']['template_variable']
                                        ) {
                                            $vars = array(
                                                '{changername}' => $changername,
                                                '{receivername}' => $receivername,
                                                '{customername}' => $customername,
                                                '{status}' => $status_title,
                                                '{remark}' => $remark,
                                                '{money}' => $money,
                                                '{update_time}' => date('Y-m-d H:i', TIMESTAMP),
                                            );
                                            $message_info = array(
                                                'template_id' => $this->module['config']['partner']['customer']['template_id'],
                                                'template_content' => $this->module['config']['partner']['customer']['template_content'],
                                                'template_variable' => $this->module['config']['partner']['customer']['template_variable'],
                                                'uniacid' => $customer['uniacid'],
                                                'receiver_uid' => $project_partner['subuid'],
                                                'url' => $url,
                                                'vars' => $vars,
                                                'openid' => $fans['openid'],
                                            );
                                            $this->sendTemplateMessage($message_info);
                                        } else {
                                            //没有配置模板消息发送客服消息
                                            $this->sendCustomerStatusNotice($fans['openid'], $changername, $receivername, $customername, $status_title, $url, TIMESTAMP, $remark, $money);
                                        }
                                    } else {
                                        if ($_W['account']['level'] == 3) { //订阅号（已认证）
                                            $this->sendCustomerStatusNotice($fans['openid'], $changername, $receivername, $customername, $status_title, $url, TIMESTAMP, $remark, $money);
                                        }
                                    }
                                }
                            }
                        } else {
                            WeUtility::logging("warning", "not found partner role, partnerid={$customer['partnerid']}, roleid={$partner['roleid']}");
                        }
                    } else {
                        WeUtility::logging("warning", "模板消息发送失败：未找到经纪人数据, partnerid=" . $customer['partnerid']);
                    }
                } else {
                    WeUtility::logging("warning", "recommendpid={$customer['recommendpid']}, partnerid={$customer['partnerid']}");
                }

                message('操作成功！', referer(), 'success');
            }
            include $this->template('partner-distribution');
        }

        private
        function do_explain()
        {
            global $_W, $_GPC, $do;
            $title = '经纪人';
            include $this->template('partner-explain');
        }

        private
        function do_regist()
        {
            global $_W, $_GPC, $do;
            $title = '经纪人';
            //已加入经纪人
            if ($this->partner['yes']) {
                if ($this->partner['status'] == -1) {
                    message('您的账号正在审核中暂未开通！', $this->createMobileUrl('partner', array('act' => 'display')), 'warning');
                } else if ($this->partner['status'] == 0) {
                    message('您的经纪人账号已被禁用！', $this->createMobileUrl('partner', array('act' => 'display')), 'warning');
                } else {
                    message('您已加入经纪人！', $this->createMobileUrl('partner', array('act' => 'display')), 'success');
                }
            }

            //角色
            $roles = adgreenlng_partner_role_fetchall();

            //经纪人注册提交
            if (checksubmit('submit')) {
                $this->checkauth();
                $name = trim($_GPC['name']);
                $phone = trim($_GPC['phone']);
                $roleid = array_key_exists($_GPC['roleid'], $roles) ? $_GPC['roleid'] : 0;
                if ($name == '') {
                    message('请输入姓名！', referer(), 'error');
                }
                if ($phone == '') {
                    message('请输入手机号！', referer(), 'error');
                }
                if (!array_key_exists($roleid, $roles)) {
                    message('请选择身份类型！', referer(), 'error');
                }
                if ($this->module['config']['partner']['agreement']) {
                    if (!isset($_GPC['agreement'])) {
                        message('加入经纪人需勾选同意经纪人协议', referer(), 'error');
                    }
                }
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner');
                $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'];
                $sql .= ' AND `phone` = ' . $phone;
                if (pdo_fetch($sql)) {
                    message('该手机已被注册经纪人账号！', referer(), 'warning');
                }
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'subuid' => $this->uid,
                    'level' => 1,
                    'roleid' => $roleid,
                    'realname' => $name,
                    'phone' => $phone,
                    'status' => $this->module['config']['partner']['reg_check'] ? -1 : 1,    //是否开启注册审核
                    'createtime' => TIMESTAMP,
                );
                pdo_insert('adgreenlng_partner', $data);
                $new_id = pdo_insertid();
                if ($new_id) {
                    if (isset($_GPC['__fromid'])) {
                        isetcookie('__fromid', '0', -1);
                    }
                    //存在邀请人
                    if ($this->from_member) {
                        $from_partner = adgreenlng_partner_fetch_by_uid($this->from_member['uid']);
                        if ($from_partner) {
                            $role = adgreenlng_partner_role_fetch_by_id($from_partner['roleid']);
                            if ($role['isadmin']) { //邀请人有管理权限
                                $data = array(
                                    'uniacid' => $_W['uniacid'],
                                    'partnerid' => $from_partner['id'], //邀请人(项目经理)
                                    'subpartnerid' => $new_id,  //业务员
                                    'createtime' => TIMESTAMP,
                                );
                                pdo_insert('adgreenlng_partner_rel', $data);
                            }
                        }
                    }
                    if ($this->module['config']['partner']['reg_check'] == 1) {
                        message('提交成功，请耐心等待管理员审核！', $this->createMobileUrl('partner', array('act' => 'display')), 'success');
                    } else {
                        message('恭喜您，加入经纪人成功！', $this->createMobileUrl('partner', array('act' => 'display')), 'success');
                    }
                } else {
                    message('系统错误，请稍后重试', referer(), 'success');
                }
            }
            include $this->template('partner-regist');
        }

        private
        function do_setting()
        {
            global $_W, $_GPC, $do;
            $title = '经纪人';
            checkauth();
            $member = mc_fetch($this->uid, array('avatar', 'email', 'mobile', 'nickname'));
            $has_email = $member['email'] ? true : false;
            if ($member['email'] == md5($_W['fans']['openid']) . '@aodao.com.cn') {
                $has_email = false;
            }
            if (checksubmit('submit')) {
                //表单验证
                $serverId = $_GPC['serverId'];
                $nickname = $_GPC['nickname'];
                $mobile = $_GPC['mobile'];
                $email = $_GPC['email'];
                if ($mobile != '') {
                    if (!preg_match('/1\d{10}/', $mobile)) {
                        exit('手机号码不合法，请重新填写。');
                    }
                    //检查手机是否存在
                    $sql = "SELECT uid FROM " . tablename('mc_members') . " WHERE mobile = :mobile AND uniacid = :uniacid AND uid != :uid";
                    $params = array(
                        ':mobile' => $mobile,
                        ':uniacid' => $_W['uniacid'],
                        ':uid' => $_W['member']['uid'],
                    );
                    $exists = pdo_fetchcolumn($sql, $params);
                    if ($exists) {
                        exit('该手机号码已存在，请重新填写。');
                    }
                }
                if ($email != '') {
                    if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/i', $email)) {
                        exit('请输入合法邮箱。');
                    }
                    //检查邮箱是否存在
                    $sql = "SELECT uid FROM " . tablename('mc_members') . " WHERE email = :email AND uniacid = :uniacid AND uid != :uid";
                    $params = array(
                        ':email' => $email,
                        ':uniacid' => $_W['uniacid'],
                        ':uid' => $_W['member']['uid'],
                    );
                    $emailexists = pdo_fetchcolumn($sql, $params);
                    if ($emailexists) {
                        exit('该邮箱已存在，请重新填写。');
                    }
                }

                //下载微信图片
                if ($serverId != '') {
                    //初始化路径
                    $path = "images/{$_W['uniacid']}/" . date('Y/m');
                    $filename = md5($serverId) . '.jpg';
                    if (IMS_VERSION == 0.6) {
                        $avatar_file = ATTACHMENT_ROOT . '/' . $path . '/' . $filename;
                        $allpath = ATTACHMENT_ROOT . '/' . $path;
                    } else {
                        $avatar_file = ATTACHMENT_ROOT . $path . '/' . $filename;
                        $allpath = ATTACHMENT_ROOT . $path;
                    }
                    mkdirs($allpath);

                    //下载微信图片
                    load()->model('account');
                    $acc = WeAccount::create($_W['account']['acid']);
                    $token = $acc->getAccessToken();
                    if (is_error($token)) {
                        WeUtility::logging('fatal', 'token error, message=' . $token['message']);
                        exit('系统错误');
                    }
                    load()->func('communication');
                    $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$token}&media_id={$serverId}";
                    $resp = ihttp_request($url);
                    if (is_error($resp)) {
                        WeUtility::logging('fatal', 'request error, message=' . $resp['message']);
                        exit('系统错误');
                    }

                    //保存头像图片
                    $fp = @fopen($avatar_file, 'wb');
                    @fwrite($fp, $resp['content']);
                    @fclose($fp);
                    $new_avatar = $path . '/' . $filename;
                    mc_update($_W['member']['uid'], array(
                        'avatar' => $new_avatar,
                    ));
                }

                //更新数据
                $data = array();
                if (isset($_GPC['mobile']) && $_GPC['mobile'] != '') {
                    $data['mobile'] = trim($_GPC['mobile']);
                }
                if (isset($_GPC['nickname']) && $_GPC['nickname'] != '') {
                    $data['nickname'] = trim($_GPC['nickname']);
                }
                if (isset($_GPC['email']) && $_GPC['email'] != '') {
                    $data['email'] = trim($_GPC['email']);
                }
                if (!empty($data)) {
                    mc_update($_W['member']['uid'], $data);
                }
                exit('success');
            }
            include $this->template('partner-setting');
        }
    }

    private function check_partner_status()
    {
        if (!$this->partner['yes']) {
            message('您未加入经纪人，请认真填写资料后提交审核！', $this->createMobileUrl('partner', array('act' => 'regist')), 'warning');
        } else if ($this->partner['status'] == 2) {
            message('您的经纪人账号已被禁用！', $this->createMobileUrl('partner', array('act' => 'display')), 'warning');
        } else if ($this->partner['status'] == -1) {
            message('您的账号正在审核中暂未开通！', $this->createMobileUrl('partner', array('act' => 'display')), 'warning');
        }
    }

$obj = new Ad_greenlng_doMobilePartner;
$obj->exec();
