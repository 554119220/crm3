<?php
define('IN_ESC',true);
require(dirname(__FILE__) . '/includes/init.php');

$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) ? 'index';

if($act == 'index'){
    
}

elseif($act == 'add'){
    
}

/*添加操作记录*/
function add_action_log($doing){
    if($doing){
        
    }
}

?>
