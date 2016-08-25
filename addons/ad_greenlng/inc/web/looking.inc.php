<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doWebLooking extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_GPC, $_W;
        $title = '看房团管理';
        $eid = intval($_GPC['eid']);
        $do = !empty($_GPC['do']) ? $_GPC['do'] : 'display';
        if ($do == 'display') {
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 20;
            $start = ($pindex - 1) * $pagesize;
            $condition = ' WHERE 1';
            if (trim($_GPC['name']) != '') {
                $condition .= " AND `name` LIKE '%" . trim($_GPC['name']) . "%'";
            }
            $uniacid = $_W['uniacid'];
            $condition .= ' AND `uniacid` = ' . $uniacid;
            $sql = "SELECT * FROM " . tablename('adgreenlng_looking');
            $sql .= $condition . " ORDER BY `displayorder` DESC, regdeadline DESC LIMIT $start, $pagesize";
            $list = pdo_fetchall($sql);
            if ($list) {
                foreach ($list as &$item) {
                    $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_looking_users') . " WHERE lookid=:lookid";
                    $item['user_total'] = pdo_fetchcolumn($sql, array(':lookid' => $item['id']));
                    unset($item);
                }
            }
            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_looking') . $condition;
            $total = pdo_fetchcolumn($sql);
            $pager = pagination($total, $pindex, $pagesize);

            $lookids = array();
            foreach ($list as &$l) {
                if ($l['regdeadline'] < TIMESTAMP && $l['status'] == 1) {
                    array_push($lookids, $l['id']);
                    $l['status'] = 2;
                }
                unset($l);
            }
            if (!empty($lookids)) {
                $sql = "UPDATE " . tablename('adgreenlng_looking') . " SET `status` = 2 WHERE `id` IN (" . implode(',', $lookids) . ")";
                pdo_query($sql);
            }

            include $this->template('web/looking');
            exit;
        } else if ($do == 'userlist') {
//            $id = intval($_GPC['_id']);
//            if(!$id){
//                message('看房团不存在或已删除', referer(), 'error');
//            }
//            $sql = "SELECT * FROM " . tablename('adgreenlng_looking');
//            $sql .= " WHERE `id` = ".$id;
//            $look = pdo_fetch($sql);
            $pindex = max(1, intval($_GPC['page']));
            $pagesize = 20;
            $start = ($pindex - 1) * $pagesize;
            $uniacid = $_W['uniacid'];
            $condition = ' WHERE `uniacid` = ' . $uniacid;
//            $condition .= ' AND `lookid` = '.$id;
            $lookings = adgreenlng_looking_fetchall();
            if ($_GPC['lookid'] > 0) {
                $condition .= ' AND `lookid` = ' . $_GPC['lookid'];
            }

            if (trim($_GPC['name']) != '') {
                $condition .= " AND `name` LIKE '%" . trim($_GPC['name']) . "%'";
            }
            if (trim($_GPC['status']) != '') {
                $condition .= " AND `status` = " . $_GPC['status'];
            }
            $sql = "SELECT * FROM " . tablename('adgreenlng_looking_users');
            $sql .= $condition . " ORDER BY `createtime` DESC LIMIT $start, $pagesize";
            $users = pdo_fetchall($sql);
            $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_looking_users') . $condition;
            $total = pdo_fetchcolumn($sql);
            $uids = array();
            foreach ($users as &$user) {
                $uids[] = $user['uid'];
                $user['lookname'] = $lookings[$user['lookid']]['name'];
                $user['likehouse'] = $user['likehouse'] ? implode(' ', unserialize($user['likehouse'])) : '';
                unset($user);
            }
            $members = mc_fetch($uids, array('avatar', 'nickname'));
            $pager = pagination($total, $pindex, $pagesize);
            include $this->template('web/looking');
            exit;
        } else if ($do == 'post') {
            $id = intval($_GPC['_id']);
            $item = array();
            $house_count = 0;
            $user_count = 0;
            $sql = "SELECT `id`, `name` FROM " . tablename('adgreenlng_house') . " WHERE `uniacid` = " . $_W['uniacid'];
            $houses = pdo_fetchall($sql, array(), 'id');
            // die(json_encode($houses));
            if ($id) {
                $sql = "SELECT * FROM " . tablename('adgreenlng_looking') . ' WHERE `id` = ' . $id;
                $item = pdo_fetch($sql);
                if (!$item) {
                    message('看房团不存在或已删除', referer(), 'error');
                }
                $pics_temp = unserialize($item['slide']);
                $pics = array();
                if ($pics_temp) {
                    foreach ($pics_temp as $pic) {
                        array_push($pics, tomedia($pic));
                    }
                    $item['slide'] = $pics;
                }
                $item['viewtime'] = date('Y-m-d H:i', $item['viewtime']);
                $item['regdeadline'] = date('Y-m-d H:i', $item['regdeadline']);
                $sql = "SELECT * FROM " . tablename('adgreenlng_looking_house') . ' WHERE `lookid` = ' . $id;
                $look_houses = pdo_fetchall($sql, array(), 'houseid');
                if (!empty($look_houses)) {
                    foreach ($look_houses as &$lh) {
                        $lh['name'] = $houses[$lh['houseid']]['name'];
                        unset($lh);
                    }
                }
                $sql = "SELECT * FROM " . tablename('adgreenlng_looking_users') . ' WHERE `lookid` = ' . $id;
                $users = pdo_fetchall($sql);
                foreach ($users as &$u) {
                    $u['likehouse'] = $u['likehouse'] ? implode(' ', unserialize($u['likehouse'])) : '';
                    unset($u);
                }
            }

            if (checksubmit('submit')) {
                if (empty($_GPC['name'])) {
                    message('看房团名称不能为空，请重新输入！');
                }

                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'name' => $_GPC['name'],
                    'viewtime' => strtotime($_GPC['viewtime']),
                    'regdeadline' => strtotime($_GPC['regdeadline']),
                    'gatheraddress' => $_GPC['gatheraddress'],
                    'phone' => $_GPC['phone'],
                    'contact' => $_GPC['contact'],
                    'status' => $_GPC['status'],
                    'remark' => $_GPC['remark'],
                    'displayorder' => $_GPC['displayorder'],
                    'createtime' => TIMESTAMP,
                );
                $picpath = $_GPC['slide'];
                if (is_array($picpath) && !empty($picpath)) {
                    $paths = array();
                    foreach ($picpath as $p) {
                        array_push($paths, $p[0]);
                    }
                    $data['slide'] = serialize($paths);
                }
                $geo = $_GPC['geo'];
                if (is_array($geo) && !empty($geo)) {
                    $data['longitude'] = $geo['lng'];
                    $data['latitude'] = $geo['lat'];
                    // $data['geohash'] = geohash_encode($geo['lng'], $geo['lat']);
                }
                //print_r($data);die;

                $look_id = $id;
                if (empty($id)) {
                    pdo_insert('adgreenlng_looking', $data);
                    $look_id = pdo_insertid();
                } else {
                    unset($data['createtime']);
                    pdo_update('adgreenlng_looking', $data, array('id' => $id));
                }

                //看房团楼盘编辑
                $ids = array();
                if (isset($_GPC['house_ids']) && $_GPC['house_ids']) {
                    foreach ($_GPC['house_ids'] as $k => $v) {
                        if (in_array($v, $_GPC['house_ids']) && !in_array($v, $ids)) {
                            array_push($ids, $v);
                        } else {
                            // 提交重复数据
                            unset($_GPC['house_ids'][$k]);
                            unset($_GPC['look_house_ids'][$k]);
                        }
                    }
                    foreach ($_GPC['house_ids'] as $k => $v) {
                        $data = array(
                            'houseid' => $v,
                            'lookid' => $look_id,
                        );
                        if (!($_GPC['look_house_ids'][$k])) {   //insert
                            $data['uniacid'] = $_W['uniacid'];
                            pdo_insert('adgreenlng_looking_house', $data);
                            $ids[] = pdo_insertid();
                        } else {    //update
                            pdo_update('adgreenlng_looking_house', $data, array('id' => $_GPC['look_house_ids'][$k]));
                            $ids[] = $_GPC['look_house_ids'][$k];
                        }
                    }
                }
                $sql = 'DELETE FROM ' . tablename('adgreenlng_looking_house') . ' WHERE lookid=:lookid';
                if (!empty($ids)) {
                    $sql .= ' AND id NOT IN(' . implode(',', $ids) . ')';
                    pdo_query($sql, array(':lookid' => $look_id));
                } else {
                    pdo_query($sql, array(':lookid' => $look_id));
                }

                message('更新成功！', url('site/entry/display', array('eid' => $eid)), 'success');
            }
            include $this->template('web/looking');
            exit;
        } else if ($do == 'delete') {
            $id = intval($_GPC['_id']);
            $sql = "SELECT * FROM " . tablename('adgreenlng_looking') . ' WHERE `id` = ' . $id;
            $item = pdo_fetch($sql, array());
            if (empty($item)) {
                message('抱歉，看房团不存在或是已经被删除！');
            }
            pdo_delete('adgreenlng_looking', array('id' => $id));
            pdo_delete('adgreenlng_looking_house', array('lookid' => $id));
            pdo_delete('adgreenlng_looking_users', array('lookid' => $id));
            message('删除成功！', referer(), 'success');
            exit;
        } else if ($do == 'deletelookhouse') {
            $id = intval($_GPC['_id']);
            if ($id > 0) {
                pdo_delete('adgreenlng_looking_house', array('id' => $id));
            }
            echo 'success';
        } else if ($do == 'deletelookuser') {
            $id = intval($_GPC['_id']);
            if ($id > 0) {
                pdo_delete('adgreenlng_looking_users', array('id' => $id));
            }
            message('删除成功！', referer(), 'success');
        } else if ($do == 'toexcel') {
            $id = intval($_GPC['lookid']);
            $lookings = adgreenlng_looking_fetchall();

            $condition = ' WHERE `uniacid`=' . $_W['uniacid'];
            if ($id > 0) {
                $condition .= ' AND `lookid`=' . $id;
            }

            $sql = "SELECT * FROM " . tablename('adgreenlng_looking_users') . $condition;
            $list = pdo_fetchall($sql);

            // $profiles = mc_fetch($uids, array('nickname', 'avatar'));
            foreach ($list as &$or) {
                // $or['nickname'] = $profiles[$or['uid']]['nickname'];
                $or['createtime'] = date('Y-m-d H:i:s', $or['createtime']);
                $or['lookname'] = $lookings[$or['lookid']]['name'];
                switch ($or['status']) {
                    case '2':
                        $or['status'] = '已确认';
                        break;
                    case '3':
                        $or['status'] = '已拒绝';
                        break;
                    case '1':
                    default :
                        $or['status'] = '待确认';
                        break;
                }
            }

            require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel.php';
            require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel/IOFactory.php';
            require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel/Writer/Excel5.php';

            $resultPHPExcel = new PHPExcel();
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                        'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                        //'color' => array('argb' => 'FFFF0000'),
                    ),
                ),
            );
            $style_fill = array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('argb' => '0xFFFF00')
                ),
            );

            $resultPHPExcel->getActiveSheet()->getStyle('A1:G1')->applyFromArray(($styleArray + $style_fill));
            $resultPHPExcel->getActiveSheet()->setCellValue('A1', '姓名');
            $resultPHPExcel->getActiveSheet()->setCellValue('B1', '手机号');
            $resultPHPExcel->getActiveSheet()->setCellValue('C1', '携带人数');
            $resultPHPExcel->getActiveSheet()->setCellValue('D1', '看房团');
            $resultPHPExcel->getActiveSheet()->setCellValue('E1', '意向楼盘');
            $resultPHPExcel->getActiveSheet()->setCellValue('F1', '报名留言');
            $resultPHPExcel->getActiveSheet()->setCellValue('G1', '报名留言');
            $resultPHPExcel->getActiveSheet()->setCellValue('H1', '报名时间');
            $resultPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
            $i = 2;
            foreach ($list as $item) {
                $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['username']);
                $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['phone']);
                $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['fellows']);
                $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['lookname']);
                $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, empty($item['likehouse']) ? '' : implode('  ', unserialize($item['likehouse'])));
                $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['message']);
                $resultPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['status']);
                $resultPHPExcel->getActiveSheet()->setCellValue('H' . $i, $item['createtime']);
                $resultPHPExcel->getActiveSheet()->getStyle('A' . $i . ':H' . $i)->applyFromArray($styleArray);
                $i++;
            }
            $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, '总报名人数：' . count($list) . '人');
            $resultPHPExcel->getActiveSheet()->getStyle('A' . $i)->applyFromArray(array('font' => array('bold' => true)));


            $outputFileName = 'total.xls';
            $xlsWriter = new PHPExcel_Writer_Excel5($resultPHPExcel);
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header('Content-Disposition:inline;filename="' . $outputFileName . '"');
            header("Content-Transfer-Encoding: binary");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            $xlsWriter->save("php://output");

        } else if ($do == 'lookconfirm') {
            $id = intval($_GPC['_id']);
            if (!$id) {
                echo '非法请求';
                exit;
            }
            $sql = "SELECT * FROM " . tablename('adgreenlng_looking_users') . " WHERE id=:id";
            $row = pdo_fetch($sql, array(':id' => $id));
            if (!$row) {
                echo '数据不存在或已删除';
                exit;
            }
            if ($row['status'] == $_GPC['status']) {
                echo '状态未改变，操作已取消';
                exit;
            }
            $ret = pdo_update('adgreenlng_looking_users', array('status' => $_GPC['status']), array('id' => $id));
            if ($ret === false) {
                echo '系统错误，请稍后重试';
                exit;
            }
            //发送模板消息通知报名结果
            if ($this->module['config']['looking']['template_id']
                && $this->module['config']['looking']['template_content']
                && $this->module['config']['looking']['template_variable']
            ) {
                if (!$_W['acid']) { //后台可能存在该参数为空
                    $accounts = uni_accounts();
                    foreach ($accounts as $k => $v) {
                        $_W['account'] = $v;
                        $_W['acid'] = $_W['account']['acid'];
                        break;
                    }
                }
                if (!$_W['uniacid']) { //后台可能存在该参数为空
                    $_W['uniacid'] = $row['uniacid'];
                }
                $fans = mc_fansinfo($row['uid']);   //获取粉丝openid
                if ($fans) {
                    if ($fans['follow']) {  //检查粉丝是否关注
                        $sql = "SELECT * FROM " . tablename('adgreenlng_looking') . " WHERE id=:id";
                        $looking = pdo_fetch($sql, array(
                            ':id' => $row['lookid'],
                        ));
                        if ($looking) { //获取看房团信息
                            $account = WeAccount::create($_W['acid']);  //初始化模板消息发送对象
                            $message = array(   //初始化消息体
                                'template_id' => $this->module['config']['looking']['template_id'],
                                'postdata' => array(),
                                'url' => $_W['siteroot'] . 'app/' . $this->createMobileUrl('looking', array(
                                        '_id' => $row['lookid'],
                                        'act' => 'form',
                                        'm' => 'ad_greenlng',
                                    )),//模板消息点击链接
                                'topcolor' => '#008000',
                            );
                            //替换模板消息中的变量
                            $vars = array(
                                '{nickname}' => $fans['nickname'],
                                '{title}' => $looking['name'],
                                '{join_time}' => date('Y-m-d H:i', $row['createtime']),
                                '{activity_time}' => date('Y-m-d H:i', $looking['viewtime']),
                                '{activity_address}' => $looking['gatheraddress'],
                                '{phone}' => $looking['phone'],
                                '{status}' => $_GPC['status'] == 2 ? '已通过' : ($_GPC['status'] == 3 ? '已拒绝' : '未确认'),
                            );
                            $template_variable = explode("\n", $this->module['config']['looking']['template_variable']);
                            foreach ($template_variable as $line) {
                                $arr = explode("=", trim($line));
                                $message['postdata'][trim($arr[0])] = array(
                                    'value' => adgreenlng_replace_variable(trim($arr[1]), $vars),
                                    'color' => '#173177',
                                );
                                if ($message['postdata'][trim($arr[0])]['value'] == '已通过') {
                                    $message['postdata'][trim($arr[0])]['color'] = '#00bc12';
                                } else if ($message['postdata'][trim($arr[0])]['value'] == '已拒绝') {
                                    $message['postdata'][trim($arr[0])]['color'] = '#f20c00';
                                }
                            }
                            //发送模板消息
                            $ret = $account->sendTplNotice($fans['openid'], $message['template_id'], $message['postdata'], $message['url'], $message['topcolor']);
                            if ($ret !== true) {
                                WeUtility::logging("fatal", "模板消息发送失败：openid={$fans['openid']}, ret=" . var_export($ret, true) . ", message=" . var_export($message, true));
                            }
                            WeUtility::logging("trace", "模板消息发送成功：template_id={$message['template_id']}, openid={$fans['openid']}, message=" . var_export($message, true));

                        } else {
                            WeUtility::logging('warning', '模板消息发送失败：看房团不存在或已删除, id=' . $row['lookid']);
                        }
                    } else {
                        WeUtility::logging("warning", "模板消息发送失败：粉丝已取消关注, fans=" . var_export($fans, true));
                    }
                } else {
                    WeUtility::logging("warning", "模板消息发送失败：没有找到粉丝信息, uid={$row['uid']}");
                }
            } else {
                WeUtility::logging("warning", "模板消息发送失败：没有配置模板消息参数");
            }
            echo 'success';
            exit;
        }
    }
}

$obj = new Ad_greenlng_doWebLooking;
$obj->exec();
