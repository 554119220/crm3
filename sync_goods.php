<?php
define('IN_ECS',true);
require(dirname('__FILE__'). '/includes/init.php');

$sql_select = 'SELECT goods_sn,goods_name FROM '.$GLOBALS['ecs']->table('goods')." GROUP BY goods_sn";
$goods_list = $GLOBALS['db']->getAll($sql_select);

$sql_select = 'SELECT goods_sn,goods_name FROM '.$GLOBALS['ecs']->table('stock_goods')." GROUP BY goods_sn";
$stock_list = $GLOBALS['db']->getAll($sql_select);

foreach($goods_list as $gkey=>&$goods){
   foreach($stock_list as $skey=>&$stock){
       if($stock['goods_name'] == $goods['goods_name'] && !empty($stock['goods_sn'])){
           //$sql_update = 'UPDATE '.$GLOBALS['ecs']->table('goods')." SET goods_sn={$stock['goods_sn']}";
           //$GLOBALS['db']->query($sql_update);
           $arr[] = $stock['goods_name'].'___'.$goods['goods_name'];
           unset($goods_list[$gkey]);
           unset($stock[$skey]);
       }

   } 
}
echo '<pre>';
print_r($arr);
echo '</pre>';
exit;
?>
