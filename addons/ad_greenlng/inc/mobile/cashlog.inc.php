<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileCashlog extends Ad_greenlngModuleSite
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
        $birth = array(
            'start' => date('Y-m-d', time() - 60 * 60 * 24 * 30),
            'end' => date('Y-m-d'),
        );
        $uid = $_W['member']['uid'];
        if (intval($uid) > 0) {
            $partner = adgreenlng_partner_fetch_by_uid($uid);
            if (!$partner) {
                message('您未加入经纪人，请认真填写资料后提交审核！', $this->createMobileUrl('partner', array('act' => 'regist')), 'warning');
            }
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 10;
            $start = ($pindex - 1) * $pagesize;
            $condition = ' WHERE 1';
            $condition .= ' AND `uid` = ' . $uid;
            $condition .= ' AND `uniacid` = ' . $_W['uniacid'];
            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_cash_apply') . $condition;
            $total = pdo_fetchcolumn($sql);

            $pager = pagination($total, $pindex, $pagesize);

            $sql = "SELECT * FROM " . tablename('adgreenlng_cash_apply');
            $sql .= $condition . " ORDER BY `createtime` DESC LIMIT $start, $pagesize";
            $list = pdo_fetchall($sql);
            $info = mc_fetch($uid, array('avatar', 'nickname'));
        }
        include $this->template('cashlog');
    }
}

$obj = new Ad_greenlng_doMobileCashlog;
$obj->exec();
