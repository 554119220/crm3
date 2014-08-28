<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

/* 操作员 */
if ($_REQUEST['act'] == 'users_list')
{
     $res = array ();
     $file = basename($_SERVER['PHP_SELF'], '.php');
     $nav = list_nav();
     $smarty->assign('nav_2nd', $nav[1][$file]);
     $smarty->assign('nav_3rd', $nav[2]);
     $smarty->assign('file_name', $file);
     $res['left'] = $smarty->fetch('left.htm');


     $sql = 'SELECT rank_id, rank_name, min_points FROM '.
          $ecs->table('user_rank').' ORDER BY min_points ASC';
     $rs = $db->query($sql);
     $ranks = array();
     while ($row = $db->FetchRow($rs))
     {
          $ranks[$row['rank_id']] = $row['rank_name'];
     }

     $smarty->assign('action',       $_SESSION['action_list']);
     $smarty->assign('user_ranks',   $ranks);
     $smarty->assign('ur_here',      $_LANG['01_users_list'].'(包括：'.@implode(',', $intente).')');
     $smarty->assign('action_link',  array('text' => $_LANG['02_users_add'], 'href'=>'users.php?act=add'));
     $smarty->assign('country_list',  get_regions());
     $smarty->assign('province_list', get_regions(1,1));
     //$user_list = user_list();

     /* 获取顾客来源、购买力、客服 */
     $smarty->assign('from_where', get_from_where());
     $smarty->assign('type_list',  get_customer_type());
     $smarty->assign('admin_list', get_admin('session'));
     $smarty->assign('eff_list',   getEffectTypes());

     // $smarty->assign('is_intention', $_REQUEST['act']);          // 意向顾客查询字段，为分页提供区分支持
     // $smarty->assign('user_list',    $user_list['user_list']);
     // $smarty->assign('filter',       $user_list['filter']);
     // $smarty->assign('record_count', $user_list['record_count']);
     // $smarty->assign('page_count',   $user_list['page_count']);
     // $smarty->assign('full_page',    1);
     // $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

     /* 会员部处理重新分配顾客 */
     if ($_SESSION['role_id'] == 9)
     {
          $smarty->assign('effects', getEffectTypes());
     }

     //判断客服的权限，是否显示团队搜索
     if($_SESSION['action_list'] == 'all')
     {
          $smarty->assign('admin_show_team',1);
          $smarty->assign('role_list', get_role());
     }
     else
     {
          $sql = 'SELECT manager,role_id FROM '.$GLOBALS['ecs']->table('admin_user').
               ' WHERE user_id='.$_SESSION['admin_id'];
          $user = $GLOBALS['db']->getRow($sql);
          if($user['manager'] === '0')
          {
               $smarty->assign('role_id',$user['role_id']);
               $smarty->assign('show_team',1);
          }
     }
     //给模板赋值；
     @$users_list = get_users_lists($user_id, $account_type);

     $smarty->assign('account_list', $users_list['account']);
     $smarty->assign('filter',       $users_list['filter']);
     $smarty->assign('record_count', $users_list['record_count']);
     $smarty->assign('page_count',   $users_list['page_count']);
     $smarty->assign('pageprev',   $users_list['filter']['page']-1);
     $smarty->assign('pagenext',   $users_list['filter']['page']+1);
     assign_query_info();
     $res['main'] = $smarty->fetch('custom_list.htm');

     die($json->encode($res));
}

/*------------------------------------------------------ */
//-- ajax返回用户列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
     $users_list = get_users_lists();
     $smarty->assign('account_list', $users_list['account']);
     $smarty->assign('filter',       $users_list['filter']);
     $smarty->assign('record_count', $users_list['record_count']);
     $smarty->assign('page_count',   $users_list['page_count']);
     $smarty->assign('pageprev',   $users_list['filter']['page']-1);
     $smarty->assign('pagenext',   $users_list['filter']['page']+1);

     $res['main'] = $smarty->fetch('custom_lists.htm');

     die($json->encode($res));
}

/*添加客户*/	
elseif($_REQUEST['act'] == 'add_users')
{
     /* 检查权限 */
     admin_priv('users_add');

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
     //print_r($user);
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

/* 查找推荐人 */
elseif($_REQUEST['act'] == 'find_referrer')
{
     admin_priv('users_add');

     $keywords = mysql_real_escape_string($_REQUEST['keywords']);
     $sql = 'SELECT DISTINCT user_id,user_name FROM '.$GLOBALS['ecs']->table('users').
          " WHERE user_name LIKE '%$keywords%' OR mobile_phone LIKE '%$keywords%' ".
          " OR home_phone LIKE '%$keywords%' ";
     $res = $GLOBALS['db']->getAll($sql);

     die($json->encode($res));
}

/* 日期切换 */
elseif ($_REQUEST['act'] == 'calendar')
{
     require 'includes\lunar.php';

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

/* 添加顾客 */
elseif ($_REQUEST['act'] == 'insert')
{
     /* 检查权限 */
     admin_priv('users_add');

     $area_code    = mysql_real_escape_string(trim($_POST['area_code']));
     $home_phone   = mysql_real_escape_string(trim($_POST['home_phone']));
     $mobile_phone = mysql_real_escape_string(trim($_POST['mobile_phone']));

     if (!empty($area_code)) $home_phone = $area_code.'-'.$home_phone;

     //获取家庭和手机号码
     if($home_phone && $mobile_phone)
     {
          $repeat_where = " home_phone='$home_phone' OR mobile_phone=$mobile_phone";
     }
     elseif($home_phone)
     {
          $repeat_where = " home_phone='$home_phone' ";
     }
     elseif($mobile_phone)
     {
          $repeat_where = " mobile_phone='$mobile_phone'";
     }

     $sql = 'SELECT COUNT(*) FROM '.$ecs->table('users')." WHERE $repeat_where";
     if ($repeat_where && $db->getOne($sql))
     {
          die ('0');
     }

     // 顾客基本信息
     $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
     $sex = in_array($sex, array(0, 1, 2)) ? $sex : 0;
     $userinfo = array (
          'user_name'     => trim($_POST['username']), // 顾客姓名
          'eff_id'        => intval($_POST['eff_id']), // 功效分类
          'sex'           => intval($_POST['sex']),                     // 性别
          'birthday'      => trim($_POST['birthday']), // 出生日期
          'age_group'     => mysql_real_escape_string(trim($_POST['age_group'])),      // 年龄段
          'from_where'    => intval($_POST['from_where']),     // 顾客来源
          'customer_type' => intval($_POST['customer_type']),  // 顾客类型
          'mobile_phone'  => mysql_real_escape_string(trim($_POST['mobile_phone'])),   // 手机号码
          'id_card'       => mysql_real_escape_string(trim($_POST['id_card'])),        // 身份证号码
          'member_cid'    => mysql_real_escape_string(trim($_POST['member_cid'])),     // 会员卡号
          'qq'            => trim($_POST['qq']),       // 腾讯QQ
          'home_phone'    => trim( $home_phone)?trim($home_phone):'',          //家庭号码；
          'aliww'         => trim($_POST['aliww']),    // 阿里旺旺
          'habby'         => trim($_POST['habby']),    // 兴趣爱好
          'email'         => trim($_POST['email']),    // 电子邮箱
          'occupat'       => trim($_POST['occupat']),  // 顾客职业
          'income'        => trim($_POST['income']),   // 经济来源
          'disease'       => $_POST['disease'] ,//疾病
          'characters'    => $_POST['characters'] ,//类型
          'disease_2'     => $_POST['disease_2'],     // 其他疾病
          'remarks'       => mysql_real_escape_string(trim($_POST['remarks'])),       // 备注
          'parent_id'     => $_POST['recommender'],   // 推荐人
          'admin_id'      => $_SESSION['admin_id'],   // 顾客归属
          'first_admin'   => $_SESSION['admin_id'],   // 添加顾客客服
          'add_time'      => time(),                  // 添加时间
          'snail'         => mysql_real_escape_string(trim($_POST['snail'])),   // 平邮地址
          'team'          => intval($_POST['team']),  // 所属团队
          'admin_name'    => $_SESSION['admin_name'], // 客服姓名
          'lang'          => intval($_POST['lang']),   // 常用语言
          'parent_id'     => intval($_POST['parent_id']),
          'role_id'       => intval($_POST['role_id'])
     );

     if ($_POST['calendar'] == 1)
     {
          $userinfo['birthday'] = trim($_POST['birthday']);
     }
     else 
     {
          require(dirname(__FILE__).'/includes/lunar.php');
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
          //sys_msg($msg, 1);
     }

     //得到顾客详细地址存入数据库
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
          $validity = strtotime(date('Ym', time() +28800)+intval($integral['validity']));

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
     //echo  $_REQUEST['uname'];
     admin_log($_POST['username'], 'add', 'users');
     $uname =implode(',',$_REQUEST['uname']); 
     if (!empty($uname))
     {
          insertSocial();
     }
     $arr['req_msg'] = true;
     $arr['timeout'] = 2000;
     $arr['message'] = '添加成功';

     die($json->encode($arr));
}

/* 删除客户 */
elseif ($_REQUEST['act'] == 'del_custom')
{
     $uid        = intval($_GET['uid']);
     $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users')." SET admin_id=0 WHERE user_id=$uid";
     $result     = $GLOBALS['db']->query($sql_update);
     if($result)
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '删除成功';

          die($json->encode($arr));
     }
     else
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '删除失败';

          die($json->encode($arr));
     }
}

/* 查看客户资料 */
elseif($_REQUEST['act'] == 'select_custom')
{
     $uid = intval($_GET['uid']);

     //查看顾客属于哪个平台
     $sql_select = 'SELECT role_name FROM '.$GLOBALS['ecs']->table('role').
          ' r INNER JOIN '.$GLOBALS['ecs']->table('users').
          " u ON r.role_id=u.role_id WHERE u.user_id=$uid";
     $role = $db->getOne( $sql_select);
     $smarty->assign('role',$role);

     //查询顾客省级地址；
     $sql_select = 'SELECT province FROM '.$GLOBALS['ecs']->table('user_address')." WHERE user_id=$uid";
     $provinces  = $db->getOne($sql_select);

     $sql_select = 'SELECT region_name FROM '.$GLOBALS['ecs']->table('region')." WHERE region_id = $provinces";
     $province   = $db->getOne($sql_select);

     $smarty->assign('province',$province);


     //查询客户城市地址
     $sql_select = 'SELECT city FROM '.$GLOBALS['ecs']->table('user_address')." WHERE user_id= $uid";
     $citys=$db->getOne($sql_select);

     $sql_select='SELECT region_name FROM '.$GLOBALS['ecs']->table('region')." WHERE region_id= $citys";
     $city=$db->getOne($sql_select);
     $smarty->assign('city',$city);

     //查询客户县区地址
     $sql_select = 'SELECT district FROM '.$GLOBALS['ecs']->table('user_address')." WHERE user_id=$uid";
     $districts  = $db->getOne($sql_select);
     if($districts)
     {
          $sql_select = 'SELECT region_name FROM '.$GLOBALS['ecs']->table('region')." WHERE region_id=$districts";
          $district = $db->getOne($sql_select);
          $smarty->assign('district',$district);
     }

     //查询客户详细地址
     $sql_select = 'SELECT address FROM '.$GLOBALS['ecs']->table('user_address')." WHERE user_id=$uid";
     $address = $db->getOne($sql_select);
     $smarty->assign('address',$address);

     //查询顾客邮编
     $sql_select = 'SELECT zipcode FROM '.$GLOBALS['ecs']->table('user_address')." WHERE user_id=$uid";
     $zipcode = $db->getOne($sql_select);
     $smarty->assign('zipcode',$zipcode);

     //查询顾客来源
     $sql_select = 'SELECT from_where FROM '.$GLOBALS['ecs']->table('users')." WHERE user_id=$uid";
     $froms = $db->getOne($sql_select);
     $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('from_where')." WHERE from_id=$froms";
     $from = $db->getAll($sql_select);
     $smarty->assign('from',$from);

     //查询顾客类型
     $sql_select = 'SELECT customer_type FROM '.$GLOBALS['ecs']->table('users')." WHERE user_id=$uid";
     $customer = $db->getOne($sql_select);
     $sql_select = 'SELECT type_name FROM '.$GLOBALS['ecs']->table('customer_type')." WHERE type_id=$customer";
     $customer_type = $db->getOne($sql_select);
     $smarty->assign('customer_types',$customer_type);

     //查询顾客分类
     $sql_select = 'SELECT eff_name FROM '.$GLOBALS['ecs']->table('effects').' e INNER JOIN '.
          $GLOBALS['ecs']->table('users')." u ON e.eff_id=u.eff_id WHERE u.user_id=$uid";
     $eff_name = $db->getOne($sql_select);
     $smarty->assign('eff_name',$eff_name);

     //查询顾客生日，qq,旺旺，电子邮件，省份证号码，常用语言会员卡号等
     $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('users')." WHERE user_id=$uid";
     $user_im = $db->getRow($sql_select);
     $smarty->assign('usere',$user_im);

     //获取顾客性格
     $sql_select = 'SELECT characters FROM '.$GLOBALS['ecs']->table('users'). " WHERE user_id=$uid";
     $characters = $db->getOne($sql_select);
     if ($characters != '')
     {
          $characters = rtrim(str_replace(':' , ',' ,$characters),',');
          $sql_select = 'SELECT characters FROM '.$GLOBALS['ecs']->table('character').
               " WHERE character_id IN( $characters)";
          $trait = $db->getCol($sql_select);
          $smarty->assign('trait',$trait);
     }

     //获取顾客经济来源
     $sql_select = 'SELECT income FROM '.$GLOBALS['ecs']->table('users')." WHERE user_id=$uid";
     $income = $db->getOne($sql_select);
     if($income != '')
     {
          $sql_select = 'SELECT income FROM '.$GLOBALS['ecs']->table('income')." WHERE income_id=$income";
          $incomes = $db->getOne($sql_select);
          $smarty->assign('incomes',$incomes);
     }

     //获取顾客所患疾病
     $sql_select = 'SELECT disease FROM '.$GLOBALS['ecs']->table('users')." WHERE user_id=$uid";
     $disease = $db->getOne($sql_select);
     if ($disease != '') 
     {
          $disease = rtrim (str_replace(':' , ',' , $disease),',' );
          $sql_select = 'SELECT disease FROM '.$GLOBALS['ecs']->table('disease').
               " WHERE disease_id IN ($disease)";
          $illness = $db->getCol($sql_select);
          $smarty->assign('illness',$illness);
     }

     //获取客户社会关系
     $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('user_relation')." WHERE user_id=$uid";
     $relation = $db->getCol($sql_select);
     $smarty->assign('relation',$relation);
     $res['main'] = $smarty->fetch('edit_custom.htm');

     die($json->encode($res));
}

/* 编辑客户资料 */
elseif($_REQUEST['act'] == 'edit_custom')
{
     admin_priv('users_add');
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
     $smarty->assign('extend_info_list', $extend_info_list);

     $smarty->assign('ur_here',          $_LANG['04_users_add']);
     $smarty->assign('action_link',      array('text' => $_LANG['01_users_list'], 'href'=>'users.php?act=list'));
     $smarty->assign('form_action',      'insert');
     $smarty->assign('start_index',      0);

     $uid = intval($_GET['uid']); //客户ID
     $user_info = get_info_custom($uid);

     //修改手机号码
     if($_REQUEST['info'] == 'mobile')
     {
          $smarty->assign('mobile', $user_info['mobile_phone']);
     }

     //修改用户名
     if($_REQUEST['info'] == 'names')
     {
          $smarty->assign('user_name', $user_info['user_name']);
     }

     //修改客户归属平台
     if($_REQUEST['info'] == 'terrace')
     {
          $smarty->assign('role_list', get_role_list(1));
     }

     //修改地区  
     if($_REQUEST['info'] == 'district')
     {
          $smarty->assign('province_list', get_regions(1,1));
     }

     //修改详细地址
     if($_REQUEST['info'] == 'address')
     {
          $smarty->assign('user_region',user_region($_GET['uid']));
     }

     //修改平邮地址
     if($_REQUEST['info'] == 'snail')
     {
          $smarty->assign('snail',$user_info['snail']);
     }

     //修改邮编
     if($_REQUEST['info'] == 'zipcode')
     {
          $smarty->assign('zipcode',user_region($_GET['uid']));
     }

     //修改顾客来源
     if($_REQUEST['info'] == 'from_where')
     {
          $smarty->assign('from_where', get_from_where());
     }

     //修改顾客类型
     if($_REQUEST['info'] == 'customer_type')
     {
          $smarty->assign('customer_type', get_customer_type());
     }

     //修改顾客分类
     if($_REQUEST['info'] == 'effects')
     {
          $sql = 'SELECT eff_id, eff_name FROM '.$ecs->table('effects').
               ' WHERE available=1 ORDER BY sort ASC';
          $smarty->assign('effects', $db->getAll($sql));
     }

     // 修改顾客生日
     if($_REQUEST['info'] == 'birthday')
     {
          $smarty->assign('birthday', $user_info['birthday']);
     }

     // 修改顾客QQ
     if($_REQUEST['info'] == 'qq')
     {
          $smarty->assign('qq', $user_info['qq']);
     }

     // 修改顾客阿里旺旺
     if($_REQUEST['info'] == 'aliww')
     {
          $smarty->assign('aliww', $user_info['aliww']);
     }

     //修改客户电子邮件
     if($_REQUEST['info'] == 'email')
     {
          $smarty->assign('email', $user_info['email']);
     }

     //修改客户身份证号码
     if($_REQUEST['info'] == 'card')
     {
          $smarty->assign('card', $user_info['id_card']);
     }

     //修改客会员卡号
     if($_REQUEST['info'] == 'member')
     {
          $smarty->assign('member', $user_info['member_cid']);
     }

     //修改客户爱好
     if ($_REQUEST['info'] == 'habby')
     {
          $smarty->assign('habby', $user_info['habby']);
     }

     //修改客户性格
     if ($_REQUEST['info'] == 'trait')
     {
          // 获取性格列表
          $arr = explode(':',$user_info['characters']);
          $smarty->assign('characters',$arr);

          $sql = 'SELECT character_id, characters FROM '.$ecs->table('character').' ORDER BY sort ASC';
          $smarty->assign('character', $db->getAll($sql));
     }

     //修改客户职业
     if ($_REQUEST['info'] == 'occupat')
     {
          $smarty->assign('occupat', $user_info['occupat']);
     }

     //修改客户经济来源
     if ($_REQUEST['info'] == 'incomes')
     {
          //获取顾客经济来源
          $smarty->assign('income', get_income());
     }

     //修改顾客所患疾病
     if ($_REQUEST['info'] == 'illness')
     {
          //获取疾病列表
          $smarty->assign('disease', get_disease());
     }

     //修改其他疾病
     if ($_REQUEST['info'] == 'disease_2')
     {
          //获取疾病列表
          $smarty->assign('disease_2', $user_info['disease_2']);
     }

     //修改客户备注
     if ($_REQUEST['info'] == 'remarks')
     {
          //获取备注信息
          $smarty->assign('remarks', $user_info['remarks']);
     }

     // 取得用户等级数组,按用户级别排序
     $smarty->assign('special_ranks',    get_rank_list(true));

     //获取城市数据
     $smarty->assign('country_list', get_regions());

     //修改会员帐户金额
     assign_query_info();
     $smarty->assign('ur_here', $_LANG['02_users_add']);

     //获取客户详细信息
     $user_info = get_info_custom($uid);
     $smarty->assign('code',substr($user_info['home_phone'],0,4));
     $smarty->assign('code_moble',substr($user_info['home_phone'],5));

     $arrs = explode(':',$user_info['disease']);
     $smarty->assign('diseases',$arrs);
     $smarty->assign('user',$user_info);

     $res['info'] = $_REQUEST['info'];
     $res['main'] = $smarty->fetch('select_custom.htm');

     die($json->encode($res));
}

/* 保存顾客信息 */
elseif($_REQUEST['act'] == 'save')
{
     $uid = intval($_POST['uid']); //客户ID

     //电话的修改
     if($_REQUEST['info']=='mobile')
     {
          $mobile_id=$_POST['mobile_id'];
     }
}

/*更新用户资料*/
elseif($_REQUEST['act'] == 'edit')
{
     //顾客基本信息
     $user_id       = intval($_GET['uid']);
     $area_code     = mysql_real_escape_string(trim($_POST['area_code']));//家庭电话
     $home_phone    = mysql_real_escape_string(trim($_POST['home_phone']));//家庭电话
     $home_phone    = $area_code.'-'.$home_phone;
     $user_name     = trim($_POST['username']);// 顾客姓名
     $eff_id        = intval($_POST['eff_id']); // 功效分类
     $sex           = intval($_POST['sex']);   // 性别
     $birthday      = trim($_POST['birthday']); // 出生日期
     $age_group     = mysql_real_escape_string(trim($_POST['age_group'])); // 年龄段
     $from_where    = $_POST['from_where'];     // 顾客来源
     $customer_type = $_POST['customer_type'];  // 顾客类型
     $mobile_phone  = mysql_real_escape_string(trim($_POST['mobile_phone']));// 手机号码
     $id_card       = mysql_real_escape_string(trim($_POST['id_card']));    // 身份证号码
     $member_cid    = mysql_real_escape_string(trim($_POST['member_cid']));// 会员卡号
     $qq            = trim($_POST['qq']);       // 腾讯QQ
     $aliww         = trim($_POST['aliww']);    // 阿里旺旺
     $habby         = trim($_POST['habby']);    // 兴趣爱好
     $email         = trim($_POST['email']);   // 电子邮箱
     $occupat       = trim($_POST['occupat']);  // 顾客职业
     $income        = trim($_POST['income']);  // 经济来源
     $disease       = $_POST['disease'];//疾病
     $characters    = $_POST['characters'];//类型
     $disease_2     = $_POST['disease_2'];    // 其他疾病
     $remarks       = $_POST['remarks'];       // 备注  这里必须加过滤
     $parent_id     = $_POST['recommender'];   // 推荐人
     $admin_id      = $_SESSION['admin_id'];   // 顾客归属
     $first_admin   = $_SESSION['admin_id'];   // 添加顾客客服
     $add_time      = time();                  // 添加时间
     $snail         = mysql_real_escape_string(trim($_POST['snail'])); // 平邮地址
     $team          = intval($_POST['team']);  // 所属团队
     $admin_name    = $_SESSION['admin_name']; // 客服姓名
     $lang          = intval($_POST['lang']);  // 常用语言
     $parent_id     = intval($_POST['parent_id']);
     $role_id       = intval($_POST['role_id']);

     $country  = intval($_POST['country']);  // 国家
     $province = intval($_POST['province']); // 省份
     $city     = intval( $_POST['city']);    // 城市
     $address  = intval($_POST['address']);  // 详细地址
     $zipcode  = intval($_POST['zipcode']) ;  // 邮编

     //如果有区县就显示区县
     if (!empty($_POST['district']))
     {
          $addr['district'] = intval($_POST['district']); // 区县
     }

     //更新客户基本信息
     $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users')." SET email='$email',user_name='$user_name', eff_id='$eff_id',id_card='$id_card',member_cid='$member_cid',habby='$habby',sex='$sex',birthday='$birthday',age_group='$age_group',from_where='$from_where',add_time='$add_time',income='$income',disease_2='$disease_2',disease='$disease',customer_type='$customer_type',first_admin='$first_admin',aliww='$aliww',qq='$qq',home_phone='$home_phone',mobile_phone='$mobile_phone',occupat='$occupat',remarks='$remarks',characters='$characters',lang='$lang',role_id='$role_id' WHERE user_id=$user_id";
     $result = mysql_query($sql_update);
     if(!$result)
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '编辑失败';

          die($json->encode($arr));
     }

     //更新客户详细地址；
     if(!empty($city) || !empty($_POST['district']))
     {
          $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('user_address').
               " SET province='$province',city='$city',address='$address',zipcode='$zipcode',district='$_POST[district]' WHERE user_id=$user_id" ;
          $result = $GLOBALS['ecs']->table($sql_update);
          if(!$result)
          {
               $arr['req_msg'] = true;
               $arr['timeout'] = 2000;
               $arr['message'] = '更新地址失败';

               die($json->encode($arr));
          }
     }

     //以上条件都成立则:
     $arr['req_msg'] = true;
     $arr['timeout'] = 2000;
     $arr['message'] = '编辑成功';

     die($json->encode($arr));
}

/* 批量修改顾客归属 */
elseif ($_REQUEST['act'] == 'batch')
{
     admin_priv('all');  // 这里改成相应的权限，也需要改的： line 533
     $sql_select = 'SELECT user_name, user_id FROM '.$ecs->table('admin_user').
          ' WHERE role_id>0 OR user_id=30';
     $admin_list = $db->getAll($sql_select);

     $smarty->assign('admin_list', $admin_list);

     $res['main']=$smarty->fetch('batch_transfer.htm');
     die($json->encode($res));
}

/* 执行对数据库的操作 */
elseif($_REQUEST['act'] == 'from_to')
{
     admin_priv('all');

     $to_admin   = intval($_POST['to_admin']);
     $from_admin = intval($_POST['from_admin']);

     $sql_select = 'SELECT user_name, role_id FROM '.
          $GLOBALS['ecs']->table('admin_user')." WHERE user_id=$to_admin";
     $res = $GLOBALS['db']->getRow($sql_select);
     if (empty($res))
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '未找到客服目标';

          die($json->encode($arr));
     }

     $to_admin_name = $res['user_name'];
     $to_role_id    = $res['role_id'];

     $sql_update = 'UPDATE '.$ecs->table('users')." SET admin_id=$to_admin, admin_name='$to_admin_name', role_id=$to_role_id WHERE admin_id=$from_admin";
     if($db->query($sql_update))
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '转移成功';

          die($json->encode($arr));
     }
}

/* 转移部分顾客归属 */  
elseif ($_REQUEST['act'] == 'part_transfer')
{
     admin_priv('part_transfer');

     //获取存在客户或者临时客户的名字
     $sql_select = 'SELECT user_name, user_id FROM '.$ecs->table('admin_user').' WHERE role_id>0 ';
     $admin_list = $db->getAll($sql_select);

     $smarty->assign('admin_list', $admin_list);
     $res['main'] = $smarty->fetch('part_transfer.htm');

     die($json->encode($res));
}

/* 部分转移顾客归属 */
elseif($_REQUEST['act'] == 'transfer_submit')
{
     $from_phone = htmlspecialchars($_POST['from_phone']); //联系电话
     $from_phone = preg_split('/[^0-9\-]+/',$from_phone);  //从非数字和-中分割字符串
     $from_phone = array_filter($from_phone);   //去除值为空的元素
     $from_phone = array_slice($from_phone,0);    //数组键值从0开始排序 

     $phone = array();
     for($i=0;$i<count($from_phone);$i++)
     {
          // 获取格式正确的电话号码或手机号码 
          if(preg_match('/^(\d{3}-)(\d{8})$|^(\d{4}-)(\d{7,8})$|^(\d{11})$/',$from_phone[$i]))
          {
               $phone[] = $from_phone[$i];
          }
     } 

     $from_phone = implode('\',\'',$phone); 
     if(empty($from_phone) || $from_phone==0)
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '顾客联系方式有误';

          die($json->encode($arr));
     } 

     // 获取转移目标客服
     $to_admin = intval($_POST['to_admin']);  //目标客服id
     $sql_select = 'SELECT user_name, role_id FROM '.
          $GLOBALS['ecs']->table('admin_user')." WHERE user_id=$to_admin";
     $res = $GLOBALS['db']->getRow($sql_select);  //目标客服name

     // 从 line 605 开始，精简代码 到 line 637
     $to_admin_name = $res['user_name'];
     $role_id       = $res['role_id'];
     $to_admin_2    = $_POST['to_admin_2'];  //再次输入的目标客服的name

     //判断转移目标客服是否一致
     if(!empty($to_admin_name) && !empty($to_admin_2) && $to_admin_name == $to_admin_2)
     {
          //检查客服的权限
          if($_SESSION['action_list'] == 'all')
          {
               $sql_select = 'SELECT admin_id FROM '.$GLOBALS['ecs']->table('users')."
                    WHERE mobile_phone IN ('".$from_phone."') or home_phone IN ('".$from_phone."')";
               $from_admin = $GLOBALS['db']->getOne($sql_select);
               if(empty($from_admin))
               {
                    $arr['req_msg'] = true;
                    $arr['timeout'] = 2000;
                    $arr['message'] = '顾客联系方式有误';

                    die($json->encode($arr));
               }
               $where = ' admin_id>0 ';
          }
          else
          {
               $from_admin = $_SESSION['admin_id'];  //转移客服
               $where = 'admin_id='.$_SESSION['admin_id'];
          }

          $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users').' SET admin_id='.$to_admin.",admin_name='".
               $to_admin_name."', role_id=$role_id WHERE ".$where." AND mobile_phone IN ('".$from_phone.
               "') OR home_phone IN ('".$from_phone."')";
          $result = $GLOBALS['db']->query($sql_update);
          $transfer_num = mysql_affected_rows();
          if($transfer_num == 0 || empty($transfer_num))
          {
               $arr['req_msg'] = true;
               $arr['timeout'] = 2000;
               $arr['message'] = '转移出错';

               die($json->encode($arr));
          }

          if($result)
          {
               //获取转移的顾客的user_id
               $sql_select = 'SELECT user_id FROM '.$GLOBALS['ecs']->table('users').
                    " WHERE mobile_phone IN ('".$from_phone."') OR home_phone IN ('".$from_phone."')";
               $from_userid = $GLOBALS['db']->getCol($sql_select);
               $user_id = implode(',',$from_userid);

               //获取转移的时间戳
               $transfer_time = strtotime("now")-3600*8;  

               //转移记录插入数据库
               $sql_insert = ' INSERT INTO '.$GLOBALS['ecs']->table('transfer_record').
                    "(from_admin,to_admin,handler_admin,transfer_time,transfer_user,transfer_num)VALUES('".
                    $from_admin."','".$to_admin."','".$_SESSION['admin_id']."','".$transfer_time."','".
                    $user_id."','".$transfer_num."')";
               $GLOBALS['db']->query($sql_insert);

               // 转移的时间戳插入到user表中
               $user_id = implode('\',\'',$from_userid);
               $sql_update = ' UPDATE '.$GLOBALS['ecs']->table('users').' SET transfer_time='.$transfer_time." 
                    WHERE user_id IN ('".$user_id."')";
               $GLOBALS['db']->query($sql_update);

               $transfer_time = strtotime("now")-8*3600-30*24*3600;
               $arr['req_msg'] = true;
               $arr['timeout'] = 2000;
               $arr['message'] = '转移成功';

               die($json->encode($arr)); 
          }
     }
     else
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '转移目标客服输入不一致';

          die($json->encode($arr)); 
     }
}

/*单个顾客转移*/
elseif($_REQUEST['act'] == 'custom_change')
{
     // 未强制转换变量类型
     $uid = intval($_GET['uid']);
     $sql_select = 'SELECT user_name, user_id FROM '.$ecs->table('admin_user').' WHERE role_id>0';
     $admin_list = $db->getAll($sql_select);

     $smarty->assign('admin_id', $uid);
     $smarty->assign('admin_list', $admin_list);
     $res['main']=$smarty->fetch('one_transfer.htm');

     die($json->encode($res));
}

/*单个顾客转移*/
elseif($_REQUEST['act'] == 'sumbit_transfer')
{
     $uid = intval($_GET['uid']);
     $to_admin = intval($_POST['to_admin']);
     $to_admin_name = trim($_POST['to_admin_2']);

     $sql_select = 'SELECT user_name FROM '.$GLOBALS['ecs']->table('admin_user')." WHERE user_id=$to_admin";
     $admin_name = $GLOBALS['db']->getOne($sql_select);


     if (empty($to_admin_name) || $admin_name !=$to_admin_name||$uid!=0)
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '转移目标客服输入不一致';

          die($json->encode($arr)); 
     }

     //获取转移客服的资料
     $sql_select = 'SELECT role_id FROM '.$GLOBALS['ecs']->table('admin_user')." WHERE user_id=$to_admin";
     $role_id = $GLOBALS['db']->getOne($sql_select);

     $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('users').
          " SET admin_id='$to_admin',admin_name='$to_admin_name',role_id='$role_id' WHERE user_id=$uid";
     if ($GLOBALS['db']->query($sql_update))
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '转移成功';

          die($json->encode($arr)); 
     }
     else
     {
          $arr['req_msg'] = true;
          $arr['timeout'] = 2000;
          $arr['message'] = '转移失败';

          die($json->encode($arr)); 
     }	
}

//获取客户列表,分页函数；
function get_users_lists()
{
     /* 检查参数 */
     //$where = " WHERE user_id = '$user_id' ";
     if (in_array($account_type, array('user_money', 'frozen_money', 'rank_points', 'pay_points')))
     {
          $where .= " AND $account_type <> 0 ";
     }

     /* 初始化分页参数 */
     $filter = array(
          'user_id'       => $user_id,
          'account_type'  => $account_type
     );

     /* 查询记录 */
     // 通过省来查询客户
     if(!empty($_POST['province']))
     {
          $sql .= 'INNER JOIN '.$GLOBALS['ecs']->table('user_address').' au ON u.user_id=au.user_id';
          $where .= ' au.province='.intval($_POST['province']);
     }

     //通过市来查找客户
     if(!empty($_POST['city']))
     {
          $where .= ' AND au.city='.intval($_POST['city']);
     }

     //通过县来查找客户
     if(!empty($_POST['district']))
     {
          $where .= 'AND au.district='.intval($_POST['district']);
     }

     //通过地址来查找客户
     if($_POST['saddress']!='地址'&&!empty($_POST['saddress'])&&(empty($_POST['province'])))
     {
          $sql.="INNER JOIN ".$GLOBALS['ecs']->table('user_address').' au ON (u.user_id=au.user_id)';
          $where.=" au.address LIKE '%$_POST[saddress]%'";
     }
     if($_POST['saddress']!='地址' && !empty($_POST['province']))
     {
          $where.= " AND address LIKE '%$_POST[saddress]%'";
     }

     //通过电话查询客户
     if(!empty($_POST['keyword'])&& $_POST['keyword']!='姓名或电话或手机')
     { 
          if($_POST['saddress']!='地址' || !empty($_POST['province']))
          {
               $where .= " AND (home_phone LIKE '%$_POST[keyword]%' OR mobile_phone LIKE '%$_POST[keyword]%' OR user_name LIKE  '%$_POST[keyword]%') ";
          }
          else
          {
               $where .= " (home_phone LIKE '%$_POST[keyword]%' OR mobile_phone LIKE '%$_POST[keyword]%' OR user_name LIKE  '%$_POST[keyword]%')";
          } 
     }

     //根据顾客来源来查询客户
     if(!empty($_POST['from_where']))
     {
          if($_POST['saddress']!='地址' || !empty($_POST['province'])||$_POST['keyword']!='姓名或电话或手机')
          {
               $where.=" AND from_where=$_POST[from_where] ";
          }
          else
          {
               $where.=" from_where=$_POST[from_where] ";
          }
     }

     //根据购买力来查询客户
     if(!empty($_POST['type']))
     {
          if($_POST['saddress']!='地址' || !empty($_POST['province'])||$_POST['keyword']!='姓名或电话或手机'||!empty($_POST['from_where']))
          {
               $where.=" AND customer_type=$_POST[type] ";
          }
          else
          {
               $where.=" customer_type=$_POST[type] ";
          }
     }

     //根据功效来查询客户
     if (!empty($_POST['effects']))
     {  
          if($_POST['saddress']!='地址' || !empty($_POST['province'])||$_POST['keyword']!='姓名或电话或手机'||!empty($_POST['from_where'])||!empty($_POST['type']))
          {
               $where.=" AND eff_id=$_POST[effects] "; 
          }
          else
          {
               $where.=" eff_id=$_POST[effects] "; 
          }
     } 

     //根据时间段来查询客户
     if (!empty($_POST['startTime'])&&!empty($_POST['endTime']))
     {
          $startime=strtotime ("$_POST[startTime]");
          $endtime =strtotime ("$_POST[endTime]");
          if($_POST['saddress']!='地址' || !empty($_POST['province'])||$_POST['keyword']!='姓名或电话或手机'||!empty($_POST['from_where'])||!empty($_POST['type'])||!empty($_POST['effects']))
          {
               $where.=" AND add_time > $startime AND add_time<$endtime ";
          }
          else
          {
               $where.=" add_time > $startime AND add_time<$endtime  ";
          }
     }

     //根据团队来查询客户
     if (!empty($_POST['group_search']))
     {
          if($_POST['saddress']!='地址' || !empty($_POST['province'])||$_POST['keyword']!='姓名或电话或手机'||!empty($_POST['from_where'])||!empty($_POST['type'])||!empty($_POST['effects']))
          {
               $where.=" AND u.role_id=$_POST[group_search] "; 
          }
          else
          {
               $where.=" u.role_id=$_POST[group_search] "; 
          }
     }

     //初始化条件
     $where = $where ? $where : ' 1';
     $sql  =$sql ? $sql :'';

     /* 查询记录总数，计算分页数 */
     $sql_select = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('users').
          ' u '.$sql.' WHERE '.$where.' AND u.admin_id>0' ;
     //echo $sql_select;
     $filter['record_count'] = $GLOBALS['db']->getOne($sql_select);
     $filter = page_and_size($filter);
     $sql = 'SELECT * FROM '.$GLOBALS['ecs']->table('users').' u INNER JOIN '.$GLOBALS['ecs']->table('role').
          ' a ON u.role_id=a.role_id '.$sql.' WHERE '.$where.' and u.admin_id>0 ORDER BY u.user_id DESC';

     $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
     $arr = array();
     while ($row = $GLOBALS['db']->fetchRow($res))
     {
          $row['change_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['change_time']);
          $arr[] = $row;
     }

     return array('account' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/**
 * 插入顾客社会关系
 */
function insertSocial ()
{
     // 添加用户社会关系信息
     $sql = 'INSERT INTO '.$GLOBALS['ecs']->table('user_relation').
          '(rela_id,user_id, uname, rela_sex, mobile, relation, habitancy, age, add_age_year, profession, financial, selfcare, rela_user_id)VALUES';
     foreach ($_POST['uname'] as $key=>$val)
     {
          // 如果没用姓名 则跳过该条记录
          if (empty($val))
          {
               continue;
          }

          $sql_temp = array (   
               'rela_id'      => $key,
               'user_id'      => $user_id ? $user_id : intval($_POST['user_id']), // 顾客关联ID 
               'uname'        => trim($val),                         // 姓名
               'rela_sex'     => intval($_POST['relasex'][$key]),    // 性别
               'mobile'       => mysql_real_escape_string(trim($_POST['mobile'][$key])),       // 联系电话
               'relation'     => mysql_real_escape_string(trim($_POST['relation'][$key])),     // 社会关系
               'habitancy'    => mysql_real_escape_string(trim($_POST['habitancy'][$key])),  // 居住情况
               'age'          => intval($_POST['age'][$key]),        // 年龄
               'add_age_year' => date('Y', time()+28800),            // 添加年份，用于计算当前年龄
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
 * 函数注释
 */
function get_address ($id)
{
     $sql = 'SELECT region_name FROM '.$GLOBALS['ecs']->table('region').
          " WHERE region_id=$id";
     return $GLOBALS['db']->getOne($sql);
}

/* 获取客户详细信息*/
function get_info_custom($uid=0)
{
     $sql_select="SELECT * FROM crm_users  WHERE user_id=$uid";
     $info = $GLOBALS['db']-> getRow($sql_select);
     return $info;
}

/**
 * 函数注释
 */
function user_region($uid=0)
{
     $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('user_address')." WHERE user_id=$uid";
     $address = $GLOBALS['db']->getRow($sql_select);
     return $address;
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

?>
