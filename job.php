<?php
/**
 * @option:	[抓取job] 每个配置文件对应一个job,系统会启动为其启动一个单独的进程去处理
 * @author:	bishenghua
 * @date:	2013/10/16
 * @email:	bsh@ojo.cc
 */

define('ROOT', dirname(__FILE__));

error_reporting(E_ALL);ini_set('display_errors', 'On');
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');

//每个配置文件对应一个job,表示抓取的是某个网站的内容
$configName = $argv[1];

require_once ROOT.'/lib/Html.php';
require_once ROOT.'/lib/Parse.php';
require_once ROOT.'/lib/Option.php';
require_once ROOT.'/lib/ImportDiscuz.php';
$config = require_once ROOT."/config/{$configName}.php";

//列表页地址
$url = $config['url'];

//实例化
$htmlIns = new Html();
$parseIns = new Parse();
$dbconf = explode('|', $config['database']);
$optionIns = new Option($dbconf[0], $dbconf[1], $dbconf[2], $dbconf[3]);

//设置规则
$parseIns->setTopicPattern($config['topic_pattern']);
$parseIns->setContentPattern($config['content_pattern']);
$parseIns->setContentPatternIgnore($config['content_pattern_ignore']);
$parseIns->setTitleAndLinkPattern($config['title_and_link_pattern']);
$parseIns->setDescriptionPattern($config['description_pattern']);
$parseIns->setDatetimePattern($config['datetime_pattern']);
$parseIns->setContentDetailPattern($config['content_detail_pattern']);

echo getDatetime()."--抓取进程[开始],进程名称:{$configName}…………………………………………………………\n\n\n";

//抓取文章列表信息
echo getDatetime()."--抓取[文章列表]信息[开始]…………………………………………………………\n\n";
for ($i = $config['pagenum']; $i >= 1; $i--) {
	//获取一页信息
	echo getDatetime()."--[第($i)页]抓取内容[开始]…………………………………………………………\n";
	$urlDo = str_replace('[]', $i, $url);
	$htmlIns->setUrl($urlDo);
	$htmlIns->setUtf8();
	$htmlIns->setHtmls();
	$htmls = $htmlIns->getHtmls();
	$parseIns->setHtmls($htmls);
	
	//获取栏目并入库
	$topicName = $parseIns->getTopic();
	$topicId = $optionIns->insertTopic($topicName, $configName);
	echo getDatetime()."--[第($i)页]内容的栏目名称:$topicName, 栏目id:{$topicId}…………………………………………\n";
	
	//插入配置信息
	$configId = $optionIns->insertConfig($topicId);
	echo getDatetime()."--[第($i)页]内容的配置信息栏目id:$topicId, 配置id:{$configId}…………………………………………\n";
	
	//获取列表信息并入库
	$list = $parseIns->getContent($optionIns->getMaxdt($topicId));
	if (empty($list)) echo getDatetime()."--[第($i)页]内容的列表信息入库[NO-DATA]…………………………………………\n";
	else {
		$flag = $optionIns->insertArticle($list, $topicId);
		echo getDatetime()."--[第($i)页]内容的列表信息入库".($flag?'[成功]':'[失败]')."…………………………………………\n";
		
		//更新该话题下文章最大时间
		$flag = $optionIns->updateMaxdt($topicId);
		echo getDatetime()."--[第($i)页]内容更新该话题下文章最大时间".($flag?'[成功]':'[失败]')."…………………………\n";
	}
	
	echo getDatetime()."--[第($i)页]抓取内容[结束]…………………………………………………………\n\n";
}

echo getDatetime()."--抓取[文章列表]信息[结束]…………………………………………………………\n\n\n";

//抓取文章详细内容
echo getDatetime()."--抓取[文章详细内容]信息[开始]…………………………………………………………\n\n";
$topic = $optionIns->getTopic();
foreach ($topic as $k => $v) {
	$j = $k + 1;
	echo getDatetime()."--[第($j)个栏目]抓取内容详情[开始]…………………………………………………………\n";
	echo getDatetime()."--[第($j)个栏目]栏目id:{$v['id']}, 名称:{$v['name']}………………\n";
	$article = $optionIns->getArticle($v['id']);
	foreach ($article as $key => $value) {
		$i = $key + 1;
		$url = $value['link'];
		//echo $url;
		echo getDatetime()."--[第($i)篇文章]抓取内容详情[开始]…………………………………………………………\n";
		echo getDatetime()."--[第($i)篇文章]文章id:{$value['id']}, 标题:{$value['title']}………………\n";
		$urlDo = str_replace('[]', $i, $url);
		$htmlIns->setUrl($urlDo);
		$htmlIns->setUtf8();
		$htmlIns->setHtmls();
		$htmls = $htmlIns->getHtmls();
		$parseIns->setHtmls($htmls);
		
		echo getDatetime()."--[第($i)篇文章]正在下载图片到本地并保存到数据库…………………\n";
		//抓取内容详情
		$data = $parseIns->getContentDetail($url, $value['datetime']);
		$optionIns->insertContentDetil($value['id'], $data);
		echo getDatetime()."--[第($i)篇文章]保存成功…………………………………………………\n";
		
		echo getDatetime()."--[第($i)篇文章]抓取内容详情[结束]…………………………………………………………\n\n";
	}
	echo getDatetime()."--[第($j)个栏目]抓取内容详情[结束]…………………………………………………………\n\n";
}

echo getDatetime()."--抓取[文章详细内容]信息[结束]…………………………………………………………\n\n\n";


//导入discuz门户文章
echo getDatetime()."--导入到discuz门户文章[开始]…………………………………………………………\n\n";
$importDiscuzIns = new ImportDiscuz($dbconf[0], $dbconf[1], $dbconf[2], $dbconf[3]);
$dbconf = explode('|', $config['database_dz']);
$importDiscuzIns->setLinkDZ($dbconf[0], $dbconf[1], $dbconf[2], $dbconf[3], $dbconf[4]);
$importDiscuzIns->ImportDiscuzPortal($config['dz_root']);
echo getDatetime()."--导入到discuz门户文章[结束]…………………………………………………………\n\n\n";


echo getDatetime()."--抓取进程[结束],进程名称:{$configName}…………………………………………………………\n\n\n\n\n\n\n\n\n";



function getDatetime() {
	return date('[Y-m-d H:i:s]', time());
}