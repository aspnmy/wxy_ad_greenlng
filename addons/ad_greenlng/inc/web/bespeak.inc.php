<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */

//安易迅并不需要预约模式
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doWebBespeak extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_GPC, $_W;
        $title = '预约管理';
        $eid = $_GPC['eid'];
        $do = !empty($_GPC['do']) ? $_GPC['do'] : 'display';

        if ($do == 'deletebespeak') {
            $id = intval($_GPC['_id']);
            if ($id > 0) {
                pdo_delete('adgreenlng_house_bespeak', array('id' => $id));
            }
            message('删除成功！', referer(), 'success');
        } else if ($do == 'bespeakconfirm') {
            $id = intval($_GPC['_id']);
            if (!$id) {
                echo '非法请求';
                exit;
            }
            $status = $_GPC['status'];
            $sql = "SELECT * FROM " . tablename('adgreenlng_house_bespeak') . " WHERE id=:id";
            $row = pdo_fetch($sql, array(':id' => $id));
            if (!$row) {
                echo '数据不存在或已删除';
                exit;
            }
            $ret = pdo_update('adgreenlng_house_bespeak', array('status' => $_GPC['status']), array('id' => $id));
            if ($ret === false) {
                echo '系统错误，请稍后重试';
                exit;
            }
            echo 'success';
            exit;

        }


        $pindex = max(1, intval($_GPC['page']));
        $pagesize = 20;
        $start = ($pindex - 1) * $pagesize;
        $condition = ' WHERE `uniacid` = ' . $_W['uniacid'];

        $sql = 'SELECT * FROM ' . tablename('adgreenlng_house');
        $sql .= ' WHERE `uniacid` = ' . $_W['uniacid'];
        $housename = trim($_GPC['housename']);
        $houseinfos = array();
        if ($housename != '') {
            $sql .= ' AND `name` LIKE \'%' . $housename . '%\'';
            $houseinfos = pdo_fetchall($sql, array(), 'id');
            $houseids = array_keys($houseinfos);
            if (empty($houseids)) {
                $pager = pagination(0, $pindex, $pagesize);
                include $this->template('web/bespeak');
                exit;
            }
            $condition .= ' AND `houseid` IN (' . implode(',', $houseids) . ')';
        } else {
            $houseinfos = pdo_fetchall($sql, array(), 'id');
        }
        $username = trim($_GPC['username']);
        if ($username != '') {
            $condition .= ' AND `username` LIKE \'%' . $username . '%\'';
        }
        $phone = trim($_GPC['phone']);
        if ($phone != '') {
            $condition .= ' AND `phone` LIKE \'%' . $phone . '%\'';
        }

        if ($do == 'display') {
            $title = '全部';
            $status = 0;
        }
        if ($do == 'bespeaking') {
            $title = '预约中';
            $condition .= ' AND `status` = 1';
            $status = 1;
        }
        if ($do == 'bespeaksuccess') {
            $title = '预约成功';
            $condition .= ' AND `status` = 2';
            $status = 2;
        }
        if ($do == 'bespeakfailure') {
            $title = '预约失败';
            $condition .= ' AND `status` = -1';
            $status = -1;
        }

        $sql = "SELECT * FROM " . tablename('adgreenlng_house_bespeak');
        if ($do == 'toexcel') {
            $sql .= $condition . " ORDER BY `createtime` DESC";
        } else {
            $sql .= $condition . " ORDER BY `createtime` DESC LIMIT $start, $pagesize";
        }
        $list = pdo_fetchall($sql);
        foreach ($list as &$v) {
            $v['bespeaktime'] = date('Y-m-d', $v['bespeaktime']);
            $v['housename'] = $houseinfos[$v['houseid']]['name'];
            unset($v);
        }

        if ($do == 'toexcel') {
            foreach ($list as &$or) {
                switch ($or['status']) {
                    case '-1':
                        $or['status'] = '预约失败';
                        break;
                    case '1':
                        $or['status'] = '预约中';
                        break;
                    case '2':
                    default :
                        $or['status'] = '预约成功';
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

            $resultPHPExcel->getActiveSheet()->getStyle('A1:F1')->applyFromArray(($styleArray + $style_fill));
            $resultPHPExcel->getActiveSheet()->setCellValue('A1', '楼盘');
            $resultPHPExcel->getActiveSheet()->setCellValue('B1', '姓名');
            $resultPHPExcel->getActiveSheet()->setCellValue('C1', '手机号');
            $resultPHPExcel->getActiveSheet()->setCellValue('D1', '预约时间');
            $resultPHPExcel->getActiveSheet()->setCellValue('E1', '状态');
            $resultPHPExcel->getActiveSheet()->setCellValue('F1', '备注信息');
            $resultPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
            $i = 2;
            foreach ($list as $item) {
                $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['housename']);
                $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['username']);
                $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['phone']);
                $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['bespeaktime']);
                $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['status']);
                $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['remark']);
                $resultPHPExcel->getActiveSheet()->getStyle('A' . $i . ':F' . $i)->applyFromArray($styleArray);
                $i++;
            }
            $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, '总预约人数：' . count($list) . '人');
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

        }

        $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_house_bespeak') . $condition;
        $total = pdo_fetchcolumn($sql);

        $pager = pagination($total, $pindex, $pagesize);
        include $this->template('web/bespeak');
        exit;
    }
}

$obj = new Ad_greenlng_doWebBespeak;
$obj->exec();
