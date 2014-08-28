<?php
/* CRM 万能工具 智选器*/
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
date_default_timezone_set('Asia/Shanghai');

if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'filter';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

//根据权限对模板赋值
if($_REQUEST['act'] == 'filter')
{
    $power = 0;
    $admin_id = $_SESSION['admin_id'];
    $role_id  = $_SESSION['role_id'];
    $where = '';
    if(admin_priv('all','',false)) 
    {
        $power = 1;
        $smarty->assign('role_list',get_role_list());           //部门
        $smarty->assign('admin_list',get_admin_userlist());     //管理员列表
    }
    elseif(admin_priv('filter','',false))
    {
        $power = 2;
        $smarty->assign('admin_list',get_admin_userlist());
        $where = " role_id=$role_id";
    }
    else
    {
    }

    $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('operater').$where;   //操作列表
    $operater_list = $GLOBALS['db']->getAll($sql_select);

    $smarty->assign('power',$power);     //管理员
    $smarty->assign('operater_list',$operater_list);

    $res['main'] = $smarty->fetch('filter.htm');
    die($json->encode($res));
}

//通过部门获得管理员列表
elseif ($_REQUEST['act'] == 'get_role_admin')
{
    $role_id = intval($_REQUEST['role_id']);
    $where = '';
    if($role_id)
    {
       $where = " WHERE role_id=$role_id "; 
    }

    $sql_select = 'SELECT user_name,user_id FROM '.$GLOBALS['ecs']->table('admin_user').$where;

    $admin_list = $GLOBALS['db']->getAll($sql_select);

    $res['main'] = $admin_list;
    $res['obj'] = 'admin_list';
    die($json->encode($res));
}

//通过管理员获得操作列表
elseif ($_REQUEST['act'] == 'get_admin_opt')
{
    $admin_id = intval($_REQUEST['admin_id']);

    if(admin_priv('all','',false))
    {
        $role_id = intval($_REQUEST['role_id']);
    }
    else
    {
        $role_id = $_SESSION['role_id'];
    }

    $where = " WHERE admin_id=$admin_id";

    if($role_id != 0)
    {
        $where .= " AND role_id=$role_id";
    }

    $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('operater').$where;
    $operater = $GLOBALS['db']->getAll($sql_select);
    
    $res['main'] = $operater;
    $res['obj'] = 'r_operater_list';

    die($json->encode($res));
}

//检查操作是否可以添加
elseif ($_REQUEST['act'] == 'check_opt')
{
    $role_id = intval($_REQUEST['role_id']);
    $admin_id = intval($_REQUEST['admin_id']);
    $operater_name = mysql_real_escape_string($_REQUEST['operater_name']);

    $sql_select = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('operater').
            " WHERE role_id=$role_id AND admin_id=$admin_id AND operater_name='$operater_name'";
    $result = $GLOBALS['db']->getOne($sql_select);

    $res = array();
    $res['req_msg'] = true;
    $res['timeout'] = 2000;
    $res['code'] = 0;

    if($result)
    {
       $res['code'] = 1; 
       $res['message'] = '已经存在相同操作';
    }

    die($json->encode($res));
}

//添加操作
elseif ($_REQUEST['act'] == 'add')
{
    $role_id = intval($_REQUEST['role_id']);
    $admin_id = intval($_REQUEST['admin_id']);
    $operater_name = mysql_real_escape_string($_REQUEST['operater_name']);
    $res['req_msg'] = true;
    $res['timeout'] = 2000;
    $res['code'] = 0;

    $sql_insert = 'INSERT INTO '.$GLOBALS['ecs']->table('operater').
        '(operater_name,role_id,admin_id,Exclude_admin,status)'.
        "VALUES('$operater_name',$role_id,$admin_id,0,1)";

    $result = $GLOBALS['db']->query($sql_insert);

    if($result)
    {
       $res['code'] = 1; 
       $res['message'] = '添加成功';
       $res['operater_id '] = $GLOBALS['db']->insert_id();
       $res['operater_name'] = $operater_namej;
    }
    else
    {
        $res['message'] = '添加失败';
    }

    die($json->encode($res));
}

?>
