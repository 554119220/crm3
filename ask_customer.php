<?php
/* 可淘顾客 */
elseif ($_REQUEST['act'] == 'ask_customer_list')
{
    if(admin_priv('ask_customer_list'))
    {
        $admin = array('role_id' => $_SESSION['role_id'],'admin_id'=> $_SESSION['admin_id'],'panel'=>1);

        //分页大小
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page'])<=0) ? 1 : intval($_REQUEST['page']);
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
        {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        }
        else
        {
            $filter['page_size'] = 20; 
        }

        if($admin['role_id'] == '')
        {
            $smarty->assign('role',get_role());
        }

        $sql_select = 'SELECT count(*) AS count FROM '.$GLOBALS['ecs']->table('ask_customer_list_view');

        $filter['record_count'] = $GLOBALS['db']->getOne($sql_select);
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
            'act'           => 'ask_customer_list',
        );

        $sql_select = 'SELECT * FROM '.$GLOBALS['ecs']->table('ask_customer_list_view')
            .' LIMIT '.($filter['page']-1)*$filter['page_size'].",{$filter['page_size']}";

        $result = $GLOBALS['db']->getAll($sql_select);
        foreach($result AS &$val)
        {
            $val['add_time'] = date('Y-m-d',$val['add_time']);
            $val['service_time'] = date('Y-m-d',$val['service_time']);
        }

        $askable = array('customer'=>$result,'title'=>'可淘顾客','type'=>0);

        $smarty->assign('customer',$askable);
        $smarty->assign('admin',$admin);
        $smarty->assign('filter',$filter);

        if(!(intval($_REQUEST['times'])))
        {
            $res['main'] = $smarty->fetch('ask_customer.htm');    
        }
        else
        {
            $res['main'] = $smarty->fetch('ask_content.htm');
        }

        die($json->encode($res));
    }
}

/* 已淘顾客列表 */
elseif ($_REQUEST['act'] == 'asked_customer_list')
{
    if(admin_priv('asked_customer_list','',false))   
    {

        $admin = array('role_id'=>$_SESSION['role_id'],'admin_id'=>$_SESSION['admin_id'],'panel'=>2);

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
    $filter['page_size'] = $_REQUEST['page_size'];

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

?>
