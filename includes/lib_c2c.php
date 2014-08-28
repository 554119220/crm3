<?php
/**
 * ECSHOP 管理中心公用函数库
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lib_main.php 17217 2011-01-19 06:29:08Z liubo $
 */
if (!defined('IN_ECS'))
{
      die('Hacking attempt');
}


/**
 * 查询顾客是否已经存在
 * @param   string          $value 要查询的值
 * @param   string          $field 要查询的字段 QQ或阿里旺旺
 * @param   string          $mobile 手机号码 
 * @param   string          $tel    固话号码 
 * return   array|boolean   如果存在，则返回数组，不存在返回false
 */
function userIsExist ($user_info, $table_name = 'users')
{
     $sql = 'SELECT user_id,admin_id,role_id FROM '.$GLOBALS['ecs']->table($table_name).' WHERE ';
     if (!empty($user_info['qq']))
     {
          $where[] = " qq='{$user_info['qq']}' ";
     }

     if (!empty($user_info['aliww']))
     {
           $where[] = " aliww='{$user_info['aliww']}' ";
     }

     if (!empty($user_info['mobile']))
     {
          $where[] = " mobile_phone='{$user_info['mobile']}' ";
     }

     if (!empty($user_info['tel']))
     {
           $where[] = " home_phone='{$user_info['tel']}' ";
     }

     $sql .= implode(' OR ', $where);
     $user = $GLOBALS['db']->getRow($sql);

     if (empty($user)) return false;
     else return $user;
}

/**
 * 分割地址信息为数组
 * @param     string  $addr  地址
 * return     array          地址数组
 */
function splitAddr ($addr)
{
     $temp = explode(' ', $addr);
     if (count($temp) > 2)
     {
          $region['state'] = array_shift($temp);
          $region['city'] = array_shift($temp);
          $region['district'] = array_shift($temp);
          $region['a'] = implode('', $temp);
     }
     elseif ( count($temp) < 3)
     {
          $region['state'] = array_shift($temp);
          $region['city'] = array_shift($temp);
          $region['a'] = implode('', $temp);
     }

     return $region;
}

/**
 * 取得相应省市区的region_id，若无，则新建省/市/区
 * return   int   region_id
 */
function getProCitDis($state, $city, $district)
{
     // 查询省级ID
     $region = array();
     $state = mb_substr($state, 0, 2, 'utf-8').'%';
     $sql = 'SELECT region_id FROM '.$GLOBALS['ecs']->table('region').
          " WHERE region_name LIKE '$state'";
     $region['state'] = $GLOBALS['db']->getOne($sql);

     if (empty($region['state'])) return false;

     $city = mb_substr($city, 0, 2, 'utf-8').'%';
     $sql = 'SELECT region_id FROM '.$GLOBALS['ecs']->table('region').
          " WHERE region_name LIKE '$city' AND parent_id={$region['state']}";
     $region['city'] = $GLOBALS['db']->getOne($sql);

     if (empty($region['city'])) return false;
     if (empty($district)) return $region;

     $district = mb_substr($district, 0, 2, 'utf-8').'%';
     $sql = 'SELECT region_id FROM '.$GLOBALS['ecs']->table('region').
          " WHERE region_name LIKE '$district' AND parent_id={$region['city']}";
     $region['district'] = $GLOBALS['db']->getOne($sql);

     return $region;
}

/**
 * 新建省市区
 * @param   string    $proCitDis    省/市/区名
 * @param   int       $region_type  region类型   
 * return   int       新建省/市/区的region_id
 */
function createProCitDis ($proCitDis, $parent_id = 1, $region_type = 1)
{
     $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('region')
          .'(region_name, parent_id, region_type)VALUES('."'$proCitDis', $parent_id, $region_type)";
     $GLOBALS['db']->query($sql);
     return $GLOBALS['db']->insert_id();
}

/**
 * 生成订单SQL
 * @param     array     $user_info  顾客信息
 * @param     array     $order_info 订单信息
 * @param     array     $goods_info 商品信息
 * return     int       订单id
 */
function createOrderSql ($user_info, $order_info, $goods_info)
{
     $order  = array_merge($user_info, $order_info);
     $fields = implode(',', array_keys($order));
     $values = implode("','", array_values($order));

     $sql[] = 'INSERT INTO '.$GLOBALS['ecs']->table('ordersyn_info')."($fields)VALUES('$values')";
     //file_put_contents('sqluser.txt', $sql[0]."\n\r", FILE_APPEND);

     $temp_sn = '';
     foreach ($goods_info['order'] as $val)
     {
          extract($val);

          /* 如果是套餐 */
          if (strpos($outer_iid, 'T') !== false)
          {
               $outiid = strstr($outer_iid, '-', true);
               $sub_sql = 'SELECT packing_id FROM '.$GLOBALS['ecs']->table('packing')." WHERE packing_desc='{$outiid}'";
               $packing_id = $GLOBALS['db']->getOne($sub_sql);
               if (empty($packing_id)) continue;

               $sub_sql = 'SELECT goods_id, num FROM '.$GLOBALS['ecs']->table('packing_goods')." WHERE packing_id='{$packing_id}'";
               $goods = $GLOBALS['db']->getAll($sub_sql);
               if (empty($goods)) continue;

               foreach ($goods as $gid)
               {
                    $goods_id[$gid['goods_id']] = $gid['num'];
               }

               $keys = array_keys($goods_id);
               $sub_sql = 'SELECT goods_sn, goods_id FROM `crm_goods` WHERE goods_id IN ('.implode(',', $keys).')';
               $packing = $GLOBALS['db']->getAll($sub_sql);
               if (empty($packing)) continue;

               foreach ($packing as $v)
               {
                    $goods_num = $num*$goods_id[$v['goods_id']];
                    $sql[] = 'INSERT INTO '.$GLOBALS['ecs']->table('ordersyn_goods').
                         '(goods_sn, goods_price, goods_number, order_sn)VALUES('.
                         "'{$v['goods_sn']}', 0.1, $goods_num, '{$order['order_sn']}')";
               }
          }
          elseif ((strpos($title, 'TB_') !== false || strpos(trim($title), 'TC_') !== false) && $goods_sn = strstr(trim($title), '-', true))
          {
               $goods_sn = str_replace('【1111购物狂欢节】', '', $goods_sn);
               $pack_sql = 'SELECT packing_id FROM '.$GLOBALS['ecs']->table('packing').
                    " WHERE packing_desc='$goods_sn'";
               $packing_id = $GLOBALS['db']->getOne($pack_sql);

               if (empty($packing_id) || $temp_sn == $order['order_sn'])
               {
                    $temp_sn = $order['order_sn'];
                    continue;
               }

               $pack_sql = 'SELECT pg.goods_id, pg.goods_name, g.goods_sn, pg.num FROM '.
                    $GLOBALS['ecs']->table('packing_goods').' pg, '.
                    $GLOBALS['ecs']->table('goods').' g '.
                    " WHERE packing_id=$packing_id AND pg.goods_id=g.goods_id";
               $goods_list = $GLOBALS['db']->getAll($pack_sql);

               foreach ($goods_list as $v)
               {
                    $pNum = $v['num'] * $num; // 套餐数量
                    $sql[] = 'INSERT INTO '.$GLOBALS['ecs']->table('ordersyn_goods').
                         '(goods_sn, goods_name, goods_price, goods_number, order_sn)VALUES('.
                         "'{$v['goods_sn']}','{$v['goods_name']}', 0.1, {$pNum}, '{$order['order_sn']}')";
                    unset($v, $pNum);
               }

               // 根据购买的套餐查询赠品及数量
               $gift_info_package[$goods_sn] = read_gift_info ($order_info['team'], $order_info['confirm_time'], '', $goods_sn, $num);
               if (!implode('', $gift_info_package) && confirm_inventor_enough($gift_info_package[$goods_sn]))
               {
                    unset($gift_info_package[$goods_sn]);
                    $gift_info_package[$goods_sn] = read_gift_info ($order_info['team'], $order_info['confirm_time'], '', $goods_sn, $num, $gift_info_package['gift_id']);
               }
          }

          /* 如果是单品 */
          else 
          {
               if ($temp_sn == $order['order_sn']) continue;

               if (isset($total_fee))
               {
                    $price = $total_fee/$num;
                    unset($total_fee);
               }

               $sql[] = 'INSERT INTO '.$GLOBALS['ecs']->table('ordersyn_goods').
                    '(goods_sn, goods_price, goods_number, order_sn)VALUES('.
                    "'$outer_iid', $price, $num, '{$order['order_sn']}')";

               // 根据购买的商品查询赠品及数量
               $gift_info_item[$outer_iid] = read_gift_info ($order_info['team'], $order_info['confirm_time'], '', $outer_iid, $num);
               if (!implode('', $gift_info_item) && confirm_inventor_enough($gift_info_item[$outer_iid]))
               {
                    unset($gift_info_item[$outer_iid]);
                    $gift_info_item[$outer_iid] = read_gift_info ($order_info['team'], $order_info['confirm_time'], '', $outer_iid, $num, $gift_info_item['gift_id']);
               }
          }
     }

     // 如果没有活动赠品，则查询订单金额赠品
     count($gift_info_package) && $gift_info = $gift_info_package;
     count($gift_info_item) && $gift_info = $gift_info_item;

     $rres = implode('', $gift_info);
     if (empty($rres))
     {
          // 根据订单金额查询赠品
          $gift_info_total = read_gift_info($order_info['team'], $order_info['confirm_time'], $order_info['goods_amount'], '', '');

          // 判断赠品库存是否充足
          if ($gift_info_total && confirm_inventor_enough($gift_info_total))
          {
               unset($gift_info_total);
               $gift_info_total = read_gift_info ($order_info['team'], $order_info['confirm_time'], $order_info['goods_amount'], '', '', $gift_info_total['gift_id']);
          }
     }

     $gift_info[] = $gift_info_total;
     if (count($gift_info))
     {
          $sql[] = createGiftSql($gift_info, $order_info['order_sn']);
     }

     // 添加赠品到此完成，剩下的就是修改订单状态，确定订单归属
     // 添加赠品处需要增加选择套餐
     return $sql;
}

/**
 * 提交订单
 * @param     array     $sql 订单提交语句
 * return     boolean   订单提交结果
 */
function submitSql ($sql)
{
     if (!is_array($sql))
     {
          return false;
     }

     $GLOBALS['db']->query('BEGIN');
     foreach ($sql as $val)
     {
          if (empty($val))
          {
               continue;
          }
          if (!$GLOBALS['db']->query($val))
          {
               $GLOBALS['db']->query('ROLLBACK');
               return false;
          }
     }

     /* 更新订单商品表中的订单id */
     $sql = 'UPDATE '.$GLOBALS['ecs']->table('ordersyn_info').' i, '
          .$GLOBALS['ecs']->table('ordersyn_goods').' g SET '.
          'g.order_id=i.order_id WHERE g.order_sn=i.order_sn';
     $GLOBALS['db']->query($sql);

     /* 更新订单商品表中的商品信息 */
     $sql = 'UPDATE '.$GLOBALS['ecs']->table('ordersyn_goods').' o, '.
          $GLOBALS['ecs']->table('goods').' g SET '.
          "o.goods_id=g.goods_id, o.goods_name=g.goods_name, o.is_real=g.is_real WHERE o.goods_sn=g.goods_sn";
     $GLOBALS['db']->query($sql);

     return $GLOBALS['db']->query('COMMIT');
}


/**
 * 分配订单
 */
function orderAssign ()
{
     $sql = 'SELECT user_id FROM '.$GLOBALS['ecs']->table('admin_user')." WHERE assign=1";
     $res = $GLOBALS['db']->getAll($sql);
     foreach ($res as $val)
     {
          $user_id[] = $val['user_id'];
     }

     $sql = 'SELECT operator FROM '.$GLOBALS['ecs']->table('ordersyn_info').
          ' WHERE order_status=0 AND pay_status=0 AND shipping_status=0 '.
          ' AND operator IN ('.implode(',', $user_id).') GROUP BY operator ORDER BY COUNT(operator) ASC';
     $res = $GLOBALS['db']->getOne($sql);

     if (empty($res))
     {
          return $user_id[array_rand($user_id)];
     }

     return $res;
}

/**
 * 延长客服在线时间
 */
function extendOnlineTime ()
{
     date_default_timezone_set('Asia/Shanghai');
     $sql = 'UPDATE '.$GLOBALS['ecs']->table('admin_user').
          ' SET expiry=UNIX_TIMESTAMP()+600 ';
     if (date('w') == 0 || date('H') >= 18 || in_array(date('H'), array(12, 13)))
     {
          /* 如果是周日 或 时间是晚上18点以后*/
          $sql .= " WHERE role_id IN (6,7) AND assign=1";
     }
     else
     {
          /* 如果不是周日 */
          $sql .= " WHERE user_id={$_SESSION['admin_id']} AND assign=1";
     }

     $GLOBALS['db']->query($sql);
}

/**
 * 读取符合条件的赠品数据
 * @param   $order_amount   float      订单金额
 * @param   $goods_num      int        购买商品的数量
 * @param   $team           int        适用平台
 * @param   $now_time       timestamp  赠送的时间条件， 一般为当前时间
 * @param   $goods_sn       int/string 商品编码/套餐
 * @param   $drop_gift_id   int        库存不足的赠品记录，若有则启用备用的赠品
 */
function read_gift_info ($team, $now_time, $order_amount, $goods_sn = '', $goods_num = '', $drop_gift_id = '')
{
     $where    = array('level' => 'level=0', 'status=1'); // 赠品条件
     $order_by = '';    // 赠品条件

     if ($team)
     {
          $where[] = "platform='$team'";
     }

     if ($goods_sn)
     {
          $where[] = "goods_sn='$goods_sn'";

          if ($goods_num)
          {
               $where[] = "goods_num<='$goods_num'";
               $order_by = ' ORDER BY goods_num DESC';
          }

     }
     elseif ($order_amount)
     {
          $where[]  = "order_amount<=$order_amount AND order_amount<>0";
          $order_by = ' ORDER BY order_amount DESC';
     }
     else
     {
          return '';
     }


     if (!$now_time)
     {
          $now_time = time() +28800;
     }

     $where[] = "start_time<$now_time AND end_time>$now_time";

     if ($drop_gift_id)
     {
          $where[] = " gift_id<>$drop_gift_id";
          $where['level'] = 'level=1';
     }

     $sql = 'SELECT gift_info, goods_num, order_amount FROM '.
          $GLOBALS['ecs']->table('goods_gift').' WHERE '.implode(' AND ', $where).$order_by;
     $res = $GLOBALS['db']->getRow($sql);

     if ($goods_sn && $res['goods_num'] > $goods_num)
     {
          return '';
     }

     if ($order_amount && $res['order_amount'] > $order_amount)
     {
          return '';
     }

     if ($res['gift_info'])
          return $res['gift_info'];
     else
          return '';
}


/**
 * 确认库存中的赠品数量是否满足该订单的需求
 * @param   $gift   Array   赠品信息
 */
function confirm_inventor_enough ($gift)
{
     $gift_info = json_decode($gift, true);

     if (!is_array($gift_info))
     {
          return true;
     }

     foreach ($gift_info as $val)
     {
          // 获取该赠品的库存数量
          $sql = 'SELECT SUM(quantity) FROM '.
               $GLOBALS['ecs']->table('stock_goods').
               ' s, '.$GLOBALS['ecs']->table('goods').
               " g WHERE g.goods_id={$val['goods_id']} AND g.goods_sn=s.goods_sn";
          $goods_stock = $GLOBALS['db']->getOne($sql);

          // 该赠品库存不足
          if ($val['goods_num'] > $goods_stock)
          {
               return true;
          }
     }

     return $gift_info;
}

/**
 * 组装赠品SQL
 */
function createGiftSql ($gift_info, $order_sn)
{
     foreach ($gift_info as $val)
     {
          $temp = json_decode($val, true);
          if (empty($temp))
          {
               continue;
          }

          foreach ($temp as $val)
          {
               $name_sql = 'SELECT goods_name, goods_sn FROM '.$GLOBALS['ecs']->table('goods').
                    " WHERE goods_id='{$val['goods_id']}'";
               $goods_info = $GLOBALS['db']->getRow($name_sql);
               $values[] = "('{$val['goods_id']}', '{$val['goods_num']}', '{$goods_info['goods_name']}', '{$goods_info['goods_sn']}', '$order_sn')";
          }
     }

     count($values) && $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('ordersyn_goods').
          '(goods_id, goods_number, goods_name, goods_sn, order_sn)VALUES'.implode(',', $values);

     return $sql;
}

/**
 * 获取待确认的订单 
 */
function get_order_owner ($role_id)
{
     $now_time = time();
     $today_time = strtotime('2012-12-21 08:00:00');
     $sql = 'SELECT order_sn FROM '.$GLOBALS['ecs']->table('ordersyn_info').
          " WHERE order_status=0 AND pay_status=0 AND team=$role_id AND (syn_time+3600)<$now_time AND syn_time>$today_time AND pay_id<>3";
     return $GLOBALS['db']->getAll($sql);
}

/**
 * 根据地址信息查询淘宝的地址ID数据
 * @param   $region array  地址信息     
 * @param   $id     int    地址ID
 * return   最终地址ID
 */
function get_shipping ($region, $id = 0)
{
     $tmp = next($region);
     if (!$tmp)
     {
          return search_shipping($id);
     }

     $sql = 'SELECT id FROM '.$GLOBALS['ecs']->table('area_taobao').' WHERE name="'.$tmp.'"';
     if ($id) $sql .= " AND parent_id=$id";
     $res = $GLOBALS['db']->getOne($sql);

     if ($res)
     {
          return get_shipping($region, $res);
     }
}

/**
 * 确认配送方式
 * @param $target_id  int   淘宝目的地ID
 * return 根据配送优先级返回可用的一种配送方式
 */
function search_shipping ($target_id, $check = '')
{
     if (!class_exists('TopClient'))
     {
          return false;
     }
     $c            = new TopClient;
     $c->appkey    = '21219338';
     $c->secretKey = '225e440bb09d11f4bb1e76484c9c92c2';
     $c->format    = 'json';

     $req = new LogisticsPartnersGetRequest;
     $req->setServiceType('online');
     $req->setSourceId('440111');
     $req->setTargetId($target_id);

     $resp = $c->execute($req);

     // 未获取到有效的快递信息
     if (empty($resp['logistics_partners'])) return false;
     $shipping = $resp['logistics_partners']['logistics_partner'];

     foreach ($shipping as $val)
     {
          if ($check)
          {
               $ship[] = $val;
          }
          else 
          {
               $ship[] = $val['partner']['company_code'];
          }
     }

     if ($check)
     {
          return $ship;
     }
     else 
     {
          $search_ship = '("'.implode('","', $ship).'")';
          $sql = 'SELECT shipping_id id, shipping_name name, shipping_code code FROM '.
               $GLOBALS['ecs']->table('shipping').
               ' WHERE priority<>0 AND company_code IN '.
               " $search_ship ORDER BY priority ASC";
          $res = $GLOBALS['db']->getRow($sql);
     }

     return $res;
}

?>
