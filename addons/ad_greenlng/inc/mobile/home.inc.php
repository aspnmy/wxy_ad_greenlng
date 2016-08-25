<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileHome extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_W, $_GPC, $do;
        $title = '首页';
        $_share = $this->_share;
        $calculator_url = $this->module['config']['base']['calculator_url'] ? $this->module['config']['base']['calculator_url'] : adgreenlng_calculator_url();
        $sql = "SELECT * FROM " . tablename('adgreenlng_looking');
        $sql .= " WHERE `uniacid` = " . $_W['uniacid'];
        $sql .= " AND `status` != 2 AND `regdeadline` > " . TIMESTAMP . " ORDER BY `displayorder` DESC LIMIT 4";

        $look_list_temp = pdo_fetchall($sql, array(), 'id');
        $look_list = array_slice($look_list_temp, 0, 4);
        foreach ($look_list as &$look) {
            $sql = "SELECT COUNT(`id`) AS `user_count` FROM " . tablename('adgreenlng_looking_users');
            $sql .= " WHERE `lookid` =" . $look['id'];
            $look['user_count'] = pdo_fetchcolumn($sql);
            $week_info = array(1 => '周一', 2 => '周二', 3 => '周三', 4 => '周四', 5 => '周五', 6 => '周六', 7 => '周日',);
            $look['week'] = $week_info[date('N', $look['viewtime'])];
            unset($look);
        }

        $pindex = max(1, intval($_GPC['page']));
        $pagesize = 5;
        $start = ($pindex - 1) * $pagesize;

        $filter = array(
            'uniacid' => $_W['uniacid']
        );
        $house_list = ad_greenlng_fetchall($filter, '', $start, $pagesize);
        if ($house_list) {
            foreach ($house_list as &$item) {
                ad_greenlng_set($item);
            }
            unset($item);
        }

        if ($_W['isajax']) {
            die(json_encode($house_list));
        }

        include $this->template('home');
    }
}

$obj = new Ad_greenlng_doMobileHome;
$obj->exec();
