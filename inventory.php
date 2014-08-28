<?php

function get_inventory_goods($order_list)
{
    foreach ($order_list as &$val)
    {
        $val['add_time']     = date('Y-m-d', $val['add_time']);   // Buy time
        $val['confirm_time'] = date('Y-m-d H:i',$val['confirm_time']); 

        $sql_select = 'SELECT goods_id,goods_name,goods_number,goods_price,is_package,goods_sn FROM '.
            $GLOBALS['ecs']->table('order_goods')." WHERE order_id={$val['order_id']}";
        $val['goods_list'] = $GLOBALS['db']->getAll($sql_select);
        $val['goods_kind'] = count($val['goods_list']);

        foreach ($val['goods_list'] as &$v)
        {
            if ($v['is_package'])
            {
                $sql_select = 'SELECT goods_name,num goods_number FROM '.
                    $GLOBALS['ecs']->table('packing_goods').' g,'.$GLOBALS['ecs']->table('packing').
                    " p WHERE p.packing_id=g.packing_id AND p.packing_desc='{$v['goods_sn']}'";
                $v['goods_list'] = $GLOBALS['db']->getAll($sql_select);
            }
        }
    }

    return $order_list;
}
?>
