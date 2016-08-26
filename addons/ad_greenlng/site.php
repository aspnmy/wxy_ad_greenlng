<?php


defined('IN_IA') or die('Access Denied');
require IA_ROOT . '/addons/ad_greenlng/common.func.php';
require IA_ROOT . '/addons/ad_greenlng/model.func.php';
define('SUPERMAN_BAIDU_MAP_AK', 'FpNN8295GdYVL7gpGIjGPDGZ');

class Ad_greenlngModuleSite extends WeModuleSite
{
    protected $modules_bindings = array();
    protected $navigation = array();

    public function __construct($allowInit = false)
    {
        if (!$allowInit) {
            return;
        }
        global $_GPC, $_W, $do;
        load()->func('tpl');
        load()->func('file');
        load()->model('mc');
        load()->model('module');
        $this->uniacid = $_W['uniacid'];
        $this->modulename = 'ad_greenlng';
        $this->module = module_fetch($this->modulename);
        $this->__define = IA_ROOT . "/addons/{$this->modulename}/module.php";
        $this->inMobile = defined('IN_MOBILE');
        if (!isset($_GPC['do']) && isset($_GPC['eid']) && $_GPC['eid']) {
            $eid = intval($_GPC['eid']);
            $sql = "SELECT `do` FROM " . tablename('modules_bindings') . " WHERE eid=:eid";
            $params = array(':eid' => $eid);
            $do = pdo_fetchcolumn($sql, $params);
        }
        if ($this->inMobile) {
            $this->_share = array();
            if (isset($this->module['config']['base']['share'])) {
                $share_params = $this->module['config']['base']['share'];
                $this->_share = array('title' => $share_params['title'], 'link' => $_W['siteurl'], 'imgUrl' => tomedia($share_params['imgurl']), 'content' => $share_params['desc']);
                unset($share_params);
            }
            $filter = array('uniacid' => $_W['uniacid'], 'isshow' => 1);
            $nav_data = adgreenlng_navigation_fetchall($filter, '', 0, -1);
            if (!$nav_data) {
                $nav_data = adgreenlng_navigation_data($this->module['config']);
            }
            foreach ($nav_data as &$v) {
                if ($v['title'] == '' || $v['url'] == '' || !$v['isshow']) {
                    continue;
                }
                $v['active'] = false;
                $url = str_replace('./', '/', $v['url']);
                $url = str_replace('//', '/', $url);
                if (strexists($_W['siteurl'], $url)) {
                    $v['active'] = true;
                }
                $this->navigation[] = $v;
            }
            if ($_W['member']['uid']) {
                $_W['member'] = array_merge($_W['member'], mc_fetch($_W['member']['uid'], array('nickname', 'avatar')));
                $data = array();
                if (!empty($_W['fans'])) {
                    if (empty($_W['member']['nickname'])) {
                        $data['nickname'] = $_W['fans']['tag']['nickname'];
                    }
                    if (empty($_W['member']['avatar'])) {
                        $data['avatar'] = $_W['fans']['tag']['headimgurl'] ? $_W['fans']['tag']['headimgurl'] : $_W['fans']['tag']['avatar'];
                    }
                } else {
                    $fan = mc_fansinfo($_W['member']['uid']);
                    if ($fan) {
                        if (empty($_W['member']['nickname'])) {
                            $data['nickname'] = $fan['tag']['nickname'];
                        }
                        if (empty($_W['member']['avatar'])) {
                            $data['avatar'] = $fan['tag']['headimgurl'] ? $fan['tag']['headimgurl'] : $fan['tag']['avatar'];
                        }
                    }
                }
                if (!empty($data)) {
                    pdo_update('mc_members', $data, array('uid' => $_W['member']['uid']));
                    $_W['member']['nickname'] = $data['nickname'];
                    $_W['member']['avatar'] = $data['avatar'];
                }
            }
        } else {
        }
    }

    public function checkauth()
    {
        global $_W, $_GPC;
        if (!$this->module['config']['base']['guide_subscribe_open']) {
            checkauth();
        }
        if (!empty($_W['member']) && (!empty($_W['member']['mobile']) || !empty($_W['member']['email']))) {
            return true;
        }
        if (!empty($_W['openid'])) {
            $fan = mc_fansinfo($_W['openid']);
            if (_mc_login(array('uid' => intval($fan['uid'])))) {
                return true;
            }
        }
        if (!empty($this->module['config']['base']['guide_subscribe_content'])) {
            message('您还未登录，请登录后继续操作！', $this->createMobileUrl('guide'), 'info');
        } else {
            if (!empty($_W['account']['subscribeurl'])) {
                message('您还未登录，请登录后继续操作！', $_W['account']['subscribeurl'], 'info');
            } else {
                echo '您还未关注公众号，请关注后，继续操作。<br><br>关注方法：微信=》添加朋友=》公众号=》搜索 "' . $_W['account']['name'] . '"';
            }
        }
        die;
    }

    public function payResult($params)
    {
        global $_W, $_GPC;
        $ordid = $params['tid'];
        $data = array('status' => $params['result'] == 'success' ? 1 : 0);
        $paytype = array('credit' => '1', 'wechat' => '2', 'alipay' => '2', 'delivery' => '3');
        if (!empty($params['is_usecard'])) {
            $cardType = array('1' => '微信卡券', '2' => '系统代金券');
            $data['paydetail'] = '使用' . $cardType[$params['card_type']] . '支付了' . ($params['fee'] - $params['card_fee']);
            $data['paydetail'] .= '元，实际支付了' . $params['card_fee'] . '元。';
        }
        $data['paytype'] = $paytype[$params['type']];
        if ($params['type'] == 'wechat') {
            $data['transid'] = $params['tag']['transaction_id'];
        }
        $data['paytime'] = TIMESTAMP;
        $ret = pdo_update('adgreenlng_house_order', $data, array('ordid' => $ordid));
        if ($ret === false) {
            WeUtility::logging('fatal', '[ad_greenlng] 订单状态更新失败, ordid=' . $ordid . ', data=' . var_export($data, true));
            message('订单状态更新失败，请联系管理员', '', 'error');
        }
        if ($params['result'] == 'success') {
            if ($params['from'] == 'return') {
                $setting = uni_setting($_W['uniacid'], array('creditbehaviors'));
                $credit = $setting['creditbehaviors']['currency'];
                if ($params['type'] == $credit) {
                    message('支付成功！', $this->createMobileUrl('myorder', array('status' => 1)), 'success');
                } else {
                    message('支付成功！', '../../app/' . $this->createMobileUrl('myorder', array('status' => 1)), 'success');
                }
            }
        }
    }

    /**
     * @param $message_info
     * @return array|bool|null|WeiXinAccount|WeiXinPlatform|YiXinAccount
     */
    public function sendTemplateMessage($message_info)
    {
        global $_W;
        $template_id = $message_info['template_id'];
        $template_variable = $message_info['template_variable'];
        if (!$_W['acid']) {
            $accounts = uni_accounts();
            foreach ($accounts as $k => $v) {
                $_W['account'] = $v;
                $_W['acid'] = $_W['account']['acid'];
                break;
            }
        }
        if (!$_W['uniacid']) {
            $_W['uniacid'] = $message_info['uniacid'];
        }
        if (!isset($message_info['openid'])) {
            $fans = mc_fansinfo($message_info['receiver_uid']);
            if (!$fans) {
                WeUtility::logging("warning", "sendTemplateMessage failed: 没有找到粉丝信息, uid={$message_info['receiver_uid']}");
                return false;
            }
            if (!$fans['follow']) {
                WeUtility::logging("warning", "sendTemplateMessage failed: 粉丝已取消关注, fans=" . var_export($fans, true));
                return false;
            }
            $message_info['openid'] = $fans['openid'];
        }
        $account = $this->initAccount();
        if (is_error($account)) {
            WeUtility::logging('fatal', 'sendTemplateMessage failed: account=' . var_export($account, true));
            return $account;
        }
        $message = array('template_id' => $template_id, 'postdata' => array(), 'url' => $message_info['url'], 'topcolor' => '#008000');
        $template_variable = explode("\n", $template_variable);
        foreach ($template_variable as $line) {
            $arr = explode("=", trim($line));
            $message['postdata'][trim($arr[0])] = ['value' => adgreenlng_replace_variable(trim($arr[1]), $message_info['vars']), 'color' => '#173177'];
        }
        $ret = $account->sendTplNotice($message_info['openid'], $message['template_id'], $message['postdata'], $message['url'], $message['topcolor']);
        if ($ret !== true) {
            WeUtility::logging("fatal", "sendTemplateMessage failed: openid={$message_info['openid']}, ret=" . var_export($ret, true) . ", message=" . var_export($message, true));
        } else {
        }
        return true;
    }

    public function initAccount()
    {
        global $_W;
        static $account = null;
        if (!is_null($account)) {
            return $account;
        }
        if (empty($_W['account'])) {
            $_W['account'] = uni_fetch($_W['uniacid']);
        }
        if (empty($_W['account'])) {
            return error(-1, '创建公众号操作类失败');
        }
        if ($_W['account']['level'] < 3) {
            return error(-1, '公众号没有经过认证');
        }
        $account = WeAccount::create();
        if (is_null($account)) {
            return error(-1, '创建公众号操作对象失败');
        }
        return $account;
    }

    public function sendCustomerStatusNotice($openid, $changername, $receivername, $customername, $status_title, $url = '', $update_time = TIMESTAMP, $remark = '', $money = 0)
    {
        global $_W;
        $account = $this->initAccount();
        if (is_error($account)) {
            WeUtility::logging('fatal', 'sendCustomerStatusNotice failed: account=' . var_export($account, true));
            return $account;
        }
        $update_time = date('Y-m-d H:i:s', $update_time);
        $text = "{$receivername} 您好，客户 {$customername} 的状态已被 {$changername} 变更为 ";
        $text .= "{$status_title}\n";
        if (!empty($remark)) {
            $text .= "备注：{$remark}\n";
        }
        $text .= "佣金：{$money}\n";
        $text .= "{$update_time}";
        $message = array('msgtype' => 'news', 'news' => array('articles' => array(array('title' => urlencode('客户状态变更通知'), 'description' => urlencode($text), 'url' => urlencode($url), 'picurl' => ''))), 'touser' => $openid);
        $result = $account->sendCustomNotice($message);
        if (is_error($result)) {
            WeUtility::logging('fatal', 'sendCustomerStatusNotice failed: result=' . var_export($result, true));
        }
        return $result;
    }

    public function sendRecommendNotice($openid, $url, $housename, $recommendname, $customername, $remark)
    {
        global $_W;
        $account = $this->initAccount();
        if (is_error($account)) {
            WeUtility::logging('fatal', 'sendCustomerStatusNotice failed: account=' . var_export($account, true));
            return $account;
        }
        $text = '您好，【' . $housename . "】有新客户\n";
        $text .= "推荐人：{$recommendname}\n";
        $text .= "被推荐人：{$customername}\n";
        $text .= "推荐时间：{$remark}\n";
        $message = array('msgtype' => 'news', 'news' => array('articles' => array(array('title' => urlencode('新客户通知'), 'description' => urlencode($text), 'url' => urlencode($url), 'picurl' => ''))), 'touser' => $openid);
        $result = $account->sendCustomNotice($message);
        if (is_error($result)) {
            WeUtility::logging('fatal', 'sendCustomerStatusNotice failed: result=' . var_export($result, true));
        }
        return $result;
    }

    public function sendDistributeNotice($openid, $url, $housename, $customername, $updatetime)
    {
        global $_W;
        $account = $this->initAccount();
        if (is_error($account)) {
            WeUtility::logging('fatal', 'sendCustomerStatusNotice failed: account=' . var_export($account, true));
            return $account;
        }
        $text = '您好，【' . $housename . "】有新客户\n";
        $text .= '任务名称：跟踪客户【' . $customername . "】\n";
        $text .= "更新内容：分配新客户\n";
        $text .= "更新时间：{$updatetime}";
        $message = array('msgtype' => 'news', 'news' => array('articles' => array(array('title' => urlencode('任务更新通知'), 'description' => urlencode($text), 'url' => urlencode($url), 'picurl' => ''))), 'touser' => $openid);
        $result = $account->sendCustomNotice($message);
        if (is_error($result)) {
            WeUtility::logging('fatal', 'sendDistributeNotice failed: result=' . var_export($result, true));
            WeUtility::logging('fatal', 'message' . var_export($message, true));
        }
        return $result;
    }
}