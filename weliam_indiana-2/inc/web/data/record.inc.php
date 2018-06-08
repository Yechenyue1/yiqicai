<?php
global $_W,$_GPC;
$op = !empty($_GPC['op'])?$_GPC['op']:'display';
$uniacid = $_W['uniacid'];
$condition = '';
if (empty($starttime) || empty($endtime)) {
	$starttime = strtotime(date('Y-m-d'));
	$endtime = strtotime(date('Y-m-d',strtotime('+1 day')));
}
if (!empty($_GPC['time'])) {
	$starttime = strtotime($_GPC['time']['start']);
	$endtime = strtotime($_GPC['time']['end']) ;
	$condition .= " AND  createtime >= '{$starttime}' AND  createtime <= '{$endtime}' ";
        $condition1 .= " AND  endtime >= '{$starttime}' AND  endtime <= '{$endtime}' ";
}
//交易记录	
if($op=='display'){
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	
	$type = $_GPC['type'];
	switch($type){
		case 'person' : $condition_type =  "and openid not like '%machine%'";break;
		case 'machine' : $condition_type =  "and openid like '%machine%'";break;
		default : $condition_type = '';
	}
	
	$goodses = pdo_fetchall("SELECT * FROM ".tablename('weliam_indiana_consumerecord')." WHERE uniacid = '{$uniacid}' and status = 1 $condition $condition_type ORDER BY id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
	foreach($goodses as$key=>$value){
		$period = pdo_fetch("SELECT goodsid,periods FROM " . tablename('weliam_indiana_period') . " where period_number = '{$value['period_number']}'");
		$goods = pdo_fetch("SELECT title,init_money,picarr FROM " . tablename('weliam_indiana_goodslist') . " where id = '{$period['goodsid']}'");
		$info = m('member')->getInfoByOpenid($value['openid']);
		$goodses[$key]['title'] = $goods['title'];
		$goodses[$key]['init_money'] = $goods['init_money'];
                $goodses[$key]['picarr'] = $goods['picarr'];
		$goodses[$key]['periods'] = $period['periods'];
		$goodses[$key]['avatar'] = $info['avatar'];
		$goodses[$key]['nickname'] = $info['nickname'];
	}
	
	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_consumerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition ");
	$pager = pagination($total, $pindex, $psize);
	
	include $this->template('records');
}
//充值记录
if($op=='recharge'){
	$uniacid = $_W['uniacid'];
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	
	$goodses = pdo_fetchall("select * from".tablename('weliam_indiana_rechargerecord')."where status = 1 and uniacid = '{$_W['uniacid']}' $condition order by id desc limit ".($pindex - 1) * $psize.','.$psize);
	foreach($goodses as$key=>$value){
		$info = m('member')->getInfoByOpenid($value['openid']);
		$goodses[$key]['avatar'] = $info['avatar'];
		$goodses[$key]['nickname'] = $info['nickname'];
	}

	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_rechargerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition ");
	$pager = pagination($total, $pindex, $psize);

	include $this->template('recharge');
}
//统计
if($op=='statistics'){
	$uniacid = $_W['uniacid'];
        $data = array();
        
        if(empty($condition)){
            $condition = '';
            
            $data[0]=statistics($uniacid,$condition,$condition1);
            $data[0]['yuefen']='总';
            //从一月到当期月的数据
            for($i=1;$i<=intval(date('n'));$i++){
                $date=getthemonth(date("Y-$i"));
                $starttime = strtotime($date['0']);
                $endtime = strtotime($date['1']) ;
                $condition = " AND  createtime >= '{$starttime}' AND  createtime <= '{$endtime}' ";
                $condition1 = " AND  endtime >= '{$starttime}' AND  endtime <= '{$endtime}' ";
                $data[$i]=statistics($uniacid,$condition,$condition1);
                $data[$i]['yuefen'] = $i."月";
           }
        }else{
            $data[0]=statistics($uniacid, $condition,$condition1);
            $data[0]['yuefen']='搜索';
        }
        //echo '<pre>';var_dump($data);echo '</pre>';die();
//	
//        //交易数据
//        $all_num = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_consumerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition ");//总交易数
//        $ture_data = pdo_fetchall("SELECT openid,num FROM ".tablename('weliam_indiana_consumerecord')." WHERE uniacid = '{$uniacid}' and status = 1 $condition ");//交易数据
//        $ture_num = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_consumerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition and openid not like 'machine%'"); //真实用户交易数
//        $robot_num= pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_consumerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition and openid like 'machine%'"); //机器人交易数
//        foreach($ture_data as $value){
//            if(strpos($value['openid'],'machine') !== false){
//                $robot_money += $value['num'];  //机器金额
//            }else{
//                $ture_money += $value['num'];  //用户金额
//            }
//            $all_money+= $value['num']; //总交易金额
//        }
//        if(empty($ture_money)){
//            $ture_money=0;
//        }
//        if(empty($robot_money)){
//            $robot_money=0;
//        }
//        if(empty($robot_money)){
//            $all_money=0;
//        }
//        //充值数据
//	$re_all_data = pdo_fetchall("select num from".tablename('weliam_indiana_rechargerecord')."where status = 1 and uniacid = '{$uniacid}' $condition ");      //充值数据  
//	foreach($re_all_data as $value){
//		$order_money += $value['num'];
//	}
//        if(empty($order_money)){
//            $order_money=0; //充值总数
//        }
//	$re_all_num = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_rechargerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition ");//总充值数
//        //中奖数据
//        $win_all_num = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_period') . " WHERE uniacid = '{$uniacid}' and status in(3,6,7) $condition ");//总中奖数
//        $win_ture_num = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_period') . " WHERE uniacid = '{$uniacid}' and status in(6,7) $condition and openid not like 'machine%'");//用户中奖数
//        $win_robot_num = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_period') . " WHERE uniacid = '{$uniacid}' and status = 3 $condition and openid like 'machine%'");//机器中奖数
//        $win_data = pdo_fetchall("select openid,goodsid,period_number from".tablename('weliam_indiana_period')."where status in(3,6,7) and uniacid = '{$uniacid}' $condition ");
//        foreach($win_data as $value){
//                $goods = m('goods')->getGoods($value['goodsid']);
//                $win_num = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_consumerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 and period_number='{$value['period_number']}' $condition and openid not like 'machine%'");
//		if(strpos($value['openid'],'machine') !== false){
//                    $win_robot_money += $goods['init_money']*$win_num;  //获取的金额
//                }else{
//                    $win_ture_money += $goods['init_money']*10;  //用户金额
//                }
//                $win_all_money+= $goods['init_money']*$win_num; //基本金额
//	}
//        if(empty($win_robot_money)){
//            $win_robot_money=0;
//        }
//        if(empty($win_ture_money)){
//            $win_ture_money=0;
//        }
//        if(empty($win_all_money)){
//            $win_all_money=0;
//        }
        
	include $this->template('statistics');
}
//全部统计
function statistics($uniacid,$condition,$condition1){
         $data = array();
        //交易数据
        $data['all_num'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_consumerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition ");//总交易数
        $ture_data = pdo_fetchall("SELECT openid,num FROM ".tablename('weliam_indiana_consumerecord')." WHERE uniacid = '{$uniacid}' and status = 1 $condition ");//交易数据
        $data['ture_num'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_consumerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition and openid not like 'machine%'"); //真实用户交易数
        $data['robot_num']= pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_consumerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition and openid like 'machine%'"); //机器人交易数
        foreach($ture_data as $value){
            if(strpos($value['openid'],'machine') !== false){
                $robot_money += $value['num'];  //机器金额
            }else{
                $ture_money += $value['num'];  //用户金额
            }
            $all_money+= $value['num']; //总交易金额
        }
        if(empty($ture_money)){
            $ture_money=0;
        }
        if(empty($robot_money)){
            $robot_money=0;
        }
        if(empty($all_money)){
            $all_money=0;
        }
        $data['ture_money'] = $ture_money;
        $data['robot_money'] = $robot_money;
        $data['all_money'] = $all_money;
        
        //充值数据
	$re_all_data = pdo_fetchall("select num from".tablename('weliam_indiana_rechargerecord')."where status = 1 and uniacid = '{$uniacid}' $condition ");      //充值数据  
	foreach($re_all_data as $value){
		$order_money += $value['num'];
	}
        if(empty($order_money)){
            $order_money=0; //充值总数
        }
	$data['re_all_num'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_rechargerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 $condition ");//总充值数
        $data['order_money'] = $order_money;
        
        //中奖数据
        $data['win_all_num'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_period') . " WHERE uniacid = '{$uniacid}' and status in(3,6,7) $condition1 ");//总中奖数
        $data['win_ture_num'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_period') . " WHERE uniacid = '{$uniacid}' and status in(6,7) $condition1 and openid not like 'machine%'");//用户中奖数
        $data['win_robot_num'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_period') . " WHERE uniacid = '{$uniacid}' and status = 3 $condition1 and openid like 'machine%'");//机器中奖数
        $win_data = pdo_fetchall("select openid,goodsid,period_number from".tablename('weliam_indiana_period')."where status in(3,6,7) and uniacid = '{$uniacid}' $condition1");
        foreach($win_data as $value){
                $goods = m('goods')->getGoods($value['goodsid']);
                $win_num = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_consumerecord') . " WHERE uniacid = '{$uniacid}' and status = 1 and period_number='{$value['period_number']}' $condition and openid not like 'machine%'");
		
                if(strpos($value['openid'],'machine') !== false){
                    $win_robot_money += $goods['init_money']*$win_num;  //获取的金额
                }else{
                    $win_ture_money += $goods['init_money']*10;  //用户金额
                }
                $win_all_money+= $goods['init_money']*$win_num; //基本金额
	}
        if(empty($win_robot_money)){
            $win_robot_money=0;
        }
        if(empty($win_ture_money)){
            $win_ture_money=0;
        }
        if(empty($win_all_money)){
            $win_all_money=0;
        }
        $data['win_robot_money'] = $win_robot_money;
        $data['win_ture_money'] = $win_ture_money;
        $data['win_all_money'] = $win_all_money;
        return $data;
}
//获取每个月的第一天和最后一天
function getthemonth($date){
        $firstday = date('Y-m-01 00:00:00', strtotime($date));
        $lastday = date('Y-m-d 23:59:59', strtotime("$firstday +1 month -1 day"));
        return array($firstday, $lastday);
}
