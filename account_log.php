<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/lib_order.php');

//办事


//调节帐号
if ($_REQUEST['act'] == 'add')
{
    if(admin_priv('account_manage'))
    {
        $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);

        $res =  array();
        $res['timeout'] = 2000;
        $res['code'] = 1;
        $res['req_msg'] = true;
        if ($user_id <= 0)
        {
            $res['message'] = '用户信息错误';
            die($json->encode($res));
        }

        $user = user_info($user_id);

        if (empty($user))
        {
            $res['message'] = '该顾客已经不存在';
            die($json->encode($res));
        }

        $smarty->assign('user', $user);     //获取顾客信息

        $res['main'] = $smarty->fetch('account_info.htm');
    }
}

//提交添加、编辑顾客帐号
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    if(admin_priv('account_manage'))
    {
        $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
        /*
        $res = array();
        $res['req_msg'] = true;
        $res['timeout'] - 2000;
         */

        if ($user_id <= 0)
        {
            $res['message'] = '顾客信息错误';
            die($json->encode($res));
        }

        $user = user_info($user_id);
        if(empty($user))
        {
            $res['code'] = 0;
            $res['message'] = '未找到此顾客';
            die($json->encode($res));
        }

        $change_desc    = sub_str($_REQUEST['change_desc'], 255, false);
        $user_money     = floatval($_REQUEST['add_sub_user_money']) * abs(floatval($_REQUEST['user_money']));   //可用资金
        $user_rank      = intval($_REQUEST['user_rank']);   //用户等级
        $rank_points    = floatval($_REQUEST['add_sub_rank_points']) * abs(floatval($_REQUEST['rank_points'])); //等级积分
        $pay_points     = floatval($_REQUEST['add_sub_pay_points']) * abs(floatval($_REQUEST['pay_points']));   //使用积分

        /*
        echo $change_desc.' '.$user_money.' '.$user_rank.' '.$rank_points.' '.$pay_points;
        exit();
         */

        if ($user_money == 0 && $frozen_money == 0 && $rank_points == 0 && $pay_points == 0 && $user_rank == 0)
        {
            $res['message'] = '输入参数有误请重新确认';
            $res['code'] = 0;
            die($json->encode($res));
        }

        if(account_change($user_id, $user_money, $frozen_money, $user_rank, $rank_points, $pay_points, $change_desc))
        {
            $res['code'] = 1;
            //$res['message'] = '修改成功';
        }

        die($json->encode($res));
    }
}

//顾客帐号变动
function account_change($user_id, $user_money = 0, $frozen_money = 0,$user_rank, $rank_points = 0, $pay_points = 0, $change_desc = '')
{
    $account_log = array(
        'user_id'       => $user_id,
        'user_money'    => $user_money,
        'frozen_money'  => $frozen_money,
        'rank_points'   => $rank_points,
        'pay_points'    => $pay_points,
        'change_time'   => gmtime(),
        'change_desc'   => $change_desc,
        'change_type'   => 0,
    );

    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('account_log'), $account_log, 'INSERT');

    $sql_update = "UPDATE " . $GLOBALS['ecs']->table('users') .
            " SET user_money = user_money + ('$user_money')," .
            " frozen_money = frozen_money + ('$frozen_money')," .
            " user_rank=$user_rank,".
            " rank_points = rank_points + ('$rank_points')," .
            " pay_points = pay_points + ('$pay_points')" .
            " WHERE user_id = '$user_id' LIMIT 1";
    return $GLOBALS['db']->query($sql_update);
}
?>
