<?php


class WxpayAPI
{
    private static $debug = false;
    private static $pay_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    private static $query_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';

    public static function pay($param, $extra = array())
    {
        $data = array('mch_appid' => $param['mch_appid'], 'mchid' => $param['mchid'], 'nonce_str' => $param['nonce_str'], 'partner_trade_no' => $param['partner_trade_no'], 'openid' => $param['openid'], 'check_name' => $param['check_name'], 're_user_name' => $param['re_user_name'], 'amount' => $param['amount'] * 100, 'desc' => $param['desc'], 'spbill_create_ip' => $param['spbill_create_ip']);
        $sign = self::sign($data, $extra['sign_key']);
        $xml_data = "<xml><mch_appid>{$data['mch_appid']}</mch_appid><mchid>{$data['mchid']}</mchid><nonce_str>{$data['nonce_str']}</nonce_str><partner_trade_no>{$data['partner_trade_no']}</partner_trade_no><openid>{$data['openid']}</openid><check_name>{$data['check_name']}</check_name><re_user_name>{$data['re_user_name']}</re_user_name><amount>{$data['amount']}</amount><desc>{$data['desc']}</desc><spbill_create_ip>{$data['spbill_create_ip']}</spbill_create_ip><sign>{$sign}</sign></xml>";
        $headers = array();
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['CURLOPT_SSL_VERIFYPEER'] = false;
        $headers['CURLOPT_SSL_VERIFYHOST'] = false;
        $headers['CURLOPT_SSLCERTTYPE'] = 'PEM';
        $headers['CURLOPT_SSLCERT'] = $extra['apiclient_cert'];
        $headers['CURLOPT_SSLKEYTYPE'] = 'PEM';
        $headers['CURLOPT_SSLKEY'] = $extra['apiclient_key'];
        if (!empty($extra['rootca'])) {
            $headers['CURLOPT_CAINFO'] = $extra['rootca'];
        }
        if (self::$debug) {
            WeUtility::logging('trace', 'xml_data=' . $xml_data);
            WeUtility::logging('trace', 'headers=' . var_export($headers, true));
        }
        $response = ihttp_request(self::$pay_url, $xml_data, $headers);
        if ($response == '') {
            return '[wxpay-api:pay] response NULL';
        }
        $response = $response['content'];
        if (self::$debug) {
            WeUtility::logging('trace', 'response=' . $response);
        }
        $xml = @simplexml_load_string($response);
        if (empty($xml)) {
            return '[wxpay-api:pay] parse xml NULL';
        }
        if (self::$debug) {
            WeUtility::logging('trace', 'xml=' . var_export($xml, true));
        }
        $return_code = $xml->return_code ? (string)$xml->return_code : '';
        $return_msg = $xml->return_msg ? (string)$xml->return_msg : '';
        $result_code = $xml->result_code ? (string)$xml->result_code : '';
        $err_code = $xml->err_code ? (string)$xml->err_code : '';
        $err_code_des = $xml->err_code_des ? (string)$xml->err_code_des : '';
        if ($return_code == 'SUCCESS' && $result_code == 'SUCCESS') {
            $ret = array('success' => true, 'partner_trade_no' => $xml->partner_trade_no, 'payment_no' => $xml->payment_no, 'payment_time' => $xml->payment_time);
            return $ret;
        } else {
            return $return_code . ':' . $return_msg . ',' . $err_code . ':' . $err_code_des;
        }
    }

    public static function sign($data, $sign_key)
    {
        ksort($data);
        $data_str = '';
        foreach ($data as $k => $v) {
            if ($v == '' || $k == 'sign') {
                continue;
            }
            $data_str .= "{$k}={$v}&";
        }
        $data_str .= "key=" . $sign_key;
        $sign = strtoupper(md5($data_str));
        return $sign;
    }

    public static function query($param)
    {
    }
}