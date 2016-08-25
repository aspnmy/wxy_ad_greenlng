<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileMyfriend extends Ad_greenlngModuleSite
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
        $myuid = $_W['member']['uid'];
        $friends = array();
        $pindex = max(1, intval($_GPC['page']));
        $pagesize = 10;
        $start = ($pindex - 1) * $pagesize;
        $condition = ' WHERE `uniacid` = ' . $_W['uniacid'] . ' AND (`uid` = ' . $myuid . ')';
        $sql = "SELECT COUNT(`id`) FROM " . tablename('adgreenlng_partner') . $condition;
        $total = pdo_fetchcolumn($sql);
        $pager = pagination($total, $pindex, $pagesize);
        $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner');
        $sql .= $condition . " ORDER BY `createtime` DESC LIMIT $start, $pagesize";
        $partners = pdo_fetchall($sql);
        $friends = array();
        foreach ($partners as $p) {
            $f = array();
            $f['uid'] = ($p['uid'] == $myuid) ? $p['subuid'] : $p['uid'];
            $f['createtime'] = date('Y-m-d H:i:s', $p['createtime']);
            $friends[$f['uid']] = $f;
        }
        $uids = array_keys($friends);
        $friend_info = mc_fetch($uids, array('avatar', 'nickname'));
        foreach ($friends as &$f) {
            $f['nickname'] = $friend_info[$f['uid']]['nickname'];
            $f['avatar'] = $friend_info[$f['uid']]['avatar'];
            unset($f);
        }

        include $this->template('myfriend');
    }
}

$obj = new Ad_greenlng_doMobileMyfriend;
$obj->exec();