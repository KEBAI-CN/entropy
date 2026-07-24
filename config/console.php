<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => array_merge([
        'update_db' => 'app\command\UpdateDb',
        'crontab:run' => 'app\command\Crontab',
        'CreateWithdrawalBatch' => 'app\command\AutoCreateWithdrawalBatch',
        'ComplaintTimeoutNotify' => 'app\command\ComplaintTimeoutNotify',
        'ReleaseTimeoutOrders' => 'app\command\ReleaseTimeoutOrders',
        'UpdateOrderLogistics' => 'app\command\UpdateOrderLogistics',
        'CompleteSignedLogisticsOrders' => 'app\command\CompleteSignedLogisticsOrders',
        'update:supply_auth' => 'app\command\UpdateSupplyAuth',
        'ensure:data' => 'app\command\EnsureData',
        'customer_service:ws' => 'app\command\CustomerServiceWs',
        'app\command\TestComplaints',
        'Tools' => 'app\command\Tools',
    ], class_exists(\app\service\plugin\PluginManager::class) ? \app\service\plugin\PluginManager::commands() : []),
];

