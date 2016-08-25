<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileMybespeak extends Ad_greenlngModuleSite
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
        $op = $_GPC['op'] ? $_GPC['op'] : 'list';
        if (!in_array($op, array('form', 'list'))) {
            message('非法请求！(' . $op . ')');
        }
        $bespeak_starttime = date('Y-m-d', strtotime('+3 days'));
        if ($op == 'form') {
            if (checksubmit('submit')) {
                if (trim($_GPC['username'] == '') || trim($_GPC['phone'] == '')) {
                    message('姓名或手机号不能为空', referer(), 'error');
                }
                if ($_GPC['bespeaktime'] < date('Y-m-d H:i', time())) {
                    message('预约时间不能小于当前的时间', referer(), 'error');
                }
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_house_bespeak');
                $sql .= ' WHERE `uid` = ' . $_W['member']['uid'];
                $sql .= ' AND `houseid` = ' . $_GPC['houseid'];
                $item = pdo_fetch($sql);
                if (!empty($item)) {
                    $old_date = date('Ymd', $item['bespeaktime']);
                    $new_date = date('Ymd', strtotime($_GPC['bespeaktime']));
                    if ($old_date == $new_date) {
                        message('相同楼盘相同日期不能预约两次', referer(), 'error');
                    }
                }
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'uid' => $_W['member']['uid'],
                    'houseid' => $_GPC['houseid'],
                    'username' => trim($_GPC['username']),
                    'phone' => trim($_GPC['phone']),
                    'remark' => trim($_GPC['remark']),
                    'bespeaktime' => strtotime($_GPC['bespeaktime']),
                    'status' => 1,
                    'createtime' => TIMESTAMP,
                    'updatetime' => TIMESTAMP,
                );
                pdo_insert('adgreenlng_house_bespeak', $data);
                $new_id = pdo_insertid();
                if (!$new_id) {
                    message('系统错误，请稍后重试', referer(), 'error');
                }
                message('提交成功，请等待管理员审核...', $this->createMobileUrl('house', array('act' => 'detail', 'id' => $_GPC['houseid'])), 'success');
                // message('提交成功，请等待管理员审核...', $this->createMobileUrl('mybespeak', array('id' => $_GPC['id'],'op' => 'list')), 'success');
            }
        }
        if ($op == 'list') {
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 5;
            $start = ($pindex - 1) * $pagesize;
            $condition = ' WHERE `uniacid` = ' . $_W['uniacid'];
            $condition .= ' AND `uid` = ' . $_W['member']['uid'];
            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_house_bespeak') . $condition;
            $total = pdo_fetchcolumn($sql);

            $pager = pagination($total, $pindex, $pagesize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));

            $sql = "SELECT * FROM " . tablename('adgreenlng_house_bespeak');
            $sql .= $condition . " ORDER BY `createtime` DESC LIMIT $start, $pagesize";
            $list = pdo_fetchall($sql);
            if (!empty($list)) {
                $houseids = array();
                foreach ($list as &$be) {
                    $sql = 'SELECT * FROM ' . tablename('adgreenlng_house') . ' WHERE `id` = ' . $be['houseid'];
                    $house = pdo_fetch($sql);
                    if (!empty($house)) {
                        $be['housename'] = $house['name'];
                        $be['bespeaktime'] = date('Y-m-d H:i', $be['bespeaktime']);
                    }
                    unset($be);
                }
            }
        }
        include $this->template('mybespeak');
    }
}

$obj = new Ad_greenlng_doMobileMybespeak;
$obj->exec();
