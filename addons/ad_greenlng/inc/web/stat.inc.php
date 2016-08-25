<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doWebStat extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_W, $_GPC;
        $title = '数据统计';
        $act = in_array($_GPC['act'], array('display')) ? $_GPC['act'] : 'display';
        if ($act == 'display') {
            $type = in_array($_GPC['type'], array('house', 'customer')) ? $_GPC['type'] : 'house';
            $scroll = intval($_GPC['scroll']);
            $st = $_GPC['datelimit']['start'] ? strtotime($_GPC['datelimit']['start']) : strtotime('-30day');
            $et = $_GPC['datelimit']['end'] ? strtotime($_GPC['datelimit']['end']) : strtotime(date('Y-m-d 23:59:59'));
            $starttime = min($st, $et);
            $endtime = max($st, $et);

            if ($type == 'house') {
                $list = $this->stat_house($starttime, $endtime);
                if ($_W['isajax']) {
                    echo json_encode($list);
                    exit;
                }
            } else if ($type == 'customer') {
                $filter = array(
                    'uniacid' => $_W['uniacid'],
                );
                //rgb color
                $rbg_colors = array(
                    '36, 165, 222',
                    '203, 48, 48',
                    '149, 192, 0',
                    '231, 160, 23',
                    '119, 119, 119',
                    '0, 0, 0',
                    '180, 10, 180',
                );
                $customer_status = adgreenlng_customer_status_fetchall($filter, 'ORDER BY displayorder ASC', 0, -1);
                if ($customer_status) {
                    foreach ($customer_status as $k => &$item) {
                        $item['color'] = $rbg_colors[$k];
                    }
                    unset($item);
                }
                $list = $this->stat_customer($starttime, $endtime);
                if ($_W['isajax']) {
                    echo json_encode($list);
                    exit;
                }
            }
            //print_r($list);
        }
        include $this->template('web/stat');
    }

    private function stat_house($starttime, $endtime)
    {
        global $_W;
        $list = array();
        $pagesize = intval(($endtime - $starttime) / 86400);
        $filter = array(
            'uniacid' => $_W['uniacid'],
        );
        $data = adgreenlng_stat_fetchall($filter, '', 0, $pagesize);
        for ($i = $starttime; $i <= $endtime; $i += (24 * 3600)) {
            if ($i == $starttime) {          //每日开始时间戳
                $t1 = $i;
            } else {
                $t1 = strtotime(date('Y-m-d 0:0:0', $i));
            }
            //$t2 = strtotime(date('Y-m-d 23:59:59', $i));
            $daytime = date('Ymd', $t1);

            //日期
            $list['label'][] = date('m-d', $t1);

            //浏览数
            $list['datasets']['flow1'][] = isset($data[$daytime]['house_views']) ? $data[$daytime]['house_views'] : 0;

            //分享数
            $list['datasets']['flow2'][] = isset($data[$daytime]['house_shares']) ? $data[$daytime]['house_shares'] : 0;

            //评论数
            //$list['datasets']['flow3'][] = isset($data[$daytime]['house_comments'])?$data[$daytime]['house_comments']:0;
        }
        return $list;
    }

    private function stat_customer($starttime, $endtime)
    {
        global $_W;
        $list = array();
        $filter = array(
            'uniacid' => $_W['uniacid'],
        );
        $customer_status = adgreenlng_customer_status_fetchall($filter, 'ORDER BY displayorder ASC', 0, -1);
        for ($i = $starttime; $i <= $endtime; $i += (24 * 3600)) {
            if ($i == $starttime) {          //每日开始时间戳
                $t1 = $i;
            } else {
                $t1 = strtotime(date('Y-m-d 0:0:0', $i));
            }
            $t2 = strtotime(date('Y-m-d 23:59:59', $i));

            //日期
            $list['label'][] = date('m-d', $t1);

            $filter = array(
                'uniacid' => $_W['uniacid'],
                'start_time' => $t1,
                'end_time' => $t2,
            );

            foreach ($customer_status as $k => $v) {
                $k += 1;
                $filter['statusid'] = $v['id'];
                $count1 = adgreenlng_customer_trace_count($filter);
                $list['datasets']['flow' . $k][] = $count1;
            }
        }
        return $list;
    }
}

$obj = new Ad_greenlng_doWebStat;
$obj->exec();
