<?php
/**
 * @option:	获取网页内容类
 * @author:	bishenghua
 * @date:	2013/10/16
 * @email:	bsh@ojo.cc
 */

class Html {
	
	private $_url = '';
	private $_htmls = '';
	private $_utf8 = false;
	
	/**
	 * 设置url
	 * @param unknown $url
	 */
	public function setUrl($url) {
		$this->_url = $url;
	}
	
	/**
	 * 设置为utf8处理方式
	 */
	public function setUtf8() {
		$this->_utf8 = true;
	}
	
	/**
	 * 获取html内容
	 */
	public function setHtmls() {
		if ($this->_url == '') return;
		
		$ctx = stream_context_create(
			array('http' => array('timeout' => 8))
		);
		$this->_htmls = file_get_contents($this->_url, 0, $ctx);
		$this->_utf8 && ($this->_htmls = self::_gbk2Utf8($this->_htmls));
	}
	
	/**
	 * 得到html内容
	 */
	public function getHtmls() {
		return $this->_htmls;
	}
	
	/**
	 * gbk转成utf8
	 * @param unknown $str
	 * @return string
	 */
	protected static function _gbk2Utf8($str) {
		return mb_convert_encoding($str, 'UTF-8', 'GBK');
	}
}