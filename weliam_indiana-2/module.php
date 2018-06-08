<?php
/**
 * 竞猜模块定义
 *
 * @author 微连科技
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');
class weliam_indianaModule extends WeModule {
	public function welcomeDisplay()
	{
		header("Location: index.php?c=site&a=entry&m=weliam_indiana&do=setting&op=display");
		exit();
	}
}