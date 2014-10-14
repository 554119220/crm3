<?php
define('IN_ECS',true);
require(dirname(__FILE__) . '/includes/init.php');

$link = mysql_connect('localhost','root','');
if($link){
    mysql_select_db('information_schema') or die('select db error');
    $sql_select = "SELECT TABLE_NAME,COLUMN_NAME,COLUMN_DEFAULT,COLLATION_NAME,COLUMN_TYPE,COLUMN_KEY,EXTRA,IS_NULLABLE FROM COLUMNS WHERE TABLE_SCHEMA='kjrs_crm2'";

    $columns_list = mysql_query($sql_select,$link);

    $sql_select = " SELECT TABLE_NAME FROM COLUMNS WHERE TABLE_SCHEMA='kjrs_crm2' GROUP BY TABLE_NAME";
    $table_list = mysql_query($sql_select);

    while($row = mysql_fetch_array($table_list)){
       $table[]['TABLE_NAME'] = $row['TABLE_NAME']; 
    }

    while($row = mysql_fetch_assoc($columns_list)){
        $column[] = $row;
    }

    foreach($table as &$tb){
       foreach($column as &$col){
           if ($tb['TABLE_NAME'] == $col['TABLE_NAME']) {
               $tb['column'][] = $col;
           } 
       } 
    }

    //echo "<pre>";
    //print_r($table);exit;

    $smarty->assign('table_list',$table);
    $smarty->display('table_column.htm');

}else{
    die('link error');
}
?>
