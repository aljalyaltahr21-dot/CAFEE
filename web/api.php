<?php
// ===========================================================
// واجهة برمجة التطبيقات (API) - البوابة السحابية لإدارة المقاهي
// يدعم 3 أدوار: أدمن (Admin)، مدير (Manager)، عميل (Customer)
// مع عزل كامل للبيانات لكل رخصة (Multi-tenant Isolation)
// ===========================================================

session_start();
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'خطأ في السيرفر: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'مكتبة cURL غير مفعلة على الاستضافة. يرجى تفعيلها من لوحة التحكم cPanel -> Select PHP Version -> Extensions.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$config = require __DIR__ . '/firebase.php';
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// قراءة جسم الطلب بصيغة JSON
$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true) ?: [];

// ===== دوال مساعدة =====

function jsonResponse($ok, $data = [], $error = null, $code = 200) {
    http_response_code($code);
    $res = ['ok' => $ok];
    if ($error) $res['error'] = $error;
    foreach ($data as $k => $v) $res[$k] = $v;
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

function getCurrentUser() {
    return $_SESSION['portal_user'] ?? null;
}

function requireAuth() {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(false, [], 'يجب تسجيل الدخول أولاً', 401);
    }
    return $user;
}

function requireRoles($allowedRoles) {
    $user = requireAuth();
    if (!in_array($user['role'], (array)$allowedRoles)) {
        jsonResponse(false, [], 'غير مصرح لك بتنفيذ هذه العملية', 403);
    }
    return $user;
}

// استخراج مفتاح الفرع/الرخصة الخاص بالعميل لضمان العزل التام
function resolveBranchId($user, $requestedBranchId = null) {
    // إذا كان كاستمير: مجبر دائماً وحصرياً على رخصته الخاصة
    if ($user['role'] === 'customer') {
        $key = trim($user['licenseKey'] ?? '');
        return $key ?: 'main';
    }
    // الأدمن والمدير يستطيعون معاينة أي فرع محدد أو الفرع الافتراضي
    return trim($requestedBranchId ?: 'main');
}

// توليد مفتاح ترخيص عشوائي مرتب
function generateLicenseKey() {
    $part1 = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    $part2 = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    return "CAFE-{$part1}-{$part2}";
}

// تهيئة الحسابات الافتراضية إذا لم تكن موجودة
function ensureDefaultAccounts($config) {
    // أدمن افتراضي
    $admin = getUserProfile($config, 'admin@cafepos.com');
    if (!$admin) {
        saveUserProfile($config, 'admin@cafepos.com', [
            'email' => 'admin@cafepos.com',
            'name' => 'المدير العام',
            'role' => 'admin',
            'password' => 'admin123',
            'phone' => '0910000000',
            'createdAt' => time() * 1000,
        ]);
    }
    // مدير تراخيص افتراضي
    $manager = getUserProfile($config, 'manager@cafepos.com');
    if (!$manager) {
        saveUserProfile($config, 'manager@cafepos.com', [
            'email' => 'manager@cafepos.com',
            'name' => 'مدير التراخيص والعمليات',
            'role' => 'manager',
            'password' => 'manager123',
            'phone' => '0920000000',
            'createdAt' => time() * 1000,
        ]);
    }
}

// ===========================================================
// 1. تسجيل الدخول والمصادقة (Auth)
// ===========================================================

if ($action === 'login' && $method === 'POST') {
    ensureDefaultAccounts($config);
    $loginType = $input['type'] ?? 'credentials'; // 'credentials' أو 'license'
    
    // أ) الدخول المباشر بمفتاح الترخيص (مريح جداً لأصحاب المقاهي)
    if ($loginType === 'license') {
        $licenseKey = trim($input['licenseKey'] ?? '');
        if (!$licenseKey) jsonResponse(false, [], 'يرجى كتابة مفتاح الترخيص');
        
        $lic = getLicenseByKey($config, $licenseKey);
        if (!$lic) {
            jsonResponse(false, [], 'مفتاح الترخيص غير مسجل في النظام');
        }
        
        $userSession = [
            'email' => $lic['customerEmail'] ?? ($licenseKey . '@cafe.local'),
            'name' => $lic['customerName'] ?? 'صاحب المقهى',
            'role' => 'customer',
            'licenseKey' => $licenseKey,
            'cafeName' => $lic['cafeName'] ?? ($lic['customerName'] ?? 'المقهى'),
            'phone' => $lic['phone'] ?? '',
        ];
        $_SESSION['portal_user'] = $userSession;
        jsonResponse(true, ['user' => $userSession, 'license' => $lic]);
    }
    
    // ب) الدخول بالإيميل وكلمة المرور
    $email = strtolower(trim($input['email'] ?? ''));
    $password = (string)($input['password'] ?? '');
    
    if (!$email || !$password) {
        jsonResponse(false, [], 'يرجى إدخال البريد الإلكتروني وكلمة المرور');
    }
    
    $profile = getUserProfile($config, $email);
    if (!$profile || ($profile['password'] ?? '') !== $password) {
        jsonResponse(false, [], 'بيانات الدخول غير صحيحة. تحقق من الإيميل وكلمة المرور');
    }
    
    // إذا كان كاستمير، جلب بيانات رخصته
    $licenseData = null;
    if ($profile['role'] === 'customer' && !empty($profile['licenseKey'])) {
        $licenseData = getLicenseByKey($config, $profile['licenseKey']);
    }
    
    $userSession = [
        'email' => $profile['email'],
        'name' => $profile['name'] ?? 'مستخدم',
        'role' => $profile['role'] ?? 'customer',
        'licenseKey' => $profile['licenseKey'] ?? '',
        'cafeName' => $profile['cafeName'] ?? '',
        'phone' => $profile['phone'] ?? '',
    ];
    $_SESSION['portal_user'] = $userSession;
    
    jsonResponse(true, ['user' => $userSession, 'license' => $licenseData]);
}

if ($action === 'logout') {
    $_SESSION['portal_user'] = null;
    unset($_SESSION['portal_user']);
    session_destroy();
    jsonResponse(true, ['message' => 'تم تسجيل الخروج بنجاح']);
}

if ($action === 'me') {
    $user = getCurrentUser();
    if (!$user) jsonResponse(false, [], 'غير مسجل');
    
    $licenseData = null;
    if (!empty($user['licenseKey'])) {
        $licenseData = getLicenseByKey($config, $user['licenseKey']);
    }
    jsonResponse(true, ['user' => $user, 'license' => $licenseData]);
}

// ===========================================================
// 2. إدارة التراخيص (Licenses) - [للأدمن والمدير]
// ===========================================================

if ($action === 'list_licenses') {
    requireRoles(['admin', 'manager']);
    $licenses = getAllLicenses($config);
    jsonResponse(true, ['licenses' => array_values($licenses)]);
}

if ($action === 'save_license' && $method === 'POST') {
    requireRoles(['admin', 'manager']);
    
    $key = trim($input['key'] ?? '');
    $isNew = false;
    if (!$key) {
        $key = generateLicenseKey();
        $isNew = true;
    }
    
    $customerName = trim($input['customerName'] ?? '');
    $customerEmail = strtolower(trim($input['customerEmail'] ?? ''));
    $cafeName = trim($input['cafeName'] ?? $customerName);
    $phone = trim($input['phone'] ?? '');
    $active = isset($input['active']) ? (bool)$input['active'] : true;
    
    // حساب تاريخ الانتهاء
    $durationDays = isset($input['durationDays']) ? (int)$input['durationDays'] : null;
    $expiresAt = null;
    if ($durationDays && $durationDays > 0) {
        $expiresAt = (time() + ($durationDays * 86400)) * 1000;
    } elseif (!empty($input['expiresAt'])) {
        $expiresAt = (int)$input['expiresAt'];
    }
    
    // جلب الرخصة السابقة للحفاظ على ربط الجهاز ما لم يُطلب فك الربط
    $prev = getLicenseByKey($config, $key) ?: [];
    
    $licenseRecord = array_merge($prev, [
        'key' => $key,
        'customerName' => $customerName ?: ($prev['customerName'] ?? 'عميل جديد'),
        'customerEmail' => $customerEmail ?: ($prev['customerEmail'] ?? ''),
        'cafeName' => $cafeName ?: ($prev['cafeName'] ?? ''),
        'phone' => $phone ?: ($prev['phone'] ?? ''),
        'active' => $active,
        'expiresAt' => $expiresAt ?: ($prev['expiresAt'] ?? null),
        'createdAt' => $prev['createdAt'] ?? (time() * 1000),
    ]);
    
    if (!empty($input['resetDevice'])) {
        $licenseRecord['deviceId'] = null;
        $licenseRecord['deviceHash'] = null;
    }
    
    $res = saveLicenseData($config, $key, $licenseRecord);
    if ($res['code'] >= 200 && $res['code'] < 300) {
        // إذا كان هناك إيميل للعميل، نربطه في حسابه تلقائياً
        if ($customerEmail) {
            $userProf = getUserProfile($config, $customerEmail);
            if ($userProf) {
                $userProf['licenseKey'] = $key;
                $userProf['cafeName'] = $cafeName;
                saveUserProfile($config, $customerEmail, $userProf);
            }
        }
        if ($isNew) {
            $existingBranch = getBranchData($config, $key);
            if (!$existingBranch || empty($existingBranch['products'])) {
                saveBranchData($config, $key, [
                    'products' => getDefaultProducts(),
                    'todaySales' => 0,
                    'ordersCount' => 0,
                    'monthSales' => 0,
                    'recentTx' => [],
                    '_branchId' => $key,
                ]);
            }
        }
        jsonResponse(true, ['license' => $licenseRecord, 'isNew' => $isNew]);
    } else {
        jsonResponse(false, [], 'فشل حفظ الترخيص في السحابة', 500);
    }
}

if ($action === 'toggle_license' && $method === 'POST') {
    requireRoles(['admin', 'manager']);
    $key = trim($input['key'] ?? '');
    if (!$key) jsonResponse(false, [], 'مفتاح الترخيص مطلوب');
    
    $lic = getLicenseByKey($config, $key);
    if (!$lic) jsonResponse(false, [], 'الترخيص غير موجود');
    
    if (isset($input['active'])) {
        $lic['active'] = (bool)$input['active'];
    }
    if (!empty($input['resetDevice'])) {
        $lic['deviceId'] = null;
        $lic['deviceHash'] = null;
    }
    
    saveLicenseData($config, $key, $lic);
    jsonResponse(true, ['license' => $lic]);
}

// ===========================================================
// 3. طلبات التراخيص والموافقة (License Requests & Approvals)
// ===========================================================

// أ) الكاستمير يطلب ترخيص أو تجديد
if ($action === 'request_license' && $method === 'POST') {
    $user = requireRoleCustomerOrAny();
    
    $requestedDuration = trim($input['requestedDuration'] ?? '30_days'); // 30_days, 90_days, 180_days, 365_days
    $notes = trim($input['notes'] ?? '');
    $cafeName = trim($input['cafeName'] ?? ($user['cafeName'] ?? 'مقهى'));
    $phone = trim($input['phone'] ?? ($user['phone'] ?? ''));
    
    $reqId = 'req_' . time() . '_' . rand(100, 999);
    $reqData = [
        'id' => $reqId,
        'customerEmail' => $user['email'],
        'customerName' => $user['name'],
        'cafeName' => $cafeName,
        'phone' => $phone,
        'licenseKey' => $user['licenseKey'] ?? '',
        'requestedDuration' => $requestedDuration,
        'notes' => $notes,
        'status' => 'pending', // pending, approved, rejected
        'createdAt' => time() * 1000,
    ];
    
    $res = saveLicenseRequestData($config, $reqId, $reqData);
    if ($res['code'] >= 200 && $res['code'] < 300) {
        jsonResponse(true, ['request' => $reqData, 'message' => 'تم إرسال طلب الترخيص بنجاح، بانتظار موافقة الإدارة']);
    } else {
        jsonResponse(false, [], 'فشل إرسال طلب الترخيص', 500);
    }
}

// ب) استعراض طلبات التراخيص
if ($action === 'list_license_requests') {
    $user = requireAuth();
    $requests = getAllLicenseRequests($config);
    
    // إذا كاستمير، يرى طلباته هو فقط
    if ($user['role'] === 'customer') {
        $myEmail = strtolower($user['email']);
        $requests = array_filter($requests, fn($r) => strtolower($r['customerEmail'] ?? '') === $myEmail);
    }
    
    jsonResponse(true, ['requests' => array_values($requests)]);
}

// ج) موافقة المدير أو الأدمن على طلب الترخيص وتحديد المدة
if ($action === 'approve_license_request' && $method === 'POST') {
    requireRoles(['admin', 'manager']);
    
    $reqId = trim($input['requestId'] ?? '');
    $days = (int)($input['days'] ?? 30);
    $responseNotes = trim($input['responseNotes'] ?? 'تمت الموافقة وتفعيل الترخيص');
    
    if (!$reqId) jsonResponse(false, [], 'معرّف الطلب مطلوب');
    if ($days <= 0) $days = 30;
    
    $requests = getAllLicenseRequests($config);
    $targetReq = $requests[$reqId] ?? null;
    if (!$targetReq) jsonResponse(false, [], 'طلب الترخيص غير موجود');
    
    $licenseKey = trim($targetReq['licenseKey'] ?? '');
    $isNewLicense = false;
    
    // إذا لم يكن عنده رخصة سابقة، ننشئ له رخصة جديدة فوراً
    if (!$licenseKey) {
        $licenseKey = generateLicenseKey();
        $isNewLicense = true;
    }
    
    $existingLic = getLicenseByKey($config, $licenseKey) ?: [];
    
    // احتساب الصلاحية: إذا كانت سارية نمدد فوقها، وإلا نبدأ من اليوم
    $baseTime = time();
    if (!empty($existingLic['expiresAt'])) {
        $curExpSec = (int)($existingLic['expiresAt'] / 1000);
        if ($curExpSec > $baseTime) {
            $baseTime = $curExpSec;
        }
    }
    $newExpiresAt = ($baseTime + ($days * 86400)) * 1000;
    
    $updatedLic = array_merge($existingLic, [
        'key' => $licenseKey,
        'customerName' => $targetReq['customerName'] ?? ($existingLic['customerName'] ?? 'صاحب المقهى'),
        'customerEmail' => $targetReq['customerEmail'] ?? ($existingLic['customerEmail'] ?? ''),
        'cafeName' => $targetReq['cafeName'] ?? ($existingLic['cafeName'] ?? ''),
        'phone' => $targetReq['phone'] ?? ($existingLic['phone'] ?? ''),
        'active' => true,
        'expiresAt' => $newExpiresAt,
        'approvedDays' => $days,
        'createdAt' => $existingLic['createdAt'] ?? (time() * 1000),
    ]);
    
    // 1. حفظ الترخيص المحدث
    saveLicenseData($config, $licenseKey, $updatedLic);
    
    // 2. تحديث حساب المستخدم لربط مفتاح الترخيص الجديد
    if (!empty($targetReq['customerEmail'])) {
        $userProf = getUserProfile($config, $targetReq['customerEmail']);
        if ($userProf) {
            $userProf['licenseKey'] = $licenseKey;
            $userProf['cafeName'] = $targetReq['cafeName'] ?? $userProf['cafeName'];
            saveUserProfile($config, $targetReq['customerEmail'], $userProf);
        }
    }
    
    // 3. تحديث حالة الطلب إلى Approved
    $targetReq['status'] = 'approved';
    $targetReq['licenseKey'] = $licenseKey;
    $targetReq['approvedDays'] = $days;
    $targetReq['expiresAt'] = $newExpiresAt;
    $targetReq['responseNotes'] = $responseNotes;
    $targetReq['resolvedAt'] = time() * 1000;
    saveLicenseRequestData($config, $reqId, $targetReq);
    
    jsonResponse(true, [
        'request' => $targetReq,
        'license' => $updatedLic,
        'message' => "تمت الموافقة بنجاح وتم تفعيل الترخيص لمدة {$days} يوماً للعميل"
    ]);
}

// د) رفض الطلب
if ($action === 'reject_license_request' && $method === 'POST') {
    requireRoles(['admin', 'manager']);
    $reqId = trim($input['requestId'] ?? '');
    $reason = trim($input['reason'] ?? 'تم رفض الطلب');
    
    if (!$reqId) jsonResponse(false, [], 'معرّف الطلب مطلوب');
    
    $requests = getAllLicenseRequests($config);
    $targetReq = $requests[$reqId] ?? null;
    if (!$targetReq) jsonResponse(false, [], 'طلب الترخيص غير موجود');
    
    $targetReq['status'] = 'rejected';
    $targetReq['responseNotes'] = $reason;
    $targetReq['resolvedAt'] = time() * 1000;
    saveLicenseRequestData($config, $reqId, $targetReq);
    
    jsonResponse(true, ['request' => $targetReq]);
}

// ===========================================================
// 4. طلبات التعديلات والدعم الفني (Modification Requests)
// ===========================================================

// أ) الكاستمير يطلب تعديل معين في المقهى أو البرنامج
if ($action === 'request_modification' && $method === 'POST') {
    $user = requireRoleCustomerOrAny();
    $title = trim($input['title'] ?? '');
    $details = trim($input['details'] ?? '');
    
    if (!$title || !$details) {
        jsonResponse(false, [], 'يرجى كتابة عنوان التعديل وتفاصيله');
    }
    
    $modId = 'mod_' . time() . '_' . rand(100, 999);
    $modData = [
        'id' => $modId,
        'title' => $title,
        'details' => $details,
        'customerEmail' => $user['email'],
        'customerName' => $user['name'],
        'cafeName' => $user['cafeName'] ?? 'مقهى',
        'licenseKey' => $user['licenseKey'] ?? '',
        'status' => 'pending', // pending, in_progress, completed, rejected
        'adminReply' => '',
        'createdAt' => time() * 1000,
    ];
    
    $res = saveModificationRequestData($config, $modId, $modData);
    if ($res['code'] >= 200 && $res['code'] < 300) {
        jsonResponse(true, ['modification' => $modData, 'message' => 'تم إرسال طلب التعديل للإدارة بنجاح']);
    } else {
        jsonResponse(false, [], 'فشل إرسال طلب التعديل', 500);
    }
}

// ب) قائمة طلبات التعديلات
if ($action === 'list_modifications') {
    $user = requireAuth();
    $mods = getAllModificationRequests($config);
    
    if ($user['role'] === 'customer') {
        $myEmail = strtolower($user['email']);
        $mods = array_filter($mods, fn($m) => strtolower($m['customerEmail'] ?? '') === $myEmail);
    }
    
    jsonResponse(true, ['modifications' => array_values($mods)]);
}

// ج) تحديث حالة طلب التعديل والرد عليه (أدمن / مدير)
if ($action === 'update_modification' && $method === 'POST') {
    requireRoles(['admin', 'manager']);
    $modId = trim($input['modId'] ?? '');
    $status = trim($input['status'] ?? 'in_progress');
    $adminReply = trim($input['adminReply'] ?? '');
    
    if (!$modId) jsonResponse(false, [], 'معرّف الطلب مطلوب');
    
    $mods = getAllModificationRequests($config);
    $targetMod = $mods[$modId] ?? null;
    if (!$targetMod) jsonResponse(false, [], 'طلب التعديل غير موجود');
    
    $targetMod['status'] = $status;
    if ($adminReply) $targetMod['adminReply'] = $adminReply;
    $targetMod['resolvedAt'] = time() * 1000;
    saveModificationRequestData($config, $modId, $targetMod);
    
    jsonResponse(true, ['modification' => $targetMod, 'message' => 'تم تحديث حالة طلب التعديل بنجاح']);
}

// ===========================================================
// 5. إدارة حسابات وإيميلات العملاء (User Accounts) - [أدمن فقط]
// ===========================================================

if ($action === 'list_users') {
    requireRoles(['admin', 'manager']);
    $users = getAllUsers($config);
    // إخفاء كلمات المرور لزيادة الأمان
    $safeUsers = array_map(function($u) {
        unset($u['password']);
        return $u;
    }, array_values($users));
    jsonResponse(true, ['users' => $safeUsers]);
}

if ($action === 'save_user' && $method === 'POST') {
    requireRoles(['admin']);
    
    $email = strtolower(trim($input['email'] ?? ''));
    $name = trim($input['name'] ?? '');
    $role = trim($input['role'] ?? 'customer');
    $password = (string)($input['password'] ?? '');
    $licenseKey = trim($input['licenseKey'] ?? '');
    $cafeName = trim($input['cafeName'] ?? '');
    $phone = trim($input['phone'] ?? '');
    
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, [], 'بريد إلكتروني غير صالح');
    }
    
    $prev = getUserProfile($config, $email) ?: [];
    
    $record = [
        'email' => $email,
        'name' => $name ?: ($prev['name'] ?? 'مستخدم'),
        'role' => in_array($role, ['admin', 'manager', 'customer']) ? $role : 'customer',
        'password' => $password ?: ($prev['password'] ?? '123456'),
        'licenseKey' => $licenseKey ?: ($prev['licenseKey'] ?? ''),
        'cafeName' => $cafeName ?: ($prev['cafeName'] ?? ''),
        'phone' => $phone ?: ($prev['phone'] ?? ''),
        'createdAt' => $prev['createdAt'] ?? (time() * 1000),
    ];
    
    $res = saveUserProfile($config, $email, $record);
    if ($res['code'] >= 200 && $res['code'] < 300) {
        unset($record['password']);
        jsonResponse(true, ['user' => $record, 'message' => 'تم حفظ حساب العميل بنجاح']);
    } else {
        jsonResponse(false, [], 'فشل حفظ الحساب في السحابة', 500);
    }
}

if ($action === 'delete_user' && $method === 'POST') {
    requireRoles(['admin']);
    $email = strtolower(trim($input['email'] ?? ''));
    if (!$email) jsonResponse(false, [], 'الإيميل مطلوب');
    
    if ($email === 'admin@cafepos.com') {
        jsonResponse(false, [], 'لا يمكن حذف حساب الأدمن الرئيسي');
    }
    
    deleteUserProfile($config, $email);
    jsonResponse(true, ['message' => 'تم حذف الحساب بنجاح']);
}

// ===========================================================
// 6. إدارة مبيعات وأصناف المقهى (Products & Sales)
// عزل تام: الكاستمير يرى ويعدل فقط أصناف رخصته هو فقط!
// ===========================================================

if ($action === 'state' || $action === 'get_branch') {
    $user = requireAuth();
    $targetBranchId = resolveBranchId($user, $_GET['branchId'] ?? null);
    
    $data = getBranchData($config, $targetBranchId);
    if ($data === null) {
        // إذا لم يكن الفرع مسجلاً بعد، ننشئ له قالباً يحتوي على الأصناف الافتراضية
        $data = [
            'products' => getDefaultProducts(),
            'todaySales' => 0,
            'ordersCount' => 0,
            'monthSales' => 0,
            'recentTx' => [],
            '_branchId' => $targetBranchId,
        ];
        saveBranchData($config, $targetBranchId, $data);
    } elseif (empty($data['products'])) {
        // إذا كان مسجلاً بدون أصناف، نزوّده بالأصناف الافتراضية لحماية القائمة
        $data['products'] = getDefaultProducts();
        saveBranchData($config, $targetBranchId, $data);
    }
    
    // جلب معلومات الترخيص المرتبطة بهذا الفرع
    $lic = null;
    if ($targetBranchId && $targetBranchId !== 'main') {
        $lic = getLicenseByKey($config, $targetBranchId);
    } elseif (!empty($user['licenseKey'])) {
        $lic = getLicenseByKey($config, $user['licenseKey']);
    }
    
    jsonResponse(true, [
        'data' => $data,
        'branchId' => $targetBranchId,
        'license' => $lic,
    ]);
}

// إضافة أو تعديل منتج - مع ضمان حفظه في رخصة العميل فقط!
if ($action === 'save_product' && $method === 'POST') {
    $user = requireAuth();
    $targetBranchId = resolveBranchId($user, $input['branchId'] ?? null);
    
    $branchData = getBranchData($config, $targetBranchId);
    if (!$branchData) {
        $branchData = [
            'products' => getDefaultProducts(),
            'todaySales' => 0,
            'ordersCount' => 0,
            'monthSales' => 0,
            'recentTx' => [],
        ];
    } elseif (empty($branchData['products'])) {
        $branchData['products'] = getDefaultProducts();
    }
    
    $products = $branchData['products'] ?? [];
    $isNew = !isset($input['id']) || !$input['id'];
    
    $productData = [
        'name' => trim($input['name'] ?? ''),
        'category' => trim($input['category'] ?? 'قهوة ساخنة'),
        'price' => (float)($input['price'] ?? 0),
        'stock' => (int)($input['stock'] ?? 0),
        'threshold' => (int)($input['threshold'] ?? 10),
        'trackStock' => !empty($input['trackStock']),
    ];
    
    if (!$productData['name']) jsonResponse(false, [], 'اسم الصنف مطلوب');
    if ($productData['price'] < 0) jsonResponse(false, [], 'السعر غير صالح');
    
    if ($isNew) {
        $maxId = 0;
        foreach ($products as $p) {
            if (($p['id'] ?? 0) > $maxId) $maxId = (int)$p['id'];
        }
        $productData['id'] = $maxId + 1;
        $products[] = $productData;
    } else {
        $found = false;
        $productData['id'] = (int)$input['id'];
        foreach ($products as &$p) {
            if ((int)$p['id'] === $productData['id']) {
                $p = $productData;
                $found = true;
                break;
            }
        }
        if (!$found) jsonResponse(false, [], 'المنتج المراد تعديله غير موجود');
    }
    
    $branchData['products'] = $products;
    $res = saveBranchData($config, $targetBranchId, $branchData);
    
    if ($res['code'] >= 200 && $res['code'] < 300) {
        jsonResponse(true, [
            'product' => $productData,
            'isNew' => $isNew,
            'branchId' => $targetBranchId,
            'message' => $isNew ? 'تم إضافة الصنف لرخصتك بنجاح' : 'تم تحديث الصنف والكمية بنجاح'
        ]);
    } else {
        jsonResponse(false, [], 'فشل حفظ الصنف في السحابة', 500);
    }
}

// حذف منتج من رخصة العميل
if ($action === 'delete_product' && $method === 'POST') {
    $user = requireAuth();
    $targetBranchId = resolveBranchId($user, $input['branchId'] ?? null);
    
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(false, [], 'معرّف المنتج غير صالح');
    
    $branchData = getBranchData($config, $targetBranchId);
    if (!$branchData) jsonResponse(false, [], 'بيانات الفرع غير موجودة');
    
    $branchData['products'] = array_values(array_filter($branchData['products'] ?? [], fn($p) => (int)($p['id'] ?? 0) !== $id));
    $res = saveBranchData($config, $targetBranchId, $branchData);
    
    if ($res['code'] >= 200 && $res['code'] < 300) {
        jsonResponse(true, ['message' => 'تم حذف الصنف بنجاح']);
    } else {
        jsonResponse(false, [], 'فشل حذف الصنف', 500);
    }
}

// نظرة عامة على جميع المقاهي (للأدمن والمدير)
if ($action === 'list_all_branches') {
    requireRoles(['admin', 'manager']);
    $branches = firebaseList($config['projectId'], 'cafe_branches');
    $licenses = getAllLicenses($config);
    
    $summary = [];
    foreach ($branches as $branchKey => $bData) {
        $lic = $licenses[$branchKey] ?? null;
        $summary[] = [
            'branchId' => $branchKey,
            'cafeName' => $lic['cafeName'] ?? ($branchKey === 'main' ? 'الفرع الرئيسي الافتراضي' : 'مقهى ' . $branchKey),
            'customerName' => $lic['customerName'] ?? '—',
            'customerEmail' => $lic['customerEmail'] ?? '—',
            'todaySales' => $bData['todaySales'] ?? 0,
            'ordersCount' => $bData['ordersCount'] ?? 0,
            'productsCount' => count($bData['products'] ?? []),
            'licenseActive' => $lic ? (bool)($lic['active'] ?? false) : false,
            'licenseExpiresAt' => $lic['expiresAt'] ?? null,
            'updatedAt' => $bData['updatedAt'] ?? null,
        ];
    }
    jsonResponse(true, ['branches' => $summary]);
}

// التحقق من تحديثات تطبيق سامسونج وأندرويد (Mobile / Samsung Update API)
if ($action === 'check_mobile_update') {
    $clientVersion = $_GET['version'] ?? '1.0.0';
    $latestVersion = '1.0.0';
    $hasUpdate = version_compare($latestVersion, $clientVersion, '>');

    jsonResponse(true, [
        'latestVersion' => $latestVersion,
        'hasUpdate' => $hasUpdate,
        'downloadUrl' => 'https://github.com/aljalyaltahr21-dot/CAFEE/releases/download/flutter-apk-latest/app-release.apk',
        'localDownloadUrl' => 'download.php?format=apk',
        'releaseNotes' => 'إصدار نسخة سامسونج وأندرويد الرسمية (Flutter POS) مع التحديثات التلقائية المباشرة.',
        'updatedAt' => date('Y-m-d H:i:s')
    ]);
}

function requireRoleCustomerOrAny() {
    return requireAuth();
}

jsonResponse(false, [], "طلب غير معروف أو غير مدعوم: {$action}", 404);
