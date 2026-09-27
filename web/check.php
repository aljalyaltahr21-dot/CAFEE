<?php
header('Content-Type: text/html; charset=utf-8');

$tests = [];

// 1. فحص إصدار PHP
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$tests[] = [
    'title' => 'إصدار PHP على السيرفر',
    'status' => $phpOk,
    'value' => 'PHP ' . $phpVersion,
    'detail' => $phpOk ? 'الإصدار متوافق تماماً ✅' : 'يُفضل ترقية PHP إلى 8.0 أو أحدث من لوحة تحكم الاستضافة (cPanel/PHP Version).'
];

// 2. فحص إضافة cURL
$curlLoaded = extension_loaded('curl') && function_exists('curl_init');
$tests[] = [
    'title' => 'مكتبة الاتصال الخارجي (cURL)',
    'status' => $curlLoaded,
    'value' => $curlLoaded ? 'مفعلة (Enabled)' : 'غير مفعلة (Disabled)',
    'detail' => $curlLoaded ? 'الاتصال السحابي مدعوم بالكامل ✅' : 'يرجى تفعيل إضافة curl من إعدادات PHP Extensions في لوحة الاستضافة.'
];

// 3. فحص مكتبة JSON
$jsonOk = function_exists('json_encode') && function_exists('json_decode');
$tests[] = [
    'title' => 'معالجة بيانات JSON',
    'status' => $jsonOk,
    'value' => $jsonOk ? 'مفعلة' : 'غير متوفرة',
    'detail' => $jsonOk ? 'جاهزة ✅' : 'مطلوبة لمعالجة ردود السحابة.'
];

// 4. فحص الجلسات (Sessions)
@session_start();
$_SESSION['test_check'] = time();
$sessionOk = isset($_SESSION['test_check']);
$tests[] = [
    'title' => 'نظام الجلسات وحفظ الدخول (Sessions)',
    'status' => $sessionOk,
    'value' => $sessionOk ? 'يعمل بنجاح' : 'معطّل أو مجلد /tmp ممتلئ',
    'detail' => $sessionOk ? 'تسجيل الدخول سيعمل بشكل طبيعي ✅' : 'تحقق من صلاحيات مجلد session.save_path على الاستضافة.'
];

// 5. فحص الاتصال بسحابة Firebase (Firestore API)
$firebaseOk = false;
$firebaseHttpCode = 0;
$firebaseError = '';

if ($curlLoaded) {
    $url = "https://firestore.googleapis.com/v1/projects/cafe-pos-49008/databases/(default)/documents/cafe_branches/main";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $res = curl_exec($ch);
    $firebaseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($firebaseHttpCode === 200) {
        $firebaseOk = true;
        $firebaseDetail = "تم الاتصال بنجاح وقراءة بيانات السحابة (HTTP 200) ✅";
    } elseif ($firebaseHttpCode > 0) {
        $firebaseOk = true;
        $firebaseDetail = "السيرفر قادر على الوصول لجوجل كلاود (كود الاستجابة: {$firebaseHttpCode})";
    } else {
        $firebaseDetail = "فشل الاتصال: " . ($curlErr ?: "قد تكون الاستضافة تحظر الاتصالات الخارجية (Outbound Connections) عبر منفذ 443.");
    }
} else {
    $firebaseDetail = "لا يمكن إجراء الفحص لتعطيل مكتبة cURL.";
}

$tests[] = [
    'title' => 'الاتصال بسحابة Firebase Firestore',
    'status' => $firebaseOk,
    'value' => $firebaseHttpCode ? "كود: {$firebaseHttpCode}" : "فشل الاتصال",
    'detail' => $firebaseDetail
];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فحص بيئة الاستضافة ☕ Cafe POS</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin:0; padding:0; font-family: 'Tajawal', sans-serif; }
  body { background: #F8FAFC; color: #0F172A; padding: 40px 16px; display: flex; justify-content: center; }
  .card { background: #fff; max-width: 650px; width: 100%; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 32px; border: 1px solid #E2E8F0; }
  h1 { font-size: 22px; font-weight: 800; color: #1D4ED8; margin-bottom: 6px; }
  p.sub { font-size: 13.5px; color: #64748B; margin-bottom: 24px; }
  .test-row { display: flex; align-items: flex-start; justify-content: space-between; padding: 14px; border-radius: 12px; margin-bottom: 10px; background: #F8FAFC; border: 1px solid #E2E8F0; }
  .test-row.ok { border-color: #86EFAC; background: #F0FDF4; }
  .test-row.fail { border-color: #FECACA; background: #FEF2F2; }
  .test-title { font-weight: 700; font-size: 14px; margin-bottom: 3px; }
  .test-detail { font-size: 12px; color: #64748B; }
  .badge { padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 800; }
  .badge.ok { background: #DCFCE7; color: #166534; }
  .badge.fail { background: #FEE2E2; color: #991B1B; }
  .btn { display: inline-block; margin-top: 20px; background: #1D4ED8; color: #fff; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; }
</style>
</head>
<body>
<div class="card">
  <h1>🔍 فحص توافق الاستضافة مع النظام</h1>
  <p class="sub">صفحة تشخيص لمعرفة سبب أي مشكلة في تسجيل الدخول أو الاتصال بالسحابة.</p>

  <?php foreach ($tests as $t): ?>
    <div class="test-row <?= $t['status'] ? 'ok' : 'fail' ?>">
      <div>
        <div class="test-title"><?= $t['status'] ? '✅' : '❌' ?> <?= htmlspecialchars($t['title']) ?></div>
        <div class="test-detail"><?= htmlspecialchars($t['detail']) ?></div>
      </div>
      <div>
        <span class="badge <?= $t['status'] ? 'ok' : 'fail' ?>"><?= htmlspecialchars($t['value']) ?></span>
      </div>
    </div>
  <?php endforeach; ?>

  <div style="text-align:center;">
    <a href="index.php" class="btn">الرجوع لصفحة تسجيل الدخول ←</a>
  </div>
</div>
</body>
</html>
