<?php
define('IN_ECS',true);

/*实时库存警报*/
function timely_stock_alarm(){
    if(admin_priv('mod_stock_quantity','',false)){
        $mod_stock_quantity = true;
        $where = " AND confirm_sto_admin<>{$_SESSION['admin_id']} AND confirm_sto_times<2 AND edit_status=0";
    }

    $time_now = time();
    $sql_select = 'SELECT g.goods_sn,g.goods_name,SUM(quantity) AS quantity,warn_number FROM '
        .$GLOBALS['ecs']->table('stock_goods').' s LEFT JOIN '.$GLOBALS['ecs']->table('goods')
        .' g ON g.goods_sn=s.goods_sn'
        ." WHERE g.is_delete=0 $where AND quantity<>0 AND warn_number<>0 GROUP BY s.goods_sn ORDER BY quantity DESC";
    $stock_goods = $GLOBALS['db']->getAll($sql_select);

    foreach($stock_goods as $val){
        if($val['quanttiy'] <= $val['warn_number']){
            $alarm_stock_goods[] = $val;
        }else{
            $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('stock_goods')
                ." SET confirm_sto_admin=0,confirm_sto_times=0,predict_arrival_time=0,edit_status=0"
                ." WHERE goods_sn='{$val['goods_sn']}'";
            $GLOBALS['db']->query($sql_update);
        }
    }

    if($mod_stock_quantity){
        $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('stock_goods').' SET edit_status=1 '
            ." WHERE goods_sn='{$alarm_stock_goods[0]['goods_sn']}'";
        $GLOBALS['db']->query($sql_update);

        return $alarm_stock_goods[0];
    }else{
        return $alarm_stock_goods;
    }
}
?>
