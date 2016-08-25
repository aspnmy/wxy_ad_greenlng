<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileRank extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_W, $_GPC;
        $_share = $this->_share;
        $pindex = max(1, intval($_GPC['page']));
        $pagesize = 10;
        $start = ($pindex - 1) * $pagesize;
        $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_commission');
        $sql .= " WHERE `uniacid` = " . $_W['uniacid'];
        $total = pdo_fetchcolumn($sql);
        $pager = pagination($total, $pindex, $pagesize);
        $sql = "SELECT * FROM " . tablename('adgreenlng_commission');
        $sql .= " WHERE `uniacid` = " . $_W['uniacid'];
        $sql .= " ORDER BY `commission` DESC LIMIT $start, $pagesize";
        $list = pdo_fetchall($sql, array(), 'uid');
        if (!empty($list)) {
            $uids = array_keys($list);
            $members = mc_fetch($uids, array('avatar', 'nickname'));
            $i = 0;
            foreach ($list as $k => &$v) {
                $v['username'] = $members[$k]['username'];
                $v['avatar'] = $members[$k]['avatar'];
                $v['index'] = $start + $i + 1;
                $i++;
                unset($v);
            }
        }
        $pager = pagination($total, $pindex, $pagesize);
        include $this->template('rank');
    }
}

$obj = new Ad_greenlng_doMobileRank;
$obj->exec();
