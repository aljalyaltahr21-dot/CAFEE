<?php
// ===========================================================
// شاشة نقطة البيع السحابية الفاخرة (Cloud Coffee Web POS)
// تصميم عصري متكامل مستوحى من أحدث واجهات المقاهي العالمية
// ===========================================================

session_start();
$user = $_SESSION['portal_user'] ?? null;
if (!$user) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>نقطة البيع السحابية | Coffee POS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Pacifico&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --primary: #63262E;          /* لون البرغندي / القهوة المركز */
      --primary-hover: #4e1d24;
      --primary-light: #FDF2F0;
      --primary-border: #E8D4D2;
      --cream-bg: #F7F3F0;          /* لون الخلفية الدافئ */
      --card-bg: #FFFFFF;
      --text-main: #231815;
      --text-muted: #7E706D;
      --text-light: #A89B98;
      --border: #EDE5E2;
      --border-light: #F4EFEB;
      --accent: #D97706;
      --success: #10B981;
      --danger: #EF4444;
      --shadow-sm: 0 2px 8px rgba(99, 38, 46, 0.04);
      --shadow-md: 0 8px 24px rgba(99, 38, 46, 0.08);
      --shadow-lg: 0 20px 50px rgba(99, 38, 46, 0.12);
      --radius-sm: 10px;
      --radius-md: 16px;
      --radius-lg: 24px;
      --radius-full: 9999px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
      -webkit-tap-highlight-color: transparent;
    }

    body {
      background: #FAFAF9;
      margin: 0;
      padding: 0;
      width: 100vw;
      height: 100vh;
      overflow: hidden;
      color: var(--text-main);
    }

    /* الحاوية الأساسية للتطبيق بكامل كبر الشاشة */
    .app-viewport {
      width: 100vw;
      height: 100vh;
      background: #FAFAF9;
      border-radius: 0;
      box-shadow: none;
      display: grid;
      grid-template-columns: 88px 1fr 390px;
      overflow: hidden;
      border: none;
    }

    /* ================= 1. القائمة الجانبية (Sidebar) ================= */
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
      margin-bottom: 32px;
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
      gap: 16px;
      width: 100%;
      align-items: center;
      flex: 1;
    }

    .nav-item {
      width: 62px;
      height: 62px;
      border-radius: var(--radius-md);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 4px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 11px;
      font-weight: 700;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      border: none;
      background: transparent;
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

    .sidebar-bottom {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      padding-top: 12px;
      border-top: 1px solid var(--border-light);
    }

    /* ================= 2. منطقة المنتجات والتصنيفات (Main Area) ================= */
    .main-area {
      background: #F9F7F5;
      padding: 24px 28px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 22px;
    }

    /* رأس الصفحة والبحث */
    .main-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }

    .header-title-box h1 {
      font-size: 22px;
      font-weight: 800;
      color: var(--text-main);
      letter-spacing: -0.3px;
    }

    .search-box {
      position: relative;
      width: 320px;
    }

    .search-input {
      width: 100%;
      height: 46px;
      background: #FFFFFF;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-full);
      padding: 0 46px 0 18px;
      font-size: 13.5px;
      color: var(--text-main);
      outline: none;
      transition: all 0.2s ease;
      box-shadow: var(--shadow-sm);
    }

    .search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(99, 38, 46, 0.12);
    }

    .search-icon {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      width: 18px;
      height: 18px;
      stroke: var(--text-light);
      fill: none;
      pointer-events: none;
    }

    /* شريط التصنيفات */
    .categories-bar {
      display: flex;
      gap: 12px;
      overflow-x: auto;
      padding-bottom: 4px;
      scrollbar-width: none;
    }
    .categories-bar::-webkit-scrollbar { display: none; }

    .category-card {
      min-width: 86px;
      height: 86px;
      background: #FFFFFF;
      border: 1.5px solid var(--border);
      border-radius: 18px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 6px;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: var(--shadow-sm);
      user-select: none;
    }

    .category-card .cat-icon {
      font-size: 24px;
      line-height: 1;
      transition: transform 0.2s;
    }

    .category-card .cat-label {
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
    }

    .category-card:hover {
      border-color: var(--primary-border);
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .category-card.active {
      border-color: var(--primary);
      background: #FFFFFF;
      box-shadow: 0 6px 18px rgba(99, 38, 46, 0.15);
      position: relative;
    }

    .category-card.active::after {
      content: "";
      position: absolute;
      inset: 2px;
      border: 1.5px solid var(--primary);
      border-radius: 15px;
      pointer-events: none;
    }

    .category-card.active .cat-label {
      color: var(--primary);
      font-weight: 800;
    }

    /* ترويسة قسم المنتجات */
    .section-headline {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 4px;
    }

    .section-headline h2 {
      font-size: 18px;
      font-weight: 800;
      color: var(--text-main);
    }

    .section-headline .results-count {
      font-size: 12.5px;
      font-weight: 600;
      color: var(--text-light);
    }

    /* شبكة المنتجات (Cards Grid) */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(285px, 1fr));
      gap: 20px;
    }

    .product-card {
      background: #FFFFFF;
      border-radius: var(--radius-lg);
      padding: 16px;
      border: 1.5px solid var(--border-light);
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      gap: 14px;
      transition: all 0.25s ease;
      position: relative;
    }

    .product-card:hover {
      box-shadow: var(--shadow-md);
      border-color: var(--primary-border);
      transform: translateY(-3px);
    }

    /* الرأس: صورة المنتج والاسم والسعر */
    .card-top {
      display: flex;
      gap: 14px;
      align-items: flex-start;
    }

    .card-img-wrap {
      width: 86px;
      height: 86px;
      border-radius: 16px;
      overflow: hidden;
      flex-shrink: 0;
      background: #F4EFEB;
      position: relative;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .card-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s;
    }

    .product-card:hover .card-img-wrap img {
      transform: scale(1.06);
    }

    .card-meta {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .card-title {
      font-size: 15px;
      font-weight: 800;
      color: var(--text-main);
      line-height: 1.3;
    }

    .card-desc {
      font-size: 11.5px;
      color: var(--text-muted);
      line-height: 1.35;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .card-price {
      font-size: 16px;
      font-weight: 900;
      color: var(--primary);
      margin-top: 4px;
    }

    /* خيارات التخصيص داخل الكرت (Mood, Size, Sugar, Ice) */
    .options-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      background: #FDFBF9;
      border: 1px solid var(--border-light);
      border-radius: 14px;
      padding: 10px 12px;
    }

    .option-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .option-label {
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
    }

    .pill-selector {
      display: flex;
      gap: 4px;
      align-items: center;
    }

    .pill-btn {
      flex: 1;
      height: 26px;
      border-radius: var(--radius-full);
      border: 1px solid var(--border);
      background: #FFFFFF;
      color: var(--text-muted);
      font-size: 11px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s ease;
      padding: 0 4px;
    }

    .pill-btn:hover {
      border-color: var(--primary);
      color: var(--primary);
    }

    .pill-btn.active {
      background: var(--primary);
      color: #FFFFFF;
      border-color: var(--primary);
      box-shadow: 0 2px 6px rgba(99, 38, 46, 0.25);
    }

    /* زر الإضافة للفاتورة */
    .btn-add-bill {
      width: 100%;
      height: 42px;
      background: var(--primary);
      color: #FFFFFF;
      border: none;
      border-radius: 14px;
      font-size: 13.5px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(99, 38, 46, 0.22);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-add-bill:hover {
      background: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(99, 38, 46, 0.32);
    }

    .btn-add-bill:active {
      transform: translateY(1px);
    }

    /* ================= 3. لوحة الفاتورة والطلبات (Bills / Cart) ================= */
    .bills-panel {
      background: #FFFFFF;
      border-right: 1.5px solid var(--border-light);
      display: flex;
      flex-direction: column;
      padding: 24px 22px;
      height: 100%;
      overflow: hidden;
    }

    /* معلومات الكاشير والتنبيهات */
    .cashier-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .cashier-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .cashier-avatar {
      width: 44px;
      height: 44px;
      border-radius: 14px;
      background: #F4EFEB;
      overflow: hidden;
      border: 1.5px solid var(--primary-border);
    }

    .cashier-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .cashier-name-box .role {
      font-size: 11px;
      font-weight: 600;
      color: var(--text-light);
    }

    .cashier-name-box .name {
      font-size: 14px;
      font-weight: 800;
      color: var(--text-main);
    }

    .bell-btn {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      border: 1.5px solid var(--border);
      background: #FFFFFF;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      position: relative;
      color: var(--text-muted);
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
      background: var(--danger);
      position: absolute;
      top: 8px;
      right: 8px;
      border: 1.5px solid #FFFFFF;
    }

    .bills-title {
      font-size: 20px;
      font-weight: 900;
      color: var(--text-main);
      margin-bottom: 14px;
    }

    /* قائمة الطلبات الحالية */
    .order-items-list {
      flex: 1;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 14px;
      padding-left: 4px;
      margin-bottom: 16px;
    }

    .order-item-card {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px;
      border-radius: 14px;
      background: #FAFAFA;
      border: 1px solid var(--border-light);
      transition: all 0.15s;
    }

    .order-item-card:hover {
      background: #FDFBF9;
      border-color: var(--border);
    }

    .order-item-thumb {
      width: 52px;
      height: 52px;
      border-radius: 12px;
      overflow: hidden;
      flex-shrink: 0;
      background: #EFE8E5;
    }

    .order-item-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .order-item-details {
      flex: 1;
      min-width: 0;
    }

    .order-item-name {
      font-size: 13.5px;
      font-weight: 800;
      color: var(--text-main);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .order-item-mods {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 2px;
      line-height: 1.2;
    }

    .order-item-qty {
      font-size: 12px;
      font-weight: 800;
      color: var(--primary);
    }

    .order-item-notes {
      font-size: 11px;
      color: var(--accent);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      margin-top: 2px;
      font-weight: 600;
    }

    .order-item-right {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 6px;
    }

    .order-item-price {
      font-size: 14px;
      font-weight: 900;
      color: var(--text-main);
    }

    .qty-controls {
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .qty-btn {
      width: 22px;
      height: 22px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: #FFFFFF;
      color: var(--text-main);
      font-size: 12px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }

    .qty-btn:hover {
      background: var(--primary-light);
      color: var(--primary);
      border-color: var(--primary);
    }

    /* الحسابات والملخص المالي */
    .bill-summary {
      border-top: 1.5px dashed var(--border);
      padding-top: 14px;
      margin-bottom: 16px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .summary-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 13px;
      color: var(--text-muted);
      font-weight: 600;
    }

    .summary-row.total {
      font-size: 17px;
      font-weight: 900;
      color: var(--text-main);
      padding-top: 6px;
      border-top: 1px solid var(--border-light);
      margin-top: 4px;
    }

    .summary-row.total .total-amount {
      color: var(--primary);
      font-size: 19px;
    }

    /* طرق الدفع (Payment Methods) */
    .payment-section {
      margin-bottom: 16px;
    }

    .payment-title {
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      margin-bottom: 8px;
    }

    .payment-methods-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
    }

    .pay-method-btn {
      height: 52px;
      border-radius: 14px;
      border: 1.5px solid var(--border);
      background: #FFFFFF;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 4px;
      cursor: pointer;
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      transition: all 0.15s ease;
    }

    .pay-method-btn svg {
      width: 18px;
      height: 18px;
      stroke: currentColor;
      fill: none;
    }

    .pay-method-btn:hover {
      border-color: var(--primary-border);
      color: var(--primary);
    }

    .pay-method-btn.active {
      border-color: var(--primary);
      background: var(--primary-light);
      color: var(--primary);
      font-weight: 800;
      box-shadow: 0 2px 8px rgba(99, 38, 46, 0.12);
    }

    /* زر الطباعة وتأكيد الفاتورة */
    .btn-print-bill {
      width: 100%;
      height: 48px;
      background: var(--primary);
      color: #FFFFFF;
      border: none;
      border-radius: 16px;
      font-size: 15px;
      font-weight: 800;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 6px 18px rgba(99, 38, 46, 0.28);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-print-bill:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(99, 38, 46, 0.35);
    }

    .btn-print-bill:active {
      transform: translateY(1px);
    }

    /* النوافذ المنبثقة (Modals) */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(35, 24, 21, 0.55);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      padding: 16px;
    }

    .modal-overlay.open {
      display: flex;
    }

    .modal-card {
      background: #FFFFFF;
      width: 100%;
      max-width: 440px;
      border-radius: var(--radius-lg);
      padding: 24px;
      box-shadow: var(--shadow-lg);
      animation: modalSlide 0.25s ease-out;
    }

    @keyframes modalSlide {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    /* إيصال الطباعة */
    .receipt-print-area {
      background: #FFFDF9;
      border: 1px dashed #CBD5E1;
      border-radius: 12px;
      padding: 18px;
      font-family: monospace;
      font-size: 13px;
      color: #1E293B;
      margin: 16px 0;
    }

    /* ============================================================
       Responsive — Tablet (≤1100px)
    ============================================================ */
    @media (max-width: 1100px) {
      .app-viewport {
        grid-template-columns: 78px 1fr 320px;
      }
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      }
    }

    /* ============================================================
       Responsive — Mobile (≤768px)
       POS layout: sidebar hidden, cart as bottom sheet toggle
    ============================================================ */
    @media (max-width: 768px) {
      body { overflow: hidden; height: 100vh; }

      .app-viewport {
        display: flex;
        flex-direction: column;
        height: 100vh;
      }

      /* إخفاء القائمة الجانبية */
      .sidebar { display: none; }

      /* المنطقة الرئيسية تأخذ كل المساحة */
      .main-area {
        flex: 1;
        overflow-y: auto;
        padding: 14px;
        gap: 14px;
        -webkit-overflow-scrolling: touch;
      }

      /* الهيدر أصغر */
      .main-header {
        flex-wrap: wrap;
        gap: 10px;
      }
      .header-title-box h1 { font-size: 17px; }
      .search-box { width: 100%; }

      /* التصنيفات بحجم أصغر */
      .category-card {
        min-width: 70px;
        height: 70px;
        border-radius: 14px;
      }
      .category-card .cat-icon { font-size: 20px; }
      .category-card .cat-label { font-size: 11px; }

      /* شبكة المنتجات — عمود واحد */
      .products-grid {
        grid-template-columns: 1fr;
        gap: 12px;
      }

      /* حجم أصغر للبطاقات */
      .card-img-wrap {
        width: 70px;
        height: 70px;
      }
      .card-title { font-size: 14px; }
      .card-price { font-size: 14px; }

      /* لوحة الفاتورة تظهر كـ bottom sheet عند الفتح */
      .bills-panel {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 75vh;
        border-radius: 24px 24px 0 0;
        border-right: none;
        border-top: 1.5px solid var(--border-light);
        transform: translateY(100%);
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 200;
        box-shadow: 0 -8px 30px rgba(0,0,0,0.15);
      }
      .bills-panel.cart-open {
        transform: translateY(0);
      }

      /* زر إظهار/إخفاء السلة (FAB) */
      .cart-fab {
        display: flex !important;
      }

      /* overlay خلف السلة */
      .cart-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(35, 24, 21, 0.4);
        backdrop-filter: blur(2px);
        z-index: 190;
      }
      .cart-overlay.open { display: block; }
    }

    /* ============================================================
       Responsive — Very Small Mobile (≤430px)
    ============================================================ */
    @media (max-width: 430px) {
      .options-grid {
        grid-template-columns: 1fr;
      }
      .payment-methods-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    /* زر الـ FAB (Floating Action Button) للسلة — مخفي على الديسكتوب */
    .cart-fab {
      display: none;
      position: fixed;
      bottom: 24px;
      left: 24px;
      z-index: 180;
      width: 58px;
      height: 58px;
      border-radius: 50%;
      background: var(--primary);
      color: #fff;
      border: none;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      box-shadow: 0 6px 20px rgba(99, 38, 46, 0.4);
      cursor: pointer;
      transition: transform 0.2s;
    }
    .cart-fab:hover { transform: scale(1.07); }
    .cart-fab-badge {
      position: absolute;
      top: -4px;
      right: -4px;
      background: #EF4444;
      color: #fff;
      font-size: 11px;
      font-weight: 900;
      border-radius: 50%;
      width: 22px;
      height: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid #fff;
    }
  </style>
</head>
<body>

  <!-- الحاوية الأساسية لتطبيق نقطة البيع -->

  <!-- زر السلة العائم (جوال فقط) -->
  <button class="cart-fab" id="cartFab" onclick="toggleMobileCart()" title="السلة">
    🛒
    <span class="cart-fab-badge" id="cartFabBadge" style="display:none">0</span>
  </button>
  <!-- overlay خلف السلة -->
  <div class="cart-overlay" id="cartOverlay" onclick="toggleMobileCart()"></div>

  <div class="app-viewport">

    <!-- 1. الشريط الجانبي (Sidebar) -->
    <aside class="sidebar">
      <a href="dashboard.php" class="brand-logo" title="العودة للوحة الإدارة">coffee</a>

      <ul class="nav-list">
        <li>
          <a href="dashboard.php" class="nav-item" title="الرئيسية">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>الرئيسية</span>
          </a>
        </li>
        <li>
          <button class="nav-item active" title="القائمة">
            <svg viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
            <span>القائمة</span>
          </button>
        </li>
        <li>
          <button class="nav-item" onclick="openHistoryModal()" title="السجل">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span>السجل</span>
          </button>
        </li>
        <li>
          <a href="dashboard.php" class="nav-item" title="الخزينة">
            <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            <span>الخزينة</span>
          </a>
        </li>
        <li>
          <button class="nav-item" onclick="alert('قسم العروض والكوبونات قيد التفعيل!')" title="العروض">
            <svg viewBox="0 0 24 24"><line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>
            <span>العروض</span>
          </button>
        </li>
        <li>
          <a href="dashboard.php" class="nav-item" title="الإعدادات">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <span>الإعدادات</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-bottom">
        <a href="api.php?action=logout" class="nav-item" title="تسجيل الخروج">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          <span>خروج</span>
        </a>
      </div>
    </aside>

    <!-- 2. منطقة المنتجات والتصنيفات (Main Workspace) -->
    <main class="main-area">
      <!-- ترويسة البحث -->
      <div class="main-header">
        <div class="header-title-box">
          <h1>اختر التصنيف</h1>
        </div>
        <div class="search-box">
          <input type="text" id="searchInput" class="search-input" placeholder="ابحث في الأصناف أو القائمة..." oninput="handleSearch(this.value)" />
          <svg class="search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </div>
      </div>

      <!-- شريط التصنيفات (Category Bar) -->
      <div class="categories-bar" id="categoriesBar">
        <div class="category-card active" onclick="selectCategory('all', this)">
          <span class="cat-icon">🍻</span>
          <span class="cat-label">الكل</span>
        </div>
        <div class="category-card" onclick="selectCategory('قهوة', this)">
          <span class="cat-icon">☕</span>
          <span class="cat-label">قهوة</span>
        </div>
        <div class="category-card" onclick="selectCategory('عصائر', this)">
          <span class="cat-icon">🍹</span>
          <span class="cat-label">عصائر</span>
        </div>
        <div class="category-card" onclick="selectCategory('حليب', this)">
          <span class="cat-icon">🥛</span>
          <span class="cat-label">حليب</span>
        </div>
        <div class="category-card" onclick="selectCategory('سناكس', this)">
          <span class="cat-icon">🥞</span>
          <span class="cat-label">سناكس</span>
        </div>
        <div class="category-card" onclick="selectCategory('وجبات', this)">
          <span class="cat-icon">🍛</span>
          <span class="cat-label">وجبات</span>
        </div>
        <div class="category-card" onclick="selectCategory('حلويات', this)">
          <span class="cat-icon">🍰</span>
          <span class="cat-label">حلويات</span>
        </div>
      </div>

      <!-- عنوان القسم والنتائج -->
      <div class="section-headline">
        <h2 id="sectionTitle">قائمة القهوة</h2>
        <span class="results-count" id="resultsCount">12 صنف متوفر</span>
      </div>

      <!-- شبكة المنتجات (Products Grid) -->
      <div class="products-grid" id="productsGrid">
        <!-- يتم ملؤها ديناميكياً بواسطة JavaScript -->
      </div>
    </main>

    <!-- 3. لوحة الفاتورة والطلبات (Bills Panel) -->
    <aside class="bills-panel">
      <!-- معلومات الكاشير والتنبيهات -->
      <div class="cashier-bar">
        <div class="cashier-info">
          <div class="cashier-avatar">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="الكاشير" />
          </div>
          <div class="cashier-name-box">
            <div class="role">أنا الكاشير 🧑‍🍳</div>
            <div class="name"><?= htmlspecialchars($user['name'] ?? 'طاهر الجالي') ?></div>
          </div>
        </div>

        <button class="bell-btn" title="التنبيهات" onclick="showToast('لا توجد تنبيهات جديدة')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          <span class="dot"></span>
        </button>
      </div>

      <h2 class="bills-title">الفاتورة</h2>

      <!-- قائمة عناصر الفاتورة -->
      <div class="order-items-list" id="orderItemsList">
        <div style="text-align:center;padding:40px 10px;color:var(--text-light);font-size:13.5px;">
          <span>☕</span><br>لم تتم إضافة أي طلبات للفاتورة بعد
        </div>
      </div>

      <!-- الملخص المالي -->
      <div class="bill-summary">
        <div class="summary-row">
          <span>المجموع الفرعي:</span>
          <span id="billSubtotal">0.000 د.ل</span>
        </div>
        <div class="summary-row">
          <span>الضريبة / الخدمة (10%):</span>
          <span id="billTax">0.000 د.ل</span>
        </div>
        <div class="summary-row total">
          <span>المجموع الكلي:</span>
          <span class="total-amount" id="billTotal">0.000 د.ل</span>
        </div>
      </div>

      <!-- طريقة الدفع (Payment Method) -->
      <div class="payment-section">
        <div class="payment-title">طريقة الدفع</div>
        <div class="payment-methods-grid">
          <button type="button" class="pay-method-btn" onclick="setPaymentMethod('cash', this)">
            <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path></svg>
            <span>نقدي</span>
          </button>
          <button type="button" class="pay-method-btn active" onclick="setPaymentMethod('card', this)">
            <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            <span>بطاقة مصرفية</span>
          </button>
          <button type="button" class="pay-method-btn" onclick="setPaymentMethod('wallet', this)">
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            <span>محفظة / سداد</span>
          </button>
        </div>
      </div>

      <!-- زر تأكيد وطباعة الفاتورة -->
      <button class="btn-print-bill" onclick="checkoutAndPrint()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        <span>طباعة الفاتورة</span>
      </button>
    </aside>

  </div>

  <!-- نافذة إضافة ملاحظة للطلب (Notes Modal) -->
  <div class="modal-overlay" id="noteModal">
    <div class="modal-card">
      <h3 style="font-size:16px;font-weight:800;margin-bottom:12px;">إضافة ملاحظات خاصة بالصنف</h3>
      <textarea id="noteInput" rows="3" style="width:100%;border:1.5px solid var(--border);border-radius:12px;padding:12px;font-size:13px;outline:none;" placeholder="مثال: حليب لوز، بدون كريمة، إكسترا شوت اسبريسو..."></textarea>
      <div style="display:flex;gap:10px;margin-top:16px;">
        <button class="btn-print-bill" style="flex:1;height:40px;font-size:13px;" onclick="saveItemNote()">حفظ الملاحظة</button>
        <button type="button" style="flex:1;height:40px;border-radius:12px;border:1px solid var(--border);background:#fff;font-weight:700;cursor:pointer;" onclick="closeNoteModal()">إلغاء</button>
      </div>
    </div>
  </div>

  <!-- نافذة الفاتورة والطباعة (Receipt Modal) -->
  <div class="modal-overlay" id="receiptModal">
    <div class="modal-card">
      <div style="text-align:center;">
        <div style="font-size:32px;">☕</div>
        <h3 style="font-size:18px;font-weight:900;color:var(--primary);margin-top:4px;"><?= htmlspecialchars($user['cafeName'] ?? 'كافيه دي بوينت') ?></h3>
        <p style="font-size:11.5px;color:var(--text-muted);">فاتورة مبيعات ضريبية مبسطة</p>
      </div>

      <div class="receipt-print-area" id="receiptContent">
        <!-- محتوى الفاتورة المطبوعة -->
      </div>

      <div style="display:flex;gap:10px;margin-top:16px;">
        <button class="btn-print-bill" style="flex:1;height:42px;font-size:13.5px;" onclick="printReceiptNow()">
          🖨️ طباعة الآن (Print)
        </button>
        <button type="button" style="height:42px;padding:0 20px;border-radius:14px;border:1.5px solid var(--border);background:#fff;font-weight:700;cursor:pointer;" onclick="closeReceiptModal()">
          إغلاق
        </button>
      </div>
    </div>
  </div>

  <script>
    // ==========================================
    // قاعدة بيانات الأصناف والقائمة المصورة
    // ==========================================
    const DEFAULT_CATALOG = [
      {
        id: 1,
        name: "كراميل فرابتشينو",
        nameEn: "Caramel Frappuccino",
        desc: "سيروب كراميل ذهبي مع اسبريسو وحليب وكريمة مخفوقة غنية",
        price: 3.95,
        category: "قهوة",
        image: "https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: ["🔥", "❄️"],
          sizes: ["S", "M", "L"],
          sugar: ["30%", "50%", "70%"],
          ice: ["30%", "50%", "70%"]
        }
      },
      {
        id: 2,
        name: "شوكولاتة فرابتشينو",
        nameEn: "Chocolate Frappuccino",
        desc: "شوكولاتة داكنة غنية مع قهوة مثلجة وكريمة شوكولا",
        price: 4.51,
        category: "قهوة",
        image: "https://images.unsplash.com/photo-1541167760496-1628856ab772?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: ["🔥", "❄️"],
          sizes: ["S", "M", "L"],
          sugar: ["30%", "50%", "70%"],
          ice: ["30%", "50%", "70%"]
        }
      },
      {
        id: 3,
        name: "نعناع ماكياتو",
        nameEn: "Peppermint Macchiato",
        desc: "نعناع منعش مع قهوة اسبريسو وكريمة حليب مخفوقة",
        price: 5.34,
        category: "قهوة",
        image: "https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: ["🔥", "❄️"],
          sizes: ["S", "M", "L"],
          sugar: ["30%", "50%", "70%"],
          ice: ["30%", "50%", "70%"]
        }
      },
      {
        id: 4,
        name: "كافيه لاتيه فرابتشينو",
        nameEn: "Coffee Latte Frappuccino",
        desc: "خلطة اسبريسو خاصة مع كريمة الشوكولاتة وحليب طازج",
        price: 4.79,
        category: "قهوة",
        image: "https://images.unsplash.com/photo-1534778101976-62847782c213?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: ["🔥", "❄️"],
          sizes: ["S", "M", "L"],
          sugar: ["30%", "50%", "70%"],
          ice: ["30%", "50%", "70%"]
        }
      },
      {
        id: 5,
        name: "اسبريسو كلاسيك",
        nameEn: "Classic Espresso",
        desc: "جرعة اسبريسو نقية وموزونة برغوة ذهبية دافئة",
        price: 2.50,
        category: "قهوة",
        image: "https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: ["🔥"],
          sizes: ["S", "M"],
          sugar: ["0%", "50%", "100%"],
          ice: []
        }
      },
      {
        id: 6,
        name: "كابتشينو إيطالي",
        nameEn: "Italian Cappuccino",
        desc: "توازن مثالي بين الإسبريسو ورغوة الحليب الناعمة",
        price: 3.50,
        category: "قهوة",
        image: "https://images.unsplash.com/photo-1577968897966-3d4325b36b61?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: ["🔥", "❄️"],
          sizes: ["S", "M", "L"],
          sugar: ["30%", "50%", "70%"],
          ice: ["30%", "50%"]
        }
      },
      {
        id: 7,
        name: "عصير برتقال طبيعي",
        nameEn: "Fresh Orange Juice",
        desc: "عصير برتقال طازج 100% معصور فورياً",
        price: 3.80,
        category: "عصائر",
        image: "https://images.unsplash.com/photo-1613478223719-2ab802602423?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: ["❄️"],
          sizes: ["M", "L"],
          sugar: ["0%", "30%", "50%"],
          ice: ["30%", "50%", "70%"]
        }
      },
      {
        id: 8,
        name: "كولد برو منعش",
        nameEn: "Cold Brew Special",
        desc: "قهوة مقطرة ببطء على البارد لمدة 18 ساعة نكهة سلسة",
        price: 4.90,
        category: "قهوة",
        image: "https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: ["❄️"],
          sizes: ["M", "L"],
          sugar: ["0%", "30%", "50%"],
          ice: ["50%", "70%"]
        }
      },
      {
        id: 9,
        name: "تشيز كيك التوت",
        nameEn: "Berry Cheesecake",
        desc: "قطعة تشيز كيك نيويورك مخبوزة مع صوص التوت البري",
        price: 5.50,
        category: "حلويات",
        image: "https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: [],
          sizes: ["M"],
          sugar: [],
          ice: []
        }
      },
      {
        id: 10,
        name: "كروسان بالزبدة",
        nameEn: "Butter Croissant",
        desc: "كروسان فرنسي طازج مقرمش بالزبدة الطبيعية",
        price: 3.00,
        category: "سناكس",
        image: "https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=400&auto=format&fit=crop&q=80",
        options: {
          mood: ["🔥"],
          sizes: ["M"],
          sugar: [],
          ice: []
        }
      }
    ];

    // ==========================================
    // حالة التطبيق التفاعلية (State)
    // ==========================================
    const state = {
      catalog: [...DEFAULT_CATALOG],
      currentCategory: "all",
      searchQuery: "",
      cart: [],
      selectedPayment: "card",
      currentNoteIndex: null,
      selections: {} // حفظ اختيارات كل كرت (mood, size, sugar, ice)
    };

    // تهيئة الاختيارات الافتراضية لكل منتج
    function initSelections() {
      state.catalog.forEach(p => {
        if (!state.selections[p.id]) {
          state.selections[p.id] = {
            mood: p.options.mood[0] || "",
            size: p.options.sizes[1] || p.options.sizes[0] || "M",
            sugar: p.options.sugar[1] || p.options.sugar[0] || "50%",
            ice: p.options.ice[1] || p.options.ice[0] || "50%"
          };
        }
      });
    }

    // ==========================================
    // رسم المنتجات في الشبكة
    // ==========================================
    function renderProducts() {
      const grid = document.getElementById("productsGrid");
      const titleEl = document.getElementById("sectionTitle");
      const countEl = document.getElementById("resultsCount");

      let filtered = state.catalog;

      // فلترة التصنيف
      if (state.currentCategory !== "all") {
        filtered = filtered.filter(p => p.category.includes(state.currentCategory));
      }

      // فلترة البحث
      if (state.searchQuery.trim()) {
        const q = state.searchQuery.toLowerCase().trim();
        filtered = filtered.filter(p => 
          p.name.toLowerCase().includes(q) || 
          p.nameEn.toLowerCase().includes(q) || 
          p.desc.toLowerCase().includes(q)
        );
      }

      countEl.textContent = `${filtered.length} صنف متوفر`;

      if (filtered.length === 0) {
        grid.innerHTML = `
          <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted);">
            <div style="font-size: 40px; margin-bottom: 10px;">🔍</div>
            <h3 style="font-size: 16px; font-weight: 800;">لا توجد أصناف مطابقة للبحث</h3>
            <p style="font-size: 12.5px; margin-top: 4px;">جرب البحث بكلمة أخرى أو اختر تصنيفاً مختلفاً</p>
          </div>
        `;
        return;
      }

      grid.innerHTML = filtered.map(p => {
        const sel = state.selections[p.id] || { mood: "🔥", size: "M", sugar: "50%", ice: "50%" };
        
        // حساب السعر بحسب الحجم
        let extra = 0;
        if (sel.size === "L") extra = 0.50;
        if (sel.size === "S") extra = -0.30;
        const currentPrice = Math.max(1, p.price + extra);

        return `
          <div class="product-card">
            <!-- رأس الكرت -->
            <div class="card-top">
              <div class="card-img-wrap">
                <img src="${p.image}" alt="${p.name}" loading="lazy" />
              </div>
              <div class="card-meta">
                <h3 class="card-title">${p.name}</h3>
                <p class="card-desc">${p.desc}</p>
                <div class="card-price">${currentPrice.toFixed(2)} د.ل</div>
              </div>
            </div>

            <!-- خيارات التخصيص التفاعلية (Mood, Size, Sugar, Ice) -->
            <div class="options-grid">
              ${p.options.mood && p.options.mood.length ? `
                <div class="option-group">
                  <span class="option-label">المزاج (Mood)</span>
                  <div class="pill-selector">
                    ${p.options.mood.map(m => `
                      <button type="button" class="pill-btn ${sel.mood === m ? 'active' : ''}" onclick="setProductOption(${p.id}, 'mood', '${m}')">
                        ${m === '🔥' ? '🔥 ساخن' : '❄️ بارد'}
                      </button>
                    `).join('')}
                  </div>
                </div>
              ` : '<div></div>'}

              ${p.options.sizes && p.options.sizes.length ? `
                <div class="option-group">
                  <span class="option-label">الحجم (Size)</span>
                  <div class="pill-selector">
                    ${p.options.sizes.map(s => `
                      <button type="button" class="pill-btn ${sel.size === s ? 'active' : ''}" onclick="setProductOption(${p.id}, 'size', '${s}')">
                        ${s}
                      </button>
                    `).join('')}
                  </div>
                </div>
              ` : '<div></div>'}

              ${p.options.sugar && p.options.sugar.length ? `
                <div class="option-group">
                  <span class="option-label">السكر (Sugar)</span>
                  <div class="pill-selector">
                    ${p.options.sugar.map(sg => `
                      <button type="button" class="pill-btn ${sel.sugar === sg ? 'active' : ''}" onclick="setProductOption(${p.id}, 'sugar', '${sg}')">
                        ${sg}
                      </button>
                    `).join('')}
                  </div>
                </div>
              ` : '<div></div>'}

              ${p.options.ice && p.options.ice.length && sel.mood !== '🔥' ? `
                <div class="option-group">
                  <span class="option-label">الثلج (Ice)</span>
                  <div class="pill-selector">
                    ${p.options.ice.map(ic => `
                      <button type="button" class="pill-btn ${sel.ice === ic ? 'active' : ''}" onclick="setProductOption(${p.id}, 'ice', '${ic}')">
                        ${ic}
                      </button>
                    `).join('')}
                  </div>
                </div>
              ` : '<div></div>'}
            </div>

            <!-- زر إضافة للفاتورة -->
            <button type="button" class="btn-add-bill" onclick="addToBill(${p.id})">
              <span>أضف للفاتورة (Add to Billing)</span>
              <span>➕</span>
            </button>
          </div>
        `;
      }).join('');
    }

    // تبديل خيارات المنتج
    function setProductOption(productId, optionKey, value) {
      if (!state.selections[productId]) {
        state.selections[productId] = {};
      }
      state.selections[productId][optionKey] = value;
      renderProducts();
    }

    // تصنيف
    function selectCategory(cat, el) {
      document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
      el.classList.add('active');
      state.currentCategory = cat;

      const titleMap = {
        'all': 'كافة المشروبات والأصناف',
        'قهوة': 'قائمة القهوة والمشروبات الساخنة والباردة',
        'عصائر': 'قائمة العصائر المنعشة',
        'حليب': 'مشروبات الحليب والنكهات',
        'سناكس': 'المخبوزات والسناكس الخفيفة',
        'وجبات': 'الوجبات والأطباق',
        'حلويات': 'الحلويات والتشيز كيك'
      };
      document.getElementById('sectionTitle').textContent = titleMap[cat] || 'قائمة الأصناف';
      renderProducts();
    }

    function handleSearch(val) {
      state.searchQuery = val;
      renderProducts();
    }

    // ==========================================
    // إدارة الفاتورة (Cart / Bills Logic)
    // ==========================================
    function addToBill(productId) {
      const prod = state.catalog.find(p => p.id === productId);
      if (!prod) return;

      const sel = { ...state.selections[productId] };
      
      let extra = 0;
      if (sel.size === "L") extra = 0.50;
      if (sel.size === "S") extra = -0.30;
      const unitPrice = Math.max(1, prod.price + extra);

      // مفتاح تعريف الصنف المخصص
      const itemKey = `${prod.id}_${sel.mood || ''}_${sel.size || ''}_${sel.sugar || ''}_${sel.ice || ''}`;

      const existing = state.cart.find(item => item.key === itemKey);
      if (existing) {
        existing.qty += 1;
      } else {
        state.cart.push({
          key: itemKey,
          id: prod.id,
          name: prod.name,
          image: prod.image,
          unitPrice: unitPrice,
          qty: 1,
          sel: sel,
          note: ""
        });
      }

      renderCart();
      playDing();
    }

    function updateCartQty(index, delta) {
      if (!state.cart[index]) return;
      state.cart[index].qty += delta;
      if (state.cart[index].qty <= 0) {
        state.cart.splice(index, 1);
      }
      renderCart();
    }

    function renderCart() {
      const listEl = document.getElementById("orderItemsList");
      if (state.cart.length === 0) {
        listEl.innerHTML = `
          <div style="text-align:center;padding:40px 10px;color:var(--text-light);font-size:13.5px;">
            <div style="font-size:30px;margin-bottom:6px;">☕</div>
            لم تتم إضافة أي طلبات للفاتورة بعد
          </div>
        `;
        document.getElementById("billSubtotal").textContent = "0.000 د.ل";
        document.getElementById("billTax").textContent = "0.000 د.ل";
        document.getElementById("billTotal").textContent = "0.000 د.ل";
        return;
      }

      let subtotal = 0;

      listEl.innerHTML = state.cart.map((item, idx) => {
        const itemTotal = item.unitPrice * item.qty;
        subtotal += itemTotal;

        const modsText = [
          item.sel.mood ? (item.sel.mood === '🔥' ? 'ساخن 🔥' : 'بارد ❄️') : '',
          item.sel.size ? `حجم ${item.sel.size}` : '',
          item.sel.sugar ? `سكر ${item.sel.sugar}` : '',
          item.sel.ice && item.sel.mood !== '🔥' ? `ثلج ${item.sel.ice}` : ''
        ].filter(Boolean).join(' | ');

        return `
          <div class="order-item-card">
            <div class="order-item-thumb">
              <img src="${item.image}" alt="${item.name}" />
            </div>
            <div class="order-item-details">
              <div class="order-item-name">${item.name}</div>
              <div class="order-item-mods">${modsText}</div>
              <div class="order-item-notes" onclick="openNoteModal(${idx})">
                <span>✏️</span> ${item.note ? htmlEscape(item.note) : 'إضافة ملاحظة'}
              </div>
            </div>
            <div class="order-item-right">
              <div class="order-item-price">${itemTotal.toFixed(2)} د.ل</div>
              <div class="qty-controls">
                <button type="button" class="qty-btn" onclick="updateCartQty(${idx}, -1)">-</button>
                <span class="order-item-qty">x${item.qty}</span>
                <button type="button" class="qty-btn" onclick="updateCartQty(${idx}, 1)">+</button>
              </div>
            </div>
          </div>
        `;
      }).join('');

      const tax = subtotal * 0.10; // 10%
      const total = subtotal + tax;

      document.getElementById("billSubtotal").textContent = subtotal.toFixed(3) + " د.ل";
      document.getElementById("billTax").textContent = tax.toFixed(3) + " د.ل";
      document.getElementById("billTotal").textContent = total.toFixed(3) + " د.ل";
    }

    function setPaymentMethod(method, btn) {
      document.querySelectorAll('.pay-method-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      state.selectedPayment = method;
    }

    // ==========================================
    // الملاحظات وإيصال الطباعة
    // ==========================================
    function openNoteModal(idx) {
      state.currentNoteIndex = idx;
      document.getElementById("noteInput").value = state.cart[idx]?.note || "";
      document.getElementById("noteModal").classList.add("open");
    }

    function closeNoteModal() {
      document.getElementById("noteModal").classList.remove("open");
    }

    function saveItemNote() {
      if (state.currentNoteIndex !== null && state.cart[state.currentNoteIndex]) {
        state.cart[state.currentNoteIndex].note = document.getElementById("noteInput").value.trim();
      }
      closeNoteModal();
      renderCart();
    }

    function checkoutAndPrint() {
      if (state.cart.length === 0) {
        alert("يرجى إضافة أصناف إلى الفاتورة أولاً!");
        return;
      }

      let subtotal = 0;
      state.cart.forEach(i => subtotal += (i.unitPrice * i.qty));
      const tax = subtotal * 0.10;
      const total = subtotal + tax;

      const dateStr = new Date().toLocaleString('ar-LY', { dateStyle: 'short', timeStyle: 'short' });
      const orderNo = "#" + Math.floor(1000 + Math.random() * 9000);

      const itemsHtml = state.cart.map(i => {
        return `
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <span>${i.name} (x${i.qty})</span>
            <b>${(i.unitPrice * i.qty).toFixed(2)} د.ل</b>
          </div>
          ${i.note ? `<div style="font-size:11px;color:#64748B;margin-bottom:4px;">↳ ملاحظة: ${htmlEscape(i.note)}</div>` : ''}
        `;
      }).join('');

      const payLabels = { 'cash': 'نقدي 💵', 'card': 'بطاقة مصرفية 💳', 'wallet': 'محفظة إلكترونية 📱' };

      document.getElementById("receiptContent").innerHTML = `
        <div style="border-bottom:1px dashed #94A3B8;padding-bottom:8px;margin-bottom:8px;">
          <div><b>رقم الطلب:</b> ${orderNo}</div>
          <div><b>التاريخ:</b> ${dateStr}</div>
          <div><b>الكاشير:</b> <?= htmlspecialchars($user['name']) ?></div>
          <div><b>طريقة الدفع:</b> ${payLabels[state.selectedPayment] || 'نقدي'}</div>
        </div>
        <div style="border-bottom:1px dashed #94A3B8;padding-bottom:8px;margin-bottom:8px;">
          ${itemsHtml}
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
          <span>المجموع الفرعي:</span>
          <span>${subtotal.toFixed(3)} د.ل</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
          <span>الضريبة (10%):</span>
          <span>${tax.toFixed(3)} د.ل</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:900;border-top:1px solid #1E293B;padding-top:6px;">
          <span>المجموع الصافي:</span>
          <span>${total.toFixed(3)} د.ل</span>
        </div>
        <div style="text-align:center;margin-top:14px;font-size:11px;color:#64748B;">
          شكراً لزيارتكم ونتمنى لكم يوماً سعيداً! ☕
        </div>
      `;

      document.getElementById("receiptModal").classList.add("open");
    }

    function printReceiptNow() {
      window.print();
      // بعد الطباعة، تفريغ الفاتورة
      state.cart = [];
      renderCart();
      closeReceiptModal();
      showToast("تمت معالجة وطباعة الفاتورة بنجاح ✓");
    }

    function closeReceiptModal() {
      document.getElementById("receiptModal").classList.remove("open");
    }

    function openHistoryModal() {
      alert("سجل الفواتير والمبيعات السابقة متاح للمراجعة في لوحة التحكم الإدارية.");
    }

    function showToast(msg) {
      alert(msg);
    }

    // ===== تبديل لوحة السلة على الجوال =====
    function toggleMobileCart() {
      const panel = document.querySelector('.bills-panel');
      const overlay = document.getElementById('cartOverlay');
      if (!panel) return;
      const isOpen = panel.classList.toggle('cart-open');
      if (overlay) overlay.classList.toggle('open', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    // تحديث badge عداد السلة على الجوال
    function updateCartFabBadge(count) {
      const badge = document.getElementById('cartFabBadge');
      if (!badge) return;
      if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }

    function htmlEscape(str) {
      return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function playDing() {
      try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = "sine";
        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.08); // A5
        gain.gain.setValueAtTime(0.08, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.15);
      } catch (_) {}
    }

    // تشغيل الصفحة
    initSelections();
    renderProducts();
    renderCart();
  </script>
</body>
</html>
