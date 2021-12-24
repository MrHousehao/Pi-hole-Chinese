# 汉化Pi-hole的网页界面
基于Pi-hole网页界面v5.9版本进行汉化，轻微调整PHP和JS代码以适应汉化界面。 
对应版本Pi-hole的AdminLTE-5.9，搞错版本我不负责！替换方式在压缩包里面
AdminLTE-5.9更新日志
原文件
/db_queries.php 第25行至30行增加提示文字“New options selected. Please reload the data or choose another time range.”，是关于修改选项后，需要重新生成列表的提示，还有增加了提示出现后的“Reload Data”按钮。
/messages.php 第38行增加备注文字
/settings.php 第833到867行官方调整，其中涉及汉化部分

备注：
『其他还有很多调整了的，自己看官方的日志，最近有点忙，懒得写了。可以看到部分页面官方优化了界面和功能。』

汉化内容调整
/db_queries.php 第25行至30行增加提示文字“New options selected. Please reload the data or choose another time range.”机翻为“选择了新选项。请重新加载数据或选择其他时间范围。”调整成“您更改了选项，请重新加载数据或选择其他时间范围。”。
第170行	『近期数据』	改为：『查询请求日志』#原文“Recent Queries”，机翻“最近的查询”，因为查询几个月前或者几天前的日志，感觉不算是“最近”或者“近期”吧
/groups-clients.php 第70行补充在客户端标题停留时显示的提示汉化。#之前漏了汉化的（—_—||）
/messages.php 第38行增加的备注文字已汉化。
/settings.php 第833到867行官方调整，已使用原官方文件进行汉化

备注：
『数据库下面的三个页面，有个自定义选择日期范围的框，里面月份的英文简写我没找到代码在哪里，曾经找到一个类似的，可是修改完以后测试就报错，我不是专业搞代码的能力有限，希望有大佬留言指导一下』
『另外，还有页面主体的“复选框和单选按钮”样式，样式名称一改就报错，实在找不到关联的代码在那里，同样希望有大佬留言指导一下』

以下是替换方法（自己做笔记用，大佬请忽略）
1、解压文件拷贝到U盘（fat32格式），找到www文件夹，放在根目录下，然后插入到树莓派中，执行下面的命令，『USB』（包括『』）改为U盘的名称，建议改英文名
sudo cp -f -r /media/pi/『USB』/www/html /var/www/
2、如果安装了SMB并且开启共享了，解压文件找到www文件夹，放在共享文件夹下，一般是/home/pi，然后执行下面的命令。
sudo cp -f -r /home/pi/www/html /var/www/
3、安装WinSCP，使用root登录树莓派，然后将解压后的www文件夹，拉到/var目录下，替换www文件夹
	sudo passwd root			#设置root用户密码
	nano /etc/ssh/sshd_config	#编辑设置文件，nano是系统自带的，vim请自行安装
	在PermitRootLogin prohibit-password下面添加，就是第33行（nano按Alt + Shift + 3 显示行号）
	PermitRootLogin yes

