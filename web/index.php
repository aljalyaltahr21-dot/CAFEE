<?php
session_start();
// إذا كان المستخدم مسجل دخوله مسبقاً، تحويله مباشرة للوحة التحكم
if (isset($_SESSION['portal_user']) && !empty($_SESSION['portal_user'])) {
    header("Location: dashboard.php");
    exit;
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
    max-width: 460px;
    padding: 38px 32px;
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
    margin-bottom: 26px;
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
    font-size: 32px;
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
    margin-bottom: 24px;
    gap: 4px;
  }

  .auth-tab {
    flex: 1;
    padding: 10px 12px;
    border-radius: 10px;
    border: none;
    background: transparent;
    font-size: 13.5px;
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
    margin-bottom: 18px;
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
    box-shadow: 0 0 0 3.5px rgba(29, 78, 216, 0.12);
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
    box-shadow: 0 4px 14px rgba(29, 78, 216, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 6px;
  }

  .btn-submit:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(29, 78, 216, 0.45);
  }

  .btn-submit:active {
    transform: scale(0.98);
  }

  .hint-box {
    margin-top: 24px;
    background: var(--primary-light);
    border: 1px dashed var(--primary-border);
    border-radius: 14px;
    padding: 14px;
    font-size: 12px;
    color: #1E40AF;
    line-height: 1.6;
  }

  .hint-title {
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
    color: var(--primary);
  }

  .quick-roles {
    display: flex;
    gap: 6px;
    margin-top: 10px;
    flex-wrap: wrap;
  }

  .role-chip {
    padding: 4px 10px;
    background: #FFFFFF;
    border: 1px solid var(--primary-border);
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    color: var(--primary);
    cursor: pointer;
    transition: all 0.15s;
  }

  .role-chip:hover {
    background: var(--primary);
    color: #fff;
  }

  .toast {
    position: fixed;
    top: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(-100px);
    background: var(--navy);
    color: #fff;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 700;
    z-index: 1000;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    transition: transform 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28);
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .toast.show { transform: translateX(-50%) translateY(0); }
  .toast.error { background: #DC2626; }
  .toast.success { background: #059669; }
</style>
</head>
<body>

<div id="toast" class="toast"></div>

<div class="login-card">
  <div class="brand-header">
    <div class="brand-icon">☕</div>
    <h1 class="brand-title">بوابة إدارة المقاهي السحابية</h1>
    <p class="brand-sub">إدارة التراخيص والمبيعات والمخزون عن بُعد</p>
  </div>

  <div class="auth-tabs">
    <button type="button" class="auth-tab active" id="tabCreds" onclick="switchTab('creds')">البريد الإلكتروني</button>
    <button type="button" class="auth-tab" id="tabLicense" onclick="switchTab('license')">مفتاح الترخيص (للكاستمير)</button>
  </div>

  <!-- نموذج الدخول بالإيميل -->
  <form id="credsForm" onsubmit="handleCredsLogin(event)">
    <div class="form-group">
      <label class="form-label">البريد الإلكتروني</label>
      <div class="input-wrap">
        <input type="email" id="emailInput" class="input-control" placeholder="admin@cafepos.com" required dir="ltr" />
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">كلمة المرور</label>
      <div class="input-wrap">
        <input type="password" id="passwordInput" class="input-control" placeholder="••••••••" required dir="ltr" />
      </div>
    </div>

    <button type="submit" class="btn-submit" id="credsBtn">
      <span>تسجيل الدخول</span>
      <span>←</span>
    </button>
  </form>

  <!-- نموذج الدخول بمفتاح الترخيص مباشرة -->
  <form id="licenseForm" style="display:none;" onsubmit="handleLicenseLogin(event)">
    <div class="form-group">
      <label class="form-label">مفتاح الترخيص الخاص بك</label>
      <div class="input-wrap">
        <input type="text" id="licenseKeyInput" class="input-control" placeholder="مثلاً: CAFE-XXXX-XXXX" required dir="ltr" style="letter-spacing:1px;font-weight:800;text-transform:uppercase;" />
      </div>
      <div style="font-size:11px;color:var(--slate);margin-top:6px;">
        💡 للدخول السريع كصاحب مقهى لمتابعة مبيعاتك وتعديل الأصناف دون الحاجة لكلمة مرور.
      </div>
    </div>

    <button type="submit" class="btn-submit" id="licenseBtn">
      <span>دخول بالمفتاح</span>
      <span>←</span>
    </button>
  </form>

  <div style="margin-top: 22px; padding-top: 16px; border-top: 1.5px dashed var(--border); text-align: center;">
    <div style="font-size: 12.5px; font-weight: 700; color: var(--slate); margin-bottom: 10px;">
      📲 تنزيل تطبيقات الكاشير ونقاط البيع:
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <a href="download.php?format=apk" class="btn-submit" style="text-decoration: none; background: #63262E; box-shadow: 0 4px 14px rgba(99, 38, 46, 0.35); font-size: 13.5px;">
        <span>📱</span>
        <span>تحميل تطبيق الهاتف (Android APK)</span>
      </a>
      <a href="download.php" class="btn-submit" style="text-decoration: none; background: #10B981; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35); font-size: 13.5px;">
        <span>💻</span>
        <span>تحميل برنامج الكمبيوتر (.exe)</span>
      </a>
      <a href="flutter/" target="_blank" class="btn-submit" style="text-decoration: none; background: #0284C7; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35); font-size: 13.5px;">
        <span>🌐</span>
        <span>تشغيل كاشير فلاتر في المتصفح (Web POS)</span>
      </a>
    </div>
  </div>
</div>

<script>
let currentTab = 'creds';

function switchTab(tab) {
  currentTab = tab;
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

function showToast(msg, type = '') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show ' + type;
  setTimeout(() => t.className = 'toast', 3500);
}

async function handleCredsLogin(e) {
  e.preventDefault();
  const email = document.getElementById('emailInput').value.trim();
  const password = document.getElementById('passwordInput').value;
  const btn = document.getElementById('credsBtn');
  
  btn.disabled = true;
  btn.textContent = 'جارٍ الدخول...';

  try {
    const res = await fetch('api.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type: 'credentials', email, password })
    });
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch(e) {
      throw new Error(text.trim() || ('خطأ خادم ' + res.status));
    }
    if (data.ok) {
      showToast('✅ تم تسجيل الدخول بنجاح', 'success');
      setTimeout(() => window.location.href = 'dashboard.php', 600);
    } else {
      showToast(data.error || 'بيانات الدخول غير صحيحة', 'error');
      btn.disabled = false;
      btn.innerHTML = '<span>تسجيل الدخول</span><span>←</span>';
    }
  } catch (err) {
    showToast('تعذر الاتصال بالخادم: ' + (err.message || ''), 'error');
    btn.disabled = false;
    btn.innerHTML = '<span>تسجيل الدخول</span><span>←</span>';
  }
}

async function handleLicenseLogin(e) {
  e.preventDefault();
  const licenseKey = document.getElementById('licenseKeyInput').value.trim();
  const btn = document.getElementById('licenseBtn');

  btn.disabled = true;
  btn.textContent = 'جارٍ التحقق...';

  try {
    const res = await fetch('api.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type: 'license', licenseKey })
    });
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch(e) {
      throw new Error(text.trim() || ('خطأ خادم ' + res.status));
    }
    if (data.ok) {
      showToast('✅ تم التحقق من الترخيص، مرحباً بك!', 'success');
      setTimeout(() => window.location.href = 'dashboard.php', 600);
    } else {
      showToast(data.error || 'الترخيص غير صالح أو غير مسجل', 'error');
      btn.disabled = false;
      btn.innerHTML = '<span>دخول بالمفتاح</span><span>←</span>';
    }
  } catch (err) {
    showToast('تعذر الاتصال بالخادم: ' + (err.message || ''), 'error');
    btn.disabled = false;
    btn.innerHTML = '<span>دخول بالمفتاح</span><span>←</span>';
  }
}
</script>

</body>
</html>
