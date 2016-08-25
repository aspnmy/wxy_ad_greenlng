<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileMycash extends Ad_greenlngModuleSite
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
        if (empty($this->module['config']['getcash']['type'])) {
            message('管理员未设置提现参数类型', referer(), 'error');
        }
        $setting = uni_setting($_W['uniacid'], array('creditnames', 'creditbehaviors', 'uc', 'payment', 'passport'));
        $creditnames = $setting['creditnames'];
        $credits = mc_credit_fetch($_W['member']['uid'], '*');
        $credit = array(
            'title' => $creditnames[$this->module['config']['getcash']['type']]['title'],
            'value' => str_replace('.00', '', $credits[$this->module['config']['getcash']['type']]),
        );
        if (!$this->module['config']['getcash']['allow_repeat']) {
            $sql = "SELECT * FROM " . tablename('adgreenlng_cash_apply') . " WHERE uid=:uid AND status=0";
            $row = pdo_fetch($sql, array(':uid' => $_W['member']['uid']));
            if (!empty($row)) {
                message('您有正在处理中的提现申请，请耐心等待管理员处理', $this->createMobileUrl('cashlog'), 'error');
            }
        }
        if (checksubmit('submit')) {
            $money = (float)sprintf("%.2f", $_GPC['money']);
            $remark = $_GPC['remark'];
            if ($money <= 0 || $money > $credit['value']) {
                message('提现金额非法', '', 'error');
            }
            if ($this->module['config']['getcash']['min'] > 0 && ($this->module['config']['getcash']['min'] > $credit['value'])) {
                message('未达到提现要求，最低提现金额为：' . $this->module['config']['getcash']['min']);
            }
            if (strlen($remark) > 200) {
                message('备注内容不能超过200个字符！', '', 'error');
            }
            $data = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'from_user' => $_W['fans']['from_user'],
                'applypay' => $money,
                'remark' => $remark,
                'status' => 0,
                'createtime' => TIMESTAMP,
            );
            pdo_insert('adgreenlng_cash_apply', $data);
            $new_id = pdo_insertid();
            if (!$new_id) {
                message('保存提现数据错误，请稍后重试！', '', 'error');
            }
            message('提交成功，请您耐心等待管理员处理', $this->createMobileUrl('cashlog'), 'success');
        }

        include $this->template('mycash');
    }
}

$obj = new Ad_greenlng_doMobileMycash;
$obj->exec();
