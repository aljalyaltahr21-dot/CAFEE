<?php
// =======================================================
// صفحة تنزيل برنامج الكاشير المكتبي (Desktop POS)
// تدعم التنزيل المباشر لمعالج التثبيت (.exe) أو الملف المضغوط (.zip)
// مع تقنية الـ Chunked Streaming لتفادي استهلاك ذاكرة السيرفر
// =======================================================

$format = strtolower($_GET['format'] ?? 'exe'); // 'exe' أو 'zip'

$downloadsDir = __DIR__ . '/downloads';
$exeFile = $downloadsDir . '/CafePOS-Setup.exe';

// البحث عن أحدث ملف مضغوط
$zipFiles = glob($downloadsDir . '/*.zip');
$latestZip = null;
if (!empty($zipFiles)) {
    usort($zipFiles, function($a, $b) { return filemtime($b) - filemtime($a); });
    $latestZip = $zipFiles[0];
}

// تحديد الملف المطلوب
$targetFile = null;
$filename = '';
$contentType = 'application/octet-stream';

if ($format === 'zip') {
    if ($latestZip && file_exists($latestZip)) {
        $targetFile = $latestZip;
        $filename = basename($latestZip);
        $contentType = 'application/zip';
    } elseif (file_exists($exeFile)) {
        $targetFile = $exeFile;
        $filename = 'CafePOS-Setup.exe';
        $contentType = 'application/octet-stream';
    }
} else {
    // الوضع الافتراضي: تحميل معالج التثبيت التنفيذي المباشر .exe
    if (file_exists($exeFile)) {
        $targetFile = $exeFile;
        $filename = 'CafePOS-Setup.exe';
        $contentType = 'application/octet-stream';
    } elseif ($latestZip && file_exists($latestZip)) {
        $targetFile = $latestZip;
        $filename = basename($latestZip);
        $contentType = 'application/zip';
    }
}

// إذا كان الملف موجوداً على السيرفر، يتم تنزيله فوراً
if ($targetFile && file_exists($targetFile)) {
    // تنظيف ذاكرة التخزين المؤقت لـ PHP
    while (ob_get_level()) {
        ob_end_clean();
    }
    set_time_limit(0);

    $filesize = filesize($targetFile);
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . $filesize);

    // قراءة الملف وتمريره كأجزاء لتفادي استهلاك الذاكرة
    $handle = fopen($targetFile, 'rb');
    if ($handle !== false) {
        while (!feof($handle)) {
            echo fread($handle, 1024 * 1024); // 1MB chunk
            flush();
        }
        fclose($handle);
    }
    exit;
}

// في حال لم يتم رفع ملف البرنامج بعد داخل مجلد downloads على الاستضافة
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تنزيل برنامج الكاشير المكتبي ☕</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@500;700;800;900&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin:0; padding:0; font-family:'Tajawal',sans-serif; }
  body {
    background: radial-gradient(circle at 10% 20%, #EFF6FF 0%, #F8FAFC 50%, #DBEAFE 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
    color: #0F172A;
  }
  .card {
    background: #fff;
    border-radius: 24px;
    border: 1.5px solid #E2E8F0;
    box-shadow: 0 12px 35px -5px rgba(29, 78, 216, 0.12);
    width: 100%;
    max-width: 500px;
    padding: 36px 28px;
    text-align: center;
  }
  .icon {
    width: 64px;
    height: 64px;
    border-radius: 20px;
    background: linear-gradient(135deg, #1D4ED8, #3B82F6);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-bottom: 16px;
    box-shadow: 0 8px 20px rgba(29,78,216,0.25);
  }
  h1 { font-size: 22px; font-weight: 900; margin-bottom: 8px; }
  p { font-size: 13.5px; color: #64748B; line-height: 1.6; margin-bottom: 20px; }
  .box {
    background: #EFF6FF;
    border: 1.5px dashed #BFDBFE;
    border-radius: 14px;
    padding: 16px;
    font-size: 13px;
    color: #1E40AF;
    text-align: right;
    margin-bottom: 24px;
    line-height: 1.7;
  }
  .btn {
    display: inline-block;
    padding: 12px 24px;
    background: #1D4ED8;
    color: #fff;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 800;
    font-size: 14px;
    transition: 0.2s;
  }
  .btn:hover { background: #1E40AF; }
</style>
</head>
<body>
<div class="card">
  <div class="icon">📦</div>
  <h1>تنزيل برنامج إدارة المقهى</h1>
  <p>ملف تثبيت البرنامج غير مرفوع حالياً داخل مجلد <code>downloads/</code> على الاستضافة.</p>
  
  <div class="box">
    <b>💡 تنبيه مهم لمدير النظام:</b><br>
    يرجى رفع ملف <code>CafePOS-Setup.exe</code> داخل مجلد <code>web/downloads/</code> على الاستضافة عبر cPanel File Manager أو FTP ليتمكن عملاؤك من التنزيل بنقرة واحدة مباشرة.
  </div>

  <a href="index.php" class="btn">العودة للبوابة السحابية ←</a>
</div>
</body>
</html>
