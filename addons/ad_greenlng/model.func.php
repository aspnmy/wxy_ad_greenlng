<?php


defined('IN_IA') or die('Access Denied');
function ad_greenlng_set(&$item)
{
    $item['credit'] = adgreenlng_format_price($item['credit']);
    $item['credit_title'] = $item['credit_type'] ? adgreenlng_credit_type($item['credit_type']) : '';
    $item['format_price'] = adgreenlng_format_price($item['price'], true);
    $item['commission'] = adgreenlng_format_price($item['commission']);
    $item['deposit'] = adgreenlng_format_price($item['deposit']);
    $item['nearby'] = $item['nearby'] ? unserialize($item['nearby']) : array();
    $item['_opentime'] = $item['opentime'];
    $item['opentime'] = $item['opentime'] ? date('Y年m月d日', $item['opentime']) : '';
    $item['format_desc'] = cutstr(strip_tags(htmlspecialchars_decode($item['description'])), 30);
    $item['coverimg'] = $item['coverimg'] ? tomedia($item['coverimg']) : adgreenlng_img_placeholder();
    if ($item['descimgs']) {
        $descimgs = unserialize($item['descimgs']);
        if ($descimgs) {
            $item['descimgs'] = array();
            foreach ($descimgs as $img) {
                $item['descimgs'][] = tomedia($img);
            }
        }
    }
    if ($item['layoutimgs']) {
        $layoutimgs = unserialize($item['layoutimgs']);
        if ($layoutimgs) {
            $arr = array();
            $item['layoutimgs'] = array();
            foreach ($layoutimgs as $img) {
                $item['layoutimgs'][] = tomedia($img);
            }
        }
    }
    $item['params'] = ad_greenlng_kv_fetchall(array('houseid' => $item['id']), '', 0, -1);
    if (!pdo_fetchcolumn('SELECT id FROM ' . tablename('article_category') . ' WHERE id=:id', array(':id' => $item['cid']))) {
        $item['cid'] = 0;
    }
    return $item;
}

function ad_greenlng_fetch($id, $fields = array())
{
    $fields = $fields && is_array($fields) ? implode(',', $fields) : '*';
    $sql = "SELECT {$fields} FROM " . tablename('adgreenlng_house') . " WHERE `id`=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function ad_greenlng_fetchall($filter = array(), $orderby = '', $start = 0, $pagesize = 10)
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['name'])) {
        $where .= " AND name LIKE '%{$filter['name']}%'";
    }
    if (isset($filter['cid'])) {
        $where .= ' AND cid=:cid';
        $params[':cid'] = $filter['cid'];
    }
    if (isset($filter['specialtype'])) {
        $where .= ' AND specialtype=:specialtype';
        $params[':specialtype'] = $filter['specialtype'];
    }
    if (isset($filter['housetype'])) {
        $where .= ' AND housetype=:housetype';
        $params[':housetype'] = $filter['housetype'];
    }
    if (isset($filter['layouttype'])) {
        $where .= ' AND layouttype=:layouttype';
        $params[':layouttype'] = $filter['layouttype'];
    }
    if (isset($filter['district'])) {
        $where .= ' AND district=:district';
        $params[':district'] = $filter['district'];
    }
    if (isset($filter['recommend'])) {
        $where .= ' AND recommend=:recommend';
        $params[':recommend'] = $filter['recommend'];
    }
    if ($orderby == '') {
        $orderby = 'ORDER BY displayorder DESC, id DESC';
    }
    $limit = '';
    if ($pagesize > 0) {
        $limit = "LIMIT {$start},{$pagesize}";
    }
    $sql = "SELECT * FROM " . tablename('adgreenlng_house') . " {$where} {$orderby} {$limit}";
    return pdo_fetchall($sql, $params);
}

function ad_greenlng_count($filter = array())
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['name'])) {
        $where .= " AND name LIKE '%{$filter['name']}%'";
    }
    if (isset($filter['cid'])) {
        $where .= ' AND cid=:cid';
        $params[':cid'] = $filter['cid'];
    }
    if (isset($filter['specialtype'])) {
        $where .= ' AND specialtype=:specialtype';
        $params[':specialtype'] = $filter['specialtype'];
    }
    if (isset($filter['housetype'])) {
        $where .= ' AND housetype=:housetype';
        $params[':housetype'] = $filter['housetype'];
    }
    if (isset($filter['layouttype'])) {
        $where .= ' AND layouttype=:layouttype';
        $params[':layouttype'] = $filter['layouttype'];
    }
    if (isset($filter['district'])) {
        $where .= ' AND district=:district';
        $params[':district'] = $filter['district'];
    }
    if (isset($filter['recommend'])) {
        $where .= ' AND recommend=:recommend';
        $params[':recommend'] = $filter['recommend'];
    }
    $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_house') . " {$where}";
    return pdo_fetchcolumn($sql, $params);
}

function ad_greenlng_update_count($id, $field, $value = 1)
{
    if (!in_array($field, array('viewcount', 'sharecount', 'commentcount'))) {
        return false;
    }
    $sql = 'UPDATE ' . tablename('adgreenlng_house') . " SET {$field}={$field}+{$value} WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_query($sql, $params) > 0 ? true : false;
}

function ad_greenlng_update($data, $condition)
{
    return pdo_update('adgreenlng_house', $data, $condition);
}

function ad_greenlng_delete($id)
{
    return pdo_delete('adgreenlng_house', array('id' => $id));
}

function ad_greenlng_insert($data)
{
    pdo_insert('adgreenlng_house', $data);
    return pdo_insertid();
}

function ad_greenlng_kv_fetch($id)
{
    $sql = "SELECT * FROM " . tablename('adgreenlng_house_kv') . " WHERE houseid=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function ad_greenlng_kv_fetchall($filter = array(), $orderby = '', $start = 0, $pagesize = 10)
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['houseid'])) {
        $where .= ' AND houseid=:houseid';
        $params[':houseid'] = $filter['houseid'];
    }
    if ($orderby == '') {
        $orderby = 'ORDER BY displayorder DESC, id DESC';
    }
    $limit = '';
    if ($pagesize > 0) {
        $limit = "LIMIT {$start},{$pagesize}";
    }
    $sql = "SELECT * FROM " . tablename('adgreenlng_house_kv') . " {$where} {$orderby} {$limit}";
    return pdo_fetchall($sql, $params);
}

function ad_greenlng_order_fetch($ordid)
{
    $sql = "SELECT * FROM " . tablename('adgreenlng_house_order') . " WHERE `ordid`=:ordid";
    $params = array(':ordid' => $ordid);
    return pdo_fetch($sql, $params);
}

function adgreenlng_new_commission_total($uid)
{
    $sql = "SELECT SUM(money) FROM " . tablename('adgreenlng_new_commission') . " WHERE uid=:uid";
    $params = array(':uid' => $uid);
    return pdo_fetchcolumn($sql, $params);
}

function adgreenlng_partner_add_commission($uid, $tid, $tidtype, $money)
{
    global $_W;
    if ($money <= 0) {
        return;
    }
    $data = array('uniacid' => $_W['uniacid'], 'uid' => $uid, 'money' => $money, 'status' => 0, 'tid' => $tid, 'tidtype' => $tidtype, 'createtime' => TIMESTAMP);
    pdo_insert('adgreenlng_new_commission', $data);
    $new_id = pdo_insertid();
    if (!$new_id) {
        WeUtility::logging('fatal', 'adgreenlng_new_commission insert failed, data=' . var_export($data, true));
        return;
    }
}

function adgreenlng_partner_fetch_by_uid($uid)
{
    global $_W;
    $sql = "SELECT a.*,b.nickname,b.avatar FROM " . tablename('adgreenlng_partner') . " AS a," . tablename('mc_members') . " AS b WHERE a.subuid=:subuid AND a.subuid=b.uid";
    $params = array(':subuid' => $uid);
    return pdo_fetch($sql, $params);
}

function adgreenlng_partner_fetch_by_id($id)
{
    global $_W;
    $sql = "SELECT * FROM " . tablename('adgreenlng_partner') . " WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function adgreenlng_customer_fetch_by_id($id)
{
    global $_W;
    $sql = "SELECT * FROM " . tablename('adgreenlng_customer') . " WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function adgreenlng_partner_house_fetch_by_id($id)
{
    global $_W;
    $sql = "SELECT b.id,b.name FROM " . tablename('adgreenlng_partner_house_ref') . " AS a, " . tablename('adgreenlng_house') . " AS b WHERE a.partnerid=:id AND a.houseid=b.id";
    $params = array(':id' => $id);
    return pdo_fetchall($sql, $params, 'id');
}

function adgreenlng_partner_rel_fetch_by_subpartnerid($subpartnerid)
{
    global $_W;
    $sql = "SELECT * FROM " . tablename('adgreenlng_partner_rel') . " WHERE subpartnerid=:subpartnerid";
    $params = array(':subpartnerid' => $subpartnerid);
    return pdo_fetch($sql, $params);
}

function adgreenlng_partner_rel_fetch_by_partnerid($partnerid)
{
    global $_W;
    $sql = "SELECT * FROM " . tablename('adgreenlng_partner_rel') . " WHERE partnerid=:partnerid";
    $params = array(':partnerid' => $partnerid);
    return pdo_fetch($sql, $params);
}

function adgreenlng_partner_role_fetch_by_id($id)
{
    global $_W;
    $sql = "SELECT * FROM " . tablename('adgreenlng_partner_role') . " WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function adgreenlng_partner_role_fetchall()
{
    global $_W;
    $sql = 'SELECT * FROM ' . tablename('adgreenlng_partner_role') . ' WHERE uniacid=:uniacid ORDER BY displayorder ASC';
    $params = array(':uniacid' => $_W['uniacid']);
    return pdo_fetchall($sql, $params, 'id');
}

function adgreenlng_customer_status_fetch($id)
{
    global $_W;
    $sql = "SELECT * FROM " . tablename('adgreenlng_customer_status') . " WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function adgreenlng_customer_status_fetchall($filter = array(), $orderby = '', $start = 0, $pagesize = 10, $keyfield = '')
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if ($orderby == '') {
        $orderby = 'ORDER BY displayorder DESC, id DESC';
    }
    $limit = '';
    if ($pagesize > 0) {
        $limit = "LIMIT {$start},{$pagesize}";
    }
    $sql = "SELECT * FROM " . tablename('adgreenlng_customer_status') . " {$where} {$orderby} {$limit}";
    return pdo_fetchall($sql, $params, $keyfield);
}

function adgreenlng_customer_status_count($filter = array())
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_customer_status') . " {$where}";
    return pdo_fetchcolumn($sql, $params);
}

function adgreenlng_customer_trace_fetch($id)
{
    $sql = "SELECT * FROM " . tablename('adgreenlng_customer_trace') . " WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function adgreenlng_customer_trace_fetchall($filter = array(), $orderby = '', $start = 0, $pagesize = 10)
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['customerid'])) {
        $where .= ' AND customerid=:customerid';
        $params[':customerid'] = $filter['customerid'];
    }
    if (isset($filter['statusid'])) {
        $where .= ' AND statusid=:statusid';
        $params[':statusid'] = $filter['statusid'];
    }
    if (isset($filter['partnerid'])) {
        $where .= ' AND partnerid=:partnerid';
        $params[':partnerid'] = $filter['partnerid'];
    }
    if (isset($filter['start_time'])) {
        $where .= ' AND dateline>=' . $filter['start_time'];
    }
    if (isset($filter['end_time'])) {
        $where .= ' AND dateline<=' . $filter['end_time'];
    }
    if ($orderby == '') {
        $orderby = 'ORDER BY id DESC';
    }
    $limit = '';
    if ($pagesize > 0) {
        $limit = "LIMIT {$start},{$pagesize}";
    }
    $sql = "SELECT * FROM " . tablename('adgreenlng_customer_trace') . " {$where} {$orderby} {$limit}";
    return pdo_fetchall($sql, $params);
}

function adgreenlng_customer_trace_count($filter = array())
{
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['customerid'])) {
        $where .= ' AND customerid=:customerid';
        $params[':customerid'] = $filter['customerid'];
    }
    if (isset($filter['statusid'])) {
        $where .= ' AND statusid=:statusid';
        $params[':statusid'] = $filter['statusid'];
    }
    if (isset($filter['partnerid'])) {
        $where .= ' AND partnerid=:partnerid';
        $params[':partnerid'] = $filter['partnerid'];
    }
    if (isset($filter['start_time'])) {
        $where .= ' AND dateline>=' . $filter['start_time'];
    }
    if (isset($filter['end_time'])) {
        $where .= ' AND dateline<=' . $filter['end_time'];
    }
    $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_customer_trace') . " {$where}";
    return pdo_fetchcolumn($sql, $params);
}

function adgreenlng_customer_trace_update($data, $condition)
{
    return pdo_update('adgreenlng_customer_trace', $data, $condition);
}

function adgreenlng_customer_trace_delete($id)
{
    return pdo_delete('adgreenlng_customer_trace', array('id' => $id));
}

function adgreenlng_customer_trace_insert($data)
{
    pdo_insert('adgreenlng_customer_trace', $data);
    return pdo_insertid();
}

function adgreenlng_looking_fetchall()
{
    global $_W;
    $sql = "SELECT * FROM " . tablename('adgreenlng_looking') . " WHERE `uniacid`=:uniacid";
    $params = array('uniacid' => $_W['uniacid']);
    return pdo_fetchall($sql, $params, 'id');
}

function ad_greenlng_type($type = 0)
{
    $all_type = array('1' => array('id' => 1, 'title' => 'specialtype', 'name' => '特色'), '2' => array('id' => 2, 'title' => 'housetype', 'name' => '类型'));
    if ($type > 0 && isset($all_types[$type])) {
        return $all_type[$type];
    } else {
        return $all_type;
    }
}

function ad_greenlng_type_fetch($id)
{
    $sql = "SELECT * FROM " . tablename('adgreenlng_house_type') . " WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function ad_greenlng_type_fetchall($filter = array(), $orderby = '', $start = 0, $pagesize = 10, $keyfield = '')
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['type'])) {
        $where .= ' AND type=:type';
        $params[':type'] = $filter['type'];
    }
    if (isset($filter['isshow'])) {
        $where .= ' AND isshow=:isshow';
        $params[':isshow'] = $filter['isshow'];
    }
    if ($orderby == '') {
        $orderby = 'ORDER BY displayorder DESC';
    }
    $limit = '';
    if ($pagesize > 0) {
        $limit = "LIMIT {$start},{$pagesize}";
    }
    $sql = "SELECT * FROM " . tablename('adgreenlng_house_type') . " {$where} {$orderby} {$limit}";
    return pdo_fetchall($sql, $params, $keyfield);
}

function ad_greenlng_type_count($filter = array())
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['type'])) {
        $where .= ' AND type=:type';
        $params[':type'] = $filter['type'];
    }
    $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_house_type') . " {$where}";
    return pdo_fetchcolumn($sql, $params);
}

function ad_greenlng_type_update($data, $condition)
{
    return pdo_update('adgreenlng_house_type', $data, $condition);
}

function ad_greenlng_type_delete($id)
{
    return pdo_delete('adgreenlng_house_type', array('id' => $id));
}

function ad_greenlng_type_insert($data)
{
    pdo_insert('adgreenlng_house_type', $data);
    return pdo_insertid();
}

function ad_greenlng_share_fetch($id)
{
    $sql = "SELECT * FROM " . tablename('adgreenlng_house_share') . " WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function ad_greenlng_share_fetchall($filter = array(), $orderby = '', $start = 0, $pagesize = 10)
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['uid'])) {
        $where .= ' AND uid=:uid';
        $params[':uid'] = $filter['uid'];
    }
    if (isset($filter['friend_uid'])) {
        $where .= ' AND friend_uid=:friend_uid';
        $params[':friend_uid'] = $filter['friend_uid'];
    }
    if (isset($filter['house_id'])) {
        $where .= ' AND house_id=:house_id';
        $params[':house_id'] = $filter['house_id'];
    }
    if ($orderby == '') {
        $orderby = 'ORDER BY id DESC';
    }
    $sql = "SELECT * FROM " . tablename('adgreenlng_house_share') . " {$where} {$orderby}";
    if ($pagesize > 0) {
        $sql .= " LIMIT {$start},{$pagesize}";
    }
    return pdo_fetchall($sql, $params);
}

function ad_greenlng_share_count($filter = array())
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['uid'])) {
        $where .= ' AND uid=:uid';
        $params[':uid'] = $filter['uid'];
    }
    if (isset($filter['friend_uid'])) {
        $where .= ' AND friend_uid=:friend_uid';
        $params[':friend_uid'] = $filter['friend_uid'];
    }
    if (isset($filter['house_id'])) {
        $where .= ' AND house_id=:house_id';
        $params[':house_id'] = $filter['house_id'];
    }
    $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_house_share') . " {$where}";
    return pdo_fetchcolumn($sql, $params);
}

function ad_greenlng_share_sum($filter = array())
{
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['uid'])) {
        $where .= ' AND uid=:uid';
        $params[':uid'] = $filter['uid'];
    }
    if (isset($filter['starttime'])) {
        $where .= ' AND dateline>=' . $filter['starttime'];
    }
    if (isset($filter['endtime'])) {
        $where .= ' AND dateline<=' . $filter['endtime'];
    }
    $sql = "SELECT SUM(credit) FROM " . tablename('adgreenlng_house_share') . " {$where}";
    return pdo_fetchcolumn($sql, $params);
}

function ad_greenlng_share_update($data, $condition)
{
    return pdo_update('adgreenlng_house_share', $data, $condition);
}

function ad_greenlng_share_delete($id)
{
    return pdo_delete('adgreenlng_house_share', array('id' => $id));
}

function ad_greenlng_share_insert($data)
{
    pdo_insert('adgreenlng_house_share', $data);
    return pdo_insertid();
}

function adgreenlng_stat_fetch($id)
{
    $sql = "SELECT * FROM " . tablename('adgreenlng_stat') . " WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function adgreenlng_stat_fetch_daytime($daytime)
{
    $sql = "SELECT * FROM " . tablename('adgreenlng_stat') . " WHERE daytime=:daytime";
    $params = array(':daytime' => $daytime);
    return pdo_fetch($sql, $params);
}

function adgreenlng_stat_fetchall($filter = array(), $orderby = '', $start = 0, $pagesize = 10, $keyfield = 'daytime')
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['daytime'])) {
        $where .= ' AND daytime=:daytime';
        $params[':daytime'] = $filter['daytime'];
    }
    if ($orderby == '') {
        $orderby = 'ORDER BY id DESC';
    }
    $sql = "SELECT * FROM " . tablename('adgreenlng_stat') . " {$where} {$orderby}";
    if ($pagesize > 0) {
        $sql .= " LIMIT {$start},{$pagesize}";
    }
    return pdo_fetchall($sql, $params, $keyfield);
}

function adgreenlng_stat_update_count($daytime, $field, $value = 1)
{
    global $_W;
    if (!in_array($field, array('house_views', 'house_shares', 'house_comments'))) {
        return false;
    }
    $row = adgreenlng_stat_fetch_daytime($daytime);
    if (!$row) {
        $data = array('uniacid' => $_W['uniacid'], 'daytime' => $daytime, $field => $value);
        return adgreenlng_stat_insert($data) ? true : false;
    } else {
        $sql = 'UPDATE ' . tablename('adgreenlng_stat') . " SET {$field}={$field}+{$value} WHERE id=:id";
        $params = array(':id' => $row['id']);
        return pdo_query($sql, $params) > 0 ? true : false;
    }
}

function adgreenlng_stat_update($data, $condition)
{
    return pdo_update('adgreenlng_stat', $data, $condition);
}

function adgreenlng_stat_delete($id)
{
    return pdo_delete('adgreenlng_stat', array('id' => $id));
}

function adgreenlng_stat_insert($data)
{
    pdo_insert('adgreenlng_stat', $data);
    return pdo_insertid();
}

function adgreenlng_navigation_data($module_config)
{
    global $_W;
    $looking_isshow = 1;
    if (!$module_config['looking']['switch']) {
        $looking_isshow = 0;
    }
    return array(array('icon' => 'fa fa-home', 'title' => '首页', 'url' => murl('entry', array('do' => 'home', 'm' => 'ad_greenlng')), 'displayorder' => '5', 'isshow' => '1'), array('icon' => 'fa fa-eye', 'title' => '看房团', 'url' => murl('entry', array('do' => 'looking', 'm' => 'ad_greenlng')), 'displayorder' => '4', 'isshow' => $looking_isshow), array('icon' => 'fa fa-child', 'title' => '经纪人', 'url' => murl('entry', array('do' => 'partner', 'm' => 'ad_greenlng')), 'displayorder' => '3', 'isshow' => '1'), array('icon' => 'fa fa-external-link', 'title' => '占位', 'url' => murl('entry', array('do' => 'home', 'm' => 'ad_greenlng')), 'displayorder' => '2', 'isshow' => '0'));
}

function adgreenlng_navigation_fetch($id)
{
    $sql = "SELECT * FROM " . tablename('adgreenlng_navigation') . " WHERE id=:id";
    $params = array(':id' => $id);
    return pdo_fetch($sql, $params);
}

function adgreenlng_navigation_fetchall($filter = array(), $orderby = '', $start = 0, $pagesize = 10)
{
    global $_W;
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['isshow'])) {
        $where .= ' AND isshow=:isshow';
        $params[':isshow'] = $filter['isshow'];
    }
    if ($orderby == '') {
        $orderby = 'ORDER BY displayorder DESC, id DESC';
    }
    $limit = '';
    if ($pagesize > 0) {
        $limit = "LIMIT {$start},{$pagesize}";
    }
    $sql = "SELECT * FROM " . tablename('adgreenlng_navigation') . " {$where} {$orderby} {$limit}";
    return pdo_fetchall($sql, $params);
}

function adgreenlng_navigation_count($filter = array())
{
    $where = ' WHERE 1=1';
    $params = array();
    if (isset($filter['uniacid'])) {
        $where .= ' AND uniacid=:uniacid';
        $params[':uniacid'] = $filter['uniacid'];
    }
    if (isset($filter['isshow'])) {
        $where .= ' AND isshow=:isshow';
        $params[':isshow'] = $filter['isshow'];
    }
    $sql = "SELECT COUNT(*) FROM " . tablename('adgreenlng_navigation') . " {$where}";
    return pdo_fetchcolumn($sql, $params);
}

function adgreenlng_navigation_update($data, $condition)
{
    return pdo_update('adgreenlng_navigation', $data, $condition);
}

function adgreenlng_navigation_delete($id)
{
    return pdo_delete('adgreenlng_navigation', array('id' => $id));
}

function adgreenlng_navigation_insert($data)
{
    pdo_insert('adgreenlng_navigation', $data);
    return pdo_insertid();
}

function adgreenlng_share_add_credit($uniacid, $uid, $share_action, $check_cookie, $credit, $friend_uid = 0)
{
    $cookie_time = strtotime(date('Y-m-d')) + 86400 - TIMESTAMP;
    isetcookie($check_cookie, 1, $cookie_time);
    $credit_type = $credit['type'];
    $credit_value = $credit['value'];
    $today_limit = $credit['limit'];
    $time_start = strtotime(date('Y-m-d'));
    $time_end = $time_start + 86400;
    $sql = 'SELECT SUM(`credit_value`) FROM ' . tablename('mc_handsel');
    $sql .= ' WHERE `uniacid` = ' . $uniacid;
    $sql .= ' AND `touid` = ' . $uid;
    $sql .= " AND `module` = 'ad_greenlng'";
    $sql .= " AND `action` IN ('housedetailshare', 'partnershare')";
    $sql .= ' AND `createtime` > ' . $time_start;
    $sql .= ' AND `createtime` < ' . $time_end;
    $today_value = pdo_fetchcolumn($sql);
    $share_action_title = '';
    if ($share_action == 'housedetailshare') {
        $share_action_title = '分享楼盘获得积分';
    } else {
        if ($share_action == 'partnershare') {
            $share_action_title = '邀请好友获得积分';
        }
    }
    if ($today_value + $credit_value <= $today_limit) {
        $data = array('uniacid' => $uniacid, 'touid' => $uid, 'fromuid' => 0, 'module' => 'ad_greenlng', 'sign' => md5(TIMESTAMP . $uid), 'action' => $share_action, 'credit_value' => $credit_value, 'createtime' => TIMESTAMP);
        pdo_insert('mc_handsel', $data);
        if ($friend_uid > 0) {
            $log = array($uid, $share_action_title . "({$credit_type}={$credit_value}, 好友id={$friend_uid})");
        } else {
            $log = array($uid, $share_action_title . "({$credit_type}={$credit_value})");
        }
        mc_credit_update($uid, $credit_type, $credit_value, $log);
    } else {
        WeUtility::logging('warning', '加积分失败，超过每天可以获得的积分数量上限，limit=' . $today_limit . ',uid=' . $uid . ',action=' . $share_action . ',credit=' . $credit_value . ',today_value=' . $today_value);
    }
}

function adgreenlng_get_credits()
{
    global $_W;
    $credits = array();
    $credits['credit1'] = array('enabled' => 0, 'title' => '');
    $credits['credit2'] = array('enabled' => 0, 'title' => '');
    $credits['credit3'] = array('enabled' => 0, 'title' => '');
    $credits['credit4'] = array('enabled' => 0, 'title' => '');
    $credits['credit5'] = array('enabled' => 0, 'title' => '');
    $list = pdo_fetch("SELECT creditnames FROM " . tablename('uni_settings') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
    if (!empty($list['creditnames'])) {
        $list = iunserializer($list['creditnames']);
        if (is_array($list)) {
            foreach ($list as $k => $v) {
                $credits[$k] = $v;
            }
        }
    }
    return $credits;
}

function adgreenlng_user_fetch_by_uid($uid)
{
    $sql = "SELECT * FROM " . tablename('users') . " WHERE uid=:uid";
    $params = array(':uid' => $uid);
    return pdo_fetch($sql, $params);
}

function adgreenlng_credit_type($type = '')
{
    global $_W;
    static $data = null;
    if ($data !== null) {
        if ($type != '') {
            return $data[$type]['title'];
        }
        return $data;
    }
    $data = pdo_fetch('SELECT `creditnames` FROM ' . tablename('uni_settings') . ' WHERE `uniacid` = :uniacid', array(':uniacid' => $_W['uniacid']));
    if (!empty($data['creditnames'])) {
        $data = iunserializer($data['creditnames']);
        if (is_array($data)) {
            if ($type) {
                foreach ($data as $k => $v) {
                    if ($type == $k && $v['enabled'] == 1) {
                        return $v['title'];
                    }
                }
            } else {
                return $data;
            }
        }
    }
}