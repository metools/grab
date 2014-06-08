<?php
/**
 * @option:	数据解析类
 * @author:	bishenghua
 * @date:	2013/10/16
 * @email:	bsh@ojo.cc
 */

class Parse {
    
	private $_htmls = '';
	private $_dom = null;
	private $_url = '';
	private $_datetime = 0;
	private $_replace = array();
	private $_topicPattern = '';
	private $_contentPattern = '';
	private $_contentPatternIgnore = '';
	private $_titleAndLinkPattern = '';
	private $_descriptionPattern = '';
	private $_datetimePattern = '';
	private $_contentDetailPattern = '';
	
	public function setHtmls($htmls) {
		$this->_htmls = $htmls;
	}
	
	public function setTopicPattern($topicPattern) {
		$topicPattern = self::_checkPattern($topicPattern);
		$this->_topicPattern = $topicPattern;
	}
	
	public function setContentPattern($contentPattern) {
		$contentPattern = self::_checkPattern($contentPattern);
		$this->_contentPattern = $contentPattern;
	}
	
	public function setContentPatternIgnore($contentPatternIgnore) {
		$contentPatternIgnore = self::_checkPattern($contentPatternIgnore);
		$this->_contentPatternIgnore = $contentPatternIgnore;
	}
	
	public function setTitleAndLinkPattern($titleAndLinkPattern) {
		$titleAndLinkPattern = self::_checkPattern($titleAndLinkPattern);
		$this->_titleAndLinkPattern = $titleAndLinkPattern;
	}
	
	public function setDescriptionPattern($descriptionPattern) {
		$descriptionPattern = self::_checkPattern($descriptionPattern);
		$this->_descriptionPattern = $descriptionPattern;
	}
	
	public function setDatetimePattern($datetimePattern) {
		$datetimePattern = self::_checkPattern($datetimePattern);
		$this->_datetimePattern = $datetimePattern;
	}
	
	public function setContentDetailPattern($contentDetailPattern) {
		$contentDetailPattern = self::_checkPattern($contentDetailPattern);
		$this->_contentDetailPattern = $contentDetailPattern;
	}
	
	/**
	 * 分析得到主题栏目
	 * @return string
	 */
    public function getTopic() {
    	if ($this->_htmls == '' || $this->_topicPattern == '') return '';
    	
    	$pattern = explode('[]', $this->_topicPattern);
    	if (preg_match("/$pattern[0](.*?)$pattern[1]/i", $this->_htmls, $match)) { //匹配规则可配置
    		return trim($match[1]);
    	}
    	return '';
    }
    
    /**
     * 分析得到内容列表内容
     * @param unknown $maxdt
     * @return multitype:|Ambigous <multitype:, string, number>
     */
    public function getContent($maxdt) {
    	if ($this->_htmls == '' || $this->_contentPattern == '') return array();
    	
    	$result = array();
    	$pattern = explode('[]', $this->_contentPattern);
    	if (preg_match("/$pattern[0](.*?)$pattern[1]/isU", $this->_htmls, $match1)) {
    		//echo $match1[1];
    		//找到标题和链接
    		if ($this->_titleAndLinkPattern) {
	    		$pattern = explode('[]', $this->_titleAndLinkPattern);
	    		if (preg_match_all("/$pattern[0](.*)$pattern[1]/isU", $match1[1], $match2)) { //匹配规则可配置
	    			foreach ($match2[1] as $key => $value) {
	    				$title = $link = '';
	    				if (preg_match('/<a[^>]+href="(.*?)"[^>]*>(.*?)<\/a>/', $value, $match3)) {
	    					$title = trim($match3[2]);
	    					$link = trim($match3[1]);
	    				}
	    				$result[$key]['title'] = $title;
	    				$result[$key]['link'] = $link;
	    			}
	    		}
    		}
    		
    		//找到描述
    		if ($this->_descriptionPattern) {
    			$pattern = explode('[]', $this->_descriptionPattern);
	    		if (preg_match_all("/$pattern[0](.*)$pattern[1]/isU", $match1[1], $match2)) { //匹配规则可配置
	    			foreach ($match2[1] as $key => $value) {
	    				$result[$key]['description'] = trim(strip_tags($value));
	    			}
	    		}
    		}
    		
    		//找到日期
    		if ($this->_datetimePattern) {
    			$pattern = explode('[]', $this->_datetimePattern);
	    		if (preg_match_all("/$pattern[0](.*)$pattern[1]/isU", $match1[1], $match2)) { //匹配规则可配置
	    			foreach ($match2[1] as $key => $value) {
	    				$datetime = strtotime(trim($value));
	    				//如果抓取到的文章时间小于或等于抓取到的最大时间，则不入库，需要把上面获取的内容删掉，保证抓取到的文章不重复
	    				if ($datetime <= $maxdt) unset($result[$key]);
	    				else $result[$key]['datetime'] = $datetime;
	    			}
	    		}
    		}
    	}

    	return $result;
    }
    
    /**
     * 分析得到内容详情页信息
     * @param unknown $url
     * @param unknown $datetime
     * @return multitype:|multitype:NULL mixed
     */
    public function getContentDetail($url, $datetime) {
    	if ($this->_htmls == '' || $this->_contentDetailPattern == '') return array();
    	
    	$result = array();
    	$pattern = explode('[]', $this->_contentDetailPattern);
    	if (preg_match("/$pattern[0](.*)$pattern[1]/isU", $this->_htmls, $match1)) {
    		if ($this->_contentPatternIgnore) {
	    		//过滤文章内容
	    		$patternArr = explode('|', $this->_contentPatternIgnore);
	    		$pattern = array();
	    		foreach ($patternArr as $value) {
	    			$pattern[] = '/'.implode('.*', explode('>[]', $value)).'/isU';
	    		}
	    		$content = preg_replace($pattern, array(), $match1[1]);
    		}
    		
    		//获取图片信息
    		$this->_url = $url;
    		$this->_datetime = $datetime;
    		$this->_replace = array('match' => array(), 'filepath' => array());
    		$content = preg_replace_callback('/(<img[^>]+src=")(.*)("[^>]*\/?>)/isU', array(&$this, '_downloadPic'), $content);
    		$content = str_replace($this->_replace['match'], $this->_replace['filepath'], $content);
    	}
    	return array('content' => $content, 'filepath' => $this->_replace['filepath']);
    }

    /**
     * 下载图片信息保存到本地
     * @param unknown $match
     * @return string
     */
    protected function _downloadPic($match) {
    	$picUrl = $match[2];
    	if (substr($match[2], 0, 7) != 'http://') {
    		$urlArr = parse_url($this->_url);
    		$picUrl = "http://{$urlArr['host']}/{$match[2]}";
    	}
    	$filepath = 'attachment/'.date('Ym/d', $this->_datetime);
    	$filename = md5($picUrl).'.jpg';
    	$file = "$filepath/$filename";
    	
    	//确保不重复
    	if (!in_array($match[2], $this->_replace['match'])) {
	    	//保存下替换信息
	    	$this->_replace['match'][] = $match[2];
	    	$this->_replace['filepath'][] = $file;
	    	
	    	//开单独进程下载
	    	$root = ROOT;
	    	exec("mkdir -p $filepath;wget -q -b '$picUrl' -O $root/$file");
    	}
    	
    	return $match[1].$file.$match[3];
    }
    
    /**
     * 检查正则表达式字符串
     * @param unknown $pattern
     * @return mixed
     */
    protected static function _checkPattern($pattern) {
    	return str_replace('/', '\/', $pattern);
    }
} 