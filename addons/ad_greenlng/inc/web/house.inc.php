<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doWebHouse extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_GPC, $_W;
        $title = '区域报价管理';
        $eid = intval($_GPC['eid']);
        $do = !empty($_GPC['do']) ? $_GPC['do'] : 'display';
        if ($do == 'display') {
            if (checksubmit('submit')) {
                $displayorder = $_GPC['displayorder'];
                if ($displayorder) {
                    foreach ($displayorder as $id => $val) {
                        pdo_update('adgreenlng_house', array('displayorder' => $val), array('id' => $id));
                    }
                    message('更新成功', referer(), 'success');
                }
            }
            $filter = array();
            if ($_W['isfounder']) {
                $accounts = uni_owned();
                if ($accounts) {
                    foreach ($accounts as &$item) {
                        $item['house_total'] = ad_greenlng_count(array('uniacid' => $item['uniacid']));
                    }
                    unset($item);
                }
                $filter['uniacid'] = isset($_GPC['_uniacid']) ? $_GPC['_uniacid'] : $_W['uniacid'];
                $_GPC['_uniacid'] = $filter['uniacid'];
            } else {
                $filter['uniacid'] = $_W['uniacid'];
            }
            if (isset($_GPC['name']) && $_GPC['name'] != '') {
                $filter['name'] = $_GPC['name'];
            }
            $total = ad_greenlng_count($filter);
            $list = array();
            if ($total > 0) {
                $pindex = max(1, intval($_GPC['page']));
                $pagesize = 20;
                $start = ($pindex - 1) * $pagesize;
                $list = ad_greenlng_fetchall($filter, '', $start, $pagesize);
                if ($list) {
                    foreach ($list as &$item) {
                        ad_greenlng_set($item);
                    }
                    unset($item);
                    $pager = pagination($total, $pindex, $pagesize);
                }
            }
            //print_r($list);
            include $this->template('web/house');
            exit;
        } else if ($do == 'post') {
            $credits = adgreenlng_get_credits();
            $house_params = get_house_params();
            $id = intval($_GPC['_id']);
            $sql = "SELECT * FROM " . tablename('adgreenlng_house') . ' WHERE `id` = ' . $id;
            $item = pdo_fetch($sql);
            if ($id && !$item) {
                message('区域报价不存在或已删除', referer(), 'warning');
            }
            if ($item) {
                ad_greenlng_set($item);
                //print_r($item);
            }


            //删除区域报价周边数据
            if (isset($_GPC['delnearby'])) {
                ad_greenlng_update(array('nearby' => ''), array('id' => $item['id']));
                exit('success');
            }

            //获取区域报价周边数据
            if (empty($item['nearby']) && $item['latitude'] != '' && $item['longitude'] != '') {
                $item['nearby'] = $this->get_house_nearby($item['latitude'], $item['longitude']);
                if ($item['nearby']) {
                    ad_greenlng_update(array('nearby' => iserializer($item['nearby'])), array('id' => $item['id']));
                }
            }
            //print_r($item['nearby']);

            //区域报价类型特色数据加载
            $all_types = ad_greenlng_type(0);
            foreach ($all_types as $t) {
                $filter = array(
                    'uniacid' => $_W['uniacid'],
                    'type' => $t['id'],
                    'isshow' => 1
                );
                $type[$t['title']] = ad_greenlng_type_fetchall($filter);
            }
            unset($t);

            // 户型图信息
            $sql = "SELECT * FROM " . tablename('adgreenlng_layout') . ' WHERE `houseid` = ' . $id . '';
            $layouts = pdo_fetchall($sql, array(), 'id');
            foreach ($layouts as &$lout) {
                $lout['imgpath'] = $lout['img'];
                $lout['img'] = tomedia($lout['img']);
                unset($lout);
            }

            // 项目经理信息
            $managers = array();
            $pids = array();
            $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner_role');
            $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'];
            $sql .= ' AND `isadmin` = 1';
            $roles = pdo_fetchall($sql, array(), 'id');
            $role_ids = array_keys($roles);
            if (!empty($role_ids)) {
                $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner');
                $sql .= ' WHERE `roleid` IN (' . implode(',', $role_ids) . ')';
                $sql .= ' AND `uniacid` = ' . $_W['uniacid'];
                $sql .= ' AND `status` = 1';
                $managers = pdo_fetchall($sql, array(), 'subuid');
                if (!empty($managers)) {
                    $muids = array_keys($managers);
                    $minfos = mc_fetch($muids, array('avatar', 'nickname'));
                    foreach ($managers as &$m) {
                        $m['nickname'] = $minfos[$m['subuid']]['nickname'];
                        unset($m);
                    }
                    $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner_house_ref');
                    $sql .= ' WHERE `houseid` = ' . $id;
                    $partners = pdo_fetchall($sql, array(), 'partnerid');
                    $pids = array_keys($partners);
                }
            }
            if (checksubmit('submit')) {
                if (empty($_GPC['name'])) {
                    message('区域报价名称不能为空，请重新输入！');
                }

                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'name' => $_GPC['name'],
                    'cid' => $_GPC['cid'],
                    'price' => $_GPC['price'],
                    'phone' => $_GPC['phone'],
                    'selleraddress' => $_GPC['selleraddress'],
                    'address' => $_GPC['address'],
                    'opentime' => $_GPC['opentime'] != '请选择日期时间' ? strtotime($_GPC['opentime']) : 0,
                    'province' => $_GPC['reside']['province'],
                    'city' => $_GPC['reside']['city'],
                    'district' => $_GPC['reside']['district'],
                    'credit' => $_GPC['credit'],
                    'credit_type' => $_GPC['credit_type'],
                    'commission' => $_GPC['new_commission'] ? '' : $_GPC['commission'],
                    'new_commission' => $_GPC['new_commission'],
                    'deposit' => $_GPC['deposit'],
                    'hotmsg' => $_GPC['hotmsg'],
                    'coverimg' => adgreenlng_fix_path($_GPC['coverimg']),
                    'description' => $_GPC['description'],
                    'dynamicdesc' => $_GPC['dynamicdesc'],
                    'pricetype' => $_GPC['pricetype'],
                    'specialtype' => $_GPC['specialtype'],
                    'housetype' => $_GPC['housetype'],
                    'layouttype' => $_GPC['layouttype'],
                    'displayorder' => $_GPC['displayorder'],
                    'createtime' => TIMESTAMP,
                    'recommend' => $_GPC['recommend'],
                );
                $picpath = $_GPC['descimgs'];
                if (is_array($picpath) && !empty($picpath)) {
                    $paths = array();
                    foreach ($picpath as $p) {
                        $p[0] = adgreenlng_fix_path($p[0]);
                        array_push($paths, $p[0]);
                    }
                    $data['descimgs'] = serialize($paths);
                }
                $geo = $_GPC['geo'];
                if (is_array($geo) && !empty($geo)) {
                    $data['longitude'] = $geo['lng'];
                    $data['latitude'] = $geo['lat'];
                    // $data['geohash'] = geohash_encode($geo['lng'], $geo['lat']);
                }
                //print_r($data);die;

                $house_id = $id;
                if (empty($id)) {
                    pdo_insert('adgreenlng_house', $data);
                    $house_id = pdo_insertid();
                } else {
                    unset($data['createtime']);
                    pdo_update('adgreenlng_house', $data, array('id' => $id));
                }

                //自定义属性
                $ids = array();
                if (isset($_GPC['params_key']) && $_GPC['params_key']) {
                    foreach ($_GPC['params_key'] as $k => $v) {
                        $data = array(
                            'uniacid' => $_W['uniacid'],
                            'houseid' => $house_id,
                            'key' => $_GPC['params_key'][$k],
                            'value' => $_GPC['params_value'][$k],
                            'displayorder' => $k,
                        );
                        if (empty($_GPC['params_id'][$k])) {   //insert
                            pdo_insert('adgreenlng_house_kv', $data);
                            $ids[] = pdo_insertid();
                        } else {    //update
                            pdo_update('adgreenlng_house_kv', $data, array('id' => $_GPC['params_id'][$k]));
                            $ids[] = $_GPC['params_id'][$k];
                        }
                    }
                }
                $sql = 'DELETE FROM ' . tablename('adgreenlng_house_kv') . ' WHERE houseid=:houseid';
                if (!empty($ids)) {
                    $sql .= ' AND id NOT IN(' . implode(',', $ids) . ')';
                    pdo_query($sql, array(':houseid' => $house_id));
                } else {
                    pdo_query($sql, array(':houseid' => $house_id));
                }

                // 户型图
                $ids = array();
                if (isset($_GPC['layout_names']) && $_GPC['layout_names']) {
                    foreach ($_GPC['layout_names'] as $k => $v) {
                        $data = array(
                            'houseid' => $house_id,
                            'name' => $v,
                            'area' => $_GPC['layout_areas'][$k],
                            'tag' => $_GPC['layout_tags'][$k],
                            'createtime' => TIMESTAMP,
                        );
                        if (!empty($_FILES['layout_imgs']['tmp_name'][$k])) {
                            $file = array(
                                'name' => $_FILES['layout_imgs']['name'][$k],
                                'tmp_name' => $_FILES['layout_imgs']['tmp_name'][$k],
                                'type' => $_FILES['layout_imgs']['type'][$k],
                                'error' => $_FILES['layout_imgs']['error'][$k],
                                'size' => $_FILES['layout_imgs']['size'][$k],
                            );
                            $upload = file_upload($file, 'image');
                            if (!$upload['success']) {
                                message($upload['errno'] . ':' . $upload['message']);
                            }
                            if (!empty($_GPC['layout_ids'][$k])) {
                                $file_path = $layouts[$_GPC['layout_ids'][$k]]['imgpath'];
                                file_delete($file_path);
                            }
                            $data['img'] = $upload['path'];
                        }

                        if (empty($_GPC['layout_ids'][$k])) {   //insert
                            pdo_insert('adgreenlng_layout', $data);
                            $ids[] = pdo_insertid();
                        } else {    //update
                            pdo_update('adgreenlng_layout', $data, array('id' => $_GPC['layout_ids'][$k]));
                            $ids[] = $_GPC['layout_ids'][$k];
                        }
                    }
                }
                $sql = 'DELETE FROM ' . tablename('adgreenlng_layout') . ' WHERE houseid=:houseid';
                if (!empty($ids)) {
                    $sql .= ' AND id NOT IN(' . implode(',', $ids) . ')';
                    pdo_query($sql, array(':houseid' => $house_id));
                } else {
                    pdo_query($sql, array(':houseid' => $house_id));
                }

                // 项目经理信息
                $ids = array();
                if (isset($_GPC['managers']) && $_GPC['managers']) {
                    foreach ($_GPC['managers'] as $v) {
                        $ids[] = $v;
                        if (!in_array($v, $pids)) {
                            $data = array(
                                'partnerid' => $v,
                                'houseid' => $id,
                            );
                            pdo_insert('adgreenlng_partner_house_ref', $data);
                        }
                    }
                }
                $sql = 'DELETE FROM ' . tablename('adgreenlng_partner_house_ref') . ' WHERE `houseid` =' . $id;
                if (!empty($ids)) {
                    $sql .= ' AND `partnerid` NOT IN(' . implode(',', $ids) . ')';
                    pdo_query($sql);
                } else {
                    pdo_query($sql);
                }

                message('更新成功！', url('site/entry/display', array('eid' => $eid)), 'success');
            }
            include $this->template('web/house');
            exit;
        } else if ($do == 'delete') {
            $id = intval($_GPC['_id']);
            $sql = "SELECT * FROM " . tablename('adgreenlng_house') . ' WHERE `id` = ' . $id;
            $item = pdo_fetch($sql, array());
            if (empty($item)) {
                message('抱歉，区域报价不存在或是已经被删除！');
            }
            if (!empty($item['descimgs'])) {
                $arr = unserialize($item['descimgs']);
                if ($arr) {
                    foreach ($arr as $v) {
                        file_delete($v);
                    }
                }
            }
            $sql = "SELECT * FROM " . tablename('adgreenlng_layout') . ' WHERE `houseid` = ' . $id;
            $layouts = pdo_fetchall($sql, array());
            if (!empty($layouts)) {
                foreach ($layouts as $v) {
                    file_delete($v['img']);
                }
            }
            pdo_delete('adgreenlng_layout', array('houseid' => $id));
            pdo_delete('adgreenlng_house', array('id' => $id));
            pdo_delete('adgreenlng_partner_house_ref', array('houseid' => $id));
            message('删除成功！', referer(), 'success');
            exit;
        } else if ($do == 'deletekv') {
            $id = intval($_GPC['_id']);
            if ($id > 0) {
                pdo_delete('adgreenlng_house_kv', array('id' => $id));
            }
            echo 'success';
        } else if ($do == 'deletelayout') {
            $id = intval($_GPC['_id']);
            if ($id > 0) {
                $sql = "SELECT * FROM " . tablename('adgreenlng_layout') . ' WHERE `id` = ' . $id;
                $item = pdo_fetch($sql);
                if (!empty($item)) {
                    file_delete($item['img']);
                    pdo_delete('adgreenlng_layout', array('id' => $id));
                }
            }
            echo 'success';
        } else if ($do == 'params') {
            global $_W, $_GPC;
            include $this->template('web/house-params-new');
            exit;
        } else if ($do == 'detail') {
            $house_params = get_house_params();
            $id = intval($_GPC['_id']);
            if ($id > 0) {
                $sql = "SELECT * FROM " . tablename('adgreenlng_house') . ' WHERE `id` = ' . $id;
                $item = pdo_fetch($sql);
                if ($item) {
                    $item['coverimg'] = tomedia($item['coverimg']);
                    $pics_temp = unserialize($item['descimgs']);
                    $pics = array();
                    if ($pics_temp) {
                        foreach ($pics_temp as $pic) {
                            array_push($pics, tomedia($pic));
                        }
                        $item['descimgs'] = $pics;
                    }
                    // 自定义属性
                    $sql = "SELECT * FROM " . tablename('adgreenlng_house_kv') . ' WHERE `houseid` = ' . $id . ' ORDER BY displayorder ASC';
                    $item['params'] = pdo_fetchall($sql);

                    // 户型图信息
                    $sql = "SELECT * FROM " . tablename('adgreenlng_layout') . ' WHERE `houseid` = ' . $id . '';
                    $layouts = pdo_fetchall($sql, array(), 'id');
                    foreach ($layouts as &$lout) {
                        $lout['imgpath'] = $lout['img'];
                        $lout['img'] = tomedia($lout['img']);
                        unset($lout);
                    }
                }
            }
            include $this->template('web/house-detail');
        } else if ($do == 'design') {
            //TODO
            include $this->template('web/house');
        } else if ($do == 'type') {
            $type = in_array($_GPC['type'], array(1, 2)) ? $_GPC['type'] : 1;   //1:特色 2:类型

            //更新是否显示字段
            if (isset($_GPC['setattr'])) {
                $id = intval($_GPC['id']);
                $field = $_GPC['field'];
                $value = $_GPC['value'];
                if ($id) {
                    if (!pdo_fieldexists('adgreenlng_house_type', $field)) {
                        die("'$field' not exist");
                    }
                    $data = array(
                        $field => $value,
                    );
                    $condition = array(
                        'id' => $_GPC['id'],
                    );
                    ad_greenlng_type_update($data, $condition);
                    die('success');
                }
                die('非法请求');
            }

            //加载新类型模板
            if (isset($_GPC['addtype'])) {
                echo include $this->template('web/house-type-new');
                exit;
            }

            //删除字段
            if (isset($_GPC['deletetype']) && $_GPC['deletetype'] == 1) {
                $id = intval($_GPC['id']);
                $condition = array(
                    'id' => $id,
                );
                $ret = pdo_delete('adgreenlng_house_type', $condition);
                if ($ret !== false) {
                    die('success');
                } else {
                    die('系统错误');
                }
            }

            //数据加载
            $filter = array(
                'uniacid' => $_W['uniacid'],
                'type' => $type,
            );
            $list = ad_greenlng_type_fetchall($filter, '', '', -1);
            //初始化类型和特色
            if (!$list) {
                $filter = array(
                    'uniacid' => 0,
                );
                $init_data = ad_greenlng_type_fetchall($filter, '', '', -1);
                if ($init_data) {
                    foreach ($init_data as $v) {
                        $data = array(
                            'uniacid' => $_W['uniacid'],
                            'type' => $v['type'],
                            'title' => $v['title'],
                            'isshow' => $v['isshow'],
                        );
                        pdo_insert('adgreenlng_house_type', $data);
                        $data['id'] = pdo_insertid();
                        $list[$data['id']] = $data;
                    }
                    unset($init_data, $v);
                    //更新区域报价类型id
                    foreach ($list as $k => $v) {
                        if ($v['title'] == '安易迅00型') {
                            pdo_update('adgreenlng_house', array('specialtype' => $k), array('specialtype' => 1, 'uniacid' => $_W['uniacid']));
                        } else if ($v['title'] == '安易迅01型') {
                            pdo_update('adgreenlng_house', array('specialtype' => $k), array('specialtype' => 2, 'uniacid' => $_W['uniacid']));
                        } else if ($v['title'] == '安易迅02型') {
                            pdo_update('adgreenlng_house', array('specialtype' => $k), array('specialtype' => 3, 'uniacid' => $_W['uniacid']));
                        } else if ($v['title'] == '安易迅03型') {
                            pdo_update('adgreenlng_house', array('housetype' => $k), array('housetype' => 1, 'uniacid' => $_W['uniacid']));
                        } else if ($v['title'] == '安易迅04型') {
                            pdo_update('adgreenlng_house', array('housetype' => $k), array('housetype' => 2, 'uniacid' => $_W['uniacid']));
                        }
                    }
                }
            }
            if (checksubmit()) {
                if ($_GPC['title']) {
                    foreach ($_GPC['title'] as $k => $v) {
                        if ($v == '') {
                            continue;
                        }
                        $id = intval($_GPC['id'][$k]);
                        $data = array(
                            'title' => $_GPC['title'][$k],
                            'uniacid' => $_W['uniacid'],
                            'type' => $type,
                            'isshow' => $_GPC['isshow'][$k] ? 1 : 0,
                            'displayorder' => $_GPC['displayorder'][$k],
                        );
                        if ($id > 0) {
                            $condition = array(
                                'id' => $id,
                            );
                            pdo_update('adgreenlng_house_type', $data, $condition);
                        } else {
                            pdo_insert('adgreenlng_house_type', $data);
                        }
                    }
                    message('更新成功', referer(), 'success');
                }
            }
            include $this->template('web/house');
        }
    }

    /** 安易迅产品不需要周边数据
     * public function get_house_nearby($lat, $lng) {
     * $data = array();
     * $ak = SUPERMAN_BAIDU_MAP_AK;
     * $radius = 2000; //单位：米
     * $querys = array(
     * '银行', '医院', '学校', '公园', '公交',
     * '地铁', '餐饮', '娱乐', '购物'
     * );
     * $pagesize = 10;
     * foreach ($querys as $q) {
     * $url = "https://api.map.baidu.com/place/v2/search?query={$q}&location={$lat},{$lng}&radius={$radius}&output=json&ak={$ak}&page_size={$pagesize}";
     * $ret = file_get_contents($url);
     * if (is_error($ret)) {
     * message('获取周边数据错误, ret='.var_export($ret, true), '', 'error');
     * }
     * $ret = json_decode($ret, true);
     * if (!is_array($ret) || !$ret) {
     * message('解析周边数据错误', '', 'error');
     * }
     * if ($ret['status'] != 0) {
     * message('周边数据接口错误, ret='.var_export($ret, true), '', 'error');
     * }
     * foreach ($ret['results'] as &$val) {
     * $data[$q][] = array(
     * 'query' => $q,
     * 'name' => $val['name'],
     * 'address' => $val['address'],
     * 'distance' => adgreenlng_get_distance($lat, $lng, $val['location']['lat'], $val['location']['lng']),
     * 'location' => $val['location'],
     * );
     * }
     * }
     * foreach ($data as $k=>$v) {
     * usort($data[$k], array($this, 'ad_greenlng_nearby_orderby'));
     * }
     * return $data;
     * }**/

    private function ad_greenlng_nearby_orderby($a, $b)
    {
        if (intval($a['distance']) == intval($b['distance'])) {
            return 0;
        }
        return (intval($a['distance']) > intval($b['distance'])) ? 1 : -1;
    }
}


$obj = new Ad_greenlng_doWebHouse;
$obj->exec();
