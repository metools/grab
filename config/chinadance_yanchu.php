<?php
/**
 * @option:	配置信息
 * @author:	bishenghua
 * @date:	2013/10/16
 * @email:	bsh@ojo.cc
 */

return array(
		'database'					=> '127.0.0.1|root||grab', //数据库设置
		'database_dz'				=> '127.0.0.1|root||discuzx|pre_', //discuz数据库设置
		'dz_root'					=> '/Users/wyq/Documents/www/bbs', //discuz根目录
		
		'pagenum' 					=> 10, // 取10页内容
		'url' 						=> 'http://www.chinadance.cn/news/yanchu/index.php?page=[]', //列表页地址及规则
		'topic_pattern'				=> '<h1 class="xs2">[]</h1>', //分类名称规则
		'content_pattern'			=> '<div class="bm_c xld">[]<div id="listloopbottom" class="area">', //内容开始结束规则
		'content_pattern_ignore'	=> '<script>[]</script>|<abcd>[]</abcd>', //文章内容过滤规则
		'title_and_link_pattern'	=> '<dt class="xs2">[]</dt>', //标题和链接规则
		'description_pattern' 		=> '<dd class="xs2 cl">[]</dd>', //文章描述规则
		'datetime_pattern'			=> '<span class="xg1">[]</span>', //文章日期规则
		
		//内容详细信息
		'content_detail_pattern'	=> '<td id="article_content">[]</td></tr></table>', //文章内容规则
		
		
);