<?php
session_start();
$config = require __DIR__ . '/firebase.php';

// إذا كان المستخدم مسجل دخوله مسبقاً، تحويله مباشرة للوحة التحكم
if (isset($_SESSION['portal_user']) && !empty($_SESSION['portal_user'])) {
    header("Location: dashboard.php");
    exit;
}

$loginError = '';
$infoMessage = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $infoMessage = 'يرجى تسجيل الدخول أولاً للوصول إلى نظام الكاشير.';
    } elseif ($_GET['error'] === 'login_required') {
        $infoMessage = 'يرجى تسجيل الدخول أولاً لتنزيل برامج وتطبيقات الكاشير.';
    }
}

// معالجة تسجيل الدخول المباشر (Standard Form POST) لضمان العمل 100% على كافة الاستضافات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = $_POST['login_type'] ?? 'credentials';
    if ($loginType === 'credentials') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        
        // 1. حساب الأدمن الافتراضي
        if ($email === 'admin@cafepos.com' && $password === 'admin123') {
            $_SESSION['portal_user'] = [
                'email' => $email,
                'name' => 'مدير النظام',
                'role' => 'admin',
                'branchId' => 'main'
            ];
            header("Location: dashboard.php");
            exit;
        }
        
        // 2. فحص الحسابات المسجلة في السيرفر
        $profile = getUserProfile($config, $email);
        if ($profile && ($profile['password'] ?? '') === $password && !empty($profile['active'])) {
            $_SESSION['portal_user'] = [
                'email' => $email,
                'name' => $profile['name'] ?? $email,
                'role' => $profile['role'] ?? 'customer',
                'licenseKey' => $profile['licenseKey'] ?? null,
                'branchId' => $profile['branchId'] ?? 'main'
            ];
            header("Location: dashboard.php");
            exit;
        } else {
            $loginError = 'بيانات الدخول غير صحيحة أو الحساب معطل.';
        }
    } elseif ($loginType === 'license') {
        $licenseKey = strtoupper(trim($_POST['license_key'] ?? ''));
        $lic = getLicenseByKey($config, $licenseKey);
        if ($lic && !empty($lic['active'])) {
            $_SESSION['portal_user'] = [
                'email' => $lic['customerEmail'] ?? ($licenseKey . '@cafe.license'),
                'name' => $lic['customerName'] ?? $lic['cafeName'] ?? 'صاحب المقهى',
                'role' => 'customer',
                'licenseKey' => $licenseKey,
                'cafeName' => $lic['cafeName'] ?? 'كافيه',
                'branchId' => $licenseKey
            ];
            header("Location: dashboard.php");
            exit;
        } else {
            $loginError = 'مفتاح الترخيص غير صالح أو تم إيقافه من قبل الإدارة.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>بوابة إدارة المقاهي والتراخيص السحابية ☕</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Pacifico&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, sans-serif; }
  
  :root {
    --primary: #63262E;
    --primary-hover: #4E1D24;
    --primary-light: #FDF2F0;
    --primary-border: #E8D4D2;
    --navy: #231815;
    --navy-light: #382522;
    --slate: #7E706D;
    --border: #EDE5E2;
    --bg: #F8F5F2;
    --white: #FFFFFF;
    --green: #10B981;
    --green-light: #ECFDF5;
    --red: #EF4444;
    --red-light: #FEE2E2;
    --gold: #D97706;
    --gold-light: #FEF3C7;
    --shadow-card: 0 20px 50px -5px rgba(99, 38, 46, 0.2), 0 4px 16px -2px rgba(35, 24, 21, 0.08);
  }

  body {
    background-color: #8C4334;
    background-image: 
      radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 20%),
      radial-gradient(circle at 90% 80%, rgba(0, 0, 0, 0.15) 0%, transparent 35%),
      radial-gradient(circle at 50% 50%, #6E3226 0%, #4D1D16 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
    color: var(--navy);
  }

  .login-card {
    background: var(--white);
    border-radius: 24px;
    border: 1.5px solid var(--border);
    box-shadow: var(--shadow-card);
    width: 100%;
    max-width: 440px;
    padding: 36px 30px;
    position: relative;
    overflow: hidden;
  }

  .login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(90deg, #63262E 0%, #8C4334 50%, #D97706 100%);
  }

  .brand-header {
    text-align: center;
    margin-bottom: 24px;
  }

  .brand-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    background: linear-gradient(135deg, #63262E, #8C4334);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    box-shadow: 0 8px 20px rgba(99, 38, 46, 0.28);
    margin-bottom: 12px;
  }

  .brand-title {
    font-family: 'Pacifico', cursive;
    font-size: 30px;
    font-weight: 700;
    color: var(--primary);
    line-height: 1.2;
  }

  .brand-sub {
    font-size: 13px;
    color: var(--slate);
    margin-top: 5px;
    font-weight: 700;
  }

  /* تبويبات طرق الدخول */
  .auth-tabs {
    display: flex;
    background: #F1F5F9;
    padding: 4px;
    border-radius: 14px;
    margin-bottom: 22px;
    gap: 4px;
  }

  .auth-tab {
    flex: 1;
    padding: 10px 12px;
    border-radius: 10px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 700;
    color: var(--slate);
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
  }

  .auth-tab.active {
    background: var(--white);
    color: var(--primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }

  .form-group {
    margin-bottom: 16px;
    text-align: right;
  }

  .form-label {
    display: block;
    font-size: 13px;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 6px;
  }

  .input-wrap {
    position: relative;
  }

  .input-control {
    width: 100%;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1.5px solid var(--border);
    font-size: 14px;
    font-weight: 600;
    color: var(--navy);
    background: #F8FAFC;
    transition: all 0.2s;
    outline: none;
  }

  .input-control:focus {
    background: #FFFFFF;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 38, 46, 0.12);
  }

  .btn-submit {
    width: 100%;
    padding: 13px;
    background: var(--primary);
    color: #FFFFFF;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 14px rgba(99, 38, 46, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 8px;
  }

  .btn-submit:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(99, 38, 46, 0.45);
  }

  .alert-banner {
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 12.5px;
    font-weight: 700;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .alert-danger {
    background: #FEF2F2;
    color: #DC2626;
    border: 1px solid #FECACA;
  }
  .alert-info {
    background: #EFF6FF;
    color: #1D4ED8;
    border: 1px solid #BFDBFE;
  }

  .footer-note {
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px dashed var(--border);
    text-align: center;
    font-size: 11.5px;
    color: var(--slate);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }
</style>
</head>
<body>

<div class="login-card">
  <div class="brand-header">
    <div class="brand-icon">☕</div>
    <h1 class="brand-title">كافيه دي بوينت</h1>
    <p class="brand-sub">بوابة إدارة المقاهي والكاشير السحابي</p>
  </div>

  <?php if ($loginError): ?>
    <div class="alert-banner alert-danger">
      <span>⚠️</span>
      <span><?= htmlspecialchars($loginError) ?></span>
    </div>
  <?php elseif ($infoMessage): ?>
    <div class="alert-banner alert-info">
      <span>🔒</span>
      <span><?= htmlspecialchars($infoMessage) ?></span>
    </div>
  <?php endif; ?>

  <div class="auth-tabs">
    <button type="button" class="auth-tab active" id="tabCreds" onclick="switchTab('creds')">البريد الإلكتروني</button>
    <button type="button" class="auth-tab" id="tabLicense" onclick="switchTab('license')">مفتاح الترخيص (اللايسنس)</button>
  </div>

  <!-- نموذج الدخول بالبريد الإلكتروني -->
  <form id="credsForm" method="POST" action="index.php">
    <input type="hidden" name="login_type" value="credentials" />
    <div class="form-group">
      <label class="form-label">البريد الإلكتروني</label>
      <div class="input-wrap">
        <input type="email" name="email" id="emailInput" class="input-control" placeholder="admin@cafepos.com" required dir="ltr" />
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">كلمة المرور</label>
      <div class="input-wrap">
        <input type="password" name="password" id="passwordInput" class="input-control" placeholder="••••••••" required dir="ltr" />
      </div>
    </div>

    <button type="submit" class="btn-submit" id="credsBtn">
      <span>تسجيل الدخول للنظام</span>
      <span>←</span>
    </button>
  </form>

  <!-- نموذج الدخول بمفتاح الترخيص مباشرة -->
  <form id="licenseForm" method="POST" action="index.php" style="display:none;">
    <input type="hidden" name="login_type" value="license" />
    <div class="form-group">
      <label class="form-label">مفتاح الترخيص (License Key)</label>
      <div class="input-wrap">
        <input type="text" name="license_key" id="licenseKeyInput" class="input-control" placeholder="مثلاً: CAFE-F313-C465" required dir="ltr" style="letter-spacing:1px;font-weight:800;text-transform:uppercase;" />
      </div>
      <div style="font-size:11px;color:var(--slate);margin-top:6px;">
        💡 خاص بأصحاب المقاهي المعتمدين للدخول المباشر لإدارة المبيعات والأصناف.
      </div>
    </div>

    <button type="submit" class="btn-submit" id="licenseBtn">
      <span>دخول بمفتاح الترخيص</span>
      <span>←</span>
    </button>
  </form>

  <div class="footer-note">
    <span>🔒</span>
    <span>نظام محمي ومشفر • يمنع الدخول إلا بحساب أو ترخيص مصرح به.</span>
  </div>
</div>

<script>
function switchTab(tab) {
  const tabCreds = document.getElementById('tabCreds');
  const tabLicense = document.getElementById('tabLicense');
  const credsForm = document.getElementById('credsForm');
  const licenseForm = document.getElementById('licenseForm');

  if (tab === 'creds') {
    tabCreds.classList.add('active');
    tabLicense.classList.remove('active');
    credsForm.style.display = 'block';
    licenseForm.style.display = 'none';
  } else {
    tabCreds.classList.remove('active');
    tabLicense.classList.add('active');
    credsForm.style.display = 'none';
    licenseForm.style.display = 'block';
  }
}
</script>

</body>
</html>
