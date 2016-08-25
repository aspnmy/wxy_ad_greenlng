<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doWebCustomer extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_GPC, $_W;
        $title = '客户管理';
        $eid = intval($_GPC['eid']);
        $do = !empty($_GPC['do']) ? $_GPC['do'] : 'display';

        $sql = 'SELECT * FROM ' . tablename('adgreenlng_customer_status');
        $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'];
        $sql .= ' ORDER BY `displayorder` ASC';
        $customer_status = pdo_fetchall($sql, array(), 'id');
        if (!$customer_status) {
            $sql = 'SELECT * FROM ' . tablename('adgreenlng_customer_status') . " WHERE uniacid=0";
            $commonlist = pdo_fetchall($sql);
            if ($commonlist) {
                foreach ($commonlist as $v) {
                    $data = array(
                        'uniacid' => $_W['uniacid'],
                        'displayorder' => $v['displayorder'],
                        'title' => $v['title'],
                    );
                    pdo_insert('adgreenlng_customer_status', $data);
                    $data['id'] = pdo_insertid();
                    $customer_status[$data['id']] = $data;
                }
            }
        }

        if ($do == 'display') {
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 20;
            $start = ($pindex - 1) * $pagesize;
            $condition = ' WHERE a.`houseid` = b.`id` AND a.`uniacid` = ' . $_W['uniacid'];
            $statusid = $_GPC['statusid'];
            if ($statusid > 0) {
                $condition .= ' AND a.`laststatusid` = ' . $statusid;
            }
            $realname = $_GPC['realname'];
            if ($realname != '') {
                $condition .= " AND a.`realname` LIKE '%" . $realname . "%'";
            }
            $mobile = $_GPC['mobile'];
            if ($mobile != '') {
                $condition .= " AND a.`mobile` LIKE '%" . $mobile . "%'";
            }
            $housename = $_GPC['housename'];
            if ($housename != '') {
                $condition .= " AND b.`name` LIKE '%" . $housename . "%'";
            }
            $display_partner_id = $_GPC['partnerid'];
            $display_partner_name = trim($_GPC['display_partner_name']);
            if ($display_partner_id != '') {
                $condition .= " AND a.`partnerid` = " . $display_partner_id;
            }
            $sql = 'SELECT COUNT(*) FROM ' . tablename('adgreenlng_customer') . ' AS a, ' . tablename('adgreenlng_house') . ' AS b';
            $sql .= $condition;
            $total = pdo_fetchcolumn($sql);
            $sql = 'SELECT a.*, b.`name` as `housename` FROM ' . tablename('adgreenlng_customer') . ' AS a, ' . tablename('adgreenlng_house') . ' AS b';
            $sql .= $condition . " ORDER BY a.`dateline` DESC LIMIT $start,$pagesize";
            $partners = array();
            $list = pdo_fetchall($sql);
            if ($list) {
                foreach ($list as &$c) {
                    $id = $c['partnerid'];
                    if ($id) {
                        if (!isset($partners[$id])) {
                            $partners[$id] = adgreenlng_partner_fetch_by_id($id);
                        }
                        $c['partner_realname'] = $partners[$id]['realname'];
                    }

                    $pid = $c['recommendpid'];
                    if (!isset($partners[$pid])) {
                        $partners[$pid] = adgreenlng_partner_fetch_by_id($pid);
                    }
                    $c['recommend_realname'] = $partners[$pid]['realname'];

                    $c['status'] = $customer_status[$c['laststatusid']]['title'];
                    unset($c);
                }
                $pager = pagination($total, $pindex, $pagesize);
            }
        } else if ($do == 'deletecustom') {
            $id = $_GPC['id'];
            if ($id > 0) {
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_customer');
                $sql .= ' WHERE `id` = ' . $id;
                $item = pdo_fetch($sql);
                if (empty($item)) {
                    message('该客户不存在', referer(), 'error');
                }
                if (pdo_delete('adgreenlng_customer', array('id' => $id)) > 0) {
                    pdo_delete('adgreenlng_customer_trace', array('customerid' => $id));
                    message('删除成功', referer(), 'success');
                } else {
                    message('删除失败', referer(), 'error');
                }
            }
        } else if ($do == 'distribution') {
            $id = $_GPC['id'];
            if ($id > 0) {
                $pindex = max(1, intval($_GPC['page']));
                $pagesize = 20;
                $start = ($pindex - 1) * $pagesize;
                $sql = 'SELECT a.*, b.`name` as `housename` FROM ' . tablename('adgreenlng_customer') . ' AS a, ' . tablename('adgreenlng_house') . ' AS b';
                $sql .= ' WHERE a.`id` = ' . $id;
                $sql .= ' AND a.`houseid` = b.`id`';
                $customer = pdo_fetch($sql);
                if (!empty($customer)) {
//                    $condition = ' WHERE a.`id` = b.`partnerid`';
//                    $condition .= ' AND b.`houseid` = '.$customer['houseid'];
//                    $sql = 'SELECT a.* FROM '.tablename('adgreenlng_partner').' AS a, '.tablename('adgreenlng_partner_house_ref').' AS b';
                    $condition = ' WHERE `uniacid`=' . $_W['uniacid'] . ' AND `roleid`!="3"';
                    $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner');
                    $sql .= $condition . " LIMIT $start,$pagesize";
                    $housepartners = pdo_fetchall($sql, array(), 'id');
//                    $sql = 'SELECT COUNT(*) FROM '.tablename('adgreenlng_partner').' AS a, '.tablename('adgreenlng_partner_house_ref').' AS b';
                    $sql = 'SELECT COUNT(*) FROM ' . tablename('adgreenlng_partner');
                    $sql .= $condition;
                    $total = pdo_fetchcolumn($sql);
                    $sql = 'SELECT COUNT(*) AS `count`, `partnerid` FROM ' . tablename('adgreenlng_customer');
                    $sql .= ' WHERE `houseid` = ' . $customer['houseid'];
                    $sql .= ' GROUP BY `partnerid`';
                    // $cuscounts = pdo_fetchall($sql, array(), 'partnerid');
                    $roles = adgreenlng_partner_role_fetchall();
                    if (!array_key_exists($customer['partnerid'], $housepartners)) {
                        $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner');
                        $sql .= ' WHERE `id` = ' . $customer['partnerid'];
                        $partner = pdo_fetch($sql);
                    } else {
                        $partner = $housepartners[$customer['partnerid']];
                    }
                    $pager = pagination($total, $pindex, $pagesize);
                }
            }
        } else if ($do == 'changepartner') {
            $customerid = $_GPC['customerid'];
            $partnerid = $_GPC['partnerid'];
            if ($customerid > 0 && $partnerid > 0) {
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_customer');
                $sql .= ' WHERE `id` = ' . $customerid;
                $item = pdo_fetch($sql);
                if (!empty($item)) {
                    if ($item['partnerid'] == $partnerid) {
                        message('分配成功', referer(), 'success');
                    }
                    $ret = pdo_update('adgreenlng_customer', array('partnerid' => $partnerid), array('id' => $customerid));
                    if ($ret !== false) {
                        //新经纪人增加客户数
                        $to_partner = adgreenlng_partner_fetch_by_id($partnerid);
                        if ($to_partner) {
                            $data = array(
                                'customer_total' => $to_partner['customer_total'] + 1,
                            );
                            $condition = array(
                                'id' => $partnerid,
                            );
                            pdo_update('adgreenlng_partner', $data, $condition);
                        }

                        if ($item['partnerid']) {
                            //给原经纪人减少客户数
                            $from_partner = adgreenlng_partner_fetch_by_id($item['partnerid']);
                            if ($from_partner) {
                                $data = array(
                                    'customer_total' => $from_partner['customer_total'] - 1,
                                );
                                $condition = array(
                                    'id' => $item['partnerid'],
                                );
                                pdo_update('adgreenlng_partner', $data, $condition);
                            }
                        }

                        // 给新经纪人发送通知
                        $sql = 'SELECT `name` FROM ' . tablename('adgreenlng_house');
                        $sql .= ' WHERE `id` = :houseid';
                        $params = array(
                            ':houseid' => $item['houseid'],
                        );
                        $house = pdo_fetch($sql, $params);
                        $newpartner = adgreenlng_partner_fetch_by_id($partnerid);
                        if (!empty($house) && !empty($newpartner)) {
                            $housename = $house['name'];
                            $fans = mc_fansinfo($newpartner['subuid']);
                            $customername = $item['realname'];
                            $url = $_W['siteroot'] . 'app/' . $this->createMobileUrl('partner', array('act' => 'mycustomer', 'realname' => $item['realname'])); //我的客户链接
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
                                        'uniacid' => $item['uniacid'],
                                        'receiver_uid' => $newpartner['subuid'],
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
            }
            message('分配成功', referer(), 'success');
        } else if ($do == 'setup') {
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
            $customer['recommend_partner'] = adgreenlng_partner_fetch_by_id($customer['recommendpid']);
            if ($customer['partnerid'] > 0) {
                $customer['partner'] = adgreenlng_partner_fetch_by_id($customer['partnerid']);
            }
            $customer['status_title'] = $customer_status[$customer['laststatusid']]['title'];
            //print_r($customer);

            //获取所有客户状态
            $filter = array(
                'uniacid' => $_W['uniacid'],
            );
            $customer_status = adgreenlng_customer_status_fetchall($filter, '', 0, -1, 'id');
            //print_r($customer_status);

            $sql = 'SELECT * FROM ' . tablename('adgreenlng_customer_trace');
            $sql .= ' WHERE customerid=:customerid';
            $sql .= ' ORDER BY statusid ASC';
            $params = array(
                ':customerid' => $id,
            );
            $list = pdo_fetchall($sql, $params, 'statusid');
            //print_r($list);
            foreach ($customer_status as $statusid => $v) {
                if (!isset($list[$statusid])) {
                    $list[$statusid]['id'] = 0;
                    $list[$statusid]['customerid'] = $customer['id'];
                    $list[$statusid]['statusid'] = $statusid;
                    $list[$statusid]['partnerid'] = 0;
                    $list[$statusid]['remark'] = '';
                    $list[$statusid]['money'] = '';
                    $list[$statusid]['partner'] = '';
                } /*else {
                    $list[$statusid]['money'] = adgreenlng_format_price($list[$statusid]['money']);
                }*/
                if (isset($list[$statusid]['partnerid']) && $list[$statusid]['partnerid'] > 0) {
                    $list[$statusid]['partner'] = adgreenlng_partner_fetch_by_id($list[$statusid]['partnerid']);
                } else {
                    $list[$statusid]['partner'] = array(
                        'realname' => '',
                    );
                }
                $list[$statusid]['title'] = $v['title'];
                unset($v);
            }
            ksort($list);
            //print_r($list);

            //$credits = adgreenlng_get_credits();
        } else if ($do == 'setremark') {
            $id = intval($_GPC['id']);
            $customerid = intval($_GPC['customerid']);
            $statusid = intval($_GPC['statusid']);
            $partnerid = intval($_GPC['partnerid']);
            $remark = $_GPC['remark'];
            $money = $_GPC['money'];
            if ($customerid <= 0) {
                message('非法请求(customerid)！', referer(), 'error');
            }
            if ($statusid <= 0) {
                message('非法请求(statusid)！', referer(), 'error');
            }

            $customer = adgreenlng_customer_fetch_by_id($customerid);
            if (!$customer) {
                message('客户不存在或已删除', referer(), 'error');
            }

            //设置客户状态
            if ($id) {
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_customer_trace') . ' WHERE id=:id';
                $params = array(
                    ':id' => $id,
                );
                $item = pdo_fetch($sql, $params);
            } else {
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_customer_trace') . ' WHERE customerid=:customerid';
                $sql .= ' AND statusid=:statusid';
                $params = array(
                    ':customerid' => $customerid,
                    ':statusid' => $statusid,
                );
                $item = pdo_fetch($sql, $params);
            }

            if (!empty($item)) {
                $data = array(
                    'partnerid' => $partnerid ? $partnerid : -1,
                    'remark' => $remark,
                    'money' => $money,
                    'dateline' => TIMESTAMP,
                );
                $condition = array(
                    'id' => $item['id'],
                );
                pdo_update('adgreenlng_customer_trace', $data, $condition);
            } else {
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'customerid' => $customerid,
                    'statusid' => $statusid,
                    'partnerid' => $partnerid ? $partnerid : -1,
                    'remark' => $remark,
                    'money' => $money,
                    'dateline' => TIMESTAMP,
                );
                pdo_insert('adgreenlng_customer_trace', $data);
            }

            //记录最新状态
            pdo_update('adgreenlng_customer', array('laststatusid' => $statusid), array('id' => $customerid));

            //记录佣金
            $partner = adgreenlng_partner_fetch_by_id($customer['recommendpid']);
            adgreenlng_partner_add_commission($partner['subuid'], $customer['id'], 'customerid', $money);

            //获取操作人姓名
            if ($partnerid > 0) {
                $sql = 'SELECT `realname` FROM ' . tablename('adgreenlng_partner');
                $sql .= ' WHERE `id` = ' . $partnerid;
                $changername = pdo_fetchcolumn($sql);
            } else {
                $changername = '管理员';
            }

            //获取状态标题
            $sql = 'SELECT `title` FROM ' . tablename('adgreenlng_customer_status');
            $sql .= ' WHERE `id` = ' . $statusid;
            $status_title = pdo_fetchcolumn($sql);

            $recommend_fansid = 0;
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
                WeUtility::logging("warning", "未找到推荐经纪人数据, partnerid=" . $customer['recommendpid']);
            }

            //当客户的所属经纪人是业务员时，给该业务员的项目经理发送通知消息
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

            message('操作成功', referer(), 'success');
        } else if ($do == 'status') {
            if (checksubmit()) {
                foreach ($_GPC['title'] as $k => $v) {
                    $title = trim($_GPC['title'][$k]);
                    if ($title == '') {
                        message('状态名称未填写！', referer(), 'error');
                    }
                    $data = array(
                        'displayorder' => ($_GPC['displayorder'][$k] > 0) ? $_GPC['displayorder'][$k] : 1000,
                        'title' => $title,
                    );
                    if (empty($_GPC['id'][$k])) {
                        $data['uniacid'] = $_W['uniacid'];
                        pdo_insert('adgreenlng_customer_status', $data);
                    } else {
                        $condition = array(
                            'id' => $_GPC['id'][$k],
                        );
                        pdo_update('adgreenlng_customer_status', $data, $condition);
                    }
                }
                message('设置成功', referer(), 'success');
            }
        } else if ($do == 'deletestatus') {
            $id = $_GPC['_id'];
            if ($id > 0) {
                pdo_delete('adgreenlng_customer_status', array('id' => $id));
            }
            echo 'success';
            exit;
        } else if ($do == 'toexcel') {
            $condition = ' WHERE a.`houseid` = b.`id` AND a.`uniacid` = ' . $_W['uniacid'];
            $statusid = $_GPC['statusid'];
            if ($statusid > 0) {
                $condition .= ' AND a.`laststatusid` = ' . $statusid;
            }
            $realname = $_GPC['realname'];
            if ($realname != '') {
                $condition .= " AND a.`realname` LIKE '%" . $realname . "%'";
            }
            $mobile = $_GPC['mobile'];
            if ($mobile != '') {
                $condition .= " AND a.`mobile` LIKE '%" . $mobile . "%'";
            }
            $housename = $_GPC['housename'];
            if ($housename != '') {
                $condition .= " AND b.`name` LIKE '%" . $housename . "%'";
            }
            $sql = 'SELECT a.*, b.`name` as `housename` FROM ' . tablename('adgreenlng_customer') . ' AS a, ' . tablename('adgreenlng_house') . ' AS b';
            $sql .= $condition . " ORDER BY a.`dateline` DESC";
            $partners = array();
            $list = pdo_fetchall($sql);
            if ($list) {
                foreach ($list as &$c) {
                    $id = $c['partnerid'];
                    if ($id) {
                        if (!isset($partners[$id])) {
                            $partners[$id] = adgreenlng_partner_fetch_by_id($id);
                        }
                        $c['partner_realname'] = $partners[$id]['realname'];
                    }

                    $pid = $c['recommendpid'];
                    if (!isset($partners[$pid])) {
                        $partners[$pid] = adgreenlng_partner_fetch_by_id($pid);
                    }
                    $c['recommend_realname'] = $partners[$pid]['realname'];

                    $c['status'] = $customer_status[$c['laststatusid']]['title'];
                    unset($c);
                }
            }

            require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel.php';
            require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel/IOFactory.php';
            require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel/Writer/Excel5.php';

            $resultPHPExcel = new PHPExcel();
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                        'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                        //'color' => array('argb' => 'FFFF0000'),
                    ),
                ),
            );
            $style_fill = array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('argb' => '0xFFFF00')
                ),
            );

            $resultPHPExcel->getActiveSheet()->getStyle('A1:F1')->applyFromArray(($styleArray + $style_fill));
            $resultPHPExcel->getActiveSheet()->setCellValue('A1', '姓名');
            $resultPHPExcel->getActiveSheet()->setCellValue('B1', '意向楼盘');
            $resultPHPExcel->getActiveSheet()->setCellValue('C1', '手机号');
            $resultPHPExcel->getActiveSheet()->setCellValue('D1', '经纪人');
            $resultPHPExcel->getActiveSheet()->setCellValue('E1', '推荐人');
            $resultPHPExcel->getActiveSheet()->setCellValue('F1', '状态');
            $resultPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
            $i = 2;
            foreach ($list as $item) {
                $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['realname']);
                $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['housename']);
                $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['mobile']);
                $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['partner_realname']);
                $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['recommend_realname']);
                $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['status']);
                $resultPHPExcel->getActiveSheet()->getStyle('A' . $i . ':F' . $i)->applyFromArray($styleArray);
                $i++;
            }
            $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, '总人数：' . count($list) . '人');
            $resultPHPExcel->getActiveSheet()->getStyle('A' . $i)->applyFromArray(array('font' => array('bold' => true)));


            $outputFileName = 'total.xls';
            $xlsWriter = new PHPExcel_Writer_Excel5($resultPHPExcel);
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header('Content-Disposition:inline;filename="' . $outputFileName . '"');
            header("Content-Transfer-Encoding: binary");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            $xlsWriter->save("php://output");
        }

        include $this->template('web/customer');
    }

    /*private function is_last_statusid($statusid) {
        global $_W;
        $sql = "SELECT id FROM ".tablename('adgreenlng_customer_status')." WHERE uniacid=:uniacid ORDER BY displayorder DESC LIMIT 1";
        $params = array(
            ':uniacid' => $_W['uniacid'],
        );
        $id = pdo_fetchcolumn($sql, $params);
        if ($id == $statusid) {
            return true;
        }
        return false;
    }*/
}

$obj = new Ad_greenlng_doWebCustomer;
$obj->exec();
