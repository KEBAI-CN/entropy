<?php
use think\facade\Route;
use app\service\SettingService;

Route::get('/', function () {
    return app(\app\controller\Page::class)->home();
});

Route::get('payment/scan', 'Page/paymentScan');
Route::get('payment/mobile-launch', 'Page/paymentMobileLaunch');
Route::get('payment/result', 'Page/paymentResult');
Route::get('payment/callback', 'Page/paymentCallback');
Route::any('wechat/api', 'Api.WechatScanAuth/serve');

Route::get('mobile-user', 'Page/mobileUser');
Route::get('mobile-user/:path', 'Page/mobileUser');

$adminLoginPrefix = 'admin';
try {
    $configuredAdminPrefix = trim((string)SettingService::get('admin_security_path', 'admin'), '/');
    if ($configuredAdminPrefix !== '') {
        $adminLoginPrefix = $configuredAdminPrefix;
    }
} catch (\Throwable $e) {
    $adminLoginPrefix = 'admin';
}

Route::get($adminLoginPrefix . '/login', 'Page/adminLogin');


Route::get('debug/docking', 'DebugDocking/index');
Route::get('debug/fix', 'DebugDocking/fix');

Route::get('notification-test', function () {
    $file = public_path() . 'notification-test.html';
    if (file_exists($file)) {
        return \think\Response::create(file_get_contents($file));
    }
    return 'notification-test not found';
});

Route::group('install', function () {
    Route::get('check_lock', 'Install/checkLock');
    Route::get('check_env', 'Install/checkEnv');
    Route::any('save_license', 'Install/saveLicense');
    Route::any('verify_license', 'Install/saveLicense');
    Route::any('license', 'Install/saveLicense');
    Route::any('test_db', 'Install/testDb');
    Route::any('run', 'Install/install');
    Route::any('create_admin', 'Install/createAdmin');
});

\app\service\plugin\PluginManager::registerRoutes();

Route::miss(function() {
    $request = request();
    if (strpos($request->url(), '/api/') === 0) {
        return json(['code' => 404, 'msg' => 'API Not Found', 'url' => $request->url()], 404);
    }
    
    return app(\app\controller\Page::class)->fallback(request());
});

Route::group('api', function () {
    // Install
    Route::group('install', function () {
        Route::get('check_lock', 'Install/checkLock');
        Route::get('check_env', 'Install/checkEnv');
        Route::any('save_license', 'Install/saveLicense');
        Route::any('verify_license', 'Install/saveLicense');
        Route::any('license', 'Install/saveLicense');
        Route::any('test_db', 'Install/testDb');
        Route::any('run', 'Install/install');
        Route::any('create_admin', 'Install/createAdmin');
    });

    Route::get('update_db', 'Api.UpdateDb/index');

    Route::get('order/fix/:no', 'Api.Order/fixOrder');

    // Public Home
    Route::get('home/index', 'Api.Home/index');
    Route::get('home/article/:id', 'Api.Home/article');
    
    // Public Order Query
    Route::get('order/query', 'Api.Order/query');
    Route::get('order/detail', 'Api.Order/detail');
    Route::post('order/receipt/code', 'Api.Order/sendReceiptCode');
    Route::post('order/confirm_receipt', 'Api.Order/confirmReceipt');
    Route::post('order/quote', 'Api.Order/quote');
    Route::post('order/create', 'Api.Order/create');
    Route::get('order/my', 'Api.Order/myOrders');

    // System Menus
    Route::get('system/menus', 'Api.System/menus');
    Route::get('system/permission-menus', 'Api.System/permissionMenus');
    Route::get('system/general-plugins/config', 'Api.System/generalPluginConfig');
    Route::post('system/general-plugins/config', 'Api.System/saveGeneralPluginConfig');
    Route::post('system/general-plugins/action', 'Api.System/runGeneralPluginAction');
    Route::post('system/general-plugins/toggle', 'Api.System/toggleGeneralPlugin');
    Route::get('system/general-plugins', 'Api.System/generalPlugins');
    Route::get('plugin/slots', 'Api.PluginSlot/slots')->middleware(\app\middleware\RequireLogin::class);
    Route::post('system/setting/cache/test-redis', 'Api.System/testRedisConnection');
    Route::any('system/setting/cache', 'Api.System/clearCache');
    Route::get('system/email-account/list', 'Api.EmailAccountController/list');
    Route::post('system/email-account/save', 'Api.EmailAccountController/save');
    Route::post('system/email-account/update', 'Api.EmailAccountController/update');
    Route::post('system/email-account/delete', 'Api.EmailAccountController/delete');
    Route::post('system/email-account/toggle', 'Api.EmailAccountController/toggle');
    Route::post('system/email-account/strategy', 'Api.EmailAccountController/updateStrategy');
    Route::post('system/email-account/test-send', 'Api.EmailAccountController/testSend');
    Route::get('system/email-account/log-months', 'Api.EmailAccountController/logMonths');
    Route::get('system/email-account/log-files', 'Api.EmailAccountController/logFiles');
    Route::get('system/email-account/log-list', 'Api.EmailAccountController/logList');
    Route::post('system/wechat-official/menu/publish', 'Api.System/publishWechatOfficialMenu');
    Route::post('system/wechat-official/menu/cancel', 'Api.System/cancelWechatOfficialMenu');
    Route::get('system/identity/providers', 'Api.System/identityProviders');
    Route::get('system/sms/providers', 'Api.System/smsProviders');
    Route::get('system/short-link/providers', 'Api.System/shortLinkProviders');
    Route::get('system/upload/providers', 'Api.System/uploadProviders');
    Route::get('system/logistics/providers', 'Api.System/logisticsProviders');
    Route::get('system/shop/effects/:code', 'Api.Shop/effect');
    Route::get('shop/effects/:code', 'Api.Shop/effect');
    Route::get('shop/template/thumb/:code', 'Api.Shop/templateThumb');

    // Theme Templates
    Route::group('theme', function () {
        Route::get('templates', 'Api.Theme/templates');
        Route::get('payload', 'Api.Theme/payload');
    });

    // Auth
    Route::get('auth/captcha', 'Api.Auth/captcha');
    Route::post('auth/login', 'Api.Auth/login');
    Route::post('auth/userLogin', 'Api.Auth/userLogin');
    Route::post('auth/impersonate', 'Api.Auth/impersonate');
    Route::post('auth/register', 'Api.Auth/register');
    Route::post('auth/bindInviteCode', 'Api.Auth/bindInviteCode');
    Route::post('auth/resetPassword', 'Api.Auth/resetPassword');
    
    // QQ Scan Login
    Route::get('auth/qq_scan/qrcode', 'Api.QQScanAuth/getQrCode');
    Route::post('auth/qq_scan/check', 'Api.QQScanAuth/checkStatus');
    Route::post('auth/qq_scan/bind', 'Api.QQScanAuth/bind');
    Route::post('auth/qq_scan/unbind', 'Api.QQScanAuth/unbind');

    // Wechat Scan Login
    Route::get('auth/wechat_scan/qrcode', 'Api.WechatScanAuth/getQrCode');
    Route::post('auth/wechat_scan/check', 'Api.WechatScanAuth/checkStatus');
    Route::post('auth/wechat_scan/bind', 'Api.WechatScanAuth/bind');
    Route::post('auth/wechat_scan/unbind', 'Api.WechatScanAuth/unbind');
    Route::any('auth/wechat_scan/callback', 'Api.WechatScanAuth/serve'); // WeChat Server Callback
    
    // Social Login
    Route::get('auth/social/redirect/:provider', 'Api.Auth/socialRedirect');
    Route::get('auth/social/callback/:provider', 'Api.Auth/socialCallback');
    Route::post('auth/social/unbind', 'Api.Auth/unbindSocial');
    Route::post('auth/social/bind_register', 'Api.Auth/bindRegister');
    
    // Docking User Auth
    Route::group('docking', function () {
        // Auth
        Route::get('auth/captcha', 'Api.DockingAuth/captcha');
        Route::post('auth/sendMobileCode', 'Api.DockingAuth/sendMobileCode');
        Route::post('auth/sendEmailCode', 'Api.DockingAuth/sendEmailCode');
        Route::post('auth/register', 'Api.DockingAuth/register');
        Route::post('auth/login', 'Api.DockingAuth/login');
        Route::post('auth/resetPassword', 'Api.DockingAuth/resetPassword');
        Route::get('auth/config', 'Api.DockingAuth/config');
        Route::any('products/fetch', 'Api.DockingAuth/getDockingProducts');

        // User (Token Auth)
        Route::group('user', function () {
            Route::get('info', 'Api.DockingAuth/info');
            Route::post('updateProfile', 'Api.DockingAuth/updateProfile');
            Route::post('changePassword', 'Api.DockingAuth/changePassword');
            Route::post('changeAccount', 'Api.DockingAuth/changeAccount');
            Route::post('recharge', 'Api.DockingAuth/recharge');
            Route::get('paymentMethods', 'Api.DockingAuth/getPaymentMethods');
            Route::get('orders', 'Api.DockingAuth/orders');
            Route::get('balance_logs', 'Api.DockingAuth/balanceLogs');
        });
    });

    // Docking Sites (User specific)
    Route::group('docking_site', function() {
        Route::get('list', 'Api.DockingSite/index');
        Route::post('save', 'Api.DockingSite/save');
        Route::post('delete', 'Api.DockingSite/delete');
    })->middleware(\app\middleware\RequireLogin::class);
    
    // Common
    Route::post('upload/image', 'Api.Common/upload');
    Route::post('upload/video', 'Api.Common/uploadVideo');
    Route::get('common/location', 'Api.Common/location');
    Route::get('common/captcha', 'Api.Common/captcha');
    Route::get('common/orderQueryImageCaptcha', 'Api.Common/orderQueryImageCaptcha');
    Route::post('common/sendMobileCode', 'Api.Common/sendMobileCode');
    Route::post('common/sendEmailCode', 'Api.Common/sendEmailCode');

    Route::group('user', function () {
        Route::get('info', 'Api.User/info');
        Route::get('list', 'Api.User/list');
        Route::get('userList', 'Api.User/userList');
        Route::post('saveUser', 'Api.User/saveUser');
        Route::post('auditRealName', 'Api.User/auditRealName');
        Route::post('updateProfile', 'Api.User/updateProfile');
        Route::get('shopSettings', 'Api.User/getUserShopSettings');
        Route::post('shopSettings', 'Api.User/updateUserShopSettings');
        Route::post('adjustBalance', 'Api.User/adjustBalance');
        Route::post('buyRealNameAuthCount', 'Api.User/buyRealNameAuthCount');
        Route::post('deleteUser', 'Api.User/deleteUser');
        Route::post('deleteAdmin', 'Api.User/deleteAdmin');
        Route::post('updateMobile', 'Api.User/updateMobile');
        Route::post('updateEmail', 'Api.User/updateEmail');
        Route::post('updatePassword', 'Api.User/updatePassword');
        Route::post('realNameAuth', 'Api.User/realNameAuth');
        Route::post('checkRealNameStatus', 'Api.User/checkRealNameStatus');
        Route::post('updateComplaintNotify', 'Api.User/updateComplaintNotify');
        Route::get('riskControl', 'Api.User/getRiskControl');
        Route::post('riskControl', 'Api.User/saveRiskControl');

        // User Shop Settings
        Route::group('shop', function() {
            Route::get('announcements', 'Api.Shop/getAnnouncements');
            Route::get('announcement/:id', 'Api.Shop/getAnnouncementDetail');
            Route::get('open-status', 'Api.Shop/getOpenStatus');
            Route::post('apply-open', 'Api.Shop/applyOpen');
            Route::get('settings', 'Api.Shop/getSettings');
            Route::post('settings', 'Api.Shop/updateSettings');
            Route::get('plugins', 'Api.Shop/getHomePlugins');
            Route::get('templates', 'Api.Shop/getTemplateCatalog');
            Route::get('orders', 'Api.Shop/getSoldOrders');
            Route::get('reports', 'Api.ShopReport/getList');
            Route::get('dashboard', 'Api.Shop/getDashboardStats');
            Route::post('slug/regenerate', 'Api.Shop/regenerateSlug');
            Route::post('slug', 'Api.Shop/updateSlug');
            Route::post('short-link', 'Api.Shop/generateShortLink');
        });

        // Agent Management
        Route::group('agent', function () {
            Route::get('info', 'Api.Agent/info');
            Route::post('status', 'Api.Agent/updateStatus');
            Route::post('regenerate_code', 'Api.Agent/regenerateCode');
            Route::get('shops', 'Api.Agent/agentShops');
            Route::get('products', 'Api.Agent/products');
            Route::get('market', 'Api.Agent/marketProducts');
            Route::get('levels', 'Api.Agent/agentLevels');
            Route::post('default_level', 'Api.Agent/updateDefaultAgentLevel');
            Route::post('product/toggle', 'Api.Agent/toggleProduct');
            Route::post('product/import', 'Api.Agent/importProduct');
            Route::post('connect', 'Api.Agent/connect');
            Route::post('disconnect', 'Api.Agent/disconnect');

            // Sub-Agent Management
            Route::post('sub_agent/status', 'Api.Agent/updateAgentStatus');
            Route::post('sub_agent/level', 'Api.Agent/updateAgentLevel');
            Route::get('sub_agent/products', 'Api.Agent/agentProducts');
            Route::get('sub_agent/orders', 'Api.Agent/agentOrders');
        });
    })->middleware(\app\middleware\RequireLogin::class);

    // Supply Square
    Route::group('supply', function () {
        Route::get('index', 'Api.Supply/index');
        Route::get('detail', 'Api.Supply/detail');
        Route::get('products', 'Api.Supply/products');
        Route::get('myStore', 'Api.Supply/myStore');
        Route::post('saveStore', 'Api.Supply/saveStore');
        Route::get('getSupplySettings', 'Api.Supply/getSupplySettings');
    })->middleware(\app\middleware\RequireLogin::class);

    // Shop Public
    Route::post('shop/report', 'Api.ShopReport/submit'); // Submit Shop Report
    Route::get('shop/info', 'Api.Shop/info'); // Public Shop Info
    Route::get('shop/products', 'Api.Shop/products'); // Public Shop Products
    
    // Product
    Route::group('product', function () {
        Route::get('list', 'Api.Product/list');
        Route::get('docking_types', 'Api.Product/dockingTypes');
        Route::get('docking_products_fetch', 'Api.Product/fetchDockingProducts');
        Route::get('upstream_stock', 'Api.Product/getUpstreamStock');
        Route::get('upstream_info', 'Api.Product/getUpstreamInfo');
        Route::post('save', 'Api.Product/save');
        Route::post('delete', 'Api.Product/delete');
        Route::post('offShelf', 'Api.Product/offShelf');
        Route::post('onShelf', 'Api.Product/onShelf');
        Route::post('forceOffline', 'Api.Product/forceOffline');
        Route::post('cancel_audit', 'Api.Product/cancelAudit');
        Route::post('regenerate_uuid', 'Api.Product/regenerateUuid');
    })->middleware(\app\middleware\RequireLogin::class);

    // Category
    Route::group('category', function () {
        Route::get('list', 'Api.Category/list');
        Route::get('select', 'Api.Category/select');
        Route::post('save', 'Api.Category/save');
        Route::post('delete', 'Api.Category/delete');
        Route::post('regenerate_slug', 'Api.Category/regenerateSlug');
    })->middleware(\app\middleware\RequireLogin::class);

    // CardKey
    Route::group('cardKey', function () {
        Route::get('list', 'Api.CardKey/list');
        Route::get('recycleBin', 'Api.CardKey/recycleBin');
        Route::post('save', 'Api.CardKey/save');
        Route::post('delete', 'Api.CardKey/delete');
        Route::post('restore', 'Api.CardKey/restore');
        Route::post('forceDelete', 'Api.CardKey/forceDelete');
        Route::post('clearByProduct', 'Api.CardKey/clearByProduct');
    })->middleware(\app\middleware\RequireLogin::class);

    // Knowledge Article (merchant CRUD, requires login)
    Route::group('knowledge', function () {
        Route::get('articles', 'Api.KnowledgeArticle/list');
        Route::post('article/save', 'Api.KnowledgeArticle/save');
        Route::post('article/delete', 'Api.KnowledgeArticle/delete');
        Route::get('article/detail', 'Api.KnowledgeArticle/detail');
        Route::get('products', 'Api.KnowledgeArticle/productList');
        Route::get('article_list', 'Api.KnowledgeArticle/articleList');
        Route::get('articles_by_product', 'Api.KnowledgeArticle/articlesByProduct');
    })->middleware(\app\middleware\RequireLogin::class);

    // Knowledge Article (public: buyer view)
    Route::get('knowledge/public/articles', 'Api.KnowledgeArticle/publicList');
    Route::get('knowledge/public/article', 'Api.KnowledgeArticle/publicDetail');
    Route::get('knowledge/public/check_access', 'Api.KnowledgeArticle/checkAccessStatus');
    Route::post('knowledge/public/generate_token', 'Api.KnowledgeArticle/generateAccessToken');

    // Invite Code
    Route::group('inviteCode', function () {
        Route::get('list', 'Api.InviteCode/index');
        Route::get('export', 'Api.InviteCode/export');
        Route::post('generate', 'Api.InviteCode/generate');
        Route::post('delete', 'Api.InviteCode/delete');
    })->middleware(\app\middleware\RequireLogin::class);

    // Log
    Route::group('log', function () {
        Route::get('operation', 'Api.Log/operation');
        Route::get('login', 'Api.Log/login');
    })->middleware(\app\middleware\RequireAdmin::class);

    // Wallet
    Route::group('wallet', function () {
        Route::get('info', 'Api.Wallet/info');
        Route::get('transactions', 'Api.Wallet/transactions');
        Route::get('withdrawals', 'Api.Wallet/withdrawals');
        Route::post('withdraw', 'Api.Wallet/withdraw');
        
        Route::post('deposit/recharge', 'Api.Wallet/rechargeDeposit');
        Route::post('deposit/withdraw', 'Api.Wallet/withdrawDeposit');
        
        Route::post('operating/recharge', 'Api.Wallet/rechargeOperating');
        Route::post('operating/withdraw', 'Api.Wallet/withdrawOperating');
        
        Route::get('withdrawal/settings', 'Api.Wallet/getWithdrawalSettings');
        
        Route::get('withdrawal/accounts', 'Api.Wallet/getWithdrawalAccounts');
        Route::post('withdrawal/account/add', 'Api.Wallet/addWithdrawalAccount');
        Route::post('withdrawal/account/update', 'Api.Wallet/updateWithdrawalAccount');
        Route::post('withdrawal/account/delete', 'Api.Wallet/deleteWithdrawalAccount');
    })->middleware(\app\middleware\RequireLogin::class);

    // Finance
    Route::group('finance', function () {
        Route::get('transactions', 'Api.Finance/transactions');
        Route::get('withdrawals', 'Api.Finance/withdrawals');
        Route::post('withdrawal/audit', 'Api.Finance/auditWithdrawal');
        Route::post('withdrawal/batch/audit', 'Api.Finance/batchAuditWithdrawal');
        Route::get('withdrawal/batches', 'Api.Finance/withdrawalBatches');
        Route::post('withdrawal/batch/create', 'Api.Finance/createWithdrawalBatch');
        Route::get('withdrawal/batch/export', 'Api.Finance/exportWithdrawalBatch');
        Route::get('withdrawal/batch/detail', 'Api.Finance/getBatchWithdrawals');
        Route::post('withdrawal/batch/set_status', 'Api.Finance/setBatchStatus');
        Route::get('transfers', 'Api.Finance/transferRecords');
        Route::get('transfer/candidates', 'Api.Finance/transferCandidates');
        Route::get('transfer/balance', 'Api.Finance/transferBalance');
        Route::post('transfer/submit', 'Api.Finance/submitTransfer');
        Route::get('transfer/accounts', 'Api.Finance/transferAccounts');
        Route::post('transfer/account/save', 'Api.Finance/saveTransferAccount');
        Route::post('transfer/account/delete', 'Api.Finance/deleteTransferAccount');
        Route::post('transfer/account/toggle', 'Api.Finance/toggleTransferAccount');
        Route::post('transfer/account/upload-cert', 'Api.Finance/uploadTransferCert');
        Route::get('withdrawal/accounts', 'Api.Finance/withdrawalAccounts');
        Route::post('withdrawal/account/update', 'Api.Finance/updateWithdrawalAccount');
        Route::post('withdrawal/account/audit', 'Api.Finance/auditWithdrawalAccount');
        Route::post('withdrawal/account/delete', 'Api.Finance/deleteWithdrawalAccount');
    })->middleware(\app\middleware\RequireAdmin::class);

    // Complaint (Public)
    Route::group('complaint', function () {
        Route::post('create', 'Api.Complaint/create');
        Route::get('query', 'Api.Complaint/query');
        Route::post('read', 'Api.Complaint/read');
        Route::post('buyer/reply', 'Api.Complaint/buyerReply');
        Route::post('buyer/cancel', 'Api.Complaint/cancel');
    });

    // Complaint (Protected)
    Route::group('complaint', function () {
        Route::get('my', 'Api.Complaint/myList');
        Route::get('seller/list', 'Api.Complaint/sellerList');
        Route::post('seller/reply', 'Api.Complaint/sellerReply');
        Route::get('admin/list', 'Api.Complaint/adminList');
        Route::get('admin/detail', 'Api.Complaint/adminDetail');
        Route::post('admin/reply', 'Api.Complaint/adminReply');
        Route::post('admin/resolve', 'Api.Complaint/adminResolve');
        Route::post('admin/delete', 'Api.Complaint/adminDelete');
    })->middleware(\app\middleware\RequireLogin::class);

    // Customer Service (buyer <-> seller, isolated by shop)
    Route::group('customer_service', function () {
        // buyer / guest public routes
        Route::get('buyer/session', 'Api.CustomerService/buyerSession');
        Route::get('buyer/messages', 'Api.CustomerService/buyerMessages');
        Route::post('buyer/send', 'Api.CustomerService/buyerSend');
        Route::post('buyer/read', 'Api.CustomerService/buyerMarkRead');

        // optional ws config
        Route::get('ws/config', 'Api.CustomerService/wsConfig');
    });

    Route::group('customer_service', function () {
        // seller (shop owner)
        Route::get('seller/sessions', 'Api.CustomerService/sellerSessions');
        Route::get('seller/messages', 'Api.CustomerService/sellerMessages');
        Route::post('seller/send', 'Api.CustomerService/sellerSend');
        Route::post('seller/read', 'Api.CustomerService/sellerMarkRead');
        Route::post('seller/blacklist', 'Api.CustomerService/sellerToggleBlacklist');
        Route::post('seller/heartbeat', 'Api.CustomerService/sellerHeartbeat');

        // admin (platform)
        Route::get('admin/sessions', 'Api.CustomerService/adminSessions');
        Route::get('admin/messages', 'Api.CustomerService/adminMessages');
    })->middleware(\app\middleware\RequireLogin::class);

    // Shop
    Route::group('shop', function () {
        Route::get('my', 'Api.Shop/myShop');
        Route::post('apply', 'Api.Shop/apply');
        Route::post('update', 'Api.Shop/update');
        Route::get('products', 'Api.Shop/products');
        Route::get('orders', 'Api.Shop/getSoldOrders');
        Route::get('order/detail', 'Api.Shop/getSoldOrderDetail');
        Route::post('order/deliver', 'Api.Shop/manualDeliverOrder');
        
        // Public routes
        Route::get('available-cards', 'Api.Shop/getAvailableCards');
        Route::get('product/:id', 'Api.Shop/getProductDetail');
        Route::get('template/:code', 'Api.Shop/getTemplateDetail');
        Route::get('resolve-domain', 'Api.Shop/resolveDomainShop');
        Route::get(':slug/public', 'Api.Shop/getPublicIndexBySlug');
        Route::get(':slug/products', 'Api.Shop/getProductsBySlug');
        Route::get(':slug/categories', 'Api.Shop/getCategoriesBySlug');
        Route::get('category/:categorySlug', 'Api.Shop/getCategoryPageBySlug');
        Route::get(':slug/payment-configs', 'Api.Shop/getPaymentConfigsBySlug');
        Route::get(':slug', 'Api.Shop/getBySlug');
    });

    // Shop Qualification
    Route::group('shop-qualification', function () {
        Route::post('submit', 'Api.ShopQualification/submit');
        Route::get('status', 'Api.ShopQualification/getStatus');
        Route::get('list', 'Api.ShopQualification/list');
        Route::get('detail', 'Api.ShopQualification/detail');
        Route::get('orders', 'Api.ShopQualification/orders');
        Route::post('audit', 'Api.ShopQualification/audit');
        Route::post('delete', 'Api.ShopQualification/delete');
    })->middleware(\app\middleware\RequireLogin::class);

    // Data Log
    Route::group('data_log', function () {
        Route::get('stats', 'Api.DataLog/getStats');
        Route::post('delete', 'Api.DataLog/delete');
    })->middleware(\app\middleware\RequireAdmin::class);

    // User Notification
    Route::group('user_notification', function () {
        Route::get('list', 'Api.UserNotification/list');
        Route::post('read', 'Api.UserNotification/read');
        Route::post('readAll', 'Api.UserNotification/readAll');
        Route::get('unreadCount', 'Api.UserNotification/unreadCount');
        Route::post('delete', 'Api.UserNotification/delete');
    })->middleware(\app\middleware\RequireLogin::class);

    // User Payment Config (requires login, not admin-only)
    Route::group('payment/user-config', function () {
        Route::get('index', 'Api.UserPaymentConfigController/index');
        Route::post('save', 'Api.UserPaymentConfigController/save');
        Route::get('read/:id', 'Api.UserPaymentConfigController/read');
        Route::post('delete/:id', 'Api.UserPaymentConfigController/delete');
    })->middleware(\app\middleware\RequireLogin::class);

    Route::group('user/payment-account', function () {
        Route::get('list', 'Api.ShopPaymentAccountController/list');
        Route::post('save', 'Api.ShopPaymentAccountController/save');
        Route::post('update', 'Api.ShopPaymentAccountController/update');
        Route::post('delete', 'Api.ShopPaymentAccountController/delete');
        Route::post('toggle', 'Api.ShopPaymentAccountController/toggle');
        Route::post('strategy', 'Api.ShopPaymentAccountController/updateStrategy');
    })->middleware(\app\middleware\RequireLogin::class);

    // Backward-compatible path used by older user-center bundles.
    Route::group('payment/shop-payment-account', function () {
        Route::get('list', 'Api.ShopPaymentAccountController/list');
        Route::post('save', 'Api.ShopPaymentAccountController/save');
        Route::post('update', 'Api.ShopPaymentAccountController/update');
        Route::post('delete', 'Api.ShopPaymentAccountController/delete');
        Route::post('toggle', 'Api.ShopPaymentAccountController/toggle');
        Route::post('strategy', 'Api.ShopPaymentAccountController/updateStrategy');
    })->middleware(\app\middleware\RequireLogin::class);

    // System settings (controller handles permission internally)
    Route::get('system/setting/getSettings', 'Api.System/getSettings')->middleware(\app\middleware\RequireLogin::class);

    // Coupon check — public, no login required (used by shop visitors)
    Route::post('coupon/check', 'Api.Coupon/check');

    // User Coupon (requires login, not admin-only)
    Route::group('coupon', function () {
        Route::get('list', 'Api.Coupon/list');
        Route::post('save', 'Api.Coupon/save');
        Route::post('delete', 'Api.Coupon/delete');
    })->middleware(\app\middleware\RequireLogin::class);

    // User logs (requires login, controller filters by user_id)
    Route::get('user/loginLogs', 'Api.User/loginLogs')->middleware(\app\middleware\RequireLogin::class);
    Route::get('user/operationLogs', 'Api.User/operationLogs')->middleware(\app\middleware\RequireLogin::class);

    // Payment
    Route::group('payment', function () {
        Route::get('qrcode', 'Api.Payment/getQrcode'); // Get Scan Payment QR Code
        Route::get('check', 'Api.Payment/checkStatus');
        Route::get('notify_order_async', 'Api.Payment/notifyOrderAsync');
        Route::any('notify/:type', 'Api.Payment/notify');

        // Public payment config endpoints (no auth required)
        Route::group('config', function () {
            Route::get('providers', 'Api.PaymentConfigController/providers');
            Route::get('enabled', 'Api.PaymentConfigController/getEnabled');
            Route::get('available', 'Api.PaymentConfigController/available');
            Route::get('types', 'Api.PaymentConfigController/getPayTypes');
        });
    });

    Route::group('v3', function () {
        Route::group('payment', function () {
            Route::any('notify/:type', 'Api.Payment/notify');
        });
    });

    Route::group('admin', function () {
        // Admin Article
        Route::group('article', function () {
            Route::get('index', 'Admin.Article/index');
            Route::post('save', 'Admin.Article/save');
            Route::post('delete', 'Admin.Article/delete');
            Route::get('detail', 'Admin.Article/detail');
        });

        // Supply Square Management
        Route::group('supply', function () {
            Route::get('index', 'Admin.Supply/index');
            Route::post('approve', 'Admin.Supply/approve');
            Route::post('reject', 'Admin.Supply/reject');
            Route::post('offShelf', 'Admin.Supply/offShelf');
            Route::post('onShelf', 'Admin.Supply/onShelf');
            Route::post('forceOffline', 'Admin.Supply/forceOffline');
            Route::post('cancelForceOffline', 'Admin.Supply/cancelForceOffline');
        });

        // Shop Report Management
        Route::group('shop_report', function () {
            Route::get('list', 'Admin.ShopReport/index');
            Route::post('delete', 'Admin.ShopReport/delete');
            Route::post('handle', 'Admin.ShopReport/handle');
        });

        // Coupon
        Route::group('coupon', function () {
            Route::get('index', 'Admin.Coupon/index');
            Route::post('save', 'Admin.Coupon/save');
            Route::post('delete', 'Admin.Coupon/delete');
        });
    })->middleware(\app\middleware\RequireAdmin::class);

    Route::group('', function () {
        // User Management (Admin)
        Route::group('user', function () {
            Route::get('info', 'Api.User/info');
            Route::get('list', 'Api.User/list');
            Route::get('userList', 'Api.User/userList');
            Route::post('saveUser', 'Api.User/saveUser');
            Route::post('auditRealName', 'Api.User/auditRealName');
            Route::post('deleteUser', 'Api.User/deleteUser');
            Route::post('deleteAdmin', 'Api.User/deleteAdmin');
        });

        // Role Management
        Route::group('role', function () {
            Route::get('list', 'Api.Role/list');
            Route::post('save', 'Api.Role/save');
            Route::post('create', 'Api.Role/create');
            Route::post('update', 'Api.Role/update');
            Route::post('delete', 'Api.Role/delete');
            Route::get('select', 'Api.Role/select');
        });

        Route::get('order/admin/list', 'Api.Order/adminIndex');
        Route::post('order/admin/update/:id', 'Api.Order/adminUpdate');
        Route::post('order/admin/delete', 'Api.Order/adminDelete');
        Route::post('order/admin/batch_operate', 'Api.Order/batchOperate');

        Route::get('system/notification/meta', 'Api.System/notificationSettingMeta');
        Route::get('system/notification-debug/meta', 'Api.NotificationDebug/meta');
        Route::post('system/notification-debug/send', 'Api.NotificationDebug/send');
        Route::get('system/plugin/providers', 'Api.System/pluginProviders');
        Route::post('system/plugin/visibility', 'Api.System/updatePluginVisibility');
        Route::post('system/plugin/enabled', 'Api.System/updatePluginEnabled');
        Route::post('system/plugin/uninstall', 'Api.System/uninstallProvider');
        Route::get('system/shop-plugins', 'Api.System/shopPlugins');
        Route::post('system/shop-plugins/visibility', 'Api.System/updateShopPluginVisibility');
        Route::post('system/shop-plugins/uninstall', 'Api.System/uninstallShopPlugin');
        Route::get('system/shop-templates', 'Api.System/shopTemplates');
        Route::get('system/shop-templates/:code', 'Api.System/shopTemplateDetail');
        Route::post('system/shop-templates/:code/variables', 'Api.System/updateShopTemplateVariables');
        Route::get('system/update/check', 'Api.SystemUpdate/index');
        Route::get('system/update/announcement', 'Api.SystemUpdate/announcement');
        Route::post('system/update/perform', 'Api.SystemUpdate/update');
        Route::get('system/update/database-repair/check', 'Api.SystemUpdate/databaseRepairCheck');
        Route::post('system/update/database-repair/perform', 'Api.SystemUpdate/databaseRepairPerform');

        Route::group('remote', function () {
            Route::get('user/info', 'Api.Remote/userInfo');
            Route::get('plugin/categories', 'Api.Remote/pluginCategories');
            Route::get('plugin/list', 'Api.Remote/pluginList');
            Route::get('plugin/detail', 'Api.Remote/pluginDetail');
            Route::post('plugin/buy', 'Api.Remote/pluginBuy');
            Route::get('plugin/download', 'Api.Remote/pluginDownload');
            Route::post('plugin/install', 'Api.Remote/pluginInstall');
        });

        Route::group('docking', function () {
            Route::get('users', 'Api.Docking/index');
            Route::get('list', 'Api.Docking/index');
            Route::get('orders', 'Api.Docking/orders');
            Route::get('balance_logs', 'Api.Docking/balanceLogs');
            Route::any('update', 'Api.Docking/update');
            Route::post('delete', 'Api.Docking/delete');
        });

        Route::group('server_log', function () {
            Route::get('list', 'Api.ServerLog/getList');
            Route::get('months', 'Api.ServerLog/getMonths');
            Route::get('files', 'Api.ServerLog/getFiles');
        });

        Route::group('system', function () {
            Route::post('setting/saveSettings', 'Api.System/saveSettings');
            Route::get('setting/agent-levels', 'Api.System/agentLevels');
            Route::get('setting/product-modes', 'Api.System/productModes');
            Route::post('setting/sendTestEmail', 'Api.System/sendTestEmail');
            Route::get('test_log', 'Api.System/testLog');
            Route::post('setting/sendTestSms', 'Api.System/sendTestSms');
            Route::post('setting/sendTestPayment', 'Api.System/sendTestPayment');
            Route::get('menus', 'Api.System/menus');
            
            Route::group('crontab', function() {
                Route::get('list', 'Api.SystemCrontab/list');
                Route::post('save', 'Api.SystemCrontab/save');
                Route::put('update', 'Api.SystemCrontab/update');
                Route::delete('delete', 'Api.SystemCrontab/delete');
                Route::get('logs', 'Api.SystemCrontab/logs');
                Route::post('run', 'Api.SystemCrontab/run');
            });
        });

        Route::group('product_approval', function () {
            Route::get('list', 'Api.ProductApproval/index');
            Route::post('approve', 'Api.ProductApproval/approve');
            Route::post('reject', 'Api.ProductApproval/reject');
        });

        Route::group('admin_notification', function () {
            Route::get('list', 'Api.AdminNotification/list');
            Route::get('read_users', 'Api.AdminNotification/readUsers');
            Route::get('user_list', 'Api.AdminNotification/userList');
            Route::post('save', 'Api.AdminNotification/save');
            Route::post('delete', 'Api.AdminNotification/delete');
            Route::post('read', 'Api.AdminNotification/read');
            Route::post('readAll', 'Api.AdminNotification/readAll');
            Route::post('send', 'Api.AdminNotification/send');
            Route::post('delete_user_notify', 'Api.AdminNotification/deleteUserNotify');
            Route::post('update_user_notify', 'Api.AdminNotification/updateUserNotify');
        });

        Route::group('rate-group', function () {
            Route::get('index', 'Api.RateGroupController/index');
            Route::post('save', 'Api.RateGroupController/save');
            Route::post('delete', 'Api.RateGroupController/delete');
        });

        Route::group('payment', function () {
            Route::group('config', function () {
                Route::post('create-driver', 'Api.PaymentConfigController/createDriver');
                Route::post('update-driver', 'Api.PaymentConfigController/updateDriver');
                Route::post('delete-driver', 'Api.PaymentConfigController/deleteDriver');
                Route::get('index', 'Api.PaymentConfigController/index');
                Route::post('save', 'Api.PaymentConfigController/save');
                Route::post('delete', 'Api.PaymentConfigController/delete');
                Route::post('test', 'Api.PaymentConfigController/test');
                Route::get('complaints', 'Api.PaymentConfigController/complaints');
                Route::get('complaintDetail', 'Api.PaymentConfigController/complaintDetail');
                Route::post('replyComplaint', 'Api.PaymentConfigController/replyComplaint');
                Route::post('finishComplaint', 'Api.PaymentConfigController/finishComplaint');
            });
            
            Route::group('user-config', function () {
                Route::get('admin_index', 'Api.UserPaymentConfigController/adminIndex');
                Route::post('admin_status', 'Api.UserPaymentConfigController/adminUpdateStatus');
                Route::post('admin_delete', 'Api.UserPaymentConfigController/adminDelete');
                Route::post('admin_force_disable', 'Api.UserPaymentConfigController/adminToggleForceDisable');
            });

            Route::group('payment-account', function () {
                Route::get('list', 'Api.PaymentAccountController/list');
                Route::post('save', 'Api.PaymentAccountController/save');
                Route::post('update', 'Api.PaymentAccountController/update');
                Route::post('delete', 'Api.PaymentAccountController/delete');
                Route::post('toggle', 'Api.PaymentAccountController/toggle');
                Route::post('strategy', 'Api.PaymentAccountController/updateStrategy');
            });
        });

        Route::group('buyer_blacklist', function () {
            Route::get('index', 'Api.BuyerBlacklistController/index');
            Route::post('save', 'Api.BuyerBlacklistController/save');
            Route::post('delete', 'Api.BuyerBlacklistController/delete');
        });

        Route::get('dashboard/stats', 'Api.Dashboard/stats');
        Route::get('dashboard/chartData', 'Api.Dashboard/chartData');
        Route::get('dashboard/newUsers', 'Api.Dashboard/newUsers');
        Route::get('dashboard/activities', 'Api.Dashboard/activities');
        Route::get('dashboard/rankings', 'Api.Dashboard/rankings');
        Route::get('dashboard/mapData', 'Api.Dashboard/mapData');
    })->middleware(\app\middleware\RequireAdmin::class);
});

Route::get('debug', 'Debug/index');
Route::get('init', 'Init/index');
