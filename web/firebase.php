<?php
// ===========================================================
// إعدادات ودوال الربط السحابي مع Firebase Firestore REST API
// تدعم إدارة التراخيص، حسابات العملاء، طلبات التجديد، والمزامنة
// ===========================================================

$FIREBASE_CONFIG = [
    'apiKey'     => 'AIzaSyBlk_6OFKNwVoQnlZwU2halQ2xc354v5u8',
    'projectId'  => 'cafe-pos-49008',
    'branchId'   => 'main',
];

if (!defined('CAFE_FIREBASE_FUNCTIONS_DEFINED')) {
    define('CAFE_FIREBASE_FUNCTIONS_DEFINED', true);

// ===== دوال Firebase REST API الأساسية =====

function firebaseGet($projectId, $path) {
    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$path}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) return null;
    return json_decode($response, true);
}

function firebaseList($projectId, $collection, $pageSize = 200) {
    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}?pageSize={$pageSize}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) return [];
    $data = json_decode($response, true);
    if (!isset($data['documents']) || !is_array($data['documents'])) return [];

    $list = [];
    foreach ($data['documents'] as $doc) {
        $nameParts = explode('/', $doc['name']);
        $docId = end($nameParts);
        $fields = isset($doc['fields']) ? parseFirestoreDoc($doc['fields']) : [];
        $fields['_id'] = $docId;
        $fields['_createTime'] = $doc['createTime'] ?? null;
        $fields['_updateTime'] = $doc['updateTime'] ?? null;
        $list[$docId] = $fields;
    }
    return $list;
}

function firebaseSet($projectId, $path, $data, $method = 'PATCH') {
    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$path}";
    $body = json_encode($data);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

function firebaseDelete($projectId, $path) {
    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$path}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode >= 200 && $httpCode < 300;
}

// ===== تحويل Firestore format لـ PHP array =====
function parseFirestoreDoc($fields) {
    $result = [];
    foreach ($fields as $key => $value) {
        $result[$key] = parseFirestoreValue($value);
    }
    return $result;
}

function parseFirestoreValue($value) {
    if (!is_array($value)) return $value;
    if (isset($value['stringValue']))  return $value['stringValue'];
    if (isset($value['integerValue'])) return (int)$value['integerValue'];
    if (isset($value['doubleValue']))  return (float)$value['doubleValue'];
    if (isset($value['booleanValue'])) return (bool)$value['booleanValue'];
    if (isset($value['nullValue']))    return null;
    if (isset($value['timestampValue'])) return $value['timestampValue'];
    if (isset($value['arrayValue'])) {
        $arr = [];
        foreach (($value['arrayValue']['values'] ?? []) as $item) {
            $arr[] = parseFirestoreValue($item);
        }
        return $arr;
    }
    if (isset($value['mapValue'])) {
        return parseFirestoreDoc($value['mapValue']['fields'] ?? []);
    }
    return null;
}

// ===== تحويل PHP value لـ Firestore format =====
function toFirestoreValue($val) {
    if (is_null($val))   return ['nullValue' => null];
    if (is_bool($val))   return ['booleanValue' => $val];
    if (is_int($val))    return ['integerValue' => (string)$val];
    if (is_float($val))  return ['doubleValue' => $val];
    if (is_string($val)) return ['stringValue' => $val];
    if (is_array($val) && (empty($val) || array_keys($val) === range(0, count($val)-1))) {
        return ['arrayValue' => empty($val) ? new stdClass() : ['values' => array_map('toFirestoreValue', $val)]];
    }
    if (is_array($val)) {
        $fields = [];
        foreach ($val as $k => $v) $fields[$k] = toFirestoreValue($v);
        return ['mapValue' => ['fields' => (object)$fields]];
    }
    return ['stringValue' => (string)$val];
}

function toFirestoreFields($arr) {
    $fields = [];
    foreach ($arr as $k => $v) {
        // لا نحفظ الحقول الداخلية التي تبدأ بـ _
        if (isset($k[0]) && $k[0] === '_') continue;
        $fields[$k] = toFirestoreValue($v);
    }
    return $fields;
}

// ===== دوال مخصصة للتراخيص، الفروع، والمستخدمين =====

function getDefaultProducts() {
    return [
        ['id' => 1, 'name' => 'اسبريسو', 'category' => 'قهوة ساخنة', 'price' => 12.0, 'stock' => 0, 'threshold' => 0, 'trackStock' => false],
        ['id' => 2, 'name' => 'كابتشينو', 'category' => 'قهوة ساخنة', 'price' => 18.0, 'stock' => 0, 'threshold' => 0, 'trackStock' => false],
        ['id' => 3, 'name' => 'لاتيه', 'category' => 'قهوة ساخنة', 'price' => 20.0, 'stock' => 0, 'threshold' => 0, 'trackStock' => false],
        ['id' => 4, 'name' => 'أمريكانو', 'category' => 'قهوة ساخنة', 'price' => 14.0, 'stock' => 0, 'threshold' => 0, 'trackStock' => false],
        ['id' => 5, 'name' => 'آيس لاتيه', 'category' => 'قهوة باردة', 'price' => 22.0, 'stock' => 0, 'threshold' => 0, 'trackStock' => false],
        ['id' => 6, 'name' => 'كولد برو', 'category' => 'قهوة باردة', 'price' => 20.0, 'stock' => 0, 'threshold' => 0, 'trackStock' => false],
        ['id' => 7, 'name' => 'فرابيه', 'category' => 'قهوة باردة', 'price' => 24.0, 'stock' => 0, 'threshold' => 0, 'trackStock' => false],
        ['id' => 8, 'name' => 'عصير برتقال', 'category' => 'مشروبات', 'price' => 15.0, 'stock' => 0, 'threshold' => 0, 'trackStock' => false],
        ['id' => 9, 'name' => 'شاي أحمر', 'category' => 'مشروبات', 'price' => 10.0, 'stock' => 0, 'threshold' => 0, 'trackStock' => false],
        ['id' => 10, 'name' => 'تشيز كيك', 'category' => 'حلويات', 'price' => 25.0, 'stock' => 4, 'threshold' => 8, 'trackStock' => true],
        ['id' => 11, 'name' => 'كوكيز', 'category' => 'حلويات', 'price' => 8.0, 'stock' => 60, 'threshold' => 15, 'trackStock' => true],
        ['id' => 12, 'name' => 'كروسان', 'category' => 'حلويات', 'price' => 10.0, 'stock' => 5, 'threshold' => 10, 'trackStock' => true],
    ];
}

// 1. بيانات الفرع (أصناف ومبيعات فرع معين برقم الرخصة)
function getBranchData($config, $branchId = null) {
    $bId = $branchId ?: ($config['branchId'] ?? 'main');
    $data = firebaseGet($config['projectId'], "cafe_branches/{$bId}");
    if (!$data || !isset($data['fields'])) return null;
    $parsed = parseFirestoreDoc($data['fields']);
    $parsed['_branchId'] = $bId;
    return $parsed;
}

function saveBranchData($config, $branchId, $branchData) {
    $bId = $branchId ?: ($config['branchId'] ?? 'main');
    $fields = toFirestoreFields(array_merge($branchData, [
        'updatedAt' => (int)(microtime(true) * 1000)
    ]));
    return firebaseSet($config['projectId'], "cafe_branches/{$bId}", ['fields' => $fields], 'PATCH');
}

// 2. التراخيص (Licenses)
function getAllLicenses($config) {
    return firebaseList($config['projectId'], 'licenses');
}

function getLicenseByKey($config, $key) {
    $cleanKey = trim((string)$key);
    if (!$cleanKey) return null;
    $data = firebaseGet($config['projectId'], "licenses/{$cleanKey}");
    if (!$data || !isset($data['fields'])) return null;
    $parsed = parseFirestoreDoc($data['fields']);
    $parsed['key'] = $cleanKey;
    return $parsed;
}

function saveLicenseData($config, $key, $data) {
    $cleanKey = trim((string)$key);
    $fields = toFirestoreFields(array_merge($data, [
        'key' => $cleanKey,
        'updatedAt' => (int)(microtime(true) * 1000)
    ]));
    return firebaseSet($config['projectId'], "licenses/{$cleanKey}", ['fields' => $fields], 'PATCH');
}

// 3. طلبات التراخيص (License Requests)
function getAllLicenseRequests($config) {
    $requests = firebaseList($config['projectId'], 'license_requests');
    uasort($requests, fn($a, $b) => ($b['createdAt'] ?? 0) <=> ($a['createdAt'] ?? 0));
    return $requests;
}

function saveLicenseRequestData($config, $id, $data) {
    $fields = toFirestoreFields(array_merge($data, [
        'id' => $id,
        'updatedAt' => (int)(microtime(true) * 1000)
    ]));
    return firebaseSet($config['projectId'], "license_requests/{$id}", ['fields' => $fields], 'PATCH');
}

// 4. طلبات التعديلات والدعم (Modification Requests)
function getAllModificationRequests($config) {
    $mods = firebaseList($config['projectId'], 'modification_requests');
    uasort($mods, fn($a, $b) => ($b['createdAt'] ?? 0) <=> ($a['createdAt'] ?? 0));
    return $mods;
}

function saveModificationRequestData($config, $id, $data) {
    $fields = toFirestoreFields(array_merge($data, [
        'id' => $id,
        'updatedAt' => (int)(microtime(true) * 1000)
    ]));
    return firebaseSet($config['projectId'], "modification_requests/{$id}", ['fields' => $fields], 'PATCH');
}

// 5. حسابات المستخدمين (Users)
function getAllUsers($config) {
    return firebaseList($config['projectId'], 'portal_users');
}

function getUserProfile($config, $email) {
    $safeId = strtolower(str_replace(['@', '.', '+', '-', ' '], '_', trim((string)$email)));
    $data = firebaseGet($config['projectId'], "portal_users/{$safeId}");
    if (!$data || !isset($data['fields'])) return null;
    return parseFirestoreDoc($data['fields']);
}

function saveUserProfile($config, $email, $data) {
    $safeId = strtolower(str_replace(['@', '.', '+', '-', ' '], '_', trim((string)$email)));
    $fields = toFirestoreFields(array_merge($data, [
        'email' => trim($email),
        'updatedAt' => (int)(microtime(true) * 1000)
    ]));
    return firebaseSet($config['projectId'], "portal_users/{$safeId}", ['fields' => $fields], 'PATCH');
}

function deleteUserProfile($config, $email) {
    $safeId = strtolower(str_replace(['@', '.', '+', '-', ' '], '_', trim((string)$email)));
    return firebaseDelete($config['projectId'], "portal_users/{$safeId}");
}

} // end if (!defined('CAFE_FIREBASE_FUNCTIONS_DEFINED'))

return $FIREBASE_CONFIG;

