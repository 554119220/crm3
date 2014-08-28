<?php

define('IN_ECS', true);
define('CURRDIR', dirname(__FILE__));
require(CURRDIR.'/includes/init.php');
require(CURRDIR.'/includes/lib_c2c.php');
require(CURRDIR.'/jingdong/JdClient.php');


/* 将获取到的数据保存到相应的记录中 */
if (isset($_REQUEST['state']) && $_REQUEST['state'] == 'taobao')
{
     require_once(CURRDIR.'/taobao/config.php');
     //请求参数
     $postfields = array(
          'grant_type'    => 'authorization_code',
          'client_id'     => $auth['appkey'],     // AppKey
          'client_secret' => $auth['secretKey'],  // secretKey
          'code'          => $_REQUEST['code'],   // 获取到的授权码
          'redirect_uri'  => 'http://192.168.1.217/crm2/admin/synchro.php' // 回调地址
     );

      $url = 'https://oauth.taobao.com/token'; // 正式环境下的sessionkey获取链接
     //$url = 'https://oauth.tbsandbox.com/token';

     $token = json_decode(curl($url,$postfields), true);

     if (file_exists(CURRDIR.'/taobao/sk.php'))
     {
          unlink(CURRDIR.'/taobao/sk.php');
     }

     $config = var_export($token, true);
     file_put_contents(CURRDIR.'/taobao/sk.php', '<?php'."\n".'$sk = '.$config.';');

     die('<script>window.close();</script>');
}
elseif (isset($_REQUEST['state']) && $_REQUEST['state'] != 'taobao')
{
     $auth = require_once(CURRDIR.'/jingdong/config.php');
     $jd = new JdClient;

     $apiParams = array (
          'grant_type'    => 'authorization_code',
          'client_id'     => $auth['appkey'],
          'client_secret' => $auth['secretKey'],
          'scope'         => 'read',
          'redirect_uri'  => 'http://192.168.1.217/crm2/admin/synchro.php',
          'code'          => $_REQUEST['code'],
          'state'         => '1'
     );

     $url = 'http://auth.360buy.com/oauth/token';
     $token = $jd->sendHttpRequest($url, $apiParams);
     $token = json_decode($token, true);

     if (file_exists(CURRDIR.'/jingdong/sk.php'))
     {
          unlink(CURRDIR.'/jingdong/sk.php');
     }

     $config = var_export($token, true);
     file_put_contents(CURRDIR.'/jingdong/sk.php', '<?php'."\n".'$sk = '.$config.';');

     die('<script>window.close();</script>');
}


if ($_REQUEST['act'] == 'synchro')
{
     // 延长客服在线时间
     $admin_id = intval($_REQUEST['admin_id']);
     $sql = 'UPDATE '.$GLOBALS['ecs']->table('admin_user').
          " SET timeout=UNIX_TIMESTAMP()+600 WHERE user_id=$admin_id";// AND order_assign=1";
     $GLOBALS['db']->query($sql);

     // 初始化时间
     $now_time = time();
     $start    = date('Y-m-d H:i:s', $now_time);
     $end      = date('Y-m-d H:i:s', $now_time+3600*8);

     // 引入第三方平台配置文件
     if ($_REQUEST['platform'] == 'taobao')
     {
          if (file_exists(CURRDIR.'/taobao/sk.php'))
          {
               require_once(CURRDIR.'/taobao/sk.php');
          }
          else 
          {
               authorize($auth['appkey'], 'taobao');
          }

          require_once(CURRDIR.'/taobao/config.php');
          require_once(CURRDIR.'/taobao/order_synchro.php');

          $c = new TopClient;
          $c->appkey = $auth['appkey'];
          $c->secretKey = $auth['secretKey'];
          $req = new TradesSoldIncrementGetRequest;
          $req->setFields("seller_nick,buyer_nick,title,type,created,sid,tid,seller_rate,buyer_rate,status,payment,discount_fee,adjust_fee,post_fee,total_fee,pay_time,end_time,modified,consign_time,buyer_obtain_point_fee,point_fee,real_point_fee,received_payment,commission_fee,pic_path,num_iid,num_iid,num,price,cod_fee,cod_status,shipping_type,receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,receiver_mobile,receiver_phone,orders.title,orders.pic_path,orders.price,orders.num,orders.iid,orders.num_iid,orders.sku_id,orders.refund_status,orders.status,orders.oid,orders.total_fee,orders.payment,orders.discount_fee,orders.adjust_fee,orders.sku_properties_name,orders.item_meal_name,orders.buyer_rate,orders.seller_rate,orders.outer_iid,orders.outer_sku_id,orders.refund_id,orders.seller_type");

          // 获取taobao特定时间段内的订单数据
          $req->setStartModified($start);
          $req->setEndModified($end);

          $resp = $c->execute($req, $sk['access_token']);

          if ($resp['total_results'] > 0)
          {
               $order_list = $resp['trades']['trade'];
               foreach ($order_list as $val)
               {
                    $order_info = array ();
                    $user_info  = array ();

                    /* 如果订单已经存在，则跳过该订单 */
                    /* 如果订单状态不是 等待卖家发货，则跳过该订单 */
                    if ($val['status'] != 'WAIT_SELLER_SEND_GOODS') continue;

                    // 查询临时订单表中  该订单是否已经存在
                    $sql = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('ordersyn_info').
                         " WHERE order_sn='".number_format($val['tid'], 0, '', '')."'";

                    /* 如果订单已经存在，则跳过该订单 */
                    if ($GLOBALS['db']->getOne($sql)) continue;

                    // 查询正式订单表中 该订单是否已经存在
                    $sql = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('order_info').
                         " WHERE order_sn='".number_format($val['tid'], 0, '', '')."'";
                    if ($GLOBALS['db']->getOne($sql)) continue;

                    /* 顾客信息 */
                    $user_info = array (
                         'consignee' => trim($val['receiver_name']),
                         'country'   => 1,
                         'zipcode'   => trim($val['receiver_zip']),
                         'tel'       => trim($val['receiver_phone']),
                         'mobile'    => trim($val['receiver_mobile']),
                         'email'     => trim($val['buyer_email']),
                         'aliww'     => trim($val['buyer_nick'])
                    );

                    $pcd = getProCitDis($val['receiver_state'], $val['receiver_city'], $val['receiver_district']);
                    if ($pcd === false)
                    {
                         $user_info['province'] = 0;
                         $user_info['city']     = 0;
                         $user_info['district'] = 0;
                         $user_info['address']  = $val['receiver_state'].$val['receiver_city'].$val['receiver_district'].$val['receiver_address'];
                    }
                    else 
                    {
                         $user_info['province'] = $pcd['state'];
                         $user_info['city']     = $pcd['city'];
                         $user_info['district'] = $pcd['district'];
                         $user_info['address']  = $address;
                    }

                    /* 订单信息 */
                    $order_info = array (
                         'goods_amount' => $val['payment'] - $val['post_fee'],
                         'shipping_fee' => bcadd($val['post_fee'], $val['cod_fee'], 2),
                         'final_amount' => $val['payment'],
                         'add_time'     => strtotime($val['created']),
                         'confirm_time' => strtotime($val['pay_time']),
                         'remarks'      => trim($val['buyer_memo']),
                         'to_seller'    => trim(strstr($val['seller_memo'], '#')),
                         'order_sn'     => number_format($val['tid'], 0, '', ''),
                         'team'         => 6,
                         'syn_time'     => time(),
                         'pay_id'       => $val['type'] == 'cod' ? 3 : 10,
                         'pay_name'     => $val['type'] == 'cod' ? '货到付款' : '支付宝',
                         'platform'     => 6
                    );

                    if ($val['type'] != 'cod')
                    {
                         $region = array (
                              'zip'      => $val['receiver_zip'],
                              'state'    => $val['receiver_state'],
                              'city'     => $val['receiver_city'],
                              'district' => $val['receiver_district']
                         );

                         $shipping = get_shipping($region);

                         $order_info['shipping_id']   = $shipping['id'];
                         $order_info['shipping_name'] = $shipping['name'];
                         $order_info['shipping_code'] = $shipping['code'];
                    }

                    /* 判断顾客是否已存在 */
                    $user = userIsExist($user_info);

                    if ($user)
                    {
                         /* 如果顾客已存在，将订单归到该顾客名下 */
                         $user_info['user_id']   = $user['user_id'];
                         $order_info['admin_id'] = $user['admin_id'];
                         $order_info['platform'] = $user['role_id'];

                         /* 分配订单 */
                         if ($order_info['admin_id'])
                         {
                              $order_info['operator'] = $order_info['admin_id'];
                         }
                         else
                         {
                              $sql = 'SELECT operator FROM '.$GLOBALS['ecs']->table('order_info').
                                   " WHERE user_id={$user_info['user_id']} AND operator<>0";
                              $order_info['operator'] = $GLOBALS['db']->getOne($sql);
                         }
                    }
                    else 
                    {
                         /* 如果顾客不存在，将顾客信息录入数据库  */
                         $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('userssyn').
                              '(user_name, home_phone, mobile_phone, email, aliww, from_where,
                              add_time, customer_type, role_id)VALUES('."'{$user_info['consignee']}',
                              '{$user_info['tel']}', '{$user_info['mobile']}', '{$user_info['email']}',
                              '{$user_info['aliww']}', 3, UNIX_TIMESTAMP(), 2,6)";
                         $GLOBALS['db']->query($sql);

                         $user_info['user_id'] = $GLOBALS['db']->insert_id();

                         //是否是网络上的黑名单顾客
                         put_in_blacklist($user_info['user_id']);

                         /* 将顾客的收货地址录入数据库 */
                         /*$sql = 'INSERT INTO '.$GLOBALS['ecs']->table('usersyn_address').
                              '(user_id, email, country, province, city, district, address, zipcode,
                              tel, mobile)VALUES('."{$user_info['user_id']}, '{$user_info['buyer_email']}', 1,
                              '{$pcd['province']}', '{$pcd['city']}', '{$pcd['district']}', 
                              '{$user_info['address']}', '{$user_info['zipcode']}', '{$user_info['tel']}', 
                              '{$user_info['mobile']}')";
                         $GLOBALS['db']->query($sql);
                         $address_id = $GLOBALS['db']->insert_id();

                         $sql = 'UPDATE '.$GLOBALS['ecs']->table('userssyn').
                              " SET address_id=$address_id WHERE user_id={$user_info['user_id']}";
                         $GLOBALS['db']->query($sql);
                          */

                         $order_info['operator'] = orderAssign();
                    }

                    unset($user_info['aliww']);

                    /* 生成订单SQL */
                    $sql = createOrderSql($user_info, $order_info, $val['orders']);
                    if (submitSql($sql) === false)
                    {
                         $local['status'] = 'error';
                         $local['platform'] = 'taobao';
                         $local['errorMessage'] = $order_info['order_sn'].'订单提交失败';
                         die($json->encode($local));
                         continue;
                    }
               }
          }

          if ($resp['code'] == 27)
          {
               authorize($auth['appkey'], 'taobao');
          }
     }

     // 获取拍拍授权
     elseif ($_REQUEST['platform'] == 'paipai')
     {
          //  引入拍拍API文件
          require_once(dirname(__FILE__).'.\paipai\PaiPaiOpenApiOauth.php');
          $auth = require_once(dirname(__FILE__).'\paipai\config.php'); // 引入鉴权参数

          $sdk = new PaiPaiOpenApiOauth($auth['appkey'], $auth['secretKey'], $auth['sessionKey'], $auth['account']);

          $sdk->setDebugOn(false); // 关闭调试模式

          $sdk->setApiPath("/deal/sellerSearchDealList.xhtml");// 调用接口函数

          $sdk->setMethod("get");    // post 获取数据的方式
          $sdk->setCharset("utf-8"); // gbk 字符编码

          // 以下部分用于设置用户在调用相关接口时url中"?"之后的各个参数，如上述描述中的a=1&b=2&c=3
          $params = &$sdk->getParams();//注意，这里使用的是引用，故可以直接使用

          $params["sellerUin"] = $auth['account'];
          $params["zhongwen"]  = "cn";
          $params["pageSize"]  = "100";
          $params["tms_op"]    = "admin@855006089";
          $params["tms_opuin"] = $auth['account'];
          $params["tms_skey"]  = "@WXOgdqq16";
          $params['pureData']  = 1;
          $params['format']    = 'json';
          $params['listItem']  = 1;

          // 获取paipai该时间内的订单
          $params['timeType']  = 'CREATE';  // 时间类型：CREATE 创建时间 PAY 支付时间
          $params['timeBegin'] = $start;
          $params['timeEnd']   = $end;

          //设置http请求接受的主机名，默认是 api.buy.qq.com。此处用户可不用修改
          //$sdk->setHostName("apitest.buy.qq.com");
          // End参数设置

          //run
          $response = $sdk->invoke();
          $resp = json_decode($response, true);

          if ($resp['countTotal'] > 0)
          {
               $order_list = $resp['dealList'];
               foreach ($order_list as $val)
               {
                    $propertymask = array ();
                    if (!empty($val['propertymask']))
                    {
                         $propertymask = explode('_', $val['propertymask']);
                    }

                    if (in_array($val['dealState'], array('DS_WAIT_SELLER_DELIVERY', 'STATE_COD_WAIT_SHIP')) || (!empty($propertymask) && in_array('2048', $propertymask)))
                    {
                        // 查询临时订单表中  该订单是否已经存在
                         $sql = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('ordersyn_info').
                              " WHERE order_sn='{$val['dealCode']}'";
                         if ($GLOBALS['db']->getOne($sql)) continue; // 如果订单已经存在，则跳过该订单

                         // 查询正式订单表中 该订单是否已经存在
                         $sql = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('order_info').
                              " WHERE order_sn='{$val['dealCode']}'";
                         if ($GLOBALS['db']->getOne($sql)) continue; // 如果订单已经存在，则跳过该订单

                         /* 订单信息 */
                         $order_info = array (
                              'goods_amount'   => 0,
                              'shipping_fee'   => sprintf('%.2f', ($val['freight']+$val['dealPayFeeCommission'])/100),
                              'final_amount'   => sprintf('%.2f', $val['dealPayFeeTotal']/100),
                              'add_time'       => strtotime($val['createTime']),
                              'confirm_time'   => strtotime($val['createTime']),
                              //'remarks'      => trim(implode('',$val['buyerRemark'])).trim(implode('', $val['dealNote'])),
                              'order_sn'       => $val['dealCode'],
                              'team'           => 7,
                              'syn_time'       => time(),
                              //'order_detail' => trim($val['dealDetailLink']),
                              'pay_id'         => $val['dealPayType'] == 'TENPAY' ? 11 : 3,
                              'pay_name'       => $val['dealPayType'] == 'TENPAY' ? '财付通' : '货到付款',
                              'platform'       => 7
                         );

                         if (!empty($propertymask) && in_array('2048', $propertymask) && $val['dealState'] != 'DS_WAIT_SELLER_DELIVERY')
                         {
                              $pattern = '/\d+\.\d+/';
                              preg_match($pattern, $val['buyerRemark'], $match);
                              $order_info['shipping_fee'] = empty($match) ? $val['freight']/100 : bcadd($val['freight']/100, 15,2);
                              $order_info['final_amount'] = bcadd($order_info['final_amount'], 15, 2);
                              if (strpos($val['buyerRemark'], '货到付款') && $val['dealState'] == 'DS_WAIT_BUYER_PAY')
                              {
                                   $order_info['pay_id']   = 3;
                                   $order_info['pay_name'] = '货到付款';
                              }
                              unset($match);
                         }

                         /* 用户信息 */
                         $user_info = array (
                              'consignee' => trim($val['receiverName']),
                              'country'   => 1,
                              'zipcode'   => intval($val['receiverPostcode']),
                              'mobile'    => $val['receiverMobile'],
                              'qq'        => trim($val['buyerUin'])
                         );

                         $address = strrchr($val['receiverAddress'], ' ');
                         $region = substr($val['receiverAddress'], 0, strrpos($val['receiverAddress'], ' '));
                         $region = splitAddr($region);
                         $pcd = getProCitDis($region['state'], $region['city'], $region['district']);
                         if ($pcd === false)
                         {
                              $user_info['province'] = 0;
                              $user_info['city']     = 0;
                              $user_info['district'] = 0;
                              $user_info['address']  = $val['receiverAddress'];
                         }
                         else 
                         {
                              $user_info['province'] = $pcd['state'];
                              $user_info['city']     = $pcd['city'];
                              $user_info['district'] = $pcd['district'];
                              $user_info['address']  = $address;
                         }

                         if(is_array($val['receiverPhone']))
                              $user_info['tel'] = implode(',', $val['receiverPhone']);

                         if ($val['dealPayType'] == 'TENPAY')
                         {
                              array_pop($region);
                              array_unshift($region, $val['receiverPostcode']);
                              $shipping = get_shipping($region);

                              $order_info['shipping_id']   = $shipping['id'];
                              $order_info['shipping_name'] = $shipping['name'];
                              $order_info['shipping_code'] = $shipping['code'];
                         }

                         /* 查询买家是否是已购买的顾客 */
                         $user = userIsExist($user_info);

                         /* 买家是已购买的顾客 */
                         if ($user)
                         {
                              /* 如果顾客已存在，将订单归到该顾客名下 */
                              $user_info['user_id']   = $user['user_id'];
                              $order_info['admin_id'] = $user['admin_id'];
                              $order_info['platform'] = $user['role_id'];

                              /* 分配订单 */
                              if ($order_info['admin_id'])
                              {
                                   $order_info['operator'] = $order_info['admin_id'];
                              }
                              else
                              {
                                   $sql = 'SELECT operator FROM '.$GLOBALS['ecs']->table('ordersyn_info').
                                        " WHERE user_id={$user_info['user_id']} AND operator<>0";
                                   $order_info['operator'] = $GLOBALS['db']->getOne($sql);
                              }
                         }
                         /* 买家是新顾客 */
                         else 
                         {
                              $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('userssyn').
                                   '(user_name, home_phone, mobile_phone, email, add_time, customer_type, qq, from_where,role_id)
                                   VALUES('."'{$user_info['consignee']}', '{$user_info['tel']}', 
                                   '{$user_info['mobile']}', '{$user_info['email']}', UNIX_TIMESTAMP(), 2, '{$user_info['qq']}', 5, 7)";
                              $GLOBALS['db']->query($sql);
                              $user_info['user_id'] = $GLOBALS['db']->insert_id();

                              /* 将顾客的收货地址录入数据库 */
                              $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('usersyn_address').
                                   '(user_id, email, country, province, city, district, address, zipcode, tel, mobile)VALUES('."{$user_info['user_id']}, '{$user_info['buyer_email']}', 1, '{$user_info['province']}', '{$user_info['city']}', '{$user_info['district']}', '{$user_info['address']}', '{$user_info['zipcode']}', '{$user_info['tel']}', '{$user_info['mobile']}')";
                              $GLOBALS['db']->query($sql);
                              $address_id = $GLOBALS['db']->insert_id();

                              $sql = 'UPDATE '.$GLOBALS['ecs']->table('userssyn')." SET address_id=$address_id".
                                   " WHERE user_id={$user_info['user_id']}";
                              $GLOBALS['db']->query($sql);

                              /* 分配订单 */
                              $order_info['operator'] = orderAssign();
                         }

                         /* 生成订单SQL */
                         unset($user_info['qq']);
                         unset($goods_info);
                         foreach ($val['itemList'] as $val)
                         {
                              if (isset($val['itemLocalCode']))
                              {
                                   $goods_info['order'][] = array (
                                        'outer_iid' => $val['itemLocalCode'],
                                        'price'     => sprintf('%.2f', $val['itemDealPrice']/100),
                                        'num'       => $val['itemDealCount'],
                                        'title'     => $val['itemName']
                                   );
                              }
                              else
                              {
                                   foreach ($val as $v)
                                   {
                                        $goods_info['order'][] = array (
                                             'outer_iid' => $v['itemLocalCode'],
                                             'price'     => sprintf('%.2f', $v['itemDealPrice']/100),
                                             'num'       => $v['itemDealCount'],
                                             'title'     => $v['itemName']
                                        );
                                   }
                              }

                              $order_info['goods_amount'] += sprintf('%.2f', $val['itemDealPrice']*$val['itemDealCount']/100);
                         }

                         $sql = createOrderSql($user_info, $order_info, $goods_info);
                         if (submitSql($sql) === false)
                         {
                              $resp['status'] = 'error';
                              $resp['platform'] = 'paipai';
                              $resp['errorMessage'] = $order_info['order_sn'].'订单提交失败';
                              echo $json->encode($resp);
                              continue;
                         }
                    }
               }
          }
     }

     // 获取京东授权
     elseif ($_REQUEST['platform'] == 'jingdong')
     {
          $auth = require(CURRDIR.'\jingdong\config.php');

          if (file_exists(CURRDIR.'\jingdong\sk.php'))
          {
               require(CURRDIR.'\jingdong\sk.php');
               require(CURRDIR.'\jingdong\request\order\OrderSearchRequest.php');
          }
          else 
          {
               authorize($auth['appkey'], 'jingdong');
          }

          $req = new OrderSearchRequest;

          $req->setStartDate($start); // 开始时间
          $req->setEndDate($end);     // 结束时间

          $req->setPage(1);     // 页码
          $req->setPageSize(100); // 每页显示数量
          $req->setOrderState('WAIT_SELLER_STOCK_OUT');  // 订单状态

          //$req->setOptionalFields("vender_id,order_id,pay_type");

          $req->secretKey;
          $jd = new JdClient;

          $jd->appKey      = $auth['appkey'];     // 京东AppKey
          $jd->appSecret   = $auth['secretKey'];  // 京东AppSecret
          $jd->accessToken = $sk['access_token']; // 京东sessionkey(access_token)

          $resp = $jd->execute($req);
          $resp = json_decode(json_encode($resp), true);
          print_r($resp);

          if ($resp['order_search_response']['order_search']['order_total'] > 0)
          {
               $order_list = $resp['order_search_response']['order_search']['order_info_list'];
               foreach ($order_list as $val)
               {
                    $order_info = array ();
                    $user_info  = array ();

                    /* 如果订单已经存在，则跳过该订单 */
                    /* 如果订单状态不是 等待卖家发货，则跳过该订单 */
                    if ($val['order_state'] != 'WAIT_SELLER_STOCK_OUT') continue;

                    // 查询临时订单表中，该订单是否已经存在
                    $sql = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('ordersyn_info').
                         " WHERE order_sn='".number_format($val['order_id'], 0, '', '')."'";

                    /* 如果订单已经存在，则跳过该订单 */
                    if ($GLOBALS['db']->getOne($sql)) continue;

                    // 查询正式订单表中 该订单是否已经存在
                    $sql = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('order_info').
                         " WHERE order_sn='".number_format($val['order_id'], 0, '', '')."'";
                    if ($GLOBALS['db']->getOne($sql)) continue;


                    /* 顾客信息 */
                    $user_info = array (
                         'consignee' => trim($val['consignee_info']['fullname']),
                         'country'   => 1,
                         'tel'       => trim($val['consignee_info']['telephone']),
                         'mobile'    => trim($val['consignee_info']['mobile']),
                    );

                    $pcd = getProCitDis($val['consignee_info']['province'], $val['consignee_info']['city'], $val['consignee_info']['country']);
                    if ($pcd === false)
                    {
                         $user_info['province'] = 0;
                         $user_info['city']     = 0;
                         $user_info['district'] = 0;
                         $user_info['address']  = $val['consignee_info']['province'].$val['consignee_info']['city'].$val['consignee_info']['country'].$val['consignee_info']['full_address'];

                    }
                    else 
                    {
                         $user_info['province'] = $pcd['state'];
                         $user_info['city']     = $pcd['city'];
                         $user_info['district'] = $pcd['district'];
                         $user_info['address']  = $val['consignee_info']['full_address'];
                    }

                    /* 订单信息 */
                    $order_info = array (
                         'goods_amount'  => bcsub($val['order_total_price'], $val['seller_discount'], 2),
                         'shipping_fee'  => $val['freight_price'],
                         'final_amount'  => $val['order_payment'],
                         'add_time'      => strtotime($val['order_start_time']),
                         'confirm_time'  => strtotime($val['order_start_time']),
                         'remarks'       => trim($val['order_remark']),
                         'delivery_type' => trim($val['delivery_type']),
                         'inv_type'      => trim($val['invoice_info']),
                         'to_seller'     => trim($val['order_remark']),
                         'order_sn'      => number_format($val['order_id'], 0, '', ''),
                         'team'          => 10,
                         'syn_time'      => time()+8*3600,
                         'pay_id'        => $val['type'] == 'cod' ? 3 : 16,
                         'pay_name'      => $val['type'] == 'cod' ? '货到付款' : '京东在线支付',
                         'platform'      => 10
                    );
               
                    if ($val['type'] != 'cod')
                    {
                         $region = array (
                              'zip'      => $val['receiver_zip'],
                              'state'    => $val['receiver_state'],
                              'city'     => $val['receiver_city'],
                              'district' => $val['receiver_district']
                         );

                         $shipping = get_shipping($region);

                         $order_info['shipping_id']   = $shipping['id'];
                         $order_info['shipping_name'] = $shipping['name'];
                         $order_info['shipping_code'] = $shipping['code'];
                    }

                    /* 判断顾客是否已存在 */
                    $user = userIsExist($user_info);

                    if ($user)
                    {
                         /* 如果顾客已存在，将订单归到该顾客名下 */
                         $user_info['user_id']   = $user['user_id'];
                         $order_info['admin_id'] = $user['admin_id'];
                         $order_info['platform'] = $user['role_id'];

                         /* 分配订单 */
                         if ($order_info['admin_id'])
                         {
                              $order_info['operator'] = $order_info['admin_id'];
                         }
                         else
                         {
                              $sql = 'SELECT operator FROM '.$GLOBALS['ecs']->table('ordersyn_info').
                                   " WHERE user_id={$user_info['user_id']} AND operator<>0";
                              $order_info['operator'] = $GLOBALS['db']->getOne($sql);
                         }
                    }
                    else 
                    {
                         /* 如果顾客不存在，将顾客信息录入数据库  */
                         $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('userssyn').
                              '(user_name, home_phone, mobile_phone, email, from_where, add_time,
                              customer_type, role_id)VALUES('."'{$user_info['consignee']}',
                              '{$user_info['tel']}', '{$user_info['mobile']}', '{$user_info['email']}',
                              3, UNIX_TIMESTAMP(), 2, 10)";
                         $GLOBALS['db']->query($sql);

                         $user_info['user_id'] = $GLOBALS['db']->insert_id();

                         /* 将顾客的收货地址录入数据库 */
                         $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('usersyn_address').
                              '(user_id, email, country, province, city, district, address, zipcode,
                              tel, mobile)VALUES('."{$user_info['user_id']}, '{$user_info['buyer_email']}', 1,
                              '{$pcd['province']}', '{$pcd['city']}', '{$pcd['district']}', 
                              '{$user_info['address']}', '{$user_info['zipcode']}', '{$user_info['tel']}', 
                              '{$user_info['mobile']}')";
                         $GLOBALS['db']->query($sql);
                         $address_id = $GLOBALS['db']->insert_id();

                         $sql = 'UPDATE '.$GLOBALS['ecs']->table('userssyn').
                              " SET address_id=$address_id WHERE user_id={$user_info['user_id']}";
                         $GLOBALS['db']->query($sql);

                         $order_info['operator'] = orderAssign();
                    }

                    unset($goods_info);
                    foreach ($val['item_info_list'] as $val)
                    {
                         if (isset($val['sku_id']))
                         {
                              $goods_info['order'][] = array (
                                   'outer_iid' => $val['outer_sku_id'],
                                   'price'     => $val['jd_price'],
                                   'num'       => $val['item_total'],
                                   'title'     => $val['sku_name']
                              );
                         }
                         else
                         {
                              foreach ($val as $v)
                              {
                                   $goods_info['order'][] = array (
                                        'outer_iid' => $v['outer_sku_id'],
                                        'price'     => $v['jd_price'],
                                        'num'       => $v['item_total'],
                                        'title'     => $v['sku_name']
                                   );
                              }
                         }

                         $order_info['goods_amount'] += $val['jd_price']*$val['item_total'];
                    }

                    /* 生成订单SQL */
                    $sql = createOrderSql($user_info, $order_info, $goods_info);
                    if (submitSql($sql) === false)
                    {
                         $local['status'] = 'error';
                         $local['platform'] = 'jingdong';
                         $local['errorMessage'] = $order_info['order_sn'].'订单提交失败';
                         die($json->encode($local));
                         continue;
                    }
               }
          }
     }

     die($json->encode(array ('main'=>'正在处理数据……')));
}

/**
 * 获取第三方平台授权
 */
function authorize ($appkey, $platform)
{
     global $json;
     $res['main']     = 'needAuth';
     $res['platform'] = $platform;//trim($_REQUEST['platform']);
     $res['appkey']   = $appkey;
     die($json->encode($res));
}

/**
 * 获取第三方平台sessionkey
 */
function curl($url, $postFields = null)
{
     $ch = curl_init();
     curl_setopt($ch, CURLOPT_URL, $url);
     curl_setopt($ch, CURLOPT_FAILONERROR, false);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

     if (is_array($postFields) && 0 < count($postFields))
     {
          $postBodyString = "";
          foreach ($postFields as $k => $v)
          {
               $postBodyString .= "$k=" . urlencode($v) . "&"; 
          }
          unset($k, $v);
          curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);  
          curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0); 
          curl_setopt ($ch, CURLOPT_POST, true);
          curl_setopt ($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
     }
     $reponse = curl_exec($ch);
     if (curl_errno($ch)){
          throw new Exception(curl_error($ch),0);
     }
     else{
          $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if (200 !== $httpStatusCode){
               throw new Exception($reponse,$httpStatusCode);
          }
     }

     curl_close($ch);
     return $reponse;
}
