<?php
/**
 * @option:	抓取程序入口
 * @author:	bishenghua
 * @date:	2013/10/16
 * @email:	bsh@ojo.cc
 */

define('ROOT', dirname(__FILE__));
error_reporting(E_ALL);ini_set('display_errors', 'On');

$phpShell = '/Applications/server/php/bin/php -c /Applications/server/php/conf/php.ini';
$dir = ROOT.'/config';
$logDir = ROOT.'/log';
$job = ROOT.'/job.php';
$dirHandle = opendir($dir);
while (($file = readdir($dirHandle)) !== false) {
	if (is_file($dir . '/' . $file)) {
		$fileArr = explode('.', $file);
		exec("$phpShell $job {$fileArr[0]} >> $logDir/{$fileArr[0]}.txt 2>&1 &");
	}
}