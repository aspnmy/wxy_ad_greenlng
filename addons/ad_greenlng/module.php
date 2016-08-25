<?php


defined('IN_IA') or die('Access Denied');
require IA_ROOT . '/addons/ad_greenlng/common.func.php';
require IA_ROOT . '/addons/ad_greenlng/model.func.php';

class Ad_greenlngModule extends WeModule
{
    private $_data = array();

    public function settingsDisplay($settings)
    {
        global $_W, $_GPC;
        load()->func('tpl');
        load()->func('file');
        $credits = adgreenlng_get_credits();
        if (!$this->module['config']['_init']) {
            $setting = uni_setting($_W['uniacid'], array('payment'));
            $pay = $setting['payment'];
            $accs = uni_accounts();
            $accounts = array();
            if (!empty($accs)) {
                foreach ($accs as $acc) {
                    if ($acc['type'] == '1' && $acc['level'] >= '3') {
                        $accounts[$acc['acid']] = array_elements(array('name', 'acid', 'key', 'secret'), $acc);
                    }
                }
                if ($pay && isset($pay['wechat']['account'])) {
                    $pay['wechat']['account_setting'] = $accounts[$pay['wechat']['account']];
                }
            }
            $this->_data = array('_init' => 1, 'base' => array('guide_subscribe_open' => 1, 'calculator_url' => 'http://m.db.house.qq.com/calculator/', 'notice_title' => '周周参加看房团，买房有优惠，还有免费礼品', 'notice_url' => ''), 'credit' => array('today_limit' => 100), 'getcash' => array('allow_repeat' => '0', 'min' => $this->module['config']['getcash']['min'] ? $this->module['config']['getcash']['min'] : '1', 'type' => $this->module['config']['getcash']['type'] ? $this->module['config']['getcash']['type'] : 'credit2', 'wxpay' => array('mch_appid' => $this->module['config']['getcash']['wxpay']['mch_appid'] ? $this->module['config']['getcash']['wxpay']['mch_appid'] : ($pay && isset($pay['wechat']['account_setting']['key']) ? $pay['wechat']['account_setting']['key'] : ''), 'mchid' => $this->module['config']['getcash']['wxpay']['mchid'] ? $this->module['config']['getcash']['wxpay']['mchid'] : ($pay && isset($pay['wechat']['mchid']) ? $pay['wechat']['mchid'] : '')), 'desc' => ''), 'partner' => array('reg_check' => '1', 'desc' => '经纪人介绍内容', 'agreement' => '经纪人协议内容', 'share' => array('title' => '哇，我在这里挖到宝藏了，赶快来抢啊！', 'imgUrl' => $_W['siteroot'] . 'addons/ad_greenlng/template/mobile/images/share_img.png', 'desc' => '史上最大优惠力度的房产平台，苹果电脑、手机、手表奖品统统送给您，现场还有明星美女助阵嗨翻天！！！'), 'customer' => array('template_id' => '', 'template_content' => '', 'template_variable' => '')), 'looking' => array('template_id' => '', 'template_content' => '', 'template_variable' => ''));
            $this->saveSettings($this->_data);
            load()->model('module');
            $this->module = module_fetch('ad_greenlng');
        } else {
            $this->_data = array('_init' => 1, 'base' => $this->module['config']['base'], 'credit' => $this->module['config']['credit'], 'getcash' => $this->module['config']['getcash'], 'partner' => $this->module['config']['partner'], 'looking' => $this->module['config']['looking']);
        }
        if (checksubmit('submit')) {
            $this->_setting_base();
            $this->_setting_credit();
            $this->_setting_getcash();
            $this->_setting_partner();
            $this->_setting_looking();
            $this->saveSettings($this->_data);
            message('更新成功！', referer(), 'success');
        }
        include $this->template('web/setting');
    }

    private function _setting_base()
    {
        global $_W, $_GPC;
        $this->_data['base'] = $_GPC['base'];
    }

    private function _setting_credit()
    {
        global $_W, $_GPC;
        $this->_data['credit'] = $_GPC['credit'];
    }

    private function _setting_getcash()
    {
        global $_W, $_GPC;
        $getcash = $_GPC['getcash'];
        if ($getcash['min'] < 0) {
            message('最低提现金额填写错误', '', 'error');
        }
        $this->_data['getcash'] = $getcash;
        $wxpay = $_GPC['getcash']['wxpay'];
        $apiclient_cert_path = ad_greenlng_getpath($wxpay['apiclient_cert']);
        $apiclient_key_path = ad_greenlng_getpath($wxpay['apiclient_key']);
        $rootca_path = ad_greenlng_getpath($wxpay['rootca']);
        if ($wxpay['del_apiclient_cert'] && file_exists($apiclient_cert_path)) {
            @unlink($apiclient_cert_path);
        }
        if ($wxpay['del_apiclient_key'] && file_exists($apiclient_key_path)) {
            @unlink($apiclient_key_path);
        }
        if ($wxpay['del_rootca'] && file_exists($rootca_path)) {
            @unlink($rootca_path);
        }
        $_W['setting']['upload']['image']['limit'] = 5000;
        $_W['setting']['upload']['image']['extentions'][] = 'pem';
        if (!empty($_FILES['getcash']['tmp_name']['wxpay']['apiclient_cert'])) {
            $file = array('name' => $_FILES['getcash']['name']['wxpay']['apiclient_cert'], 'tmp_name' => $_FILES['getcash']['tmp_name']['wxpay']['apiclient_cert'], 'type' => $_FILES['getcash']['type']['wxpay']['apiclient_cert'], 'error' => $_FILES['getcash']['error']['wxpay']['apiclient_cert'], 'size' => $_FILES['getcash']['size']['wxpay']['apiclient_cert']);
            $upload = file_upload($file, 'image', "apiclient_cert_" . random(6));
            if (!$upload['success']) {
                message($upload['errno'] . ':' . $upload['message']);
            }
            if (file_exists($apiclient_cert_path)) {
                @unlink($apiclient_cert_path);
            }
            $wxpay['apiclient_cert'] = $upload['path'];
        } else {
            $wxpay['apiclient_cert'] = $this->module['config']['getcash']['wxpay']['apiclient_cert'];
        }
        if (!empty($_FILES['getcash']['tmp_name']['wxpay']['apiclient_key'])) {
            $file = array('name' => $_FILES['getcash']['name']['wxpay']['apiclient_key'], 'tmp_name' => $_FILES['getcash']['tmp_name']['wxpay']['apiclient_key'], 'type' => $_FILES['getcash']['type']['wxpay']['apiclient_key'], 'error' => $_FILES['getcash']['error']['wxpay']['apiclient_key'], 'size' => $_FILES['getcash']['size']['wxpay']['apiclient_key']);
            $upload = file_upload($file, 'image', "apiclient_key_" . random(6));
            if (!$upload['success']) {
                message($upload['errno'] . ':' . $upload['message']);
            }
            if (file_exists($apiclient_key_path)) {
                @unlink($apiclient_key_path);
            }
            $wxpay['apiclient_key'] = $upload['path'];
        } else {
            $wxpay['apiclient_key'] = $this->module['config']['getcash']['wxpay']['apiclient_key'];
        }
        if (!empty($_FILES['getcash']['tmp_name']['wxpay']['rootca'])) {
            $file = array('name' => $_FILES['getcash']['name']['wxpay']['rootca'], 'tmp_name' => $_FILES['getcash']['tmp_name']['wxpay']['rootca'], 'type' => $_FILES['getcash']['type']['wxpay']['rootca'], 'error' => $_FILES['getcash']['error']['wxpay']['rootca'], 'size' => $_FILES['getcash']['size']['wxpay']['rootca']);
            $upload = file_upload($file, 'image', "rootca_" . random(6));
            if (!$upload['success']) {
                message($upload['errno'] . ':' . $upload['message']);
            }
            if (file_exists($rootca_path)) {
                @unlink($rootca_path);
            }
            $wxpay['rootca'] = $upload['path'];
        } else {
            $wxpay['rootca'] = $this->module['config']['getcash']['wxpay']['rootca'];
        }
        $this->_data['getcash']['wxpay'] = $wxpay;
    }

    private function _setting_partner()
    {
        global $_W, $_GPC;
        $partner = $_GPC['partner'];
        $this->_data['partner'] = $partner;
    }

    private function _setting_looking()
    {
        global $_W, $_GPC;
        pdo_update('adgreenlng_navigation', array('isshow' => $_GPC['looking']['switch'] ? 1 : 0), array('title' => '看房团'));
        $this->_data['looking'] = $_GPC['looking'];
    }
}