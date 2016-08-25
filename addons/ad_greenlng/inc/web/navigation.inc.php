<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doWebNavigation extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_W, $_GPC;
        $title = '底部导航';
        $act = in_array($_GPC['act'], array('display')) ? $_GPC['act'] : 'display';
        if ($act == 'display') {
            if (isset($_GPC['setattr']) && $_GPC['setattr'] == 1) {
                $id = intval($_GPC['id']);
                $field = in_array($_GPC['field'], array('isshow')) ? $_GPC['field'] : '';
                if (!$id || !$field) {
                    exit('非法请求');
                }
                $value = $_GPC['value'];
                $data = array(
                    $field => $value,
                );
                adgreenlng_navigation_update($data, array('id' => $id));
                exit('success');
            }

            if (checksubmit()) {
                foreach ($_GPC['title'] as $key => $val) {
                    $id = $_GPC['id'][$key];
                    $icon = trim($_GPC['icon'][$key]);
                    $title = trim($_GPC['title'][$key]);
                    $url = trim($_GPC['url'][$key]);
                    $displayorder = intval($_GPC['displayorder'][$key]);
                    $isshow = $_GPC['isshow'][$key] ? 1 : 0;
                    $data = array(
                        'uniacid' => $_W['uniacid'],
                        'icon' => $icon,
                        'title' => $title,
                        'url' => $url,
                        'displayorder' => $displayorder,
                        'isshow' => $isshow,
                    );
                    if ($id) {
                        adgreenlng_navigation_update($data, array('id' => $id));
                    } else {
                        adgreenlng_navigation_insert($data);
                    }
                }
                message('操作成功', referer(), 'success');
            }

            $filter = array(
                'uniacid' => $_W['uniacid'],
            );
            $list = adgreenlng_navigation_fetchall($filter, '', 0, -1);
            if (!$list) {
                $list = adgreenlng_navigation_data($this->module['config']);
            }
        }
        include $this->template('web/navigation');
    }
}

$obj = new Ad_greenlng_doWebNavigation;
$obj->exec();
