function getUserInventoryOrder(user_id,type)
{
  Ajax.call('order.php?act=get_user_inventory_order','user_id='+user_id+'&type='+type,getUserInventoryOrderRes,'GET','JSON');

}

function getUserInventoryOrderRes(res)
{
  document.getElementById('pop_ups').innerHTML = res.info;
  //document.getElementById('fade').style.display = 'block';
  document.getElementById('div_pop_ups').style.display = 'block';
}
