<?php
	// 
	//  index.inc.php
	//  <project>
	//  首页商品数据传递加载
	//  Created by Administrator on 2016-04-23.
	//  Copyright 2016 Administrator. All rights reserved.
	// 
	
	global $_GPC,$_W;
	load()->func('communication');
	ihttp_request('http://'.$_SERVER["HTTP_HOST"].'/addons/weliam_indiana/core/api/checkmachine.api.php', array('uniacid' => $_W['uniacid']),array('Content-Type' => 'application/x-www-form-urlencoded'),1);
	$share_data = $this->module['config'];
	$uniacid=$_W['uniacid'];
	$openid = m('user') -> getOpenid();
	/******判定加载页数****/
	$page = !empty($_GPC['page'])?$_GPC['page']:0;
	$pagenum = !empty($_GPC['pagenum'])?$_GPC['pagenum']:2;
	$pagestart = $page * $pagenum;
	/*******判定加载类型和加载条件*******/
	$op = !empty($_GPC['op'])?$_GPC['op']:'go';
	/*************公告加载*************/
	//$notice = pdo_fetchall("SELECT * FROM " . tablename('weliam_indiana_notice') . " WHERE enabled = 1 and uniacid = '{$_W['uniacid']}' ORDER BY id DESC");
	
	
	//$rank='sort desc,scale desc,createtime desc';$condition='';$condition_period='';$rank_period='a.sort desc,a.scale desc,a.createtime desc';
	if($op == 'announce'){
		//最新揭晓商品获取
		/*****最新揭晓商品选择开始*****/
		$announce_num = !empty($_GPC['announce_num'])?$_GPC['announce_num']:8;
		$announce_page = !empty($_GPC['announce_page'])?$_GPC['announce_page']:0;
		$announce_pagestart = $announce_num * $announce_page;
		/*****最新揭晓商品选择结束*****/
		$sql_announce = "select id,goodsid,periods,endtime,avatar,openid,nickname,partakes,code,period_number from".tablename('weliam_indiana_period')."where uniacid = :uniacid and status in(2,3,4,5,6,7) order by endtime desc limit ".$announce_pagestart.",".$announce_num;
		$data_announce = array(
			':uniacid'=>$_W['uniacid']
		);
		$result_announce = pdo_fetchall($sql_announce,$data_announce);
		foreach($result_announce as $key => $value){
			$goods = m('goods')->getGoods($value['goodsid']);
			$result_announce[$key]['picarr'] = tomedia($goods['picarr']);
			$result_announce[$key]['init_money'] = $goods['init_money'];
			$result_announce[$key]['title'] = $goods['title'];
			$result_announce[$key]['nowtime'] = time();
			$result_announce[$key]['change_endtime'] = date('Y-m-d H:i:s',$value['endtime']);
		}
		die(json_encode(array('status'=>1,'result'=>$result_announce)));exit;			//传回返回值
	}
        
        
        
	/******幻灯片开始****/
	$sql_adv = "select * from " . tablename('weliam_indiana_adv') . " where enabled=:enabled and weid= :weid order by displayorder desc";
	$data_adv = array(
		':enabled'=>1,
		':weid'=>$_W['uniacid']
	);
	$advs = pdo_fetchall($sql_adv,$data_adv);
	foreach ($advs as &$adv) {
		if (substr($adv['link'], 0, 5) != 'http:') {
			$adv['link'] = "http://" . $adv['link'];
		}
	}
	unset($adv);
	/******幻灯片结束****/
       
	$mtotal = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_member') . " WHERE uniacid = '{$_W['uniacid']}'");
	$mtotals = str_split($mtotal);
	/*print_r($mtotals);exit;*/
	$navis = pdo_fetchall("SELECT * FROM " . tablename('weliam_indiana_navi') . " WHERE uniacid = '{$_W['uniacid']}' and enabled = 1 ORDER BY displayorder DESC");
	
        /*******获取自定义全部分类开始*******/
        $category = m('category')->getList(array('enabled'=>1,'order'=>'id asc','by'=>'','parentid'=>'0'));
        
        //$goodslist=  fenlei($category[0]['id'],$_W['uniacid']);
        //获取分类商品信息
        foreach ($category as $k=>$v){
            $goodslist[$v['id']]=fenlei($v['id'],$_W['uniacid']);
        }
        /*******获取中奖信息*******/
        $info_zhong = zuixinjiexiao($_W['uniacid']);
        
        //echo '<pre>';var_dump($info_zhong);echo '<pre/>';die();
      
	if($op == 'go'){
                
                
		include $this->template('goods/index');
	}
        
       function fenlei($fl_id,$uniacid){
            
       $rank='sort desc,scale desc,createtime desc';$rank_period='a.sort desc,a.scale desc,a.createtime desc';
       $condition=' and category_parentid='.$fl_id;$condition_period=' and b.category_parentid='.$fl_id;
       
       // $rank='sort desc,scale desc,createtime desc';
        //$condition='';$condition_period='';
        $rank_period='a.sort desc,a.scale desc,a.createtime desc';
        
	$navis = pdo_fetchall("SELECT * FROM " . tablename('weliam_indiana_navi') . " WHERE uniacid = '{$uniacid}' and enabled = 1 ORDER BY displayorder DESC");
	$datam = array(
		':uniacid'=>$uniacid,
		':status'=>2
	);
	$data = array(
		':uniacid'=>$uniacid,
		':status'=>1
	);
	$sql_allgoods = "select * from".tablename('weliam_indiana_goodslist')."where uniacid = :uniacid and status = :status order by ".$rank." limit 0,2";
	//$sql_allperiod = "select scale,period_number,canyurenshu,shengyu_codes,zong_codes,periods,goodsid from".tablename('weliam_indiana_period')."where uniacid = :uniacid and status = :status order by ".$rank." limit ".$pagestart.",".$pagenum;
	
        $sql_allperiod_1 = "select a.scale,a.period_number,a.canyurenshu,a.shengyu_codes,a.zong_codes,a.periods,a.goodsid,b.merchant_id,b.title,picarr,b.init_money from".tablename('weliam_indiana_period')." a,".tablename('weliam_indiana_goodslist')." b  where a.goodsid = b.id  ".$condition_search_period." and a.uniacid = :uniacid and a.status = :status ".$condition_period." order by ".$rank_period." limit 0,2";
	
        $goodslist = pdo_fetchall($sql_allperiod_1,$data);
            foreach($goodslist as $key => $value){
                    $goodslist[$key]['picarr'] = tomedia($value['picarr']);
                    $goodslist[$key]['id'] = $value['goodsid'];
                    $goodslist[$key]['merchant_id'] = $value['merchant_id'];
                    $goodslist[$key]['title'] = $value['title'];
                    $goodslist[$key]['init_money'] = $value['init_money'];
            }
            return $goodslist;
        }
        
        function zuixinjiexiao($uniacid){
		//最新揭晓商品获取
		/*****最新揭晓商品选择开始*****/
		$announce_num = 8;
		$announce_page = 0;
		$announce_pagestart = $announce_num * $announce_page;
		/*****最新揭晓商品选择结束*****/
		$sql_announce = "select id,goodsid,periods,openid,endtime,nickname,period_number from".tablename('weliam_indiana_period')."where uniacid = :uniacid and status in(2,3,4,5,6,7) order by endtime desc limit ".$announce_pagestart.",".$announce_num;
		$data_announce = array(
			':uniacid'=>$uniacid
		);
		$result_announce = pdo_fetchall($sql_announce,$data_announce);
		foreach($result_announce as $key => $value){
			$goods = m('goods')->getGoods($value['goodsid']);
			//$result_announce[$key]['picarr'] = tomedia($goods['picarr']);
			$result_announce[$key]['init_money'] = $goods['init_money'];
			$result_announce[$key]['title'] = $goods['title'];
			$result_announce[$key]['change_endtime'] = date('Y-m-d H:i:s',$value['endtime']);
		}
		return $result_announce;			//传回返回值
	}
?>