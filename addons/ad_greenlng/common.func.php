<?php
//DIRECTORY_SEPARATOR PHP内置常量系统分隔符
//define('ROOT',dirname(__FILE__)."\upload"); == define('ROOT',dirname(__FILE__).DIRECTORY_SEPARATOR."upload");
//define('ROOT',dirname(__FILE__).DIRECTORY_SEPARATOR."upload"); 无错写法

function get_house_params()
{
    return array('specialtype' => array('name' => '特色', 'values' => array(1 => '安易迅00型', 2 => '安易迅01型', 3 => '不限购')), 'housetype' => array('name' => '类别', 'values' => array(1 => '安易迅00型', 2 => '安易迅01型')));
}

function ad_greenlng_getpath($file)
{
    global $_W;
    return IA_ROOT . DIRECTORY_SEPARATOR . $_W['config']['upload']['attachdir'] . DIRECTORY_SEPARATOR . $file;
}

function adgreenlng_format_price($price, $showcut = false)
{
    if ($showcut && $price > 10000) {
        $price = $price / 10000;
        $price .= '万';
    }
    return str_replace('.00', '', $price);
}

function adgreenlng_img_placeholder($returnsrc = true)
{
    global $_W;
    $src = $_W['siteroot'] . "addons/ad_greenlng/template/mobile/images/placeholder.jpg";
    if ($returnsrc) {
        return $src;
    } else {
        return "<img src='{$src}'/>";
    }
}

function adgreenlng_replace_variable($str, $vars)
{
    if (!$vars) {
        return $str;
    }
    foreach ($vars as $k => $v) {
        if (strpos($str, $k) !== false) {
            $str = str_replace($k, $v, $str);
        }
    }
    return $str;
}
//内置大宗lng出厂价比对数据来源于第三方网站
//@adgreenlng_lngprice_url() 第三方大宗交易lng出厂价对比工具
function adgreenlng_lngprice_url()
{
    return 'http://price.sci99.com/search.aspx?keyword=lng%20%E5%87%BA%E5%8E%82%E4%BB%B7&RequestId=eb2501e9626a842b';
}

function adgreenlng_qrcode_png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 10, $margin = 4, $saveandprint = false)
{
    include_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
    QRcode::png($text, $outfile, $level, $size, $margin, $saveandprint);
}

function adgreenlng_fix_path($path)
{
    global $_W;
    $path = strpos($path, 'http://') !== false || strpos($path, 'https://') !== false ? str_replace($_W['attachurl'], '', $path) : $path;
    $path = strpos($path, 'http://') !== false || strpos($path, 'https://') !== false ? str_replace($_W['siteroot'], '', $path) : $path;
    return $path;
}

function adgreenlng_get_distance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000;
    $lat1 = $lat1 * pi() / 180;
    $lng1 = $lng1 * pi() / 180;
    $lat2 = $lat2 * pi() / 180;
    $lng2 = $lng2 * pi() / 180;
    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
}

function adgreenlng_hide_mobile($mobile)
{
   //正则表达式替换手机号码是1开头前五位数字
    return preg_replace('/(\d{3})(\d{4})/', "$1****", $mobile);
}