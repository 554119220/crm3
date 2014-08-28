<?php
define('IN_ECS', true);
require(dirname(__FILE__).'/includes/init.php');
date_default_timezone_set('Asia/Shanghai');

$sql_select = 'SELECT goods_name,comment FROM '.$GLOBALS['ecs']->table('temp_goods')
    .' ORDER BY goods_name DESC';
$temp_goods = $GLOBALS['db']->getAll($sql_select);

$sql_select = 'SELECT goods_name,goods_sn FROM '.$GLOBALS['ecs']->table('goods').
    ' ORDER BY goods_name DESC';

$goods = $GLOBALS['db']->getAll($sql_select);

foreach($temp_goods as $key=>&$temp){
    foreach($goods as &$desk_goods){
        if($temp['goods_name'] == $desk_goods['goods_name']){
            $sql_insert = 'INSERT INTO '.$GLOBALS['ecs']->table('knowlage').
                '(goods_sn,knowlage_name,add_time,add_admin,content,knowlage_class)'.
                "VALUES('{$desk_goods['goods_sn']}','{$temp['goods_name']}产品说明',{$_SERVER['REQUEST_TIME']},1,'{$temp['comment']}',2)";
            $result = $GLOBALS['db']->query($sql_insert);
            if (!$result) {
                file_put_contents('erroe_log.txt',"\r\n{$temp['goods_name']}",FILE_APPEND);
            }
            unset($temp[$key]);
        }
    }
}

?>
