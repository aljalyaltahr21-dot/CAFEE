<?php
session_start();
$user = $_SESSION['portal_user'] ?? null;
if (!$user) {
    header("Location: index.php");
    exit;
}
$role = $user['role'] ?? 'customer';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة التحكم السحابية - نظام إدارة المقاهي ☕</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Pacifico&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, sans-serif; -webkit-tap-highlight-color: transparent; }
  
  :root {
    --primary: #63262E;          /* لون البرغندي والقهوة الفاخر */
    --primary-dark: #4E1D24;
    --primary-light: #FDF2F0;
    --primary-border: #E8D4D2;
    --navy: #231815;             /* لون حبوب القهوة الداكنة */
    --navy-surface: #382522;
    --slate: #7E706D;
    --slate-light: #A89B98;
    --border: #EDE5E2;
    --border-light: #F4EFEB;
    --bg: #F8F5F2;               /* لون الخلفية الكريمة الدافئة */
    --white: #FFFFFF;
    --green: #10B981;
    --green-light: #ECFDF5;
    --green-dark: #065F46;
    --red: #EF4444;
    --red-light: #FEE2E2;
    --gold: #D97706;
    --gold-light: #FEF3C7;
    --radius-sm: 10px;
    --radius-md: 14px;
    --radius-lg: 18px;
    --radius-xl: 24px;
    --shadow-sm: 0 2px 8px rgba(99, 38, 46, 0.04);
    --shadow-md: 0 8px 24px rgba(99, 38, 46, 0.08);
    --shadow-lg: 0 20px 50px rgba(99, 38, 46, 0.12);
  }

  body {
    background: #FAF8F6;
    margin: 0;
    padding: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    color: var(--navy);
  }

  /* إطار التطبيق السحابي بكامل كِبر الشاشة بدون أي فراغات جانبية */
  .app-viewport {
    width: 100vw;
    height: 100vh;
    background: #FAF8F6;
    border-radius: 0;
    box-shadow: none;
    display: grid;
    grid-template-columns: 88px 1fr;
    overflow: hidden;
    border: none;
  }

  /* ============================================================
     زر القائمة للجوال (Hamburger)
  ============================================================ */
  .mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 14px;
    right: 14px;
    z-index: 350;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--primary);
    border: none;
    color: #fff;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(99,38,46,0.3);
  }

  .mobile-menu-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(35, 24, 21, 0.45);
    z-index: 200;
    backdrop-filter: blur(2px);
  }
  .mobile-menu-overlay.open { display: block; }

  /* القائمة الجانبية (Sidebar) */
  .sidebar {
    background: #FFFFFF;
    border-left: 1.5px solid var(--border-light);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24px 8px;
    z-index: 10;
  }

  .brand-logo {
    font-family: 'Pacifico', cursive;
    color: var(--primary);
    font-size: 26px;
    margin-bottom: 28px;
    text-decoration: none;
    letter-spacing: -0.5px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .nav-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
    align-items: center;
    flex: 1;
  }

  .nav-item {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    color: var(--slate);
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    border: none;
    background: transparent;
    position: relative;
  }

  .nav-item svg {
    width: 22px;
    height: 22px;
    stroke-width: 2;
    stroke: currentColor;
    fill: none;
    transition: transform 0.2s;
  }

  .nav-item:hover {
    color: var(--primary);
    background: var(--primary-light);
    transform: translateY(-2px);
  }

  .nav-item.active {
    background: var(--primary);
    color: #FFFFFF;
    box-shadow: 0 8px 18px rgba(99, 38, 46, 0.3);
  }

  .nav-item.active svg {
    stroke: #FFFFFF;
  }

  .tab-badge {
    position: absolute;
    top: 6px;
    right: 8px;
    padding: 2px 6px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 900;
    background: var(--red);
    color: #fff;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35);
  }

  .sidebar-bottom {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid var(--border-light);
  }

  /* منطقة المحتوى والترويسة (Main Workspace) */
  .main-wrapper {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
    background: #FAF8F6;
  }

  .main-header {
    background: #FFFFFF;
    border-bottom: 1.5px solid var(--border-light);
    padding: 0 28px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-shrink: 0;
  }

  .page-title {
    font-size: 20px;
    font-weight: 900;
    color: var(--navy);
  }

  .search-box {
    position: relative;
    width: 320px;
  }

  .search-input {
    width: 100%;
    height: 44px;
    background: #F9F7F5;
    border: 1.5px solid var(--border);
    border-radius: 999px;
    padding: 0 46px 0 18px;
    font-size: 13px;
    color: var(--navy);
    outline: none;
    transition: all 0.2s ease;
  }

  .search-input:focus {
    border-color: var(--primary);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(99, 38, 46, 0.12);
  }

  .search-icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    stroke: var(--slate);
    fill: none;
    pointer-events: none;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .user-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 14px 5px 6px;
    background: #FDFBF9;
    border: 1.5px solid var(--border);
    border-radius: 999px;
  }

  .user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--primary-light);
    border: 1.5px solid var(--primary-border);
  }

  .user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .user-details {
    display: flex;
    flex-direction: column;
    line-height: 1.25;
  }

  .user-role-label {
    font-size: 10.5px;
    font-weight: 700;
    color: var(--primary);
  }

  .user-name-text {
    font-size: 13px;
    font-weight: 800;
    color: var(--navy);
  }

  .bell-btn {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: 1.5px solid var(--border);
    background: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    color: var(--slate);
    transition: all 0.2s;
  }

  .bell-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
  }

  .bell-btn .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--red);
    position: absolute;
    top: 9px;
    right: 9px;
    border: 1.5px solid #FFFFFF;
  }

  /* المحتوى الرئيسي */
  .main-content {
    flex: 1;
    overflow-y: auto;
    padding: 24px 28px;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  /* بطاقات الإحصائيات العلوية */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 18px 20px;
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
  }

  .stat-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .stat-label {
    font-size: 12px;
    color: var(--slate);
    font-weight: 700;
  }

  .stat-value {
    font-size: 22px;
    font-weight: 900;
    color: var(--navy);
  }

  .stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
  }
  .icon-blue   { background: #EFF6FF; color: #1D4ED8; }
  .icon-green  { background: #ECFDF5; color: #10B981; }
  .icon-gold   { background: #FEF3C7; color: #F59E0B; }
  .icon-red    { background: #FEE2E2; color: #EF4444; }

  /* صندوق رخصة العميل البارز */
  .license-banner {
    background: linear-gradient(135deg, #1E40AF 0%, #1D4ED8 100%);
    color: #FFFFFF;
    border-radius: var(--radius-xl);
    padding: 24px 28px;
    margin-bottom: 24px;
    box-shadow: 0 10px 28px rgba(29, 78, 216, 0.25);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 18px;
  }

  .license-banner-left {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .license-icon-box {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    border: 1px solid rgba(255,255,255,0.25);
  }

  .license-key-text {
    font-family: monospace;
    font-size: 20px;
    font-weight: 900;
    letter-spacing: 2px;
  }

  .license-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    margin-top: 4px;
  }
  .status-active  { background: #D1FAE5; color: #065F46; }
  .status-expired { background: #FEE2E2; color: #991B1B; }

  .license-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  /* أزرار العمليات */
  .btn {
    padding: 9px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 800;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .btn-primary {
    background: var(--primary);
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(29, 78, 216, 0.25);
  }
  .btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
  }
  .btn-white {
    background: #FFFFFF;
    color: var(--primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }
  .btn-white:hover {
    background: #F8FAFC;
    transform: translateY(-1px);
  }
  .btn-outline {
    background: #FFFFFF;
    border: 1.5px solid var(--border);
    color: var(--navy);
  }
  .btn-outline:hover {
    background: #F1F5F9;
    border-color: var(--slate);
  }
  .btn-success {
    background: var(--green);
    color: #FFFFFF;
  }
  .btn-success:hover {
    background: #059669;
  }
  .btn-danger {
    background: var(--red);
    color: #FFFFFF;
  }
  .btn-danger:hover {
    background: #DC2626;
  }
  .btn-sm {
    padding: 6px 12px;
    font-size: 11.5px;
    border-radius: 8px;
  }

  /* البطاقات والجداول */
  .section-card {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-xl);
    padding: 24px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
  }

  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 12px;
  }

  .section-title {
    font-size: 17px;
    font-weight: 900;
    color: var(--navy);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .table-responsive {
    overflow-x: auto;
    width: 100%;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: right;
  }

  th {
    padding: 12px 14px;
    background: #F8FAFC;
    color: var(--slate);
    font-weight: 800;
    border-bottom: 1.5px solid var(--border);
    white-space: nowrap;
  }

  td {
    padding: 13px 14px;
    border-bottom: 1px solid var(--border);
    color: var(--navy);
    font-weight: 600;
    vertical-align: middle;
  }

  tr:hover td {
    background: #F8FAFC;
  }

  .badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
  }

  /* النوافذ المنبثقة (Modals) */
  .modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    z-index: 1000;
  }
  .modal-overlay.open {
    display: flex;
  }

  .modal-box {
    background: var(--white);
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 500px;
    padding: 28px 24px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-title {
    font-size: 18px;
    font-weight: 900;
    color: var(--navy);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .form-group {
    margin-bottom: 14px;
    text-align: right;
  }

  .form-label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 5px;
  }

  .input-control {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1.5px solid var(--border);
    font-size: 13.5px;
    font-weight: 600;
    background: #F8FAFC;
    outline: none;
    transition: all 0.2s;
  }
  .input-control:focus {
    background: #FFFFFF;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
  }

  /* أصناف ومخزون المنتجات */
  .category-pills {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    margin-bottom: 16px;
    padding-bottom: 4px;
  }

  .category-pill {
    padding: 7px 16px;
    border-radius: 999px;
    background: #F1F5F9;
    border: 1.5px solid var(--border);
    font-size: 12.5px;
    font-weight: 700;
    color: var(--slate);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s;
  }
  .category-pill.active {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
  }

  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 14px;
  }

  .product-card {
    background: #FFFFFF;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12px;
    transition: all 0.2s;
  }
  .product-card:hover {
    border-color: var(--primary-border);
    box-shadow: var(--shadow-md);
  }

  .pcard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }

  .pcard-name {
    font-size: 15px;
    font-weight: 900;
    color: var(--navy);
  }

  .pcard-cat {
    font-size: 11px;
    color: var(--slate);
    font-weight: 700;
    margin-top: 2px;
  }

  .pcard-price {
    font-size: 16px;
    font-weight: 900;
    color: var(--primary);
  }

  .stock-badge {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 800;
  }
  .stock-badge.low { background: var(--red-light); color: var(--red); }
  .stock-badge.ok  { background: var(--green-light); color: var(--green-dark); }

  /* الإشعارات Toast */
  .toast {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(-100px);
    background: var(--navy);
    color: #fff;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 700;
    z-index: 2000;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    transition: transform 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28);
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .toast.show { transform: translateX(-50%) translateY(0); }
  .toast.error { background: #DC2626; }
  .toast.success { background: #059669; }

  /* ============================================================
     Responsive — Tablet (≤1024px)
  ============================================================ */
  @media (max-width: 1024px) {
    .app-viewport {
      grid-template-columns: 72px 1fr;
    }
    .nav-item {
      width: 54px;
      height: 54px;
      font-size: 10px;
    }
    .main-content {
      padding: 16px 18px;
    }
    .main-header {
      padding: 0 18px;
      gap: 12px;
    }
    .search-box {
      width: 220px;
    }
    .user-details {
      display: none;
    }
    .products-grid {
      grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    }
    .stats-grid {
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
  }

  /* ============================================================
     Responsive — Mobile (≤768px)
  ============================================================ */
  @media (max-width: 768px) {
    /* إخفاء body overflow للسماح بالتمرير */
    body { overflow: auto; height: auto; min-height: 100vh; }

    /* تحويل لـ flex عمودي */
    .app-viewport {
      display: flex;
      flex-direction: column;
      height: auto;
      min-height: 100vh;
      overflow: visible;
    }

    /* الشريط الجانبي يتحول لقائمة منزلقة من اليمين */
    .sidebar {
      position: fixed;
      top: 0;
      right: -100%;
      width: 240px;
      height: 100vh;
      z-index: 300;
      flex-direction: column;
      align-items: flex-start;
      padding: 80px 16px 24px;
      transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: -8px 0 30px rgba(0,0,0,0.15);
    }
    .sidebar.mobile-open {
      right: 0;
    }

    /* أزرار التنقل داخل القائمة المنزلقة */
    .nav-list {
      width: 100%;
      align-items: flex-start;
      gap: 6px;
    }
    .nav-item {
      width: 100%;
      height: 50px;
      border-radius: 14px;
      flex-direction: row;
      justify-content: flex-start;
      padding: 0 16px;
      font-size: 13px;
      gap: 12px;
    }
    .nav-item svg {
      flex-shrink: 0;
    }
    .sidebar-bottom {
      width: 100%;
      align-items: flex-start;
    }
    .sidebar-bottom .nav-item {
      flex-direction: row;
      justify-content: flex-start;
      padding: 0 16px;
      width: 100%;
      height: 48px;
    }

    /* زر الهمبرغر */
    .mobile-menu-toggle {
      display: flex;
    }

    /* المحتوى الرئيسي يأخذ كامل العرض */
    .main-wrapper {
      width: 100%;
      height: auto;
      min-height: 100vh;
    }

    /* الهيدر يتكيف مع الجوال */
    .main-header {
      height: auto;
      min-height: 60px;
      padding: 10px 14px 10px 64px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .header-center {
      order: 3;
      width: 100%;
    }
    .search-box {
      width: 100%;
    }
    .page-title {
      font-size: 16px;
    }
    .user-chip {
      padding: 4px 10px 4px 4px;
    }
    .user-details {
      display: none;
    }
    .user-role-label { display: none; }

    /* المحتوى الرئيسي */
    .main-content {
      padding: 14px;
      gap: 14px;
    }

    /* الإحصائيات — عمود واحد */
    .stats-grid {
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .stat-value { font-size: 18px; }

    /* شبكة الأصناف — عمودان */
    .products-grid {
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .product-card {
      padding: 12px;
    }
    .pcard-name { font-size: 13px; }
    .pcard-price { font-size: 14px; }

    /* الجداول قابلة للتمرير أفقياً */
    .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    table { font-size: 12px; }
    th, td { padding: 10px 10px; white-space: nowrap; }

    /* شريط الفئات قابل للتمرير */
    .category-pills {
      flex-wrap: nowrap;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      padding-bottom: 6px;
    }

    /* بطاقة الترخيص */
    .license-banner {
      padding: 16px;
      flex-direction: column;
      gap: 12px;
    }
    .license-key-text { font-size: 15px; }
    .license-actions { flex-direction: column; width: 100%; }
    .license-actions .btn { width: 100%; justify-content: center; }

    /* النوافذ المنبثقة */
    .modal-box {
      margin: 0;
      border-radius: 20px 20px 0 0;
      max-height: 85vh;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      max-width: 100%;
    }
    .modal-overlay {
      align-items: flex-end;
      padding: 0;
    }
    .modal-footer {
      flex-direction: column-reverse;
    }
    .modal-footer .btn {
      width: 100%;
      justify-content: center;
    }

    /* البطاقة الجانبية (section-card) */
    .section-card {
      padding: 14px;
    }
    .section-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
    }
    .section-header .btn {
      width: 100%;
      justify-content: center;
    }

    /* الشريط الجانبي البرنامج */
    .brand-logo {
      font-size: 22px;
    }
  }

  /* ============================================================
     Responsive — Very Small Mobile (≤430px)
  ============================================================ */
  @media (max-width: 430px) {
    .stats-grid {
      grid-template-columns: 1fr;
    }
    .products-grid {
      grid-template-columns: 1fr;
    }
    .main-header {
      padding: 10px 10px 10px 60px;
    }
  }
</style>
</head>
<body>

<div id="toast" class="toast"></div>

<!-- زر القائمة للجوال -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()" title="القائمة">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
    <line x1="3" y1="6" x2="21" y2="6"/>
    <line x1="3" y1="12" x2="21" y2="12"/>
    <line x1="3" y1="18" x2="21" y2="18"/>
  </svg>
</button>
<!-- طبقة الإغلاق خلف القائمة -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="toggleMobileMenu()"></div>

<!-- إطار التطبيق السحابي الفخم (بنمط تصميم القهوة العالمي) -->
<div class="app-viewport">

  <!-- القائمة الجانبية العمودية (Sidebar) -->
  <aside class="sidebar" id="appSidebar">
    <a href="dashboard.php" class="brand-logo" title="لوحة إدارة المقاهي">coffee</a>

    <div class="nav-list">
      <?php if ($role === 'customer'): ?>
        <button class="nav-item nav-tab active" onclick="switchView('my_products')" title="أصنافي ومخزوني">
          <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
          <span>الأصناف</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('my_sales')" title="المبيعات">
          <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          <span>المبيعات</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('my_license')" title="الترخيص والاشتراك">
          <svg viewBox="0 0 24 24"><path d="M21 2l-2 2m-1.5 1.5L16 7l-1.5-1.5-2 2 1.5 1.5L12 11l-1.5-1.5-2 2 1.5 1.5L8 15l-1.5-1.5-2 2 1.5 1.5L4 19l-2 2"></path><circle cx="7.5" cy="7.5" r="4.5"></circle></svg>
          <span>الترخيص</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('my_modifications')" title="طلباتي">
          <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
          <span>تعديلاتي</span>
        </button>
      <?php elseif ($role === 'manager'): ?>
        <button class="nav-item nav-tab active" onclick="switchView('license_requests')" title="طلبات التراخيص">
          <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          <span>الطلبات</span>
          <span class="tab-badge" id="badgePendingLicReq" style="display:none;">0</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('modifications')" title="التعديلات">
          <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
          <span>تعديلات</span>
          <span class="tab-badge" id="badgePendingMods" style="display:none;">0</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('licenses')" title="جميع التراخيص">
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          <span>التراخيص</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('all_branches')" title="المقاهي والأصناف">
          <svg viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
          <span>المقاهي</span>
        </button>
      <?php else: /* admin */ ?>
        <button class="nav-item nav-tab active" onclick="switchView('licenses')" title="إدارة التراخيص">
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          <span>التراخيص</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('license_requests')" title="طلبات التراخيص">
          <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          <span>الطلبات</span>
          <span class="tab-badge" id="badgePendingLicReq" style="display:none;">0</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('modifications')" title="طلبات التعديلات">
          <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
          <span>تعديلات</span>
          <span class="tab-badge" id="badgePendingMods" style="display:none;">0</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('users')" title="المستخدمين والعملاء">
          <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <span>العملاء</span>
        </button>
        <button class="nav-item nav-tab" onclick="switchView('all_branches')" title="مبيعات وأصناف المقاهي">
          <svg viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
          <span>المقاهي</span>
        </button>
      <?php endif; ?>
    </div>

    <div class="sidebar-bottom">
      <a href="flutter/" class="nav-item" title="نظام الكاشير المطور (Flutter POS)" style="color:#0284C7;">
        <svg viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
        <span>فلاتر POS</span>
      </a>
      <a href="pos.php" class="nav-item" title="شاشة الكاشير السحابية (Web POS)">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        <span>POS قديم</span>
      </a>
      <a href="download.php" class="nav-item" title="تنزيل برنامج الكاشير">
        <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        <span>البرنامج</span>
      </a>
      <button class="nav-item" onclick="logout()" title="تسجيل الخروج">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>خروج</span>
      </button>
    </div>
  </aside>

  <!-- منطقة المحتوى الرئيسية مع الرأس المطور (Main Workspace) -->
  <div class="main-wrapper">
    <header class="main-header">
      <div class="header-left">
        <h1 class="page-title" id="currentSectionTitle">
          <?php if ($role === 'customer'): ?>أصنافي ومخزوني 📦<?php elseif ($role === 'manager'): ?>طلبات التراخيص والاشتراكات 📥<?php else: ?>إدارة التراخيص والمفاتيح 🔑<?php endif; ?>
        </h1>
      </div>

      <div class="header-center">
        <div class="search-box">
          <input type="text" id="globalSearchInput" class="search-input" placeholder="بحث سريع في التراخيص والأصناف..." oninput="handleGlobalSearch(this.value)" />
          <svg class="search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </div>
      </div>

      <div class="header-right">
        <div class="user-chip">
          <div class="user-avatar">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="avatar" />
          </div>
          <div class="user-details">
            <span class="user-role-label">
              <?php if ($role === 'admin'): ?>المدير العام 👑<?php elseif ($role === 'manager'): ?>مدير العمليات 👔<?php else: ?>صاحب المقهى ☕<?php endif; ?>
            </span>
            <span class="user-name-text"><?= htmlspecialchars($user['cafeName'] ?: $user['name']) ?></span>
          </div>
        </div>

        <button class="bell-btn" title="التنبيهات" onclick="showToast('النظام متزامن ويعمل بشكل طبيعي')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          <span class="dot"></span>
        </button>
      </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="main-content">

  <!-- ==================== 1. قسم الكاستمير: بطاقة رخصتي ==================== -->
  <?php if ($role === 'customer'): ?>
  <div id="view-my_license" style="display:none;">
    <div class="license-banner">
      <div class="license-banner-left">
        <div class="license-icon-box">🔑</div>
        <div>
          <div style="font-size:12px;opacity:0.85;">مفتاح الترخيص الخاص بك:</div>
          <div class="license-key-text" id="custLicKey">جارٍ التحميل...</div>
          <div id="custLicStatusBadge" class="license-status-chip status-active">فحص الصلاحية...</div>
        </div>
      </div>
        <a href="download.php" class="btn btn-white" style="background:#10B981;color:#fff;box-shadow:0 4px 14px rgba(16,185,129,0.35);font-size:13.5px;text-decoration:none;">
          <span>💻</span> تنزيل البرنامج المكتبي (.exe)
        </a>
        <button class="btn btn-white" onclick="openRequestLicenseModal()">⚡ طلب تجديد / ترخيص بالمدة</button>
        <button class="btn btn-white" style="background:rgba(255,255,255,0.18);color:#fff;" onclick="openRequestModModal()">📝 طلب تعديل معين</button>
      </div>
    </div>

    <!-- بطاقة تنزيل وتثبيت البرنامج المكتبي -->
    <div class="section-card" style="background:linear-gradient(135deg, #EFF6FF 0%, #FFFFFF 100%);border:2px solid #BFDBFE;margin-bottom:24px;">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:center;gap:16px;">
          <div style="width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,#1D4ED8,#3B82F6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:30px;box-shadow:0 6px 18px rgba(29,78,216,0.25);">
            💻
          </div>
          <div>
            <h3 style="font-size:17px;font-weight:900;color:var(--navy);margin-bottom:3px;">تحميل وتثبيت برنامج الكاشير المكتبي (Desktop POS)</h3>
            <p style="font-size:12px;color:var(--slate);">الإصدار الرسمي 1.0.3 لويندوز 64-bit | تنزيل آمن وسريع | معالج تثبيت تلقائي</p>
          </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <a href="download.php" class="btn btn-primary" style="padding:12px 24px;font-size:14px;gap:8px;text-decoration:none;background:#10B981;box-shadow:0 4px 14px rgba(16,185,129,0.35);">
            <span>⬇️</span> <b>تحميل مباشر (.exe)</b>
          </a>
          <a href="download.php?format=zip" class="btn btn-outline" style="padding:12px 18px;font-size:13px;text-decoration:none;">
            <span>📦</span> تحميل نسخة مضغوطة (.zip)
          </a>
        </div>
      </div>
      <div style="margin-top:14px;padding-top:14px;border-top:1px dashed #BFDBFE;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;font-size:12px;color:#1E40AF;">
        <div style="display:flex;align-items:center;gap:6px;">
          <span>1️⃣</span> <span>حمّل ملف <b>.zip</b> وافتحه لتشغيل معالج التثبيت <b>Setup.exe</b>.</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
          <span>2️⃣</span> <span>أدخل مفتاح رخصتك لمرة واحدة فقط ليتم التفعيل وربط المقهى.</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
          <span>🔄</span> <span><b>تحديثات تلقائية مضمونة:</b> أي تحديث جديد مستقبلاً سيصله تلقائياً داخل البرنامج عبر الإنترنت!</span>
        </div>
      </div>
    </div>

    <!-- بطاقات تفاصيل الترخيص -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-info">
          <span class="stat-label">تاريخ انتهاء الترخيص</span>
          <span class="stat-value" id="custLicExpiryDate" style="font-size:16px;">—</span>
        </div>
        <div class="stat-icon icon-blue">📅</div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <span class="stat-label">الأيام المتبقية</span>
          <span class="stat-value" id="custLicDaysLeft">—</span>
        </div>
        <div class="stat-icon icon-gold">⏳</div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <span class="stat-label">حالة المزامنة السحابية</span>
          <span class="stat-value" style="font-size:15px;color:var(--green)">متصل ومتزامن ✅</span>
        </div>
        <div class="stat-icon icon-green">☁️</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- شريط تنزيل سريع في شاشة الأصناف للكاستمير -->
  <?php if ($role === 'customer'): ?>
  <div style="background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:var(--radius-lg);padding:12px 20px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:10px;">
      <span style="font-size:22px;">💻</span>
      <div>
        <span style="font-weight:800;color:#1E40AF;font-size:13.5px;">برنامج الكاشير المكتبي للكمبيوتر جاهز للتنزيل</span>
        <span style="font-size:11.5px;color:var(--slate);display:block;">قم بتثبيته على جهاز نقطة البيع لربطه برخصتك ومزامنة هذه الأصناف فوراً</span>
      </div>
    </div>
    <a href="download.php" class="btn btn-primary" style="background:#10B981;font-size:12.5px;padding:8px 16px;text-decoration:none;">
      <span>📦</span> تحميل البرنامج المكتبي (.zip)
    </a>
  </div>
  <?php endif; ?>

  <!-- ==================== 2. قسم الأصناف والمخزون (الخاص بالرخصة) ==================== -->
  <div id="view-my_products">
    <div class="section-card">
      <div class="section-header">
        <div>
          <h2 class="section-title">📦 قائمة الأصناف والمخزون</h2>
          <p style="font-size:12px;color:var(--slate);margin-top:2px;">
            <?php if ($role === 'customer'): ?>
              الأصناف الخاصة بمقهاك ورخصتك فقط (تتزامن تلقائياً مع تطبيق الكاشير عندك)
            <?php else: ?>
              معاينة وإدارة أصناف ومخزون الفرع المحدد
            <?php endif; ?>
          </p>
        </div>
        <button class="btn btn-primary" onclick="openProductModal()">➕ إضافة صنف جديد</button>
      </div>

      <!-- فئات الفلترة -->
      <div class="category-pills" id="categoryPills"></div>

      <!-- شبكة الأصناف -->
      <div class="products-grid" id="productsGrid">
        <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--slate);">جارٍ تحميل الأصناف...</div>
      </div>
    </div>
  </div>

  <!-- ==================== 3. قسم مبيعات الكاستمير ==================== -->
  <div id="view-my_sales" style="display:none;">
    <div class="stats-grid" id="salesStatsGrid">
      <div class="stat-card">
        <div class="stat-info">
          <span class="stat-label">مبيعات الوردية / اليوم</span>
          <span class="stat-value" id="valTodaySales">0 د.ل</span>
        </div>
        <div class="stat-icon icon-green">💰</div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <span class="stat-label">عدد الطلبات</span>
          <span class="stat-value" id="valOrdersCount">0</span>
        </div>
        <div class="stat-icon icon-blue">🧾</div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <span class="stat-label">مبيعات الشهر</span>
          <span class="stat-value" id="valMonthSales">0 د.ل</span>
        </div>
        <div class="stat-icon icon-gold">📈</div>
      </div>
    </div>

    <div class="section-card">
      <h2 class="section-title" style="margin-bottom:14px;">🧾 آخر الفواتير المنفذة</h2>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th># الفاتورة</th>
              <th>رقم المناداة</th>
              <th>الوقت</th>
              <th>طريقة الدفع</th>
              <th>الإجمالي</th>
              <th>الحالة</th>
            </tr>
          </thead>
          <tbody id="salesTableBody">
            <tr><td colspan="6" style="text-align:center;color:var(--slate);">لا توجد مبيعات مسجلة بعد</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ==================== 4. قسم طلباتي وتعديلاتي (للكاستمير) ==================== -->
  <div id="view-my_modifications" style="display:none;">
    <div class="section-card">
      <div class="section-header">
        <div>
          <h2 class="section-title">🛠️ طلبات التعديلات والدعم الفني</h2>
          <p style="font-size:12px;color:var(--slate);">تتبع طلباتك الخاصة والردود الواردة عليها من إدارة النظام</p>
        </div>
        <button class="btn btn-primary" onclick="openRequestModModal()">➕ إرسال طلب تعديل جديد</button>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>عنوان التعديل</th>
              <th>التفاصيل</th>
              <th>تاريخ الإرسال</th>
              <th>الحالة</th>
              <th>رد الإدارة</th>
            </tr>
          </thead>
          <tbody id="myModsTableBody">
            <tr><td colspan="5" style="text-align:center;color:var(--slate);">لا توجد طلبات تعديلات بعد</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ==================== 5. قسم إدارة التراخيص (أدمن / مدير) ==================== -->
  <?php if ($role === 'admin' || $role === 'manager'): ?>
  <div id="view-licenses" style="display:none;">
    <div class="section-card">
      <div class="section-header">
        <div>
          <h2 class="section-title">🔑 إدارة تراخيص النظام (Licenses)</h2>
          <p style="font-size:12px;color:var(--slate);">توليد التراخيص، تحديد الصلاحيات، وإلغاء قفل الأجهزة</p>
        </div>
        <button class="btn btn-primary" onclick="openNewLicenseModal()">➕ توليد ترخيص جديد</button>
      </div>

      <!-- ملخص المراقبة الحية لشبكة المقاهي -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px;">
        <div style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:14px;padding:14px;text-align:center;">
          <div style="font-size:11.5px;font-weight:700;color:var(--slate);margin-bottom:4px;">إجمالي المقاهي والتراخيص</div>
          <div style="font-size:22px;font-weight:900;color:var(--navy);" id="kpiFleetTotal">0</div>
        </div>
        <div style="background:#ECFDF5;border:1.5px solid #A7F3D0;border-radius:14px;padding:14px;text-align:center;">
          <div style="font-size:11.5px;font-weight:700;color:#065F46;margin-bottom:4px;">🟢 متصلة أونلاين الآن</div>
          <div style="font-size:22px;font-weight:900;color:#059669;" id="kpiFleetOnline">0</div>
        </div>
        <div style="background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:14px;padding:14px;text-align:center;">
          <div style="font-size:11.5px;font-weight:700;color:#1E40AF;margin-bottom:4px;">✅ محدثة للإصدار v1.0.3</div>
          <div style="font-size:22px;font-weight:900;color:#1D4ED8;" id="kpiFleetUpdated">0</div>
        </div>
        <div style="background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:14px;padding:14px;text-align:center;">
          <div style="font-size:11.5px;font-weight:700;color:#92400E;margin-bottom:4px;">⚠️ تحتاج تحديث (&lt; v1.0.3)</div>
          <div style="font-size:22px;font-weight:900;color:#D97706;" id="kpiFleetOutdated">0</div>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>مفتاح الترخيص</th>
              <th>صاحب المقهى / الاسم</th>
              <th>اسم المقهى</th>
              <th>الإصدار الحالي</th>
              <th>الاتصال والنشاط</th>
              <th>البريد الإلكتروني</th>
              <th>تاريخ الانتهاء</th>
              <th>الحالة</th>
              <th>الجهاز</th>
              <th>إجراءات</th>
            </tr>
          </thead>
          <tbody id="licensesTableBody">
            <tr><td colspan="10" style="text-align:center;color:var(--slate);">جارٍ تحميل التراخيص...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== 6. قسم طلبات التراخيص والموافقة (أدمن / مدير) ==================== -->
  <?php if ($role === 'admin' || $role === 'manager'): ?>
  <div id="view-license_requests" style="display:none;">
    <div class="section-card">
      <div class="section-header">
        <div>
          <h2 class="section-title">📥 طلبات التراخيص والتجديد الواردة</h2>
          <p style="font-size:12px;color:var(--slate);">طلبات العملاء لتجديد الرخص — يمكنك الموافقة وتحديد المدة مباشرة فتصل للعميل</p>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>العميل / المقهى</th>
              <th>البريد الإلكتروني</th>
              <th>الرخصة الحالية</th>
              <th>المدة المطلوبة</th>
              <th>ملاحظات العميل</th>
              <th>تاريخ الطلب</th>
              <th>الحالة</th>
              <th>إجراءات الموافقة</th>
            </tr>
          </thead>
          <tbody id="licRequestsTableBody">
            <tr><td colspan="8" style="text-align:center;color:var(--slate);">جارٍ تحميل الطلبات...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== 7. قسم طلبات التعديلات العامة (أدمن / مدير) ==================== -->
  <?php if ($role === 'admin' || $role === 'manager'): ?>
  <div id="view-modifications" style="display:none;">
    <div class="section-card">
      <div class="section-header">
        <div>
          <h2 class="section-title">🛠️ طلبات التعديلات من أصحاب المقاهي</h2>
          <p style="font-size:12px;color:var(--slate);">مراجعة التعديلات المطلوبة والرد عليها وتحديث حالتها</p>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>المقهى / العميل</th>
              <th>عنوان التعديل</th>
              <th>التفاصيل والملاحظات</th>
              <th>تاريخ الطلب</th>
              <th>الحالة</th>
              <th>رد الإدارة</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody id="allModsTableBody">
            <tr><td colspan="7" style="text-align:center;color:var(--slate);">جارٍ تحميل طلبات التعديل...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== 8. قسم إدارة المستخدمين والإيميلات (أدمن فقط) ==================== -->
  <?php if ($role === 'admin'): ?>
  <div id="view-users" style="display:none;">
    <div class="section-card">
      <div class="section-header">
        <div>
          <h2 class="section-title">👥 حسابات العملاء والمستخدمين (Emails)</h2>
          <p style="font-size:12px;color:var(--slate);">إدخال إيميلات العملاء، تحديد الصلاحيات، وربط كل حساب برخصته الخاصة</p>
        </div>
        <button class="btn btn-primary" onclick="openNewUserModal()">➕ إضافة عميل / مستخدم جديد</button>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>الاسم</th>
              <th>البريد الإلكتروني</th>
              <th>الدور (Role)</th>
              <th>اسم المقهى</th>
              <th>مفتاح الترخيص المرتبط</th>
              <th>رقم الهاتف</th>
              <th>إجراءات</th>
            </tr>
          </thead>
          <tbody id="usersTableBody">
            <tr><td colspan="7" style="text-align:center;color:var(--slate);">جارٍ تحميل المستخدمين...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== 9. قسم نظرة عامة على المقاهي (أدمن / مدير) ==================== -->
  <?php if ($role === 'admin' || $role === 'manager'): ?>
  <div id="view-all_branches" style="display:none;">
    <div class="section-card">
      <div class="section-header">
        <div>
          <h2 class="section-title">☕ جميع المقاهي والفروع المتصلة</h2>
          <p style="font-size:12px;color:var(--slate);">متابعة مبيعات كل مقهى والانتقال لتعديل أصنافه مباشرة</p>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>اسم المقهى</th>
              <th>مفتاح الترخيص / الفرع</th>
              <th>صاحب المقهى</th>
              <th>مبيعات اليوم</th>
              <th>الطلبات</th>
              <th>عدد الأصناف</th>
              <th>حالة الرخصة</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody id="allBranchesTableBody">
            <tr><td colspan="8" style="text-align:center;color:var(--slate);">جارٍ التحميل...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</main>
  </div><!-- .main-wrapper -->
</div><!-- .app-viewport -->

<!-- ==============================================================
     النوافذ المنبثقة (MODALS)
============================================================== -->

<!-- 1. نافذة إضافة / تعديل منتج ومخزون -->
<div class="modal-overlay" id="productModal">
  <div class="modal-box">
    <h3 class="modal-title" id="productModalTitle">➕ إضافة منتج جديد</h3>
    <input type="hidden" id="pEditId" />
    <div class="form-group">
      <label class="form-label">اسم الصنف</label>
      <input type="text" id="pName" class="input-control" placeholder="مثلاً: كابتشينو" required />
    </div>
    <div class="form-group">
      <label class="form-label">التصنيف</label>
      <select id="pCategory" class="input-control">
        <option value="قهوة ساخنة">قهوة ساخنة</option>
        <option value="قهوة باردة">قهوة باردة</option>
        <option value="مشروبات">مشروبات</option>
        <option value="حلويات">حلويات</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">السعر (د.ل)</label>
      <input type="number" id="pPrice" step="0.5" class="input-control" placeholder="15" required />
    </div>
    <div class="form-group">
      <label class="form-label">كمية المخزون المتوفرة (Stock)</label>
      <input type="number" id="pStock" class="input-control" placeholder="50" />
    </div>
    <div class="form-group">
      <label class="form-label">حد تنبيه انخفاض المخزون</label>
      <input type="number" id="pThreshold" class="input-control" value="10" />
    </div>
    <div class="form-group" style="display:flex;align-items:center;gap:8px;">
      <input type="checkbox" id="pTrackStock" checked style="width:18px;height:18px;" />
      <label for="pTrackStock" style="font-size:13px;font-weight:700;cursor:pointer;">تفعيل متابعة كمية المخزون لهذا الصنف</label>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('productModal')">إلغاء</button>
      <button class="btn btn-primary" onclick="saveProduct()">حفظ الصنف</button>
    </div>
  </div>
</div>

<!-- 2. نافذة طلب ترخيص / تجديد (للكاستمير) -->
<div class="modal-overlay" id="reqLicenseModal">
  <div class="modal-box">
    <h3 class="modal-title">⚡ طلب تجديد أو تفعيل ترخيص</h3>
    <p style="font-size:12.5px;color:var(--slate);margin-bottom:16px;">
      أرسل طلبك للإدارة، وبمجرد الموافقة سيتم تمديد رخصتك وتحديث الصلاحية تلقائياً دون أي خطوات إضافية!
    </p>
    <div class="form-group">
      <label class="form-label">المدة المطلوبة</label>
      <select id="reqLicDuration" class="input-control">
        <option value="30_days">شهر واحد (30 يوماً)</option>
        <option value="90_days">3 أشهر (90 يوماً)</option>
        <option value="180_days">6 أشهر (نصف سنة)</option>
        <option value="365_days">سنة كاملة (365 يوماً)</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">ملاحظات إضافية (اختياري)</label>
      <textarea id="reqLicNotes" class="input-control" rows="3" placeholder="اكتب أي ملاحظة للإدارة أو رقم الحوالة/الدفع..."></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('reqLicenseModal')">إلغاء</button>
      <button class="btn btn-primary" onclick="submitLicenseRequest()">إرسال الطلب للإدارة</button>
    </div>
  </div>
</div>

<!-- 3. نافذة طلب تعديل معين (للكاستمير) -->
<div class="modal-overlay" id="reqModModal">
  <div class="modal-box">
    <h3 class="modal-title">📝 طلب تعديل معين في البرنامج / المقهى</h3>
    <div class="form-group">
      <label class="form-label">عنوان التعديل المطلوب</label>
      <input type="text" id="reqModTitle" class="input-control" placeholder="مثلاً: تعديل ترويسة الفاتورة أو إضافة تقرير خاص" required />
    </div>
    <div class="form-group">
      <label class="form-label">تفاصيل التعديل المطلوب بدقة</label>
      <textarea id="reqModDetails" class="input-control" rows="4" placeholder="اشرح بالتفصيل التعديل الذي تريده ليقوم المطور أو الإدارة بتنفيذه لك..." required></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('reqModModal')">إلغاء</button>
      <button class="btn btn-primary" onclick="submitModificationRequest()">إرسال التعديل</button>
    </div>
  </div>
</div>

<!-- 4. نافذة موافقة على طلب ترخيص وتحديد المدة (للأدمن والمدير) -->
<div class="modal-overlay" id="approveReqModal">
  <div class="modal-box">
    <h3 class="modal-title">✅ الموافقة على طلب الترخيص</h3>
    <input type="hidden" id="appReqId" />
    <div style="background:#F8FAFC;padding:12px;border-radius:10px;margin-bottom:14px;font-size:12.5px;line-height:1.6;" id="appReqSummary">
      <!-- ملخص الطلب -->
    </div>
    <div class="form-group">
      <label class="form-label">المدة المعتمدة للعميل (بالأيام)</label>
      <input type="number" id="appReqDays" class="input-control" value="30" min="1" required />
      <div style="display:flex;gap:6px;margin-top:6px;">
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('appReqDays').value=30">30 يوم</button>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('appReqDays').value=90">90 يوم</button>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('appReqDays').value=180">180 يوم</button>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('appReqDays').value=365">سنة كاملة</button>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">ملاحظات أو رسالة للعميل</label>
      <input type="text" id="appReqNotes" class="input-control" value="تمت الموافقة وتفعيل الترخيص بنجاح، شكراً لتعاملكم معنا." />
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('approveReqModal')">إلغاء</button>
      <button class="btn btn-success" onclick="confirmApproveRequest()">تأكيد الموافقة والتفعيل فوراً</button>
    </div>
  </div>
</div>

<!-- 5. نافذة الرد على طلب تعديل (للأدمن والمدير) -->
<div class="modal-overlay" id="replyModModal">
  <div class="modal-box">
    <h3 class="modal-title">🛠️ الرد على طلب التعديل</h3>
    <input type="hidden" id="replyModId" />
    <div style="background:#F8FAFC;padding:12px;border-radius:10px;margin-bottom:14px;font-size:12.5px;" id="replyModSummary"></div>
    <div class="form-group">
      <label class="form-label">تحديث حالة الطلب</label>
      <select id="replyModStatus" class="input-control">
        <option value="in_progress">⚙️ قيد العمل والتنفيذ</option>
        <option value="completed">✅ تم التنفيذ بنجاح</option>
        <option value="rejected">❌ تم الرفض أو غير متاح</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">رد الإدارة للعميل</label>
      <textarea id="replyModText" class="input-control" rows="3" placeholder="اكتب ردك أو التحديث لصاحب المقهى..."></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('replyModModal')">إلغاء</button>
      <button class="btn btn-primary" onclick="confirmReplyMod()">حفظ التحديث</button>
    </div>
  </div>
</div>

<!-- 6. نافذة إنشاء / تعديل ترخيص (للأدمن والمدير) -->
<div class="modal-overlay" id="licenseModal">
  <div class="modal-box">
    <h3 class="modal-title" id="licenseModalTitle">🔑 إنشاء ترخيص جديد</h3>
    <div class="form-group">
      <label class="form-label">مفتاح الترخيص (اتركه فارغاً للتوليد التلقائي)</label>
      <input type="text" id="licKeyInput" class="input-control" placeholder="تلقائي: CAFE-XXXX-XXXX" dir="ltr" />
    </div>
    <div class="form-group">
      <label class="form-label">اسم العميل / صاحب المقهى</label>
      <input type="text" id="licCustomerName" class="input-control" placeholder="مثلاً: محمد أحمد" required />
    </div>
    <div class="form-group">
      <label class="form-label">اسم المقهى</label>
      <input type="text" id="licCafeName" class="input-control" placeholder="مثلاً: مقهى الأندلس" />
    </div>
    <div class="form-group">
      <label class="form-label">البريد الإلكتروني للعميل</label>
      <input type="email" id="licCustomerEmail" class="input-control" placeholder="mohammed@example.com" dir="ltr" />
    </div>
    <div class="form-group">
      <label class="form-label">رقم الهاتف</label>
      <input type="text" id="licPhone" class="input-control" placeholder="091XXXXXXX" />
    </div>
    <div class="form-group">
      <label class="form-label">مدة الصلاحية (بالأيام)</label>
      <input type="number" id="licDurationDays" class="input-control" value="30" />
      <div style="display:flex;gap:6px;margin-top:6px;">
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('licDurationDays').value=30">30 يوم</button>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('licDurationDays').value=90">90 يوم</button>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('licDurationDays').value=180">180 يوم</button>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('licDurationDays').value=365">سنة</button>
      </div>
    </div>
    <div class="form-group" style="display:flex;align-items:center;gap:8px;">
      <input type="checkbox" id="licActive" checked style="width:18px;height:18px;" />
      <label for="licActive" style="font-size:13px;font-weight:700;cursor:pointer;">الترخيص نشط ومفعل</label>
    </div>
    <div class="form-group" style="display:flex;align-items:center;gap:8px;">
      <input type="checkbox" id="licResetDevice" style="width:18px;height:18px;" />
      <label for="licResetDevice" style="font-size:13px;font-weight:700;cursor:pointer;">إلغاء قفل الجهاز (السماح بالتثبيت على جهاز جديد)</label>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('licenseModal')">إلغاء</button>
      <button class="btn btn-primary" onclick="saveLicense()">حفظ الترخيص</button>
    </div>
  </div>
</div>

<!-- 7. نافذة إضافة حساب عميل وإيميل (للأدمن) -->
<div class="modal-overlay" id="userModal">
  <div class="modal-box">
    <h3 class="modal-title">👥 إضافة عميل / مستخدم جديد</h3>
    <div class="form-group">
      <label class="form-label">الاسم الكامل</label>
      <input type="text" id="uName" class="input-control" placeholder="اسم العميل" required />
    </div>
    <div class="form-group">
      <label class="form-label">البريد الإلكتروني (Login Email)</label>
      <input type="email" id="uEmail" class="input-control" placeholder="client@cafe.com" required dir="ltr" />
    </div>
    <div class="form-group">
      <label class="form-label">كلمة المرور</label>
      <input type="text" id="uPassword" class="input-control" placeholder="123456" value="123456" required dir="ltr" />
    </div>
    <div class="form-group">
      <label class="form-label">الدور والصلاحية (Role)</label>
      <select id="uRole" class="input-control">
        <option value="customer" selected>صاحب مقهى (Customer)</option>
        <option value="manager">مدير تراخيص ومبيعات (Manager)</option>
        <option value="admin">مدير عام (Admin)</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">اسم المقهى</label>
      <input type="text" id="uCafeName" class="input-control" placeholder="اسم المقهى" />
    </div>
    <div class="form-group">
      <label class="form-label">مفتاح الترخيص المرتبط بهذا الحساب</label>
      <input type="text" id="uLicenseKey" class="input-control" placeholder="CAFE-XXXX-XXXX" dir="ltr" />
    </div>
    <div class="form-group">
      <label class="form-label">رقم الهاتف</label>
      <input type="text" id="uPhone" class="input-control" placeholder="091XXXXXXX" />
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('userModal')">إلغاء</button>
      <button class="btn btn-primary" onclick="saveUser()">حفظ الحساب</button>
    </div>
  </div>
</div>

<script>
const USER_ROLE = "<?= $role ?>";
const CATEGORIES = ["الكل", "قهوة ساخنة", "قهوة باردة", "مشروبات", "حلويات"];
let activeCategory = "الكل";
let currentBranchData = null;
let currentSelectedBranchId = null;

// ===== التنقل بين التبويبات =====
function switchView(viewName) {
  document.querySelectorAll('[id^="view-"]').forEach(el => el.style.display = 'none');
  const target = document.getElementById('view-' + viewName);
  if (target) target.style.display = 'block';

  document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
  const clicked = Array.from(document.querySelectorAll('.nav-tab')).find(t => t.getAttribute('onclick')?.includes(viewName));
  if (clicked) clicked.classList.add('active');

  const titleMap = {
    'licenses': 'إدارة التراخيص والمفاتيح 🔑',
    'license_requests': 'طلبات التراخيص والاشتراكات 📥',
    'modifications': 'طلبات التعديلات والمميزات 🛠️',
    'users': 'العملاء والمستخدمين 👥',
    'all_branches': 'مبيعات وأصناف شبكة المقاهي ☕',
    'my_products': 'أصنافي ومخزوني 📦',
    'my_sales': 'مبيعات المقهى 📊',
    'my_license': 'رخصتي والاشتراك 🔑',
    'my_modifications': 'طلباتي وتعديلاتي 🛠️'
  };
  const titleEl = document.getElementById('currentSectionTitle');
  if (titleEl && titleMap[viewName]) titleEl.textContent = titleMap[viewName];

  // تحميل البيانات الخاصة بالتبويب
  if (viewName === 'my_products' || viewName === 'my_sales' || viewName === 'my_license') {
    loadBranchState();
  } else if (viewName === 'licenses') {
    loadLicenses();
  } else if (viewName === 'license_requests') {
    loadLicenseRequests();
  } else if (viewName === 'modifications' || viewName === 'my_modifications') {
    loadModifications();
  } else if (viewName === 'users') {
    loadUsers();
  } else if (viewName === 'all_branches') {
    loadAllBranches();
  }
}

// بحث سريع في التراخيص والأصناف عبر شريط البحث العلوي
function handleGlobalSearch(q) {
  const query = q.toLowerCase().trim();
  const activeView = Array.from(document.querySelectorAll('[id^="view-"]')).find(v => v.style.display !== 'none');
  if (!activeView) return;
  const rows = activeView.querySelectorAll('tbody tr');
  rows.forEach(r => {
    if (r.children.length === 1 && r.textContent.includes('لا توجد')) return;
    const text = r.textContent.toLowerCase();
    r.style.display = text.includes(query) ? '' : 'none';
  });
}

// ===== قائمة الجوال (Mobile Sidebar) =====
function toggleMobileMenu() {
  const sidebar = document.getElementById('appSidebar');
  const overlay = document.getElementById('mobileMenuOverlay');
  if (!sidebar) return;
  const isOpen = sidebar.classList.toggle('mobile-open');
  overlay.classList.toggle('open', isOpen);
  document.body.style.overflow = isOpen ? 'hidden' : '';
}

// إغلاق القائمة عند الضغط على أي زر تنقل داخلها (جوال)
document.addEventListener('DOMContentLoaded', function() {
  const navTabs = document.querySelectorAll('.nav-item.nav-tab');
  navTabs.forEach(tab => {
    tab.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('appSidebar');
        const overlay = document.getElementById('mobileMenuOverlay');
        if (sidebar && sidebar.classList.contains('mobile-open')) {
          sidebar.classList.remove('mobile-open');
          overlay.classList.remove('open');
          document.body.style.overflow = '';
        }
      }
    });
  });
});

// ===== إشعارات Toast =====
function showToast(msg, type = '') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show ' + type;
  setTimeout(() => t.className = 'toast', 3500);
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ==============================================================
// 1. تحميل حالة الفرع والأصناف (مع العزل التام للرخصة)
// ==============================================================
async function loadBranchState() {
  try {
    let url = 'api.php?action=state';
    if (currentSelectedBranchId) url += '&branchId=' + encodeURIComponent(currentSelectedBranchId);

    const res = await fetch(url);
    const json = await res.json();
    if (!json.ok) {
      showToast(json.error || 'تعذر جلب البيانات', 'error');
      return;
    }

    currentBranchData = json.data || {};
    const lic = json.license;

    // تحديث بيانات رخصة العميل إذا كان في صفحة رخصتي
    if (lic && document.getElementById('custLicKey')) {
      document.getElementById('custLicKey').textContent = lic.key || 'غير مسجلة';
      const expiresAt = lic.expiresAt ? new Date(lic.expiresAt) : null;
      const isExpired = expiresAt && Date.now() > expiresAt.getTime();
      
      const badge = document.getElementById('custLicStatusBadge');
      if (lic.active && !isExpired) {
        badge.className = 'license-status-chip status-active';
        badge.textContent = 'سارية ونشطة ✅';
      } else {
        badge.className = 'license-status-chip status-expired';
        badge.textContent = isExpired ? 'منتهية الصلاحية ⚠️' : 'معطلة من الإدارة ❌';
      }

      if (expiresAt) {
        document.getElementById('custLicExpiryDate').textContent = expiresAt.toLocaleDateString('ar-LY');
        const diffDays = Math.ceil((expiresAt.getTime() - Date.now()) / (1000 * 86400));
        document.getElementById('custLicDaysLeft').textContent = diffDays > 0 ? (diffDays + ' يوم') : 'منتهية';
      }
    }

    // تحديث الأصناف
    renderProducts();

    // تحديث المبيعات
    if (document.getElementById('valTodaySales')) {
      document.getElementById('valTodaySales').textContent = (currentBranchData.todaySales || 0).toLocaleString('ar') + ' د.ل';
      document.getElementById('valOrdersCount').textContent = currentBranchData.ordersCount || 0;
      document.getElementById('valMonthSales').textContent = (currentBranchData.monthSales || 0).toLocaleString('ar') + ' د.ل';

      const txs = currentBranchData.recentTx || [];
      const tbody = document.getElementById('salesTableBody');
      if (txs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--slate);padding:24px;">لا توجد مبيعات مسجلة بعد</td></tr>';
      } else {
        tbody.innerHTML = txs.slice(0, 30).map(t => `
          <tr>
            <td><b>#${t.invoiceNumber || '-'}</b></td>
            <td><span style="font-weight:900;color:var(--primary)">#${t.orderToken || '-'}</span></td>
            <td>${t.time || '—'}</td>
            <td>${t.paymentMethod === 'card' ? '💳 بطاقة' : '💵 نقداً'}</td>
            <td style="font-weight:800;color:var(--green)">${(t.total || 0).toLocaleString('ar')} د.ل</td>
            <td>${t.refunded ? '<span style="color:var(--red)">مسترجع</span>' : '<span style="color:var(--green)">ناجح ✓</span>'}</td>
          </tr>
        `).join('');
      }
    }

  } catch (err) {
    showToast('خطأ في الاتصال بالسحابة', 'error');
  }
}

// عرض الأصناف
function renderProducts() {
  const container = document.getElementById('productsGrid');
  const pillsContainer = document.getElementById('categoryPills');
  if (!container) return;

  // أزرار الفئات
  pillsContainer.innerHTML = CATEGORIES.map(c => `
    <button class="category-pill ${c === activeCategory ? 'active' : ''}" onclick="setCategory('${c}')">${c}</button>
  `).join('');

  const products = currentBranchData.products || [];
  const filtered = activeCategory === 'الكل' ? products : products.filter(p => p.category === activeCategory);

  if (filtered.length === 0) {
    container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--slate);">لا توجد أصناف في هذا التصنيف. اضغط على "إضافة صنف جديد" لإضافته لرخصتك.</div>';
    return;
  }

  container.innerHTML = filtered.map(p => `
    <div class="product-card">
      <div>
        <div class="pcard-header">
          <div>
            <div class="pcard-name">${p.name}</div>
            <div class="pcard-cat">${p.category}</div>
          </div>
          <div style="display:flex;gap:4px;">
            <button class="btn btn-outline btn-sm" onclick='editProduct(${JSON.stringify(p)})' title="تعديل">✏️</button>
            <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id})" title="حذف">🗑️</button>
          </div>
        </div>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:flex-end;">
        <div class="pcard-price">${(p.price || 0).toLocaleString('ar')} د.ل</div>
        ${p.trackStock ? `
          <div class="stock-badge ${p.stock <= p.threshold ? 'low' : 'ok'}">
            ${p.stock <= p.threshold ? '⚠️ مخزون منخفض: ' : 'متوفر: '} <b>${p.stock}</b>
          </div>
        ` : '<span style="font-size:11px;color:var(--slate);">غير محدود</span>'}
      </div>
    </div>
  `).join('');
}

function setCategory(cat) {
  activeCategory = cat;
  renderProducts();
}

function openProductModal() {
  document.getElementById('pEditId').value = '';
  document.getElementById('pName').value = '';
  document.getElementById('pCategory').value = 'قهوة ساخنة';
  document.getElementById('pPrice').value = '';
  document.getElementById('pStock').value = '50';
  document.getElementById('pThreshold').value = '10';
  document.getElementById('pTrackStock').checked = true;
  document.getElementById('productModalTitle').textContent = '➕ إضافة صنف جديد لرخصتك';
  openModal('productModal');
}

function editProduct(p) {
  document.getElementById('pEditId').value = p.id;
  document.getElementById('pName').value = p.name;
  document.getElementById('pCategory').value = p.category || 'قهوة ساخنة';
  document.getElementById('pPrice').value = p.price;
  document.getElementById('pStock').value = p.stock || 0;
  document.getElementById('pThreshold').value = p.threshold || 10;
  document.getElementById('pTrackStock').checked = p.trackStock !== false;
  document.getElementById('productModalTitle').textContent = '✏️ تعديل صنف والكمية';
  openModal('productModal');
}

async function saveProduct() {
  const id = document.getElementById('pEditId').value;
  const name = document.getElementById('pName').value.trim();
  const category = document.getElementById('pCategory').value;
  const price = parseFloat(document.getElementById('pPrice').value);
  const stock = parseInt(document.getElementById('pStock').value) || 0;
  const threshold = parseInt(document.getElementById('pThreshold').value) || 10;
  const trackStock = document.getElementById('pTrackStock').checked;

  if (!name) { showToast('يرجى كتابة اسم الصنف', 'error'); return; }
  if (isNaN(price) || price < 0) { showToast('يرجى إدخال سعر صحيح', 'error'); return; }

  const payload = { name, category, price, stock, threshold, trackStock };
  if (id) payload.id = parseInt(id);
  if (currentSelectedBranchId) payload.branchId = currentSelectedBranchId;

  try {
    const res = await fetch('api.php?action=save_product', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.ok) {
      showToast(json.message || 'تم حفظ الصنف بنجاح', 'success');
      closeModal('productModal');
      loadBranchState();
    } else {
      showToast(json.error || 'فشل الحفظ', 'error');
    }
  } catch (err) {
    showToast('خطأ في الاتصال بالخادم', 'error');
  }
}

async function deleteProduct(id) {
  if (!confirm('هل أنت متأكد من حذف هذا الصنف من رخصتك؟')) return;
  try {
    const res = await fetch('api.php?action=delete_product', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, branchId: currentSelectedBranchId })
    });
    const json = await res.json();
    if (json.ok) {
      showToast('تم حذف الصنف بنجاح', 'success');
      loadBranchState();
    } else {
      showToast(json.error || 'فشل الحذف', 'error');
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

// ==============================================================
// 2. طلبات التراخيص والموافقة
// ==============================================================
function openRequestLicenseModal() {
  openModal('reqLicenseModal');
}

async function submitLicenseRequest() {
  const requestedDuration = document.getElementById('reqLicDuration').value;
  const notes = document.getElementById('reqLicNotes').value.trim();

  try {
    const res = await fetch('api.php?action=request_license', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ requestedDuration, notes })
    });
    const json = await res.json();
    if (json.ok) {
      showToast('✅ ' + json.message, 'success');
      closeModal('reqLicenseModal');
      document.getElementById('reqLicNotes').value = '';
    } else {
      showToast(json.error || 'فشل إرسال الطلب', 'error');
    }
  } catch (e) {
    showToast('تعذر إرسال الطلب', 'error');
  }
}

async function loadLicenseRequests() {
  try {
    const res = await fetch('api.php?action=list_license_requests');
    const json = await res.json();
    if (!json.ok) return;

    const reqs = json.requests || [];
    const pending = reqs.filter(r => r.status === 'pending');
    
    // شارة الطلبات المعلقة في التبويب
    const badge = document.getElementById('badgePendingLicReq');
    if (badge) {
      if (pending.length > 0) {
        badge.textContent = pending.length;
        badge.style.display = 'inline-block';
      } else {
        badge.style.display = 'none';
      }
    }

    const tbody = document.getElementById('licRequestsTableBody');
    if (!tbody) return;

    if (reqs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--slate);">لا توجد طلبات تراخيص واردة</td></tr>';
      return;
    }

    tbody.innerHTML = reqs.map(r => `
      <tr>
        <td><b>${r.cafeName || '—'}</b><br><small style="color:var(--slate);">${r.customerName || 'عميل'}</small></td>
        <td dir="ltr" style="text-align:right;">${r.customerEmail || '—'}</td>
        <td style="font-family:monospace;font-weight:700;">${r.licenseKey || '<span style="color:var(--gold)">ترخيص جديد</span>'}</td>
        <td><span class="badge-pill" style="background:#EFF6FF;color:#1D4ED8;">${r.requestedDuration?.replace('_', ' ') || '30 days'}</span></td>
        <td style="max-width:200px;font-size:12px;">${r.notes || '—'}</td>
        <td style="font-size:11.5px;color:var(--slate);">${r.createdAt ? new Date(r.createdAt).toLocaleDateString('ar-LY') : '—'}</td>
        <td>
          ${r.status === 'approved' ? '<span class="badge-pill" style="background:#D1FAE5;color:#065F46;">تمت الموافقة ✅</span>' :
            r.status === 'rejected' ? '<span class="badge-pill" style="background:#FEE2E2;color:#991B1B;">مرفوض ❌</span>' :
            '<span class="badge-pill" style="background:#FEF3C7;color:#92400E;">قيد الانتظار ⏳</span>'}
        </td>
        <td>
          ${r.status === 'pending' ? `
            <div style="display:flex;gap:4px;">
              <button class="btn btn-success btn-sm" onclick='openApproveModal(${JSON.stringify(r)})'>موافقة وتحديد المدة</button>
              <button class="btn btn-danger btn-sm" onclick="rejectRequest('${r.id}')">رفض</button>
            </div>
          ` : `<span style="font-size:11px;color:var(--slate);">${r.responseNotes || 'مكتمل'}</span>`}
        </td>
      </tr>
    `).join('');

  } catch (e) {
    showToast('فشل جلب طلبات التراخيص', 'error');
  }
}

function openApproveModal(r) {
  document.getElementById('appReqId').value = r.id;
  document.getElementById('appReqSummary').innerHTML = `
    <b>العميل:</b> ${r.customerName} (${r.cafeName})<br>
    <b>الرخصة:</b> ${r.licenseKey || 'ترخيص جديد يتم توليده الآن'}<br>
    <b>المدة التي طلبها:</b> ${r.requestedDuration}
  `;
  openModal('approveReqModal');
}

async function confirmApproveRequest() {
  const requestId = document.getElementById('appReqId').value;
  const days = parseInt(document.getElementById('appReqDays').value) || 30;
  const responseNotes = document.getElementById('appReqNotes').value;

  try {
    const res = await fetch('api.php?action=approve_license_request', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ requestId, days, responseNotes })
    });
    const json = await res.json();
    if (json.ok) {
      showToast(json.message || 'تمت الموافقة وتفعيل الترخيص بنجاح', 'success');
      closeModal('approveReqModal');
      loadLicenseRequests();
    } else {
      showToast(json.error || 'فشلت العملية', 'error');
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

async function rejectRequest(requestId) {
  const reason = prompt('سبب الرفض:');
  if (reason === null) return;

  try {
    const res = await fetch('api.php?action=reject_license_request', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ requestId, reason })
    });
    const json = await res.json();
    if (json.ok) {
      showToast('تم رفض الطلب', 'success');
      loadLicenseRequests();
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

// ==============================================================
// 3. طلبات التعديلات والدعم الفني
// ==============================================================
function openRequestModModal() {
  openModal('reqModModal');
}

async function submitModificationRequest() {
  const title = document.getElementById('reqModTitle').value.trim();
  const details = document.getElementById('reqModDetails').value.trim();

  if (!title || !details) { showToast('يرجى ملء جميع الحقول', 'error'); return; }

  try {
    const res = await fetch('api.php?action=request_modification', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title, details })
    });
    const json = await res.json();
    if (json.ok) {
      showToast(json.message || 'تم إرسال طلب التعديل بنجاح', 'success');
      closeModal('reqModModal');
      document.getElementById('reqModTitle').value = '';
      document.getElementById('reqModDetails').value = '';
      if (document.getElementById('myModsTableBody')) loadModifications();
    } else {
      showToast(json.error || 'فشل الإرسال', 'error');
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

async function loadModifications() {
  try {
    const res = await fetch('api.php?action=list_modifications');
    const json = await res.json();
    if (!json.ok) return;

    const mods = json.modifications || [];

    // للكاستمير: جدول طلباتي
    const myTbody = document.getElementById('myModsTableBody');
    if (myTbody) {
      if (mods.length === 0) {
        myTbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--slate);">لا توجد طلبات تعديلات بعد</td></tr>';
      } else {
        myTbody.innerHTML = mods.map(m => `
          <tr>
            <td><b>${m.title}</b></td>
            <td style="max-width:280px;font-size:12.5px;">${m.details}</td>
            <td style="font-size:11.5px;color:var(--slate);">${m.createdAt ? new Date(m.createdAt).toLocaleDateString('ar-LY') : '—'}</td>
            <td>
              ${m.status === 'completed' ? '<span class="badge-pill" style="background:#D1FAE5;color:#065F46;">تم التنفيذ ✅</span>' :
                m.status === 'in_progress' ? '<span class="badge-pill" style="background:#EFF6FF;color:#1D4ED8;">قيد العمل ⚙️</span>' :
                m.status === 'rejected' ? '<span class="badge-pill" style="background:#FEE2E2;color:#991B1B;">مرفوض ❌</span>' :
                '<span class="badge-pill" style="background:#FEF3C7;color:#92400E;">قيد المراجعة ⏳</span>'}
            </td>
            <td style="font-size:12px;color:var(--navy);font-weight:700;">${m.adminReply || '<span style="color:var(--slate);font-weight:400">بانتظار رد الإدارة</span>'}</td>
          </tr>
        `).join('');
      }
    }

    // للأدمن والمدير: جدول جميع طلبات التعديلات
    const allTbody = document.getElementById('allModsTableBody');
    if (allTbody) {
      const pendingCount = mods.filter(m => m.status === 'pending').length;
      const b = document.getElementById('badgePendingMods');
      if (b) {
        b.style.display = pendingCount > 0 ? 'inline-block' : 'none';
        b.textContent = pendingCount;
      }

      if (mods.length === 0) {
        allTbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--slate);">لا توجد طلبات تعديلات</td></tr>';
      } else {
        allTbody.innerHTML = mods.map(m => `
          <tr>
            <td><b>${m.cafeName}</b><br><small style="color:var(--slate);">${m.customerName}</small></td>
            <td><b>${m.title}</b></td>
            <td style="max-width:260px;font-size:12.5px;">${m.details}</td>
            <td style="font-size:11.5px;color:var(--slate);">${m.createdAt ? new Date(m.createdAt).toLocaleDateString('ar-LY') : '—'}</td>
            <td>
              ${m.status === 'completed' ? '<span class="badge-pill" style="background:#D1FAE5;color:#065F46;">مكتمل ✅</span>' :
                m.status === 'in_progress' ? '<span class="badge-pill" style="background:#EFF6FF;color:#1D4ED8;">جاري العمل ⚙️</span>' :
                m.status === 'rejected' ? '<span class="badge-pill" style="background:#FEE2E2;color:#991B1B;">مرفوض ❌</span>' :
                '<span class="badge-pill" style="background:#FEF3C7;color:#92400E;">جديد ⏳</span>'}
            </td>
            <td style="font-size:12px;">${m.adminReply || '—'}</td>
            <td>
              <button class="btn btn-outline btn-sm" onclick='openReplyModModal(${JSON.stringify(m)})'>رد وتحديث</button>
            </td>
          </tr>
        `).join('');
      }
    }

  } catch (e) {
    showToast('فشل جلب طلبات التعديلات', 'error');
  }
}

function openReplyModModal(m) {
  document.getElementById('replyModId').value = m.id;
  document.getElementById('replyModSummary').innerHTML = `
    <b>المقهى:</b> ${m.cafeName} | <b>العنوان:</b> ${m.title}<br>
    <b>التفاصيل:</b> ${m.details}
  `;
  document.getElementById('replyModStatus').value = m.status || 'in_progress';
  document.getElementById('replyModText').value = m.adminReply || '';
  openModal('replyModModal');
}

async function confirmReplyMod() {
  const modId = document.getElementById('replyModId').value;
  const status = document.getElementById('replyModStatus').value;
  const adminReply = document.getElementById('replyModText').value.trim();

  try {
    const res = await fetch('api.php?action=update_modification', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ modId, status, adminReply })
    });
    const json = await res.json();
    if (json.ok) {
      showToast('تم حفظ التحديث بنجاح', 'success');
      closeModal('replyModModal');
      loadModifications();
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

// ==============================================================
// 4. إدارة التراخيص (للأدمن والمدير)
// ==============================================================
function openNewLicenseModal() {
  document.getElementById('licKeyInput').value = '';
  document.getElementById('licCustomerName').value = '';
  document.getElementById('licCafeName').value = '';
  document.getElementById('licCustomerEmail').value = '';
  document.getElementById('licPhone').value = '';
  document.getElementById('licDurationDays').value = '30';
  document.getElementById('licActive').checked = true;
  document.getElementById('licResetDevice').checked = false;
  document.getElementById('licenseModalTitle').textContent = '➕ توليد ترخيص جديد';
  openModal('licenseModal');
}

function editLicense(l) {
  document.getElementById('licKeyInput').value = l.key;
  document.getElementById('licCustomerName').value = l.customerName || '';
  document.getElementById('licCafeName').value = l.cafeName || '';
  document.getElementById('licCustomerEmail').value = l.customerEmail || '';
  document.getElementById('licPhone').value = l.phone || '';
  document.getElementById('licActive').checked = l.active !== false;
  document.getElementById('licResetDevice').checked = false;
  document.getElementById('licenseModalTitle').textContent = '✏️ تعديل الترخيص: ' + l.key;
  openModal('licenseModal');
}

async function saveLicense() {
  const key = document.getElementById('licKeyInput').value.trim();
  const customerName = document.getElementById('licCustomerName').value.trim();
  const cafeName = document.getElementById('licCafeName').value.trim();
  const customerEmail = document.getElementById('licCustomerEmail').value.trim();
  const phone = document.getElementById('licPhone').value.trim();
  const durationDays = parseInt(document.getElementById('licDurationDays').value) || 0;
  const active = document.getElementById('licActive').checked;
  const resetDevice = document.getElementById('licResetDevice').checked;

  if (!customerName) { showToast('اسم العميل مطلوب', 'error'); return; }

  try {
    const res = await fetch('api.php?action=save_license', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ key, customerName, cafeName, customerEmail, phone, durationDays, active, resetDevice })
    });
    const json = await res.json();
    if (json.ok) {
      showToast('✅ تم حفظ الترخيص بنجاح', 'success');
      closeModal('licenseModal');
      loadLicenses();
    } else {
      showToast(json.error || 'فشل حفظ الترخيص', 'error');
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

async function toggleLicenseActive(key, currentActive) {
  try {
    const res = await fetch('api.php?action=toggle_license', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ key, active: !currentActive })
    });
    const json = await res.json();
    if (json.ok) {
      showToast(currentActive ? 'تم إيقاف الترخيص' : 'تم تفعيل الترخيص', 'success');
      loadLicenses();
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

async function resetDeviceLock(key) {
  if (!confirm('هل تريد فك ربط الجهاز لهذا الترخيص للسماح بتفعيله على جهاز جديد؟')) return;
  try {
    const res = await fetch('api.php?action=toggle_license', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ key, resetDevice: true })
    });
    const json = await res.json();
    if (json.ok) {
      showToast('تم فك ربط الجهاز بنجاح', 'success');
      loadLicenses();
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

async function loadLicenses() {
  const tbody = document.getElementById('licensesTableBody');
  if (!tbody) return;

  try {
    const res = await fetch('api.php?action=list_licenses');
    const json = await res.json();
    if (!json.ok) return;

    const licenses = json.licenses || [];
    
    // حساب مؤشرات الشبكة الحية
    let onlineCount = 0;
    let updatedCount = 0;
    let outdatedCount = 0;
    const now = Date.now();

    licenses.forEach(l => {
      const lastActive = l.lastActiveAt || l.lastValidatedAt || null;
      let lastMillis = 0;
      if (lastActive) {
        lastMillis = typeof lastActive === 'number' ? lastActive : (lastActive.seconds ? lastActive.seconds * 1000 : new Date(lastActive).getTime());
      }
      const isOnline = lastMillis > 0 && (now - lastMillis) <= (10 * 60 * 1000); // خلال آخر 10 دقائق
      if (isOnline) onlineCount++;

      const ver = (l.appVersion || '1.0.1').trim();
      if (ver === '1.0.3') {
        updatedCount++;
      } else {
        outdatedCount++;
      }
    });

    const elTotal = document.getElementById('kpiFleetTotal');
    const elOnline = document.getElementById('kpiFleetOnline');
    const elUpdated = document.getElementById('kpiFleetUpdated');
    const elOutdated = document.getElementById('kpiFleetOutdated');
    if (elTotal) elTotal.textContent = licenses.length;
    if (elOnline) elOnline.textContent = onlineCount;
    if (elUpdated) elUpdated.textContent = updatedCount;
    if (elOutdated) elOutdated.textContent = outdatedCount;

    if (licenses.length === 0) {
      tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:24px;color:var(--slate);">لا توجد تراخيص مسجلة بعد</td></tr>';
      return;
    }

    tbody.innerHTML = licenses.map(l => {
      const exp = l.expiresAt ? new Date(l.expiresAt) : null;
      const isExp = exp && Date.now() > exp.getTime();
      const hasDevice = !!(l.deviceId || l.deviceHash);

      const lastActive = l.lastActiveAt || l.lastValidatedAt || null;
      let lastMillis = 0;
      if (lastActive) {
        lastMillis = typeof lastActive === 'number' ? lastActive : (lastActive.seconds ? lastActive.seconds * 1000 : new Date(lastActive).getTime());
      }
      const isOnline = lastMillis > 0 && (now - lastMillis) <= (10 * 60 * 1000);

      let timeText = 'غير متصل';
      if (lastMillis > 0) {
        const diffSec = Math.floor((now - lastMillis) / 1000);
        if (diffSec < 60) timeText = 'منذ ثوانٍ';
        else if (diffSec < 3600) timeText = `منذ ${Math.floor(diffSec / 60)} دقيقة`;
        else if (diffSec < 86400) timeText = `منذ ${Math.floor(diffSec / 3600)} ساعة`;
        else timeText = `منذ ${Math.floor(diffSec / 86400)} يوم`;
      }

      const ver = (l.appVersion || '1.0.1').trim();
      const isLatestVer = ver === '1.0.3';

      return `
        <tr>
          <td><b style="font-family:monospace;font-size:14px;color:var(--primary);">${l.key}</b></td>
          <td><b>${l.customerName || '—'}</b></td>
          <td>${l.cafeName || '—'}</td>
          <td>
            ${isLatestVer ? 
              `<span class="badge-pill" style="background:#D1FAE5;color:#065F46;font-weight:800;font-size:11.5px;">v${ver} ✅ مُحدَّث</span>` : 
              `<span class="badge-pill" style="background:#FEF3C7;color:#92400E;font-weight:800;font-size:11.5px;">v${ver} ⚠️ قديم</span>`}
          </td>
          <td>
            ${isOnline ? 
              `<span style="display:inline-flex;align-items:center;gap:6px;color:#059669;font-weight:800;font-size:12px;">
                <span style="width:9px;height:9px;border-radius:50%;background:#10B981;box-shadow:0 0 8px #10B981;display:inline-block;"></span> 
                متصل الآن
              </span>` : 
              `<span style="color:var(--slate);font-size:11.5px;display:inline-flex;align-items:center;gap:4px;">
                <span style="width:7px;height:7px;border-radius:50%;background:#CBD5E1;display:inline-block;"></span> 
                ${timeText}
              </span>`}
          </td>
          <td dir="ltr" style="text-align:right;">${l.customerEmail || '—'}</td>
          <td style="font-size:12px;">
            ${exp ? exp.toLocaleDateString('ar-LY') : 'دائم'}
            ${isExp ? '<br><small style="color:var(--red);font-weight:800;">منتهي الصلاحية</small>' : ''}
          </td>
          <td>
            ${l.active !== false && !isExp ? '<span class="badge-pill" style="background:#D1FAE5;color:#065F46;">نشط ✅</span>' :
              isExp ? '<span class="badge-pill" style="background:#FEE2E2;color:#991B1B;">منتهي ⚠️</span>' :
              '<span class="badge-pill" style="background:#F1F5F9;color:var(--slate);">معطل ❌</span>'}
          </td>
          <td style="font-size:11px;">
            ${hasDevice ? '<span style="color:var(--green)">مرتبط 🔒</span>' : '<span style="color:var(--slate)">جاهز 🔓</span>'}
          </td>
          <td>
            <div style="display:flex;gap:4px;">
              <button class="btn btn-outline btn-sm" onclick='editLicense(${JSON.stringify(l)})' title="تعديل">✏️</button>
              <button class="btn ${l.active !== false ? 'btn-danger' : 'btn-success'} btn-sm" onclick="toggleLicenseActive('${l.key}', ${l.active !== false})">
                ${l.active !== false ? 'تعطيل' : 'تفعيل'}
              </button>
              ${hasDevice ? `<button class="btn btn-outline btn-sm" onclick="resetDeviceLock('${l.key}')" title="فك ربط الجهاز">فك القفل</button>` : ''}
            </div>
          </td>
        </tr>
      `;
    }).join('');

  } catch (e) {
    showToast('فشل جلب التراخيص', 'error');
  }
}

// ==============================================================
// 5. إدارة حسابات وإيميلات العملاء (للأدمن)
// ==============================================================
function openNewUserModal() {
  document.getElementById('uName').value = '';
  document.getElementById('uEmail').value = '';
  document.getElementById('uPassword').value = '123456';
  document.getElementById('uRole').value = 'customer';
  document.getElementById('uCafeName').value = '';
  document.getElementById('uLicenseKey').value = '';
  document.getElementById('uPhone').value = '';
  openModal('userModal');
}

async function saveUser() {
  const name = document.getElementById('uName').value.trim();
  const email = document.getElementById('uEmail').value.trim();
  const password = document.getElementById('uPassword').value;
  const role = document.getElementById('uRole').value;
  const cafeName = document.getElementById('uCafeName').value.trim();
  const licenseKey = document.getElementById('uLicenseKey').value.trim();
  const phone = document.getElementById('uPhone').value.trim();

  if (!email || !name) { showToast('الاسم والبريد الإلكتروني مطلوبان', 'error'); return; }

  try {
    const res = await fetch('api.php?action=save_user', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email, password, role, cafeName, licenseKey, phone })
    });
    const json = await res.json();
    if (json.ok) {
      showToast(json.message || 'تم حفظ الحساب بنجاح', 'success');
      closeModal('userModal');
      loadUsers();
    } else {
      showToast(json.error || 'فشل حفظ الحساب', 'error');
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

async function deleteUser(email) {
  if (!confirm('هل تريد حذف حساب المستخدم ' + email + '؟')) return;
  try {
    const res = await fetch('api.php?action=delete_user', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    const json = await res.json();
    if (json.ok) {
      showToast('تم حذف الحساب بنجاح', 'success');
      loadUsers();
    }
  } catch (e) {
    showToast('خطأ في الاتصال', 'error');
  }
}

async function loadUsers() {
  const tbody = document.getElementById('usersTableBody');
  if (!tbody) return;

  try {
    const res = await fetch('api.php?action=list_users');
    const json = await res.json();
    if (!json.ok) return;

    const users = json.users || [];
    if (users.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--slate);">لا توجد حسابات مسجلة</td></tr>';
      return;
    }

    tbody.innerHTML = users.map(u => `
      <tr>
        <td><b>${u.name || '—'}</b></td>
        <td dir="ltr" style="text-align:right;">${u.email}</td>
        <td>
          <span class="badge-pill ${u.role === 'admin' ? 'role-admin' : u.role === 'manager' ? 'role-manager' : 'role-customer'}">
            ${u.role === 'admin' ? '👑 أدمن' : u.role === 'manager' ? '👔 مدير' : '☕ عميل / مقهى'}
          </span>
        </td>
        <td>${u.cafeName || '—'}</td>
        <td style="font-family:monospace;font-weight:700;">${u.licenseKey || '<span style="color:var(--slate)">غير مرتبط</span>'}</td>
        <td>${u.phone || '—'}</td>
        <td>
          ${u.email !== 'admin@cafepos.com' ? `
            <button class="btn btn-danger btn-sm" onclick="deleteUser('${u.email}')">حذف 🗑️</button>
          ` : '<span style="font-size:11px;color:var(--slate)">حساب رئيسي</span>'}
        </td>
      </tr>
    `).join('');

  } catch (e) {
    showToast('فشل جلب المستخدمين', 'error');
  }
}

// ==============================================================
// 6. نظرة عامة على المقاهي (أدمن / مدير)
// ==============================================================
async function loadAllBranches() {
  const tbody = document.getElementById('allBranchesTableBody');
  if (!tbody) return;

  try {
    const res = await fetch('api.php?action=list_all_branches');
    const json = await res.json();
    if (!json.ok) return;

    const branches = json.branches || [];
    if (branches.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--slate);">لا توجد مقاهي مسجلة بعد</td></tr>';
      return;
    }

    tbody.innerHTML = branches.map(b => `
      <tr>
        <td><b>${b.cafeName}</b></td>
        <td style="font-family:monospace;font-weight:700;">${b.branchId}</td>
        <td>${b.customerName}</td>
        <td style="font-weight:800;color:var(--green);">${(b.todaySales || 0).toLocaleString('ar')} د.ل</td>
        <td>${b.ordersCount || 0}</td>
        <td>${b.productsCount || 0} صنف</td>
        <td>
          ${b.licenseActive ? '<span class="badge-pill" style="background:#D1FAE5;color:#065F46;">نشطة ✅</span>' :
            '<span class="badge-pill" style="background:#FEE2E2;color:#991B1B;">غير نشطة ⚠️</span>'}
        </td>
        <td>
          <button class="btn btn-primary btn-sm" onclick="viewBranchProducts('${b.branchId}')">إدارة الأصناف 📦</button>
        </td>
      </tr>
    `).join('');

  } catch (e) {
    showToast('فشل جلب بيانات المقاهي', 'error');
  }
}

function viewBranchProducts(branchId) {
  currentSelectedBranchId = branchId;
  switchView('my_products');
}

// تسجيل الخروج
async function logout() {
  if (!confirm('هل تريد تسجيل الخروج؟')) return;
  await fetch('api.php?action=logout');
  window.location.href = 'index.php';
}

// ===== بدء التشغيل التلقائي =====
if (USER_ROLE === 'customer') {
  switchView('my_products');
} else if (USER_ROLE === 'manager') {
  switchView('license_requests');
} else {
  switchView('licenses');
}

// فحص دوري كل 60 ثانية لتحديث العدادات والطلبات
setInterval(() => {
  if (USER_ROLE !== 'customer') {
    loadLicenseRequests();
    loadModifications();
  }
}, 60000);
</script>

</body>
</html>
