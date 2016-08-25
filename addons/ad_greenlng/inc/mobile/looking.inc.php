<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileLooking extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_W, $_GPC, $do;
        $_share = $this->_share;
        if (!$this->module['config']['looking']['switch']) {
            message('看房团未开启！', referer(), 'warning');
        }
        $act = $_GPC['act'] ? $_GPC['act'] : 'list';
        if (!in_array($act, array('list', 'form'))) {
            message('非法请求！(' . $act . ')');
        }
        //看房团-列表
        if ($act == 'list') {
            $title = '看房团';
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 10;
            $start = ($pindex - 1) * $pagesize;
            $condition = ' WHERE 1';
            $condition .= ' AND `uniacid` = ' . $_W['uniacid'];
            $status = intval($_GPC['status']);
            $status = $status > 0 ? $status : 1;
            if ($status == 2) {
                $condition .= ' AND `regdeadline` < ' . TIMESTAMP;
            } else {
                $condition .= ' AND status!= 2 AND `regdeadline` > ' . TIMESTAMP;
            }

            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_looking') . $condition;
            $total = pdo_fetchcolumn($sql);

            $pager = pagination($total, $pindex, $pagesize);

            $sql = "SELECT * FROM " . tablename('adgreenlng_looking');
            $sql .= $condition . " ORDER BY `displayorder` DESC LIMIT $start, $pagesize";
            $list = pdo_fetchall($sql, array(), 'id');
            if (!empty($list)) {
                $look_ids = array_keys($list);
                if (!empty($look_ids)) {
                    $sql = "SELECT `lookid`, COUNT(`id`) AS `user_count` FROM " . tablename('adgreenlng_looking_users');
                    $sql .= " WHERE `lookid` IN (" . implode(',', $look_ids) . ") GROUP BY `lookid` ";
                    $user_counts = pdo_fetchall($sql, array(), 'lookid');

                    $house_table = tablename('adgreenlng_house');
                    $lookh_table = tablename('adgreenlng_looking_house');
                    foreach ($list as &$looking) {
                        $looking['slide'] = unserialize($looking['slide']);
                        $looking['slide'] = $looking['slide'][0] ? tomedia($looking['slide'][0]) : '';
                        if ($looking['status'] != 2) {
                            $looking['status'] = $looking['regdeadline'] < TIMESTAMP ? 2 : $looking['status'];
                        }
                        $looking['viewtime'] = date("Y-m-d", $looking['viewtime']);
                        $looking['user_count'] = intval($user_counts[$looking['id']]['user_count']);
                        $sql = "SELECT " . $house_table . ".* FROM " . $house_table . ", " . $lookh_table;
                        $sql .= " WHERE " . $lookh_table . ".`lookid` = " . $looking['id'];
                        $sql .= " AND " . $house_table . ".`id` = " . $lookh_table . ".`houseid` ";
                        $looking['house_info'] = pdo_fetchall($sql, array(), $house_table . '`id`');
                        if ($looking['house_info']) {
                            foreach ($looking['house_info'] as &$v) {
                                $v['price'] = adgreenlng_format_price($v['price'], true);
                                unset($v);
                            }
                        }
                        unset($looking);
                    }
                }
            }
            $pager = pagination($total, $pindex, $pagesize);
        }
        //看房团-报名
        if ($act == 'form') {
            $id = intval($_GPC['_id']);
            if ($id > 0) {
                $sql = "SELECT * FROM " . tablename('adgreenlng_looking') . ' WHERE `id` = ' . $id;
                $item = pdo_fetch($sql);
                if (!$item) {
                    message('看房团不存在或已删除', referer(), 'error');
                }
                $title = $item['name'];
                $pics_temp = unserialize($item['slide']);
                $pics = array();
                if ($pics_temp) {
                    foreach ($pics_temp as $pic) {
                        array_push($pics, tomedia($pic));
                    }
                    $item['slide'] = $pics;
                }
                $pindex = max(1, intval($_GPC['page']));
                $pagesize = 5;
                $start = ($pindex - 1) * $pagesize;
                $uids = array();
                $sql = "SELECT * FROM " . tablename('adgreenlng_looking_users');
                $sql .= " WHERE `lookid` = " . $id;
                $sql .= " ORDER BY `createtime` LIMIT {$start},{$pagesize}";
                $users = pdo_fetchall($sql);
                $item['user_count'] = 0;
                if ($users) {
                    foreach ($users as $u) {
                        $uids[] = $u['uid'];
                        $u['createtime'] = date('Y-m-d H:i:s', $u['createtime']);
                        unset($u);
                    }
                    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('adgreenlng_looking_users') . " WHERE lookid=:lookid", array(':lookid' => $id));
                    $pager = pagination($total, $pindex, $pagesize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
                    $members = mc_fetch($uids, array('avatar', 'nickname'));
                    $item['user_count'] = $total;
                }

                $countdown = $item['regdeadline'] - time();
                if ($countdown > 0) {
                    $day = floor($countdown / (24 * 60 * 60));
                    $hour = floor($countdown % (24 * 60 * 60) / (60 * 60));
                    $min = floor($countdown % (24 * 60) / 60);
                    $ctemp = $day > 0 ? $day . '天' : '';
                    $ctemp .= $hour > 0 ? $hour . '小时' : '';
                    $ctemp .= $min > 0 ? $min . '分钟' : '';
                    $item['countdown'] = $ctemp;
                }
                if ($item['status'] != 2) {
                    $item['status'] = $item['regdeadline'] < TIMESTAMP ? 2 : $item['status'];
                }
                $item['viewtime'] = date('Y-m-d H:i:s', $item['viewtime']);

                $house_table = tablename('adgreenlng_house');
                $lookh_table = tablename('adgreenlng_looking_house');
                $sql = "SELECT " . $house_table . ".* FROM " . $house_table . ", " . $lookh_table;
                $sql .= " WHERE " . $lookh_table . ".`lookid` = " . $id;
                $sql .= " AND " . $house_table . ".`id` = " . $lookh_table . ".`houseid` ";
                $house_info = pdo_fetchall($sql);
                foreach ($house_info as &$h) {
                    $h['coverimg'] = tomedia($h['coverimg']);
                    $h['credit'] = adgreenlng_format_price($h['credit']);
                    $h['price'] = adgreenlng_format_price($h['price'], true);
                    $h['commission'] = adgreenlng_format_price($h['commission']);
                    $h['deposit'] = adgreenlng_format_price($h['deposit']);
                    unset($h);
                }
                if (checksubmit('submit')) {
                    $this->checkauth();
                    if ($item['regdeadline'] < TIMESTAMP || $item['status'] == 2) {
                        message('看房团已过报名截止时间，欢迎参加其他进行中的看房团！', referer(), 'error');
                    }
                    $sql = "SELECT id FROM " . tablename('adgreenlng_looking_users') . " WHERE uid=:uid";
                    $sql .= " AND uniacid=:uniacid AND lookid=:lookid";
                    $param = array(':uid' => $_W['member']['uid'], ':uniacid' => $_W['uniacid'], ':lookid' => $_GPC['lookid']);
                    $row = pdo_fetch($sql, $param);
                    if ($row) {
                        message('您已报名参加该看房团', referer(), 'warning');
                    }
                    if (empty($_GPC['username'])) {
                        message('请填写您的姓名', '', 'error');
                    }
                    if (empty($_GPC['mobile'])) {
                        message('请填写您的电话', '', 'error');
                    }
                    $data = array(
                        'uniacid' => $_W['uniacid'],
                        'uid' => $_W['member']['uid'],
                        'lookid' => $_GPC['lookid'],
                        'username' => $_GPC['username'],
                        'phone' => $_GPC['mobile'],
                        'message' => $_GPC['message'],
                        'fellows' => $_GPC['fellows'],
                        'likehouse' => $_GPC['likehouse'] ? iserializer($_GPC['likehouse']) : '',
                        'status' => 1,
                        'createtime' => TIMESTAMP,
                        'updatetime' => TIMESTAMP,
                    );
                    pdo_insert('adgreenlng_looking_users', $data);
                    $new_id = pdo_insertid();
                    if ($new_id) {
                        message('提交成功,报名结果稍后将以微信提醒的方式提醒您，请耐心等待！', $this->createMobileUrl('looking', array('act' => 'form', '_id' => $id)), 'success');
                    } else {
                        message('系统错误，请稍后重试', referer(), 'error');
                    }
                }
            }
        }
        include $this->template('looking-' . $act);
    }
}

$obj = new Ad_greenlng_doMobileLooking;
$obj->exec();
