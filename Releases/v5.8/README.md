# 汉化Pi-hole的网页界面
基于Pi-hole网页界面v5.8版本进行汉化，轻微调整PHP和JS代码以适应汉化界面。 
对应版本Pi-hole的AdminLTE-5.8，搞错版本我不负责！替换方式在压缩包里面
AdminLTE-5.8更新日志
原文件
/api_db.php、api_FTL.php 与5.7版相比，非汉化部分有新增代码和代码调整，请使用5.8版汉化文件
/network.php 中第37行和51增加Action，可以进行删除客户端操作，已汉化
/queries.php 中第161行增加说明『Note: Queries for pi.hole  and the hostname are never logged.』本次汉化已对该段文字根据中文语境进行汉化
/settings.php 中第1295至1305，代码有调整，第1325至1347新增一段代码
/scripts/pi-hole/php/auth.php 中删除了部分代码
/scripts/pi-hole/php/footer.php 中增加『Docker Tag』的相关代码
/scripts/pi-hole/php/func.php 中数段代码调整，不列了，想知道自己去下原文件对比
/scripts/pi-hole/php/network.php 是5.8中新增的文件
/scripts/pi-hole/php/teleporter.php 增加和调整多行代码，涉及汉化部分的引用参数调整（数量比较多）
/scripts/pi-hole/js/network.js 增加和调整多行代码
汉化内容调整
/db_queries.php 中第78行和91行
第78行 『已吞噬的数据』	改为：『已吞噬数据』
第91行 『已吞噬的数据（通配符）』	改为：『已吞噬数据（通配符）』
/network.php 中第21行
第21行	『客户端列表』	改为：『客户端概览（Pi-hole使用情况）』
/queries.php 中第165和166行，将5.7汉化文件中不符合中文语境的内容进行调整
第165行『允许高亮显示以复制到剪贴板』	改为：『可高亮选择文字以便复制到剪贴板』
第166行『手机：长按可突出显示文本并允许复制到剪贴板』	改为：『手机：长按可高亮并选择文字以便复制到剪贴板』
/settings.php 中第330行
第330行	『清空客户端列表』	改为：『清空客户端概况』
第1330～1346行，官方调整了传送器（就是备份和恢复）中，导入结果信息输出页面的代码，本次也汉化了
/scripts/pi-hole/php/footer.php 中增加『Docker Tag』的部分只是汉化为『Docker标签』，这个应该是Docker的
我目前只装了树莓派，所以没测Docker不知道在Docker中会不会出错，反正我没发现出错

