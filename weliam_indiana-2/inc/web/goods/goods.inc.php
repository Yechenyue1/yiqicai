<?php
	global $_W,$_GPC;
	load() -> func('tpl');
	$ops = array('display', 'edit', 'delete','wdisplay','recover','regain','shiftdelete','machine','persondownload','del','daoru','xiazai'); // 只支持此 3 种操作.
	$op = in_array($_GPC['op'], $ops) ? $_GPC['op'] : 'display';
	$args = array('enabled' => 1, 'order' => 'displayorder desc', 'by' => '','parentid'=>'0');
	$category = m('category')->getList($args);
	if(empty($category)){
		message("暂无商品,请先添加分类",$this->createWebUrl('category'));exit;
	}
	$childs = array();
	foreach($category as $key=>$value){
		$category_childs = pdo_fetchall("SELECT * FROM " . tablename('weliam_indiana_category') . " WHERE uniacid = '{$_W['uniacid']}' and parentid={$value['id']} and enabled=1 ORDER BY displayorder DESC");
		$childs[$value['id']] = $category_childs;
	}
	
	if($op == 'display'){
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$category2 = $_GPC['category'];
		$title = $_GPC['title'];
		$time = $_GPC['time'];
		$condition = '';
		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}
		if (!empty($_GPC['time'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']) ;
			$condition .= " AND  createtime >= '{$starttime}' AND  createtime <= '{$endtime}' and status > 0";
		}
		if ($title!='') {
			$condition .= " AND  title LIKE '%{$_GPC['title']}%'";
		}
		if(!empty($category2['parentid'])){
			$condition .= " AND  category_parentid = '{$category2['parentid']}' ";
			
		}
		if(!empty($category2['childid'])){
			$condition .= " AND  category_childid = '{$category2['childid']}' ";
		}
		
		$goodses = pdo_fetchall("select * from".tablename('weliam_indiana_goodslist')."where uniacid={$_W['uniacid']}  $condition and status>0 order by sort desc , id asc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_goodslist') . " WHERE uniacid = '{$_W['uniacid']}'".$condition);
		$pager = pagination($total, $pindex, $psize);
		foreach ($goodses as $key => $value) {
			$merchant = pdo_fetch("SELECT name FROM ".tablename('weliam_indiana_merchant')." WHERE uniacid = {$_W['uniacid']} and id='{$value['merchant_id']}' "); 
			$goodses[$key]['merchant'] = $merchant['name'];
		}
		
		include $this->template('goods_display');
	}
	//商品编辑
	if ($op == 'edit') {
		$id = intval($_GPC['id']);
		$merchants = pdo_fetchall("SELECT * FROM ".tablename('weliam_indiana_merchant')." WHERE uniacid = {$_W['uniacid']} ORDER BY id DESC "); 
		$child = array();
		foreach($merchants as $k=>$v){
			$merchants_childs = pdo_fetchall("SELECT * FROM ".tablename('weliam_indiana_coupon')." WHERE uniacid = {$_W['uniacid']} and merchantid = {$v['id']}  ORDER BY couponid DESC ");
			$child[$v['id']] = $merchants_childs;
		}
		if(!empty($id)){
			$goods = m('goods')->getGoods($id);
			$sql = "SELECT code FROM " . tablename('weliam_indiana_period') . " where goodsid='{$goods['id']}' and periods={$goods['periods']}";
			$period = pdo_fetch($sql, $params);
			$goods['code'] = $period['code'];
			$a = unserialize($goods['automatic']);
			$goods['select_automatic'] = $a['select'];
			$goods['automatic'] = $a['num'];
			if(empty($goods)){
				message('未找到指定的商品.', $this->createWebUrl('goods'));
			}
			//获取当前图集
			$listt = pdo_fetchall("SELECT * FROM" . tablename('weliam_indiana_goods_atlas') .  "WHERE g_id = '{$id}' ");
			$piclist = array();
			if(is_array($listt)){
				foreach($listt as $p){
					$piclist[] = $p['thumb'];
				}
			}
		}else{
			if(WELIAM_INDIANA_VERSION == 'special'){
				if(empty($merchants)){
				message("暂无商家,请先添加商家",$this->createWebUrl('merchant'));exit;
				}
			}			
		}
		
		if (checksubmit()) {
			$data = $_GPC['goods']; // 获取打包值
			$images = $_GPC['img'];//获取图集
			$data['content'] = htmlspecialchars_decode($data['content']);
			$data['category_parentid'] = $_GPC['category']['parentid'];
			$data['category_childid'] = $_GPC['category']['childid'];
			$data['merchant_id'] = $_GPC['mcid']['parentid'];
			$data['couponid'] = $_GPC['mcid']['childid'];
			if($data['select_automatic'] == 2 || $data['select_automatic'] == 3){
				$data['automatic'] = array(
					'select' => $data['select_automatic'],
					'num' => $data['automatic']
				);
				$data['automatic'] = serialize($data['automatic']);
			}elseif($data['select_automatic'] == 1){
				$data['automatic'] = '';
			}else{
				unset($data['automatic']);
			}
			unset($data['select_automatic']);
			
			if(empty($goods)){
				$data['uniacid'] = $_W['uniacid'];
				$data['createtime'] = TIMESTAMP;
				$data['periods'] = 0;
				//echo '<pre>';var_dump($data); var_dump($images);echo '</pre>';die();
                                
				$ret = pdo_insert('weliam_indiana_goodslist', $data);
				if (!empty($ret)) {
					$id = pdo_insertid();
					if($images){
	                	foreach ($images as $key => $value){
	                    	$datam = array(
	                        	'thumb' => $images[$key], 
	                        	'g_id' => $id
	                        );
	                    	$ret = pdo_insert('weliam_indiana_goods_atlas',$datam);
	                	}
	            	}
				}
				//第一期夺宝码计算
				$period_number = m('codes')->create_newgoods($id);
			} else {
				if($_GPC['code']){
					$code = intval($_GPC['code']);
					$maxcode = 1000000+intval($_GPC['maxcode']);
					if($code >= 1000001 && $code <= $maxcode){
						$ret = pdo_update('weliam_indiana_period', array('code'=>$_GPC['code']), array('goodsid'=>$id,'periods'=>$_GPC['periods']));
					}
				}
				
				$datam['category_parentid'] = $_GPC['category']['parentid'];
				$datam['category_childid'] = $_GPC['category']['childid'];
				$datam['next_init_money'] = $data['init_money'];
				$datam['sort'] = $data['sort'];
				$datam['title'] = $data['title'];
				$datam['jiexiao_time'] = $data['jiexiao_time'];
				$datam['maxperiods'] = $data['maxperiods'];
				$datam['content'] = $data['content'];
				$datam['picarr'] = $data['picarr'];
				$datam['maxnum'] = $data['maxnum'];
				$datam['automatic'] = $data['automatic'];
				$datam['is_alert'] = $data['is_alert'];
				$datam['is_alone'] = $data['is_alone'];
				$datam['aloneprice'] = $data['aloneprice'];
				$ret = pdo_update('weliam_indiana_goodslist', $datam, array('id'=>$id));
				pdo_update('weliam_indiana_period',array('sort'=>$datam['sort']),array('goodsid'=>$id,'status'=>1));//修改进行期商品
				if($images){
					pdo_delete('weliam_indiana_goods_atlas',  array('g_id' => $id));
				    foreach ($images as $key => $value){
				        $datam1 = array(
				            'thumb' => $images[$key], 
				            'g_id' => $id
				            );
				        $ret = pdo_insert('weliam_indiana_goods_atlas',$datam1);
				    }
				}
			}
			
			message('商品信息保存成功', $this->createWebUrl('goods'), 'success');
		}
		
		include $this->template('goods_edit');
	}
	
	if($op == 'delete') {
		$id = intval($_GPC['id']);
		if(empty($id)){
			message('未找到指定商品分类');
		}
		$result = pdo_update('weliam_indiana_goodslist', array('status'=> 0), array('id'=>$id, 'uniacid'=>$_W['uniacid']));
		pdo_update("weliam_indiana_period",array('status'=> 0),array('goodsid'=>$id,'uniacid'=>$_W['uniacid'],'status'=>1));
		if(intval($result) == 1){
			message('删除商品成功.', $this->createWebUrl('goods'), 'success');
		} else {
			message('删除商品失败.');
		}
	}
	
	//商品回收站
	if($op == 'recover'){
		$pindex = max(1, intval($_GPC['page']));
		$psize = 15;
		$category2 = $_GPC['category'];
		$title = $_GPC['title'];
		$time = $_GPC['time'];
		$condition = '';
		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}
		if (!empty($_GPC['time'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']) ;
			//message(date('Y-m-d H:i:s', $endtime)."==".date('Y-m-d H:i:s', time()));exit;
			$condition .= " AND  createtime >= '{$starttime}' AND  createtime <= '{$endtime}' and status = 0";
		}
		if ($title!='') {
			$condition .= " AND  title LIKE '%{$_GPC['title']}%'";
		}
		if(!empty($category2['parentid'])){
			$condition .= " AND  category_parentid = '{$category2['parentid']}' ";
			
		}
		if(!empty($category2['childid'])){
			$condition .= " AND  category_childid = '{$category2['childid']}' ";
		}
		
		$goodses = pdo_fetchall("select * from".tablename('weliam_indiana_goodslist')."where uniacid={$_W['uniacid']}  $condition and status=0 order by id asc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('weliam_indiana_goodslist') . " WHERE uniacid = '{$_W['uniacid']}'  $condition  and status=0");
		$pager = pagination($total, $pindex, $psize);
		foreach ($goodses as $key => $value) {
			$merchant = pdo_fetch("SELECT name FROM ".tablename('weliam_indiana_merchant')." WHERE uniacid = {$_W['uniacid']} and id='{$value['merchant_id']}' "); 
			$goodses[$key]['merchant'] = $merchant['name'];
		}
		
		include $this->template('goods_display');
	}

	//恢复商品
	if($op == 'regain') {
		$id = intval($_GPC['id']);
		if(empty($id)){
			message('未找到指定商品分类');
		}
		$result = pdo_update('weliam_indiana_goodslist', array('status'=> 1), array('id'=>$id, 'uniacid'=>$_W['uniacid']));
		pdo_update("weliam_indiana_period",array('status'=> 1),array('goodsid'=>$id,'uniacid'=>$_W['uniacid'],'status'=>0));
		if(intval($result) == 1){
			message('恢复商品成功.', $this->createWebUrl('goods'), 'success');
		} else {
			message('恢复商品失败.');
		}
	}
	
	//彻底删除商品
	if($op == 'shiftdelete') {
		$id = intval($_GPC['id']);
		if(empty($id)){
			message('未找到指定商品分类');
		}
		//删除晒单信息
		pdo_delete('weliam_indiana_showprize', array('goodsid'=>$id));
		//删除物品幻灯片信息
		pdo_delete('weliam_indiana_goods_atlas', array('g_id'=>$id));
		//删除所有期物品信息
		pdo_delete('weliam_indiana_period', array('goodsid'=>$id));
		//删除商品支付记录信息
		pdo_delete('weliam_indiana_record', array('goodsid'=>$id));
		//删除物品信息
		$result = pdo_delete('weliam_indiana_goodslist' ,array('id'=>$id));
		
		if(empty($result)){
			message('彻底删除商品失败.');
		}else{
			message('彻底删除商品成功.数据不可恢复', $this->createWebUrl('goods'), 'success');
		}
		
		
	}
	if($op == 'machine') {
		$data = m('order')->randnum(10,3,10);
		echo "<pre>";print_r($data);exit;
		$url='../addons/weliam_indiana/static/head_imgs'; //放图片的文件夹路径名称
		$id = intval($_GPC['goodsid']);
		$periods = intval($_GPC['periods']);
		$goods = pdo_fetch("select * from".tablename('weliam_indiana_period')."where uniacid={$_W['uniacid']}  and goodsid={$id} and periods={$periods}");
		if (checksubmit('sure')) {
//			message("xxx");exit;
			$code_num = $_GPC['code_num'];
			$limit_num = $_GPC['limit_num'];
			$data = m('order')->randnum($code_num,$limit_num);
			$head_imgs_array = $getheadimgs->get_head_img($url, $lack);		
			message('修改成功！', referer(), 'success');
		}
		include $this->template('goods_machine');
	}
	
	if($op == 'del'){
		$time = time()-2592000;
		$result = pdo_fetchall("select * from".tablename('weliam_indiana_consumerecord')."where uniacid = '{$_W['uniacid']}' and num<0 and createtime>'{$time}' and status = 1");
		foreach($result as $key=>$value){
			$list = pdo_fetch("select shengyu_codes,canyurenshu from".tablename('weliam_indiana_period')."where uniacid = '{$_W['uniacid']}' and period_number = '{$value['period_number']}'");
			$data['shengyu_codes'] = $list['shengyu_codes'] - $value['num'];
			$data['canyurenshu'] = $list['canyurenshu'] + $value['num'];
			pdo_update('weliam_indiana_period',$data,array('uniacid'=>$_W['uniacid'],'period_number'=>$value['period_number']));
		}
		message('修改成功',referer(),'success');
	}
        //导入
        if($op == 'daoru'){
                $file = $_FILES['file'];
                $max_size = "2000000";
                $fname = $file['name'];
                $ftype = strtolower(substr(strrchr($fname, '.'), 1));
                //文件格式
                $all_number = 0;		//统计总导入数量
                $success_number = 0;	//统计成功数量
                $error_number = 0;		//统计失败数量

                $uploadfile = $file['tmp_name'];
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        if (is_uploaded_file($uploadfile)) {
                                if ($file['size'] > $max_size) {
                                        message("导入文件过大!",referer(),'error');
                                        exit ;
                                }
                                if ($ftype == 'xls') {
                                        //导入xls文件
                                        require_once '../framework/library/phpexcel/PHPExcel.php';
                                        $objReader = PHPExcel_IOFactory::createReader('Excel5');
                                        $objPHPExcel = $objReader -> load($uploadfile);
                                        $sheet = $objPHPExcel -> getSheet(0);
                                        $highestRow = $sheet -> getHighestRow();
                                        $data=array(
                                        "sort"=> "", //商品排序
                                        "picarr"=>"images/1.jpg",
                                        "init_num"=>"11",  //多少人
                                        "is_alert"=>"2",  //发通知
                                        "status"=> "2",  //0删除1下架2上架
                                        "maxnum"=>"1",  //最大购买数量
                                        "maxperiods"=>"1",  // 最大期数
                                        "jiexiao_time"=>"1", //多少分钟后揭晓（整数）
                                        "content"=> "",  //商品内容
                                        "category_childid"=> "0", //商品分类二级
                                        "merchant_id"=>NULL, //所属商家ID
                                        "couponid"=>NULL,  //优惠卷ID
                                        "uniacid"=>$_W['uniacid'],  //公众号标志
                                        "periods"=>0,
                                        );
                                        $images=NULL;
                                        for ($j = 2; $j <= $highestRow; $j++) {
                                                $data['title'] = trim($objPHPExcel -> getActiveSheet() -> getCell("A$j") -> getValue());
                                                $data['init_money'] = trim($objPHPExcel -> getActiveSheet() -> getCell("B$j") -> getValue());
                                                $data['num_min'] =  trim($objPHPExcel -> getActiveSheet() -> getCell("C$j") -> getValue());
                                                $data['num_max'] = trim($objPHPExcel -> getActiveSheet() -> getCell("D$j") -> getValue());
                                                $data['automatic'] = array(
                                                    'select' => '3',
                                                    'num' => sprintf("%d",intval($data['init_money'])*10)
                                                );
                                                $data['automatic'] = serialize($data['automatic']);
                                                $data['createtime'] =time();
                                                $data['price'] = sprintf("%d",mt_rand(intval($data['num_min'])+8,intval($data['num_max'])-8));
                                                switch ($data['init_money']){
                                                    case "1":$data['category_parentid']='99'; break;
                                                    case "10":$data['category_parentid']='100'; break;
                                                    case "50":$data['category_parentid']='101'; break;
                                                    case "100":$data['category_parentid']='102'; break;
                                                    default:$data['category_parentid']='99'; break;
                                                }
                                                $result = pdo_insert('weliam_indiana_goodslist', $data);
                                                //echo '<pre>';var_dump($data);var_dump($images);echo '</pre>';die();
                                                if(!empty($result)){
                                                        $id = pdo_insertid();
                                                        $period_number = m('codes')->create_newgoods($id);
                                                        if($images){
                                                            foreach ($images as $key => $value){
                                                                $datam = array(
                                                                        'thumb' => $images[$key], 
                                                                        'g_id' => $id
                                                                );
                                                                $ret = pdo_insert('weliam_indiana_goods_atlas',$datam);
                                                            }
                                                        }
                                                }
                                        }
                                        fclose($handle); //关闭指针
                                        message("导入成功！", $this->createWebUrl('goods'), 'success');
                                }else{
                                        message("文件后缀格式必须为xls或者csv!",referer(),'success');
                                        exit ;
                                }
                        } else {
                                message("文件不能为空!",referer(),'error');
                                exit ;
                        }
                }
        }
        //下载
        if($op == 'xiazai'){
            //下载名称模板
            $filename=realpath("../addons/weliam_indiana/static/download/shangpindaoru.xls"); //文件名 
            Header( "Content-type:  application/octet-stream ");  
            Header( "Accept-Ranges:  bytes "); 
            Header( "Accept-Length: " .filesize($filename)); 
            header( "Content-Disposition:  attachment;  filename= shangpindaoru.xls");  
            echo file_get_contents($filename); 
            readfile($filename); 
        }
?>