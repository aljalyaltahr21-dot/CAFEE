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

if ($format === 'apk') {
    $apkFile = $downloadsDir . '/app-release.apk';
    if (file_exists($apkFile)) {
        $targetFile = $apkFile;
        $filename = 'CafePOS-Flutter-Android.apk';
        $contentType = 'application/vnd.android.package-archive';
    } else {
        // إعادة التوجيه للتحميل المباشر من GitHub Releases
        header("Location: https://github.com/aljalyaltahr21-dot/CAFEE/releases/download/flutter-apk-latest/app-release.apk");
        exit;
    }
} elseif ($format === 'zip') {
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

// في حال لم يتم رفع ملف البرنامج بعد داخل مجلد downloads على الاستضافة:
// يتم التنزيل مباشرة وبأعلى سرعة من سيرفر GitHub الرسمي المعتمد
$githubDirectUrl = 'https://github.com/aljalyaltahr21-dot/CAFEE/releases/download/v1.0.3/CafePOS-Setup-1.0.3.exe';
header('Location: ' . $githubDirectUrl);
exit;

