<?php
/**
 * @option:	数据库操作相关类
 * @author:	bishenghua
 * @date:	2013/10/16
 * @email:	bsh@ojo.cc
 */

class Option {
	/**
	 * 连接标识
	 * @var unknown
	 */
	private $_link = null;
	
	/**
	 * 构造函数连接数据库，获取连接资源
	 * @param unknown $host
	 * @param unknown $username
	 * @param unknown $password
	 * @param unknown $dbname
	 */
	public function __construct($host, $username, $password, $dbname) {
		$this->_link = @mysql_connect($host, $username, $password);
		mysql_select_db($dbname, $this->_link);
		mysql_query('set names utf8', $this->_link);
	}
	
	/**
	 * 插入主题栏目
	 * @param unknown $name
	 * @return number|Ambigous <>
	 */
	public function insertTopic($name, $config) {
		if (empty($name) || empty($config)) return 0;
		
		$name = $this->_escape($name);
		$config = $this->_escape($config);
		$sql = "select id from `topic` where `name`='$name' and `config`='$config'";
		$result = mysql_query($sql, $this->_link);
		//存在则返回id，否则则插入
		if ($row = mysql_fetch_assoc($result)) {
			return $row['id'];
		}
		$sql = "insert into `topic` (`name`,`config`) values ('$name','$config')";
		mysql_query($sql, $this->_link);
		return mysql_insert_id($this->_link);
	}
	
	/**
	 * 插入文件列表信息
	 * @param unknown $list
	 * @param unknown $topicId
	 * @return boolean
	 */
	public function insertArticle($list, $topicId) {
		if (empty($list) || !($topicId = intval($topicId)) || !is_array($list)) return false;
		$data = 'insert into `article` (`topic_id`,`title`,`link`,`description`,`datetime`) values';
		$comma = '';
		foreach ($list as $value) {
			$title = $this->_escape($value['title']);
			$link = $this->_escape($value['link']);
			$description = $this->_escape($value['description']);
			$datetime = $this->_escape($value['datetime']);
			$data .= "$comma('$topicId','$title','$link','$description','$datetime')";
			$comma = ',';
		}
		return mysql_query($data, $this->_link) !== false;
	}
	
	/**
	 * 插入配置控制信息
	 * @param unknown $topicId
	 * @return number|Ambigous <>
	 */
	public function insertConfig($topicId) {
		if (!$topicId) return 0;
		
		$sql = "select id from `config` where `topic_id`='$topicId'";
		$result = mysql_query($sql, $this->_link);
		//存在则返回id，否则则插入
		if ($row = mysql_fetch_assoc($result)) {
			return $row['id'];
		}
		$sql = "insert into `config` (`topic_id`) values ('$topicId')";
		mysql_query($sql, $this->_link);
		return mysql_insert_id($this->_link);
	}
	
	/**
	 * 插入文章内容及图片信息
	 * @param unknown $articleId
	 * @param unknown $data
	 */
	public function insertContentDetil($articleId, $data) {
		//保存图片
		if (isset($data['filepath']) && !empty($data['filepath'])) {
			$sql = 'insert into `image` (`article_id`,`filepath`) values';
			$comma = '';
			foreach ($data['filepath'] as $value) {
				$value = $this->_escape($value);
				$sql .= "$comma('$articleId','$value')";
				$comma = ',';
			}
			mysql_query($sql, $this->_link);
		}
		//保存内容并设置标识为已处理
		if (isset($data['content']) && !empty($data['content'])) {
			$content = $this->_escape($data['content']);
			$sql = "update `article` set `isdeal`='1',`text`='$content' where `id`='$articleId'";
			mysql_query($sql, $this->_link);
		}
	}
	
	/**
	 * 更新该主题下最大文章时间
	 * @param unknown $topicId
	 * @return boolean
	 */
	public function updateMaxdt($topicId) {
		if (!($maxdt = $this->getMaxdt($topicId))) return false;
		
		$sql = "update `config` set `grab_max_dt`='$maxdt' where `topic_id`='$topicId'";
		return mysql_query($sql, $this->_link) !== false;
	}
	
	/**
	 * 获取该主题下最大文章时间
	 * @param unknown $topicId
	 * @return number|Ambigous <>
	 */
	public function getMaxdt($topicId) {
		if (!$topicId) return 0;
		$sql = "select max(datetime) as max_dt from `article` where `topic_id`='$topicId'";
		$result = mysql_query($sql, $this->_link);
		if ($row = mysql_fetch_assoc($result)) {
			return $row['max_dt'];
		}
		return 0;
	}
	
	/**
	 * 获取文章列表信息
	 * @param unknown $topicId
	 * @return multitype:|multitype:multitype:
	 */
	public function getArticle($topicId) {
		if (!$topicId) return array();
		
		$sql = "select id,title,link,datetime from `article` where `isdeal`='0' and `topic_id`='$topicId'";
		$result = mysql_query($sql, $this->_link);
		$return = array();
		while ($row = mysql_fetch_assoc($result)) {
			$return[] = $row;
		}
		return $return;
	}
	
	/**
	 * 获取主题栏目信息
	 * @return multitype:multitype:
	 */
	public function getTopic() {
		$sql = "select id,name from `topic`";
		$result = mysql_query($sql, $this->_link);
		$return = array();
		while ($row = mysql_fetch_assoc($result)) {
			$return[] = $row;
		}
		return $return;
	}
	
	/**
	 * 获得安全字符串，避免sql出错
	 * @param unknown $str
	 * @return string
	 */
	protected function _escape($str) {
		return mysql_real_escape_string($str, $this->_link);
	}
}