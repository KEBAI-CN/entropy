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


插件开发机制
本文档说明 app/service/plugin 目录下的统一插件开发规范。

目录约定
所有统一插件放在：

app/service/plugin/
复制
每个插件一个独立目录，目录名必须唯一，并且必须是合法 PHP 类名：

app/service/plugin/DemoPlugin/
  DemoPlugin.php
  config.php
  params.php
  controller/
    Index.php
  service/
    DemoService.php
  model/
    DemoLog.php
  command/
    Sync.php
  view/
    index.html
  Hook.php
  static/
    js/app.js
    css/app.css
复制
命名规则：

插件目录：DemoPlugin
主类文件：DemoPlugin.php
主类命名空间：app\service\plugin\DemoPlugin
主类类名：DemoPlugin
核心文件
统一插件机制核心文件：

app/service/plugin/PluginInterface.php
app/service/plugin/AbstractPlugin.php
app/service/plugin/HookInterface.php
app/service/plugin/PluginController.php
app/service/plugin/PluginService.php
app/service/plugin/PluginModel.php
app/service/plugin/PluginCommand.php
app/service/plugin/PluginManager.php
复制
插件主类
插件主类必须实现 PluginInterface，推荐继承 AbstractPlugin。

示例：

<?php
namespace app\service\plugin\DemoPlugin;

use app\service\plugin\AbstractPlugin;

class DemoPlugin extends AbstractPlugin
{
    public function getCode(): string
    {
        return 'DemoPlugin';
    }

    public function install(): bool
    {
        return true;
    }

    public function uninstall(): bool
    {
        return true;
    }
}
复制
config.php
插件目录必须包含 config.php，用于描述插件信息。

示例：

<?php
return [
    'version' => '1.0.0',
    'min_system_version' => '1.0.0',
    'category_name' => '工具',
    'logo' => '',
    'name' => '演示插件',
    'description' => '这是一个插件开发示例',
    'author' => 'Entropy',
    'menu' => [
        [
            'tag' => 'a',
            'name' => '打开页面',
            'src' => '/plugin/DemoPlugin/index',
        ],
    ],
    'hook' => [
        'SimpleCommand' => 'Hook',
    ],
    'actions' => [
        [
            'key' => 'syncData',
            'name' => '同步数据',
            'description' => '在插件设置弹窗中显示为一个独立操作按钮',
            'controller' => 'Index',
            'method' => 'syncData',
            'button_type' => 'primary',
            'status' => 'normal',
            'confirm_title' => '确认同步？',
            'confirm_content' => '确定要执行插件数据同步吗？',
            'success_message' => '同步完成',
        ],
    ],
    'form_fields' => [
        [
            'field' => 'status',
            'name' => '启用状态',
            'type' => 'switch',
            'default' => 1,
        ],
        [
            'field' => 'api_url',
            'name' => 'API地址',
            'type' => 'text',
            'default' => '',
            'placeholder' => '请输入API地址',
            'description' => '外部服务的API地址',
        ],
        [
            'field' => 'timeout',
            'name' => '超时时间',
            'type' => 'number',
            'default' => 30,
            'placeholder' => '秒',
        ],
        [
            'field' => 'mode',
            'name' => '运行模式',
            'type' => 'select',
            'default' => 'production',
            'options' => [
                ['label' => '生产环境', 'value' => 'production'],
                ['label' => '测试环境', 'value' => 'test'],
            ],
        ],
        [
            'field' => 'remark',
            'name' => '备注',
            'type' => 'textarea',
            'default' => '',
            'placeholder' => '请输入备注信息',
        ],
    ],
];
复制
字段说明：

字段	说明
version	插件版本号
min_system_version	最低系统版本
category_name	插件分类
logo	插件图标，支持图片 URL 或 base64
name	插件名称
description	插件描述
author	插件作者
menu	插件管理入口、后台/用户中心导航菜单
hook	插件钩子配置
actions	插件设置弹窗中的独立功能按钮
form_fields	插件配置表单字段
settings_footer	是否显示插件设置弹窗默认保存页脚，默认 true；纯工具型插件可设为 false
menu 插件页面与导航入口
menu 既可用于管理后台“通用插件”的设置弹窗页面入口，也可直接把插件页加入管理后台、PC 用户中心或移动端用户中心的左侧菜单。插件启用后菜单立即可用；启停和保存插件配置会自动刷新菜单缓存。

所有导航菜单页都会生成同源 iframe 路由，默认页面地址为：

/plugin/<插件标识>/<控制器>/<方法>
复制
这样插件页面不会被编进任一前端工程的业务包。页面内需要调用受保护接口时，仍应使用现有后端 API 并自行完成权限校验。

挂载到已有后台菜单（二级菜单）
为菜单设置 parent，可将其作为既有目录的二级菜单。parent 支持父菜单的 path 或 name；permission 仅在管理员菜单中生效，对应后台角色权限标识。

'menu' => [
    [
        'area' => 'admin',
        'parent' => '/system',
        'path' => '/system/demo-plugin',
        'name' => 'PluginDemoPlugin',
        'title' => '演示插件',
        'icon' => 'ri:apps-2-line',
        'order' => 90,
        'controller' => 'Index',
        'action' => 'index',
        'permission' => 'PluginManage',
    ],
],
复制
新建一级菜单和多个二级页面
菜单项包含 children 时会自动成为一级目录。子菜单没有指定 area 时继承父菜单的入口范围；子菜单相对路径会自动拼到父路径下。

'menu' => [
    [
        'area' => ['admin', 'user'],
        'path' => '/plugin-tools',
        'name' => 'PluginTools',
        'title' => '插件工具',
        'icon' => 'ri:tools-line',
        'order' => 80,
        'children' => [
            [
                'path' => 'overview',
                'name' => 'PluginToolsOverview',
                'title' => '概览',
                'controller' => 'Index',
                'action' => 'index',
            ],
            [
                'path' => 'logs',
                'name' => 'PluginToolsLogs',
                'title' => '运行日志',
                'controller' => 'Log',
                'action' => 'index',
            ],
        ],
    ],
],
复制
area 可取 admin、user、mobile-user 或 all，也可传数组。三个前端入口均直接消费后端 /api/system/menus 的完整菜单树；移动端会携带 area=mobile-user，因此可单独声明移动端页面。title、path、controller 和 action 应保持稳定，避免角色权限、已打开标签或浏览器历史失效。

每个 menu 数组项支持以下属性：

属性	说明
area/areas	菜单显示入口，默认 admin
parent	挂载到已有目录的路径或名称，设置后作为二级菜单
path	前端路由路径；子菜单可使用相对路径
name	稳定的路由名称；未填写时系统自动生成
title	菜单标题
icon	Remix Icon 标识
order	同级排序值，越小越靠前
children	子菜单数组，用于创建一级目录和多个二级页面
controller	插件控制器，默认 Index
action	插件页面方法，默认 index
permission	管理员菜单所需角色权限标识（可选）
isHide/isHideTab/keepAlive/fixedTab/activePath	路由显示与标签页属性（可选）
历史 src 入口仍可用于“通用插件”设置弹窗。导航菜单统一根据 controller 和 action 生成站内插件页地址，不接受外部 URL。 仅包含旧字段 name、description、src 的菜单项会继续按设置弹窗入口处理，不会出现在侧边栏。

插件页面路由由后端统一注册，插件启用后即可访问：

/plugin/:code
/plugin/:code/:controller
/plugin/:code/:controller/:action
复制
form_fields 字段说明
每个 form_fields 数组项支持以下属性：

属性	说明
field	字段标识，用于存取配置值
name	字段显示名称
type	字段类型：text、textarea、number、switch、select
default	默认值
placeholder	输入框占位文本（可选）
description	字段描述说明（可选）
options	select 类型的选项列表，格式 [['label' => '名称', 'value' => '值']]
配置存储机制
插件配置有两种存储方式：

系统设置表存储（主存储）：配置保存在 system_settings 表中，key 为 plugin_config_{插件标识}，值为 JSON 格式。管理后台插件管理页面会根据 form_fields 动态渲染配置表单，保存时自动写入设置表。

params.php 文件存储（兼容存储）：安装插件时自动生成，用于兼容旧插件和旧版本配置文件。读取配置时如果设置表没有内容，会从 params.php 迁移到设置表；保存配置时会同步写入设置表和 params.php。

在代码中读取配置时，统一使用 plugconf() 或 PluginManager::config()。它们会优先读取系统设置表，并自动兼容旧的 params.php：

$config = plugconf('DemoPlugin');
$status = plugconf('DemoPlugin.status') ?? 1;

app\service\plugin\PluginManager::config('DemoPlugin.status', 1);
复制
不要在业务代码中直接拼接 system_settings 的 key 读取插件配置，避免跳过迁移、缓存刷新和兼容逻辑。

actions 功能按钮
actions 用于在管理后台“通用插件”的设置弹窗中增加独立功能按钮。它适合执行同步、清理缓存、初始化数据、测试连接等动作，不需要把所有能力都塞进配置 JSON。

每个 actions 数组项支持以下属性：

属性	说明
key	动作标识，前端点击时提交这个值
name	动作名称
description	动作说明
controller	插件控制器名称，默认 Index
method	控制器方法名
button_type	按钮类型，支持 Arco Button 的 type，如 primary、outline、text
button_text	按钮文案，默认使用 name
status	按钮状态，如 normal、warning、danger、success
src/url	如果该按钮只是打开页面，可填写页面地址，前端会直接打开
confirm_title	确认弹窗标题
confirm_content	确认弹窗内容
success_message	执行成功后前端提示文案
confirm	是否需要确认弹窗，默认 true
后端只允许执行插件 config.php 中声明过的 action。管理端点击按钮时会请求：

POST /api/system/general-plugins/action
复制
请求体：

{
  "code": "DemoPlugin",
  "action": "syncData",
  "params": {
    "api_url": "https://example.com"
  }
}
复制
管理后台执行 action 时会把当前设置弹窗里的表单值一起提交到 params。因此像“预览影响”“测试连接”“执行清理”这类操作，可以直接读取当前输入框内容，不要求用户先保存配置。

示例控制器方法：

<?php
namespace app\service\plugin\DemoPlugin\controller;

use app\service\plugin\PluginController;
use think\facade\Request;

class Index extends PluginController
{
    public function syncData()
    {
        $params = Request::post('params', []);
        $apiUrl = trim((string)($params['api_url'] ?? ''));

        // 在这里执行插件自己的业务逻辑

        return json([
            'code' => 200,
            'msg' => '同步完成',
            'data' => [
                'params' => $params,
            ],
        ]);
    }
}
复制
说明：

action 会经过管理员登录和管理员身份校验。
action 不要求插件启用，适合在插件设置阶段执行初始化或测试功能。
前端执行成功后会重新读取当前插件配置，方便动作修改配置后立即刷新表单。
自定义控制器和独立页面
插件可以提供自己的控制器方法，用于更复杂的管理功能。控制器放在插件目录的 controller/ 下，通过以下地址访问：

/plugin/:code/:controller/:action
复制
例如：

/plugin/DemoPlugin/Index/logs
复制
适合放置日志列表、复杂批处理、独立管理页等不适合塞进配置弹窗的能力。需要在设置弹窗展示入口时，可以在 config.php 的 menu 中声明页面地址；需要按钮触发后台逻辑时，优先使用 actions。

params.php
params.php 是插件配置保存文件。

安装插件时，如果不存在，会根据 form_fields 自动生成默认配置。它现在主要用于兼容和兜底，插件运行时应通过 plugconf() 或 PluginManager::config() 读取最终配置。

示例：

<?php return [
    'status' => 1,
];
复制
读取和保存插件配置
使用全局函数：

plugconf('DemoPlugin.status');
plugconf('DemoPlugin.status', 1);
plugconf('DemoPlugin');
复制
也可以直接使用：

app\service\plugin\PluginManager::config('DemoPlugin.status');
复制
触发和过滤 Hook
全局函数：

plughook('ShopInfoAfter', $payload);
$payload = plugfilter('ShopInfoAfter', $payload);
$result = pluguntil('SomeHook', $payload);
复制
管理器方法：

PluginManager::safeTrigger('ShopInfoAfter', $payload);
PluginManager::filter('ShopInfoAfter', $payload);
PluginManager::until('SomeHook', $payload);
复制
说明：

plughook() / safeTrigger()：执行所有启用插件的对应 Hook。
plugfilter() / filter()：执行 Hook 后返回被插件修改后的 $payload。
pluguntil() / until()：返回第一个非 null 且非 false 的插件结果。
Hook 异常会写入日志，不会中断主流程。
静态资源
插件静态资源放在：

app/service/plugin/DemoPlugin/static/
复制
使用：

plugstatic('DemoPlugin', 'js/app.js');
复制
返回：

/plugin/DemoPlugin/static/anMvYXBwLmpz
复制
说明：

静态资源名称会 base64 URL-safe 编码。
这样做是为了与参考机制保持一致，避免暴露原始文件名。
已接入 /plugin/:code/static/:file 路由，会从插件 static 目录读取文件。
ThinkPHP 风格目录
插件支持尽量接近 ThinkPHP 原生写法，可以在插件目录内写：

controller/
service/
model/
command/
view/
复制
对应命名空间：

app\service\plugin\DemoPlugin\controller
app\service\plugin\DemoPlugin\service
app\service\plugin\DemoPlugin\model
app\service\plugin\DemoPlugin\command
复制
控制器
文件：

app/service/plugin/DemoPlugin/controller/Index.php
复制
示例：

<?php
namespace app\service\plugin\DemoPlugin\controller;

use app\service\plugin\PluginController;

class Index extends PluginController
{
    public function index()
    {
        return $this->view('index', [
            'title' => '插件页面',
            'static' => $this->pluginStatic('js/app.js'),
        ]);
    }
}
复制
访问地址：

/plugin/DemoPlugin
/plugin/DemoPlugin/Index/index
复制
说明：

控制器默认继承 PluginController。
$this->view() 会渲染插件自己的 view 目录。
$this->pluginStatic() 会生成插件静态资源地址。
$this->config() 可读取插件配置。
View 渲染
文件：

app/service/plugin/DemoPlugin/view/index.html
复制
示例：

<h1>{$title}</h1>
<script src="{$static}"></script>
复制
也可以直接：

app\service\plugin\PluginManager::viewPath('DemoPlugin', 'index');
复制
Service
文件：

app/service/plugin/DemoPlugin/service/DemoService.php
复制
示例：

<?php
namespace app\service\plugin\DemoPlugin\service;

use app\service\plugin\PluginService;

class DemoService extends PluginService
{
    public function text(): string
    {
        return (string)$this->config('text', '默认文本');
    }
}
复制
Model
文件：

app/service/plugin/DemoPlugin/model/DemoLog.php
复制
示例：

<?php
namespace app\service\plugin\DemoPlugin\model;

use app\service\plugin\PluginModel;

class DemoLog extends PluginModel
{
    protected $name = 'plugin_demo_log';
}
复制
写法与 ThinkPHP Model 基本一致。

Command
文件：

app/service/plugin/DemoPlugin/command/Sync.php
复制
示例：

<?php
namespace app\service\plugin\DemoPlugin\command;

use app\service\plugin\PluginCommand;
use think\console\Input;
use think\console\Output;

class Sync extends PluginCommand
{
    protected function configure()
    {
        $this->setName('plugin:DemoPlugin:sync')
            ->setDescription('DemoPlugin 同步任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('sync ok');
    }
}
复制
插件命令会自动扫描 command/*.php，并合并到 config/console.php。

默认自动命令名格式：

plugin:DemoPlugin:sync
plugin:DemoPlugin:sync_data
复制
路由
已全局注册插件路由：

/plugin/<code>
/plugin/<code>/<controller>
/plugin/<code>/<controller>/<action>
/plugin/<code>/static/<file>
复制
插件必须启用后才能访问控制器和静态资源。

Hook 开发
插件可以在 config.php 中声明 Hook：

'hook' => [
    'SimpleCommand' => 'Hook',
]
复制
然后创建：

app/service/plugin/DemoPlugin/Hook.php
复制
示例：

<?php
namespace app\service\plugin\DemoPlugin;

use app\service\plugin\HookInterface;

class Hook implements HookInterface
{
    public function handle(&$payload = null)
    {
        // 修改 payload 或执行插件逻辑
        return true;
    }
}
复制
触发 Hook：

$data = ['name' => 'test'];
plughook('SimpleCommand', $data);
复制
或：

app\service\plugin\PluginManager::trigger('SimpleCommand', $data);
复制
页面插槽
页面插槽用于把插件自己的 CSS、JS 和内容挂载到指定页面位置。一个插件可以声明任意多个插槽 Hook；页面未放置对应宿主时不会显示内容。

已提供位置
入口	插槽
管理后台全局布局	admin.layout.header.after、admin.layout.content.before、admin.layout.content.after、admin.layout.footer.before
管理后台工作台	admin.dashboard.banner.after、admin.dashboard.metrics.after、admin.dashboard.main.after、admin.dashboard.side.after、admin.dashboard.todo.extra
管理后台已有页面	admin.system.setting.extra、admin.product.form.extra、admin.order.action.extra
网站首页（所有内置模板）	home.nav.after、home.content.before、home.hero.after、home.content.after、home.footer.before
首页插槽是公开接口，不需要登录；后台插槽会校验管理员登录态。首页 Hook 的 context.request 含 template、position、path，可按当前模板输出不同内容。

插件声明和输出
config.php：

'hook' => [
    'home.hero.after' => 'HomeHeroSlotHook',
    'admin.dashboard.banner.after' => 'AdminDashboardSlotHook',
],
复制
Hook 中向 items 追加内容：

<?php
namespace app\service\plugin\DemoPlugin;

use app\service\plugin\HookInterface;

class HomeHeroSlotHook implements HookInterface
{
    public function handle(&$payload = null)
    {
        if (!is_array($payload)) {
            return false;
        }

        $payload['items'][] = [
            'key' => 'demo-home-hero',
            'code' => 'DemoPlugin',
            'css' => plugstatic('DemoPlugin', 'css/home-slot.css'),
            'js' => plugstatic('DemoPlugin', 'js/home-slot.js'),
            'payload' => [
                'title' => '插件内容',
            ],
        ];
        return true;
    }
}
复制
插件 JS 注册到 window.EntropySlotPlugins，系统会调用其 mount：

window.EntropySlotPlugins = window.EntropySlotPlugins || {}
window.EntropySlotPlugins.DemoPlugin = {
  mount(el, payload, context) {
    el.textContent = payload.title || ''
  },
}
复制
新增页面位置时，在对应独立前端工程加入 PluginSlotHost 或首页工程的 HomePluginSlotHost，并使用 admin.* 或 home.* 的唯一插槽名；插件无需修改 PluginManager。

插件管理
扫描插件
app\service\plugin\PluginManager::scan();
复制
列出插件
app\service\plugin\PluginManager::list();
app\service\plugin\PluginManager::list(true); // 仅启用插件
复制
获取插件
app\service\plugin\PluginManager::get('DemoPlugin');
复制
安装插件
app\service\plugin\PluginManager::install('DemoPlugin');
复制
安装时会：

读取插件主类。
读取 config.php。
如果没有 params.php，则按 form_fields 生成默认配置。
执行插件 install()。
标记插件启用。
卸载插件
app\service\plugin\PluginManager::uninstall('DemoPlugin');
复制
卸载时会：

执行插件 uninstall()。
标记插件禁用。
启用 / 禁用插件
app\service\plugin\PluginManager::enable('DemoPlugin');
app\service\plugin\PluginManager::disable('DemoPlugin');
复制
启用状态保存到系统设置：

plugin_enabled
复制
已接入 Hook
全局
Hook	触发位置	说明
GlobalHook	每次业务 Hook 派发时	统一监听所有业务 Hook。接收 payload 的 hook 为当前 Hook 名，payload 为该 Hook 的原始引用参数。为避免递归，派发 GlobalHook 本身时不会再次调用全局监听。
GlobalRequestBefore	全局 HTTP 中间件、控制器执行前	可读取 request 和已脱敏的 params；设置 blocked=true、message、可选 status_code（400-599）可中止请求。OPTIONS 请求不会触发。
PluginMenuRoutesBeforeMerge	插件菜单合并到入口菜单树前	payload 包含 area、user 和可修改的 menus，可筛选或补充当前入口的插件菜单。
AdminMenuAfter	管理后台菜单树组装完成后	payload 包含 area、user 和可修改的 menus；在角色权限筛选后的插件菜单已合并。
UserCenterMenuAfter	用户中心菜单树组装完成后	payload 包含 area、user 和可修改的 menus；PC 用户中心使用 user 入口。
MobileUserMenuAfter	移动端用户中心菜单树组装完成后	payload 包含 area、user 和可修改的 menus；仅在请求携带 area=mobile-user 时触发。
PluginPageBeforeDispatch	插件控制器页面/方法调用前	payload 包含 code、controller、action、request；设置 abort=true，并可填写 message 或 response 中止调用。
GlobalRequestAfter	全局 HTTP 中间件、响应生成后	可读取 request 与 response.status_code，适合审计、指标和异步同步，不建议修改响应。
GlobalRequestBefore 中会将包含 password、token、secret 的字段及验证码字段替换为 ******；嵌套数组和 JSON 参数也会递归脱敏，不会向插件暴露原始敏感值。

全局监听示例：

<?php
namespace app\service\plugin\DemoPlugin;

use app\service\plugin\HookInterface;

class GlobalAuditHook implements HookInterface
{
    public function handle(&$payload = null)
    {
        if (!is_array($payload) || !is_array($payload['payload'] ?? null)) {
            return false;
        }

        if (($payload['hook'] ?? '') === 'OrderCreateBefore') {
            $orderPayload = &$payload['payload'];
            // 可按需审计或修改 $orderPayload['data']，也可设置 blocked/message。
        }

        return true;
    }
}
复制
在 config.php 中注册：

'hook' => [
    'GlobalHook' => 'GlobalAuditHook',
]
复制
系统配置
Hook	触发位置	说明
AdminSystemConfigBefore	管理员保存系统设置前	可读取/修改 settings，也可设置 blocked=true 和 message 拦截保存
AdminSystemConfigSaved	管理员保存系统设置、清理缓存后	payload 含 user、settings，可同步外部服务或清理插件缓存
AdminSystemConfigAfter	管理员读取系统设置后	可修改 settings 以追加或过滤返回配置
店铺
Hook	触发位置	说明
ShopInfoAfter	公开店铺信息返回前	可追加店铺展示字段
ShopPublicIndexAfter	店铺首页聚合接口返回前	可调整首页商品、分类、店铺信息
ShopProductsAfter	店铺公开商品列表返回前	payload 含店铺 ID、查询条件和可修改的商品分页 data。
ShopCategoriesAfter	店铺公开分类列表返回前	payload 含店铺 ID 和可修改的分类 data。
ShopCategoryPageAfter	分类独立公开页返回前	payload 含分类链接、店铺 ID、查询条件和可修改的页面 data。
ShopPaymentMethodsAfter	店铺公开支付方式返回前	payload 含店铺 ID、终端类型和可修改的支付方式 data；不含任何支付账户配置或密钥。
ShopDashboardAfter	商户仪表盘接口返回前	可追加分类区下方广告位、快捷入口、提示内容
ShopSettingBefore	商户保存店铺设置前	可修改保存数据
ShopSettingAfter	商户保存店铺设置后	可修改响应或同步插件配置
ShopOpened	店铺开通事务提交后	payload 含 user_id、shop、need_pay_deposit、deposit_amount
ShopOpenBefore	店铺开通条件检查完成后、开通事务前	payload 含 user_id、开店条件状态；可设置 blocked=true、message 拦截开通。
ShopSlugChangeBefore	商户重置或修改店铺链接前	payload 含店铺、旧/新链接和来源；可设置 blocked=true、message 拦截变更。
ShopSlugChanged	商户重置或修改店铺链接后	payload 含店铺、旧/新链接和来源，适合外部短链、域名或统计同步。
ShopEffectBeforeRender	店铺首页特效脚本生成前	payload 含特效编码与可修改的已保存配置；可设置 blocked=true、message 拦截生成。
ShopEffectRendered	店铺首页特效脚本生成后	payload 含特效编码与脚本长度，不提供脚本内容。
ShopSoldOrderListBefore	商户查询售出订单列表前	payload 含操作人、店铺 ID 与脱敏筛选条件；可设置 blocked=true、message 拦截。关键词正文不提供。
ShopSoldOrderListViewed	商户售出订单列表组装完成后	payload 含操作人、店铺 ID、脱敏订单摘要、分页和 visible_order_ids；可设置为当前页可显示的订单 ID 以过滤响应，不含买家联系方式、收货地址、卡密或物流单号。
ShopSoldOrderDetailBefore	商户查询售出订单详情前	payload 含操作人、店铺 ID 和订单摘要；可设置 blocked=true、message 拦截。不含买家联系方式、收货地址、卡密或物流单号。
ShopSoldOrderDetailViewed	商户售出订单详情组装完成后	payload 含操作人、店铺 ID 和订单摘要，用于审计或同步；不含买家联系方式、收货地址、卡密或物流单号。
订单
Hook	触发位置	说明
OrderCreateBefore	创建订单入库前	可调整订单字段
OrderCreateAfter	创建订单入库后	可记录扩展订单信息
OrderQuoteBefore	订单报价计算前	payload 含可修改的请求 data；设置 blocked=true、message 可拒绝报价。
OrderQuoteAfter	订单报价计算完成后	payload 含商品 ID、购买数量与可修改的 quote，适合增加展示字段或记录报价指标。
OrderQueryAfter	买家查询订单、组装返回内容后	可修改 data 调整订单查询返回内容
OrderPublicQueryBefore	公开订单查询完成验证码与限流校验、数据库查询前	payload 仅含查询方式、输入类型、分页和自动查询标记；可设置 blocked=true、message 拦截。不含查询关键词、联系方式、指纹或 IP。
OrderPublicQueryViewed	公开订单查询的既有 OrderQueryAfter 处理完成后	payload 含脱敏订单摘要、分页和 visible_order_ids；可设置为当前页可显示的订单 ID 以过滤响应。不含联系方式、卡密、通知信息或详情访问令牌。
OrderPublicDetailBefore	公开订单详情完成验证码或访问令牌验证、读取详情前	payload 含订单摘要和访问方式；可设置 blocked=true、message 拦截。不含验证码、访问令牌、联系方式、卡密或物流信息。
OrderPublicDetailViewed	公开订单详情组装完成后	payload 含订单摘要和访问方式，用于审计或外部同步；不含详情正文、联系方式、卡密、物流信息或访问令牌。
OrderUserListBefore	用户中心订单列表查询前	payload 含用户 ID 与脱敏筛选条件；可设置 blocked=true、message 拦截。关键词仅提供是否存在的标记。
OrderUserListViewed	用户中心订单列表组装完成后	payload 含用户 ID、脱敏订单摘要、分页和 visible_order_ids；可设置为当前页可显示的订单 ID 以过滤响应，不含卡密或联系方式。
OrderAdminListBefore	管理员订单列表查询前	payload 含操作人和脱敏筛选条件；可设置 blocked=true、message 拦截。关键词与时间范围正文不提供。
OrderAdminListViewed	管理员订单列表组装完成后	payload 含操作人、脱敏订单摘要、分页和 visible_order_ids；可设置为当前页可显示的订单 ID 以过滤响应，不含卡密、联系方式、收货地址或通知信息。
OrderQueryBefore	主动查询支付渠道前	可调整渠道查询参数
OrderQueryProviderAfter	主动查询支付渠道后	可调整渠道查询结果
OrderPendingShipment	订单进入待发货状态后	可处理人工发货/实物订单扩展逻辑
OrderComplete	订单支付并发货完成后	可处理返佣、通知、外部同步
OrderDelivered	管理员或商家手动处理待发货订单、事务提交后	payload 含订单摘要、delivery_mode、card_count，可同步物流或发货状态
OrderReceiptCodeBeforeSend	实物订单签收验证码发送前	payload 含订单摘要及脱敏手机号；可设置 blocked=true、message 拦截发送。
OrderReceiptCodeSent	实物订单签收验证码发送成功后	payload 含订单摘要及脱敏手机号，不提供验证码。
OrderReceiptBeforeConfirm	买家确认实物订单签收前	payload 含订单摘要；可设置 blocked=true、message 拦截确认。
OrderReceiptConfirmed	买家确认实物订单签收、收入解冻后	payload 含订单摘要，适合同步履约状态。
OrderQueryAfter 为历史兼容事件，仍可修改完整的公开查询响应 data。新增查询事件均使用脱敏订单摘要；其完成事件只支持通过 visible_order_ids 过滤当前页，不会将敏感返回字段交给插件。

OrderCreateBefore 支持设置 blocked=true 和 message 拦截下单，也可修改 data；OrderCreateAfter 会在订单记录创建后触发。OrderComplete 仅在订单支付、自动发货流程提交成功后触发。

支付与通知
Hook	触发位置	说明
PaymentInitiateBefore	调用支付渠道前	payload 含订单号、金额、渠道、支付类型和模式；可设置 blocked=true、message 拦截。为保证账务一致性，不能修改这些交易字段。
PaymentInitiated	支付渠道成功生成支付信息后	payload 含订单号、金额、渠道、模式、是否含支付链接/二维码；不含支付 URL、账户及密钥。
PlatformPaymentMethodsAfter	主站公开支付方式列表返回前	payload 含终端、是否全终端、是否充值场景和可修改的支付方式 data；不含支付账户配置或密钥。
NotificationDispatchBefore	通知场景开始分发前	payload 含场景和显式指定渠道；可设置 blocked=true、message 阻止该次投递，不含消息正文和接收人。
NotificationDispatched	通知被禁用或投递流程完成后	payload 含场景、渠道、状态、尝试/成功/失败计数，不含正文、接收人和上游响应。
VerificationCodeSendBefore	通用短信或邮箱验证码发送前	payload 含渠道、场景、脱敏接收人、接收人哈希和 IP；可设置 blocked=true、message 拦截，不提供验证码或完整联系方式。
VerificationCodeSent	通用短信或邮箱验证码发送成功后	payload 同发送前（不含拦截字段），不提供验证码。
VerificationCodeSendFailed	通用短信或邮箱验证码发送失败后	payload 含渠道、场景、脱敏接收人和固定错误标记，不提供上游错误或验证码。
上传
Hook	触发位置	说明
UploadBefore	图片或视频校验完成、写入存储前	payload 含用户摘要、媒体类型、上传类型、大小和扩展名；可设置 blocked=true、message 拦截。
UploadCompleted	图片或视频写入成功后	payload 含用户摘要、文件元数据及路径/URL，不含文件内容。
用户
Hook	触发位置	说明
UserRegistered	主站用户注册成功、默认注册赠送余额处理后	可处理注册奖励、用户分组、外部同步；payload 含 user_id、username、channel
UserRegistrationCreated	主站用户注册完成、奖励清单准备后	可读取注册渠道、上下文与默认奖励清单，适合审计或补充注册标签。
UserRegistrationRewardsBefore	注册奖励实际发放前	可修改 rewards 追加普通余额、保证金或运营余额；设置 blocked=true 可跳过本次全部注册奖励。每项包含 key、asset_type、amount、remark。
UserRegistrationRewardsGranted	注册奖励处理完成后	payload 含原奖励清单与 granted_rewards，每项带 granted 状态；适合发券、通知或外部同步。
UserLoginAfter	用户账号密码或验证码登录成功后	payload 含 user_id、username、login_type、ip
UserLoginBefore	用户登录验证码校验通过后、查询账号前	payload 含登录账号、登录方式与 IP；可设置 blocked=true、message 拦截登录。
AdminLoginBefore	管理员登录验证码校验通过后、查询账号前	payload 含用户名、IP、User-Agent；可设置 blocked=true、message 拦截登录。
AdminLoginAfter	管理员登录成功后	payload 含 admin_id、用户名和 IP，适合审计、同步或登录告警。
UserRegistrationBefore	手机或邮箱验证码验证通过后、写入用户前	payload 含用户名、账号类型和邀请码；可设置 blocked=true、message 拦截注册。
UserProfileUpdated	用户绑定手机、修改邮箱或密码成功后	payload 含 user_id、changes、source；密码变更仅标记为 true，不提供密码内容
UserProfileUpdateBefore	用户绑定手机、修改邮箱或密码写入前	payload 含 user_id、changes、source；可设置 blocked=true、message 拦截，密码不会提供原文。
UserPasswordResetBefore	找回密码验证码验证后、写入新密码前	payload 含 user_id、账号类型和来源；可设置 blocked=true、message 拦截，密码不会提供原文。
UserPasswordResetAfter	找回密码成功后	payload 含 user_id、账号类型和来源；适合安全审计或通知。
UserCancellationBeforeApply	用户注销申请写入前	payload 含用户 ID 与申请说明；可设置 blocked=true、message 拦截申请。
UserCancellationApplied	用户注销申请创建后	payload 含用户 ID 与申请记录，适合通知、工单或外部审批同步。
UserCancellationReviewBefore	管理员审核注销申请前	payload 含申请 ID、操作人、动作与审核说明；可设置 blocked=true、message 拦截审核。
UserCancellationReviewed	管理员审核注销申请后	payload 含申请 ID、操作人、审核动作与申请记录。
AdminUserDeleteBefore	管理员删除普通用户或管理员前	payload 含目标类型、用户 ID、用户名和操作人；可设置 blocked=true、message 拦截删除。
AdminUserDeleted	管理员删除普通用户或管理员后	payload 含目标类型、用户 ID、用户名和操作人，适合外部账户清理。
AdminUserSaveBefore	管理员新增或编辑普通用户、管理员前	payload 含目标类型、动作、操作人和白名单 changes；可设置 blocked=true、message 拦截。密码仅用 password_changed 标记。
AdminUserCreated	管理员新增普通用户或管理员后	payload 含目标类型、用户 ID、操作人、白名单 changes 与密码是否变更标记。
AdminUserUpdated	管理员编辑普通用户或管理员后	payload 含目标类型、用户 ID、操作人、白名单 changes 与密码是否变更标记。
UserRiskControlUpdated	管理员保存用户支付风控设置后	payload 含目标 user_id、允许的风控开关、操作人 ID
UserRealNameAuditCompleted	管理员保存、通过或拒绝实名认证后	payload 含用户 ID、操作类型、实名状态、操作人 ID；不含姓名或身份证号
InviteCodeBeforeBind	注册填写邀请码或已注册用户手动绑定前	payload 含邀请码、邀请人、来源、待绑定用户/用户名和可修改的 rate_group_id；可设置 blocked=true、message 拦截绑定。费率组不存在会拒绝操作。
InviteCodeBound	用户注册或手动绑定邀请码后	payload 含邀请码、邀请人、被邀请人 ID 和绑定来源。
InviteCodeCashbackGranted	邀请码注册/绑定返现到账后	payload 含邀请码、邀请双方 ID、返现金额与场景。
邀请码管理
Hook	触发位置	说明
InviteCodesGenerateBefore	管理员或商户批量生成邀请码前	payload 含操作人、数量、长度、是否带前缀和备注长度；可设置 blocked=true、message 拦截，不提供邀请码内容或前缀。
InviteCodesGenerated	管理员或商户批量生成邀请码后	payload 含操作人、生成条件和实际生成数量，不提供邀请码内容。
InviteCodesDeleteBefore	管理员或商户批量删除邀请码前	payload 含操作人、邀请码 ID 列表和匹配数量；可设置 blocked=true、message 拦截，不提供邀请码内容。
InviteCodesDeleted	管理员或商户批量删除邀请码后	payload 含操作人、邀请码 ID 列表和删除前匹配数量，不提供邀请码内容。
用户通知
Hook	触发位置	说明
UserNotificationRead	用户标记一条站内通知已读后	payload 含用户、通知 ID、通知类型和全局/个人范围，不含通知标题或正文。
UserNotificationsReadAll	用户标记全部站内通知已读后	payload 含用户 ID、本次标记的个人通知和全局通知数量。
UserNotificationDeleted	用户删除个人站内通知后	payload 含用户、通知 ID 和通知类型，不含通知正文。
AdminUserNotificationBeforeSend	管理员向全部或指定用户投递站内通知前	payload 含操作人、目标范围、渠道、类型和标题/正文长度；可设置 blocked=true、message 拦截，不含通知正文或联系方式。
AdminUserNotificationSent	管理员完成用户站内通知投递后	payload 含操作人、目标范围、渠道和写入数量；全员通知额外含通知摘要，不含正文。
AdminUserNotificationUpdateBefore	管理员编辑用户站内通知前	payload 含操作人、通知摘要与修改标记；可设置 blocked=true、message 拦截，不含正文。
AdminUserNotificationUpdated	管理员编辑用户站内通知后	payload 含操作人和更新后的通知摘要，不含正文。
AdminUserNotificationDeleteBefore	管理员删除用户站内通知前	payload 含操作人和通知摘要；可设置 blocked=true、message 拦截，不含正文。
AdminUserNotificationDeleted	管理员删除用户站内通知后	payload 含操作人和通知摘要，不含正文。
商品
Hook	触发位置	说明
ProductSaveBefore	商品新增或编辑的校验前	可读取/修改 data；设置 blocked=true 和 message 可拒绝保存。payload 含 user、is_update。
ProductCreated	新商品保存成功后	payload 含 user、product、is_update=false
ProductUpdated	商品编辑保存成功后	payload 含 user、product、is_update=true
ProductDeleted	商品删除成功后	payload 含 user、product_ids、deleted_count
ProductsOnShelf	批量上架成功后	payload 含 user、实际成功的 product_ids
ProductsOffShelf	批量普通下架成功后	payload 含 user、product_ids、下架原因
ProductsForceOfflineChanged	管理员修改强制下架状态后	payload 含 user、product_ids、is_forced_offline
ProductUuidChangeBefore	商品链接标识重置前	payload 含商品 ID、旧/新 UUID；可设置 blocked=true、message 拦截变更。
ProductUuidChanged	商品链接标识重置后	payload 含商品 ID、旧/新 UUID，适合短链、搜索或外部系统同步。
ProductListAfter	管理端或用户中心商品列表返回前	payload 含当前用户、查询条件、可修改的分页 data 和 from_cache；修改仅作用于本次响应。
ProductApprovalBefore	管理员通过或驳回商品审核前	payload 含操作人、审核动作、商品摘要；驳回时可修改 reason，或设置 blocked=true、message 拦截。
ProductApproved	管理员审核通过商品后	payload 含操作人和商品摘要，适合上架同步、审计或通知。
ProductRejected	管理员驳回商品审核后	payload 含操作人、商品摘要和拒绝原因。
分类
Hook	触发位置	说明
CategorySaveBefore	分类新增或编辑的校验前	可读取/修改 data；设置 blocked=true、message 可拒绝保存。payload 含 user、is_update。
CategoryCreated	分类新增成功后	payload 含 user、分类数据和 is_update=false。
CategoryUpdated	分类编辑成功后	payload 含 user、分类数据和 is_update=true。
CategoriesDeleted	分类及其关联商品删除完成后	payload 含 user、实际删除的 category_ids 和 deleted_count。
CategorySlugChangeBefore	分类链接标识重置前	payload 含分类 ID、旧/新链接；可设置 blocked=true、message 拦截变更。
CategorySlugChanged	分类链接标识重置后	payload 含分类 ID、旧/新链接，适合同步分类短链或缓存。
CategoryListAfter	管理端或用户中心分类列表返回前	payload 含当前用户、查询条件、可修改的分页 data 和 from_cache；修改仅作用于本次响应。
角色与权限组
Hook	触发位置	说明
RoleSaveBefore	超级管理员新增或编辑权限组前	payload 含操作人、动作、角色摘要和编辑后的摘要；可设置 blocked=true、message 拦截。仅提供 rule_count，不提供完整权限规则。
RoleCreated	超级管理员新增权限组后	payload 含操作人和角色摘要。
RoleUpdated	超级管理员编辑权限组、刷新关联菜单缓存后	payload 含操作人、角色摘要和编辑后的摘要。
RoleDeleteBefore	超级管理员删除权限组前	payload 含操作人和角色摘要；可设置 blocked=true、message 拦截。
RoleDeleted	超级管理员删除权限组后	payload 含操作人和角色摘要。
卡密
Hook	触发位置	说明
CardKeysAddBefore	批量写入卡密前	可设置 blocked=true 和 message 拦截；payload 仅含商品摘要和数量，不含卡密正文
CardKeysAdded	批量写入卡密、刷新缓存后	payload 含 user、商品摘要、写入数量
CardKeysDeleted	软删除卡密、事务提交后	payload 含 user、card_ids、数量
CardKeysRestored	回收站恢复卡密、事务提交后	payload 含 user、card_ids、数量
CardKeysPermanentlyDeleted	彻底删除回收站卡密、事务提交后	payload 含 user、card_ids、数量
CardKeysCleared	清空商品未售卡密、事务提交后	payload 含 user、商品摘要、清空数量
CardKeysClaimed	买家查看可取卡订单、首次标记取卡后	payload 含 trade_no、总数、实际取卡数、取卡时间；不含卡密正文
客服
Hook	触发位置	说明
CustomerServiceSessionOpened	买家创建客服会话后	payload 含会话摘要、创建来源
CustomerServiceSessionReopened	已结束会话被买家发送消息重新拉起后	payload 含会话摘要、拉起来源
CustomerServiceSessionsClosed	12 小时无消息的会话自动结束后	payload 含会话 ID 列表、筛选的商家/店铺 ID、结束原因
CustomerServiceBuyerMessageBefore	买家消息校验通过、写入前	可修改 message.message_type、message.content，或设置 blocked=true、message_text 拦截
CustomerServiceBuyerMessageSent	买家消息写入、事务提交后	payload 含会话摘要和已规范化的消息
CustomerServiceSellerMessageBefore	商家消息校验通过、写入前	可修改消息或使用 blocked/message_text 拦截；结束会话本身不会进入该 Hook
CustomerServiceSellerMessageSent	商家消息写入、事务提交后	payload 含会话摘要和已规范化的消息
CustomerServiceBlacklistChanged	商家调整客服拉黑状态后	payload 含会话摘要、操作人、is_blacklisted
投诉与退款
Hook	触发位置	说明
ComplaintCreateBefore	投诉校验完成、写入前	可设置 blocked=true、message 拦截；订单摘要已移除联系方式等隐私字段
ComplaintCreated	投诉、冻结金额事务提交及通知后	payload 含脱敏投诉、订单摘要、冻结金额
ComplaintCancelBefore	买家撤销投诉、解冻及删除前	payload 含投诉摘要、买家操作人；可设置 blocked=true、message 拦截。不含投诉密码、联系方式或投诉内容。
ComplaintCancelled	买家撤销投诉、事务提交后	payload 含脱敏投诉、订单摘要、取消原因
ComplaintReplyBefore	买家、商家或管理员回复投诉、写入前	payload 含投诉摘要、reply、actor、操作人 ID；可修改 reply 或设置 blocked=true、message 拦截。不含投诉密码、联系方式。
ComplaintReplied	买家、商家或管理员回复投诉后	payload 含脱敏投诉、回复正文、actor、操作人 ID
ComplaintResolveBefore	管理员投诉裁决、退款或解冻事务开始前	payload 含投诉/订单摘要、裁决结果、请求退款金额、是否 API 退款和操作人；可设置 blocked=true、message 拦截。不含支付配置、联系方式。
ComplaintResolved	管理员裁决、退款/解冻事务提交后	payload 含裁决结果、退款金额、是否 API 退款及脱敏订单/投诉摘要
ComplaintAdminDeleteBefore	管理员批量删除投诉、解冻及删除前	payload 含操作人、投诉 ID 与摘要、总数、未结案数量；可设置 blocked=true、message 拦截。
ComplaintAdminDeleted	管理员批量删除投诉、事务提交后	payload 含操作人、投诉 ID 与摘要、总数、未结案数量。
对接站点与商品
Hook	触发位置	说明
DockingSiteCreated	用户保存新对接站点后	payload 含用户、站点摘要；不含配置和 API 密钥
DockingSiteUpdated	用户更新对接站点后	payload 含用户、站点摘要；不含配置和 API 密钥
DockingSiteSaveBefore	用户新建或编辑对接站点、落库前	payload 含用户、站点摘要、action 和字段变更标记；可设置 blocked=true、message 拦截。不含站点配置、上游地址、对接码和 API 密钥。
DockingSiteDeleteBefore	用户删除对接站点前	payload 含用户和站点摘要；可设置 blocked=true、message 拦截。不含配置和 API 密钥。
DockingSiteDeleted	用户删除对接站点后	payload 含用户、站点摘要；不含配置和 API 密钥
DockingProductsFetchBefore	调用上游商品列表前	可修改 query.name/query.size，或设置 blocked=true、message 拦截；不提供上游密钥
DockingProductsFetched	上游商品列表成功返回后	可修改 data，用于过滤、补充或映射上游商品
DockingProductsFetchFailed	上游商品列表调用失败后	payload 含脱敏查询条件和错误信息，可用于监控、告警
DockingUserUpdateBefore	管理员更新平台对接用户前	payload 含操作人、对接用户摘要和各字段变更标记；可设置 blocked=true、message 拦截。密码、余额数值和联系方式不提供。
DockingUserUpdated	管理员更新平台对接用户后	payload 含操作人、更新后的对接用户摘要与变更标记。
DockingUserDeleteBefore	管理员删除平台对接用户前	payload 含操作人和对接用户摘要；可设置 blocked=true、message 拦截。
DockingUserDeleted	管理员删除平台对接用户后	payload 含操作人和删除前的对接用户摘要。
DockingUserRegistrationBefore	对接用户验证码校验通过、创建账户前	payload 含店铺 ID、用户名和账户类型；可设置 blocked=true、message 拦截。密码、验证码与联系方式不提供。
DockingUserRegistered	对接用户账户创建成功后	payload 含店铺 ID、用户名、账户类型和账户摘要，不提供密码、联系方式或 API Key。
DockingUserLoginBefore	对接用户账号密码验证通过、生成登录态前	payload 含账户摘要、登录方式、请求店铺和 IP；可设置 blocked=true、message 拦截。密码、Token、联系方式和 API Key 不提供。
DockingUserLoggedIn	对接用户登录态创建后	payload 含账户摘要、登录方式、实际店铺和 IP，不提供 Token。
DockingOrderListBefore	管理员查询指定对接用户订单列表前	payload 含操作人、对接用户 ID 与脱敏筛选条件；可设置 blocked=true、message 拦截。关键词正文不提供。
DockingOrderListViewed	管理员查询指定对接用户订单列表后	payload 含操作人、对接用户 ID、脱敏订单摘要、分页和 visible_order_ids；可设置为当前页可显示的订单 ID 以过滤响应，不含联系方式、卡密或通知信息。
DockingUserPasswordBeforeChange	对接用户找回或主动修改密码前	payload 含账户摘要和来源；可设置 blocked=true、message 拦截。密码、验证码和联系方式不提供。
DockingUserPasswordChanged	对接用户找回或主动修改密码后	payload 含账户摘要和来源，不提供密码。
DockingUserProfileBeforeUpdate	对接用户修改 QQ 资料前	payload 含账户摘要、来源和变更标记；可设置 blocked=true、message 拦截，不提供 QQ 原文。
DockingUserProfileUpdated	对接用户修改 QQ 资料后	payload 含更新后的账户摘要和变更标记。
DockingUserAccountBeforeChange	对接用户绑定邮箱或手机号前	payload 含账户摘要、目标账户类型和来源；可设置 blocked=true、message 拦截。验证码和联系方式不提供。
DockingUserAccountChanged	对接用户绑定邮箱或手机号后	payload 含更新后的账户摘要和账户类型，不提供联系方式。
平台文章
Hook	触发位置	说明
PlatformArticleSaveBefore	管理员新增或编辑平台文章前	payload 含操作人、动作、文章摘要和编辑后的摘要；可设置 blocked=true、message 拦截。文章正文只提供长度。
PlatformArticleCreated	管理员新增平台文章、清理文章缓存后	payload 含操作人和文章摘要，不提供正文。
PlatformArticleUpdated	管理员编辑平台文章、清理文章缓存后	payload 含操作人、更新后的文章摘要和编辑前后的摘要，不提供正文。
PlatformArticleDeleteBefore	管理员删除平台文章前	payload 含操作人和文章摘要；可设置 blocked=true、message 拦截。
PlatformArticleDeleted	管理员删除平台文章、清理文章缓存后	payload 含操作人和删除前文章摘要。
优惠券
Hook	触发位置	说明
CouponCreated	平台或商家优惠券创建后	payload 含用户、优惠券数据
CouponUpdated	优惠券状态更新后	payload 含用户、优惠券数据
CouponDeleted	优惠券软删除后	payload 含用户、删除前优惠券数据
CouponSaveBefore	平台或商家创建/更新优惠券前	payload 含用户、优惠券摘要、动作与更新字段；可设置 blocked=true、message 拦截保存。
CouponDeleteBefore	优惠券软删除前	payload 含用户与优惠券摘要；可设置 blocked=true、message 拦截删除。
店铺资质与举报
Hook	触发位置	说明
ShopQualificationSubmitBefore	商户提交或重新提交资质前	payload 仅含用户 ID、资质类型、附件数量和是否重新提交；可设置 blocked=true、message 拦截，不含姓名、证件号和文件地址。
ShopQualificationSubmitted	商户资质提交成功后	payload 含资质 ID、用户 ID、类型、附件数量和状态。
ShopQualificationAuditBefore	管理员审核资质前	payload 含资质 ID、用户 ID、操作人、状态和审核原因；可修改状态/原因，或设置 blocked=true、message 拦截。
ShopQualificationAudited	管理员审核资质后	payload 含资质 ID、用户 ID、操作人和最终状态。
ShopQualificationDeleteBefore	管理员删除资质前	payload 含资质 ID、用户 ID、操作人；可设置 blocked=true、message 拦截。
ShopQualificationDeleted	管理员删除资质后	payload 含资质 ID、用户 ID、操作人。
ShopReportBeforeSubmit	访客提交店铺举报前	payload 含店铺 ID 与可修改的举报内容；可设置 blocked=true、message 拦截。
ShopReportSubmitted	访客提交店铺举报后	payload 含举报 ID、店铺 ID 与举报内容；不含举报 IP 与定位。
ShopReportHandleBefore	管理员处理店铺举报前	payload 含操作人、举报摘要、原/目标状态和处理备注长度；可设置 blocked=true、message 拦截。举报与备注正文不提供。
ShopReportHandled	管理员处理店铺举报及健康分变更后	payload 含操作人、更新后的举报摘要、原/目标状态和处理备注长度。
ShopReportDeleteBefore	管理员删除店铺举报前	payload 含操作人和举报摘要；可设置 blocked=true、message 拦截。
ShopReportDeleted	管理员删除店铺举报后	payload 含操作人和删除前举报摘要。
货源广场
Hook	触发位置	说明
SupplyStoreSaveBefore	商户新增或编辑货源广场店铺前	payload 含商户、动作、店铺摘要和编辑后的摘要；可设置 blocked=true、message 拦截。仅提供商品数量和描述是否存在，不提供联系方式、商品内容或描述正文。
SupplyStoreCreated	商户新增货源广场店铺后	payload 含商户、店铺摘要和审核状态。
SupplyStoreUpdated	商户编辑货源广场店铺后	payload 含商户、更新后的店铺摘要和编辑前后的摘要。
SupplyTagSaveBefore	管理员新增或编辑货源标签前	payload 含操作人、动作、标签摘要和编辑后的摘要；可设置 blocked=true、message 拦截。标签 HTML 仅提供长度。
SupplyTagCreated	管理员新增货源标签后	payload 含操作人和标签摘要，不提供标签 HTML。
SupplyTagUpdated	管理员编辑货源标签后	payload 含操作人、更新后的标签摘要和编辑前后的摘要，不提供标签 HTML。
SupplyTagDeleteBefore	管理员删除货源标签前	payload 含操作人和标签摘要；可设置 blocked=true、message 拦截。
SupplyTagDeleted	管理员删除货源标签及关联展示位后	payload 含操作人和标签摘要。
SupplyStoreDisplayBeforeUpdate	管理员调整货源店铺置顶权重和标签前	payload 含操作人、店铺摘要和即将保存的置顶权重/标签 ID；可设置 blocked=true、message 拦截。
SupplyStoreDisplayUpdated	管理员保存货源店铺展示配置后	payload 含操作人、更新后的店铺展示摘要。
SupplyStoreAuditBefore	管理员通过或驳回货源店铺审核前	payload 含操作人、动作、店铺摘要和驳回原因长度；可设置 blocked=true、message 拦截，不提供原因正文。
SupplyStoreApproved	管理员通过货源店铺审核后	payload 含操作人和店铺摘要。
SupplyStoreRejected	管理员驳回货源店铺审核后	payload 含操作人、店铺摘要和驳回原因长度。
SupplyStoreStatusBeforeChange	管理员上架、下架、强制下架或解除强制下架前	payload 含操作人、动作、店铺摘要和可选原因长度；可设置 blocked=true、message 拦截。
SupplyStoreStatusChanged	管理员变更货源店铺上下架状态后	payload 含操作人、动作和更新后的店铺摘要。
代理与对接
Hook	触发位置	说明
AgentDefaultLevelBeforeUpdate	商户修改本店默认代理等级前	payload 含商户、店铺摘要和目标等级标识；可设置 blocked=true、message 拦截。
AgentDefaultLevelUpdated	商户修改本店默认代理等级后	payload 含商户、店铺摘要和已保存的等级标识。
AgentSupplyStatusBeforeChange	商户开启或关闭供货功能前	payload 含商户、店铺摘要、原状态和目标状态；可设置 blocked=true、message 拦截。
AgentSupplyStatusChanged	商户供货功能状态变更后	payload 含商户、更新后的店铺摘要和目标状态。
AgentCodeBeforeChange	商户生成或修改店铺对接码前	payload 含商户、店铺摘要、旧对接码哈希/长度和是否自动生成；可设置 blocked=true、message 拦截。对接码原文不提供。
AgentCodeChanged	商户生成或修改店铺对接码后	payload 含商户和更新后的店铺摘要；对接码仅提供 SHA-256 哈希和长度。
AgentProductDistributionBeforeChange	商户开启或关闭商品代理分销前	payload 含商户、商品摘要、原状态和目标状态；可设置 blocked=true、message 拦截。
AgentProductDistributionChanged	商户商品代理分销状态变更后	payload 含商户和更新后的商品摘要。
AgentRelationshipBeforeConnect	商户绑定或重新启用上级供货商前	payload 含商户、上下游店铺 ID、是否重新启用；可设置 blocked=true、message 拦截。
AgentRelationshipConnected	商户绑定或重新启用上级供货商后	payload 含商户、上下游店铺 ID 和关系状态。
AgentProductImportBefore	商户从已对接供货商导入商品前	payload 含商户、上下游店铺、源商品摘要、加价方式/数值和价格是否变化；可设置 blocked=true、message 拦截。
AgentProductImported	商户导入对接商品后	payload 含商户、上下游店铺、源商品摘要和新商品摘要，不含商品正文或成本金额。
AgentRelationshipBeforeDisconnect	商户解除一个或全部上级供货商前	payload 含商户、目标上级店铺和受影响关系摘要；可设置 blocked=true、message 拦截。
AgentRelationshipDisconnected	商户解除一个或全部上级供货商后	payload 含商户、目标上级店铺和已停用的关系摘要。
SubAgentStatusBeforeChange	供货商修改下级代理关系状态前	payload 含供货商、关系摘要、原状态和目标状态；可设置 blocked=true、message 拦截。
SubAgentStatusChanged	供货商修改下级代理关系状态后	payload 含供货商和更新后的关系摘要。
SubAgentLevelBeforeUpdate	供货商修改下级代理等级前	payload 含供货商、关系摘要、原等级和目标等级；可设置 blocked=true、message 拦截。
SubAgentLevelUpdated	供货商修改下级代理等级后	payload 含供货商和更新后的关系摘要。
结算批次
Hook	触发位置	说明
WithdrawalBatchCreated	管理员生成支付宝提现结算批次、事务提交后	payload 含批次号、金额/数量汇总和提现 ID 列表，不含收款账户与实名信息
WithdrawalBatchTransferBefore	管理员调用提现批次代付前	payload 含操作人、批次、代付通道、支付账户 ID、提现数量与金额汇总；可设置 blocked=true、message 拦截。不含收款账户、实名、支付账户配置或密钥。
WithdrawalBatchTransferred	管理员批次代付处理完成后	payload 含批次号、渠道、成功/失败/处理中/跳过计数和操作人 ID，不含单笔收款信息
提现审核与账户
Hook	触发位置	说明
WithdrawalAuditBefore	管理员审核单笔提现前	payload 含操作人、提现摘要、动作和备注长度；可设置 blocked=true、message 拦截。不含收款账户、实名或备注正文。
WithdrawalAudited	管理员审核单笔提现、事务提交后	payload 含操作人、更新后的提现摘要、动作和备注长度。
WithdrawalBatchAuditBefore	管理员批量审核提现前	payload 含操作人、提现 ID 列表、数量、动作和备注长度；可设置 blocked=true、message 拦截，不含收款账户。
WithdrawalBatchAudited	管理员批量审核提现、事务提交后	payload 含操作人、提现 ID 列表、动作和实际处理数量。
WithdrawalBatchStatusBeforeChange	管理员修改提现批次状态前	payload 含操作人、批次、目标状态和备注长度；可设置 blocked=true、message 拦截。
WithdrawalBatchStatusChanged	管理员修改提现批次状态、事务提交后	payload 含操作人、批次、目标状态和实际处理数量。
TransferAccountSaveBefore	管理员新增或编辑平台代付账户前	payload 含操作人、动作、账户摘要和编辑后的摘要；可设置 blocked=true、message 拦截。仅提供私钥/证书是否已配置，不提供配置、路径或密钥。
TransferAccountCreated	管理员新增平台代付账户后	payload 含操作人和账户摘要，不提供配置或密钥。
TransferAccountUpdated	管理员编辑平台代付账户后	payload 含操作人、更新后的账户摘要和编辑前后的摘要，不提供配置或密钥。
TransferAccountDeleteBefore	管理员删除平台代付账户前	payload 含操作人和账户摘要；可设置 blocked=true、message 拦截。
TransferAccountDeleted	管理员删除平台代付账户后	payload 含操作人和账户摘要。
TransferAccountToggleBefore	管理员切换平台代付账户状态前	payload 含操作人、账户摘要、原状态和目标状态；可设置 blocked=true、message 拦截。
TransferAccountToggled	管理员切换平台代付账户状态后	payload 含操作人、更新后的账户摘要和原状态。
WithdrawalAccountAdminUpdateBefore	管理员直接编辑用户提现收款账户前	payload 含操作人、收款账户摘要和字段变更标记；可设置 blocked=true、message 拦截。不含账户号、实名或收款码地址。
WithdrawalAccountAdminUpdated	管理员直接编辑用户提现收款账户后	payload 含操作人、更新后的收款账户摘要和字段变更标记。
WithdrawalAccountAuditBefore	管理员审核用户提现收款账户新增/编辑/删除申请前	payload 含操作人、收款账户摘要、审核动作和原因长度；可设置 blocked=true、message 拦截。不含待审核账户资料。
WithdrawalAccountAudited	管理员审核用户提现收款账户申请后	payload 含操作人、账户摘要、审核动作和原因长度。
WithdrawalAccountDeleteBefore	管理员直接删除用户提现收款账户前	payload 含操作人和收款账户摘要；可设置 blocked=true、message 拦截。
WithdrawalAccountDeleted	管理员直接删除用户提现收款账户后	payload 含操作人和收款账户摘要。
知识文章
Hook	触发位置	说明
KnowledgeArticleSaveBefore	知识文章新建或编辑、落库前	payload 含用户、文章摘要、is_update；可修改标题、摘要、封面、排序、状态，或设置 blocked=true、message 拦截。不含正文。
KnowledgeArticleCreated	知识文章新建成功后	payload 含用户、文章摘要和 is_update=false；不含文章正文
KnowledgeArticleUpdated	知识文章编辑成功后	payload 含用户、文章摘要和 is_update=true；不含文章正文
KnowledgeArticleDeleteBefore	知识文章及关联关系删除前	payload 含用户、文章摘要；可设置 blocked=true、message 拦截。不含正文。
KnowledgeArticleDeleted	知识文章及关联关系删除后	payload 含用户、删除前文章摘要；不含文章正文
钱包与提现
Hook	触发位置	说明
WalletBalanceChanged	WalletService 自己的余额变动事务提交后	payload 含 user_id、amount、type、remark、asset_type、biz_type、biz_no；外层事务中的 changeBalanceInTransaction() 不会提前触发。
WalletRechargeBeforeCreate	用户创建保证金或运营余额充值单前	payload 含用户、资产类型、金额和支付方式；设置 blocked=true、message 可阻止充值。
WalletRechargeCreated	保证金或运营余额充值单创建后	payload 含用户、订单号、金额、资产类型、支付方式；不含支付账户敏感信息。
WalletRechargePaid	保证金或运营余额充值到账、事务提交后	payload 含用户、订单号、金额、资产类型与支付方式，适合通知和账务同步。
WalletWithdrawalBeforeSubmit	三类资产提现校验前	与旧 Hook user.wallet.withdraw.beforeSubmit 使用同一 payload；可设置 blocked=true、message 或修改 params。
WalletWithdrawalCreated	余额、保证金、运营余额提现申请创建后	payload 含 user_id、withdrawal、asset_type
AdminWalletAdjustBefore	管理员调整用户资产前	可修改 asset_type、action、amount、remark，或设置 blocked=true、message 拦截。
AdminWalletAdjusted	管理员调整用户资产后	payload 含目标用户、操作人、资产类型、操作、金额和备注。
商户自定义支付
Hook	触发位置	说明
UserPaymentConfigCreated	商户创建自定义支付接口后	payload 含用户、操作类型和支付接口数据。
UserPaymentConfigUpdated	商户更新自定义支付接口后	payload 含用户、操作类型和更新后的支付接口数据。
UserPaymentConfigDeleted	商户删除自定义支付接口后	payload 含用户、操作类型和支付接口 ID。
UserPaymentConfigAdminStatusUpdated	管理员切换自定义支付接口状态后	payload 含所属用户、操作人和支付接口摘要。
UserPaymentConfigAdminDeleted	管理员删除自定义支付接口后	payload 含所属用户、操作人和删除前支付接口摘要。
UserPaymentConfigForceDisabledChanged	管理员调整接口强制停用状态后	payload 含所属用户、操作人和更新后的支付接口摘要。
ShopPaymentAccountBeforeCreate	商户新增支付账户前	可修改账户的名称、权重、启用状态、排序，或设置 blocked=true、message 拦截。账户配置密钥不会进入 payload。
ShopPaymentAccountCreated	商户新增支付账户后	payload 含用户、操作人、支付接口和账户数据；不含账户配置密钥。
ShopPaymentAccountBeforeUpdate	商户编辑支付账户前	可修改 changes，或设置 blocked=true、message 拦截。
ShopPaymentAccountUpdated	商户编辑支付账户后	payload 含用户、操作人、支付接口、账户 ID 与更新后的账户数据。
ShopPaymentAccountDeleted	商户删除支付账户后	payload 含用户、操作人、支付接口与删除前账户数据。
ShopPaymentAccountToggled	商户切换支付账户启用状态后	payload 含用户、操作人、支付接口、账户 ID 与启用状态。
ShopPaymentAccountStrategyUpdated	商户修改支付账户轮询策略后	payload 含用户、操作人、支付接口与轮询策略。
PaymentConfigSaveBefore	管理员创建或编辑平台支付接口前	payload 含操作人、动作、接口摘要和编辑后的摘要；可设置 blocked=true、message 拦截，不提供接口密钥或 config 内容。
PaymentConfigCreated	管理员创建平台支付接口、清理支付方式缓存后	payload 含操作人和接口摘要。
PaymentConfigUpdated	管理员编辑平台支付接口、清理支付方式缓存后	payload 含操作人、接口摘要和编辑后的摘要。
PaymentConfigDeleteBefore	管理员删除平台支付接口前	payload 含操作人和接口摘要；可设置 blocked=true、message 拦截。
PaymentConfigDeleted	管理员删除平台支付接口、清理支付方式缓存后	payload 含操作人和接口摘要。
PaymentAccountSaveBefore	管理员新增或编辑平台支付账户前	payload 含操作人、动作、账户摘要和编辑后的摘要；可设置 blocked=true、message 拦截。账户 account_config 不会进入 payload，仅使用 config_updated 标记。
PaymentAccountCreated	管理员新增平台支付账户后	payload 含操作人和账户摘要，不含支付账户配置。
PaymentAccountUpdated	管理员编辑平台支付账户后	payload 含操作人、账户摘要和编辑后的摘要，不含支付账户配置。
PaymentAccountDeleteBefore	管理员删除平台支付账户前	payload 含操作人和账户摘要；可设置 blocked=true、message 拦截。
PaymentAccountDeleted	管理员删除平台支付账户后	payload 含操作人和账户摘要。
PaymentAccountToggled	管理员切换平台支付账户启用状态后	payload 含操作人、账户摘要和 previous_enabled。
PaymentAccountStrategyUpdated	管理员调整平台支付账户轮询策略后	payload 含操作人、支付接口 ID 和轮询策略。
支付驱动
Hook	触发位置	说明
PaymentDriverSaveBefore	管理员新建或编辑支付驱动文件前	payload 含操作人、动作、驱动摘要和编辑后的摘要；可设置 blocked=true、message 拦截。不会提供驱动 PHP 源码。
PaymentDriverCreated	管理员创建支付驱动文件后	payload 含操作人和驱动摘要，不含文件路径和源码。
PaymentDriverUpdated	管理员更新或重命名支付驱动文件后	payload 含操作人和更新后的驱动摘要，不含文件路径和源码。
PaymentDriverDeleteBefore	管理员删除支付驱动文件前	payload 含操作人和驱动摘要；可设置 blocked=true、message 拦截。
PaymentDriverDeleted	管理员删除支付驱动文件后	payload 含操作人和驱动摘要。
费率组
Hook	触发位置	说明
RateGroupSaveBefore	管理员新增或编辑费率组前	payload 含操作人、动作、费率组摘要和编辑后的摘要；可设置 blocked=true、message 拦截。仅提供各规则数量，不提供具体费率值。
RateGroupCreated	管理员新增费率组后	payload 含操作人和费率组规则数量摘要。
RateGroupUpdated	管理员编辑费率组后	payload 含操作人、更新后的费率组摘要和编辑前后的规则数量摘要。
RateGroupDeleteBefore	管理员删除费率组前	payload 含操作人和费率组摘要；可设置 blocked=true、message 拦截。
RateGroupDeleted	管理员删除费率组后	payload 含操作人和费率组摘要。
买家黑名单
Hook	触发位置	说明
BuyerBlacklistSaveBefore	管理员新增或编辑买家黑名单前	payload 含操作人、动作、黑名单摘要和编辑后的摘要；可设置 blocked=true、message 拦截。黑名单值仅提供 SHA-256 哈希和长度，不提供原文。
BuyerBlacklistCreated	管理员新增买家黑名单后	payload 含操作人和黑名单摘要，不提供黑名单原文或备注。
BuyerBlacklistUpdated	管理员编辑买家黑名单后	payload 含操作人、更新后的黑名单摘要和编辑前后的摘要。
BuyerBlacklistDeleteBefore	管理员删除买家黑名单前	payload 含操作人和黑名单摘要；可设置 blocked=true、message 拦截。
BuyerBlacklistDeleted	管理员删除买家黑名单后	payload 含操作人和黑名单摘要。
邮箱账户
Hook	触发位置	说明
EmailAccountSaveBefore	管理员新增或编辑邮箱发送账户前	payload 含操作人、动作、账户摘要和编辑后的摘要；可设置 blocked=true、message 拦截。SMTP 主机、用户名和密码不提供，仅使用 credentials_updated 标记。
EmailAccountCreated	管理员新增邮箱发送账户后	payload 含操作人和账户摘要，不含 SMTP 凭据。
EmailAccountUpdated	管理员编辑邮箱发送账户后	payload 含操作人、账户摘要和编辑后的摘要，不含 SMTP 凭据。
EmailAccountDeleteBefore	管理员删除邮箱发送账户前	payload 含操作人和账户摘要；可设置 blocked=true、message 拦截。
EmailAccountDeleted	管理员删除邮箱发送账户后	payload 含操作人和账户摘要。
EmailAccountToggled	管理员切换邮箱发送账户启用状态后	payload 含操作人、账户摘要和 previous_enabled。
EmailAccountStrategyUpdated	管理员更新邮箱账户轮询与自动停用策略后	payload 含操作人、策略和可选阈值。
EmailAccountTestBeforeSend	管理员发送邮箱账户测试邮件前	payload 含操作人、账户摘要、脱敏收件人；可设置 blocked=true、message 拦截。
EmailAccountTestSent	管理员测试邮件投递尝试结束后	payload 含操作人、账户摘要、脱敏收件人和 success，不含底层错误与 SMTP 凭据。
定时任务
Hook	触发位置	说明
SimpleCommandStart	使用 CrontabLogTrait 的命令开始时	可记录开始状态
SimpleCommandEnd	使用 CrontabLogTrait 的命令结束时	可读取输出、状态和耗时
SystemCrontabSaveBefore	管理员新增或编辑系统定时任务前	payload 含操作人、动作、任务摘要和编辑后的摘要；可设置 blocked=true、message 拦截。任务内容仅提供长度。
SystemCrontabCreated	管理员新增系统定时任务后	payload 含操作人和任务摘要，不提供任务内容。
SystemCrontabUpdated	管理员编辑系统定时任务后	payload 含操作人和更新后的任务摘要，不提供任务内容。
SystemCrontabRunBefore	管理员手动执行系统定时任务前	payload 含操作人和任务摘要；可设置 blocked=true、message 拦截。
SystemCrontabRan	管理员手动执行系统定时任务完成后	payload 含操作人和任务摘要，不提供执行输出。
SystemCrontabDeleteBefore	管理员删除系统定时任务前	payload 含操作人和任务摘要；可设置 blocked=true、message 拦截。
SystemCrontabDeleted	管理员删除系统定时任务后	payload 含操作人和任务摘要。
Hook 开发示例
修改店铺首页返回
<?php
namespace app\service\plugin\DemoPlugin;

use app\service\plugin\HookInterface;

class ShopHook implements HookInterface
{
    public function handle(&$payload = null)
    {
        if (!is_array($payload)) {
            return false;
        }
        $payload['data']['shop']['plugin_text'] = '由插件追加';
        return true;
    }
}
复制
config.php 中声明：

'hook' => [
    'ShopPublicIndexAfter' => 'ShopHook',
]
复制
商户仪表盘追加广告位
适合移动版用户中心分类卡片下方的多广告位场景。

<?php
namespace app\service\plugin\DemoPlugin;

use app\service\plugin\HookInterface;

class DashboardHook implements HookInterface
{
    public function handle(&$payload = null)
    {
        if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
            return false;
        }

        $payload['data']['ad_slots'][] = [
            'key' => 'dashboard_ads_main',
            'title' => '精选推荐',
            'items' => [
                [
                    'title' => '推广活动一',
                    'subtitle' => '这里可以放副标题',
                    'image' => plugstatic('DemoPlugin', 'images/banner-1.png'),
                    'path' => '/user/wallet/coupon',
                    'badge' => '限时'
                ],
                [
                    'title' => '推广活动二',
                    'subtitle' => '这里可以跳转插件页',
                    'image' => plugstatic('DemoPlugin', 'images/banner-2.png'),
                    'link' => '/plugin/DemoPlugin'
                ]
            ]
        ];

        return true;
    }
}
复制
config.php 中声明：

'hook' => [
    'ShopDashboardAfter' => 'DashboardHook',
]
复制
注册赠送运营余额
注册奖励会先构造普通余额、保证金和运营余额的默认清单，再触发 UserRegistrationRewardsBefore。插件可在此时追加新的奖励项目，统一由系统校验资产类型、金额和幂等流水后发放。

<?php
namespace app\service\plugin\DemoPlugin;

use app\service\plugin\HookInterface;

class RegisterRewardHook implements HookInterface
{
    public function handle(&$payload = null)
    {
        if (!is_array($payload) || (int)($payload['user_id'] ?? 0) <= 0) {
            return false;
        }

        $payload['rewards'][] = [
            'key' => 'demo_register_operating_balance',
            'asset_type' => 'operating_balance',
            'amount' => '5.00',
            'remark' => '注册赠送运营余额',
        ];
        return true;
    }
}
复制
config.php 中声明：

'hook' => [
    'UserRegistrationRewardsBefore' => 'RegisterRewardHook',
]
复制
可用资产类型仅为 balance、deposit、operating_balance。key 必须在单次注册奖励中唯一，系统会以“用户 ID + key”保证同一奖励不会重复发放。

指定邀请码分配费率组
邀请码验证完成、写入用户资料前会触发 InviteCodeBeforeBind。插件可为当前邀请码设置 rate_group_id，系统会验证费率组存在后写入用户；设置 blocked=true 与 message 可拒绝该次注册或绑定。

<?php
namespace app\service\plugin\DemoPlugin;

use app\service\plugin\HookInterface;

class InviteRateGroupHook implements HookInterface
{
    public function handle(&$payload = null)
    {
        if (!is_array($payload) || strtoupper((string)($payload['invite_code'] ?? '')) !== 'VIP2026') {
            return false;
        }

        $payload['rate_group_id'] = 3;
        return true;
    }
}
复制
config.php 中声明：

'hook' => [
    'InviteCodeBeforeBind' => 'InviteRateGroupHook',
]
复制
内置的 InviteCodeRateGroup 示例插件提供“指定邀请码列表”和“目标费率组 ID”两个配置项，不包含独立页面；邀请码支持每行一个，也支持逗号、分号或空格分隔，列表任一项匹配都会分配到该费率组。

订单完成后同步
<?php
namespace app\service\plugin\DemoPlugin;

use app\service\plugin\HookInterface;

class OrderHook implements HookInterface
{
    public function handle(&$payload = null)
    {
        $tradeNo = (string)($payload['trade_no'] ?? '');
        if ($tradeNo === '') {
            return false;
        }
        // 在这里同步外部系统
        return true;
    }
}
复制
config.php 中声明：

'hook' => [
    'OrderComplete' => 'OrderHook',
]
复制
建议 Hook 命名
未接入但后续可扩展：

MerchantSystemConfigAfter
ShopSystemConfigAfter
AdminJs
MerchantJs
ShopJs
GoodsJs
复制
已接入 Hook 以“已接入 Hook”章节为准。

安全规范
插件目录名必须唯一，且必须是合法类名。
插件主类必须实现 PluginInterface。
Hook 处理类必须实现 HookInterface。
不建议允许商户直接上传 JS 或 PHP 代码。
插件静态资源建议只允许图片、CSS、经过审核的 JS。
插件配置保存到 system_settings，并同步兼容 params.php，不要把密钥写死在主类中。
Hook 中如需修改业务数据，建议使用引用参数并保持返回值可追踪。
全局 Hook 会收到所有已接入事件；仅应安装和启用受信任的服务端插件，并避免在请求链路中执行耗时操作。
与现有插件机制的关系
当前系统已有一些专用插件目录：

app/service/shop_plugin
app/service/notification_plugin
app/service/docking_plugin
复制
app/service/plugin 是新的统一插件基础机制，用于承载更通用的插件开发规范。

后续可以逐步将专用插件目录接入统一的：

PluginManager::scan()
PluginManager::trigger()
plugconf()
plugstatic()




店铺插件开发文档
本文档描述店铺首页插件的目录结构、开发接口、动态加载与注册流程。

目录结构
每个插件一个文件，统一放在：

app/service/shop_plugin
复制
插件接口
所有插件需实现接口：

app\service\shop_plugin\ShopPluginInterface
复制
接口方法说明：

方法	说明
getCode	插件唯一标识
getName	插件名称
getDescription	插件描述
getType	插件类型
getEntry	插件入口地址
getDefaultConfig	默认配置
renderScript	输出前端脚本内容
自动注册机制
系统会扫描 app/service/shop_plugin 下的 PHP 文件并自动注册：

文件类名需与文件名一致
类命名空间为 app\service\shop_plugin
类需实现 ShopPluginInterface
注册逻辑位于：

app/service/ShopPluginProviderFactory.php
复制
插件目录输出
前端通过接口获取插件目录：

GET /api/user/shop/plugins
复制
返回结构示例：

{
  "code": 200,
  "msg": "success",
  "data": {
    "available": [
      {
        "code": "sakura",
        "name": "樱花飘落",
        "desc": "在店铺首页展示樱花飘落特效",
        "type": "effect",
        "entry": "/api/shop/effects/sakura",
        "config": { "count": 24 }
      }
    ],
    "enabled": []
  }
}
复制
插件配置保存
启用与配置保存到 home_plugins 字段：

[
  {
    "code": "sakura",
    "enabled": true,
    "config": { "count": 24 }
  }
]
复制
动态脚本入口
插件脚本统一由路由分发：

GET /api/shop/effects/{code}
复制
后端会调用插件的 renderScript 输出 JavaScript 内容。前端根据 entry 动态插入 <script> 并带上配置参数。

可覆盖样式类
店铺首页模板提供统一的样式类，方便插件直接覆盖：

shop-template-root：模板根节点
shop-page：页面容器
shop-container：主容器（部分模板）
shop-header：头部区域
shop-header-main：头部主行
shop-avatar：店铺头像
shop-name：店铺名称
shop-name-gradient：店铺名称渐变
shop-actions：头部操作区
shop-notice：店铺公告区域
shop-search：搜索区域
shop-categories：分类区域
shop-content：内容区域
shop-product-list：商品列表
shop-product-item：商品卡片
shop-surface：白色面板/卡片层（可覆盖为透明）
shop-product-name：商品名称
shop-product-price：商品价格
shop-product-original-price：商品原价
shop-product-action：商品操作按钮
shop-product-stock：商品库存/状态标签
不同模板不一定包含全部样式类，插件可按需覆盖。

开发步骤
在 app/service/shop_plugin 新增插件文件并实现接口
在 renderScript 中输出需要的 JS 脚本
如需配置项，在应用设置页增加对应表单控件
前端无需新增路由，插件会自动注册并出现在应用列表
最小插件示例
示例插件：在店铺首页加载一个简单的动效脚本。

<?php
namespace app\service\shop_plugin;

class GlowLinePlugin implements ShopPluginInterface
{
    public function getCode(): string
    {
        return 'glow_line';
    }

    public function getName(): string
    {
        return '流光线条';
    }

    public function getDescription(): string
    {
        return '在店铺首页添加轻量流光效果';
    }

    public function getType(): string
    {
        return 'effect';
    }

    public function getEntry(): string
    {
        return '/api/shop/effects/glow_line';
    }

    public function getDefaultConfig(): array
    {
        return [
            'speed' => 1.2,
            'count' => 8,
            'color' => '#60a5fa'
        ];
    }

    public function renderScript(array $config = []): string
    {
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return <<<JS
(function () {
  var config = $configJson || {};
  var root = document.querySelector('.shop-template-root') || document.body;
  var el = document.createElement('div');
  el.className = 'glow-line-plugin';
  el.dataset.config = JSON.stringify(config);
  root.appendChild(el);
})();
JS;
    }
}
复制
renderScript 示例（带配置读取）
public function renderScript(array $config = []): string
{
    $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return <<<JS
(function () {
  var config = $configJson || {};
  var count = Number(config.count || 24);
  var color = String(config.color || '#f472b6');
  window.__shopPluginConfig = window.__shopPluginConfig || {};
  window.__shopPluginConfig.sakura = { count: count, color: color };
})();
JS;
}
复制
配置结构示例
启用时保存到 home_plugins 字段：

[
  {
    "code": "glow_line",
    "enabled": true,
    "config": {
      "speed": 1.2,
      "count": 8,
      "color": "#60a5fa"
    }
  }
]
复制
调试建议
先确认插件出现在“应用列表”
确认 entry 可直接访问并返回 JS 内容
若脚本未生效，检查 renderScript 是否输出了有效 JS
如需样式，可在脚本中动态注入 <style> 或依赖页面已有样式类


支付方式插件开发指南
本文档介绍如何在熵云寄售系统中开发与接入支付方式驱动（Payment Driver），包含接口约定、目录结构、图标配置、后端与前端的调用路径、以及常见调试流程。

1. 总览
支付方式以“驱动”的形式动态加载，后端会扫描驱动目录并注册到支付提供商列表，然后由管理端配置并在前端渲染。

驱动目录：app/service/payment
驱动接口：PaymentDriverInterface.php
提供商工厂：PaymentProviderFactory.php
管理端创建驱动接口：PaymentConfigController.php
店铺支付方式输出（含图标）：Shop.php
用户中心充值使用的支付方式列表接口（含图标）：PaymentConfigController.php
2. 驱动创建方式
2.1 管理端创建（推荐）
在“系统设置 → 支付配置 → 开发者选项”中填写：

支付方式标识：key，如 qqpay
支付方式名称：label
支付方式描述：description（可选）
图标标识：icon（支持 iconify 或 class）
图标链接：icon_url（可选，优先级最高）
系统会生成驱动文件，路径示例：

app/service/payment/QqpayDriver.php
复制
创建时写入的 icon 与 icon_url 会自动写入驱动模板的 getIcon() / getIconUrl()。

相关前端创建入口与字段：

开发者弹窗表单：setting.vue
创建驱动 API 请求：payment-config.ts
2.2 手动创建
在驱动目录中新增 XxxDriver.php，并实现 PaymentDriverInterface。文件名与类名保持一致，例如 QqpayDriver.php / class QqpayDriver。

可参考已有驱动：

支付宝：AlipayDriver.php
微信：WxpayDriver.php
QQ支付示例：QqpayDriver.php
3. 驱动接口说明
驱动需实现以下方法（按接口顺序）：

interface PaymentDriverInterface
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getDescription(): string;
    public function getTypes(): array;
    public function getIcon(): string;
    public function getIconUrl(): string;
    public function pay($orderId, $amount, $title, array $config, string $type): array;
    public function verifyNotify(array $params, array $config, string $type): bool;
    public function refund($tradeNo, $amount, array $config, string $type): bool;
    public function queryOrder($orderId, array $config, string $provider): bool;
    public function handleNotify(array $config);
}
复制
3.1 基础信息
getKey()：支付方式标识（小写），必须唯一。例如 alipay、wxpay。
getLabel()：展示名称。
getDescription()：描述信息（可为空）。
3.2 类型与字段
getTypes() 返回可配置的支付类型列表。每个类型定义一组表单字段，用于后台配置页动态渲染。

示例结构：

public function getTypes(): array
{
    return [
        [
            'key' => 'epay',
            'label' => '易支付通道',
            'modes' => [0, 1],
            'default_mode' => 0,
            'fields' => [
                [
                    'key' => 'api_url',
                    'label' => '接口地址',
                    'type' => 'text',
                    'placeholder' => '例如：https://pay.example.com/',
                    'required' => true
                ],
                [
                    'key' => 'pid',
                    'label' => '商户PID',
                    'type' => 'text',
                    'placeholder' => '请输入商户PID',
                    'required' => true
                ]
            ],
            'default_config' => [
                'epay_type' => 'qqpay'
            ]
        ]
    ];
}
复制
字段说明（常用）：

key：字段键名，最终写入配置 config 中
label：字段展示名
type：text / textarea / radio / switch / select
required：是否必填
placeholder / tip：提示文本
options：当 type=radio/select 时可用
default_config：类型默认配置，打开创建或切换类型时会填充
3.3 图标配置
驱动支持两类图标：

getIcon()：图标标识（推荐 iconify 格式，如 ri:alipay-fill；也可返回传统 class 如 ri-alipay-fill）
getIconUrl()：图标链接（优先级最高）
前端渲染规则：

若 icon_url 存在，优先显示图片
否则使用 icon，若为 iconify 格式则 Icon 组件渲染
其它情况使用 class 渲染
使用到图标的页面包括：

店铺下单弹窗：OrderModal.vue
用户中心保证金/余额充值：balance/index.vue
3.4 配置字段完整示例
以下示例展示了多类型、多字段的组合写法：

public function getTypes(): array
{
    return [
        [
            'key' => 'scan',
            'label' => '扫码支付',
            'modes' => [0, 1],
            'default_mode' => 0,
            'fields' => [
                [
                    'key' => 'api_url',
                    'label' => '接口地址',
                    'type' => 'text',
                    'placeholder' => 'https://pay.example.com/',
                    'required' => true
                ],
                [
                    'key' => 'appid',
                    'label' => 'AppId',
                    'type' => 'text',
                    'placeholder' => '请输入AppId',
                    'required' => true
                ],
                [
                    'key' => 'mch_id',
                    'label' => '商户号',
                    'type' => 'text',
                    'placeholder' => '请输入商户号',
                    'required' => true
                ],
                [
                    'key' => 'secret_key',
                    'label' => '密钥',
                    'type' => 'textarea',
                    'placeholder' => '请输入密钥',
                    'required' => true
                ]
            ],
            'default_config' => [
                'timeout' => 600
            ]
        ],
        [
            'key' => 'h5',
            'label' => 'H5支付',
            'modes' => [0],
            'default_mode' => 0,
            'fields' => [
                [
                    'key' => 'return_url',
                    'label' => '回跳地址',
                    'type' => 'text',
                    'placeholder' => 'https://example.com/return',
                    'required' => false
                ]
            ],
            'default_config' => []
        ]
    ];
}
复制
4. 支付流程接口
驱动需要实现完整的支付流程：

4.1 发起支付
public function pay($orderId, $amount, $title, array $config, string $type): array
复制
返回结构示例：

跳转支付：
['pay_url' => 'https://...']
扫码支付：
['pay_url' => 'weixin://...'] 或二维码链接
更完整的返回示例：

return [
    'pay_url' => 'https://pay.example.com/checkout?order=202402190001',
    'order_no' => '202402190001',
    'expire_time' => 600
];
复制
4.2 回调校验
public function verifyNotify(array $params, array $config, string $type): bool
复制
校验签名，返回 true/false。

示例：

public function verifyNotify(array $params, array $config, string $type): bool
{
    $sign = $params['sign'] ?? '';
    $data = $params;
    unset($data['sign']);
    ksort($data);
    $query = urldecode(http_build_query($data));
    $expected = md5($query . $config['secret_key']);
    return $sign === $expected;
}
复制
4.3 退款与查单
public function refund($tradeNo, $amount, array $config, string $type): bool
public function queryOrder($orderId, array $config, string $provider): bool
复制
若支付通道不支持，建议抛出异常或返回 false。

4.4 异步通知处理
public function handleNotify(array $config)
复制
处理回调内容并完成业务逻辑（如更新订单状态）。

示例：

public function handleNotify(array $config)
{
    $params = request()->param();
    $verified = $this->verifyNotify($params, $config, $params['type'] ?? '');
    if (!$verified) {
        return 'fail';
    }
    return 'success';
}
复制
5. 管理端支付配置
管理端配置支付方式会写入 PaymentConfig 表。配置字段中的 config 会存储 getTypes() 定义的字段。

相关接口：

配置列表：/api/payment/config/index
配置保存：/api/payment/config/save
支付提供商列表：/api/payment/config/providers
提供商列表来自 PaymentProviderFactory::listProviders()，包含：

key, label, description, types, icon, icon_url
复制
6. 前端消费支付方式列表
6.1 店铺支付方式（下单页面）
接口：

GET /api/shop/{slug}/payment-configs
复制
返回字段（与图标有关）：

icon
icon_url
6.2 用户中心充值支付方式
接口：

GET /api/payment/config/enabled
复制
返回字段（与图标有关）：

icon
icon_url
7. 调试与常见问题
驱动未生效

检查类名与文件名是否一致
确认实现了 PaymentDriverInterface
确认 getKey() 与数据库中 provider 一致
图标不显示

若使用图片链接，请确认 icon_url 可访问
若使用 iconify，请确保格式为 前缀:名称
若使用 class，请确保前端已引入对应图标库
支付回调失败

查看服务端日志
校验签名是否正确
确认回调 URL 与 notify_url 配置一致
8. 接入流程清单
使用开发者入口创建驱动，或手动新建 XxxDriver.php
实现 getTypes() 定义配置字段
完成 pay/verifyNotify/handleNotify 逻辑
在管理端保存该驱动的配置
在前端下单或充值页面进行测试
9. 最小驱动示例
<?php
namespace app\service\payment;

class DemoPayDriver implements PaymentDriverInterface
{
    public function getKey(): string { return 'demopay'; }
    public function getLabel(): string { return '演示支付'; }
    public function getDescription(): string { return '演示用支付通道'; }
    public function getIcon(): string { return 'ri:bank-card-fill'; }
    public function getIconUrl(): string { return ''; }

    public function getTypes(): array
    {
        return [
            [
                'key' => 'demo',
                'label' => '演示通道',
                'modes' => [0],
                'default_mode' => 0,
                'fields' => [
                    [
                        'key' => 'api_url',
                        'label' => '接口地址',
                        'type' => 'text',
                        'placeholder' => 'https://pay.example.com/',
                        'required' => true
                    ]
                ],
                'default_config' => []
            ]
        ];
    }

    public function pay($orderId, $amount, $title, array $config, string $type): array
    {
        return ['pay_url' => 'https://pay.example.com/checkout?order=' . $orderId];
    }

    public function verifyNotify(array $params, array $config, string $type): bool
    {
        return true;
    }

    public function refund($tradeNo, $amount, array $config, string $type): bool
    {
        return false;
    }

    public function queryOrder($orderId, array $config, string $provider): bool
    {
        return false;
    }

    public function handleNotify(array $config)
    {
        return false;
    }
}



店铺示例插件
最后更新于：2026-07-18 04:58:30
樱花飘落特效
文件名SakuraPlugin.php
路径：/app/service/shop_plugin/SakuraPlugin.php
<?php
namespace app\service\shop_plugin;

class SakuraPlugin implements ShopPluginInterface
{
    public function getCode(): string
    {
        return 'sakura';
    }

    public function getName(): string
    {
        return '樱花飘落';
    }

    public function getDescription(): string
    {
        return '在店铺首页展示樱花飘落特效';
    }

    public function getType(): string
    {
        return 'effect';
    }

    public function getEntry(): string
    {
        return '/api/shop/effects/sakura';
    }

    public function getDefaultConfig(): array
    {
        return [
            'count' => 24
        ];
    }

    public function renderScript(array $config): string
    {
        $count = (int)($config['count'] ?? 24);
        if ($count < 8) {
            $count = 8;
        }
        if ($count > 80) {
            $count = 80;
        }

        return <<<JS
(function(){
  if (window.__shopSakuraEffectInitialized) return;
  window.__shopSakuraEffectInitialized = true;
  var canvas = document.getElementById('shop-sakura-canvas');
  if (!canvas) {
    canvas = document.createElement('canvas');
    canvas.id = 'shop-sakura-canvas';
    document.body.appendChild(canvas);
  }
  canvas.style.position = 'fixed';
  canvas.style.left = '0';
  canvas.style.top = '0';
  canvas.style.width = '100%';
  canvas.style.height = '100%';
  canvas.style.pointerEvents = 'none';
  canvas.style.zIndex = '9998';
  var ctx = canvas.getContext('2d');
  var width = 0;
  var height = 0;
  var resize = function() {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
  };
  resize();
  window.addEventListener('resize', resize);
  var count = {$count};
  var petals = [];
  var rand = function(min, max) { return Math.random() * (max - min) + min; };
  for (var i = 0; i < count; i++) {
    petals.push({
      x: rand(0, width),
      y: rand(0, height),
      r: rand(6, 14),
      s: rand(0.5, 1.6),
      d: rand(0.2, 1.2),
      o: rand(0.5, 0.9),
      a: rand(0, Math.PI * 2),
      w: rand(0.002, 0.01)
    });
  }
  var step = function() {
    ctx.clearRect(0, 0, width, height);
    for (var i = 0; i < petals.length; i++) {
      var p = petals[i];
      p.y += p.s;
      p.x += Math.sin(p.a) * p.d;
      p.a += p.w;
      if (p.y - p.r > height) {
        p.y = -p.r;
        p.x = rand(0, width);
      }
      ctx.save();
      ctx.translate(p.x, p.y);
      ctx.rotate(p.a);
      ctx.beginPath();
      ctx.ellipse(0, 0, p.r * 0.6, p.r, 0, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(255, 183, 197,' + p.o + ')';
      ctx.fill();
      ctx.restore();
    }
    window.__shopSakuraEffectFrame = requestAnimationFrame(step);
  };
  step();
  window.__shopPluginCleanup_sakura = function() {
    window.removeEventListener('resize', resize);
    if (window.__shopSakuraEffectFrame) {
      cancelAnimationFrame(window.__shopSakuraEffectFrame);
      window.__shopSakuraEffectFrame = null;
    }
    if (canvas && canvas.parentNode) {
      canvas.parentNode.removeChild(canvas);
    }
    window.__shopSakuraEffectInitialized = false;
  };
})();
JS;
    }
}

复制
音乐播放器
文件名MusicPlayerPlugin.php
路径：/app/service/shop_plugin/MusicPlayerPlugin.php
<?php
namespace app\service\shop_plugin;

class MusicPlayerPlugin implements ShopPluginInterface
{
    public function getCode(): string
    {
        return 'music_player';
    }

    public function getName(): string
    {
        return '音乐播放器';
    }

    public function getDescription(): string
    {
        return '在店铺首页展示音乐播放器';
    }

    public function getType(): string
    {
        return 'widget';
    }

    public function getEntry(): string
    {
        return '/api/shop/effects/music_player';
    }

    public function getDefaultConfig(): array
    {
        return [];
    }

    public function renderScript(array $config): string
    {
        $scriptUrl = 'https://player.xfyun.club/js/xf-MusicPlayer/js/xf-MusicPlayer.min.js';
        $cdnName = 'https://player.xfyun.club/js';
        return <<<JS
(function(){
  if (window.__shopMusicPlayerInitialized) return;
  if (window.__shopMusicPlayerLoading) return;
  window.__shopMusicPlayerLoading = true;
  var styleId = 'shop-music-player-style';
  var styleEl = document.getElementById(styleId);
  if (!styleEl) {
    styleEl = document.createElement('style');
    styleEl.id = styleId;
    styleEl.textContent = "html,body{height:100%!important;overflow:visible!important;}#xf-MusicPlayer{position:fixed!important;right:16px!important;bottom:16px!important;z-index:9999!important;}";
    document.head.appendChild(styleEl);
  }
  var htmlEl = document.documentElement;
  var bodyEl = document.body;
  var prev = {
    htmlOverflow: htmlEl ? htmlEl.style.overflow : '',
    bodyOverflow: bodyEl ? bodyEl.style.overflow : '',
    bodyMinHeight: bodyEl ? bodyEl.style.minHeight : '',
    bodyPosition: bodyEl ? bodyEl.style.position : ''
  };
  if (htmlEl) {
    htmlEl.style.overflow = 'visible';
  }
  if (bodyEl) {
    bodyEl.style.overflow = 'visible';
    bodyEl.style.minHeight = '100%';
    if (!bodyEl.style.position) {
      bodyEl.style.position = 'relative';
    }
  }
  var scriptId = 'shop-music-player-script';
  var insertPlayer = function() {
    var existing = document.getElementById('xf-MusicPlayer');
    if (!existing) {
      if (document.body) {
        document.body.insertAdjacentHTML('beforeend', '<div id="xf-MusicPlayer" data-cdnName="{$cdnName}" data-musicApi="api.xfyun.club"></div>');
      } else {
        var tempContainer = document.createElement('div');
        tempContainer.id = 'xf-MusicPlayer';
        tempContainer.setAttribute('data-cdnName', '{$cdnName}');
        tempContainer.setAttribute('data-musicApi', 'api.xfyun.club');
        document.documentElement.appendChild(tempContainer);
      }
    }
    var containerEl = document.getElementById('xf-MusicPlayer');
    var oldScript = document.getElementById(scriptId);
    if (oldScript && oldScript.parentNode) {
      oldScript.parentNode.removeChild(oldScript);
    }
    var script = document.createElement('script');
    script.id = scriptId;
    script.src = '{$scriptUrl}';
    script.async = false;
    script.onload = function() {
      window.__shopMusicPlayerInitialized = true;
      window.__shopMusicPlayerLoading = false;
      if (document.readyState !== 'loading') {
        var hasPlayer = document.querySelector('#xf-MusicPlayer .xf-MusicPlayer-Main');
        if (!hasPlayer) {
          window.dispatchEvent(new Event('DOMContentLoaded'));
        }
      }
    };
    script.onerror = function() {
      window.__shopMusicPlayerInitialized = false;
      window.__shopMusicPlayerLoading = false;
      var scriptEl = document.getElementById(scriptId);
      if (scriptEl && scriptEl.parentNode) {
        scriptEl.parentNode.removeChild(scriptEl);
      }
    };
    if (containerEl && containerEl.parentNode) {
      containerEl.insertAdjacentElement('afterend', script);
    } else if (document.body) {
      document.body.appendChild(script);
    } else {
      document.documentElement.appendChild(script);
    }
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', insertPlayer, { once: true });
  } else {
    insertPlayer();
  }
  window.__shopPluginCleanup_music_player = function() {
    var scriptEl = document.getElementById(scriptId);
    if (scriptEl && scriptEl.parentNode) {
      scriptEl.parentNode.removeChild(scriptEl);
    }
    var styleNode = document.getElementById(styleId);
    if (styleNode && styleNode.parentNode) {
      styleNode.parentNode.removeChild(styleNode);
    }
    var containerEl = document.getElementById('xf-MusicPlayer');
    if (containerEl && containerEl.parentNode) {
      containerEl.parentNode.removeChild(containerEl);
    }
    if (htmlEl) {
      htmlEl.style.overflow = prev.htmlOverflow;
    }
    if (bodyEl) {
      bodyEl.style.overflow = prev.bodyOverflow;
      bodyEl.style.minHeight = prev.bodyMinHeight;
      bodyEl.style.position = prev.bodyPosition;
    }
    window.__shopMusicPlayerInitialized = false;
    window.__shopMusicPlayerLoading = false;
  };
})();
JS;
    }
}




短信插件开发文档
本文档说明如何在 熵云后端新增/维护"短信插件"（SMS Plugin），用于统一处理：

短信发送（验证码、通知等）
配置项动态渲染
多服务商切换
1. 目录与核心文件
插件目录：/backend/app/service/sms
接口定义：/backend/app/service/sms/SmsDriverInterface.php
基类（可选继承）：/backend/app/service/sms/BaseSmsDriver.php
插件工厂：/backend/app/service/SmsProviderFactory.php
服务入口：/backend/app/service/SmsService.php
当前内置插件
驱动类	getKey()	说明
AliyunDriver	aliyun	阿里云短信（标准短信服务）
AliyunAuthDriver	aliyun_auth	阿里云号码认证短信
TencentDriver	tencent	腾讯云短信
SmsBaoDriver	smsbao	短信宝
2. 插件加载机制
系统通过 SmsProviderFactory::drivers() 自动扫描 service/sms/*.php，并实例化实现了 SmsDriverInterface 的类。

关键点：

文件放在 service/sms 目录下
类命名空间为 app\service\sms
类必须实现 SmsDriverInterface（通常继承 BaseSmsDriver 获取 HTTP 工具方法）
getKey() 返回值必须唯一（工厂按 key 建索引）
工厂会检查 system_plugin_visibility 系统设置中的 sms 分组，过滤不可见的插件
3. 接口说明（必须实现）
SmsDriverInterface 定义了 5 个方法：

getKey(): string 插件唯一标识（如 aliyun、tencent），对应 system_settings 中 sms_provider 的值。

getLabel(): string 插件显示名称（后台下拉展示）。

getDescription(): string 插件描述信息，支持 HTML，用于后台展示费用参考与产品地址。

getFields(): array 配置项定义（用于前端动态渲染配置表单）。

send(string $phoneNumber, array $params, array $config): bool 发送短信。成功返回 true，失败抛出 Exception。

4. 基类 BaseSmsDriver
BaseSmsDriver 是一个抽象类，实现了 SmsDriverInterface 并提供以下工具方法，可供子类直接使用：

httpRequest(string $url, $data = null, array $headers = [], string $method = 'GET'): string 通用 HTTP 请求方法，优先使用 cURL，备选 shell_exec 和 file_get_contents。内置 5s 连接超时 + 10s 读取超时。

percentEncode(string $str): string URL 编码（兼容阿里云签名规范的百分号编码）。

sign(string $key, string $msg): string HMAC-SHA256 签名。

建议所有插件继承 BaseSmsDriver，以复用 HTTP 和签名工具方法。

5. 配置字段约定（getFields）
字段结构示例：

[
    'key'         => 'sms_aliyun_access_key_id',   // 对应 system_settings 表的 key
    'label'       => 'AccessKeyId',                 // 前端表单标签
    'type'        => 'text',                        // 控件类型
    'placeholder' => '请输入 AccessKeyId'            // 输入框提示
]
复制
常用 type：

type	说明
text	普通文本输入框
password	密码输入框（隐藏输入内容）
info	纯提示信息（不渲染输入控件），配合 tip 字段展示说明文字
注意：字段 key 会作为 system_settings 表的键名存储，建议加 sms_ 前缀避免冲突。

6. 最小插件骨架
以下是一个完整的短信插件最小实现：

<?php
namespace app\service\sms;

use think\Exception;

class DemoDriver extends BaseSmsDriver
{
    public function getKey(): string
    {
        return 'demo';
    }

    public function getLabel(): string
    {
        return '演示短信';
    }

    public function getDescription(): string
    {
        return '<div class="space-y-1">'
            . '<p><span class="font-medium">费用参考：</span>按量计费</p>'
            . '<p><span class="font-medium">产品地址：</span>'
            . '<a href="https://example.com" target="_blank" class="underline">https://example.com</a></p>'
            . '<p>演示短信通道。</p></div>';
    }

    public function getFields(): array
    {
        return [
            [
                'key'         => 'sms_demo_api_key',
                'label'       => 'API Key',
                'type'        => 'text',
                'placeholder' => '请输入 API Key'
            ],
            [
                'key'         => 'sms_demo_api_secret',
                'label'       => 'API Secret',
                'type'        => 'password',
                'placeholder' => '请输入 API Secret'
            ],
        ];
    }

    public function send(string $phoneNumber, array $params, array $config): bool
    {
        $apiKey = trim($config['sms_demo_api_key'] ?? '');
        $apiSecret = trim($config['sms_demo_api_secret'] ?? '');

        if (empty($apiKey) || empty($apiSecret)) {
            throw new Exception("演示短信配置不完整");
        }

        // 构建请求参数
        $content = $params['content'] ?? ("验证码为 " . ($params['code'] ?? '123456'));
        $payload = json_encode([
            'phone'   => $phoneNumber,
            'content' => $content,
            'key'     => $apiKey,
            'secret'  => $apiSecret,
        ]);

        // 使用基类的 httpRequest 方法发送请求
        $result = $this->httpRequest(
            'https://api.example.com/sms/send',
            $payload,
            ['Content-Type: application/json'],
            'POST'
        );

        $data = json_decode($result, true);
        if (isset($data['code']) && $data['code'] === 0) {
            return true;
        }

        throw new Exception("短信发送失败: " . ($data['message'] ?? $result));
    }
}
复制
7. 调用链路
7.1 直接发送短信
Controller / Command
  └─ SmsService::send($phone, $params, $config)
       └─ SmsProviderFactory::getDriver($provider)  // 根据 sms_provider 配置选取驱动
            └─ Driver::send($phone, $params, $config)
复制
$params 常用字段：
code — 验证码（如 123456）
min — 有效期分钟数
content — 自定义短信内容（短信宝使用）
template_code — 短信模板 Code（阿里云系列使用）
template_id — 短信模板 ID（腾讯云使用）
其余键值对作为模板变量传递
$config 为 null 时从 SettingService 读取全部短信配置；可手动传入覆盖（用于测试发送）。
7.2 通过通知系统发送
NotificationService
  └─ SmsNotificationPlugin::send($message, $recipient, $config)
       └─ SmsService::send($phone, $smsParams, $config)
复制
SmsNotificationPlugin 会将通知模板中的 template_code / template_id 注入到 $params 中传递给底层驱动。

8. 与后台接口联动
8.1 获取短信服务商列表
接口：GET /api/system/sms_providers
返回：SmsProviderFactory::listProviders()，经 filterVisibleProviders('sms', ...) 过滤
返回结构：

[
  {
    "key": "aliyun",
    "label": "阿里云短信",
    "description": "...",
    "fields": [...]
  }
]
复制
8.2 测试发送短信
接口：POST /api/system/send_test_sms
参数：phone（手机号）、config（可选，覆盖配置）
内部调用 SmsService::send() 并捕获异常返回结果。
8.3 插件可见性控制
通过 system_plugin_visibility 系统设置中的 sms 分组控制：

{
  "sms": {
    "aliyun": 1,
    "tencent": 1,
    "smsbao": 0
  }
}
复制
值为 0 或未列出的插件会被隐藏。后台"系统设置 - 插件管理"中可配置可见性。

9. 开发建议
getKey() 要稳定：上线后不要修改，因为 system_settings 中 sms_provider 存储的是此值。
配置字段 key 加前缀：建议以 sms_{driver_name}_ 开头，避免与其他设置键冲突。
异常要明确：send() 失败时抛出 Exception，消息应包含上游错误码和描述，方便排查。
超时兜底：BaseSmsDriver::httpRequest() 已内置 5s+10s 超时，如需调整可覆写。
模板参数处理：在 $params 中 template_code、template_id、content 为系统保留键，发送前应过滤掉再作为模板变量传递。
频率限制：如需防止短信轰炸，可在驱动内通过 Cache::set/get 实现发送间隔控制。
日志记录：建议在关键错误分支记录日志（\think\facade\Log::error()），便于排查线上问题。
10. 验收清单
新增短信插件后请至少验证：

后台"系统设置 - 短信设置"页面可看到新插件
选择新插件后配置表单正确展示
保存配置后配置项持久化正常
点击"测试发送"可正常收到短信
注册/登录验证码流程可正常发送
通知系统（订单通知、投诉通知等）可正常通过新插件发送
上游异常时返回可读错误信息，不出现静默失败
11. 常见问题
Q1：新增插件后后台列表没出现？
检查文件是否在 service/sms 目录下
检查命名空间是否为 app\service\sms
检查是否实现了 SmsDriverInterface
检查 getKey() 是否与已有插件冲突
检查 system_plugin_visibility 设置是否将该插件设为了隐藏
Q2：插件加载了但发送时报"未配置短信服务商"？
检查 system_settings 表中 sms_provider 的值是否与插件的 getKey() 一致
检查后台是否已选择并保存了该短信服务商
Q3：如何实现纯内容短信（非模板）？
短信宝（SmsBaoDriver）是纯内容短信的代表实现，直接拼接 content 发送
阿里云/腾讯云使用模板模式，通过 template_code / template_id + 模板变量发送
在 send() 中检查 $params['content'] 即可区分两种模式
Q4：如何支持通知系统的场景化模板？
通知系统会通过 NotificationSceneSettingService 为不同场景（如 auth.code.user、order.paid.user）分配独立的 template_code / template_id。驱动在 send() 中应优先使用 $params['template_code'] / $params['template_id']，无值时回退到全局配置。



网站首页模板开发文档
本文档说明如何开发网站首页动态模板。网站首页模板由后端目录驱动，主要用于 / 首页直出渲染，不等同于 frontend/home 的 SPA 入口。

1. 目录结构
首页模板源码放在：

app/home/{code}
复制
首页模板静态资源放在：

public/templates/home/{code}
复制
推荐结构：

app/home/my_home/
├── config.php
├── route.php
├── controller/
│   └── Index.php
└── view/
    └── index/
        └── index.html

public/templates/home/my_home/
└── assets/
    ├── css/
    ├── js/
    └── images/
复制
最小结构：

app/home/my_home/
├── config.php
├── route.php
├── controller/
│   └── Index.php
└── view/
    └── index/
        └── index.html
复制
2. 命名规范
{code} 必须与 config.php 中的 code 一致。
{code} 只建议使用小写字母、数字、下划线、短横线。
内置首页模板示例：
default
bing
black_gold
enterprise
spring_festival
3. config.php
config.php 必须返回数组。

示例：

<?php

return [
    'code' => 'my_home',
    'name' => '我的首页模板',
    'description' => '自定义网站首页模板',
    'version' => '1.0.0',
    'author' => 'Entropy',
    'module' => 'home',
    'platform' => 'responsive',
    'params' => [],
];
复制
字段说明：

字段	必填	说明
code	是	模板编码，必须与目录名一致
name	是	模板显示名称
description	否	模板描述
version	否	模板版本
author	否	作者
module	是	首页模板固定为 home
platform	否	responsive、desktop、mobile 等
params	否	模板参数，可在控制器或视图中使用
4. route.php
首页模板可以注册自己的首页路由。

示例：

<?php

use app\service\HomeTemplateService;
use think\facade\Route;

Route::rule('', function () {
    return HomeTemplateService::dispatchController('my_home');
});

Route::rule('/', function () {
    return HomeTemplateService::dispatchController('my_home');
});
复制
注意：

首页模板路由会由 HomeTemplateService::registerRoutes() 加载。
不要注册 /install、/user、/api、/order/search、/articles 等系统保留路径。
自定义路由尽量只处理首页相关路径，避免抢占后台、用户中心、公开 SPA 页面。
5. controller/Index.php
最小控制器示例：

<?php

namespace app\home\my_home\controller;

use app\BaseController;
use app\service\HomeTemplateService;

class Index extends BaseController
{
    public function index()
    {
        return view(app_path() . 'home/my_home/view/index/index.html', [
            'templateCode' => 'my_home',
            'params' => HomeTemplateService::getConfig('my_home'),
        ]);
    }
}
复制
6. view/index/index.html
首页模板是 ThinkPHP 视图文件，可以直接写 HTML、CSS、JS。

示例：

<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$params.name|default='网站首页'}</title>
    <link rel="stylesheet" href="/templates/home/my_home/assets/css/index.css">
</head>
<body>
    <main class="home-page">
        <h1>{$params.name|default='网站首页'}</h1>
        <p>{$params.description|default=''}</p>
    </main>
    <script src="/templates/home/my_home/assets/js/index.js"></script>
</body>
</html>
复制
7. 静态资源
静态资源建议放在：

public/templates/home/{code}/assets
复制
页面中使用站内绝对路径引用：

<link rel="stylesheet" href="/templates/home/my_home/assets/css/index.css">
<script src="/templates/home/my_home/assets/js/index.js"></script>
<img src="/templates/home/my_home/assets/images/banner.png" alt="">
复制
不要使用：

../
..//
http://templates/...
//templates/...
复制
8. 启用模板
当前首页模板由系统设置决定，服务会读取以下字段：

theme_home
home_template
home_template_code
site_home_template
保存值支持：

my_home
ext_my_home
复制
后端会自动去掉 ext_ 前缀并检查模板目录是否存在。

如果配置的模板不存在，会回退到：

default
复制
9. 后端服务
核心服务：

app/service/HomeTemplateService.php
主要能力：

扫描 app/home/*
读取 config.php
注册当前首页模板路由
分发到模板控制器
读取模板目录与配置
为主题设置提供模板目录信息
10. 新增模板流程
复制一个现有模板目录，例如 app/home/default。
改名为新模板编码，例如 my_home。
修改 config.php 中的 code、name、description。
修改 route.php 中的模板编码。
修改 controller/Index.php 的命名空间和视图路径。
编写 view/index/index.html。
如有静态资源，放到 public/templates/home/my_home/assets。
在系统设置中选择或保存该首页模板。
执行清缓存：
php think clear
复制
访问 / 验证。
11. 注意事项
首页模板是后端直出模板，不需要重新构建 frontend/home。
frontend/home 是公开 SPA 入口，用于 /articles、/order/search、登录注册、支付回调等页面。
首页模板不要抢占系统公开路径，否则会导致 /install、/articles、/order/search 等页面异常。
如果修改的是 frontend/home，需要重新执行前端构建；如果只修改 app/home/{code}，通常清缓存即可。
12. 参考模板
app/home/default
app/home/bing
app/home/black_gold
app/home/enterprise
app/home/spring_festival


店铺首页模板开发文档
本文档说明如何开发店铺首页动态模板。当前店铺首页模板由后端目录驱动，访问店铺链接时后端直接渲染对应模板，不依赖 frontend/shop 的 SPA 页面入口。

1. 目录结构
店铺首页模板源码放在：

app/shop_home/{code}
复制
店铺首页模板静态资源放在：

public/templates/shop_home/{code}
复制
推荐结构：

app/shop_home/my_shop/
├── config.php
├── controller/
│   └── Index.php
└── view/
    └── index/
        └── index.html

public/templates/shop_home/my_shop/
└── assets/
    ├── css/
    ├── js/
    ├── fonts/
    └── images/
复制
最小结构：

app/shop_home/my_shop/
├── config.php
├── controller/
│   └── Index.php
└── view/
    └── index/
        └── index.html
复制
2. 命名规范
{code} 必须与 config.php 中的 code 一致。
{code} 只建议使用小写字母、数字、下划线、短横线。
店铺保存模板值时使用 ext_{code}，后端会自动解析为 {code}。
内置店铺模板示例：
default
simple
card
black_gold
anime
summer
winter
spring_festival
mobile_blue
mobile_flat
3. config.php
config.php 必须返回数组。

示例：

<?php

return [
    'code' => 'my_shop',
    'name' => '我的店铺模板',
    'description' => '自定义店铺首页模板',
    'version' => '1.0.0',
    'author' => 'Entropy',
    'module' => 'shop_home',
    'platform' => 'responsive',
    'devices' => ['desktop', 'mobile'],
    'variables' => [
        'show_category_all' => [
            'name' => '分类全部按钮',
            'description' => '是否显示分类中的“全部”按钮',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_user_customizable' => true,
            'default_admin_value' => true,
        ],
    ],
    'params' => [],
];
复制
字段说明：

字段	必填	说明
code	是	模板编码，必须与目录名一致
name	是	模板显示名称
description	否	模板描述
version	否	模板版本
author	否	作者
module	是	店铺首页模板固定为 shop_home
platform	否	responsive、desktop、mobile 等
devices	否	支持设备，desktop、mobile
variables	否	模板变量定义，用于后台和用户侧配置
params	否	模板参数
3.1 devices
devices 用于限制模板支持的设备类型：

'devices' => ['desktop'],
'devices' => ['mobile'],
'devices' => ['desktop', 'mobile'],
复制
说明：

desktop：PC 端模板。
mobile：移动端模板。
不配置时默认视为同时支持。
3.2 variables
variables 用于声明模板变量，后台可以控制默认值和用户是否可自定义。

简单写法：

'variables' => [
    'shopName' => '店铺名称',
    'shopAvatar' => '店铺头像',
]
复制
详细写法：

'variables' => [
    'show_category_all' => [
        'name' => '分类全部按钮',
        'description' => '是否显示分类中的“全部”按钮',
        'type' => 'boolean',
        'default_enabled' => true,
        'default_user_customizable' => true,
        'default_admin_value' => true,
    ],
]
复制
4. controller/Index.php
店铺模板控制器推荐直接调用 DynamicShopTemplateService::renderBuiltTemplate()。

示例：

<?php

namespace app\shop_home\my_shop\controller;

use app\BaseController;
use app\service\DynamicShopTemplateService;

class Index extends BaseController
{
    public function index(string $slug = '', array $shop = [])
    {
        return DynamicShopTemplateService::renderBuiltTemplate('my_shop', $slug, $shop);
    }
}
复制
5. view/index/index.html
模板视图是 ThinkPHP HTML 文件，可以使用后端预处理好的变量。

常用变量由 DynamicShopTemplateService 注入，建议优先使用这些变量，而不是在模板中直接查数据库。

最小示例：

<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$shop.name|default='店铺首页'}</title>
    <link rel="stylesheet" href="/templates/shop_home/my_shop/assets/css/index.css">
</head>
<body>
    <main class="shop-template-root shop-page">
        <header class="shop-header">
            <div class="shop-avatar">
                <img src="{$shop.avatar|default=''}" alt="店铺头像">
            </div>
            <div class="shop-header-info">
                <h1 class="shop-name">{$shop.name|default='店铺'}</h1>
                <div class="shop-notice">{$shop.notice|raw|default=''}</div>
            </div>
        </header>

        <section class="shop-categories">
            {$categoriesHtml|raw|default=''}
        </section>

        <section class="shop-product-list">
            {$productsHtml|raw|default=''}
        </section>

        <button type="button" data-action="load-more">加载更多</button>
    </main>
    <script src="/templates/shop_home/my_shop/assets/js/index.js"></script>
</body>
</html>
复制
6. 后端渲染数据
DynamicShopTemplateService 会负责：

根据店铺链接或短链找到店铺。
记录店铺访客。
读取店铺模板配置。
加载商品列表。
加载分类列表。
生成商品、分类、按钮等 HTML 片段。
渲染 app/shop_home/{code}/view/index/index.html。
核心服务：

app/service/DynamicShopTemplateService.php
复制
7. 静态资源
静态资源建议放在：

public/templates/shop_home/{code}/assets
复制
页面中使用站内绝对路径引用：

<link rel="stylesheet" href="/templates/shop_home/my_shop/assets/css/index.css">
<script src="/templates/shop_home/my_shop/assets/js/index.js"></script>
<img src="/templates/shop_home/my_shop/assets/images/banner.png" alt="">
复制
不要使用：

../
..//
http://templates/...
//templates/...
复制
8. 推荐保留的 CSS 类
为了兼容店铺插件、头像框、铭牌、客服按钮和后续扩展，建议保留以下类名：

shop-template-root
shop-page
shop-header
shop-header-left
shop-header-info
shop-actions
shop-avatar
shop-avatar-frame-host
shop-name
shop-name-extra
shop-status
shop-notice
shop-notice-content
shop-stats-bar
shop-search
shop-categories
shop-category-list
shop-category-item
shop-content
shop-product-list
shop-product-item
shop-product-cover
shop-product-image
shop-product-name
shop-product-price
shop-product-stock
推荐写法：

<div class="shop-product-item my-shop-product-item" data-product-id="{$product.id}">
    ...
</div>
复制
即“标准类 + 模板私有类”同时保留。

9. 标准交互属性
模板脚本或系统交互可通过以下属性识别行为：

属性	说明
data-action="search-input"	搜索输入框
data-action="search"	触发搜索
data-action="load-more"	加载更多商品
data-action="share"	分享店铺
data-action="report"	举报店铺
data-action="open-link"	打开外链，配合 data-href
data-action="copy-wechat"	复制微信号
data-action="call-phone"	拨打电话
data-action="login"	打开登录或用户面板
data-action="toggle-drawer"	打开侧边栏
data-action="close-drawer"	关闭侧边栏
data-action="order-search"	跳转订单查询
data-product-id="{id}"	商品点击，下单或详情弹窗
data-product-uuid="{uuid}"	商品 UUID，推荐保留
data-category-id="{id}"	分类筛选
10. 移动端注意事项
移动端模板需要额外注意：

底部购买按钮不要被客服悬浮按钮遮挡。
弹窗打开时应隐藏或降低悬浮按钮层级。
分类点击建议在当前页筛选，不建议跳新页面。
商品点击应触发下单确认弹窗或商品弹窗。
登录按钮建议使用系统登录弹窗或用户面板逻辑。
不要强制禁止缩放，例如不要使用 user-scalable=no。
注意安全区：env(safe-area-inset-bottom)。
11. 新增模板流程
复制现有模板，例如 app/shop_home/default。
改名为新模板编码，例如 my_shop。
修改 config.php 中的 code、name、devices。
修改 controller/Index.php 的命名空间和 renderBuiltTemplate() 参数。
编写 view/index/index.html。
如有静态资源，放到 public/templates/shop_home/my_shop/assets。
在用户店铺设置中选择新模板并保存。
执行清缓存：
php think clear
复制
访问店铺首页验证。
12. 构建型模板
如果模板来自 frontend/shop 构建产物，需要同时关注：

frontend/shop
app/shop_home/{code}
public/templates/shop_home/{code}
复制
一般流程：

cd frontend/shop
pnpm run build:shop-templates
复制
构建后会更新：

app/shop_home/{code}
public/templates/shop_home/{code}
复制
如果只修改 app/shop_home/{code}/view/index/index.html 或静态 CSS/JS，通常不需要重新构建前端，只需要清缓存。

13. 启用和回退
店铺模板字段支持保存：

my_shop
ext_my_shop
复制
后端会自动解析 ext_ 前缀。

如果店铺保存的模板不存在，后端会回退到可用默认模板。

14. 参考模板
源码模板参考：

app/shop_home/default
app/shop_home/simple
app/shop_home/card
app/shop_home/mobile_blue
app/shop_home/mobile_flat
静态资源参考：

public/templates/shop_home/default
public/templates/shop_home/minimal
public/templates/shop_home/mobile_blue
public/templates/shop_home/mobile_flat


对接插件开发文档
本文档说明如何在 Entropy 后端新增/维护“商品对接插件”（Docking Plugin），用于统一处理：

对接商品识别
库存获取
上游成本/价格同步
下单发货（拉取卡密）
1. 目录与核心文件
插件目录：app/service/docking_plugin
接口定义：app/service/docking_plugin/DockingPluginInterface.php
插件工厂：app/service/DockingPluginProviderFactory.php
当前内置插件
CrossSiteDockingPlugin（跨站对接，type=1）
说明：同系统对接已并入跨站对接，系统仅保留跨站对接插件。

2. 插件加载机制
系统会自动扫描 service/docking_plugin/*.php，并实例化实现了 DockingPluginInterface 的类。

关键点：

文件在 docking_plugin 目录下
类命名空间为 app\service\docking_plugin
类必须实现 DockingPluginInterface
getType() 必须唯一（工厂内部按 type 建索引）
3. 接口说明（必须实现）
DockingPluginInterface 当前方法：

getCode(): string
插件编码（建议全局唯一，如 cross_site）

getName(): string
插件显示名称（后台下拉与列表展示）

getType(): int
插件类型编号（数据库 docking_type 对应值）

requiresRemoteConfig(): bool
是否要求远程配置（影响商品保存时配置校验逻辑）

getConfigFields(): array
配置项定义（用于前端动态渲染和后端必填校验）

supports($product): bool
判定当前商品是否由该插件处理

getCardProductId($product): int
返回卡池商品 ID（本地卡池/映射商品的统一入口）

getStock($product): int
返回商品可用库存

getUpstreamInfo($product): array
返回上游信息，至少建议包含：

cost_price
stock
fetchCardsForOrder($product, $order): array
支付/补单时发货入口，返回卡密数组（字符串数组）

4. 最小插件骨架
<?php
namespace app\service\docking_plugin;

class DemoDockingPlugin implements DockingPluginInterface
{
    public function getCode(): string { return 'demo'; }
    public function getName(): string { return '演示插件'; }
    public function getType(): int { return 99; }

    public function requiresRemoteConfig(): bool { return true; }

    public function getConfigFields(): array
    {
        return [
            ['key' => 'docking_link', 'label' => '对接地址', 'type' => 'text', 'required' => 1],
            ['key' => 'docking_api_key', 'label' => 'API密钥', 'type' => 'password', 'required' => 1],
        ];
    }

    public function supports($product): bool
    {
        return (int)($product->is_docking ?? 0) === 1
            && (int)($product->docking_type ?? 0) === $this->getType();
    }

    public function getCardProductId($product): int
    {
        return (int)($product->source_product_id ?? $product->id ?? 0);
    }

    public function getStock($product): int
    {
        return 0;
    }

    public function getUpstreamInfo($product): array
    {
        return ['cost_price' => 0, 'stock' => 0];
    }

    public function fetchCardsForOrder($product, $order): array
    {
        return [];
    }
}
复制
5. 配置字段约定（getConfigFields）
字段结构示例：

[
  'key' => 'docking_api_key',
  'label' => 'API密钥',
  'type' => 'password',
  'placeholder' => '请输入 API 密钥',
  'required' => 1,
]
复制
常用 type：text / password。
required=1 的字段会在站点保存时被后端强校验。

6. 业务接入点（已插件化）
以下核心路径已经通过 DockingPluginProviderFactory::getPluginForProduct($product) 做分发：

下单前上游价格/库存检查（Api/Order）
支付回调发货（Api/Payment::handleProductOrderNotify）
后台补单发货（Api/Order::adminUpdate）
批量补单（Api/Order::_performCompensate）
商品/店铺/首页库存展示（Api/Product、Api/Shop、Api/Home）
因此新增插件后，满足 supports() 即可自动参与这些流程。

7. 与前端/接口联动
7.1 获取插件列表
接口：GET /api/product/docking_types
返回：DockingPluginProviderFactory::listPlugins()
包含：

code
name
type
requires_remote_config
config_fields
7.2 对接站点配置保存
接口：POST /api/docking_site/save
若传入 docking_type，后端会按插件 getConfigFields() 校验必填配置。
8. 开发建议
supports() 要严格，避免误命中其它插件。
getType() 保持稳定，避免线上商品历史数据失配。
fetchCardsForOrder() 必须保证返回字符串数组，异常时抛出明确错误。
外部 HTTP 调用建议记录日志（请求地址、响应码、错误信息）。
对远程异常要兜底（超时、空响应、非 JSON、业务码非 200）。
任何“是否外部对接”的判断优先用插件能力，不再新增硬编码 docking_type==X 分支。
9. 验收清单
新增插件后请至少验证：

商品编辑页可看到插件类型并正确保存
对接站点配置必填校验生效
商品列表/店铺页库存显示正常
下单后可正常发货（含卡密回填）
后台 adminUpdate 与批量补单可正常走插件发货
上游异常时返回可读错误，不出现静默失败
10. 常见问题
Q1：新增插件后列表没出现？
检查文件是否在 service/docking_plugin 下
检查命名空间/类名
检查是否实现 DockingPluginInterface
检查 getType() 是否与其它插件冲突
Q2：插件被加载了，但业务没走到？
检查 supports($product) 判定条件
检查商品的 is_docking / docking_type / source_product_id 字段值
Q3：是否还需要实现“同系统对接”插件？
不需要。当前仅保留跨站对接插件，站内“同系统对接”能力已统一归并到跨站对接模型中。



实名认证插件开发文档
本文档说明如何在熵云寄售他新增/维护“实名认证插件”（Identity Plugin），用于统一处理：

实名认证服务商切换
实名认证配置项动态渲染
发起认证
查询认证结果
后台插件显示/隐藏与卸载
1. 目录与核心文件
插件目录：app/service/identity
接口定义：app/service/identity/IdentityDriverInterface.php
插件工厂：app/service/IdentityProviderFactory.php
后台插件列表接口：app/controller/Api/System.php
当前内置插件
驱动类	getKey()	说明
ManualDriver	manual	人工审核，系统内置兜底流程
AlipayDriver	alipay	支付宝实名认证
TencentDriver	tencent	腾讯云慧眼实名认证
OjwyunVideoDriver	ojwyun_v1_video	OJW 实人认证 H5
2. 插件加载机制
系统通过 IdentityProviderFactory::drivers() 自动扫描：

app/service/identity/*.php
复制
并实例化所有实现了 IdentityDriverInterface 的类。

关键点：

文件必须放在 app/service/identity 目录下。
类命名空间必须是 app\service\identity。
类必须实现 IdentityDriverInterface。
getKey() 返回值必须唯一。
插件会受系统设置 system_plugin_visibility.identity 控制，隐藏后不会出现在可选实名服务商列表。
当前启用的实名服务商由系统设置 real_name_provider 决定。
如果未配置 real_name_provider，且旧配置 tencent_faceid_enabled=1，系统会兼容使用 tencent。
如果目标插件不存在或被隐藏，系统自动回退到 manual。
3. 接口说明（必须实现）
IdentityDriverInterface 定义如下：

<?php
namespace app\service\identity;

interface IdentityDriverInterface
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getDescription(): string;
    public function getFields(): array;
    public function getStartMessage(): string;
    public function getStatusMessage(string $status): string;
    public function startAuth(string $realName, string $idCard, string $returnUrl): array;
    public function checkStatus(string $bizToken): array;
}
复制
3.1 getKey(): string
返回插件唯一标识。

要求：

全局唯一。
建议使用小写字母、数字、下划线。
会作为 real_name_provider 的保存值。
后台插件管理的显示/隐藏、卸载也依赖这个 key。
public function getKey(): string
{
    return 'demo_identity';
}
复制
3.2 getLabel(): string
返回插件显示名称，用于后台下拉框和插件列表。

public function getLabel(): string
{
    return '演示实名认证';
}
复制
3.3 getDescription(): string
返回插件描述，支持 HTML。

通常用于展示：

服务商名称
费用说明
产品地址
使用注意事项
public function getDescription(): string
{
    return '<div class="space-y-1">'
        . '<p><span class="font-medium">费用参考：</span>按量计费</p>'
        . '<p><span class="font-medium">产品地址：</span>'
        . '<a href="https://example.com" target="_blank" class="underline">https://example.com</a></p>'
        . '<p>演示实名认证服务。</p></div>';
}
复制
3.4 getFields(): array
返回后台动态配置字段。

字段会由前端动态渲染，并保存到 system_settings 表。

public function getFields(): array
{
    return [
        [
            'key' => 'demo_identity_app_id',
            'label' => 'AppID',
            'type' => 'text',
            'placeholder' => '请输入 AppID'
        ],
        [
            'key' => 'demo_identity_secret',
            'label' => 'Secret',
            'type' => 'password',
            'placeholder' => '请输入 Secret'
        ]
    ];
}
复制
常用字段属性：

字段	说明
key	配置项键名，对应 system_settings.key
label	表单标签
type	控件类型
placeholder	输入提示
tip	辅助说明
rows	textarea 行数
min / max / step	数字输入约束
default	默认值
常用 type：

type	说明
text	普通文本
password	密码/密钥
textarea	多行文本，适合证书、私钥、公钥
number	数字输入
info	纯说明信息
建议配置 key 加插件前缀，例如 demo_identity_，避免和其他配置冲突。

3.5 getStartMessage(): string
发起认证成功后，前端展示的提示文案。

public function getStartMessage(): string
{
    return '请在新页面完成实名认证';
}
复制
3.6 getStatusMessage(string $status): string
根据认证状态返回展示文案。

系统常用状态：

status	说明
success	认证成功
pending	认证中
failed	认证失败
public function getStatusMessage(string $status): string
{
    if ($status === 'success') {
        return '认证成功';
    }
    if ($status === 'pending') {
        return '认证进行中';
    }
    return '认证失败';
}
复制
3.7 startAuth(string $realName, string $idCard, string $returnUrl): array
发起实名认证。

参数：

参数	说明
$realName	用户填写的真实姓名
$idCard	用户填写的身份证号
$returnUrl	认证完成后的返回地址
返回格式：

return [
    'url' => 'https://example.com/auth?token=xxx',
    'biz_token' => '第三方业务 token',
    'provider' => $this->getKey()
];
复制
字段说明：

字段	必填	说明
url	是	前端需要跳转/打开的认证地址
biz_token	是	后续查询认证状态使用的业务 token
provider	是	当前插件 key
异常处理：

配置缺失时抛出 Exception('未配置实名认证服务')。
第三方返回异常时抛出具体错误信息。
不要直接 echo / exit。
3.8 checkStatus(string $bizToken): array
查询实名认证状态。

参数：

参数	说明
$bizToken	startAuth() 返回的 biz_token
返回格式：

return [
    'status' => 'success',
    'data' => [
        'raw' => '第三方原始响应或关键字段'
    ]
];
复制
字段说明：

字段	必填	说明
status	是	success / pending / failed
data	否	第三方返回数据，建议保留关键字段用于排查
4. 最小插件骨架
新增文件：

app/service/identity/DemoIdentityDriver.php
复制
示例代码：

<?php
namespace app\service\identity;

use app\service\SettingService;
use think\Exception;

class DemoIdentityDriver implements IdentityDriverInterface
{
    public function getKey(): string
    {
        return 'demo_identity';
    }

    public function getLabel(): string
    {
        return '演示实名认证';
    }

    public function getDescription(): string
    {
        return '<div class="space-y-1">'
            . '<p><span class="font-medium">费用参考：</span>按量计费</p>'
            . '<p><span class="font-medium">产品地址：</span>'
            . '<a href="https://example.com" target="_blank" class="underline">https://example.com</a></p>'
            . '<p>演示实名认证插件。</p></div>';
    }

    public function getFields(): array
    {
        return [
            [
                'key' => 'demo_identity_api_key',
                'label' => 'API Key',
                'type' => 'password',
                'placeholder' => '请输入 API Key'
            ],
            [
                'key' => 'demo_identity_auth_type',
                'label' => '认证类型',
                'type' => 'number',
                'min' => 1,
                'max' => 3,
                'step' => 1,
                'default' => 1,
                'placeholder' => '请输入认证类型'
            ]
        ];
    }

    public function getStartMessage(): string
    {
        return '请在新页面完成实名认证';
    }

    public function getStatusMessage(string $status): string
    {
        if ($status === 'success') {
            return '认证成功';
        }
        if ($status === 'pending') {
            return '认证进行中';
        }
        return '认证失败';
    }

    public function startAuth(string $realName, string $idCard, string $returnUrl): array
    {
        $apiKey = trim((string)SettingService::get('demo_identity_api_key'));
        if ($apiKey === '') {
            throw new Exception('未配置实名认证服务');
        }

        $result = $this->request('/auth/start', [
            'name' => $realName,
            'id_card' => $idCard,
            'return_url' => $returnUrl
        ], $apiKey);

        $url = trim((string)($result['url'] ?? ''));
        $token = trim((string)($result['token'] ?? ''));
        if ($url === '' || $token === '') {
            throw new Exception('实名认证服务返回异常');
        }

        return [
            'url' => $url,
            'biz_token' => $token,
            'provider' => $this->getKey()
        ];
    }

    public function checkStatus(string $bizToken): array
    {
        $apiKey = trim((string)SettingService::get('demo_identity_api_key'));
        if ($apiKey === '') {
            throw new Exception('未配置实名认证服务');
        }

        $result = $this->request('/auth/query', [
            'token' => $bizToken
        ], $apiKey);

        $passed = !empty($result['passed']);

        return [
            'status' => $passed ? 'success' : 'failed',
            'data' => $result
        ];
    }

    private function request(string $path, array $params, string $apiKey): array
    {
        $url = 'https://api.example.com' . $path;
        $payload = json_encode($params, JSON_UNESCAPED_UNICODE);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ]),
                'content' => $payload,
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new Exception('实名认证服务请求失败');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new Exception('实名认证服务返回异常');
        }

        return $decoded;
    }
}
复制
5. 参考 OJW 实人认证插件
OjwyunVideoDriver 是一个完整的第三方 H5 实人认证示例，核心流程如下：

5.1 配置字段
[
    [
        'key' => 'ojwyun_face_key',
        'label' => 'API Key',
        'type' => 'password'
    ],
    [
        'key' => 'ojwyun_face_type',
        'label' => '认证类型',
        'type' => 'number',
        'default' => 1
    ]
]
复制
5.2 发起认证
调用第三方初始化接口，传入：

name
idcard
type
returnurl
notifyurl
并返回：

[
    'url' => $url,
    'biz_token' => $token,
    'provider' => $this->getKey()
]
复制
5.3 查询认证状态
调用第三方查询接口，根据返回字段判断是否通过：

$passed = $success === true || $success === 1 || $success === '1' || $success === 'true';

return [
    'status' => $passed ? 'success' : 'failed',
    'data' => $result
];
复制
6. 与后台设置联动
6.1 获取实名插件列表
接口：

GET /api/system/identity_providers
复制
返回来源：

IdentityProviderFactory::listProviders()
复制
返回结构包含：

[
    'key' => $driver->getKey(),
    'label' => $driver->getLabel(),
    'description' => $driver->getDescription(),
    'fields' => $driver->getFields()
]
复制
6.2 插件管理列表
接口：

GET /api/system/plugin_providers
复制
其中 identity 分组会扫描 app/service/identity，用于后台插件管理。

6.3 启用某个实名插件
后台会保存系统设置：

real_name_provider = 插件 getKey()
复制
例如：

real_name_provider = ojwyun_v1_video
复制
6.4 隐藏或卸载插件
后台插件管理会通过：

system_plugin_visibility.identity.{key}
复制
控制插件是否显示。

卸载插件时，如果当前 real_name_provider 等于被卸载插件的 key，系统会清空该设置并回退到默认流程。

7. 业务调用流程
7.1 解析当前实名服务商
$provider = IdentityProviderFactory::resolveProvider();
逻辑：
优先读取 real_name_provider。
兼容旧配置 tencent_faceid_enabled=1。
没有可用插件时回退 manual。
7.2 发起认证
$result = IdentityProviderFactory::startAuth(
    $provider,
    $realName,
    $idCard,
    $returnUrl
);
插件需要返回认证地址和业务 token。

7.3 查询状态
$result = IdentityProviderFactory::checkStatus($provider, $bizToken);
插件需要返回统一状态：

[
    'status' => 'success|pending|failed',
    'data' => []
]
8. 开发规范
插件类名建议以 Driver 结尾，例如 DemoIdentityDriver。
getKey() 必须稳定，发布后不要随意改名，否则旧配置会失效。
配置字段 key 必须加插件前缀，避免与系统配置冲突。
不要在插件中直接输出内容，不要使用 echo、var_dump、exit。
认证失败、接口异常、配置缺失统一抛出 Exception。
第三方接口原始返回建议放入 data，方便后续排查。
身份证号、姓名、token 属于敏感信息，不要写入普通日志。
checkStatus() 应只返回业务状态，不要在里面修改用户认证状态，状态落库由业务层处理。
外部请求建议设置超时时间，避免阻塞请求。
H5 实人认证类插件必须正确处理 returnUrl。
9. 常见问题
9.1 插件没有出现在后台列表
检查：

文件是否在 app/service/identity。
命名空间是否为 app\service\identity。
类是否实现 IdentityDriverInterface。
getKey() 是否和其他插件重复。
system_plugin_visibility.identity.{key} 是否被设置为 0。
9.2 保存配置后仍提示未配置
检查：

getFields() 中的 key 是否和插件读取的 SettingService::get() key 一致。
配置是否保存到了 system_settings。
是否需要清理配置缓存。
9.3 认证发起成功但查询失败
检查：

startAuth() 返回的 biz_token 是否是第三方查询接口需要的 token。
checkStatus() 使用的查询接口是否和初始化接口匹配。
第三方是否异步回调，需要等待一段时间后再查询。
9.4 认证完成后没有跳回系统
检查：

发起认证时是否传入了 $returnUrl。
第三方参数名是否正确，如 returnurl、return_url、callbackUrl。
第三方后台是否需要配置白名单域名。
10. 发布检查清单
新增实名插件后，至少检查：

 PHP 文件语法正确。
 插件出现在后台实名服务商列表。
 后台配置字段能正常显示和保存。
 real_name_provider 能保存为插件 key。
 发起认证返回 url、biz_token、provider。
 查询认证状态返回 success / pending / failed。
 配置缺失时提示明确。
 第三方接口异常时不会导致页面白屏。
