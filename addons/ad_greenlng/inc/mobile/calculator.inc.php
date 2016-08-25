<?php
/**
 * 【超人】房产模块定义
 *
 * @author 超人
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ad_greenlng_doMobileCalculator extends Ad_greenlngModuleSite
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function exec()
    {
        $url = $this->module['config']['base']['calculator_url'] ? $this->module['config']['base']['calculator_url'] : adgreenlng_calculator_url();
        @header('Location: ' . $url);
        exit;
    }
}

$obj = new Ad_greenlng_doMobileCalculator;
$obj->exec();
