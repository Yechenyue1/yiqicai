<?php
	global $_W,$_GPC;
	$pnum = $_GPC['pnum'];
	$openid = !empty($_GPC['openid'])?$_GPC['openid']:m('user') -> getOpenid();
	$period = pdo_fetch("SELECT goodsid,get_code,count FROM " . tablename('weliam_indiana_record') . " WHERE uniacid = '{$_W['uniacid']}' and openid ='{$openid}' and period_number = '{$pnum}'");
	$goods=pdo_fetch("SELECT title FROM " . tablename('weliam_indiana_goodslist') . " WHERE uniacid = '{$_W['uniacid']}' and id ='{$period['goodsid']}'");
	$codes = '';
	$pcodes = $period['get_code'];
        $codes = '<div class="item">'.$pcodes.'</div>';
        $style = 'style="left: 14px; top: 20%;"';
//	foreach ($pcodes as $value) {
//		
//	}
//	if(count($pcodes) >= 50){
//		$style = 'style = "top: 10px; position: absolute;"';
//	}else{
//		$style = 'style="left: 14px; top: 20%;"';
//	}
	$result = '<div class="pro-mask" id="pro-view-42"></div><div class="w-msgbox" id="pro-view-40" '.$style.'><div id="close" class="w-msgbox-close">×</div><div class="w-msgbox-bd"><h3 class="w-msgbox-title"></h3><div class="m-user-codeMsgbox"><div class="title">'.$goods['title'].'</div><p>竞猜价格为：</p><div class="list">'.$codes.'</div></div></div><div data-pro="footer" class="w-msgbox-ft w-msgbox-ft-1"><button class="pro-btn" type="button" id="confirmbut"><span>确定</span></button></div></div>';
	echo $result;
?>