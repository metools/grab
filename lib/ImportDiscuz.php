<?php
/**
 * @option:	导入discuz类
 * @author:	bishenghua
 * @date:	2013/10/16
 * @email:	bsh@ojo.cc
 */

class ImportDiscuz {
	
	/**
	 * 连接标识
	 * @var unknown
	 */
	private $_link = null;
	
	/**
	 * DZ连接标识
	 * @var unknown
	 */
	private $_linkDZ = null;
	
	private $_tablepreDZ = '';
	
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
	
	public function setLinkDZ($host, $username, $password, $dbname, $tablepre) {
		$this->_linkDZ = @mysql_connect($host, $username, $password, 1);
		mysql_select_db($dbname, $this->_linkDZ);
		mysql_query('set names utf8', $this->_linkDZ);
		$this->_tablepreDZ = $tablepre;
	}
	
	/**
	 * /**
	 * 
	 * 文章内容表
	 * pre_portal_article_content
	 * cid(自增) aid content dateline id=0 pageorder=1
	 * data/attachment/portal/201310/17/094840icc63z523n6n4g5c.jpg
	 * data/attachment/portal/201310/17/094840icc63z523n6n4g5c.jpg.thumb.jpg
	 * 
	 * pre_portal_article_count
	 * aid catid viewnum=1
	 * 
	 * pre_portal_article_title
	 * aid(自增) catid uid=1 username=admin title highlight='|||' summary pic(有图为路径，没有为空) thumb(1表示有小图，默认为0) contents(1为有内容，默认0为没有)
	 * allowcomment=1 tag=1 dateline preaid nextaid
	 * 
	 * pre_portal_attachment
	 * attachid(自增) uid=1 dateline filename=113443r5a4ftwy5h4s1h43.jpg filetype=jpg fizesize=303826 attachment(201310/01/105235l4t2xee2zbxw99zp.jpg) isimage=1 thumb=1
	 * 
	 * pre_portal_category
	 * catid articles(导入一篇文章该字段加1)
	 * 先从此表中取出catname='舞蹈演出'的记录，得到catid
	
	 *
	 * @return boolean
	 */
	public function ImportDiscuzPortal($discuzRoot) {
		if (!$this->_link || !$this->_linkDZ) return false;
		
		require_once ROOT.'/lib/Image.php';
		$imageIns = new Image();
		
		//获取数据源栏目信息
		$sql = 'select id,name from `topic`';
		$result1 = mysql_query($sql, $this->_link);
		while ($row1 = mysql_fetch_assoc($result1)) {
			//print_r($row1);
			echo getDatetime()."--处理栏目[id:{$row1['id']}, 名称:{$row1['name']}]\n";
			//从dz中中取出catname='舞蹈演出'的记录，得到catid
			$sql = "select catid from {$this->_tablepreDZ}portal_category where catname='{$row1['name']}'";
			$result2 = mysql_query($sql, $this->_linkDZ);
			if ($row2 = mysql_fetch_assoc($result2)) {
				//print_r($row2);
				//获取文章内容
				echo getDatetime()."--对应文章分类信息[catid:{$row2['catid']}]\n";
				$sql = "select `id`,`title`,`link`,`datetime`,`description`,`text` from `article` where `import_discuz_portal`='0' and `topic_id`='{$row1['id']}'";
				$result3 = mysql_query($sql, $this->_link);
				//获取前篇文章id
				$preaid = 0;
				$result = mysql_query("select max(aid) as preaid from {$this->_tablepreDZ}portal_article_title", $this->_linkDZ);
				if ($row = mysql_fetch_assoc($result)) {
					$preaid = $row['preaid'];
				}
				$number = 0; //记录一下导入文章数
				while ($row3 = mysql_fetch_assoc($result3)) {
					$number++;
					echo getDatetime()."--开始导入文章[id:{$row3['id']}, 标题:{$row3['title']}]\n";
					//处理图片信息
					$isthumb = 0;
					$pic = '';
					$content = $row3['text'];
					$iscontent = $content ? 1 : 0;
					
					//导入dz文章标题表
					$isql = "insert into {$this->_tablepreDZ}portal_article_title
							(catid,uid,username,title,highlight,summary,contents,allowcomment,tag,dateline,preaid)values
							('{$row2['catid']}','1','admin','{$row3['title']}','|||','{$row3['description']}','$iscontent','1','1','{$row3['datetime']}','$preaid')";
					if (mysql_query($isql, $this->_linkDZ)) {
						$aid = mysql_insert_id($this->_linkDZ);
						//更新nextaid字段
						mysql_query("update {$this->_tablepreDZ}portal_article_title set nextaid='$aid' where aid='$preaid'", $this->_linkDZ);
						//赋值前篇文章id
						$preaid = $aid;
						
						//获取文章下的图片信息
						$result = mysql_query("select filepath from `image` where article_id='{$row3['id']}'", $this->_link);
						$i = 0;
						$comma = '';
						$isql = "insert into {$this->_tablepreDZ}portal_attachment (uid,dateline,filename,filetype,filesize,attachment,isimage,thumb,aid) values";
						while ($row = mysql_fetch_assoc($result)) {
							echo getDatetime()."--处理该文章下图片[filepath:{$row['filepath']}]\n";
							$isthumb = 1;
							$sourceFile = ROOT."/{$row['filepath']}";
							$filepath = str_replace('attachment/', '', $row['filepath']);
							$dir = substr($filepath, 0, -37);
							$filename = substr($filepath, -36);
							$filesize = filesize($sourceFile);
							if ($i++ == 0) $pic = $filepath;
							exec("mkdir -p $discuzRoot/data/attachment/$dir;cp -rf $sourceFile $discuzRoot/data/attachment/$filepath");
							$content = str_replace($row['filepath'], "data/attachment/$filepath", $content);
						
							//生成缩略图
							$imageIns->Thumb($sourceFile, "$discuzRoot/data/attachment/{$filepath}.thumb.jpg", 300, 300);
							
							$isql .= "$comma('1','{$row3['datetime']}','$filename','jpg','$filesize','$filepath','1','1','$aid')";
							$comma = ',';
						}
						if ($comma) {
							if (mysql_query($isql, $this->_linkDZ)) {
								//更新文章封面图
								$isql = "update {$this->_tablepreDZ}portal_article_title set pic='$pic',thumb='1' where aid='$aid'";
								mysql_query($isql, $this->_linkDZ);
								echo getDatetime()."--该文章下图片导入[成功]\n";
							} else {
								echo getDatetime()."--该文章下图片导入[失败]\n";
							}
						}
						
						if ($content) {
							//插入dz文章内容表
							$isql = "insert into {$this->_tablepreDZ}portal_article_content
									(aid,content,dateline,pageorder)values
									('$aid','$content','{$row3['datetime']}','1')";
							if (mysql_query($isql, $this->_linkDZ)) {
								echo getDatetime()."--文章内容插入[成功]\n";
							} else {
								echo getDatetime()."--文章内容插入[失败]\n";
							}
						}
						
						//插入dz文章计数表
						$isql = "insert into {$this->_tablepreDZ}portal_article_count
								(aid,catid,viewnum)values
								('$aid','{$row2['catid']}','1')";
						mysql_query($isql, $this->_linkDZ);
						
						//更新标志位
						mysql_query("update `article` set `import_discuz_portal`='1' where id='{$row3['id']}'", $this->_link);
						echo getDatetime()."--该文章导入[成功][dz中aid:$aid]\n";
					} else {
						echo getDatetime()."--该文章导入[失败]\n";
					}
					
					echo getDatetime()."--成功导入文章[id:{$row3['id']}, 标题:{$row3['title']}]\n\n";
					
					//break;
				}
				if ($number) {
					echo getDatetime()."--成功导入文章数[$number]\n\n";
				} else {
					echo getDatetime()."--没有需要导入的文章[NO-DATA]\n\n";
				}
			}
		}
	}
}