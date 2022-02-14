# Pi-hole汉化
基于Pi-hole官方的网页界面进行汉化，轻微调整PHP和JS代码以适应汉化界面。

网页界面汉化程度达到95%，其中90%的汉化内容经过实测，至少不会报错。剩下的暂时没法测试，因为条件不满足触发不了，这些基本都是些错误、警告那些，一般的设置报错都汉化了，但是涉及主机的报错，这我测不了。

说明：

1、部分名词如原文“gravity”直译应该是“重力”，但是“更新重力”显然不符合语境，因此我结合“Pi-hole作为互联网广告黑洞”中“黑洞”这一词，选择改用“引力场”作为“gravity”的代替祠。

2、此外原文“blocked”、“blocke”等直译应该是“阻止、阻挡“的，也根据“黑洞”，改用“吞噬”作为替代词。

3、如果不喜欢的请自行搜索更换回原文直译词语。

# 以下是替换方法（自己做笔记用，大佬请忽略）

1、解压文件拷贝到U盘（fat32格式），找到www文件夹，放在根目录下，然后插入到树莓派中，执行下面的命令，『USB』（包括『』）改为U盘的名称，建议改英文名
sudo cp -f -r /media/pi/『USB』/www/html /var/www/

2、如果安装了SMB并且开启共享了，解压文件找到www文件夹，放在共享文件夹下，一般是/home/pi，然后执行下面的命令。

sudo cp -f -r /home/pi/www/html /var/www/

3、安装WinSCP，使用root登录树莓派，然后将解压后的www文件夹，拉到/var目录下，替换www文件夹

sudo passwd root			#设置root用户密码

nano /etc/ssh/sshd_config	#编辑设置文件，nano是系统自带的，vim请自行安装

在PermitRootLogin prohibit-password下面添加，就是第33行（nano按Alt + Shift + 3 显示行号）

PermitRootLogin yes
