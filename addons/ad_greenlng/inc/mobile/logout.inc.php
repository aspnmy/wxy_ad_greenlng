<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileLogout extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_W, $_GPC, $do;
        unset($_SESSION);
        session_destroy();
        isetcookie('logout', 1, 60);
        @header('Location: ' . $this->createMobileUrl('home'));
        exit;
    }
}

$obj = new Ad_greenlng_doMobileLogout;
$obj->exec();
