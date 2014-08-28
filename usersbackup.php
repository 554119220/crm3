<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
date_default_timezone_set('Asia/Shanghai');
$file = basename($_SERVER['PHP_SELF'], '.php');
$smarty->assign('file', $file);

/* 保留搜索关键词 */
if (isset($_REQUEST['keywords']) || isset($_REQUEST['start_time']) || isset($_REQUEST['end_time']))
{
     $smarty->assign('kf', $_REQUEST['keyfields']);
     $smarty->assign('kw', urldecode($_REQUEST['keywords']));

     if (!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time']))
     {
          $smarty->assign('start_time', stamp2date($_REQUEST['start_time'], 'Y-m-d H:i'));
          $smarty->assign('end_time', stamp2date($_REQUEST['end_time'], 'Y-m-d H:i'));

//          $_REQUEST['start_time'] = strtotime(stamp2date($_REQUEST['start_time'], 'Y-m-d H:i:s'));
 //         $_REQUEST['end_time']   = strtotime(stamp2date($_REQUEST['end_time'], 'Y-m-d H:i:s'));
     }
}

/*-- 订单子菜单 --*/
if ($_REQUEST['act'] == 'menu')
{
     $nav = list_nav();
     $smarty->assign('nav_2nd', $nav[1][$file]);
     $smarty->assign('nav_3rd', $nav[2]);
     $smarty->assign('file_name', $file);

     die($smarty->fetch('left.htm'));
}

/* 意向顾客列表 */
if ($_REQUEST['act'] == 'intention')
{
      /* 检查权限 */
      //admin_priv('intention');
      $res = array ('switch_tag' => true, 'id' => 4);

      $sql = "SELECT rank_id, rank_name, min_points FROM ".
           $ecs->table('user_rank')." ORDER BY min_points ASC ";
      $rs = $db->query($sql);

      $ranks = array();
      while ($row = $db->FetchRow($rs))
      {
            $ranks[$row['rank_id']] = $row['rank_name'];
      }

      if (!isset($_REQUEST['type']))
      {
            $_REQUEST['type'] = '1, 12, 6, 7, 8';
      }
      else
      {
           $_REQUEST['type'] = urldecode($_REQUEST['type']);
      }

      $intention = get_customer_type($_REQUEST['type']);
      foreach ($intention as $val)
      {
            $intente[] = $val['type_name'];
      }

      $smarty->assign('action',       $_SESSION['action_list']);
      $smarty->assign('user_ranks',    $ranks);
      $smarty->assign('action_link',   array('text' => $_LANG['02_users_add'], 'href'=>'users.php?act=add'));
      $smarty->assign('country_list',  get_regions());
      $smarty->assign('province_list', get_regions(1,1));

      $user_list = user_list();

      /* 获取顾客来源、购买力、客服 */
      $smarty->assign('from_where', get_from_where());
      $smarty->assign('type_list',  get_customer_type());

      $smarty->assign('admin_list', get_admin('session'));
      $smarty->assign('eff_list',   getEffectTypes());

      $smarty->assign('ur_here',       $_LANG['06_intention'].'(包括：'.@implode(',', $intente).')');
      $smarty->assign('num', sprintf('（共%d条）', $user_list['record_count']));

      $smarty->assign('user_list', $user_list['user_list']);
      $smarty->assign('page_link',    $user_list['condition']);
      $smarty->assign('filter',       $user_list['filter']);
      $smarty->assign('record_count', $user_list['record_count']);
      $smarty->assign('page_count',   $user_list['page_count']);
      $smarty->assign('page_size',    $user_list['page_size']);
      $smarty->assign('page_start',   $user_list['start']);
      $smarty->assign('page_end',     $user_list['end']);
      $smarty->assign('full_page',    1);
      $smarty->assign('page_set',     $user_list['page_set']);
      $smarty->assign('page',         $user_list['page']);
      $smarty->assign('act',          $_REQUEST['act']);
      $smarty->assign('tag',          $_REQUEST['tag'] ? $_REQUEST['tag'] :0);
      $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

      assign_query_info();
      $res['main'] = $smarty->fetch('users_list.htm');

      $res['left'] = sub_menu_list($file);
      if ($res['left'] === false) unset($res['left']);

      die($json->encode($res));
}

/* 已购买顾客列表 */
elseif ($_REQUEST['act'] == 'users_list')
{
     /* 检查权限 */
     admin_priv('users_list');
     $res = array ('switch_tag' => true, 'id' => 0);

     if($_SESSION['admin_id'] == 78){
          $_REQUEST['type'] = '13';
     }
     elseif (!$_REQUEST['type']){
          $_REQUEST['type'] = '2, 3, 4, 5, 11';
     }

     $smarty->assign('user_ranks',   $ranks);
     $smarty->assign('ur_here',      $_LANG['01_users_list']);
     $smarty->assign('country_list',  get_regions());
     $smarty->assign('province_list', get_regions(1,1));

     $user_list = user_list();

     /* 获取顾客来源、购买力、客服 */
     $smarty->assign('from_where', get_from_where());
     $smarty->assign('type_list',  get_customer_type());
     $smarty->assign('admin_list', get_admin('session'));
     $smarty->assign('eff_list',   getEffectTypes());

     $smarty->assign('is_intention', $_REQUEST['act']);  // 意向顾客查询字段，为分页提供区分支持
     $smarty->assign('user_list',    $user_list['user_list']);

     // 分页设置
     $smarty->assign('page_link',    $user_list['condition']);
     $smarty->assign('filter',       $user_list['filter']);
     $smarty->assign('record_count', $user_list['record_count']);
     $smarty->assign('page_count',   $user_list['page_count']);
     $smarty->assign('page_size',    $user_list['page_size']);
     $smarty->assign('page_start',   $user_list['start']);
     $smarty->assign('page_end',     $user_list['end']);
     $smarty->assign('full_page',    1);
     $smarty->assign('page_set',     $user_list['page_set']);
     $smarty->assign('page',         $user_list['page']);
     $smarty->assign('act',          $_REQUEST['act']);
     $smarty->assign('tag',          $_REQUEST['tag'] ? $_REQUEST['tag'] :0);

     $smarty->assign('num', sprintf('（共%d条）', $user_list['record_count']));
     $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

     //判断客服的权限，是否显示团队搜索
     if($_SESSION['action_list'] == 'all')
     {
          $smarty->assign('admin_show_team',1);
          $smarty->assign('role_list', get_role());
     }
     else
     {
          $sql = 'SELECT manager,role_id FROM '.$GLOBALS['ecs']->table('admin_user').
               " WHERE user_id={$_SESSION['admin_id']}";
          $user = $GLOBALS['db']->getRow($sql);
          if($user['manager'] === '0')
          {
               $smarty->assign('role_id',$user['role_id']);
               $smarty->assign('show_team',1);
          }
     }

     // assign_query_info();

     $res['main'] = $smarty->fetch('users_list.htm');

     $res['left'] = sub_menu_list($file);
     if ($res['left'] === false) unset($res['left']);

     die($json->encode($res));
}

/* 顾客分类显示 */
elseif ($_REQUEST['act'] == 'users_list_group')
{
     /* 检查权限 */
     admin_priv('users_list_group');
     $res = array (
          'switch_tag' => true,
          'id' => !isset($_REQUEST['tag']) ? 0 : intval($_REQUEST['tag'])
     );

     if (!$_REQUEST['type'])
     {
          $_REQUEST['type'] = '2, 3, 4, 5, 11';
     }

     $smarty->assign('user_ranks',   $ranks);
     $smarty->assign('ur_here',      $_LANG['01_users_list']);
     $smarty->assign('country_list',  get_regions());
     $smarty->assign('province_list', get_regions(1,1));

     $user_list = user_list();

     /* 获取顾客来源、购买力、客服 */
     $smarty->assign('from_where', get_from_where());
     $smarty->assign('type_list',  get_customer_type());
     $smarty->assign('admin_list', get_admin('session'));
     $smarty->assign('eff_list',   getEffectTypes());

     $smarty->assign('is_intention', $_REQUEST['act']);  // 意向顾客查询字段，为分页提供区分支持
     $smarty->assign('user_list',    $user_list['user_list']);

     // 分页设置
     $smarty->assign('page_link',    $user_list['condition']);
     $smarty->assign('filter',       $user_list['filter']);
     $smarty->assign('record_count', $user_list['record_count']);
     $smarty->assign('page_count',   $user_list['page_count']);
     $smarty->assign('page_size',    $user_list['page_size']);
     $smarty->assign('page_start',   $user_list['start']);
     $smarty->assign('page_end',     $user_list['end']);
     $smarty->assign('full_page',    1);
     $smarty->assign('page_set',     $user_list['page_set']);
     $smarty->assign('page',         $user_list['page']);
     $smarty->assign('act',          $_REQUEST['act']);
     $smarty->assign('tag',          $_REQUEST['tag'] ? $_REQUEST['tag'] :0);

     $smarty->assign('num', sprintf('（共%d条）', $user_list['record_count']));

     //判断客服的权限，是否显示团队搜索
     if($_SESSION['action_list'] == 'all')
     {
          $smarty->assign('admin_show_team',1);
          $smarty->assign('role_list', get_role());
     }
     else
     {
          $sql = 'SELECT manager,role_id FROM '.$GLOBALS['ecs']->table('admin_user').
               " WHERE user_id={$_SESSION['admin_id']}";
          $user = $GLOBALS['db']->getRow($sql);
          if($user['manager'] === '0')
          {
               $smarty->assign('role_id',$user['role_id']);
               $smarty->assign('show_team',1);
          }
     }

     // assign_query_info();

     $res['main'] = $smarty->fetch('users_list_group.htm');

     $res['left'] = sub_menu_list($file);
     if ($res['left'] === false) unset($res['left']);

     die($json->encode($res));
}

/* 顾客自定义分类 */
elseif ($_REQUEST['act'] == 'user_cat_list')
{
     /* 检查权限 */
     admin_priv('users_list');
     $res = array ('switch_tag' => true, 'id' => $_REQUEST['tag'] ? $_REQUEST['tag'] : 0);

     if($_SESSION['admin_id'] == 78){
	     $_REQUEST['type'] = '13';
     }
     elseif (!$_REQUEST['type']){
	     $_REQUEST['type'] = '2, 3, 4, 5, 11';
     }

     $smarty->assign('user_ranks',   $ranks);
     $smarty->assign('ur_here',      $_LANG['01_users_list']);
     $smarty->assign('country_list',  get_regions());
     $smarty->assign('province_list', get_regions(1,1));

     $user_list = user_list();

     /* 获取顾客来源、购买力、客服 */
     $smarty->assign('from_where', get_from_where());
     $smarty->assign('type_list',  get_customer_type());
     $smarty->assign('admin_list', get_admin('session'));
     $smarty->assign('eff_list',   getEffectTypes());

     $smarty->assign('is_intention', $_REQUEST['act']);  // 意向顾客查询字段，为分页提供区分支持
     $smarty->assign('user_list',    $user_list['user_list']);

     $smarty->assign('cat_list', user_cat_list(1));

     // 分页设置
     $smarty->assign('page_link',    $user_list['condition']);
     $smarty->assign('filter',       $user_list['filter']);
     $smarty->assign('record_count', $user_list['record_count']);
     $smarty->assign('page_count',   $user_list['page_count']);
     $smarty->assign('page_size',    $user_list['page_size']);
     $smarty->assign('page_start',   $user_list['start']);
     $smarty->assign('page_end',     $user_list['end']);
     $smarty->assign('full_page',    1);
     $smarty->assign('page_set',     $user_list['page_set']);
     $smarty->assign('page',         $user_list['page']);
     $smarty->assign('act',          $_REQUEST['act']);
     $smarty->assign('tag',          $_REQUEST['tag'] ? $_REQUEST['tag'] :0);

     $smarty->assign('num', sprintf('（共%d条）', $user_list['record_count']));
     $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

     //判断客服的权限，是否显示团队搜索
     if($_SESSION['action_list'] == 'all')
     {
          $smarty->assign('admin_show_team',1);
          $smarty->assign('role_list', get_role());
     }
     else
     {
          $sql = 'SELECT manager,role_id FROM '.$GLOBALS['ecs']->table('admin_user').
               " WHERE user_id={$_SESSION['admin_id']}";
          $user = $GLOBALS['db']->getRow($sql);
          if($user['manager'] === '0')
          {
               $smarty->assign('role_id',$user['role_id']);
               $smarty->assign('show_team',1);
          }
     }

     // assign_query_info();

     $res['main'] = $smarty->fetch('user_cat_list.htm');

     $res['left'] = sub_menu_list($file);
     if ($res['left'] === false) unset($res['left']);

     die($json->encode($res));
}

/* 更改顾客分类 */
elseif ($_REQUEST['act'] == 'change_cat')
{
     $cat_list = user_cat_list(1);
     if (empty($cat_list)){
          $res['req_msg'] = true;
          $res['timeout'] = 20000;
          $res['message'] = '请先添加分类！';

          die($json->encode($res));
     }

     $smarty->assign('cat_list', $cat_list);
     $smarty->assign('user_id', $_REQUEST['user_id']);

     $res['message'] = $smarty->fetch('change_cat.htm');
     $res['req_msg'] = true;
     $res['timeout'] = 20000;

     die($json->encode($res));
}

/* 修改数据 */
elseif ($_REQUEST['act'] == 'update_cat')
{
     $cat = addslashes_deep($_REQUEST);
     $sql_select = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('users').
          " WHERE user_cat='{$_SESSION['admin_id']}-{$cat['cat']}' AND user_id={$cat['user_id']}";
     if ($GLOBALS['db']->getOne($sql_select)){
          $res['req_msg'] = true;
          $res['timeout'] = 2000;
          $res['message'] = '该顾客无需修改分类！';
          die($json->encode($res));
     }

     $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users').
          " SET user_cat='{$_SESSION['admin_id']}-{$cat['cat']}' WHERE user_id={$cat['user_id']}";
     $GLOBALS['db']->query($sql_update);
     if ($GLOBALS['db']->affected_rows()){
          $res['req_msg'] = true;
          $res['timeout'] = 2000;
          $res['message'] = '修改成功！';

          die($json->encode($res));
     }
}

/* 添加顾客分类 */
elseif ($_REQUEST['act'] == 'add_user_cat')
{
     if (!admin_priv('add_user_cat', '', false)){
          $res['req_msg'] = true;
          $res['timeout'] = 2000;
          $res['message'] = '当前帐号无法创建新分类！';
          die($json->encode($res));
     }

     $smarty->assign('cat_list', user_cat_list());

     $res['main'] = $smarty->fetch('add_user_cat.htm');
     die($json->encode($res));
}

/* 保存分类到数据库 */
elseif ($_REQUEST['act'] == 'insert_user_cat')
{
     $res['req_msg'] = true;
     $res['timeout'] = 2000;
     $res['code'] = 0;
     if (!admin_priv('add_user_cat', '', false)){
          $res['message'] = '当前帐号无法创建新分类！';
          die($json->encode($res));
     }

     $cat = addslashes_deep($_REQUEST);
     $sql_select = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('user_cat').
          " WHERE cat_name='{$cat['cat_name']}' AND admin_id={$_SESSION['admin_id']}";
     $is_exist = $GLOBALS['db']->getOne($sql_select);
     if ($is_exist){
          $res['message'] = '您提交的顾客分类名称已经存在！';
          die($json->encode($res));
     }

     $sql_select = 'SELECT cat_tag FROM '.$GLOBALS['ecs']->table('user_cat').
          " WHERE admin_id='{$_SESSION['admin_id']}' AND available=1 ORDER BY cat_tag DESC";
     $cat_tag = $GLOBALS['db']->getOne($sql_select) +1;

     $sql_insert = 'INSERT INTO '.$GLOBALS['ecs']->table('user_cat').'(cat_name,cat_desc,cat_tag,admin_id)VALUES('.
          "'{$cat['cat_name']}','{$cat['cat_desc']}',$cat_tag,{$_SESSION['admin_id']})";
     $GLOBALS['db']->query($sql_insert);
     if ($cat['cat_id'] = $GLOBALS['db']->insert_id()){
          $cat['cat_tag'] = $cat_tag;
          $cat['req_msg'] = true;
          $cat['timeout'] = 2000;
          $cat['message'] = '添加成功';
          $cat['code'] = 1;
          die($json->encode($cat));
     }
}

/* 第一回访 */
elseif ($_REQUEST['act'] == 'first_trace')
{
     if(!admin_priv('first_trace', '', false)){
          $res['req_msg'] = true;
          $res['timeout'] = 2000;
          $res['message'] = '当前帐号无访问权限！';

          die($json->encode($res));
     }

     $res = array ('switch_tag' => true, 'id' => 2);

     $days_three = time() -3*24*3600; // 三天前
     //$days_five  = $three_days -2*24*3600; // 五天前

     $sql = 'SELECT i.user_id,i.consignee,i.mobile,i.tel,i.receive_time,o.goods_name, '.
          ' i.receive_time+g.take_days*o.goods_number take_time,i.order_id,i.add_time,a.user_name add_admin, '.
          "IF(u.service_time>$days_three,u.service_time,'-') recently FROM ".$GLOBALS['ecs']->table('order_info').
          ' i,'.$GLOBALS['ecs']->table('admin_user').' a,'.$GLOBALS['ecs']->table('order_goods').' o,'.
          $GLOBALS['ecs']->table('goods').' g,'. $GLOBALS['ecs']->table('users').
          ' u WHERE i.add_admin_id=a.user_id AND i.user_id=u.user_id AND o.goods_id=g.goods_id AND i.order_id=o.order_id';

     if (! admin_priv('all', '', false))
     {
          $sql .= " AND u.admin_id={$_SESSION['admin_id']} ";
     }

     // 最近三天确认收货的顾客
     $res_three = $GLOBALS['db']->getAll($sql." AND i.receive_time>$days_three GROUP BY i.order_id ORDER BY u.service_time ASC");

     foreach ($res_three as &$val)
     {
          $val['take_timetable'] = $val['goods_name'].date('Y-m-d',$val['take_time']);
          $val['receive_time']   = date('Y-m-d',$val['receive_time']);
          $val['add_time']       = date('Y-m-d', $val['add_time']);

          if ($val['recently'] != '-')
          {
               $val['recently'] = date('Y-m-d', $val['recently']);
          }
     }

     // 最近五天确认收货的顾客
     //$res_five = $GLOBALS['db']->getAll($sql." AND i.receive_time>$days_five");

     /*foreach ($res_three as &$val)
     {
         $sql = 'SELECT o.goods_name, FROM_UNIXTIME(i.receive_time+g.take_days,"%Y-%m-%d") take_date FROM '.
             $GLOBALS['ecs']->table('goods').' g,'.$GLOBALS['ecs']->table('order_goods').' o,'.
             $GLOBALS['ecs']->table('order_info').' i '.
             " WHERE o.goods_id=g.goods_id AND i.order_id=o.order_id AND o.order_id={$val['order_id']}";
         $val['time_table'] = $GLOBALS['db']->getAll($sql);
     }
      */

     $smarty->assign('user_list', $res_three);
     $smarty->assign('full_page', 1);

     assign_query_info();
     $res['main'] = $smarty->fetch('first_trace.htm');

     die($json->encode($res));
}

/* 预约服务 */
elseif ($_REQUEST['act'] == 'check')
{
     $res = array ('switch_tag' => true, 'id' => 3);

     $smarty->assign('user_ranks',   $ranks);
     $smarty->assign('ur_here',      $_LANG['02_serve_check']);

     $sql = 'SELECT u.user_id, u.user_name, u.mobile_phone, u.home_phone, u.age_group, u.sex, '.
          's.handler,s.service_time,u.admin_name,s.logbook FROM '.$GLOBALS['ecs']->table('users').
          ' u, '.$GLOBALS['ecs']->table('service').' s WHERE u.user_id=s.user_id AND s.handler<>0 ';
     $sql .= " AND u.admin_id={$_SESSION['admin_id']} ";

     $now_time = time();
     $sql .= " AND s.service_time=u.service_time AND s.handler>$now_time ORDER BY s.handler DESC";
     $user_list = $GLOBALS['db']->getAll($sql);
     foreach ($user_list as &$val)
     {
          $val['handler']      = date('Y-m-d H:i', $val['handler']);
          $val['service_time'] = date('Y-m-d', $val['service_time']);
     }

     $smarty->assign('user_list',    $user_list);
     $smarty->assign('action',       $_SESSION['action_list']);
     $smarty->assign('full_page',    1);
     $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

     assign_query_info();
     $res['main'] = $smarty->fetch('check_list.htm');
     die($json->encode($res));
}

/* 重复购买的顾客 */
elseif ($_REQUEST['act'] == 'repeat')
{
     /* 检查权限 */
     admin_priv('users_list');
     $res = array ('switch_tag' => true, 'id' => 5);

     $smarty->assign('user_ranks',   $ranks);
     $smarty->assign('country_list',  get_regions());
     $smarty->assign('province_list', get_regions(1,1));

     if (!isset($_REQUEST['number_purchased'])){
          $_REQUEST['number_purchased'] = 2;
     }

     $user_list = user_list();

     /* 获取顾客来源、购买力、客服 */
     $smarty->assign('from_where', get_from_where());
     $smarty->assign('type_list',  get_customer_type());
     $smarty->assign('admin_list', get_admin('session'));
     $smarty->assign('eff_list',   getEffectTypes());

     $smarty->assign('is_intention', $_REQUEST['act']);  // 意向顾客查询字段，为分页提供区分支持
     $smarty->assign('user_list',    $user_list['user_list']);

     // 分页设置
     $smarty->assign('page_link',    $user_list['condition']);
     $smarty->assign('filter',       $user_list['filter']);
     $smarty->assign('record_count', $user_list['record_count']);
     $smarty->assign('page_count',   $user_list['page_count']);
     $smarty->assign('page_size',    $user_list['page_size']);
     $smarty->assign('page_start',   $user_list['start']);
     $smarty->assign('page_end',     $user_list['end']);
     $smarty->assign('full_page',    1);
     $smarty->assign('page_set',     $user_list['page_set']);
     $smarty->assign('page',         $user_list['page']);
     $smarty->assign('act',          $_REQUEST['act']);
     $smarty->assign('tag',          $_REQUEST['tag'] ? $_REQUEST['tag'] :0);

     $smarty->assign('num', sprintf('（共%d条）', $user_list['record_count']));
     $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

     //判断客服的权限，是否显示团队搜索
     if($_SESSION['action_list'] == 'all')
     {
          $smarty->assign('admin_show_team',1);
          $smarty->assign('role_list', get_role());
     }
     else
     {
          $sql = 'SELECT manager,role_id FROM '.$GLOBALS['ecs']->table('admin_user').
               " WHERE user_id={$_SESSION['admin_id']}";
          $user = $GLOBALS['db']->getRow($sql);
          if($user['manager'] === '0')
          {
               $smarty->assign('role_id',$user['role_id']);
               $smarty->assign('show_team',1);
          }
     }

     // assign_query_info();

     $res['main'] = $smarty->fetch('repeat_list.htm');

     $res['left'] = sub_menu_list($file);
     if ($res['left'] === false) unset($res['left']);

     die($json->encode($res));
}

/* 顾客详细信息 */
elseif ($_REQUEST['act'] == 'user_detail')
{
     $user_id = intval($_REQUEST['id']);
     $res['id'] = $user_id;
     $res['response_action'] = 'detail';

     $user_info = get_user_info($user_id);  // 获取顾客基本资料

     $order_list = access_purchase_records($user_id); // 获取顾客购买记录（订单记录）

     $service_list = get_user_services($user_id); // 获取顾客受服务记录

     $return_list = get_return_list($user_id); // 获取顾客的退货记录

     $case = get_before_case();

     //获取支付类型
     $surplus_payment = array();
     $sql = "SELECT pay_id, pay_name FROM ".$GLOBALS['ecs']->table('payment').
         " WHERE enabled = 1 AND pay_code != 'cod' ORDER BY pay_id";
     $result = $GLOBALS['db']->getAll($sql);
     $surplus_payment = $result;

     /* 会员充值和提现申请记录 */
     include_once(ROOT_PATH . 'includes/lib_clips.php');

     $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page'])<=0) ? 1 : intval($_REQUEST['page']);
     if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
     {
         $filter['page_size'] = intval($_REQUEST['page_size']);
     }
     else
     {
         $filter['page_size'] = 20; 
     }

     /* 获取记录条数 */
     $sql = "SELECT COUNT(*) FROM " .$ecs->table('user_account').
         " WHERE user_id = '$user_id'" .
         " AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN));
     $record_count = $db->getOne($sql);

     $filter['record_count'] = $record_count;
     $filter['page_count'] = $filter['record_count']>0 ? ceil($filter['record_count']/$filter['page_size']) : 1;

     // 设置分页
     $page_set = array (1,2,3,4,5,6,7);
     if ($filter['page'] > 4)
     {
         foreach ($page_set as &$val)
         {
             $val += $filter['page'] -4;
         }
     }

     if (end($page_set) > $filter['page_count'])
     {
         $page_set = array ();
         for ($i = 7; $i >= 0; $i--)
         {
             if ($filter['page_count'] - $i > 0)
             {
                 $page_set[] = $filter['page_count'] - $i;
             }
         }
     }

     $filter = array (
         'filter'        => $filter,
         'page_count'    => $filter['page_count'],
         'record_count'  => $filter['record_count'],
         'page_size'     => $filter['page_size'],
         'page'          => $filter['page'],
         'page_set'      => $page_set,
         'condition'     => $condition,
         'start'         => ($filter['page'] - 1)*$filter['page_size'] +1,
         'end'           => $filter['page']*$filter['page_size'],
         'act'           => 'user_detail',
     );

     //获取剩余余额
     $surplus_amount = get_user_surplus($user_id);
     $sql = "SELECT SUM(user_money) FROM " .$GLOBALS['ecs']->table('account_log').
         " WHERE user_id = '$user_id'";

     if (empty($surplus_amount))
     {
         $surplus_amount = 0;
     }

     //获取会员帐号明细
     $account_log = array();
     $account_detail = array();

     $sql_select = "SELECT * FROM " . $ecs->table('account_log') .
         " WHERE user_id = '$user_id'" .
         ' ORDER BY log_id DESC LIMIT '.($filter['start']-1)*$filter['page_size'].",{$filter['page_size']}";
     $account_detail = $GLOBALS['db']->getAll($sql_select);

     //$account_log = get_account_log($user_id, $filter['page_size'], $filter['start']);
     $sql_select = "SELECT * FROM ".$GLOBALS['ecs']->table('user_account')." WHERE user_id=$user_id LIMIT ".
         ($filter['start']-1) * $filter['page_size'].",{$filter['page_size']}";
     $account_log = $GLOBALS['db']->getAll($sql_select);

     lange_account($account_log);
     lange_account($account_detail);

     //模板赋值
     $smarty->assign('count_log',$record_count);
     $smarty->assign('account_detail',$account_detail);
     $smarty->assign('surplus_amount', price_format($surplus_amount, false));
     $smarty->assign('lang',$_LANG);
     $smarty->assign('account_log',    $account_log);
     $smarty->assign('action',         'account_log');
     $smarty->assign('filter',         $filter);

     // 订单类型
     $sql_select = 'SELECT type_id, type_name FROM '.$GLOBALS['ecs']->table('order_type').
          ' WHERE available=1 ORDER BY sort DESC';
     $order_type = $GLOBALS['db']->getAll($sql_select);
     $smarty->assign('order_type', $order_type);

     $healthy_file = get_healthy($user_id);
     $isexistHF    = $healthy_file['baseInfo']['total'];
     $smarty->assign('case_list',$case['case_list']);  //idsease
     $smarty->assign('before_case',$case['before_case']);  //idsease
     $smarty->assign('isexistHF',$isexistHF);

     $smarty->assign('disease',        get_disease());        // diseases
     $smarty->assign('characters',     get_characters());     // characters
     $smarty->assign('payment',        payment_list());       // payment
     $smarty->assign('shipping',       shipping_list(3));      // shipping
     $smarty->assign('service_class',  get_service_class());  // servcie class
     $smarty->assign('service_manner', get_service_manner()); // sercie manner
     $smarty->assign('integral_log',   get_integral_log($user_id));        //integral log

     $smarty->assign('admin_list',    get_admin_tmp_list());
     $smarty->assign('platform_list', get_role_list(1));

     $smarty->assign('province_list', get_regions(1, 1));
     $smarty->assign('city_list',     get_regions(2, $user_info['province_id']));
     $smarty->assign('district_list', get_regions(3, $user_info['city_id']));

     $smarty->assign('user',        $user_info);            // 顾客信息
     $smarty->assign('order_list',  $order_list);           // 购买记录
     $smarty->assign('service',     $service_list);         // 服务记录
     $smarty->assign('return',      $return_list);          // 退货记录
     $smarty->assign('surplus_payment',    $surplus_payment);             //充值提现支付方式
     //$smarty->assign('service',     get_service($user_id)); // service records

     $smarty->assign('service_time', date('Y-m-d H:i'));
     $res['info'] = $smarty->fetch('users_detail.htm');

     die($json->encode($res));
}

/* 添加顾客 */
elseif($_REQUEST['act'] == 'add_users')
{
     /* 检查权限 */
     $user = array(
          'rank_points' => $_CFG['register_points'],
          'pay_points'  => $_CFG['register_points'],
          'sex'         => 0,
          'credit_line' => 0
     );

     // 取出注册扩展字段
     $sql = 'SELECT * FROM '.$ecs->table('reg_fields').
          ' WHERE type<2 AND display=1 AND id<>6 ORDER BY dis_order, id';
     $extend_info_list = $db->getAll($sql);

     // 给模板赋值
     $smarty->assign('extend_info_list', $extend_info_list);
     $smarty->assign('ur_here',          $_LANG['04_users_add']);
     $smarty->assign('action_link',      array('text' => $_LANG['01_users_list'], 'href'=>'users.php?act=list'));
     $smarty->assign('form_action',      'insert');
     $smarty->assign('start_index',      0);
     $smarty->assign('user',             $user);
     $smarty->assign('special_ranks',    get_rank_list(true));

     $smarty->assign('country_list', get_regions());
     $smarty->assign('province_list', get_regions(1,1));

     //获取顾客类型
     $smarty->assign('customer_type', get_customer_type());

     //获取顾客来源
     $smarty->assign('from_where', get_from_where());

     //获取顾客经济来源
     $smarty->assign('income', get_income());

     //获取疾病列表
     $smarty->assign('disease', get_disease());

     // 获取性格列表
     $sql = 'SELECT character_id, characters FROM '.$ecs->table('character').' ORDER BY sort ASC';
     $smarty->assign('character', $db->getAll($sql));

     // 获取顾客分类
     $sql = 'SELECT eff_id, eff_name FROM '.$ecs->table('effects').
          ' WHERE available=1 ORDER BY sort ASC';
     $smarty->assign('effects', $db->getAll($sql));

     // 获取销售平台列表
     $smarty->assign('role_list', get_role_list(1));

     $smarty->assign('row_number',       2);
     assign_query_info();
     $smarty->assign('ur_here', $_LANG['02_users_add']);
     $smarty->display('user_info.htm');

     $res['main']=$smarty->fetch('add_custom.htm');

     die($json->encode($res));
}

/* 添加客户 */
elseif($_REQUEST['act'] == 'add_custom')
{
     $area_code = trim($_POST['area_code']);
     $hphone    = trim($_POST['hphone']);
     $mphone    = trim($_POST['mphone']);

     if(!empty($area_code))
     {
          $hphone = "$area_code-$hphone";
     }

     //查询条件为家庭号码
     if($hphone)
     {
          $where = " home_phone='$hphone'";
     }

     //查询条件为手机号码
     if($where && $mphone)
     {
          $where .= " OR mobile_phone='$mphone'";
     }
     elseif($mphone)
     {
          $where = " mobile_phone='$mphone'";
     }

     $sql = 'SELECT admin_name FROM '.$ecs->table('users')." WHERE $where";
     $admin_name = $db->getOne($sql);

     if(!empty($admin_name))
     {
          die($admin_name);
     }
     else
     {
          die('1');
     }

     $sql = 'SELECT admin_name FROM '.$ecs->table('users')." WHERE $where";
     $admin_name = $db->getOne($sql);

     if(!empty($admin_name))
     {
          die($admin_name);
     }
     else
     {
          die(0);
     }
}

/* 修改顾客信息 */
elseif ($_REQUEST['act'] == 'edit')
{
     $res['response_action'] = 'edit_user';
     $id = intval($_REQUEST['id']); // 顾客ID
     $request = addslashes_deep($_REQUEST);

     if (!in_array($request['info'], array ('district','address')))
     {
          $sql_select = "SELECT add_time,{$request['info']} FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id=$id";
          $user_info = $GLOBALS['db']->getRow($sql_select);
          if ($add_time < time() -3*24*3600 && !empty($user_info[$request['info']]) && in_array($_REQUEST['info'], array ('home_phone', 'mobile_phone','qq','aliww')))
          {
               $res['code']    = 2;
               $res['req_msg'] = true;
               $res['timeout'] = 2000;
               $res['message'] = '该顾客资料已不允许修改';

               die($json->encode($res));
          }
     }

     // 修改地址信息
     if ($_REQUEST['info'] == 'district')
     {
          $smarty->assign('province_list', get_regions(1,1));
     }

     // 修改详细地址
     if ($_REQUEST['info'] == 'address')
     {
          $sql_select = 'SELECT address FROM '.$GLOBALS['ecs']->table('user_address').
               " WHERE user_id=$id";
          $val = $GLOBALS['db']->getOne($sql_select);
     }

     if ($_REQUEST['type'] == 'text' && $_REQUEST['info'] != 'address')
     {
          $sql_select = "SELECT {$_REQUEST['info']} FROM ".$GLOBALS['ecs']->table('users').
               " WHERE user_id=$id";
          $val = $GLOBALS['db']->getOne($sql_select);

          if (!isset($val))
          {
               $val = '';
          }
     }

     if ($_REQUEST['type'] == 'select' && $_REQUEST['info'] != 'district')
     {
          $sql_select = "SELECT {$_REQUEST['info']} FROM ".$GLOBALS['ecs']->table('users').
               " WHERE user_id=$id";
          $val = $GLOBALS['db']->getOne($sql_select);

          switch ($_REQUEST['info'])
          {
               case 'role_id' :
                    $list = list_role_common(); 
                    break;
               case 'eff_id' :
                    $list = list_effects_common();
                    break;
               case 'customer_type' :
                    $list = list_customer_type();
                    break;
               case 'from_where' :
                    $list = list_from_where();
          }

          $smarty->assign('list', $list);
     }

     $res['info'] = $_REQUEST['info'];
     $res['id']   = $id;
     $res['act']  = 'edit';
     $res['type'] = $_REQUEST['type'];

     $smarty->assign('type',  $_REQUEST['type']);
     $smarty->assign('field', $_REQUEST['info']);
     $smarty->assign('value', $val);
     $res['main'] = $smarty->fetch('detail.htm');

     die($json->encode($res));
}

/* 保存顾客信息 */
elseif ($_REQUEST['act'] == 'save')
{
     $user_id = intval($_REQUEST['id']); // 订单ID

     // 转义所有数据 包括数组的key
     $request = addslashes_deep($_REQUEST);
     $request['type'] = strtolower($request['type']);

     $res['id']      = $user_id;
     $res['info']    = $request['info'];
     $res['type']    = $request['type'];
     $res['req_msg'] = true;
     $res['message'] = '顾客信息修改成功！';
     $res['timeout'] = 2000;

     if ($res['info'] == 'address' && empty($request['value'])){
          $res['message'] = '顾客地址不能为空！';
          die($json->encode($res));
     }

     // 修改疾病与性格
     if (in_array($_REQUEST['info'], array('disease', 'characters')))
     {
          $sql_select = "SELECT {$request['info']} FROM ".$GLOBALS['ecs']->table('users').
               " WHERE user_id=$user_id";
          $val_exist = $GLOBALS['db']->getOne($sql_select);
          $exist = strpos(':'.$val_exist.':', ':'.$request['value'].':');
          if ($exist !== false)
          {
               $val_exist = str_replace(":{$request['value']}", '', $val_exist);
          }
          else 
          {
               $val_exist .= ':'.$request['value'];
          }

          $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users').
               " SET {$request['info']}='$val_exist' WHERE user_id=$user_id";
          $GLOBALS['db']->query($sql_update);
     }

     // 修改信息
     if (in_array($request['type'], array('text','select','radio')) && !in_array($_REQUEST['info'], array('district','address')))
     {
          $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users').
               " SET {$request['info']}='{$request['value']}' WHERE user_id=$user_id";
          if ($GLOBALS['db']->query($sql_update))
          {
               $sql_select = "SELECT {$request['info']} FROM ".
                    $GLOBALS['ecs']->table('users')." WHERE user_id=$user_id";
               $res['main'] = $GLOBALS['db']->getOne($sql_select);

               switch ($request['info'])
               {
               case 'eff_id' : $sql_select = 'SELECT eff_name FROM '.
                    $GLOBALS['ecs']->table('effects')." WHERE eff_id={$res['main']}";
                    $res['main'] = $GLOBALS['db']->getOne($sql_select);
                    break;
               case 'role_id' : $sql_select = 'SELECT role_name FROM '.
                    $GLOBALS['ecs']->table('role')." WHERE role_id={$res['main']}";
                    $res['main'] = $GLOBALS['db']->getOne($sql_select);
                    break;
               case 'sex' : $res['main'] = $res['main'] ? $res['main'] == 1 ? '男' : '女' : '不详';
                    break;
               case 'from_where' :
                    $sql_select = 'SELECT `from` FROM '.
                         $GLOBALS['ecs']->table('from_where')." WHERE from_id={$res['main']}";
                    $res['main'] = $GLOBALS['db']->getOne($sql_select);
                    break;
               }
          }
          else 
          {
               $res['message'] = '修改失败！';
          }
     }

     // 保存地址信息
     if ($_REQUEST['info'] == 'district')
     {
          $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('user_address').
               " SET province={$request['province']},city={$request['city']},".
               " district={$request['district']} WHERE user_id=$user_id";
          if ($GLOBALS['db']->query($sql_update))
          {
               $sql_select = 'SELECT p.region_name province,c.region_name city,d.region_name district FROM '.
                    $GLOBALS['ecs']->table('user_address').' u LEFT JOIN '.$GLOBALS['ecs']->table('region').
                    ' p ON u.province=p.region_id LEFT JOIN '.$GLOBALS['ecs']->table('region').
                    ' c ON u.city=c.region_id LEFT JOIN '.$GLOBALS['ecs']->table('region').
                    ' d ON d.region_id=u.district'." WHERE u.user_id=$user_id";
               $region = $GLOBALS['db']->getAll($sql_select);

               $res['main'] = implode('', $region[0]);
          }
     }

     // 保存详细地址信息
     if ($_REQUEST['info'] == 'address')
     {
          $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('user_address').
               " SET address='{$request['value']}' WHERE user_id=$user_id";
          $GLOBALS['db']->query($sql_update);

          $sql_select = 'SELECT address FROM '.$GLOBALS['ecs']->table('user_address').
               " WHERE user_id=$user_id";
          $res['main'] = $GLOBALS['db']->getOne($sql_select);
     }

     // 记录客服操作
     record_operate($sql_update, 'ordersyn_info');

     die($json->encode($res));
}

// 添加包邮卡
elseif ($_REQUEST['act'] == 'add_freecard')
{
     $user_id = intval($_GET['user_id']);
     $user_name = mysql_real_escape_string(trim($_GET['user_name']));
     $admin_id = $_SESSION['admin_id'];

     /* START 包邮卡编号生成 */
     //获取$admin_id所属平台
     $sql = 'SELECT r.role_describe FROM ' .$GLOBALS['ecs']->table('admin_user').' a, '
          .$GLOBALS['ecs']->table('role'). " r WHERE a.role_id=r.role_id AND a.user_id=$admin_id";
     $admin = $GLOBALS['db']->getOne($sql);
     $mon = date(m);
     $str_rand = "012356789";
     $rand = "";
     $num = 7 - strlen($user_id);
     for($i=0;$i<$num;$i++){
          $rand .= substr($str_rand,rand(0,8),1);
     }
     $freecard_num = str_replace(' ','',$admin.$mon).' '.chunk_split($rand.'0'.$user_id,"4"," ");
     //    $freecard_str = str_replace(' ','',$freecard_num);
     //    $freecard_num = chunk_split($freecard_str,"4"," ");
     /* END 包邮卡编号生成 */

     $smarty->assign('freecard_num',$freecard_num);
     $smarty->assign('user_id',$user_id);
     $smarty->assign('user_name',$user_name);
     //     $smarty->assign('free_type',$free_type);
     $smarty->assign('free_platform',$free_platform);

     //编辑包邮卡信息初始化页面
     if($_REQUEST['handle'] == 'edit')
     {
          $smarty->assign('show',1);
          $sql = 'SELECT free_limit,effective_date,free_type,free_platform,free_remarks FROM ' .$GLOBALS['ecs']->table('free_postal_card'). ' WHERE user_id = '.$user_id;
          $res = $GLOBALS['db']->query($sql);

          $free = mysql_fetch_assoc($res);
          $smarty->assign('free',$free);
          die($smarty->fetch('free_postal_card.htm')); 
     }

     //添加包邮卡信息初始化页面
     else{
          $smarty->assign('show',0);
          die($smarty->fetch('free_postal_card.htm'));         
     }        
}

//把包邮卡信息插入数据库中
elseif ($_REQUEST['act'] == 'insert_free')
{
     $relat_info = array
          (
               'user_id'   => $_POST['user_id'],
               'freecard_num'   => str_replace(' ','',$_POST['freecard_num']),
               'free_limit'    => $_POST['free_limit'],
               'effective_date'     => $_POST['effective_date'],
               'free_type'   => $_POST['free_type'],
               'free_platform' => $_POST['free_platform'],
               'free_remarks'   => $_POST['free_remarks'],
          ); 
     $fields = array_keys($relat_info);
     $values = array_values($relat_info);
     //更新包邮卡信息到数据库
     if($_REQUEST['update'] == 'update'){
          $count = count($fields);
          for($i=0;$i<$count;$i++){
               $insert .= $fields[$i]."='".$values[$i]."',";
          }
          $insert=rtrim($insert,',');
          $sql = 'UPDATE '.$GLOBALS['ecs']->table('free_postal_card').' SET '.$insert.' WHERE user_id= '.$_POST['user_id'];
          $GLOBALS['db']->query($sql);
          die("更新成功！！");
     }
     //插入包邮卡信息到数据库
     else{  
          $sql = 'SELECT freecard_id FROM '.$GLOBALS['ecs']->table('free_postal_card'). ' WHERE user_id = '.$_POST['user_id'];
          $res = $GLOBALS['db']->query($sql);
          if(mysql_fetch_assoc($res)){ die('顾客包邮卡已经添加了！！'); }
          else{         
               $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('free_postal_card').'('.implode(',',$fields).')VALUES(\''.implode('\',\'',$values).'\')';
               $GLOBALS['db']->query($sql); 
               die("顾客包邮卡添加成功！！"); 
          }
     }   
}

/*------------------------------------------------------ */
//-- ajax返回用户列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{

     $user_list = user_list();

     $smarty->assign('user_list',    $user_list['user_list']);
     $smarty->assign('filter',       $user_list['filter']);
     $smarty->assign('action',       $_SESSION['action_list']);
     $smarty->assign('record_count', $user_list['record_count']);
     $smarty->assign('page_count',   $user_list['page_count']);

     $sort_flag  = sort_flag($user_list['filter']);
     $smarty->assign($sort_flag['tag'], $sort_flag['img']);

     /* 会员部处理重新分配顾客 */
     if ($_SESSION['role_id'] == 9)
     {
          $smarty->assign('effects', getEffectTypes());
     }

     //显示一个月内转移的顾客
     if($user_list['filter']['transfer_time']){
          $smarty->assign('transfer',1);
     }

     make_json_result($smarty->fetch('users_list.htm'), '', array('filter' => $user_list['filter'], 'page_count' => $user_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
     /* 检查权限 */
     admin_priv('users_add');

     $user = array(
          'rank_points' => $_CFG['register_points'],
          'pay_points'  => $_CFG['register_points'],
          'sex'         => 0,
          'credit_line' => 0
     );

     /* 取出注册扩展字段 */
     $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
     $extend_info_list = $db->getAll($sql);
     $smarty->assign('extend_info_list', $extend_info_list);
     $smarty->assign('ur_here',          $_LANG['04_users_add']);
     $smarty->assign('action_link',      array('text' => $_LANG['01_users_list'], 'href'=>'users.php?act=list'));
     $smarty->assign('form_action',      'insert');
     $smarty->assign('start_index',      0);
     $smarty->assign('user',             $user);
     $smarty->assign('special_ranks',    get_rank_list(true));

     $smarty->assign('country_list', get_regions());
     $smarty->assign('province_list', get_regions(1,1));

     //获取顾客类型
     $smarty->assign('customer_type', get_customer_type());

     //获取顾客来源
     $smarty->assign('from_where', get_from_where());

     //获取顾客经济来源
     $smarty->assign('income', get_income());

     //获取疾病列表
     $smarty->assign('disease', get_disease());

     // 获取性格列表
     $sql = 'SELECT character_id, characters FROM '.$ecs->table('character').' ORDER BY sort ASC';
     $smarty->assign('character', $db->getAll($sql));

     /* 获取顾客分类 */
     $sql = 'SELECT eff_id, eff_name FROM '.$ecs->table('effects').' WHERE available=1 ORDER BY sort ASC';
     $smarty->assign('effects', $db->getAll($sql));

     /* 获取销售平台列表 */
     $smarty->assign('role_list', get_role_list(1));

     $smarty->assign('row_number',       2);
     assign_query_info();
     $smarty->assign('ur_here', $_LANG['02_users_add']);

     $smarty->display('user_info.htm');
}

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert')
{
     /* 检查权限 */
     //admin_priv('users_add');
     $res['response_action'] = $_REQUEST['act'];

     $res['code']    = 0;
     $res['req_msg'] = 'true';
     $res['timeout'] = 2000;

     $area_code    = mysql_real_escape_string(trim($_POST['area_code']));
     $home_phone   = mysql_real_escape_string(trim($_POST['home_phone']));
     $mobile_phone = mysql_real_escape_string(trim($_POST['mobile_phone']));

     if (!empty($area_code))
          $home_phone = $area_code.'-'.$home_phone;

     if ($_POST['home_phone'] && $_POST['mobile_phone'])
          $repeat_where = " home_phone='$home_phone' OR mobile_phone=$mobile_phone" ;
     elseif($_POST['home_phone'])
          $repeat_where = " home_phone='$home_phone' ";
     elseif($_POST['mobile_phone'])
          $repeat_where = " mobile_phone='$mobile_phone'";

     $sql = 'SELECT COUNT(*) FROM '.$ecs->table('users')." WHERE $repeat_where";
     if ($repeat_where && $db->getOne($sql))
     {
          $res['message'] = '该顾客已存在！';
          die($json->encode($res));
     }

     // 顾客基本信息
     $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
     $sex = in_array($sex, array(0, 1, 2)) ? $sex : 0;
     $userinfo = array (
          'user_name'     => trim($_POST['username']), // 顾客姓名
          'eff_id'        => intval($_POST['eff_id']), // 功效分类
          'sex'           => $sex,                     // 性别
          'birthday'      => trim($_POST['birthday']), // 出生日期
          'age_group'     => mysql_real_escape_string(trim($_POST['age_group'])),      // 年龄段
          'from_where'    => $_POST['from_where'],     // 顾客来源
          'customer_type' => $_POST['customer_type'],  // 顾客类型
          'mobile_phone'  => mysql_real_escape_string(trim($_POST['mobile_phone'])),   // 手机号码
          'id_card'       => mysql_real_escape_string(trim($_POST['id_card'])),        // 身份证号码
          'member_cid'    => mysql_real_escape_string(trim($_POST['member_cid'])),     // 会员卡号
          'qq'            => trim($_POST['qq']),       // 腾讯QQ
          'aliww'         => trim($_POST['aliww']),    // 阿里旺旺
          'habby'         => trim($_POST['habby']),    // 兴趣爱好
          'email'         => trim($_POST['email']),    // 电子邮箱
          'occupat'       => trim($_POST['occupat']),  // 顾客职业
          'income'        => trim($_POST['income']),   // 经济来源

          'disease'       => isset($_POST['disease']) && is_array($_POST['disease']) ? implode(',', $_POST['disease']) : '',                  // 疾病
          'characters'    => isset($_POST['characters']) && is_array($_POST['characters']) ? ','.implode(',', $_POST['characters']).',' : '', // 性格

          'disease_2'   => $_POST['disease_2'],     // 其他疾病
          'remarks'     => $_POST['remarks'],       // 备注
          'parent_id'   => $_POST['recommender'],   // 推荐人
          'admin_id'    => $_SESSION['admin_id'],   // 顾客归属
          'first_admin' => $_SESSION['admin_id'],   // 添加顾客客服
          'add_time'    => time(),                  // 添加时间
          'snail'       => trim($_POST['snail']),   // 平邮地址
          'team'        => intval($_POST['team']),  // 所属团队
          'admin_name'  => $_SESSION['admin_name'], // 客服姓名
          'lang'        => intval($_POST['lang']),   // 常用语言
          'parent_id'   => intval($_POST['parent_id']),
          'role_id'     => intval($_POST['role_id'])
     );

     if ($_POST['calendar'] == 1)
     {
          $userinfo['birthday'] = trim($_POST['birthday']);
     }
     else 
     {
          require(dirname(__FILE__) . '/includes/lunar.php');
          $lunar = new Lunar();
          $userinfo['birthday'] = date('Y-m-d', $lunar->S2L(trim($_POST['birthday'])));
     }

     $userinfo['email'] = empty($userinfo['email']) ? empty($userinfo['qq']) ? '' : $userinfo['qq'].'@qq.com' :$userinfo['email']; 

     $userinfo['home_phone'] = empty($_POST['area_code']) ? $_POST['home_phone'] : $_POST['area_code'].'-'.$_POST['home_phone'];

     // 顾客地址信息
     $addr = array(
          'country'   => $_POST['country'],  // 国家
          'province'  => $_POST['province'], // 省份
          'city'      => $_POST['city'],     // 城市
          'address'   => $_POST['address'],  // 详细地址
          'zipcode'   => $_POST['zipcode']   // 邮编
     );

     if (!empty($_POST['district']))
     {
          $addr['district'] = $_POST['district']; // 区县
     }

     if($_SESSION['role_id'] == 2)
     {
          $userinfo['dm'] = 1;
          $userinfo['mag_no'] = 1;
     }

     $users =& init_users();

     $user_id = $users->add_user($userinfo, $addr);

     if (!$user_id)
     {
          /* 插入会员数据失败 */
          if ($users->error == ERR_INVALID_USERNAME)
          {
               $msg = $_LANG['username_invalid'];
          }
          elseif ($users->error == ERR_NULL_PHONE)
          {
               $msg = $_LANG['null_phone'];
          }
          elseif ($users->error == ERR_INVALID_AREA)
          {
               $msg = $_LANG['invalid_area'];
          }
          elseif ($users->error == ERR_NULL_ADDR)
          {
               $msg = $_LANG['null_addr'];
          }
          else
          {
               //die('Error:'.$users->error_msg());
          }

          $res['message'] = $msg;
          die($json->encode($res));
     }

     if (empty($addr['zipcode'])) unset($addr['zipcode']);

     $addr['user_id'] = $user_id;
     $fields = array_keys($addr);
     $values = array_values($addr);
     $sql = 'INSERT INTO '.$ecs->table('user_address').
          '('.implode(',',$fields).')VALUES(\''.implode('\',\'',$values).'\')';
     $db->query($sql);

     $sql = 'UPDATE '.$ecs->table('users').' u, '.$ecs->table('user_address').' a '
          .' SET u.address_id=a.address_id WHERE u.user_id=a.user_id AND u.user_id='.$user_id;
     $db->query($sql);

     /* 推荐顾客赠送积分 */
     if (!empty($userinfo['parent_id']))
     {
          include_once './includes/cls_integral.php';
          $integ = new integral($ecs->table('integral'), $db);
          $integral = $integ->countIntegral($userinfo['role_id'], 1);
          $validity = strtotime(date('Ym', time())+intval($integral['validity']));

          if ($integral['integral_way'] == 1)
          {
               $sql = 'INSERT INTO '.$ecs->table('user_integral').
                    '(integral_id,integral,source,source_id,receive_time,validity,user_id,admin_id)'.
                    "VALUES('{$integral['integral_id']}','{$integral['scale']}','users','$user_id',".
                    " UNIX_TIMESTAMP(),$validity,'{$userinfo['parent_id']}',{$_SESSION['admin_id']})";
               $db->query($sql);
          }
     }

     /* 记录管理员操作 */
     admin_log($_POST['username'], 'add', 'users');
     $uname = implode('', $_REQUEST['uname']);
     if (!empty($uname))
     {
          insertSocial();
     }

     /* 提示信息 */
     $res['message'] = '该顾客已存在！';
     die($json->encode($res));
}

/*------------------------------------------------------ */
//-- 编辑顾客档案
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'file')
{
     //由于需要设置部分字段的次数，所以需要修改条件中的数字
     //该SQL需要进行深度修改
     $nowtime = time();
     $sql = 'SELECT user_id,sex,user_name,id_card,mobile_phone,home_phone,address,region, remarks FROM '.$GLOBALS['ecs']->table('files').' WHERE nullnum=0 AND noman<10 AND cannot<10 AND ok<>1 AND admin_id='.$_SESSION['admin_id']." AND add_time<$nowtime ORDER BY add_time ASC";
     $user = $db->getRow($sql);

     $smarty->assign('userinfo', $user);

     $smarty->display('users_file.htm');
}
elseif ($_REQUEST['act'] == 'checkfiles')
{
     if (array_key_exists('nullnum', $_POST))
     {
          //空号
          $sql = 'UPDATE '.$GLOBALS['ecs']->table('files').' SET nullnum=1 WHERE user_id='.$_POST['user_id'];
          if($db->query($sql))
          {
               header('location:?act=file');
          }
     }
     elseif(array_key_exists('noman', $_POST))
     {
          //无人接听
          $sql = 'UPDATE '.$GLOBALS['ecs']->table('files').' SET noman=noman+1, add_time=UNIX_TIMESTAMP() WHERE user_id='.$_POST['user_id'];
          if($db->query($sql))
          {
               header('location:?act=file');
          }
     }
     elseif (array_key_exists('cannot', $_POST))
     {
          //打不通
          $sql = 'UPDATE '.$GLOBALS['ecs']->table('files').' SET cannot=cannot+1, add_time=UNIX_TIMESTAMP() WHERE user_id='.$_POST['user_id'];
          if($db->query($sql))
          {
               header('location:?act=file');
          }
     }
     elseif (array_key_exists('night', $_POST))
     {
          //晚上再打
          $sql = 'UPDATE '.$GLOBALS['ecs']->table('files').' SET night=night+1, add_time=UNIX_TIMESTAMP() WHERE user_id='.$_POST['user_id'];
          if($db->query($sql))
          {
               header('location:?act=file');
          }
     }
     elseif (array_key_exists('ok', $_POST))
     {
          //有效
          $sql = 'UPDATE '.$GLOBALS['ecs']->table('files').' SET ok=1 WHERE user_id='.$_POST['user_id'];
          if($db->query($sql))
          {
               $user = $_POST;

               $sql = 'SELECT * FROM '.$ecs->table('customer_type');
               $smarty->assign('customer_type', $db->getAll($sql));

               //获取顾客来源
               $sql = 'SELECT * FROM '.$ecs->table('from_where').' ORDER BY sort ASC';
               $smarty->assign('from_where', $db->getAll($sql));

               //获取顾客经济来源
               $sql = 'SELECT * FROM '.$ecs->table('income');
               $smarty->assign('income', $db->getAll($sql));

               //获取疾病列表
               $sql = 'SELECT * FROM '.$ecs->table('disease');
               $smarty->assign('disease', $db->getAll($sql));

               assign_query_info();
               $smarty->assign('ur_here',          $_LANG['users_edit']);
               $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list&' . list_link_postfix()));
               $smarty->assign('user',             $user);
               $smarty->assign('form_action',      'insert');
               $smarty->assign('special_ranks',    get_rank_list(true));
               $smarty->assign('country_list',     get_regions());
               $smarty->assign('province_list',    get_regions(1, 1));
               $smarty->assign('city_list',        get_regions(2, $user_region['province']));
               $smarty->assign('district_list',    get_regions(3, $user_region['city']));
               $smarty->assign('user_region',      $user_region);
               $smarty->display('user_info.htm');
          }
     }
}

/*------------------------------------------------------ */
//-- 更新用户帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'update')
{
     /* 检查权限 */
     admin_priv('users_edit');
     $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
     $sex = in_array($sex, array(0, 1, 2)) ? $sex : 0;
     $userinfo = array(
          'user_id'       => $_POST['user_id'],
          'user_name'     => trim($_POST['username']),
          'eff_id'        => intval($_POST['eff_id']),
          'sex'           => $sex,
          'age_group'     => $_POST['age_group'],
          'from_where'    => $_POST['from_where'],
          'customer_type' => $_POST['customer_type'],
          'mobile_phone'  => trim($_POST['mobile_phone']),
          'id_card'       => trim($_POST['id_card']),
          'member_cid'    => trim($_POST['member_cid']),
          'qq'            => trim($_POST['qq']),
          'aliww'         => trim($_POST['aliww']),
          'habby'         => trim($_POST['habby']),
          'email'         => trim($_POST['email']),
          'occupat'       => trim($_POST['occupat']),
          'income'        => $_POST['income'],
          'disease'       => isset($_POST['disease']) && is_array($_POST['disease']) ? implode(',', $_POST['disease']) : '',
          'characters'    => isset($_POST['characters']) && is_array($_POST['characters']) ? ','.implode(',', $_POST['characters']).',' : '',
          'disease_2'     => trim($_POST['disease_2']),
          'remarks'       => trim($_POST['remarks']),
          //'parent_id'   => $_POST['recommender'],  // 推荐人信息
          'edit_time'     => time(),
          'snail'         => trim($_POST['snail']),
          'team'          => intval($_POST['team']),
          'admin_name'    => $_SESSION['admin_name'],
          'lang'          => intval($_POST['lang']), // 常用语言
          'parent_id'     => intval($_POST['parent_id']),
          'role_id'       => intval($_POST['role_id'])
     );

     if ($_POST['calendar'] == 1)
     {
          $userinfo['birthday'] = trim($_POST['birthday']);
     }
     else 
     {
          require(dirname(__FILE__) . '/includes/lunar.php');
          $lunar = new Lunar();
          $userinfo['birthday'] = date('Y-m-d', $lunar->S2L(trim($_POST['birthday'])));
     }
     $userinfo['email'] = empty($userinfo['email']) ? empty($userinfo['qq']) ? '' : $userinfo['qq'].'@qq.com' :$userinfo['email']; 

     if (empty($_POST['area_code']))
     {
          $userinfo['home_phone'] = $_POST['home_phone'];
     }
     else
     {
          $userinfo['home_phone'] = $_POST['area_code'].'-'.$_POST['home_phone'];
     }

     $addr = array(
          'user_id'   => $_POST['user_id'],
          'country'   => $_POST['country'],
          'province'  => $_POST['province'],
          'city'      => $_POST['city'],
          'district'  => $_POST['district'],
          'address'   => $_POST['address'],
          'zipcode'   => $_POST['zipcode']
     );

     $users  =& init_users();
     if($users->edit_user($userinfo, $addr))
     {
          foreach ($addr as $key=>$val)
          {
               if($key != 'user_id' || !empty($val))
                    $addr2db[] = $key."='$val'";
          }

          $addr2db = implode(',', $addr2db);
          $sql = 'UPDATE '.$ecs->table('user_address')." SET $addr2db WHERE user_id=$addr[user_id]";
          $db->query($sql);
     }
     else
     {
          if ($users->error == ERR_EMAIL_EXISTS)
          {
               $msg = $_LANG['email_exists'];
          }
          else
          {
               $msg = $_LANG['edit_user_failed'];
          }
          sys_msg($msg, 1);
     }

     /* 更新用户扩展字段的数据 */
     //$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
     //$fields_arr = $db->getAll($sql);
      /*$user_id_arr = $users->get_profile_by_name($username);
      $user_id = $user_id_arr['user_id'];

      foreach ($fields_arr AS $val)       //循环更新扩展用户信息
      {
            $extend_field_index = 'extend_field' . $val['id'];
            if(isset($_POST[$extend_field_index]))
            {
                  $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];

                  $sql = 'SELECT * FROM ' . $ecs->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
                  if ($db->getOne($sql))      //如果之前没有记录，则插入
                  {
                        $sql = 'UPDATE ' . $ecs->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
                  }
                  else
                  {
                        $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
                  }
                  $db->query($sql);
            }
      }
       */

     /* 更新会员的其它信息 */
      /*$other =  array();
      $other['credit_line'] = $credit_line;
      $other['user_rank'] = $rank;

      $other['aliww'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
      $other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
      $other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
      $other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';
      $other['mobile_phone'] = isset($_POST['extend_field5']) ? htmlspecialchars(trim($_POST['extend_field5'])) : '';

      $db->autoExecute($ecs->table('users'), $other, 'UPDATE', "user_name = '$username'");
       */
     /* 记录管理员操作 */
     admin_log($username, 'edit', 'users');

     /* 更新顾客社会关系 */
     $uname = implode('', $_REQUEST['uname']);
     if (!empty($uname))
     {
          updateSocial();
     }

     /* 提示信息 */
     $links[0]['text']    = $_LANG['goto_list'];
     $links[0]['href']    = 'users.php?act=list&' . list_link_postfix();
     $links[1]['text']    = $_LANG['go_back'];
     $links[1]['href']    = 'javascript:history.back()';

     sys_msg($_LANG['update_success'], 0, $links);

}

/*------------------------------------------------------ */
//-- 批量删除会员帐号
/*------------------------------------------------------ */

elseif($_REQUEST['act'] == 'del_rela')
{
     $sql = 'DELETE FROM '.$GLOBALS['ecs']->table('user_relation').
          " WHERE user_id={$_REQUEST['user_id']} AND rela_id={$_REQUEST['rela_id']}";
     if (1)//$GLOBALS['db']->query($sql))
     {
          echo 1;
     }
     else 
     {
          echo 0;
     }
     exit;
}
/*------------------------------------------------------ */
//-- 批量删除会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_remove')
{
     /* 检查权限 */
     admin_priv('users_drop');

     if (isset($_POST['checkboxes']))
     {
          $sql = 'UPDATE '.$ecs->table('users').' SET admin_id=-1 WHERE user_id '.
               db_create_in($_POST['checkboxes']);
          $db->query($sql);
          //$sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id " . db_create_in($_POST['checkboxes']);
        /*
        $col = $db->getCol($sql);
        $usernames = implode(',',addslashes_deep($col));
        $count = count($col);
         */
          /* 通过插件来删除用户 */
          //$users =& init_users();
          //$users->remove_user($col);

          admin_log($usernames, 'batch_remove', 'users');

          $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
          sys_msg(sprintf($_LANG['batch_remove_success'], $count), 0, $lnk);
     }
     else
     {
          $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
          sys_msg($_LANG['no_select_user'], 0, $lnk);
     }
}

/* 编辑用户名 */
elseif ($_REQUEST['act'] == 'edit_username')
{
     /* 检查权限 */
     check_authz_json('users_manage');

     $username = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));
     $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

     if ($id == 0)
     {
          make_json_error('NO USER ID');
          return;
     }

     if ($username == '')
     {
          make_json_error($GLOBALS['_LANG']['username_empty']);
          return;
     }

     $users =& init_users();

     if ($users->edit_user($id, $username))
     {
          if ($_CFG['integrate_code'] != 'ecshop')
          {
               /* 更新商城会员表 */
               $db->query('UPDATE ' .$ecs->table('users'). " SET user_name = '$username' WHERE user_id = '$id'");
          }

          admin_log(addslashes($username), 'edit', 'users');
          make_json_result(stripcslashes($username));
     }
     else
     {
          $msg = ($users->error == ERR_USERNAME_EXISTS) ? $GLOBALS['_LANG']['username_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
          make_json_error($msg);
     }
}

/*------------------------------------------------------ */
//-- 编辑email
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_email')
{
     /* 检查权限 */
     check_authz_json('users_manage');

     $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
     $email = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));

     $users =& init_users();

     $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '$id'";
     $username = $db->getOne($sql);


     if (is_email($email))
     {
          if ($users->edit_user(array('username'=>$username, 'email'=>$email)))
          {
               admin_log(addslashes($username), 'edit', 'users');

               make_json_result(stripcslashes($email));
          }
          else
          {
               $msg = ($users->error == ERR_EMAIL_EXISTS) ? $GLOBALS['_LANG']['email_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
               make_json_error($msg);
          }
     }
     else
     {
          make_json_error($GLOBALS['_LANG']['invalid_email']);
     }
}

/*------------------------------------------------------ */
//-- 删除会员帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove')
{
     /* 检查权限 */
     admin_priv('users_drop');
     $sql = 'UPDATE '.$ecs->table('users')." SET admin_id=-1 WHERE user_id=$_GET[id]";
     $db->query($sql);


     //$sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
     //$username = $db->getOne($sql);
     /* 通过插件来删除用户 */
     //$users =& init_users();
     //$users->remove_user($username); //已经删除用户所有数据

     /* 记录管理员操作 */
     admin_log(addslashes($username), 'remove', 'users');

     /* 提示信息 */
     $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
     sys_msg(sprintf($_LANG['remove_success'], $username), 0, $link);
}

/*------------------------------------------------------ */
//--  收货地址查看
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'address_list')
{
     $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
     $sql = "SELECT a.*, c.region_name AS country_name, p.region_name AS province, ct.region_name AS city_name, d.region_name AS district_name ".
          " FROM " .$ecs->table('user_address'). " as a ".
          " LEFT JOIN " . $ecs->table('region') . " AS c ON c.region_id = a.country " .
          " LEFT JOIN " . $ecs->table('region') . " AS p ON p.region_id = a.province " .
          " LEFT JOIN " . $ecs->table('region') . " AS ct ON ct.region_id = a.city " .
          " LEFT JOIN " . $ecs->table('region') . " AS d ON d.region_id = a.district " .
          " WHERE user_id='$id'";
     $address = $db->getAll($sql);
     $smarty->assign('address',          $address);
     assign_query_info();
     $smarty->assign('ur_here',          $_LANG['address_list']);
     $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list&' . list_link_postfix()));
     $smarty->display('user_address_list.htm');
}

/*------------------------------------------------------ */
//-- 脱离推荐关系
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove_parent')
{
     /* 检查权限 */
     admin_priv('users_manage');

     $sql = "UPDATE " . $ecs->table('users') . " SET parent_id = 0 WHERE user_id = '" . $_GET['id'] . "'";
     $db->query($sql);

     /* 记录管理员操作 */
     $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
     $username = $db->getOne($sql);
     admin_log(addslashes($username), 'edit', 'users');

     /* 提示信息 */
     $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
     sys_msg(sprintf($_LANG['update_success'], $username), 0, $link);
}

/*------------------------------------------------------ */
//-- 查看用户推荐会员列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'aff_list')
{
     /* 检查权限 */
     admin_priv('users_manage');
     $smarty->assign('ur_here',      $_LANG['03_users_list']);

     $auid = $_GET['auid'];
     $user_list['user_list'] = array();

     $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
     $smarty->assign('affiliate', $affiliate);

     empty($affiliate) && $affiliate = array();

     $num = count($affiliate['item']);
     $up_uid = "'$auid'";
     $all_count = 0;
     for ($i = 1; $i<=$num; $i++)
     {
          $count = 0;
          if ($up_uid)
          {
               $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
               $query = $db->query($sql);
               $up_uid = '';
               while ($rt = $db->fetch_array($query))
               {
                    $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                    $count++;
               }
          }
          $all_count += $count;

          if ($count)
          {
               $sql = "SELECT user_id, user_name, '$i' AS level, email, is_validated, user_money, frozen_money, rank_points, pay_points, add_time ".
                    " FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id IN($up_uid)" .
                    " ORDER by level, user_id";
               $user_list['user_list'] = array_merge($user_list['user_list'], $db->getAll($sql));
          }
     }

     $temp_count = count($user_list['user_list']);
     for ($i=0; $i<$temp_count; $i++)
     {
          $user_list['user_list'][$i]['add_time'] = date($_CFG['date_format'], $user_list['user_list'][$i]['add_time']);
     }

     $user_list['record_count'] = $all_count;

     $smarty->assign('user_list',    $user_list['user_list']);
     $smarty->assign('record_count', $user_list['record_count']);
     $smarty->assign('full_page',    1);
     $smarty->assign('action_link',  array('text' => $_LANG['back_note'], 'href'=>"users.php?act=edit&id=$auid"));

     assign_query_info();
     $smarty->display('affiliate_list.htm');
}

/* 单个顾客转移-页面 */
elseif ($_REQUEST['act'] == 'transfer')
{
     $user['user_id'] = intval($_GET['user_id']);
     $user['user_name'] = mysql_real_escape_string(trim($_GET['user_name']));
     $sql = 'SELECT user_id, user_name FROM '.$GLOBALS['ecs']->table('admin_user').' WHERE transfer=1';
     $result = $db->getAll($sql);

     $smarty->assign('user', $user);
     $smarty->assign('admin', $result);
     $smarty->display('users_transfer.htm');
}

/* 单个顾客转移-执行转移 */
elseif ($_REQUEST['act'] == 'give_up')
{
     $to_id = intval($_REQUEST['to_id']); // 目标客服id
     $user_id = intval($_REQUEST['uid']); // 要转赠的顾客id

     // 将返回的信息参数
     $res['id']      = $user_id;
     $res['info']    = $request['info'];
     $res['type']    = $request['type'];
     $res['req_msg'] = true;
     $res['timeout'] = 2000;

     // 查询目标客服与该顾客当前所属客服是否为同一人
     $sql_select = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('users').
          " WHERE user_id=$user_id AND admin_id=$to_id";
     $is_same_admin = $GLOBALS['db']->getOne($sql_select);
     if ($is_same_admin){
          $res['message'] = '所属客服与目标客服一致，无需转赠！';
          die($json->encode($res));
     }

     // 查询目标客服是否可以接收顾客
     $sql_select = 'SELECT max_customer FROM '.$GLOBALS['ecs']->table('admin_user').
          " WHERE max_customer>0 AND user_id=$to_id";
     $max_customer = $GLOBALS['db']->getOne($sql_select);
     if (!empty($max_customer)){
          // 统计目标客服已经拥有的顾客数量
          $sql_select = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('users')." WHERE admin_id=$to_id";
          $had_users_number = $GLOBALS['db']->getOne($sql_select);
     }
     else{
          // 目标客服不允许接收顾客
          $res['message'] = '目标客服不适合接收顾客！';
          die($json->encode($res));
     }

     if ($max_customer > $had_users_number){
          $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users')." SET last_admin=admin_id WHERE user_id=$user_id";
          $GLOBALS['db']->query($sql_update);

          $now_time = time();
          $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users').' u, '.$GLOBALS['ecs']->table('admin_user').
               " a SET u.admin_id=$to_id,u.assign_time=$now_time,u.user_cat='',u.admin_name=a.user_name WHERE u.user_id=$user_id AND a.user_id=$to_id";
          $GLOBALS['db']->query($sql_update);

          $res['message'] = '顾客转赠成功！';
          $res['code'] = 1;
          die($json->encode($res));
     }
     else{
          // 目标客服的顾客数量已达到上限
          $res['message'] = '目标客服的顾客数量已达到上线！';
          die($json->encode($res));
     }
}

//查看顾客详细信息
elseif ($_REQUEST['act'] == 'get_detail')
{
     $sql = 'SELECT user_id,user_name,sex,IF(birthday="1952-01-01",age_group,CONCAT(LEFT(NOW(),4) - LEFT(birthday,4),"岁")) birthday,parent_id,from_where,home_phone,mobile_phone,id_card,email,qq,aliww,income,habby,disease,characters,remarks FROM '.
          $GLOBALS['ecs']->table('users').' WHERE user_id='.$_GET['user_id'];
     $result = $GLOBALS['db']->getRow($sql);
     extract($result);

     //获取客户来源
     $sql = 'SELECT `from` FROM '.$GLOBALS['ecs']->table('from_where').' WHERE from_id='.$from_where;
     $from_where = $GLOBALS['db']->getOne($sql);

     //获取经济来源
     $sql = 'SELECT income FROM '.$GLOBALS['ecs']->table('income').' WHERE income_id='.$income;
     $income = $GLOBALS['db']->getOne($sql);

     $sql = 'SELECT zipcode, address, province, city, district FROM '.
          $GLOBALS['ecs']->table('user_address').' WHERE user_id='.$user_id;
     $address = $GLOBALS['db']->getRow($sql);

     if($address['province'] && $address['city'])
     {
          extract($address);
          $province = get_address($province);
          $city     = get_address($city);
          if (!empty($district))
          {
               $district = get_address($district);
          }
          else 
          {
               $district = '';
          }

          $address = $province.$city.$district.$address;
     }
     else
     {
          $address = "请到点击<a href='users.php?act=edit&id=$user_id' title='编辑'><img src='images/icon_edit.gif' alt='编辑' /></a>完善顾客地址信息！";
     }

     switch($sex)
     {
     case 1 : $sex = '男'; break;
     case 2 : $sex = '女'; break;
     case 0 : $sex = '未知'; break;
     }

     if (!empty($disease))
     {
          $sql = 'SELECT disease FROM '.$GLOBALS['ecs']->table('disease').' WHERE disease_id IN ('.$disease.')';
          $disease = $GLOBALS['db']->getAll($sql);

          foreach ($disease as $val)
          {
               $temp[] = $val['disease'];
          }

          $disease = implode(',', $temp);
          unset($temp);
     }

     if (!empty($characters) && $characters != ',')
     {
          $sql = 'SELECT characters FROM '.$GLOBALS['ecs']->table('character').
               ' WHERE character_id IN ('.substr($characters, 1, -1).')';
          $characters = $GLOBALS['db']->getAll($sql);

          foreach ($characters as $val)
          {
               $temp[] = $val['characters'];
          }

          $characters = implode('，', $temp);
          unset($temp);
     }

     // 获取服务信息
     $ex_where = " WHERE s.user_id={$_GET['user_id']} AND s.service_class=c.class_id AND s.service_manner=m.manner_id ";
     $sql = "SELECT s.admin_name,s.service_id,c.class service_class,m.manner service_manner,FROM_UNIXTIME(service_time,'%m月%d日 %H:%i') servicetime,user_name,service_status,logbook,admin_id FROM ".$GLOBALS['ecs']->table('service').' s,'.$GLOBALS['ecs']->table('service_class').' c,'.$GLOBALS['ecs']->table('service_manner').' m '.$ex_where." ORDER by service_time ASC";
     $list = $GLOBALS['db']->getAll($sql);

     $no = 0;
     foreach($list as $val)
     {
          //++$no;
          extract($val);
          $final .= '【'.$servicetime.'】<font color="olive">'.$admin_name.'</font>通过<font color="olive">'.$service_manner.'</font>进行<font color="olive">'.$service_class.'</font>：'.$logbook.'<br>';
     }

     /* 获取订单信息 */
     $sql = 'SELECT i.order_id,FROM_UNIXTIME(i.add_time,"%Y-%m-%d") add_time,i.final_amount,i.shipping_fee,r.role_name,i.admin_id,i.add_admin_id,i.platform FROM '.
          $GLOBALS['ecs']->table('order_info').' i LEFT JOIN '.$GLOBALS['ecs']->table('admin_user').
          ' u ON i.add_admin_id=u.user_id LEFT JOIN '.$GLOBALS['ecs']->table('role').
          " r ON u.role_id=r.role_id WHERE i.pay_status=2 AND i.order_status=5 AND i.user_id={$_GET['user_id']} ORDER BY i.add_time DESC";
     $order_list = $GLOBALS['db']->getAll($sql);
     if(!empty($order_list))
     {
          foreach ($order_list as $val)
          {
               $sql = 'SELECT goods_name, goods_number, goods_price FROM '.
                    $GLOBALS['ecs']->table('order_goods')." WHERE order_id={$val['order_id']}";
               $goods_list = $GLOBALS['db']->getAll($sql);

               //$sql = 'SELECT r.role_name FROM '.$GLOBALS['ecs']->table('admin_user')." au, ".
               //$GLOBALS['ecs']->table('role').' r '." WHERE au.role_id=r.role_id AND au.user_id={$val['add_admin_id']}";
               $sql = 'SELECT role_name FROM '.$GLOBALS['ecs']->table('role')." WHERE role_id={$val['platform']}";
               $platform = $GLOBALS['db']->getOne($sql);

               $order_record[$val['order_id']] = '【'.$val['add_time'].'】在<font color="red">&nbsp;&nbsp;'.
                    $platform.'&nbsp;&nbsp;</font>购买<font color="olive"> ';
               foreach ($goods_list as $v)
               {
                    $order_record[$val['order_id']] .= $v['goods_name'].' ('.$v['goods_number'].') ，&nbsp;';
               }

               $order_record[$val['order_id']] .= '</font>共'.$val['final_amount'].'元';
          }

          $order_record = implode('<br />', $order_record);
          $sql = 'SELECT SUM(goods_amount) FROM '.$GLOBALS['ecs']->table('order_info').
               " WHERE order_status=5 AND pay_status=2 AND user_id=".intval($_GET['user_id']);
          $all_money = '共'.$GLOBALS['db']->getOne($sql).'元';
     }

     $sql = 'SELECT * FROM '.$GLOBALS['ecs']->table('user_photos')." WHERE user_id=".intval($_GET['user_id']);
     $photos = $GLOBALS['db']->getAll($sql);

     $smarty->assign('photos', $photos);

     $smarty->assign('user_id', intval($_GET['user_id']));
     $smarty->assign('user_name', $user_name);
     $smarty->assign('member_cid', $member_cid);
     $smarty->assign('id_card', $id_card);
     $smarty->assign('sex', $sex);
     $smarty->assign('zipcode', $zipcode);
     $smarty->assign('home_phone', $home_phone);
     $smarty->assign('mobile_phone', $mobile_phone);
     $smarty->assign('qq', $qq);
     $smarty->assign('birthday', $birthday);
     $smarty->assign('from_where', $from_where);
     $smarty->assign('aliww', $aliww);
     $smarty->assign('characters', $characters);
     $smarty->assign('address', $address);
     $smarty->assign('email', $email);
     $smarty->assign('remarks', $remarks);
     $smarty->assign('income', $income);
     $smarty->assign('disease', $disease);
     $smarty->assign('final', $final);
     $smarty->assign('all_money', $all_money);
     $smarty->assign('order_record', $order_record);

     die($smarty->fetch('detail.htm'));
}

// 验证顾客是否已经存在
elseif ($_REQUEST['act'] == 'is_repeat')
{
     $area_code = trim($_POST['area_code']);
     $hphone    = trim($_POST['hphone']);
     $mphone    = trim($_POST['mphone']);

     if(!empty($area_code))
     {
          $hphone = "$area_code-$hphone";
     }

     if($hphone)
     {
          $where = " home_phone='$hphone'";
     }

     if($where && $mphone)
     {
          $where .= " OR mobile_phone='$mphone'";
     }
     elseif($mphone)
     {
          $where = " mobile_phone='$mphone'";
     }

     $sql = 'SELECT admin_name FROM '.$ecs->table('users')." WHERE $where";
     $admin_name = $db->getOne($sql);

     if(!empty($admin_name))
     {
          die($admin_name);
     }
     else
     {
          die(0);
     }
}

// 快速添加服务
elseif ($_REQUEST['act'] == 'add_service')
{
     $smarty->assign('user_id', $_GET['user_id']);
     $smarty->assign('username', $_GET['username']);
     $smarty->assign('service_class', get_service_class());
     $smarty->assign('service_manner', get_service_manner());

     // 获取用户的生日信息
     $sql = 'SELECT birthday FROM '.$GLOBALS['ecs']->table('users').
          ' WHERE user_id='.intval($_GET['user_id']);
     $birthday = $GLOBALS['db']->getOne($sql);

     $invalid_birth = array ('0000-00-00', '1970-01-01', '1952-01-01');
     if (empty($birthday) || in_array($birthday, $invalid_birth))
     {
          $smarty->assign('birthday', $birthday);
     }

     // 获取性格列表
     $sql = 'SELECT character_id, characters FROM '.$ecs->table('character').' ORDER BY sort ASC';
     $smarty->assign('character', $db->getAll($sql));

     // 判断用户性格、杂志、专项服务、购买意向是否已经存在
     $sql = 'SELECT characters, dm, mag_no, purchase FROM '.$ecs->table('users')." WHERE user_id=$_GET[user_id]";
     $cdmp = $db->getRow($sql);
     $smarty->assign('has_character', $cdmp['characters']);
     $smarty->assign('dm', $cdmp['dm']);
     $smarty->assign('purchase', $cdmp['purchase']);

     // 获取评分类型
     $sql = 'SELECT grade_type_id,grade_type_name FROM '.$GLOBALS['ecs']->table('grade_type').' 
          WHERE available = 1 ORDER BY sort';
     $grade_type = $GLOBALS['db']->getAll($sql);
     $smarty->assign('grade_type',$grade_type);  

     // 初始化时间
     $smarty->assign('default_time', date('Y-m-d H:i', (time())));

     $smarty->assign('form', 1);

     die($smarty->fetch('fast_service.htm'));
}

// 快速添加服务----提交至数据库
elseif ($_REQUEST['act'] == 'fast_add')
{
     $service_info = array (
          'service_manner'   => intval($_POST['service_manner']),
          'service_class'    => intval($_POST['service_class']),
          'service_time'     => strtotime($_POST['service_time']),
          'service_status'   => intval($_POST['service_status']),
          //'special_feedback' => $_POST['special_feedback'],
          'logbook'          => mysql_real_escape_string(trim($_POST['logbook'])),
          'admin_id'         => $_SESSION['admin_id'],
          'admin_name'       => $_SESSION['admin_name'],
          'user_id'          => intval($_POST['user_id']),
          'user_name'        => mysql_real_escape_string(trim($_POST['user_name']))
     );

     // 优先保存顾客生日
     $birthday = mysql_real_escape_string(trim($_POST['birthday']));
     $sql = 'UPDATE '.$GLOBALS['ecs']->table('users').
          " SET birthday='$birthday' WHERE user_id={$service_info['user_id']}";
     $GLOBALS['db']->query($sql);

     if(isset($_POST['handler']) && strlen($_POST['handler']) > 0)
     {
          $service_info['handler'] = strtotime($_POST['handler']);
     }

     $characters = substr($_POST['characters'], 1);
     $purchase = $_POST['purchase'];
     $service_info['service_time'] = $service_info['service_time'] ? $service_info['service_time'] : time();

     foreach ($service_info as $key=>$val)
     {
          if ($val)
          {
               if($key == 'special_feedback' && $val == 1)
                    continue;
               $fields[] = $key;
               $values[] = $val;
          }
     }

     $sql = 'INSERT INTO '.$ecs->table('service').'('.implode(',', $fields)
          .')VALUES("'.implode('","', $values).'")';
     if ($db->query($sql))
     {
          if($characters)
          {
               $characters = ", u.characters=',$characters,'";
          }

          if ($purchase && $purchase != 1)
          {
               $dm = ", u.purchase='$purchase', u.dm=0";
          }

          $sql = 'UPDATE '.$ecs->table('users').' u, '.$ecs->table('service')." s SET u.service_time=$service_info[service_time] $characters $dm WHERE u.user_id=$service_info[user_id]";
          $db->query($sql);

          //获取service表关联service_id
          $sql = 'SELECT service_id FROM '.$GLOBALS['ecs']->table('service'). 
               "WHERE service_time = $service_info[service_time] AND user_id = $service_info[user_id]";
          $service_id = $GLOBALS['db']->getOne($sql);
          // 获取评分类型
          $sql = 'SELECT grade_type_id FROM '.$GLOBALS['ecs']->table('grade_type').' 
               WHERE available = 1 ORDER BY sort';
          $grade_type = $GLOBALS['db']->getCol($sql);
          $insert = '';
          foreach($grade_type as $grade){
               $i = grade.$grade; 
               if($_REQUEST[$i]){
                    $insert .= "('".$grade."','".$_REQUEST[$i]."','.$service_id.'),";
               }                
          }
          $insert = rtrim($insert,',');
          if($insert){
               $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('grade').' (grade_type_id,grade_type_value,service_id)'.'
                    VALUES '.$insert;
               $GLOBALS['db']->query($sql); 
          }

          die($smarty->fetch('fast_service.htm'));
     }

}

/* 批量修改顾客归属 */
elseif ($_REQUEST['act'] == 'batch')
{
    if(admin_priv('batch','',false))
    {
        $sql_select = 'SELECT user_name, user_id FROM '.$ecs->table('admin_user').
            ' WHERE role_id>0 OR user_id=30';

        $admin_list = $GLOBALS['db']->getAll($sql_select);

        $smarty->assign('admin_list', $admin_list);
        $res['main']=$smarty->fetch('batch_transfer.htm');

        die($json->encode($res));
    }
}

/* 执行对数据库的操作 */
elseif($_REQUEST['act'] == 'from_to')
{
    if(admin_priv('all','',false))
    {
        $to_admin = intval($_REQUEST['to_admin']);
        $from_admin = intval($_REQUEST['from_admin']);
        $limit = intval($_REQUEST['transfer_num']);
        $condition = '';
        
        $sql = 'SELECT user_name, role_id FROM '.$GLOBALS['ecs']->table('admin_user')." WHERE user_id=$to_admin";
        $res = $GLOBALS['db']->getRow($sql);

        $result = array();
        $result['req_msg'] = true;
        $result['timeout'] = 2000;

        if (empty($res))
        {
            $result['message'] = '没有找到目标客服';
            die($json->encode($result));
        }
        else
        {
            $sql_select = 'SELECT user_id FROM '.$GLOBALS['ecs']->table('users')." WHERE admin_id=$from_admin";
            $transfer_user = $GLOBALS['db']->getCol($sql_select);
            if($limit != 0)
            {
                $condition = " Limit $limit";
                $transfer_num = $limit;
            }
            else
                $transfer_num = count($transfer_user);
            $transfer_user = implode(',',$transfer_user);   //转移的顾客

            if($transfer_user == '')
            {
                $result['message'] = ' 源客服已没有顾客';
                die($json->encode($result));
            }
        }

        $to_admin_name = $res['user_name'];
        $to_role_id = $res['role_id'];

        $sql = 'UPDATE '.$ecs->table('users').
            " SET admin_id=$to_admin, admin_name='$to_admin_name', role_id=$to_role_id WHERE admin_id=$from_admin $condition";

        if($GLOBALS['db']->query($sql))
        {
            $result['message'] = '转移成功';

            //记录操作
            $sql_update = 'INSERT INTO '.$GLOBALS['ecs']->table('transfer_record').' (from_admin,to_admin,handler_admin,transfer_time,transfer_user,transfer_num) '.
                "VALUES($from_admin,$to_admin,{$_SESSION['admin_id']},UNIX_TIMESTAMP(NOW()),'$transfer_user',$transfer_num)";
            $GLOBALS['db']->query($sql_update);
        }
        else
        {
            $result['message'] = '转移失败，或没有找到目标客服';
        }
        die($json->encode($result));
    }
}

/* 转移部分顾客归属 */  
elseif ($_REQUEST['act'] == 'part_transfer')
{
    if(admin_priv('part_transfer','',false))
    {
        //获取存在客户或者临时客户的名字
        $sql_select = 'SELECT user_name, user_id FROM '.$ecs->table('admin_user').' WHERE role_id>0 ';
        $admin_list = $db->getAll($sql_select);

        $smarty->assign('admin_list', $admin_list);
        $res['main'] = $smarty->fetch('part_transfer.htm');

        die($json->encode($res));
    }
}

/* 顾客转移 */
elseif($_REQUEST['act'] == 'transfer_submit')
{
    $from_phone = htmlspecialchars($_REQUEST['from_phone']);    //联系电话
    $from_phone = preg_split('/[^0-9\-]+/',$from_phone);        //从非数字和-中分割字符串
    $from_phone = array_filter($from_phone);                    //去除值为空的元素
    $from_phone = array_slice($from_phone,0);                   //数组键值从0开始排序 
    $phone = $msg = array();
    $msg['req_msg'] = true;
    $msg['timeout'] = 2000;

    for($i=0;$i<count($from_phone);$i++)
    {     
        //获取格式正确的电话号码或手机号码
        if(preg_match('/^(\d{3}-)(\d{8})$|^(\d{4}-)(\d{7,8})$|^(\d{11})$/',$from_phone[$i]))
        {
            $phone[] = $from_phone[$i];
        }
    } 

    $from_phone = implode('\',\'',$phone); 

    if(empty($from_phone) || $from_phone==0)
    {
        $msg['message'] = '顾客联系方式有误';
        die($json->encode($msg));
    } 

    //获取转移目标客服
    $to_admin = intval($_REQUEST['to_admin']);

    $sql = 'SELECT user_name, role_id FROM '.$GLOBALS['ecs']->table('admin_user').' WHERE user_id='.$to_admin;
    $res = $GLOBALS['db']->getRow($sql);

    $to_admin_name = $res['user_name'];
    $role_id       = $res['role_id'];

    if($res)
    {
        //检查客服的权限
        if($_SESSION['action_list'] == 'all')
        {
            $sql = 'SELECT admin_id FROM '.$GLOBALS['ecs']->table('users')."
                WHERE mobile_phone IN ('".$from_phone."') OR home_phone IN ('".$from_phone."')";

            $from_admin = $GLOBALS['db']->getOne($sql);     //源客服
            if(empty($from_admin))
            {
                $msg['message'] = '顾客联系方式不正确或没有找到目标顾客';
                die($msg);
            }
            $where = ' admin_id>0 ';
        }
        else
        {
            $from_admin = $_SESSION['admin_id'];
            $where = 'admin_id='.$_SESSION['admin_id'];
        }

        $sql = 'UPDATE '.$GLOBALS['ecs']->table('users').' SET admin_id='.$to_admin.",admin_name='".
            $to_admin_name."', role_id=$role_id WHERE ".$where." AND mobile_phone IN ('".$from_phone.
            "') OR home_phone IN ('".$from_phone."')";

        $result = $GLOBALS['db']->query($sql);

        $transfer_num = mysql_affected_rows();
        if($transfer_num == 0 || empty($transfer_num))
        {
            $msg['message'] = '转移失败';
            die($msg);
        }

        if($result)
        {
            //获取转移的顾客的user_id
            $sql = ' SELECT user_id FROM '.$GLOBALS['ecs']->table('users').
                " WHERE mobile_phone IN ('".$from_phone."') OR home_phone IN ('".$from_phone."')";
            $from_userid = $GLOBALS['db']->getCol($sql);
            $user_id = implode(',',$from_userid);

            //获取转移的时间戳
            $transfer_time = strtotime("now");

            //转移记录插入数据库
            $sql = ' INSERT INTO '.$GLOBALS['ecs']->table('transfer_record').
                "(from_admin,to_admin,handler_admin,transfer_time,transfer_user,transfer_num)VALUES('".
                $from_admin."','".$to_admin."','".$_SESSION['admin_id']."','".$transfer_time."','".
                $user_id."','".$transfer_num."')";

            $GLOBALS['db']->query($sql);

            //转移的时间戳插入到user表中
            $user_id = implode('\',\'',$from_userid);
            $sql = ' UPDATE '.$GLOBALS['ecs']->table('users').' SET transfer_time='.$transfer_time." 
                WHERE user_id IN ('".$user_id."')";

            $GLOBALS['db']->query($sql);
            $msg['message'] = '转移成功';
        }
    }
    else
    {
        $msg['message'] = '转移目标客服输入不一致';
    } 

    die($json->encode($msg));
}

/* 重新分配 */
elseif ($_REQUEST['act'] == 'setEffect')
{
    //admin_priv('user_list');

    include_once(ROOT_PATH.'includes/cls_json.php');
    $json = new JSON;

    $userId = intval($_REQUEST['userId']);
    $effId  = intval($_REQUEST['effId']);

    /* 重置功效分配，以便可以修改 */
    if (isset($_REQUEST['req']) && $_REQUEST['req'] == 'showEdit')
    {
        $sql = 'UPDATE '.$GLOBALS['ecs']->table('users').
            " SET eff_id=0 WHERE user_id=$userId";
        if ($GLOBALS['db']->query($sql))
        {
            $res = array(
                'code' => 2,
                    'ele'  => $userId,
                    'msg'  => '请刷新页面'
               );
               echo $json->encode($res);
               exit;
          }
     }


     if (isset($_REQUEST['req']) && $_REQUEST['req'] == 'showEdit')
     {
          $res = array(
               'code' => 2,
               'ele'  => $userId,
               'msg'  => getEffectTypes()
          );
          echo $json->encode($res);
          exit;
     }

     if (empty($userId))
     {
          echo $json->encode(array('code'=>0, 'msg'=>'用户ID丢失，请联系管理员！'));
          exit;
     }

     $sql = 'UPDATE '.$GLOBALS['ecs']->table('users').
          " SET eff_id=$effId WHERE user_id=$userId";
     if ($GLOBALS['db']->query($sql))
     {
          $sql = 'SELECT eff_name FROM '.$GLOBALS['ecs']->table('effects').
               " WHERE eff_id=$effId";
          echo $json->encode(array('code'=> 1, 'msg'=> $GLOBALS['db']->getOne($sql), 'ele'=>$userId));
          exit;
     }
     else 
     {
          echo $json->encode(array('code'=>0, 'msg'=> '出错啦，请稍后再试'));
          exit;
     }

}

/* 日期切换 */
elseif ($_REQUEST['act'] == 'calendar')
{
     require '..\includes\cls_json.php';
     require 'includes\lunar.php';

     $json  = new JSON;
     $lunar = new Lunar;

     if ($_REQUEST['type'] == 2)
     {
          $nl = date("Y-m-d",$lunar->S2L($_REQUEST['birthday']));
          echo $temp = $json->encode(array('type' => '农历：', 'date' =>$nl));
     }
     else 
     {
          //$date = $lunar->convertLunarToSolar($date[0], $date[1], $date[2]);
          $gl = date("Y-m-d",$lunar->L2S($_REQUEST['birthday']));   
          die($json->encode(array('type' => '公历：', 'date' => $gl)));
     }
}

/* 查找顾客 */
elseif ($_REQUEST['act'] == 'find_referrer')
{
     admin_priv('users_add');

     $keywords = mysql_real_escape_string($_REQUEST['keywords']);
     $sql = 'SELECT DISTINCT user_id,user_name FROM '.$GLOBALS['ecs']->table('users').
          " WHERE user_name LIKE '%$keywords%' OR mobile_phone LIKE '%$keywords%' ".
          " OR home_phone LIKE '%$keywords%' ";
     $res = $GLOBALS['db']->getAll($sql);

     die($json->encode($res));
}

/* 售后服用时间预测 */
elseif ($_REQUEST['act'] == 'forecast')
{
     /* 统计每位客服的销售额 */
     $res = array ('switch_tag' => true, 'id' => 1);
     $end   = $time ? $start +3600*24*$time : strtotime('tomorrow') +3600*24;

     $forecast_list = forecast_list();
     $smarty->assign('orders', $forecast_list['forecast_list']);

     // 分页设置
     $smarty->assign('filter',       $forecast_list['filter']);
     $smarty->assign('record_count', $forecast_list['record_count']);
     $smarty->assign('page_count',   $forecast_list['page_count']);
     $smarty->assign('page_size',    $forecast_list['page_size']);
     $smarty->assign('page_start',   $forecast_list['start']);
     $smarty->assign('page_end',     $forecast_list['end']);
     $smarty->assign('full_page',    1);
     $smarty->assign('page_set',     $forecast_list['page_set']);
     $smarty->assign('page',         $forecast_list['page']);
     $smarty->assign('act',          $_REQUEST['act']);

     $res['main'] = $smarty->fetch('forecast_list.htm');

     die($json->encode($res));
}

/* 可淘顾客 */
elseif ($_REQUEST['act'] == 'ask_customer_list')
{
    admin_priv('ask_customer_list');
    $admin = array('role_id' => $_SESSION['role_id'],'admin_id'=> $_SESSION['admin_id'],'panel'=>1);

    $filter = array();
    $filter['page'] = intval($_REQUEST['page']);                      //当前页
    $filter['page_size'] = intval($_REQUEST['page_size']);            //每页条数

    if($admin['role_id'] == '')
    {
        $smarty->assign('role',get_role());
    }

    $result = ask_customer('ask_customer_list_view',$filter);
    $askable = array('customer'=>$result['result'],'title'=>'可淘顾客','type'=>0);
    $result['filter']['act'] = 'ask_customer_list';

    $smarty->assign('customer',$askable);
    $smarty->assign('admin',$admin);
    $smarty->assign('filter',$result['filter']);

    if(!(intval($_REQUEST['times'])))
    {
        $res['main'] = $smarty->fetch('ask_customer.htm');    
    }
    else
    {
        $res = $smarty->fetch('ask_content.htm');
    }

    die($json->encode($res));
}

/* 已淘顾客列表 */
elseif ($_REQUEST['act'] == 'asked_customer_list')
{
    admin_priv('asked_customer_list');   
    $admin = array('role_id'=>$_SESSION['role_id'],'admin_id'=>$_SESSION['admin_id'],'panel'=>2);

    //分页参数
    $filter = array();
    $page = intval($_REQUEST['page']);                      //当前页
    $page_size = intval($_REQUEST['page_size']);            //每页条数

    if($admin['role_id'] == '')
    {
        $smarty->assign('role',get_role());
    }

    $result = ask_customer('asked_customer',$filter);
    $asked = array('customer'=>$result['result'],'title'=>'已淘顾客','type'=>1);
    $result['filter']['act'] = 'asked_customer_list';

    $smarty->assign('customer',$asked);
    $smarty->assign('admin',$admin);
    $smarty->assign('filter',$result['filter']);

    $res = $smarty->fetch('ask_content.htm');
    die($json->encode($res));
}

/* 淘顾客记录 */
elseif ($_REQUEST['act'] == 'asked_history')
{
    admin_priv('asked_history');   
    $filter = array();
    $filter['page'] = intval($_REQUEST['page']);
    $filter['page_size'] = intval($_REQUEST['page_size']);

    $admin = array('role_id' => $_SESSION['role_id'],'admin_id'=> $_SESSION['admin_id'],'title'=>'淘顾客记录','panel'=>3);

    if($admin['role_id'] == '')
    {
        $smarty->assign('role',get_role());
    }

    $sqlstrs['select'] = 'SELECT DISTINCT e.*,r.role_name FROM '.$GLOBALS['ecs']->table('exchange_his').
        ' AS e LEFT JOIN '.$GLOBALS['ecs']->table('role').' AS r ON e.role_id=r.role_id'.
        ' WHERE to_admin_id='.$admin['admin_id'].' OR from_admin_id='.$admin['admin_id'];
    $sqlstrs['count'] = 'SELECT DISTINCT count(*) as count FROM '.$GLOBALS['ecs']->table('exchange_his').
        ' AS e LEFT JOIN '.$GLOBALS['ecs']->table('role').' AS r ON e.role_id=r.role_id'.
        ' WHERE to_admin_id='.$admin['admin_id'].' OR from_admin_id='.$admin['admin_id'];

    $result = filter_page($filter,$sqlstrs);
    foreach($result['result'] as &$val)
    {
        $val['exchangetime'] = date('Y-m-d H:i',$val['exchangetime']);
        $val['service_time'] = date('Y-m-d H:i',$val['service_time']);
        $val['add_time'] = date('Y-m-d H:i',$val['add_time']);
    }
    $result['filter']['act'] = 'asked_history';

    $smarty->assign('customer',$result['result']);
    $smarty->assign('type',3);
    $smarty->assign('admin',$admin);
    $smarty->assign('filter',$result['filter']);
    $res = $smarty->fetch('ex_history.htm');

    die($json->encode($res));
}

/* 顾客流向记录 */
elseif ($_REQUEST['act'] == 'exchange_his')
{
    admin_priv('exchange_his');   
    $admin = array('role_id' => $_SESSION['role_id'],'admin_id'=> $_SESSION['admin_id'],'panel'=>4,'title'=>'顾客流向');
    $filter = array();
    $filter['page'] = intval($_REQUEST['page']);
    $filter['page_size'] = intval($_REQUEST['page_size']);
    if($admin['role_id'] == '')
    {
        $smarty->assign('role',get_role());
    }

    $sqlstrs['count'] = 'SELECT count(*) AS count FROM '.$GLOBALS['ecs']->table('exchange_his').
        ' AS e LEFT JOIN'.$GLOBALS['ecs']->table('role').
        ' AS r ON e.role_id=r.role_id';
    $sqlstrs['select'] = 'SELECT e.*,r.role_name FROM '.$GLOBALS['ecs']->table('exchange_his').
        ' AS e LEFT JOIN'.$GLOBALS['ecs']->table('role').
        ' AS r ON e.role_id=r.role_id';

    $result = filter_page($filter,$sqlstrs);
    foreach($result['result'] as &$val)
    {
        $val['exchangetime'] = date('Y-m-d H:i',$val['exchangetime']);
        $val['service_time'] = date('Y-m-d H:i',$val['service_time']);
        $val['add_time'] = date('Y-m-d H:i',$val['add_time']);
    }
    $result['filter']['act'] = 'exchange_his';

    $smarty->assign('customer',$result['result']);
    $smarty->assign('type',4);
    $smarty->assign('admin',$admin);
    $smarty->assign('filter',$result['filter']);
    $res = $smarty->fetch('ex_history.htm');

    die($json->encode($res));
}

/*  禁止被淘顾客列表 */
elseif ($_REQUEST['act'] == 'ban_ask_list')
{
    if(admin_priv('all','',false))
    {
        $admin = array('role_id' => $_SESSION['role_id'],'admin_id'=> $_SESSION['admin_id'],'panel'=>5);

        //分页参数
        $filter = array();
        $filter['page'] = intval($_REQUEST['page']);                      //当前页
        $filter['page_size'] = intval($_REQUEST['page_size']);            //每页条数

        if($admin['role_id'] == '')
        {
            $smarty->assign('role',get_role());
        }

        $result = ask_customer('ban_ask_view',$filter);

        $banask = array('customer'=>$result['result'],'title'=>'禁止被淘顾客','type'=>2,'admin'=>$admin);
        $result['filter']['act'] = 'ban_ask_list';

        $smarty->assign('customer',$banask);
        $smarty->assign('admin',$admin);
        $smarty->assign('filter',$result['filter']);
        $res = $smarty->fetch('ask_content.htm');

        die($json->encode($res));
    }
}

/* 淘顾客相关操作 */
elseif ($_REQUEST['act'] == 'control_ask')
{
    if(admin_priv('all','',false))
    {
        $user_id = intval($_REQUEST['user_id']);
        $action = mysql_real_escape_string($_REQUEST['action']);        //执行的操作
        $admin_id = $_SESSION['admin_id'];
        $admin_name = $_SESSION['admin_name'];

        switch($action) 
        {
        case 'askCustomer' :       //淘顾客
            $count = 0;
            $sqls = 'INSERT INTO '.$GLOBALS['ecs']->table('asked_customer').
                '(user_id,user_name,last_service,last_purchase,admin_name,admin_id,plateform,role_id)'.
                ' SELECT * FROM '.$GLOBALS['ecs']->table('ask_customer_list_view')." WHERE user_id=$user_id".
                ';UPDATE '.$GLOBALS['ecs']->table('asked_customer')." SET ask_id=$admin_id,ask_name='$admin_name',last_asked=UNIX_TIMESTAMP(NOW()) WHERE user_id=$user_id".
                ';INSERT INTO '.$GLOBALS['ecs']->table('exchange_his').
                '(user_id,user_name,service_time,add_time,role_id,from_admin_id,from_admin_name)'.
                ' SELECT user_id,user_name,service_time,add_time,role_id,admin_id,admin_name FROM '.
                $GLOBALS['ecs']->table('ask_customer_list_view')." WHERE user_id=$user_id".
                ';UPDATE '.$GLOBALS['ecs']->table('exchange_his').
                " SET to_admin_id=$admin_id,to_admin_name='$admin_name',exchangetime=UNIX_TIMESTAMP(NOW()) WHERE user_id=$user_id".
                ";UPDATE ".$GLOBALS['ecs']->table('users')." SET admin_id=$admin_id,admin_name='$admin_name',asked=1 WHERE user_id=$user_id";

            $arrsql = explode(';',$sqls);   //执行多条SQL

            foreach($arrsql as $val)
            {
                if(trim($val) != '')     
                {
                    if($GLOBALS['db']->query($val)) $count++;
                }
            }
            if($count == 5)
            {
                $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users')." SET asked=1 WHERE user_id=$user_id";
            }
            $msg_su = '淘客成功';
            $msg_fa = '淘客失败';
            break;
        case 'cancelAsk' :         //放弃顾客
            $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users')." SET asked=0,admin_id=$admin_id,admin_name='$admin_name ' WHERE user_id=$user_id";
            if($GLOBALS['db']->query($sql_update))
            {
                $sql_update = 'DELETE FROM '.$GLOBALS['ecs']->table('asked_customer')." WHERE user_id=$user_id";
            }
            $msg_su = '已放弃';
            $msg_fa = '禁止失败';
            break;
        case 'banAsk' :            //禁止淘顾客
            $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users')." SET asked=2 WHERE user_id=$user_id";
            $msg_su = '禁止成功';
            $msg_fa = '禁止失败';
            break;
        case 'replaceAsk' :        //还原顾客
            $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users')." SET asked=0 WHERE user_id=$user_id";
            $msg_su = '已经恢复';
            $msg_fa = '恢复失败';
            break;
        default :
            break;
        }
        $result = $GLOBALS['db']->query($sql_update);
        if($result)
        {
            $res['user_id'] = $user_id;
            $res['msg'] = array('req_msg'=>true,'timeout'=>2000,'message'=>$msg_su,'user_id'=>$user_id);
        }
        else
        {
            $res['msg'] = array('req_msg'=>true,'timeout'=>2000,'message'=>$msg_fa);
        }
        die($json->encode($res));
    }
    else
    {
        $res['msg'] = array('timeout'=>2000,'message'=>'没有权限','req_msg'=>true);
        die($json->encode($res));
    }
}

//通过部门筛选可淘顾客
elseif ($_REQUEST['act'] == 'role_customer')
{
    $title = intval($_REQUEST['title']);
    $role_id = intval($_REQUEST['role_id']);

    $contion = "&title=$title&role_id=$role_id";
    $filter['role_d'] = $_SESSION['role_id'];
    if($filter['role_id'] == '')
    {
        $smarty->assign('role',get_role());
    }

    $filter = array();
    $filter['page'] = $_REQUEST['page'];
    $filter['page_size'] = $_REUQEST['page_size'];

    //记录数
    $sql_count = 'SELECT count(*) AS count FROM '.$ecs->table('users')
        .' AS u LEFT JOIN '.$ecs->table('service')
        .' AS s ON u.user_id = s.user_id '
        .' LEFT JOIN '.$ecs->table('order_info')
        .' AS o ON u.user_id = o.user_id LEFT JOIN '.$ecs->table('role')
        .' AS r ON u.role_id = r.role_id '
        ." WHERE
        (
            TO_DAYS( NOW( ) ) - TO_DAYS( FROM_UNIXTIME( u.service_time ) ) >=30
            OR TO_DAYS( NOW( ) ) - TO_DAYS( FROM_UNIXTIME( o.add_time ) ) >=30
        )";

    //查询
    $sql_ban_askable= 'SELECT DISTINCT u.user_id, u.user_name, u.service_time, o.add_time,o.admin_name,o.admin_id,r.role_name,u.role_id FROM '.$ecs->table('users')
        .' AS u LEFT JOIN '.$ecs->table('service')
        .' AS s ON u.user_id = s.user_id '
        .' LEFT JOIN '.$ecs->table('order_info')
        .' AS o ON u.user_id = o.user_id LEFT JOIN '.$ecs->table('role')
        .' AS r ON u.role_id = r.role_id '
        ." WHERE
        (
            TO_DAYS( NOW( ) ) - TO_DAYS( FROM_UNIXTIME( u.service_time ) ) >=30
            OR TO_DAYS( NOW( ) ) - TO_DAYS( FROM_UNIXTIME( o.add_time ) ) >=30
        )";

    if($title == 0)
    {
        $type = 0;
        $title = '可淘顾客';
        $sqlstrs['select'] = $sql_ban_askable." AND u.asked=0 AND u.role_id=$role_id GROUP BY u.user_id ORDER BY  u.service_time DESC ,o.add_time DESC"; 
        $sqlstrs['count'] = $sql_count." AND u.asked=0 AND u.role_id=$role_id GROUP BY u.user_id ORDER BY  u.service_time DESC ,o.add_time DESC"; 
    }
    elseif($title == 1)
    {
        $type = 1;
        $title = '已淘顾客';
        $sqlstrs['select']= 'SELECT * FROM '.$GLOBALS['ecs']->table('asked_customer')." WHERE role_id=$role_id";
        $sqlstrs['count']= 'SELECT count(*) AS count FROM '.$GLOBALS['ecs']->table('asked_customer')." WHERE role_id=$role_id";
    }
    elseif($title == 2)
    {
        $type = 2;
        $title = '禁止淘顾客';
        $sqlstrs['select'] = $sql_ban_askable." AND u.asked=2 AND u.role_id=$role_id GROUP BY u.user_id ORDER BY  u.service_time DESC ,o.add_time DESC"; 
        $sqlstrs['count'] = $sql_count." AND u.asked=2 AND u.role_id=$role_id GROUP BY u.user_id ORDER BY  u.service_time DESC ,o.add_time DESC"; 
    }

    $result = filter_page($filter,$sqlstrs);
    $result['filter']['act'] = 'role_customer'.$condition;

    $admin = array('role_id' => $_SESSION['role_id'],'admin_id'=> $_SESSION['admin_id']);
    $ask_customer = array('customer'=>$result['result'],'title'=>$title,'type'=>$type,'admin'=>$admin,'role_id'=>$role_id);

    $smarty->assign('customer',$ask_customer);
    $smarty->assign('admin',$admin);
    $smarty->assign('filter',$result['filter']);
    $res = $smarty->fetch('ask_content.htm');

    die($json->encode($res));
}

//陶顾客基本设置
elseif ($_REQUEST['act'] == 'ask_config')
{
    $ser_time = intval($_REQUEST['ser_time'])*30;      //距上次服务时间间隔
    $pur_time = intval($_REQUEST['pur_time'])*30;      //距上次购买时间间隔

    /*$sql_create = 'ALTER VIEW '.$GLOBALS['ecs']->table('ask_customer_list_view').
        ' AS SELECT DISTINCT u.user_id, u.user_name, u.service_time, o.add_time,o.admin_name,o.admin_id,r.role_name,u.role_id
          FROM crm_users AS u
          LEFT JOIN crm_service AS s ON u.user_id = s.user_id
          LEFT JOIN crm_order_info AS o ON u.user_id = o.user_id
          LEFT JOIN crm_role AS r ON u.role_id = r.role_id
          WHERE
          (
          TO_DAYS( NOW( ) ) - TO_DAYS( FROM_UNIXTIME( u.service_time ) ) >='.$ser_time.
          ' OR TO_DAYS( NOW( ) ) - TO_DAYS( FROM_UNIXTIME( o.add_time ) ) >='.$pur_time.
          ')
          AND u.asked=0
          GROUP BY u.user_id
          ORDER BY  u.service_time DESC ,o.add_time DESC';
     */
    $sql_update = 'UPDATE '.$ecs->table('askconfig')." SET service_time=$ser_time,purchase_time=$pur_time,modify_time=UNIX_TIMESTAMP(NOW()),modify_admin=".$_SESSION['admin_id'];

    $result = $GLOBALS['db']->query($sql_update);
    $res['req_message'] = true;
    $res['timeout'] = 2000;

    if($result)
    {
        $res['message'] = '设置成功';
    }
    else
    {
        $res['message'] = '设置失败';
    }

    die($json->encode($res));
}

//会员等级列表
elseif ($_REQUEST['act'] == 'vip_list')
{
    if(admin_priv('vip_list','',false))
    {
        $sort = mysql_real_escape_string($_REQUEST['sort']);
        $sort_type = mysql_real_escape_string($_REQUEST['sort_type']);
        $sort_type = empty($sort_type) ? 'ASC' : $sort_type;
        $vip_sort = 'role_id';
        $role_sort = 'mum_total';
        if($sort == 'vip_total' || $sort == 'role_id')
        {
            $vip_sort = $sort.' '.$sort_type;
        }
        elseif($sort == 'mum_total')
        {
            $role_sort = $sort.' '.$sort_type;
        }
        $sort_type = $sort_type == 'DESC' ? 'ASC' : 'DESC';

        $sql_select = 'SELECT r.*,NOW() AS update_time,p.role_name,(SELECT COUNT(*) FROM '.
            $GLOBALS['ecs']->table('users').' u WHERE r.rank_id=u.user_rank'.
            ') as vip_total FROM '.
            $GLOBALS['ecs']->table('user_rank').' r'.
            ' LEFT JOIN '.$GLOBALS['ecs']->table('role').
            ' p ON p.role_id=r.role_id '.
            ' ORDER BY '.$vip_sort.',r.rank_id ASC ';

        $rank = $GLOBALS['db']->getAll($sql_select);

        $sql_select = 'SELECT r.*,(SELECT COUNT(*) FROM '.
            $GLOBALS['ecs']->table('users').
            ' u WHERE r.role_id=u.role_id'.
            ' )AS mum_total FROM '.$GLOBALS['ecs']->table('role').' r'.
            ' WHERE role_id IN(1,2,6,7,9,10,12)'.
            ' ORDER BY '.$role_sort;

        $role_list = $GLOBALS['db']->getAll($sql_select);

        $smarty->assign('sort_type',$sort_type);
        $smarty->assign('role_list',$role_list);
        $smarty->assign('rank_list',$rank);
        $res['main'] = $smarty->fetch('vip_list.htm');

        die($json->encode($res));
    }
}

//顾客高级转移搜索
elseif ($_REQUEST['act'] == 'advance_tansfer')
{
   if(admin_priv('advane_tansfer')) 
   {
      $from_admin = intval($_REQUEST['from_admin']); 
      $to_admin = intval($_REQUEST['to_admin']); 
      $user_active = intval($_REQUEST['user_active']);
      $user_class = intval($_REQUEST['user_class']);
      $ser_startTime = strtotime($_REQUEST['ser_startTime']);
      $ser_endTime = strtotime($_REQUEST['ser_endTime']);
      $add_startTime = strtotime($_REQUEST['add_startTime']);
      $add_addTime = strtotime($_REQUEST['add_endTime']);
   }
}

/* 函数区 */
//陶顾客列表函数
function ask_customer($table,$filter)
{
    if(!$filter['role_id'])
    {
        $sqlstrs['count'] = 'SELECT count(*) AS count FROM '.$GLOBALS['ecs']->table($table);
        $sqlstrs['select'] = 'SELECT * FROM '.$GLOBALS['ecs']->table($table);
    }
    else
    {
        $sqlstrs['count'] = 'SELECT count(*) AS count FROM '.$GLOBALS['ecs']->table($table).' WHERE role_id='.$filter['role_id'];
        $sqlstrs['select'] = 'SELECT * FROM '.$GLOBALS['ecs']->table($table).' WHERE role_id='.$filter['role_id'];
    }

    $result = filter_page($filter,$sqlstrs);

    foreach($result['result'] as &$val)
    {
        $val['add_time'] = date('Y-m-d H:i',$val['add_time']);
        if($val['service_time'])
        {
            $val['service_time'] = date('Y-m-d H:i',$val['servcie_time']);
        }
    }

    return $result;
}

/**
 *  返回用户列表数据
 *
 * @access  public
 * @param
 *
 * @return void
 */
function user_list()
{

    $result = get_filter();
    if ($result === false)
    {
        /* 过滤条件 */
        //$filter['user_name']    = empty($_REQUEST['user_name'])    ? '' : trim($_REQUEST['user_name']);
        $filter['admin_id']     = empty($_REQUEST['admin_id'])     ? '' : intval($_REQUEST['admin_id']);
        $filter['address']      = empty($_REQUEST['address'])      ? '' : trim($_REQUEST['address']);
        $filter['zipcode']      = empty($_REQUEST['zipcode'])      ? '' : trim($_REQUEST['zipcode']);
        $filter['home_phone']   = empty($_REQUEST['tel'])          ? '' : trim($_REQUEST['tel']);
        $filter['mobile_phone'] = empty($_REQUEST['mobile'])       ? 0  : intval($_REQUEST['mobile']);
        $filter['country']      = empty($_REQUEST['country'])      ? 0  : intval($_REQUEST['country']);
        $filter['province']     = empty($_REQUEST['province'])     ? 0  : intval($_REQUEST['province']);
        $filter['city']         = empty($_REQUEST['city'])         ? 0  : intval($_REQUEST['city']);
        $filter['district']     = empty($_REQUEST['district'])     ? 0  : intval($_REQUEST['district']);
        $filter['platform']     = empty($_REQUEST['platform'])     ? 0  : intval($_REQUEST['platform']);
          $filter['from_where']   = !intval($_REQUEST['from_where']) ? 0  : intval($_REQUEST['from_where']);
          $filter['type']         = empty($_REQUEST['type'])         ? 0  : urldecode($_REQUEST['type']);
          $filter['eff_id']       = empty($_REQUEST['eff_id'])       ? 0  : intval($_REQUEST['eff_id']);
          $filter['start_time']   = empty($_REQUEST['start_time'])   ? 0  : $_REQUEST['start_time'];
          $filter['end_time']     = empty($_REQUEST['end_time'])     ? 0  : $_REQUEST['end_time'];
          $filter['purchase']     = empty($_REQUEST['purchase'])     ? 0  : trim($_REQUEST['purchase']);
          $filter['district']     = empty($_REQUEST['district'])     ? 0  : intval($_REQUEST['district']);
          $filter['city']         = empty($_REQUEST['city'])         ? 0  : $_REQUEST['city'];
          $filter['province']     = empty($_REQUEST['province'])     ? 0  : $_REQUEST['province'];
          $filter['address']      = empty($_REQUEST['address'])      ? 0  : trim($_REQUEST['address']);
          $filter['cat_tag']      = empty($_REQUEST['cat_tag'])      ? 0  : intval($_REQUEST['cat_tag']);

          $filter['number_purchased'] = empty($_REQUEST['number_purchased'])?0:intval($_REQUEST['number_purchased']);

          $filter['start_time'] = strtotime(stamp2date($_REQUEST['start_time'], 'Y-m-d H:i:s'));
          $filter['end_time']   = strtotime(stamp2date($_REQUEST['end_time'], 'Y-m-d H:i:s'));

          if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
          {
               $filter['keywords'] = json_str_iconv($filter['keywords']);
          }

          $ex_where = ' WHERE 1 ';

          // 顾客搜索
          if (! empty($_REQUEST['keywords']))
          {
               $filter['keyfields'] = mysql_real_escape_string(trim($_REQUEST['keyfields']));
               $filter['keywords']  = mysql_real_escape_string(trim(urldecode($_REQUEST['keywords'])));

               $ex_where .= " AND {$filter['keyfields']} LIKE '%{$filter['keywords']}%' ";
          }

          foreach ($filter as $key=>$val)
          {
               if (!empty($val))
               {
                    if ($key == 'type')
                    {
                         $condition .= "&$key=".urlencode($val);
                         continue;
                    }

                    $condition .= "&$key=$val";
               }
          }

          if ($filter['purchase'])
          {
               $ex_where .= " AND purchase='{$filter['purchase']}' ";
          }

          if ($filter['number_purchased'])
          {
               $ex_where .= " AND number_purchased>={$filter['number_purchased']} ";
          }

          /* 按顾客来源显示 顾客列表 */
          if ($filter['from_where'])
          {
               $ex_where .= " AND u.from_where='{$filter['from_where']}' ";
          }

          $sql = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('users').' u ';

          if (admin_priv('all', '', false))
          {
               $ex_where .= ' AND u.admin_id>0';
          }
          elseif (admin_priv('role_users', '', false))
          {
               $ex_where .= " AND u.role_id={$_SESSION['role_id']}";
          }
          // 客服
          elseif ($filter['admin_id'])
          {
               if (admin_priv('all', '',false))
               {
                    $ex_where .= " AND u.admin_id={$filter['admin_id']} ";
               }
               elseif (admin_priv('section', '', false))
               {
                    $sql_select_admin = 'SELECT user_id FROM '.$GLOBALS['ecs']->table('admin_user').
                         " WHERE user_id={$filter['admin_id']} AND role_id={$_SESSION['role_id']}";
                    $admin_id = $GLOBALS['db']->getOne($sql_select_admin);

                    if ($admin_id)
                    {
                         $ex_where .= " AND u.admin_id={$filter['admin_id']} ";
                    }
               }
          }
          else 
          {
               $ex_where .= " AND u.admin_id={$_SESSION['admin_id']}";
          }

          if ($filter['district'])
          {
               $sql .= ', '.$GLOBALS['ecs']->table('region').' r, '.$GLOBALS['ecs']->table('user_address').' a ';
               $ex_where .= " AND u.user_id=a.user_id AND a.district=r.region_id AND a.district=$filter[district]";
          }

          //判断所属省，市的顾客
          elseif ($filter['city'])
          {
               $sql .= ', '.$GLOBALS['ecs']->table('region').' r, '.$GLOBALS['ecs']->table('user_address').' a ';
               $ex_where .= " AND u.user_id=a.user_id AND a.city=r.region_id AND a.city=$filter[city]";
          }

          //判断所属省份的顾客
          elseif ($filter['province'])
          {
               $sql .= ', '.$GLOBALS['ecs']->table('region').' r, '.$GLOBALS['ecs']->table('user_address').' a ';
               $ex_where .= " AND u.user_id=a.user_id AND a.province=r.region_id AND a.province=$filter[province]";
          }

          if ($filter['saddress'])
          {
               $sql .= ', '.$GLOBALS['ecs']->table('user_address').' a ';
               $ex_where .= " AND a.address LIKE '%$filter[saddress]%' AND a.user_id=u.user_id";
          }

          // 自定义分类
          if ($filter['cat_tag'])
          {
               $ex_where .= " AND user_cat='{$_SESSION['admin_id']}-{$filter['cat_tag']}' ";
          }
          elseif ($_REQUEST['act'] == 'user_cat_list')
          {
               $ex_where .= " AND user_cat='' ";
          }

          if ($filter['start_time'] && $filter['end_time'])
          {
               if ($filter['start_time'] > $filter['end_time'])
               {
                    $time_tmp = $filter['end_time'];
                    $filter['end_time'] = $filter['start_time'];
                    $filter['start_time'] = $time_tmp;
               }

               $ex_where .= " AND u.add_time BETWEEN '{$filter['start_time']}' AND '{$filter['end_time']}'";
          }

          if($filter['type'])
          {
               $ex_where .= " AND u.customer_type IN ({$filter['type']}) ";
          }

          // 功效分类
          if ($filter['eff_id'] && $filter['eff_id'] > 0){
               $ex_where .= " AND u.eff_id={$filter['eff_id']}";
          }
          elseif ($filter['eff_id'] && $filter['eff_id'] < 0){
               $ex_where .= ' AND u.eff_id=0 ';
          }

          $sql .= $ex_where;

          $filter['record_count'] = $GLOBALS['db']->getOne($sql);

          /* 分页大小 */
          $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page'])<=0) ? 1 : intval($_REQUEST['page']);
          if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
          {
               $filter['page_size'] = intval($_REQUEST['page_size']);
          }
          else
          {
               $filter['page_size'] = 20; 
          }

          $filter['page_count'] = $filter['record_count']>0 ? ceil($filter['record_count']/$filter['page_size']) : 1;

          // 设置分页
          $page_set = array (1,2,3,4,5,6,7);
          if ($filter['page'] > 4)
          {
               foreach ($page_set as &$val)
               {
                    $val += $filter['page'] -4;
               }
          }

          if (end($page_set) > $filter['page_count'])
          {
               $page_set = array ();
               for ($i = 7; $i >= 0; $i--)
               {
                    if ($filter['page_count'] - $i > 0)
                    {
                         $page_set[] = $filter['page_count'] - $i;
                    }
               }
          }

          $sql = 'SELECT u.qq,u.aliww,u.dm,u.number_purchased,u.age_group,u.admin_name,u.user_id,e.eff_name,'.
               'u.user_name,u.sex,IF(u.birthday="2012-01-01",u.age_group,(YEAR(NOW())-YEAR(u.birthday))) birthday,'.
               'u.home_phone,u.mobile_phone,u.is_validated,u.user_money,u.add_time,u.remarks,u.service_time,'.
               'u.transfer_time FROM '.$GLOBALS['ecs']->table('users').' u,'.$GLOBALS['ecs']->table('effects').' e ';

          //判断一个月内转移的顾客
          $_REQUEST['transfer_time'] && $filter['transfer_time'] = $_REQUEST['transfer_time'];  
          if($filter['transfer_time'])
          {
               $ex_where .= ' AND u.transfer_time>'.$filter['transfer_time'];
          }    

          if ($filter['district'])
          {
               $sql .= ', '. $GLOBALS['ecs']->table('region').' r , '. $GLOBALS['ecs']->table('user_address').' a ';
               $ex_where .= " AND u.user_id=a.user_id AND a.district=r.region_id AND a.district=$filter[district]";
          }

          //判断所属省，市的顾客
          elseif ($filter['city'])
          {
               $sql .= ', '.$GLOBALS['ecs']->table('region').' r, '.$GLOBALS['ecs']->table('user_address').' a ';
               $ex_where .= " AND u.user_id=a.user_id AND a.city=r.region_id AND a.city=$filter[city]";
          }

          //判断所属省份的顾客
          elseif ($filter['province'])
          {
               $sql .= ', '.$GLOBALS['ecs']->table('region').' r, '.$GLOBALS['ecs']->table('user_address').' a ';
               $ex_where .= " AND u.user_id=a.user_id AND a.province=r.region_id AND a.province=$filter[province]";
          }

          $ex_where .= ' AND u.eff_id=e.eff_id ORDER by service_time ASC LIMIT '.
               ($filter['page'] -1)*$filter['page_size'].', '.$filter['page_size'];
          $sql .= $ex_where;

          $filter['keywords'] = stripslashes($filter['keywords']);
          set_filter($filter, $sql);
     }
     else
     {
          $sql    = $result['sql'];
          $filter = $result['filter'];
     }

     $user_list = $GLOBALS['db']->getAll($sql);

     foreach ($user_list as &$val)
     {
          $val['add_time']      = date('Y-m-d', $val['add_time']);
          $val['transfer_time'] = $val['transfer_time'] ? date('Y-m-d', $val['transfer_time']) : '-';
          $val['service_time']  = date('Y-m-d', $val['service_time']);
     }

     $arr = array(
          'user_list'    => $user_list,
          'filter'       => $filter,
          'page_count'   => $filter['page_count'],
          'record_count' => $filter['record_count'],
          'page_size'    => $filter['page_size'],
          'page'         => $filter['page'],
          'page_set'     => $page_set,
          'condition'    => $condition,
          'start'        => ($filter['page'] - 1)*$filter['page_size'] +1,
          'end'          => $filter['page']*$filter['page_size'],
     );

     return $arr;
}

/**
 * 获取会员部客服信息
 * return   Array  客服ID、客服姓名
 */
function getMemAdmin()
{
     $sql = 'SELECT user_id, user_name FROM '.$GLOBALS['ecs']->table('admin_user')
          .' WHERE role_id=9';
     return $GLOBALS['db']->getAll($sql);
}

/**
 * 获取功效分类信息
 * return  Array  人群分类/分类ID
 */
function getEffectTypes()
{
     $sql = 'SELECT eff_id, eff_name FROM '.$GLOBALS['ecs']->table('effects').
          ' WHERE available=1 ORDER BY sort ASC ';
     return $GLOBALS['db']->getAll($sql);
}

/**
 * 更新用户社会关系信息
 */
function updateSocial()
{
     foreach ($_POST['uname'] as $key=>$val)
     {
          if (empty($val))
          {
               continue;
          }

          $sql = 'SELECT rela_id FROM '.$GLOBALS['ecs']->table('user_relation')." WHERE rela_id=$key AND user_id={$_POST['user_id']}";
          $rela = $GLOBALS['db']->getOne($sql);
          if ($rela)
          {
               $update_tmp = array (
                    'uname="'.mysql_real_escape_string($val).'"',
                    'mobile="'.mysql_real_escape_string(trim($_POST['mobile'][$key])).'"',
                    'relation="'.mysql_real_escape_string(trim($_POST['relation'][$key])).'"',
                    'habitancy="'.mysql_real_escape_string(trim($_POST['habitancy'][$key])).'"',
                    'age="'.intval($_POST['age'][$key]).'"',
                    'rela_sex="'.intval($_POST['relasex'][$key]).'"',
                    'profession="'.mysql_real_escape_string(trim($_POST['profession'][$key])).'"',
                    'financial="'.mysql_real_escape_string(trim($_POST['financial'][$key])).'"',
                    'selfcare="'.mysql_real_escape_string(trim($_POST['selfcare'][$key])).'"',
               );
               $sql = 'UPDATE '.$GLOBALS['ecs']->table('user_relation').' SET '.implode(',', $update_tmp)." WHERE rela_id='$key' AND user_id='{$_POST['user_id']}'";
               unset($_POST['uname'][$key]);
          }
          else 
          {
               $newRela = 1;
          }

          $GLOBALS['db']->query($sql);
     }

     $newRela == 1 && insertSocial();
}

/**
 * 插入顾客社会关系
 */
function insertSocial ()
{
     // 添加用户社会关系信息
     $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('user_relation').'(rela_id, user_id, uname, rela_sex, mobile, relation, habitancy, age, add_age_year, profession, financial, selfcare, rela_user_id)VALUES';
     foreach ($_POST['uname'] as $key=>$val)
     {
          // 如果没用姓名 则跳过该条记录
          if (empty($val))
          {
               continue;
          }

          $sql_temp = array (
               'rela_id'      => $key,
               'user_id'      => $user_id ? $user_id : $_POST['user_id'], // 顾客关联ID
               'uname'        => trim($val),                         // 姓名
               'rela_sex'     => intval($_POST['relasex'][$key]),    // 性别
               'mobile'       => mysql_real_escape_string(trim($_POST['mobile'][$key])),       // 联系电话
               'relation'     => mysql_real_escape_string(trim($_POST['relation'][$key])),     // 社会关系
               'habitancy'    => mysql_real_escape_string(trim($_POST['habitancy'][$key])),  // 居住情况
               'age'          => intval($_POST['age'][$key]),        // 年龄
               'add_age_year' => date('Y', time()),            // 添加年份，用于计算当前年龄
               'profession'   => mysql_real_escape_string(trim($_POST['profession'][$key])), // 职业
               'financial'    => intval($_POST['financial'][$key]),  // 经济状况
               'selfcare'     => intval($_POST['selfcare'][$key]),   // 保健意识
               'rela_user_id' => ''
          );

          // 查询该号码的主人是否已成为顾客
          $sql_temp['rela_user_id'] = $GLOBALS['db']->getOne('SELECT user_id FROM '.$GLOBALS['ecs']->table('users')." WHERE home_phone='{$sql_temp['mobile']}' OR mobile_phone='{$sql_temp['mobile']}'");

          $sql_array[] = '("'.implode('","', array_map('mysql_real_escape_string', $sql_temp)).'")';
     }

     $sql .= implode(',', $sql_array);
     $GLOBALS['db']->query($sql);
}

/**
 * 获取地址信息
 * @param   $id   int   地区ID
 */
function get_address ($id)
{
     $sql = 'SELECT region_name FROM '.$GLOBALS['ecs']->table('region').
          " WHERE region_id=$id";
     return $GLOBALS['db']->getOne($sql);
}

/**
 * Details of user
 * @param   $id  int   User's ID
 */
function get_user_info ($id)
{
     // get the details  of user
     $sql_select = 'SELECT user_name,sex,birthday,mobile_phone,home_phone,characters,service_time,member_cid,'.
          'aliww,number_purchased,habby,email,disease,disease_2,from_where,user_id,add_time,id_card,eff_id,qq,'.
          't.type_name customer_type,remarks FROM '.
          $GLOBALS['ecs']->table('users').' u, '.
          $GLOBALS['ecs']->table('customer_type').' t'.
          " WHERE u.customer_type=t.type_id AND u.user_id=$id";
     $user_info = $GLOBALS['db']->getRow($sql_select);

     $sql_select = "SELECT r.rank_name,u.rank_points,u.user_rank FROM ".
         $GLOBALS['ecs']->table('user_rank').' r,'.
         $GLOBALS['ecs']->table('users').' u '.
         " WHERE u.user_rank=r.rank_id AND u.user_id=$id";
     $user_rank = array();
     $user_rank = $GLOBALS['db']->getRow($sql_select);
     $user_info = array_merge($user_info,$user_rank);

     // 获取顾客地址
     $sql_select = 'SELECT p.region_name province,c.region_name city,d.region_name district,'.
          'ua.address,ua.province province_id,ua.city city_id,ua.district district_id FROM '.
          $GLOBALS['ecs']->table('user_address').' ua LEFT JOIN '.$GLOBALS['ecs']->table('region').
          ' p ON p.region_id=ua.province LEFT JOIN '.$GLOBALS['ecs']->table('region').
          ' c ON c.region_id=ua.city LEFT JOIN '.$GLOBALS['ecs']->table('region').
          ' d ON d.region_id=ua.district'." WHERE ua.user_id=$id";
     $user_region = $GLOBALS['db']->getAll($sql_select);

     if (is_array($user_region[0]))
     {
          $user_info = array_merge($user_info, $user_region[0]);
     }

     $sql_select = 'SELECT r.role_name platform FROM '.$GLOBALS['ecs']->table('role').
          ' r, '.$GLOBALS['ecs']->table('users')." u WHERE u.role_id=r.role_id AND u.user_id=$id";
     $user_info['platform'] = $GLOBALS['db']->getOne($sql_select);

     // format time
     $user_info['add_time']     = date('Y-m-d H:i', $user_info['add_time']);     // 添加时间
     $user_info['service_time'] = date('Y-m-d H:i', $user_info['service_time']); // 上次服务时间
     @$user_info['birthday']    = date('Y-m-d', $user_info['birthday']);

     $user_info['disease']    = explode(':', $user_info['disease']);    // 疾病
     $user_info['characters'] = explode(':', $user_info['characters']); // 性格

     // 获取顾客需求
     $sql_select = 'SELECT eff_name FROM '.$GLOBALS['ecs']->table('effects').
          " WHERE eff_id='{$user_info['eff_id']}'";
     $user_info['eff_name'] = $GLOBALS['db']->getOne($sql_select);

     // 获取顾客来源
     $sql_select = 'SELECT `from` FROM '.$GLOBALS['ecs']->table('from_where').
          " WHERE from_id='{$user_info['from_where']}'";
     $user_info['from_where'] = $GLOBALS['db']->getOne($sql_select);

     // 获取顾客经济来源
     $sql_select = 'SELECT income FROM '.$GLOBALS['ecs']->table('income').
          " WHERE income_id='{$user_info['income']}'";
     $user_info['income'] = $GLOBALS['db']->getOne($sql_select);

     return $user_info;
}

/**
 * Access to user purchase records
 * @param  $id    int   user_id
 */
function access_purchase_records ($id)
{
     // Get user to buy records 获取顾客购买记录
     $sql_select = 'SELECT o.order_id,o.order_sn,o.consignee,o.order_status,o.shipping_status,o.add_time,o.shipping_name,o.pay_name,o.final_amount,'.
          'o.tracking_sn express_number,a.user_name operator,o.receive_time,o.shipping_code FROM '.
          $GLOBALS['ecs']->table('order_info').' o,'.$GLOBALS['ecs']->table('admin_user').
          " a WHERE o.add_admin_id=a.user_id AND o.order_status=5 AND o.shipping_status IN (1,2) AND o.user_id=$id GROUP BY o.order_id";
     $order_list = $GLOBALS['db']->getAll($sql_select);

     // Format time
     foreach ($order_list as &$val)
     {
          $val['add_time']     = date('Y-m-d', $val['add_time']);   // Buy time
          $val['service_time'] = date('Y-m-d', $val['service_time']);
          $val['receive_time'] = $val['receive_time'] ? date('Y-m-d H:i', $val['receive_time']) : '-';// Receive time

          // 宅急送code处理
          $val['shipping_code'] = $val['shipping_code'] == 'sto_express' ? 'zjs' :$val['shipping_code'];
          $val['shipping_code'] = $val['shipping_code'] == 'sto_nopay' ? 'zjs' :$val['shipping_code'];

          $sql_select = 'SELECT goods_name,goods_number,goods_price,is_package,goods_sn FROM '.
               $GLOBALS['ecs']->table('order_goods')." WHERE order_id={$val['order_id']}";
          $val['goods_list'] = $GLOBALS['db']->getAll($sql_select);

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

          $temp_status = $val['order_status'].$val['shipping_status'];

          switch($temp_status)
          {
          case "52" : $val['finaly_order_status'] = '<font>已签收</font>'; $val['tr'] = 'bgcolor=""'; break;
          case "51" : $val['finaly_order_status'] = '<font color="#000">已发货</font>'; $val['tr'] = ''; break;
          case "54" : $val['finaly_order_status'] = '<font color="#000">已退货</font>';  $val['tr'] = 'style="background:#FFF7FB !important"';break;
          case "13" : $val['finaly_order_status'] = '<font color="#000">已取消</font>'; $val['tr'] = 'style="background:##F0F0F0 !important"'; break;
          case "10" : $val['finaly_order_status'] = '<font color="#000">待发货</font>'; $val['tr'] = 'style="background:#D1E9E9 !important"'; break;
          }
     }

     return $order_list;
}

/**
 * Get characters
 */
function get_characters ()
{
    $sql_select = 'SELECT character_id,characters FROM '.$GLOBALS['ecs']->table('character').
        ' WHERE available=1 ORDER BY sort ASC';
    $characters = $GLOBALS['db']->getAll($sql_select);

    return $characters;
}

/**
 * effects list
 */
function list_effects_common ()
{
    $sql_select = 'SELECT eff_id id,eff_name name FROM '.$GLOBALS['ecs']->table('effects').
        ' WHERE available=1 ORDER BY sort ASC';
    $effects = $GLOBALS['db']->getAll($sql_select);

    return $effects;
}

/**
 * platform list
 */
function list_role_common ()
{
    $sql_select = 'SELECT role_id id,role_name name FROM '.$GLOBALS['ecs']->table('role').
        ' WHERE role_type>0';
    $platform = $GLOBALS['db']->getAll($sql_select);

    return $platform;
}

/**
 * 获取顾客服务记录
 */
function get_user_services ($user_id)
{
    $sql_select = 'SELECT s.user_name,s.user_id,s.logbook,s.admin_name,s.service_time,c.class,m.manner FROM '.
        $GLOBALS['ecs']->table('service').' s,'.$GLOBALS['ecs']->table('service_class').' c,'.
        $GLOBALS['ecs']->table('service_manner').' m WHERE s.service_manner=m.manner_id AND '.
        " s.service_class=c.class_id AND s.user_id=$user_id ORDER BY s.service_time DESC";
    $res = $GLOBALS['db']->getAll($sql_select);

    foreach ($res as &$val)
    {
        $val['service_time'] = date('Y-m-d H:i', $val['service_time']);
    }

    return $res;
}

/**
 * 获取退货记录
 */
function get_return_list ($user_id)
{
    $sql_select = 'SELECT o.consignee,o.shipping_name,o.other_reason,o.mobile,o.tel,o.return_time FROM '.
        $GLOBALS['ecs']->table('back_order')." o WHERE user_id=$user_id";
    $res = $GLOBALS['db']->getAll($sql_select);

    foreach ($res as &$val)
    {
        $val['return_time'] = local_date('Y-m-d H:i:s', $val['return_time']);
    }

    return $res;
}

/**
 * 健康档案
 */
function get_healthy($user_id)
{
    $sql_select = 'SELECT COUNT(*) AS total,born_address,work_address,is_marry,regular_check,cycle_check,psychology,allergy,allergy_reason,family_case,before_case,other,tumour FROM '.$GLOBALS['ecs']->table('user_archive').
        " WHERE user_id=$user_id";
    $base_info = $GLOBALS['db']->getAll($sql_select);       //基本信息-过敏史-家庭病历-心理-其它
    $base_info = $base_info[0];

    $before_case = explode(' ',$base_info['before_case']);
    $base_info['before_case'] = $before_case;

     $family_case = explode(' ',$base_info['family_case']);
     $base_info['family_case'] = $family_case;

     $sql_select = 'SELECT COUNT(*) AS total,height,weight,BMI,waistline,hipline,WHR FROM '.$GLOBALS['ecs']->table('weight_condition').
          " WHERE user_id=$user_id";                          //体重情况
     $weight_condition = $GLOBALS['db']->getAll($sql_select);
     $weight_condition = $weight_condition[0];

     $sql_select = 'SELECT COUNT(*) AS total,work_type,work_time,travel_situation,enviroment,healthy_element,blood_type FROM '.$GLOBALS['ecs']->table('work_condition').
          " WHERE user_id=$user_id";                       //工作情况
     $work_condition = $GLOBALS['db']->query($sql_select);
     $work_condition = $work_condition[0];

     $sql_select = 'SELECT COUNT(*) AS total,food_taste,fixed_dinner,mealtime,sleep_habit,bedtime_start,sport_times,sport_time,sport_type,smoke,smoke_number,passive_smoke,drink,drink_type,drink_capacity,bedtime,sleep_quality,sport_period,smoke_age FROM '.$GLOBALS['ecs']->table(lifestyle)
          ." WHERE user_id=$user_id";                           //生活习惯
     $lifestyle = $GLOBALS['db']->getAll($sql_select); 
     $lifestyle = $lifestyle[0];

     $food_taste = explode(' ',$lifestyle['food_taste']);
     $lifestyle['food_taste'] = $food_taste;

     $sport_type = explode(' ',$lifestyle['sport_type']);
     $lifestyle['sport_type'] = $sport_type;

     $drink_type = explode(' ',$lifestyle['drink_type']);
     $lifestyle['drink_type'] = $drink_type;

     $healthy_file = array('baseInfo'=>$base_info,'weight_condition'=>$weight_condition,'work_condition'=>$work_condition,'lifestyle'=>$lifestyle);

     return $healthy_file;
}

/**
 * 病例 
 */
function get_before_case()
{
     $sql_select = 'SELECT s.sickness_id,s.disease,c.class_id FROM '.$GLOBALS['ecs']->table('sickness').
          ' AS s LEFT JOIN '.$GLOBALS['ecs']->table('sick_class').' AS c ON s.class=c.class_id WHERE s.availble=1';
     $before_case = $GLOBALS['db']->getAll($sql_select);
     $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('sick_class');
     $case_list = $GLOBALS['db']->getAll($sql_select);
     $result = array('before_case'=>$before_case,'case_list'=>$case_list);
     return $result;
}

/**
 * 增值服务列表
 */
function forecast_list ()
{
     $now_date    = strtotime(date('Y-m-d 00:00', time()));
     $now_time    = time();
     $future_days = $now_date +24*3600*3;

     $filter['admin_id'] = empty($_REQUEST['admin_id']) ? 0 : intval($_REQUEST['admin_id']);

     $sql_select = 'SELECT g.goods_name,g.goods_number,g.taking_time,i.receive_time+g.taking_time over_time,'.
          'i.order_id,i.mobile,i.tel,i.receive_time,u.service_time,u.sex,i.user_id,i.consignee,u.admin_name FROM '
          .$GLOBALS['ecs']->table('order_info').' i,'.$GLOBALS['ecs']->table('users').' u,'.
          $GLOBALS['ecs']->table('order_goods').' g WHERE i.user_id=u.user_id AND i.order_id=g.order_id AND '.
          "i.receive_time+g.taking_time BETWEEN $now_date AND $future_days AND u.service_time<$now_time";

     $sql_where = '';
     if(admin_priv('all','',false)){
     }
     else{
	     $sql_where .= " AND u.admin_id={$_SESSION['admin_id']}";
     }

     if($filter['admin_id'] && admin_priv('forecast_view','',false)){
	     $sql_where .= " AND u.admin_id={$filter['admin_id']}";
     }

     $forecast = $GLOBALS['db']->getAll($sql_select.$sql_where.' GROUP BY g.order_id ORDER BY over_time ASC');
     if (empty($forecast)){
	     return false;
     }

     foreach ($forecast as &$val){
	     $val['receive_time'] = date('Y-m-d', $val['receive_time']);
	     $val['over_time'] = date('Y-m-d', $val['over_time']);
     }

     $arr = array (
          'forecast_list' => $forecast,
          'filter'        => $filter,
          'page_count'    => $filter['page_count'],
          'record_count'  => $filter['record_count'],
          'page_size'     => $filter['page_size'],
          'page'          => $filter['page'],
          'page_set'      => $page_set,
          'condition'     => $condition,
          'start'         => ($filter['page'] - 1)*$filter['page_size'] +1,
          'end'           => $filter['page']*$filter['page_size'],
     );

     return $arr;
}

/**
 * 顾客自定义分类
 */
function user_cat_list ($available = false)
{
     $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('user_cat').
          " WHERE admin_id={$_SESSION['admin_id']}";
     if ($available){
          $sql_select .= ' AND available=1';
     }
     $sql_select .= ' ORDER BY sort DESC';

     $cat_list = $GLOBALS['db']->getAll($sql_select);
     return $cat_list;
}

/**
 * 顾客类型
 */
function list_customer_type ()
{
     $sql_select = 'SELECT type_id id, type_name name FROM '.$GLOBALS['ecs']->table('customer_type').
          ' WHERE available=1 ORDER BY sort ASC';
     $type_list = $GLOBALS['db']->getAll($sql_select);

     return $type_list;
}

/*
 * 积分日志
 * */
function get_integral_log($user_id)
{
    $sql_select = 'SELECT ui.*,u.user_name,r.rank_name,a.user_name as admin_name,i.integral_title,(ui.pre_points+ui.exchange_points) as cur_integral,o.goods_amount FROM '.$GLOBALS['ecs']->table('user_integral').
        ' AS ui LEFT JOIN '.$GLOBALS['ecs']->table('users').
        ' AS u ON ui.user_id=u.user_id LEFT JOIN '.$GLOBALS['ecs']->table('admin_user').
        ' AS a ON u.admin_id=a.user_id LEFT JOIN '.$GLOBALS['ecs']->table('integral').
        ' AS i ON ui.integral_id=i.integral_id LEFT JOIN '.$GLOBALS['ecs']->table('order_info').
        ' AS o ON ui.source_id=o.order_id LEFT JOIN '.$GLOBALS['ecs']->table('user_rank').
        " AS r ON u.user_rank=r.rank_id WHERE u.user_id=$user_id ORDER BY confirm_time,receive_time DESC";

    $result = $GLOBALS['db']->getAll($sql_select);
    foreach($result as &$val)
    {
        $val['receive_time'] = date('Y-m-d H:i',$val['receive_time']);
        $val['validity'] = date('Y-m-d H:i',$val['validity']);
    }

    return $result;
}
/**
 * 顾客来源
 */
function list_from_where()
{
     $now = time();
     $sql_select = 'SELECT `from` name,from_id id FROM '.$GLOBALS['ecs']->table('from_where').
          " WHERE available=1 AND enddate>=$now OR enddate=0 ORDER BY sort ASC";
     $list = $GLOBALS['db']->getAll($sql_select);

     return $list;
}

//重组会员资金变化记录
function lange_account(&$account_log)
{
    foreach($account_log as &$rows)
    {
        $rows['add_time']         = date('Y-m-d H:i:s',$rows['add_time']);
        $rows['change_time']      = date('Y-m-d H:i:s',$rows['add_time']);
        $rows['admin_note']       = nl2br(htmlspecialchars($rows['admin_note']));
        $rows['short_admin_note'] = ($rows['admin_note'] > '') ? sub_str($rows['admin_note'], 30) : 'N/A';
        $rows['user_note']        = nl2br(htmlspecialchars($rows['user_note']));
        $rows['short_user_note']  = ($rows['user_note'] > '') ? sub_str($rows['user_note'], 30) : 'N/A';
        $rows['pay_status']       = ($rows['is_paid'] == 0) ? $GLOBALS['_LANG']['un_confirm'] : $GLOBALS['_LANG']['is_confirm'];
        $rows['amount']           = price_format(abs($rows['amount']), false);
        $rows['user_money']       = price_format(abs($rows['user_money']), false);

        /* 会员的操作类型： 冲值，提现 */
        if ($rows['process_type'] == 0)
        {
            $rows['type'] = $GLOBALS['_LANG']['surplus_type_0'];
        }
        else
        {
            $rows['type'] = $GLOBALS['_LANG']['surplus_type_1'];
        }
    }
}

