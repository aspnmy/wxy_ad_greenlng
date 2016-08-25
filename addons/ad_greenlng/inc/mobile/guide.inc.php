<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileGuide extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        global $_W, $_GPC;
        $_share = $this->_share;
        include $this->template('guide');
    }
}

$obj = new Ad_greenlng_doMobileGuide;
$obj->exec();
