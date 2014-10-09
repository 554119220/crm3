function updateOrderPlatform(obj){
  var orderSn = obj.elements['order_sn'].value;
  var platform = obj.elements['platform'].value;

  if(orderSn && platform){
    Ajax.call('order.php?act=update_order_form_done','order_sn='+orderSn+'&platform='+platform,showMsg,'GET','JSON');
  }
}
