<?php
/*=============================================================================
#     FileName: school.php
#         Desc: goods and other knowlage 
#       Author: Wuyuanhang
#        Email: 1828343841@qq.com
#     HomePage: kjrs_crm
#      Version: 0.0.1
#   LastChange: 2014-09-23 14:15:16
#      History:
=============================================================================*/
define('IN_ECS',true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_common.php');
require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_goods.php');
include_once(ROOT_PATH."includes/fckeditor/fckeditor.php");
date_default_timezone_set('Asia/Shanghai');

$act = isset($_REQUEST['act']) ? mysql_real_escape_string($_REQUEST['act']) : 'menu';

if ($act == 'menu') {
    $file = strstr(basename($_SERVER['PHP_SELF']), '.', true);
    $nav = list_nav();
    $smarty->assign('nav_2nd', $nav[1][$file]);
    $smarty->assign('nav_3rd', $nav[2]);
    $smarty->assign('file_name', $file);

    die($smarty->fetch('left.htm'));
}

/*添加知识库表单*/
elseif($_REQUEST['act'] == 'add_knowlage'){

    $knowlage_class_list = get_knowlage_class(0,1);
    $brank_list          = brand_list();
    $knowlage_id         = isset($_REQUEST['knowlage_id']) ? intval($_REQUEST['knowlage_id']) : 0;
    $unedit              = false;

    if(!empty($knowlage_id)){
        $sql_select = 'SELECT knowlage_name,knowlage_class,content FROM '.
            $GLOBALS['ecs']->table('knowlage')." WHERE knowlage_id=$knowlage_id";
        $filter = $GLOBALS['db']->getRow($sql_select);
    }

    $filter['knowlage_class'] = isset($_REQUEST['knowlage_class']) ? intval($_REQUEST['knowlage_class']) : 0;
    if($filter['knowlage_class'] < 4){
        $filter['goods_sn']       = isset($_REQUEST['goods_sn']) ? mysql_real_escape_string($_REQUEST['goods_sn']) : '';
        $filter['goods_name']     = isset($_REQUEST['goods_name']) ? mysql_real_escape_string($_REQUEST['goods_name']) : '';
    }else{
        $smarty->assign('no_goods',true);
    }

    $filter['knowlage_id']    = $knowlage_id;
    if(!empty($filter['goods_sn'])){
        $unedit = true;
    }

    $content = $filter ? $filter['content'] : '';

    /* 创建 html editor */
    create_html_editor('FCKeditor1',$content);

    $smarty->assign('filter',$filter);
    $smarty->assign('unedit',$unedit);
    $smarty->assign('brank_list',$brank_list);
    $smarty->assign('knowlage_class_list',$knowlage_class_list);

    $smarty->display('add_knowlage.htm');
}

/*添加知识库操作*/
elseif($_REQUEST['act'] == 'add_knowlage_done'){
    $knowlage_name  = trim(mysql_real_escape_string($_REQUEST['knowlage_name']));
    $knowlage_class = isset($_REQUEST['knowlage_class
            ']) ? intval($_REQUEST['knowlage_class']) : '';
    $goods_sn       = isset($_REQUEST['goods_id']) ? mysql_real_escape_string($_REQUEST['goods_id']) : '';
    $tags           = trim(mysql_real_escape_string($_REQUEST['tags']));
    $content        = $_REQUEST['FCKeditor1'];
    $knowlage_id    = isset($_REQUEST['knowlage_id']) ? intval($_REQUEST['knowlage_id']) : '';

    $filter       = array(
        'knowlage_name'  => $knowlage_name,
        'knowlage_class' => $knowlage_class,
        'goods_name'     => $_REQUEST['search'],
    );

    $msg = array(
        'message' => '',
        'code'    => false
    );

    if(!empty($knowlage_name) && !empty($content) && !empty($goods_sn)){
        if($knowlage_id){
            $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('knowlage').
                " SET knowlage_name='$knowlage_name',content='$content' WHERE knowlage_id=$knowlage_id"; 
            $msg['code'] = $GLOBALS['db']->query($sql_update);
            $msg['message'] = $msg['code'] ? '修改成功' : '修改失败';

            $smarty->assign('unedit',true);
            $filter['goods_sn']   = $goods_sn;
            $filter['goods_name'] = $_REQUEST['search'];
        }else{
            if($knowlage_class == 1 || $knowlage_class == 2){
                $sql_select = 'SELECT COUNT(*) FROM '.$GLOBALS['ecs']->table('knowlage').
                    " WHERE knowlage_class=$knowlage_class AND goods_sn='$goods_sn'";
                $only_mod = $GLOBALS['db']->getOne($sql_select);    
            }else{
                $only_mod = 0;
            }

            if($only_mod > 0){
                $msg['message'] = '已经存在该产品的相关知识,只许修改,不允许重复添加';
            }else{
                $sql_insert = "INSERT INTO ".$GLOBALS['ecs']->table('knowlage').
                    '(knowlage_name,knowlage_class,goods_sn,content,add_time,add_admin)VALUES('.
                    "'$knowlage_name',$knowlage_class,'$goods_sn','$content',{$_SERVER['REQUEST_TIME']},{$_SESSION['admin_id']})";
                $msg['code'] = $GLOBALS['db']->query($sql_insert);

                if($msg['code']){
                    $msg['code']    = true;
                    $msg['message'] = '添加成功';
                }else{
                    $msg['message'] = '添加失败';
                }

                $content = '';
            }    
        }
    }else{
        $msg['message'] = '没有完全填写资料,请认真检查';
    }

    $knowlage_class_list = get_knowlage_class(0,1);
    $brank_list          = brand_list();

    create_html_editor('FCKeditor1',$content);

    $smarty->assign('filter',$filter);
    $smarty->assign('msg', $msg);
    $smarty->assign('brank_list',$brank_list);
    $smarty->assign('knowlage_class_list',$knowlage_class_list);

    $smarty->display('add_knowlage.htm');
}

/*产品知识库*/
elseif($_REQUEST['act'] == 'knowlage_list'){
    if(admin_priv('all','',false) || admin_priv('edit_knowlage','',false)){
        $smarty->assign('edit_pwd',1);
        $keyword = isset($_REQUEST['keyword']) ? trim(mysql_real_escape_string($_REQUEST['keyword'])) : '';

        $where = ' WHERE is_delete=0 AND goods_sn NOT LIKE "%\_%" ';
        if(!empty($keyword)){
            $where .=  " AND goods_name LIKE '%$keyword%'";
        }

        $sql_select = 'SELECT goods_name,goods_sn,0 as goods_knowlage,0 as introduction,0 as talk_skill FROM '.$GLOBALS['ecs']->table('goods').$where.' ORDER BY brand_id DESC';
        $goods_list = $GLOBALS['db']->getAll($sql_select);

        if($goods_list){
            foreach($goods_list as &$val){
                $goods_sn_list[] = $val['goods_sn'];
            }    

            $goods_sn_list = join("','",$goods_sn_list);
            $sql_select = 'SELECT goods_sn,knowlage_id,knowlage_class FROM '.$GLOBALS['ecs']->table('knowlage').
                " WHERE goods_sn IN('$goods_sn_list')";
            $knowlage_id_list = $GLOBALS['db']->getAll($sql_select);

            if($knowlage_id_list){
                foreach($goods_list as &$goods){
                    foreach($knowlage_id_list as &$knowlage){
                        if($goods['goods_sn'] == $knowlage['goods_sn']){
                            switch($knowlage['knowlage_class']){
                            case 1:
                                $goods['goods_knowlage'] = $knowlage['knowlage_id'];
                                break;
                            case 2:
                                $goods['introduction'] = $knowlage['knowlage_id'];
                                break;
                            case 3:
                                $goods['talk_skill'] = $knowlage['knowlage_id'];
                                break;
                            }
                        }
                    }
                }
            }
        }

        $smarty->assign('keyword',$keyword);
        $smarty->assign('goods_list',$goods_list);
        $res['main'] = $smarty->fetch('goods_knowlage.htm');

        die($json->encode($res));
    }
}

/*操作数据库*/
elseif($_REQUEST['act'] == 'knowlage_ctr'){

    $behave = trim(mysql_real_escape_string($_REQUEST['behave']));
    $knowlage_id = intval($_REQUEST['knowlage_id']);

    switch($behave){
    case 'read' :
        $knowlage_id   = intval($_REQUEST['id']);
        $knowlage_list = get_knowlage_list($knowlage_id);

        $sql_update = 'UPDATE '.$GLOBALS['ecs']->table('knowlage').
            " SET read_times=read_times+1 WHERE knowlage_id=$knowlage_id";
        $GLOBALS['db']->query($sql_update);

        if($knowlage_list){
            $knowlage_info = $knowlage_list[0];
        }

        $smarty->assign('knowlage_info',$knowlage_info);
        $smarty->display('knowlage_content.htm');
        exit;
        break;
    case 'mod' :
        if($knowlage_id != 0){
            $sql_select = 'SELECT knowlage_id,knowlage_name,g.goods_name,content,knowlage_class FROM '.
                $GLOBALS['ecs']->table('knowlage').' k LEFT JOIN '.$GLOBALS['ecs']->table('goods').
                ' g ON k.goods_sn=g.goods_sn '.
                " WHERE knowlage_id=$knowlage_id";
            $knowlage_info = $GLOBALS['db']->getRow($sql_select);

            $sql_select = 'SELECT tag_name FROM '.$GLOBALS['ecs']->table('knowlage_tags').
                " WHERE knowlage_id=$knowlage_id";

            $tags = $GLOBALS['db']->getCol($sql_select);
            $knowlage_info['tags'] = implode(',',$tags); 

            $knowlage_class_list = get_knowlage_class(0,1);
            $brank_list          = brand_list();

            $smarty->assign('brank_list',$brank_list);
            $smarty->assign('knowlage_class_list',$knowlage_class_list);
            $smarty->assign('knowlage_info',$knowlage_info);
            $smarty->assign('knowlage_id',$knowlage_id);
            $smarty->assign('mod',true);
            $res['behave'] = $behave;

            $res['main'] = $smarty->fetch('add_knowlage.htm');
        }
        break;
    case 'del' :
        $res = array(
            'timeout' => 2000,
            'code'    => false,
            'message' => '',
            'req_msg' => true
        );

        $sql_del = 'DELETE FROM '.$GLOBALS['ecs']->table('knowlage')." WHERE knowlage_id=$knowlage_id";
        $res['code'] = $GLOBALS['db']->query($sql_del);

        $res['message'] = $res['code'] ? '删除成功' : '删除失败';
        break;
    }

    die($json->encode($res));
}

/*从购买历史查看某产品知识*/
elseif($_REQUEST['act'] == 'get_knowlage'){
    $goods_sn = isset($_REQUEST['goods_id']) ? mysql_real_escape_string($_REQUEST['goods_id']) : '';
    $item_name = isset($_REQUEST['item_name']) ? mysql_real_escape_string($_REQUEST['item_name']) : '';

    if(!empty($goods_sn) && !empty($item_name)){
        $sql_select = 'SELECT knowlage_class_name,knowlage_class_id FROM '.$GLOBALS['ecs']->table('knowlage_class').
            " WHERE label='$item_name'";
        $knowlage_class = $GLOBALS['db']->getRow($sql_select);

        $sql_select = "SELECT content,knowlage_name,'{$knowlage_class['knowlage_class_name']}' as knowlage_class_name FROM ".$GLOBALS['ecs']->table('knowlage').
            " WHERE goods_sn='$goods_sn' AND knowlage_class={$knowlage_class['knowlage_class_id']}";
        $knowlage = $GLOBALS['db']->getRow($sql_select);

        $smarty->assign('knowlage',$knowlage);
        $res = array(
            'item_name'  => $item_name,
            'class_name' => $knowlage_class['knowlage_class_name'],
            'knowlage_name' => $knowlage['knowlage_name'],
            'is_exist'   => $knowlage ? 1 : 0,
            'content'    => $smarty->fetch('knowlage_content_div.htm'),
            'goods_sn'   => $goods_sn,
            'content'    => $knowlage['content']
        );
    }

    die($json->encode($res));
}

//学习分享
elseif($_REQUEST['act'] == 'share_knowlage'){

}

//健康知识
elseif($_REQUEST['act'] == 'health_knowlage'){
    setAuthority();
    $smarty->assign('knowlage_div',$smarty->fetch('knowlage_div.htm'));
    $res['main'] = $smarty->fetch('knowlage.htm');
    die($json->encode($res));

}

//销售知识
elseif($_REQUEST['act'] == 'sale_knowalge'){
    $smarty->assign('knowlage_div',$smarty->fetch('knowlage_div.htm'));
    $res['main'] = $smarty->fetch('knowlage.htm');
    die($json->encode($res));
}

//函数区
/*知识分类*/
function get_knowlage_class($parent_class=0,$level=0){
    $where = ' WHERE 1 ';
    if($parent_class > 0){
        $where .= " AND parent_class=$parent_class ";
    }

    if($level > 0){
        $where .= " AND level=$level";
    }

    $sql_select = 'SELECT knowlage_class_id,knowlage_class_name,level,parent_class FROM '.
        $GLOBALS['ecs']->table('knowlage_class').$where;    

    return $GLOBALS['db']->getAll($sql_select);
}

/*获取知识库列表*/
function get_knowlage_list($keyword){
    if(is_string($keyword)){
        $content = "LEFT(k.content,20) content";
        $where = " WHERE k.knowlage_name LIKE '%$keyword%' OR k.content LIKE '%$keyword%' OR tg.tag_name LIKE '%$keyword%' OR g.goods_name LIKE '%$keyword%'";
    }elseif(is_int($keyword)){
        $where = " WHERE k.knowlage_id=$keyword";
        $content = "k.content";
    }

    $sql_select = "SELECT k.knowlage_id,k.knowlage_name,c.knowlage_class_name,tg.tag_name,g.goods_name,$content,k.more_link FROM ".
        $GLOBALS['ecs']->table('knowlage').' k LEFT JOIN '.
        $GLOBALS['ecs']->table('goods').' g ON k.goods_sn=g.goods_sn LEFT JOIN '.
        $GLOBALS['ecs']->table('knowlage_tags').' tg ON tg.goods_sn=k.goods_sn LEFT JOIN '.
        $GLOBALS['ecs']->table('knowlage_class').' c ON c.knowlage_class_id=k.knowlage_class '.$where;

    $knowlage_list = $GLOBALS['db']->getAll($sql_select);
    return $knowlage_list;
}

function setAuthority(){
    global $smarty;
    if(admin_priv('all','',false) || admin_priv('knowlage_mgr','',false)){
        $smarty->assign('knowlage_mgr',true);
    }
}
?>
