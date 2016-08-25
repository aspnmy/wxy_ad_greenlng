<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doWebManual extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        @header('Location: http://www.kancloud.cn/supermanapp/house/72530');
        exit;
    }
}

$obj = new Ad_greenlng_doWebManual;
$obj->exec();
