描述：通用网页抓取脚本，适用于采集新闻、论坛等网站数据

作者: bishenghua
时间: 2013/10/16
邮箱: bsh@ojo.cc


touch README.md
git init
git add README.md
git commit -m "first commit"
git remote add origin git@github.com:bishenghua/grab.git
git push -u origin master



git init
git config --global user.email net.bsh@gmail.com
git config --global user.name bishenghua
git config --global core.editor vim
git config --global remote.origin.url git@github.com:bishenghua/grab.git
git pull -f origin
