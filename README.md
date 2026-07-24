熵云寄售 系统安装文档 (宝塔面板)
本文档将指导您如何在宝塔面板环境中安装和配置 熵云寄售 系统。

视频教程：https://assets.slm.gx.cn/assets/videos/install.mp4

一、 环境要求
在开始安装之前，请确保您的服务器满足以下要求：

操作系统: Linux (CentOS 7.x+ / Ubuntu / Debian)
控制面板: 宝塔面板 (Baota Panel) 7.x+
Web 服务器: Nginx 1.20+ (推荐) 或 Apache 2.4+
PHP 版本: 8.2 (推荐)
MySQL 版本: 5.7 (必须)
二、 PHP 扩展安装
在宝塔面板的 PHP 管理中，请务必安装以下扩展：

sg16 (SourceGuardian)
说明: 这是系统核心加密组件，必须安装。
注意: 如果您的宝塔面板显示为 sg16，请直接安装 sg16；通常情况下为 sg11。
fileinfo
说明: 用于文件上传和类型检测。
三、 站点创建与上传
添加站点:

在宝塔面板“网站”菜单中点击“添加站点”。
填写域名。
数据库选择“MySQL”，并设置用户名和密码（记录下来，稍后安装需要）。
PHP 版本选择 PHP-82。
上传源码:

进入网站根目录（例如 /www/wwwroot/your-domain.com）。
删除目录下默认生成的 index.html 和 404.html。
上传系统源码包（entropy.xxx.zip）并解压。
解压后，请确保源码文件直接位于根目录下，而不是在子文件夹中。
目录权限:

确保网站目录的所有者为 www，用户组为 www。
通常宝塔会自动设置，若不正确请在文件管理中设置权限为 777，所有者为 www。
四、 站点配置 (关键)
运行目录:

在站点设置中，点击左侧“网站目录”。
将 运行目录 修改为 /public。
点击保存。
伪静态:

在站点设置中，点击左侧“伪静态”。
在下拉菜单中选择 thinkphp。
点击保存。
五、 系统安装
访问安装程序:

在浏览器中访问您的域名：http://your-domain.com/install
系统会自动检查环境是否满足要求（PHP版本、扩展、目录权限等）。
配置特征码:

请前往 授权站获取特征码，要确保授权域名和特征码对得上。
授权域名填写一级域名即可。
配置数据库:

填写之前创建的 MySQL 数据库信息（主机、数据库名、用户名、密码）。
注意: 数据库主机通常为 127.0.0.1 或 localhost。
完成安装:

点击安装按钮，系统将自动导入数据库表结构并初始化必要的数据（角色、配置、定时任务）。
安装成功后，系统会提示创建管理员账号。
六、 计划任务 (Crontab)
为了保证系统功能的正常运行（如自动提现、超时订单释放），建议在宝塔面板“计划任务”中添加以下 Shell 脚本任务：

任务类型: Shell 脚本 执行周期: N分钟 1分钟 (建议每分钟执行，或根据需求调整)

脚本内容示例:

# 自动生成提现批次
cd /www/wwwroot/项目目录
php think CreateWithdrawalBatch

# 释放超时订单
cd /www/wwwroot/项目目录
php think ReleaseTimeoutOrders

# 投诉超时通知
cd /www/wwwroot/项目目录
php think ComplaintTimeoutNotify

请将 您的域名目录 替换为实际的路径，例如 entropy.com

七、 命令行工具箱
*在网站根目录下的命令行内输入： php think Tools

八、 常见问题
安装页面 404: 请检查是否设置了 运行目录 为 /public 并且配置了 伪静态。
提示缺少 sg11/sg16: 请到 PHP 管理中安装对应的 SourceGuardian 扩展并重启 PHP 服务。
数据库连接失败: 请检查数据库账号密码是否正确，以及数据库权限是否开启（通常本地连接无需额外权限）。

其他文档：https://entropy.slmsns.com/doc?id=1
