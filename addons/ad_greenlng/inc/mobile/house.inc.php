<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url
 */
defined('IN_IA') or exit('Access Denied');
require_once IA_ROOT . '/addons/ad_greenlng/common.func.php';

class Ad_greenlng_doMobileHouse extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_W, $_GPC;
        $_share = $this->_share;
        $act = in_array($_GPC['act'], array('list', 'detail', 'layout', 'view', 'share')) ? $_GPC['act'] : 'detail';
        if ($act == 'list') {
            $title = '区域报价列表';
            $all_types = ad_greenlng_type();
            foreach ($all_types as $v) {
                $filter = array(
                    'uniacid' => $_W['uniacid'],
                    'type' => $v['id'],
                    'isshow' => 1
                );
                $house_params[$v['title']] = array(
                    'name' => $v['name'],
                    'values' => ad_greenlng_type_fetchall($filter, '', '', -1, 'id')
                );
            }
            $house_params['district']['name'] = '地区';
            if (isset($this->module['config']['base']['default_reside']['city'])) {
                $city = $this->module['config']['base']['default_reside']['city'];
                if ($city) {
                    $sql = "SELECT DISTINCT(district) FROM " . tablename('adgreenlng_house') . " WHERE city=:city";
                    $params = array(
                        ':city' => $city,
                    );
                    $rows = pdo_fetchall($sql, $params);
                    if ($rows) {
                        foreach ($rows as $k => $v) {
                            $house_params['district']['values'][++$k] = $v['district'];
                        }
                    }
                }
            }
            ksort($house_params);
            //print_r($house_params);
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 5;
            $start = ($pindex - 1) * $pagesize;
            $condition = ' WHERE 1';
            $list_params = array('act' => 'list');
            $pricetype = intval($_GPC['pricetype']);
            if ($pricetype > 0 && array_key_exists($pricetype, $house_params['pricetype']['values'])) {
                $condition .= ' AND `pricetype` = ' . $pricetype;
                $list_params['pricetype'] = $pricetype;
            }
            $specialtype = intval($_GPC['specialtype']);
            if ($specialtype > 0 && array_key_exists($specialtype, $house_params['specialtype']['values'])) {
                $condition .= ' AND `specialtype` = ' . $specialtype;
                $list_params['specialtype'] = $specialtype;
            }
            $housetype = intval($_GPC['housetype']);
            if ($housetype > 0 && array_key_exists($housetype, $house_params['housetype']['values'])) {
                $condition .= ' AND `housetype` = ' . $housetype;
                $list_params['housetype'] = $housetype;
            }
            $layouttype = intval($_GPC['layouttype']);
            if ($layouttype > 0 && array_key_exists($layouttype, $house_params['layouttype']['values'])) {
                $condition .= ' AND `layouttype` = ' . $layouttype;
                $list_params['layouttype'] = $layouttype;
            }
            $district = trim($_GPC['district']);
            if ($district != '' && array_key_exists($district, $house_params['district']['values'])) {
                $condition .= " AND `district` = '{$house_params['district']['values'][$district]}'";
                $list_params['district'] = $district;
            }
            $condition .= ' AND `uniacid` = ' . $_W['uniacid'];
            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_house') . $condition;
            //echo $sql;
            $total = pdo_fetchcolumn($sql);
            $pager = pagination($total, $pindex, $pagesize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
            $sql = "SELECT * FROM " . tablename('adgreenlng_house');
            $sql .= $condition . " ORDER BY `displayorder` DESC,`createtime` DESC LIMIT $start, $pagesize";
            $list = pdo_fetchall($sql);
            if ($list) {
                foreach ($list as &$p) {
                    ad_greenlng_set($p);
                }
                unset($p);
            }
            if ($_W['isajax']) {
                die(json_encode($list));
            }
        } else if ($act == 'detail') {
            $setting = uni_setting($_W['uniacid'], array('payment'));
            $payment = $setting['payment'];
            $id = intval($_GPC['id']);
            $sql = "SELECT * FROM " . tablename('adgreenlng_house') . ' WHERE `id` = ' . $id;
            $item = pdo_fetch($sql);
            if (!$item) {
                message('区域报价不存在或已删除', referer(), 'error');
            }
            ad_greenlng_set($item);
            $title = $item['name'];
            //print_r($item);
            //var_dump($item['opentime']);
            $_share = array(
                'title' => $item['name'],
                'link' => $_W['siteurl'],
                'imgUrl' => tomedia($item['coverimg']),
                'content' => cutstr(strip_tags(htmlspecialchars_decode($item['description'])), 30),
            );
            $myuid = $_W['member']['uid'];
            if ($myuid > 0) {
                $_share['link'] = $_W['siteroot'] . 'app/' . $this->createMobileUrl('house', array('act' => 'detail', 'fromid' => $myuid, 'id' => $id));
            }
            $partner = adgreenlng_partner_fetch_by_uid($myuid);
            if ($partner) {
                $partner['yes'] = true;
            }

            //分享赚积分
            if (isset($_GPC['fromid']) && $item['credit'] > 0 && $_W['container'] == 'wechat') {
                $this->update_share($_GPC['fromid'], $item['credit'], $item['credit_type'], $item['id']);
            }

            /*
            $fromid = isset($_GPC['fromid'])?intval($_GPC['fromid']):$_GPC['__fromid'];
            $is_shared = intval($_GPC['__housedetailshared']);
            if($fromid > 0 && $is_shared != 1){
                $from_info = mc_fetch($fromid, array('avatar', 'nickname'));
                if($from_info){
                    $sql = 'UPDATE '.tablename('adgreenlng_house').' SET `sharecount` = `sharecount` + 1';
                    $sql .= ' WHERE `id` = '.$id;
                    pdo_query($sql);
                    $credit = array(
                        'type' => $this->module['config']['credit']['type'],
                        'value' => $item['credit']?$item['credit']:$this->module['config']['credit']['share_house'],
                        'limit' => $this->module['config']['credit']['today_limit'],
                    );
                    $friend_uid = $_W['member']['uid'];
                    adgreenlng_share_add_credit($_W['uniacid'], $fromid, 'housedetailshare', '__housedetailshared', $credit, $friend_uid);
                }
            }
             */
            /*$item['credit'] = adgreenlng_format_price($item['credit']);
            $item['price'] = adgreenlng_format_price($item['price']);
            $item['commission'] = adgreenlng_format_price($item['commission']);
            $item['deposit'] = adgreenlng_format_price($item['deposit']);
            $item['coverimg'] = tomedia($item['coverimg']);
            $pics_temp = unserialize($item['descimgs']);
            $pics = array();
            if($pics_temp){
                foreach($pics_temp as $pic){
                    array_push($pics, tomedia($pic));
                }
                $item['descimgs'] = $pics;
            }
            $pics_temp = unserialize($item['layoutimgs']);
            $pics = array();
            if($pics_temp){
                foreach($pics_temp as $pic){
                    array_push($pics, tomedia($pic));
                }
                $item['layoutimgs'] = $pics;
            }
            $item['opentime'] = $item['opentime'] < 1 ? date('Y年m月d日') : date('Y年m月d日', $item['opentime']);
            */

            $sql = 'SELECT * FROM ' . tablename('adgreenlng_house_kv') . ' WHERE `houseid` = ' . $id . ' ORDER BY displayorder ASC';
            $item['kvs'] = pdo_fetchall($sql);
            $sql = 'SELECT * FROM ' . tablename('adgreenlng_layout') . ' WHERE `houseid` = ' . $id;
            $item['layouts'] = pdo_fetchall($sql);
            foreach ($item['layouts'] as &$ly) {
                $ly['img'] = tomedia($ly['img']);
                $ly['tags'] = explode(' ', $ly['tag']);
                unset($ly);
            }

            $item['look_info'] = array();
            $look_table = tablename('adgreenlng_looking');
            $lhouse_table = tablename('adgreenlng_looking_house');
            $sql = 'SELECT ' . $look_table . '.* FROM ' . $look_table . ', ' . $lhouse_table;
            $sql .= ' WHERE ' . $lhouse_table . '.`houseid` = ' . $id;
            $sql .= ' AND ' . $lhouse_table . '.`lookid` = ' . $look_table . '.`id`';
            $sql .= ' AND ' . $look_table . '.`status` = 1';
            $look_info = pdo_fetch($sql);
            if (!empty($look_info)) {
                $week_info = array(1 => '星期一', 2 => '星期二', 3 => '星期三', 4 => '星期四', 5 => '星期五', 6 => '星期六', 7 => '星期日',);
                $look_info['week'] = $week_info[date('N', $look_info['viewtime'])];
                $look_info['viewtime'] = date('m月d日', $look_info['viewtime']);
                $sql = 'SELECT COUNT(`id`) FROM ' . tablename('adgreenlng_looking_users') . ' WHERE `lookid` = ' . $look_info['id'];
                $look_info['user_count'] = pdo_fetchcolumn($sql);
                $sql = 'SELECT ' . tablename('adgreenlng_house') . '.`name` FROM ';
                $sql .= tablename('adgreenlng_house') . ', ' . tablename('adgreenlng_looking_house');
                $sql .= ' WHERE ' . tablename('adgreenlng_looking_house') . '.`houseid` = ';
                $sql .= tablename('adgreenlng_house') . '.`id`';
                $sql .= ' AND ' . tablename('adgreenlng_looking_house') . '.`lookid` = ' . $look_info['id'];
                $sql .= ' AND ' . tablename('adgreenlng_house') . '.`id` != ' . $id;
                $look_info['house_names'] = pdo_fetchall($sql);
                $item['look_info'] = $look_info;
            }
        } else if ($act == 'layout') {
            $id = intval($_GPC['id']);
            if ($id <= 0) {
                message('非法参数！', referer(), 'error');
            }
            $sql = 'SELECT * FROM ' . tablename('adgreenlng_layout') . ' WHERE houseid=:houseid';
            $params = array(
                ':houseid' => $id,
            );
            $list = pdo_fetchall($sql, $params);
            foreach ($list as &$item) {
                $item['img'] = tomedia($item['img']);
                $item['tags'] = explode(' ', $item['tag']);
                unset($item);
            }
            $label_styles = array(
                'default',
                'primary',
                'success',
                'info',
                'warning',
                'danger',
            );
            //print_r($list);
        } else if ($act == 'view') {
            if ($_W['container'] == 'wechat') {
                $id = intval($_GPC['id']);
                $key = md5('_house_view_' . $id . '_SuPerMan');
                $value = 'yes';
                if (!isset($_GPC[$key]) || $_GPC[$key] != $value) {
                    $ret = ad_greenlng_update_count($id, 'viewcount');
                    if ($ret) {
                        $expire_time = strtotime(date('Y-m-d 23:59:59')) - TIMESTAMP;
                        $expire_time = $expire_time >= 3600 ? $expire_time : 3600;
                        isetcookie($key, $value, $expire_time);
                    }
                    adgreenlng_stat_update_count(date('Ymd'), 'house_views');
                }
            }
            exit();
        } else if ($act == 'share') {
            if ($_W['container'] == 'wechat') {
                $id = intval($_GPC['id']);
                ad_greenlng_update_count($id, 'sharecount');
                adgreenlng_stat_update_count(date('Ymd'), 'house_shares');
            }
            exit();
        }
        include $this->template('house-' . $act);
    }

    //更新分享数据
    private function update_share($uid, $credit, $credit_type, $house_id)
    {
        global $_W, $_GPC;
        $share_key = '_' . md5('_house_share_' . $uid . '_' . $house_id . 'ad_greenlng_share');
        $share_value = 'yes';
        if (isset($_GPC[$share_key]) && $share_value == 'yes') {
            WeUtility::logging('trace', '[update_share] updated, house_id=' . $house_id);
            return;
        }
        $friend_uid = 0;
        $member = mc_fetch($uid, array('nickname'));
        if ($member) {
            //每天领取分享积分上限
            if (isset($this->module['config']['credit']['today_limit']) && $this->module['config']['credit']['today_limit'] > 0) {
                $filter = array(
                    'uniacid' => $_W['uniacid'],
                    'uid' => $uid,
                    'starttime' => strtotime(date('Y-m-d 0:0:0', TIMESTAMP)),
                    'endtime' => strtotime(date('Y-m-d 23:59:59', TIMESTAMP)),
                );
                $credit_total = ad_greenlng_share_sum($filter);
                if ($credit_total > 0 && $credit_total + $credit > $this->module['config']['credit']['today_limit']) {
                    WeUtility::logging('trace', "[update_share] limited, credit={$credit}, credit_total={$credit_total}, today_limit={$this->module['config']['credit']['today_limit']}");
                    return;
                }
            }
            if ($_W['member']['uid']) { //会员
                $friend_uid = $_W['member']['uid'];
                $filter = array(
                    'uniacid' => $_W['uniacid'],
                    'uid' => $uid,
                    'house_id' => $house_id,
                    'friend_uid' => $friend_uid,
                );
                $list = ad_greenlng_share_fetchall($filter, '', 0, 1);
                if ($list) {   //存在分享记录
                    WeUtility::logging('trace', "[update_share] shared, uid={$uid}, house_id={$house_id}, friend_uid={$friend_uid}");
                    return;
                }
            } else {
                $filter = array(
                    'uniacid' => $_W['uniacid'],
                    'uid' => $uid,
                    'house_id' => $house_id,
                    'ip' => $_W['clientip'],
                );
                $list = ad_greenlng_share_fetchall($filter, '', 0, 1);
                if ($list && count($list) >= 3) {   //存在分享记录
                    WeUtility::logging('trace', "[update_share] shared, uid={$uid}, house_id={$house_id}, ip={$_W['clientip']}");
                    return;
                }
            }
            //记录分享数据
            $data = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $uid,
                'house_id' => $house_id,
                'friend_uid' => $friend_uid,
                'ip' => $_W['clientip'],
                'credit_type' => $credit_type,
                'credit' => $credit,
                'dateline' => TIMESTAMP,
            );
            $new_id = ad_greenlng_share_insert($data);
            if ($new_id) {
                //增加积分
                $log = array(
                    $friend_uid,    //记录积分来源
                    "分享区域报价(id={$house_id})奖励积分",
                    'ad_greenlng',
                );
                $ret = mc_credit_update($uid, $credit_type, $credit, $log);
                if (is_error($ret)) {
                    WeUtility::logging('fatal', '[update_share] mc_credit_update failed, result=' . var_export($ret, true));
                    return;
                }

                //记录cookie
                $expire_time = 30 * 365 * 86400;
                isetcookie($share_key, $share_value, $expire_time);
            }
            WeUtility::logging('trace', "[update_share] success, uid={$uid}, house_id={$house_id}, friend_uid={$friend_uid}, ip={$_W['clientip']}, credit={$credit}, credit_type={$credit_type}");
        }
    }
}

$obj = new Ad_greenlng_doMobileHouse;
$obj->exec();
