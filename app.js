const cloud = require("./cloud");
const { icon, logoMark } = require("./icons");
const auth = require("./auth");
const license = require("./license");

// ================= بيانات وحالة التطبيق =================
const ROLE_LABELS = { admin: "مدير النظام", manager: "مدير الفرع", cashier: "الكاشير" };
const ROLE_DESC = {
  admin: "صلاحية كاملة: الأرباح، إدارة المنتجات والمخزون بالكامل",
  manager: "إدارة الفرع اليومية: المنتجات، المخزون، والمبيعات",
  cashier: "بيع سريع من نقطة البيع فقط",
};
const ROLE_ICON = { admin: "roleAdmin", manager: "roleManager", cashier: "roleCashier" };

const NAV_ITEMS = [
  { key: "dashboard", label: "لوحة التحكم", icon: "dashboard", roles: ["admin", "manager"] },
  { key: "pos", label: "نقطة البيع", icon: "pos", roles: ["admin", "manager", "cashier"] },
  { key: "products", label: "المنتجات", icon: "productsNav", roles: ["admin", "manager"] },
  { key: "inventory", label: "المخزون", icon: "inventory", roles: ["admin", "manager"] },
  { key: "staff", label: "الموظفون", icon: "staff", roles: ["admin"] },
  { key: "purchases", label: "المشتريات", icon: "receipt", roles: ["admin", "manager"] },
  { key: "settings", label: "الإعدادات والتقارير", icon: "edit", roles: ["admin", "manager"] },
];

const CATEGORIES = ["الكل", "قهوة ساخنة", "قهوة باردة", "مشروبات", "حلويات"];
const CAT_EMOJI = { "قهوة ساخنة": "catHot", "قهوة باردة": "catCold", "مشروبات": "catDrinks", "حلويات": "catSweets" };

let state = {
  role: null,
  view: "dashboard",
  products: [
    { id: 1, name: "اسبريسو", category: "قهوة ساخنة", price: 12, stock: 40, threshold: 15 },
    { id: 2, name: "كابتشينو", category: "قهوة ساخنة", price: 18, stock: 35, threshold: 15 },
    { id: 3, name: "لاتيه", category: "قهوة ساخنة", price: 20, stock: 8, threshold: 15 },
    { id: 4, name: "أمريكانو", category: "قهوة ساخنة", price: 14, stock: 50, threshold: 15 },
    { id: 5, name: "آيس لاتيه", category: "قهوة باردة", price: 22, stock: 6, threshold: 12 },
    { id: 6, name: "كولد برو", category: "قهوة باردة", price: 20, stock: 25, threshold: 12 },
    { id: 7, name: "فرابيه", category: "قهوة باردة", price: 24, stock: 18, threshold: 12 },
    { id: 8, name: "عصير برتقال", category: "مشروبات", price: 15, stock: 30, threshold: 10 },
    { id: 9, name: "شاي أحمر", category: "مشروبات", price: 10, stock: 45, threshold: 10 },
    { id: 10, name: "تشيز كيك", category: "حلويات", price: 25, stock: 4, threshold: 8 },
    { id: 11, name: "كوكيز", category: "حلويات", price: 8, stock: 60, threshold: 15 },
    { id: 12, name: "كروسان", category: "حلويات", price: 10, stock: 5, threshold: 10 },
  ],
  cart: [],
  search: "",
  category: "الكل",
  discount: 0,
  todaySales: 0,
  monthSales: 0,
  ordersCount: 0,
  recentTx: [],
  weekly: [],
  topProducts: [],
  productSales: [],
  dayNeedsClosing: false,
  productModal: null,
  deleteId: null,
  stockModal: null,
  toast: null,
  cloud: { configured: false, online: false, hasPendingWrites: false, error: false, paused: false },
  authUser: null,
  authRole: null,
  authError: null,
  demoMode: false,
  staffList: [],
  staffModal: false,
  update: { status: "idle" },
  shiftModal: null, // { mode: 'open'|'close' }
  currentShift: null,
  suppliers: [],
  purchases: [],
  purchaseModal: false,
  shortageCatalog: [],
  shortageInputMode: "select", // select | text
  paymentModal: null, // { supplierId, supplierName }
  heldCarts: [],
  shortages: [],
  availablePrinters: [],
  licenseState: "checking", // checking | needs-key | valid | grace | invalid
  licenseInfo: null,
  paymentMethod: "cash", // cash | card
  numpadBuffer: "", // الكمية المدخلة من النمباد
  nextOrderToken: 1, // رقم المناداة التالي للزبون
  customBuzzer: "", // رقم جهاز البيجر / المناداة الاختياري
  expenseModal: false,
  shiftExpenses: [],
  shortageModal: false,
  receiptSettings: {
    storeName: "مقهى بُنّ الاحترافي",
    address: "طرابلس - ليبيا",
    phone: "0910000000",
    footerMsg: "شكراً لزيارتكم! نأمل رؤيتكم قريباً.",
    logoDataUrl: "",
    printerName: "",
    paperWidth: "80",
  }
};

// ================= المزامنة السحابية =================
function syncToCloud() {
  // نستبعد صورة الشعار من المزامنة عمدًا: حجمها ممكن يتجاوز حد حجم مستند Firestore (1MB)
  // ويخرب المزامنة كاملة. تبقى محلية فقط على هذا الجهاز.
  const { logoDataUrl, ...receiptSettingsForCloud } = state.receiptSettings || {};
  const data = {
    products: state.products,
    todaySales: state.todaySales,
    ordersCount: state.ordersCount,
    monthSales: state.monthSales,
    recentTx: state.recentTx,
    heldCarts: state.heldCarts,
    shortages: state.shortages,
    receiptSettings: receiptSettingsForCloud,
  };
  cloud.pushCloudState(data);
}

function handleCloudData(data) {
  if (data.products && Array.isArray(data.products)) {
    if (window.electronAPI && window.electronAPI.dbSyncProductsFromCloud) {
      window.electronAPI.dbSyncProductsFromCloud(data.products).then((merged) => {
        if (merged && merged.length > 0) {
          state.products = merged;
          // إذا كان المحلي يحتوي على أصناف غير متوفرة في السحابة، نرفع القائمة المدمجة فوراً للسحابة
          if (merged.length > data.products.length) {
            syncToCloud();
          }
          if (state.role) render();
        }
      }).catch(err => {
        console.error("Failed to sync cloud products to SQLite:", err);
      });
    } else {
      // وضع المتصفح أو بيئة بدون قاعدة بيانات مباشرة: دمج ذكي بواسطة المعرف ID
      const map = new Map();
      (state.products || []).forEach(p => map.set(p.id, p));
      data.products.forEach(p => map.set(p.id, { ...(map.get(p.id) || {}), ...p }));
      state.products = Array.from(map.values());
      if (state.role) render();
    }
  } else if ((!data.products || data.products.length === 0) && state.products && state.products.length > 0) {
    // إذا كان المستند السحابي فارغاً أو بدون أصناف، نرفع أصنافنا الافتراضية
    syncToCloud();
  }
  if (data.shortages) state.shortages = data.shortages;
  if (data.shortageCatalog) state.shortageCatalog = data.shortageCatalog;
  if (data.receiptSettings) state.receiptSettings = { ...state.receiptSettings, ...data.receiptSettings };
  if (typeof data.todaySales === "number") state.todaySales = data.todaySales;
  if (typeof data.ordersCount === "number") state.ordersCount = data.ordersCount;
  if (typeof data.monthSales === "number") state.monthSales = data.monthSales;
  if (data.recentTx) state.recentTx = data.recentTx;
  if (data.heldCarts) state.heldCarts = data.heldCarts;
  if (state.role) render();
}

function handleCloudStatus(status) {
  state.cloud = { ...state.cloud, ...status };
  if (state.role) render();
}

function toggleSync() {
  state.cloud.paused = !state.cloud.paused;
  cloud.setSyncEnabled(!state.cloud.paused);
  render();
}

function retryCloudSync() {
  state.cloud = { configured: false, online: false, hasPendingWrites: false, error: false, paused: false };
  cloud.retryInit(handleCloudData, handleCloudStatus);
  render();
}

// ================= تسجيل الدخول والموظفون =================
async function submitLogin(e) {
  e.preventDefault();
  const email = e.target.email.value.trim();
  const password = e.target.password.value;
  state.authError = null;
  try {
    const user = await auth.login(email, password);
    const roleData = await auth.fetchRole(user.uid);
    if (!roleData) {
      state.authError = "هذا الحساب غير مرتبط بأي صلاحية. تواصل مع مدير النظام.";
      await auth.logout();
      render();
      return;
    }
    state.authUser = { uid: user.uid, email: user.email };
    state.authRole = roleData;
    state.role = roleData.role;
    state.view = "pos";
    render();
  } catch (err) {
    state.authError = "بيانات الدخول غير صحيحة أو الحساب غير موجود.";
    render();
  }
}

function useDemoMode() { state.demoMode = true; render(); }

async function logoutAccount() {
  if (!confirm("هل تريد تسجيل الخروج؟")) return;
  if (state.authUser) await auth.logout().catch(() => {});
  state.authUser = null; state.authRole = null; state.role = null; state.demoMode = false;
  state.cart = [];
  render();
}

async function loadStaffIfNeeded() {
  if (!auth.isAuthConfigured()) return;
  state.staffList = await auth.listStaff().catch(() => []);
  renderMain();
}

function openStaffModal() { state.staffModal = true; render(); }
function closeStaffModal() { state.staffModal = false; render(); }

async function saveStaff(e) {
  e.preventDefault();
  const f = e.target;
  try {
    await auth.createStaffAccount(f.email.value.trim(), f.password.value, f.role.value, f.name.value.trim());
    state.staffModal = false;
    state.staffList = await auth.listStaff().catch(() => state.staffList);
    render();
    showToast("تم إنشاء حساب الموظف");
  } catch (err) {
    alert("تعذر إنشاء الحساب: تأكد من تفعيل Email/Password في Firebase Authentication.");
  }
}

// ================= الورديات =================
function applyFreshState(freshState) {
  if (!freshState) return;
  if (freshState.products) state.products = freshState.products;
  state.todaySales = freshState.todaySales || 0;
  state.ordersCount = freshState.ordersCount || 0;
  if (freshState.nextOrderToken !== undefined) state.nextOrderToken = freshState.nextOrderToken;
  if (freshState.monthSales !== undefined) state.monthSales = freshState.monthSales;
  state.recentTx = freshState.recentTx || [];
  state.productSales = freshState.productSales || [];
  state.topProducts = freshState.topProducts || [];
  state.weekly = freshState.weekly || [];
  state.dayNeedsClosing = !!freshState.dayNeedsClosing;
  state.currentShift = freshState.currentShift || null;
  if (freshState.receiptSettings) state.receiptSettings = freshState.receiptSettings;
  if (freshState.shortages) state.shortages = freshState.shortages;
  if (freshState.shortageCatalog) state.shortageCatalog = freshState.shortageCatalog;
  if (freshState.suppliers) state.suppliers = freshState.suppliers;
  if (freshState.purchases) state.purchases = freshState.purchases;
  if (freshState.shiftExpenses) state.shiftExpenses = freshState.shiftExpenses;
}

function openShiftModal() { state.shiftModal = { mode: "open" }; render(); }
function openCloseShiftModal() { state.shiftModal = { mode: "close" }; render(); }
function closeShiftModal() { state.shiftModal = null; render(); }

function currentUserLabel() {
  return (state.authRole && (state.authRole.name || state.authRole.email)) || ROLE_LABELS[state.role];
}

async function submitOpenShift(e) {
  e.preventDefault();
  const openingCash = Number(e.target.openingCash.value) || 0;
  if (window.electronAPI && window.electronAPI.dbOpenShift) {
    const freshState = await window.electronAPI.dbOpenShift({ openingCash, openedBy: currentUserLabel() });
    applyFreshState(freshState);
  }
  state.shiftModal = null;
  render();
  syncToCloud();
  showToast("تم فتح الوردية");
}

function updateCashDiffPreview(value) {
  const expected = state.currentShift ? state.currentShift.expectedCash : 0;
  const diff = (Number(value) || 0) - expected;
  const el = document.getElementById("cashDiffPreview");
  if (!el) return;
  if (Math.abs(diff) < 0.001) { el.textContent = "مطابق تمامًا ✓"; el.style.color = "var(--success)"; }
  else if (diff > 0) { el.textContent = `زيادة ${money(diff)} عن المتوقع`; el.style.color = "var(--gold-dark)"; }
  else { el.textContent = `عجز ${money(Math.abs(diff))} عن المتوقع`; el.style.color = "var(--rust-dark)"; }
}

async function submitCloseShift(e) {
  e.preventDefault();
  const actualCash = Number(e.target.actualCash.value) || 0;

  const expected = state.currentShift ? state.currentShift.expectedCash : 0;
  const openingCash = state.currentShift ? state.currentShift.openingCash : 0;
  const cashSales = state.currentShift ? state.currentShift.cashSales : 0;
  const cardSales = state.currentShift ? state.currentShift.cardSales : 0;
  const expenses = state.currentShift ? state.currentShift.totalExpenses : 0;
  const diff = actualCash - expected;
  const openedAtStr = state.currentShift && state.currentShift.openedAt ? new Date(state.currentShift.openedAt).toLocaleTimeString("ar-LY", { hour: "2-digit", minute: "2-digit" }) : "";
  const closedBy = currentUserLabel();

  const report = {
    date: new Date().toLocaleDateString("ar-LY", { year: "numeric", month: "long", day: "numeric" }),
    time: new Date().toLocaleTimeString("ar-LY", { hour: "2-digit", minute: "2-digit" }),
    openedAt: openedAtStr,
    closedBy: closedBy,
    openingCash: money(openingCash),
    todaySales: money(state.todaySales),
    cashSales: money(cashSales),
    cardSales: money(cardSales),
    expenses: money(expenses),
    expectedCash: money(expected),
    actualCash: money(actualCash),
    diffVal: diff,
    diffFormatted: (Math.abs(diff) < 0.001) ? "مطابق تماماً ✓" : (diff > 0 ? `+${money(diff)} (زيادة)` : `-${money(Math.abs(diff))} (عجز)`),
    ordersCount: state.ordersCount,
    productSales: state.productSales.map((p) => ({ name: p.name, qty: p.qty, revenue: money(p.revenue) })),
    expensesList: (state.shiftExpenses || []).map(exp => ({ reason: exp.reason, amount: money(exp.amount) })),
    receiptSettings: state.receiptSettings,
  };

  if (window.electronAPI && window.electronAPI.printDayReport) {
    await window.electronAPI.printDayReport(report);
  }

  if (window.electronAPI && window.electronAPI.dbCloseShift) {
    const freshState = await window.electronAPI.dbCloseShift({ actualCash, closedBy });
    applyFreshState(freshState);
  }

  state.shiftModal = null;
  showToast("تم إغلاق الوردية وطباعة فاتورة التوكة بنجاح");
  renderMain();
  syncToCloud();
}

async function confirmCloseWeek() {
  if (!confirm("هل أنت متأكد من إغلاق الأسبوع؟ سيتم تصفير مخطط المبيعات الأسبوعية.")) return;
  const report = { 
    periodName: "الأسبوع", 
    date: new Date().toLocaleDateString("ar-LY"), 
    totalSales: money(state.weekly.reduce((acc, curr) => acc + (curr.sales || 0), 0)),
    receiptSettings: state.receiptSettings
  };
  if (window.electronAPI && window.electronAPI.printPeriodReport) await window.electronAPI.printPeriodReport(report);
  if (window.electronAPI && window.electronAPI.closeWeek) {
    const freshState = await window.electronAPI.closeWeek();
    applyFreshState(freshState);
  } else { state.weekly = []; }
  renderMain();
  syncToCloud();
  showToast("تم إغلاق الأسبوع وطباعة التقرير بنجاح");
}

async function confirmCloseMonth() {
  if (!confirm("هل أنت متأكد من إغلاق الشهر؟ سيتم تصفير إجمالي المبيعات الشهرية.")) return;
  const report = { 
    periodName: "الشهر", 
    date: new Date().toLocaleDateString("ar-LY"), 
    totalSales: money(state.monthSales),
    receiptSettings: state.receiptSettings
  };
  if (window.electronAPI && window.electronAPI.printPeriodReport) await window.electronAPI.printPeriodReport(report);
  if (window.electronAPI && window.electronAPI.closeMonth) {
    const freshState = await window.electronAPI.closeMonth();
    applyFreshState(freshState);
  } else { state.monthSales = 0; }
  renderMain();
  syncToCloud();
  showToast("تم إغلاق الشهر وطباعة التقرير بنجاح");
}

// 🔄 دالة استرجاع الفاتورة (Refund) مع التحديث الحقيقي بقاعدة البيانات
async function refundTransaction(index) {
  const tx = state.recentTx[index];
  if (!tx || tx.refunded) return;
  if (!confirm(`هل أنت متأكد من استرجاع الفاتورة بقيمة ${money(tx.total)}؟ سيتم إرجاع الأصناف للمخزون.`)) return;

  if (window.electronAPI && window.electronAPI.dbRefundTransaction && tx.id) {
    const freshState = await window.electronAPI.dbRefundTransaction(tx.id);
    applyFreshState(freshState);
  } else {
    tx.refunded = true;
    state.todaySales = Math.max(0, state.todaySales - tx.total);
    state.monthSales = Math.max(0, state.monthSales - tx.total);
    state.ordersCount = Math.max(0, state.ordersCount - 1);

    // إرجاع المخزون
    if (Array.isArray(tx.items)) {
      tx.items.forEach(item => {
        const p = state.products.find(prod => prod.name === item.name || prod.id === item.id);
        if (p && p.trackStock !== false) {
          p.stock += item.qty;
        }
      });
    }
  }

  showToast("تم استرجاع الفاتورة وتحديث المخزون بنجاح");
  renderMain();
  syncToCloud();
}

// 💵 طريقة الدفع ومصروفات الدرج (Petty Cash)
function setPaymentMethod(method) {
  state.paymentMethod = method;
  const cartPanel = document.querySelector(".cart-panel");
  if (cartPanel) {
    cartPanel.innerHTML = renderCartPanelInner();
  }
}

function openExpenseModal() {
  state.expenseModal = true;
  render();
}

function closeExpenseModal() {
  state.expenseModal = false;
  render();
}

async function submitExpense(e) {
  e.preventDefault();
  const f = e.target;
  const amount = Number(f.amount.value) || 0;
  const reason = f.reason.value.trim();
  if (!amount || !reason) return;

  if (window.electronAPI && window.electronAPI.dbAddExpense) {
    const freshState = await window.electronAPI.dbAddExpense({
      amount,
      reason,
      createdBy: currentUserLabel()
    });
    applyFreshState(freshState);
  }

  state.expenseModal = false;
  render();
  syncToCloud();
  showToast("تم تسجيل المصروف وتحديث رصيد الدرج المتوقع");
}

// 📊 تصدير البيانات كـ CSV
function exportSalesToCSV() {
  if (state.recentTx.length === 0) { showToast("لا توجد مبيعات لتصديرها"); return; }
  let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
  csvContent += "الوقت,اسم الكاشير,عدد الأصناف,الإجمالي,الحالة\n";
  state.recentTx.forEach(t => {
    const status = t.refunded ? "مسترجع" : "ناجح";
    csvContent += `"${t.time || ''}","${t.cashier || ''}",${t.itemsCount || 0},${t.total || 0},"${status}"\n`;
  });
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `تقرير_مبيعات_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// ================= التحديثات والدوال المساعدة =================
function restartToUpdate() { window.electronAPI.restartApp(); }

async function downloadAppUpdate() {
  if (!window.electronAPI || !window.electronAPI.downloadUpdate) return;
  state.update = { ...state.update, status: "downloading", percent: 0 };
  render();
  const result = await window.electronAPI.downloadUpdate();
  if (!result || !result.ok) {
    state.update = { status: "error", message: "تعذّر تنزيل التحديث. حاول لاحقاً." };
    render();
  }
}

async function checkAppUpdatesManual() {
  if (!window.electronAPI || !window.electronAPI.checkForUpdate) {
    showToast("فحص التحديثات يعمل في النسخة المثبتة الرسمية (Packaged)");
    return;
  }
  showToast("جارٍ فحص التحديثات من السيرفر...");
  state.update = { status: "checking" };
  renderMain();
  const res = await window.electronAPI.checkForUpdate();
  if (res && !res.ok) {
    showToast(res.error || "تعذّر فحص التحديثات");
    state.update = { status: "error", message: res.error };
    renderMain();
  }
}

if (window.electronAPI && window.electronAPI.onUpdateStatus) {
  window.electronAPI.onUpdateStatus((data) => {
    state.update = data;
    if (data.status === "ready") {
      showToast("🎉 تم تنزيل تحديث جديد وجاهز للتثبيت!");
    } else if (data.status === "available") {
      showToast(`يتوفر إصدار جديد v${data.version || ""} — جارٍ التحميل...`);
    } else if (data.status === "latest") {
      showToast("النظام مُحدَّث لآخر إصدار ✓");
    }
    renderMain();
  });
}

function money(n) {
  const num = Number(n) || 0;
  return num.toLocaleString("ar-LY", { minimumFractionDigits: 3, maximumFractionDigits: 3 }) + " د.ل";
}

// ✅ تعقيم HTML — يمنع XSS من أسماء المنتجات أو الموردين أو المدخلات
function esc(str) {
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function showToast(msg) {
  const old = document.querySelector(".toast");
  if (old) old.remove();
  const toastEl = document.createElement("div");
  toastEl.className = "toast";
  toastEl.textContent = msg;
  document.body.appendChild(toastEl);
  setTimeout(() => {
    toastEl.classList.add("toast-out");
    setTimeout(() => toastEl.remove(), 280);
  }, 2000);
}

// ================= إجراءات الصفحة =================
function selectRole(role) { state.role = role; state.view = "pos"; render(); }
function switchRole() { if (!confirm("هل تريد تبديل الدور؟")) return; state.role = null; state.cart = []; render(); }
function setView(key) {
  state.view = key;
  render();
  if (key === "staff") loadStaffIfNeeded();
  if (key === "settings") loadPrinters();
}

async function loadPrinters() {
  if (!window.electronAPI || !window.electronAPI.listPrinters) return;
  const printers = await window.electronAPI.listPrinters().catch(() => []);
  state.availablePrinters = printers || [];
  renderMain();
}
function setSearch(v) { state.search = v; updatePOSProductsOnly(); }
function setCategory(c) { state.category = c; updatePOSProductsOnly(); }


function addToCart(id) {
  const p = state.products.find(x => x.id === id);
  if (!p) return;
  const tracked = p.trackStock !== false;
  if (tracked && p.stock <= 0) return;

  // إذا كان النمباد يحوي كمية، أضفها دفعةً واحدة
  const bufQty = parseInt(state.numpadBuffer) || 0;
  const qty = bufQty > 0 ? bufQty : 1;

  const line = state.cart.find(l => l.id === id);
  if (line) {
    const newQty = line.qty + qty;
    line.qty = (!tracked || newQty <= p.stock) ? newQty : p.stock;
  } else {
    const addQty = (!tracked || qty <= p.stock) ? qty : p.stock;
    state.cart.push({ id, qty: addQty });
  }

  // امسح النمباد بعد الإضافة
  if (bufQty > 0) {
    state.numpadBuffer = "";
    const display = document.querySelector(".numpad-display");
    if (display) display.textContent = "0";
  }

  updatePOSCartOnly(id);
}


function changeQty(id, delta) {
  const line = state.cart.find(l => l.id === id);
  if (!line) return;
  line.qty += delta;
  if (line.qty <= 0) state.cart = state.cart.filter(l => l.id !== id);
  updatePOSCartOnly(id);
}

// ===== دوال لوحة الأرقام (Numpad) =====
function numpadPress(digit) {
  if (state.numpadBuffer.length >= 4) return; // حد أقصى 4 أرقام
  state.numpadBuffer += digit;
  const display = document.querySelector(".numpad-display");
  if (display) display.textContent = state.numpadBuffer || "0";
}

function numpadBackspace() {
  state.numpadBuffer = state.numpadBuffer.slice(0, -1);
  const display = document.querySelector(".numpad-display");
  if (display) display.textContent = state.numpadBuffer || "0";
}

function numpadClear() {
  state.numpadBuffer = "";
  const display = document.querySelector(".numpad-display");
  if (display) display.textContent = "0";
}

function confirmCancelCart() {
  if (state.cart.length === 0) { showToast("السلة فارغة أصلًا"); return; }
  if (!confirm("هل تريد إلغاء هذه السلة بالكامل؟")) return;
  state.cart = [];
  state.discount = 0;
  const cartPanel = document.querySelector(".cart-panel");
  if (cartPanel) cartPanel.innerHTML = renderCartPanelInner();
  updatePOSProductsOnly();
  showToast("تم إلغاء السلة");
}

function holdCurrentCart() {
  if (state.cart.length === 0) { showToast("السلة فارغة، ما فيه شي تعلّقه"); return; }
  state.heldCarts.push({
    id: Date.now(),
    items: state.cart,
    discount: state.discount,
    time: new Date().toLocaleTimeString("ar", { hour: "2-digit", minute: "2-digit" }),
  });
  state.cart = [];
  state.discount = 0;
  renderMain();
  if (window.electronAPI && window.electronAPI.dbSaveHeldCarts) window.electronAPI.dbSaveHeldCarts(state.heldCarts);
  syncToCloud();
  showToast("تم تعليق الطلب — جاهز لزبون جديد");
}

function resumeHeldCart(id) {
  const held = state.heldCarts.find(h => h.id === id);
  if (!held) return;
  if (state.cart.length > 0) {
    state.heldCarts.push({
      id: Date.now(),
      items: state.cart,
      discount: state.discount,
      time: new Date().toLocaleTimeString("ar", { hour: "2-digit", minute: "2-digit" }),
    });
  }
  state.heldCarts = state.heldCarts.filter(h => h.id !== id);
  state.cart = held.items;
  state.discount = held.discount || 0;
  renderMain();
  if (window.electronAPI && window.electronAPI.dbSaveHeldCarts) window.electronAPI.dbSaveHeldCarts(state.heldCarts);
  syncToCloud();
  showToast("تم استكمال الطلب المعلّق");
}

function deleteHeldCart(id) {
  if (!confirm("هل تريد حذف هذا الطلب المعلّق نهائيًا؟")) return;
  state.heldCarts = state.heldCarts.filter(h => h.id !== id);
  renderMain();
  if (window.electronAPI && window.electronAPI.dbSaveHeldCarts) window.electronAPI.dbSaveHeldCarts(state.heldCarts);
  syncToCloud();
  showToast("تم حذف الطلب المعلّق");
}

function heldCartTotal(held) {
  return held.items.reduce((s, l) => {
    const p = state.products.find(pp => pp.id === l.id);
    return s + (p ? p.price * l.qty : 0);
  }, 0);
}

function setDiscount(v) {
  state.discount = Math.max(0, Math.min(100, Number(v) || 0));
  if (state.view === "pos") {
    const cartPanel = document.querySelector(".cart-panel");
    if (cartPanel) { updateCartTotalsOnly(cartPanel); return; }
  }
  renderMain();
}

function renderCartLineHtml(rawLine) {
  const product = state.products.find(p => p.id === rawLine.id);
  if (!product) return "";
  return `
    <div class="cart-line" data-cart-id="${rawLine.id}">
      <div><div class="cart-line-name">${product.name}</div><div class="cart-line-price">${money(product.price)}</div></div>
      <div class="qty-controls">
        <button class="qty-btn" onclick="changeQty(${rawLine.id},-1)">${icon("minus", 13)}</button>
        <span class="qty-value">${rawLine.qty}</span>
        <button class="qty-btn" onclick="changeQty(${rawLine.id},1)">${icon("plus", 13)}</button>
      </div>
    </div>
  `;
}

function renderCartPanelInner() {
  const lines = cartLines();
  const { subtotal, discountAmount, total } = cartTotals();
  const buf = state.numpadBuffer || "0";
  return `
    <!-- رأس السلة -->
    <div class="cart-panel-head">
      <h3>${icon("pos", 16)} الفاتورة الحالية</h3>
      <div class="cart-actions">
        <button type="button" class="icon-btn-sm" title="سلة جديدة لزبون آخر" onclick="holdCurrentCart()">${icon("newCustomer", 14)}</button>
        <button type="button" class="icon-btn-sm icon-btn-danger" title="إلغاء السلة" onclick="confirmCancelCart()">${icon("cancelCart", 14)}</button>
      </div>
    </div>

    <!-- حاوية أصناف السلة: تعرض 4 أصناف بارتفاع ثابت ولا تزيد لأسفل أبداً -->
    <div class="cart-lines-wrap">
      ${lines.length === 0
        ? `<div class="cart-empty-placeholder">
             <div class="cart-empty-icon">${icon("pos", 26)}</div>
             <span>السلة فارغة</span>
           </div>`
        : `<div class="cart-lines">
             ${state.cart.map(l => renderCartLineHtml(l)).join("")}
           </div>`
      }
    </div>

    <!-- لوحة الأرقام (الآلة الحاسبة) — حجم ثابت محكم -->
    <div class="numpad-wrap">
      <div class="numpad-display-row">
        <span class="numpad-hint">الكمية ← اختر الصنف</span>
        <span class="numpad-display">${buf}</span>
      </div>
      <div class="numpad-grid">
        <button type="button" class="numpad-btn" onclick="numpadPress('7')">7</button>
        <button type="button" class="numpad-btn" onclick="numpadPress('8')">8</button>
        <button type="button" class="numpad-btn" onclick="numpadPress('9')">9</button>
        <button type="button" class="numpad-btn" onclick="numpadPress('4')">4</button>
        <button type="button" class="numpad-btn" onclick="numpadPress('5')">5</button>
        <button type="button" class="numpad-btn" onclick="numpadPress('6')">6</button>
        <button type="button" class="numpad-btn" onclick="numpadPress('1')">1</button>
        <button type="button" class="numpad-btn" onclick="numpadPress('2')">2</button>
        <button type="button" class="numpad-btn" onclick="numpadPress('3')">3</button>
        <button type="button" class="numpad-btn numpad-btn-clear" onclick="numpadClear()">C</button>
        <button type="button" class="numpad-btn" onclick="numpadPress('0')">0</button>
        <button type="button" class="numpad-btn numpad-btn-back" onclick="numpadBackspace()">⌫</button>
      </div>
    </div>

    <!-- الإجماليات والدفع — ثابتة في الأسفل وواضحة جداً -->
    <div class="cart-bottom-section">
      <div class="totals-compact">
        <div class="totals-row-sub">
          <span>المجموع: <b class="val-subtotal">${money(subtotal)}</b></span>
          <span style="display:flex;align-items:center;gap:4px">
            خصم %:
            <input type="number" min="0" max="100" class="discount-input-compact" value="${state.discount}" onchange="setDiscount(this.value)" />
          </span>
        </div>
        <div class="totals-row final">
          <div style="display:flex;align-items:center;gap:6px">
            <span>الإجمالي:</span>
            <span class="val-total">${money(total)}</span>
          </div>
          <div class="token-chip" title="رقم المناداة الذي سيطبع على الفاتورة">
            <span class="token-lbl">النداء:</span>
            <b class="token-num">#${state.nextOrderToken || (state.ordersCount + 1)}</b>
          </div>
        </div>
      </div>

      <div class="pay-method-compact">
        <button type="button" class="btn ${state.paymentMethod !== 'card' ? 'btn-primary' : 'btn-outline'}" onclick="setPaymentMethod('cash')">
          ${icon('money', 13)} نقداً
        </button>
        <button type="button" class="btn ${state.paymentMethod === 'card' ? 'btn-primary' : 'btn-outline'}" onclick="setPaymentMethod('card')">
          ${icon('receipt', 13)} بطاقة
        </button>
        <input type="text" class="buzzer-input" placeholder="البيجر (اختياري)" value="${esc(state.customBuzzer || '')}" oninput="state.customBuzzer = this.value" />
      </div>

      <button type="button" class="btn btn-primary checkout-btn" ${lines.length === 0 ? "disabled" : ""} onclick="checkout()">
        ${icon('check', 16)} بيع وإتمام الفاتورة (${money(total)})
      </button>
    </div>
  `;
}




function renderProductGridInner() {
  const filtered = state.products.filter(p =>
    (state.category === "الكل" || p.category === state.category) && p.name.includes(state.search)
  );
  if (filtered.length === 0) {
    return `<div class="empty-state" style="grid-column:1/-1"><div class="empty-state-icon">${icon("search", 34)}</div><p>لا توجد منتجات مطابقة للبحث</p></div>`;
  }
  return filtered.map(p => {
    const tracked = p.trackStock !== false;
    const soldOut = tracked && p.stock <= 0;
    const low = tracked && p.stock <= p.threshold;
    const inCartItem = state.cart.find(item => item.id === p.id);
    const cartQty = inCartItem ? inCartItem.qty : 0;

    return `
    <div class="product-card ${soldOut ? "pcard-disabled" : ""} ${cartQty > 0 ? "pcard-in-cart" : ""}" data-prod-id="${p.id}" onclick="if(!${soldOut}) addToCart(${p.id})">
      <div class="pcard-top">
        <span class="pcard-icon-chip">${icon(CAT_EMOJI[p.category] || "catHot", 26)}</span>
        ${cartQty > 0 ? `<span class="pcard-cart-badge">${cartQty}</span>` : (soldOut ? `<span class="pcard-badge pcard-sold">نفد</span>` : (low ? `<span class="pcard-badge pcard-low">قليل</span>` : ''))}
      </div>
      <div class="pcard-body">
        <div class="product-name">${esc(p.name)}</div>
        <div class="product-meta">
          <span class="product-price">${money(p.price)}</span>
          ${tracked ? `<span class="product-stock ${low ? "low" : ""}">${p.stock}</span>` : ""}
        </div>
      </div>
    </div>
  `;
  }).join("");
}


function updateCartTotalsOnly(cartPanel) {
  const { subtotal, discountAmount, total } = cartTotals();
  const subEl = cartPanel.querySelector(".val-subtotal");
  const discEl = cartPanel.querySelector(".val-discount");
  const totEl = cartPanel.querySelector(".val-total");
  if (subEl) subEl.textContent = money(subtotal);
  if (discEl) discEl.textContent = "-" + money(discountAmount);
  if (totEl) totEl.textContent = money(total);

  const checkoutBtn = cartPanel.querySelector(".checkout-btn");
  if (checkoutBtn) {
    if (state.cart.length === 0) {
      checkoutBtn.setAttribute("disabled", "true");
      checkoutBtn.innerHTML = `${icon('check', 16)} بيع وإتمام الفاتورة`;
    } else {
      checkoutBtn.removeAttribute("disabled");
      checkoutBtn.innerHTML = `${icon('check', 16)} بيع وإتمام الفاتورة (${money(total)})`;
    }
  }
}

function updatePOSCartOnly(changedId) {
  if (state.view !== "pos") { renderMain(); return; }
  const cartPanel = document.querySelector(".cart-panel");
  if (!cartPanel) { renderMain(); return; }

  const hasChangedId = changedId !== undefined && changedId !== null;
  const linesWrap = cartPanel.querySelector(".cart-lines");
  const cartItem = hasChangedId ? state.cart.find(l => l.id === changedId) : undefined;
  const lineEl = hasChangedId && linesWrap ? linesWrap.querySelector(`.cart-line[data-cart-id="${changedId}"]`) : null;

  // تحديث شارة الكمية في بطاقة المنتج
  if (hasChangedId) {
    const cardEl = document.querySelector(`.product-card[data-prod-id="${changedId}"]`);
    if (cardEl) {
      const currentQty = cartItem ? cartItem.qty : 0;
      let badge = cardEl.querySelector(".pcard-cart-badge");

      if (currentQty > 0) {
        // تحديث أو إنشاء الشارة
        if (badge) {
          badge.textContent = currentQty;
        } else {
          badge = document.createElement("span");
          badge.className = "pcard-cart-badge";
          badge.textContent = currentQty;
          const topDiv = cardEl.querySelector(".pcard-top");
          if (topDiv) {
            // إزالة أي شارة أخرى (نفد / قليل) واستبدالها
            const existingBadge = topDiv.querySelector(".pcard-badge");
            if (existingBadge) existingBadge.remove();
            topDiv.appendChild(badge);
          }
        }
        cardEl.classList.add("pcard-in-cart");
      } else {
        // إزالة الشارة عند إزالة المنتج من السلة
        if (badge) badge.remove();
        cardEl.classList.remove("pcard-in-cart");
      }
    }
  }


  if (lineEl && cartItem) {
    const qtyVal = lineEl.querySelector(".qty-value");
    if (qtyVal) qtyVal.textContent = cartItem.qty;
    updateCartTotalsOnly(cartPanel);
    return;
  }

  if (lineEl && !cartItem) {
    lineEl.remove();
    if (state.cart.length === 0) { cartPanel.innerHTML = renderCartPanelInner(); return; }
    updateCartTotalsOnly(cartPanel);
    return;
  }

  if (!lineEl && cartItem && linesWrap) {
    const wrapper = document.createElement("div");
    wrapper.innerHTML = renderCartLineHtml(cartItem);
    linesWrap.appendChild(wrapper.firstElementChild);
    linesWrap.scrollTop = linesWrap.scrollHeight;
    updateCartTotalsOnly(cartPanel);
    return;
  }

  cartPanel.innerHTML = renderCartPanelInner();
}

function updatePOSProductsOnly() {
  if (state.view === "pos") {
    const grid = document.querySelector(".product-grid");
    const pillRow = document.querySelector(".pill-row");
    if (grid) grid.innerHTML = renderProductGridInner();
    if (pillRow) {
      pillRow.innerHTML = CATEGORIES.map(c => `<button class="pill ${state.category === c ? "active" : ""}" onclick="setCategory('${c}')">${c}</button>`).join("");
    }
    return;
  }
  renderMain();
}

function cartLines() {
  return state.cart.map(l => ({ ...l, product: state.products.find(p => p.id === l.id) })).filter(l => l.product);
}

function cartTotals() {
  const lines = cartLines();
  const subtotal = lines.reduce((s, l) => s + l.product.price * l.qty, 0);
  // ✅ الدينار الليبي يستخدم 3 خانات عشرية — نحافظ على الدقة بدل Math.round
  const discountAmount = Number((subtotal * state.discount / 100).toFixed(3));
  return { subtotal, discountAmount, total: Number((subtotal - discountAmount).toFixed(3)) };
}

// ================= عملية البيع المحدثة =================
async function checkout() {
  if (enforceLicenseExpiration()) return;
  const lines = cartLines();
  if (lines.length === 0) return;
  if (!state.currentShift) { showToast("افتح وردية أولاً قبل البيع"); openShiftModal(); return; }

  const { subtotal, discountAmount, total } = cartTotals();

  const paymentMethod = state.paymentMethod || "cash";
  const paymentMethodLabel = paymentMethod === "card" ? "بطاقة / سداد إلكتروني" : "نقداً (كاش)";
  const cashierName = (state.authRole && (state.authRole.name || state.authRole.email)) || ROLE_LABELS[state.role];
  const timeLabel = new Date().toLocaleTimeString("ar", { hour: "2-digit", minute: "2-digit" });
  const buzzerNote = (state.customBuzzer || "").trim();

  // ✅ مسح السلة والبيجر فوراً للاستجابة السريعة بالواجهة
  state.cart = [];
  state.discount = 0;
  state.customBuzzer = "";

  // ✅ الحفظ في قاعدة البيانات واسترجاع رقم الفاتورة ورقم المناداة
  let invoiceNumber = null;
  let orderToken = null;

  if (window.electronAPI && window.electronAPI.dbCheckout) {
    const dbLines = lines.map(l => ({ id: l.id, name: l.product.name, qty: l.qty, price: l.product.price }));
    try {
      const freshState = await window.electronAPI.dbCheckout({ lines: dbLines, total, paymentMethod, buzzerNote });
      applyFreshState(freshState);
      // استخرج رقم الفاتورة ورقم المناداة من أحدث عملية مسجّلة
      if (freshState && freshState.recentTx && freshState.recentTx.length > 0) {
        invoiceNumber = freshState.recentTx[0].invoiceNumber || null;
        orderToken = freshState.recentTx[0].orderToken || invoiceNumber || 1;
      }
    } catch (err) {
      console.error("[Checkout] DB error:", err);
      showToast("تعذّر حفظ الفاتورة — تحقق من قاعدة البيانات");
      renderMain();
      return;
    }
  }

  const finalToken = orderToken || invoiceNumber || 1;
  showToast(`🎉 تم حفظ الطلب بنجاح! رقم المناداة: #${finalToken}`);

  // ✅ طباعة الفاتورة مرة واحدة مع رقم المناداة الكبير ورقم الفاتورة
  if (window.electronAPI && window.electronAPI.printReceipt) {
    window.electronAPI.printReceipt({
      storeName: state.receiptSettings.storeName,
      address: state.receiptSettings.address,
      phone: state.receiptSettings.phone,
      footerMsg: state.receiptSettings.footerMsg,
      logoDataUrl: state.receiptSettings.logoDataUrl,
      printerName: state.receiptSettings.printerName,
      paperWidth: state.receiptSettings.paperWidth,
      invoiceNumber,
      orderToken: finalToken,
      buzzerNote: buzzerNote,
      date: new Date().toLocaleDateString("ar-LY"),
      time: timeLabel,
      cashier: cashierName,
      paymentMethodLabel: paymentMethodLabel,
      items: lines.map(l => ({ name: l.product.name, qty: l.qty, lineTotal: money(l.product.price * l.qty) })),
      subtotal: money(subtotal),
      discountAmount: money(discountAmount),
      total: money(total),
    });
  }

  if (cloud.saveSaleToCloud) {
    cloud.saveSaleToCloud({
      total, subtotal, discount: discountAmount,
      itemsCount: lines.length,
      paymentMethod,
      orderToken: finalToken,
      buzzerNote: buzzerNote,
      items: lines.map(l => ({ id: l.id, name: l.product.name, qty: l.qty, price: l.product.price })),
      cashier: cashierName,
      timestamp: new Date().toISOString(),
      time: timeLabel,
      refunded: false,
    });
  }

  syncToCloud();
  renderMain();
}

function openProductModal(mode, id) { state.productModal = { mode, id: id || null }; render(); }
function closeProductModal() { state.productModal = null; render(); }

async function saveProduct(e) {
  e.preventDefault();
  const f = e.target;
  const trackStock = f.trackStock ? f.trackStock.checked : true;
  const data = {
    name: f.name.value.trim(),
    category: f.category.value,
    price: Number(f.price.value),
    trackStock,
    stock: trackStock ? Number(f.stock.value || 0) : 0,
    threshold: trackStock ? (Number(f.threshold.value) || 10) : 0,
  };
  if (!data.name || isNaN(data.price)) return;

  let product;
  if (state.productModal && state.productModal.mode === "edit") {
    product = { id: state.productModal.id, ...data };
    showToast("تم تعديل المنتج");
  } else {
    product = { id: Date.now(), ...data };
    showToast("تمت إضافة المنتج");
  }

  if (window.electronAPI && window.electronAPI.dbSaveProduct) {
    const products = await window.electronAPI.dbSaveProduct(product);
    if (products) state.products = products;
  } else {
    const existing = state.products.find(p => p.id === product.id);
    if (existing) Object.assign(existing, product); else state.products.push(product);
  }

  state.productModal = null;
  render();
  syncToCloud();
}

function askDelete(id) { state.deleteId = id; render(); }
function cancelDelete() { state.deleteId = null; render(); }

async function confirmDelete() {
  const id = state.deleteId;
  if (window.electronAPI && window.electronAPI.dbDeleteProduct) {
    const products = await window.electronAPI.dbDeleteProduct(id);
    if (products) state.products = products;
  } else {
    state.products = state.products.filter(p => p.id !== id);
  }
  state.deleteId = null;
  render();
  syncToCloud();
  showToast("تم حذف المنتج");
}

function openStockModal(id) { state.stockModal = { id }; render(); }
function closeStockModal() { state.stockModal = null; render(); }

async function applyStockAdd(e) {
  e.preventDefault();
  const amount = Number(e.target.amount.value || 0);
  const id = state.stockModal.id;
  if (amount) {
    if (window.electronAPI && window.electronAPI.dbAdjustStock) {
      const products = await window.electronAPI.dbAdjustStock({ id, amount });
      if (products) state.products = products;
    } else {
      const p = state.products.find(x => x.id === id);
      if (p) p.stock += amount;
    }
  }
  state.stockModal = null;
  render();
  syncToCloud();
  showToast("تم تحديث المخزون");
}

function openShortageModal() { state.shortageModal = true; render(); }
function closeShortageModal() { state.shortageModal = false; render(); }

async function reportShortage(e) {
  e.preventDefault();
  const f = e.target;
  const selectVal = f.shortageName.value;
  const name = (selectVal === "__new__" ? f.shortageNameCustom.value : selectVal).trim();
  const note = f.shortageNote.value.trim();
  if (!name) return;
  const reporter = (state.authRole && (state.authRole.name || state.authRole.email)) || ROLE_LABELS[state.role];

  if (window.electronAPI && window.electronAPI.dbAddShortage) {
    const result = await window.electronAPI.dbAddShortage({ name, note, reporter });
    if (result) {
      state.shortages = result.shortages || state.shortages;
      state.shortageCatalog = result.catalog || state.shortageCatalog;
    }
  } else {
    state.shortages.unshift({ id: Date.now(), name, note, reporter, createdAt: Date.now() });
    if (!state.shortageCatalog.some(c => c.name === name)) {
      state.shortageCatalog.push({ id: Date.now(), name });
    }
  }
  state.shortageModal = false;
  state.shortageInputMode = "select";
  render();
  syncToCloud();
  showToast("تم الإبلاغ عن النقص");
}

function toggleShortageNameInput(select) {
  state.shortageInputMode = select.value === "__new__" ? "text" : "select";
  const customWrap = document.getElementById("shortageCustomWrap");
  const customInput = customWrap ? customWrap.querySelector("input") : null;
  if (customWrap) customWrap.style.display = state.shortageInputMode === "text" ? "" : "none";
  if (customInput) customInput.required = state.shortageInputMode === "text";
}

async function resolveShortageItem(id) {
  if (window.electronAPI && window.electronAPI.dbResolveShortage) {
    const list = await window.electronAPI.dbResolveShortage(id);
    state.shortages = list || state.shortages.filter(s => s.id !== id);
  } else {
    state.shortages = state.shortages.filter(s => s.id !== id);
  }
  renderMain();
  syncToCloud();
  showToast("تم تعليم النقص كمتوفر");
}

// ================= المشتريات والموردون =================
function openPurchaseModal() { state.purchaseModal = true; render(); }
function closePurchaseModal() { state.purchaseModal = false; render(); }

async function savePurchase(e) {
  e.preventDefault();
  const f = e.target;
  const supplierName = f.supplierName.value.trim();
  const description = f.description.value.trim();
  const cost = Number(f.cost.value) || 0;
  const productId = f.productId.value ? Number(f.productId.value) : null;
  const restockQty = f.restockQty.value ? Number(f.restockQty.value) : null;
  if (!description || !cost) return;

  let supplierId = null;
  if (supplierName) {
    const existing = state.suppliers.find(s => s.name === supplierName);
    if (existing) {
      supplierId = existing.id;
    } else if (window.electronAPI && window.electronAPI.dbAddSupplier) {
      const list = await window.electronAPI.dbAddSupplier({ name: supplierName });
      state.suppliers = list || state.suppliers;
      const created = state.suppliers.find(s => s.name === supplierName);
      supplierId = created ? created.id : null;
    }
  }

  if (window.electronAPI && window.electronAPI.dbAddPurchase) {
    const result = await window.electronAPI.dbAddPurchase({
      supplierId, description, cost, productId, restockQty, createdBy: currentUserLabel(),
    });
    if (result) {
      state.purchases = result.purchases || state.purchases;
      state.products = result.products || state.products;
      // نحدّث كشوفات الموردين بعد تسجيل الشراء
      if (window.electronAPI.dbGetState) {
        const fresh = await window.electronAPI.dbGetState();
        if (fresh && fresh.suppliers) state.suppliers = fresh.suppliers;
      }
    }
  }

  state.purchaseModal = false;
  render();
  syncToCloud();
  showToast("تم تسجيل عملية الشراء");
}

function openPaymentModal(supplierId, supplierName) {
  state.paymentModal = { supplierId, supplierName };
  render();
}
function closePaymentModal() { state.paymentModal = null; render(); }

async function submitSupplierPayment(e) {
  e.preventDefault();
  const f = e.target;
  const amount = Number(f.amount.value) || 0;
  const note = f.note.value.trim();
  if (!amount || !state.paymentModal) return;
  const supplierId = state.paymentModal.supplierId;

  if (window.electronAPI && window.electronAPI.dbAddSupplierPayment) {
    const list = await window.electronAPI.dbAddSupplierPayment({ supplierId, amount, note, createdBy: currentUserLabel() });
    state.suppliers = list || state.suppliers;
  } else {
    const s = state.suppliers.find(x => x.id === supplierId);
    if (s) { s.totalPaid = (s.totalPaid || 0) + amount; s.balance = s.totalSpent - s.totalPaid; }
  }

  state.paymentModal = null;
  render();
  syncToCloud();
  showToast("تم تسجيل الدفعة");
}

// ================= النسخ الاحتياطي =================
async function exportBackup() {
  if (!window.electronAPI || !window.electronAPI.backupExport) { showToast("النسخ الاحتياطي غير متاح بهذا الوضع"); return; }
  const result = await window.electronAPI.backupExport();
  if (result && result.ok) showToast("تم تصدير النسخة الاحتياطية بنجاح");
  else if (result && result.error) showToast("تعذّر التصدير: " + result.error);
}

async function restoreBackup() {
  if (!window.electronAPI || !window.electronAPI.backupRestore) { showToast("الاستعادة غير متاحة بهذا الوضع"); return; }
  if (!confirm("سيتم استبدال كل بياناتك الحالية بالنسخة المُستعادة. هل تريد المتابعة؟")) return;
  const result = await window.electronAPI.backupRestore();
  if (result && result.ok) {
    showToast("تم الاستعادة بنجاح — سيُعاد تشغيل التطبيق الآن");
    setTimeout(() => { if (window.electronAPI.relaunchApp) window.electronAPI.relaunchApp(); }, 1200);
  } else if (result && result.error) {
    showToast("تعذّرت الاستعادة: " + result.error);
  }
}

async function saveReceiptSettings(e) {
  e.preventDefault();
  const f = e.target;
  const newSettings = {
    storeName: f.storeName.value.trim(),
    address: f.address.value.trim(),
    phone: f.phone.value.trim(),
    footerMsg: f.footerMsg.value.trim(),
    logoDataUrl: state.receiptSettings.logoDataUrl || "",
    printerName: f.printerName.value,
    paperWidth: f.paperWidth.value,
  };
  if (window.electronAPI && window.electronAPI.dbSaveReceiptSettings) {
    const saved = await window.electronAPI.dbSaveReceiptSettings(newSettings);
    state.receiptSettings = saved || newSettings;
  } else {
    state.receiptSettings = newSettings;
  }
  render();
  syncToCloud();
  showToast("تم حفظ إعدادات الفاتورة");
}

async function pickLogo() {
  if (!window.electronAPI || !window.electronAPI.pickLogoImage) { showToast("هذه الميزة غير متاحة بهذا الوضع"); return; }
  const result = await window.electronAPI.pickLogoImage();
  if (!result || !result.ok) {
    if (result && result.error) showToast(result.error);
    return;
  }
  const updated = { ...state.receiptSettings, logoDataUrl: result.dataUrl };
  if (window.electronAPI.dbSaveReceiptSettings) {
    const saved = await window.electronAPI.dbSaveReceiptSettings(updated);
    state.receiptSettings = saved || updated;
  } else {
    state.receiptSettings = updated;
  }
  render();
  syncToCloud();
  showToast("تم تحديث الشعار");
}

async function removeLogo() {
  const updated = { ...state.receiptSettings, logoDataUrl: "" };
  if (window.electronAPI && window.electronAPI.dbSaveReceiptSettings) {
    const saved = await window.electronAPI.dbSaveReceiptSettings(updated);
    state.receiptSettings = saved || updated;
  } else {
    state.receiptSettings = updated;
  }
  render();
  syncToCloud();
  showToast("تمت إزالة الشعار المخصص");
}

// ================= عرض الشاشات والترخيص =================
function getLicenseRemainingInfo() {
  const info = state.licenseInfo;
  if (!info || !info.licenseKey) return null;
  const expiresAt = info.lastValidExpiresAt;
  if (!expiresAt) {
    return {
      statusText: "اشتراك دائم (مدى الحياة)",
      subText: "غير مقيّد بتاريخ انتهاء",
      days: 99999,
      isLifetime: true,
      urgent: false,
      color: "var(--sage-dark)"
    };
  }
  const diffMs = expiresAt - Date.now();
  if (diffMs <= 0) {
    return {
      statusText: "انتهت صلاحية الاشتراك",
      subText: "تم إيقاف النظام لحين التجديد",
      days: 0,
      expired: true,
      urgent: true,
      color: "var(--rust-dark)"
    };
  }
  const days = Math.ceil(diffMs / (24 * 60 * 60 * 1000));
  const expDate = new Date(expiresAt).toLocaleDateString("ar-LY", { year: "numeric", month: "long", day: "numeric" });
  if (days <= 7) {
    return {
      statusText: `متبقي ${days} ${days === 1 ? "يوم" : days === 2 ? "يومان" : "أيام"} على الانتهاء`,
      subText: `تاريخ الانتهاء: ${expDate}`,
      days,
      urgent: true,
      color: "var(--rust-dark)"
    };
  }
  return {
    statusText: `متبقي على الانتهاء: ${days} يوم`,
    subText: `تاريخ الانتهاء: ${expDate}`,
    days,
    urgent: false,
    color: "var(--sage-dark)"
  };
}

function renderLicenseBadge(compact = false) {
  const lic = getLicenseRemainingInfo();
  if (!lic) return "";
  if (compact) {
    return `
      <div class="topbar-alert-badge ${lic.urgent ? "urgent" : ""}" title="تنبيه الاشتراك: ${lic.statusText} (${lic.subText})" onclick="setView('settings')">
        ${icon("warning", 14, lic.urgent ? "icon-warning" : "")} 
        <span>${lic.isLifetime ? "ترخيص دائم" : `${lic.statusText}`}</span>
      </div>
    `;
  }
  return `
    <div class="license-badge-sidebar ${lic.urgent ? "urgent" : ""}" onclick="setView('settings')" style="cursor:pointer">
      <div style="display:flex;align-items:center;gap:6px;font-weight:700;">
        ${icon("warning", 14, lic.urgent ? "icon-warning" : "")} <span>${lic.statusText}</span>
      </div>
      <span class="license-sub">${lic.subText}</span>
    </div>
  `;
}

function renderLicenseWarningBanner() {
  // تم تحويل تنبيه انتهاء الاشتراك إلى أيقونة إشعار تحذيرية أنيقة في الشريط العلوي والسايدبار
  return "";
}

function enforceLicenseExpiration() {
  if (state.licenseState === "valid" || state.licenseState === "grace") {
    const info = state.licenseInfo;
    if (info && info.lastValidExpiresAt && Date.now() > info.lastValidExpiresAt) {
      state.licenseState = "invalid";
      state.licenseError = "انتهت صلاحية هذا الاشتراك. تم إيقاف تشغيل النظام لحين التجديد.";
      render();
      return true;
    }
  }
  return false;
}

function renderLicenseChecking() {
  return `
    <div class="gate">
      <div class="gate-logo-row">${renderLogo(52)}</div>
      <div class="gate-sub">جاري التحقق من الترخيص...</div>
    </div>
  `;
}

function renderLicenseKeyScreen() {
  return `
    <div class="gate">
      <div class="gate-logo-row">${renderLogo(52)}</div>
      <div class="gate-sub">نظام إدارة المقهى</div>
      <form class="modal" style="max-width:380px" onsubmit="submitLicenseKey(event)">
        <div class="modal-head"><h3>تفعيل الترخيص</h3></div>
        <div class="field"><label>مفتاح الترخيص</label><input name="licenseKey" placeholder="XXXX-XXXX-XXXX-XXXX" required autofocus /></div>
        ${state.licenseError ? `<p style="color:var(--rust-dark);font-size:12px;margin:0 0 10px">${state.licenseError}</p>` : ""}
        <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">تفعيل</button>
      </form>
    </div>
  `;
}

function renderLicenseBlockedScreen() {
  return `
    <div class="gate">
      <div class="gate-logo-row">${renderLogo(52)}</div>
      <div class="gate-sub">نظام إدارة المقهى</div>
      <div class="modal" style="max-width:380px;text-align:center">
        <div class="confirm-icon">${icon("warning", 28, "icon-warning")}</div>
        <h3 style="margin-bottom:8px">التطبيق غير مفعّل</h3>
        <p class="confirm-text" style="margin-bottom:18px">${state.licenseError || "تعذّر التحقق من صلاحية الترخيص"}</p>
        <button class="btn btn-primary" style="width:100%;justify-content:center" onclick="retryLicenseCheck()">إعادة المحاولة</button>
        <button class="btn btn-outline" style="width:100%;justify-content:center;margin-top:8px" onclick="enterNewLicenseKey()">إدخال مفتاح ترخيص آخر</button>
      </div>
    </div>
  `;
}

function renderRoleGate() {
  const cards = ["admin", "manager", "cashier"].map(r => `
    <button class="role-card" onclick="selectRole('${r}')">
      <div class="role-icon" style="background:${r === "admin" ? "var(--rust-soft)" : r === "manager" ? "var(--sage-soft)" : "var(--gold-soft)"};color:${r === "admin" ? "var(--rust)" : r === "manager" ? "var(--sage-dark)" : "#8A6D3B"}">${icon(ROLE_ICON[r], 22)}</div>
      <div class="role-title">${ROLE_LABELS[r]}</div>
      <div class="role-desc">${ROLE_DESC[r]}</div>
    </button>
  `).join("");
  return `
    <div class="gate">
      <div class="gate-logo-row">${renderLogo(52)}</div>
      <div class="gate-sub">نظام إدارة المقهى</div>
      <div class="gate-grid">${cards}</div>
      <div class="gate-note">اختر دورًا لمعاينة الصلاحيات الخاصة به</div>
    </div>
  `;
}

function renderSidebar() {
  const items = NAV_ITEMS.filter(n => n.roles.includes(state.role) && (n.key !== "staff" || auth.isAuthConfigured()))
    .map(n => `
    <button class="nav-item ${state.view === n.key ? "active" : ""}" onclick="setView('${n.key}')">
      <span class="ic">${icon(n.icon, 18)}</span>${n.label}
    </button>
  `).join("");
  const displayName = state.authRole ? (state.authRole.name || state.authRole.email) : ROLE_LABELS[state.role];
  const logoutHandler = state.authUser ? "logoutAccount()" : "switchRole()";
  const logoutLabel = state.authUser ? "تسجيل الخروج" : "تبديل الدور";
  return `
    <aside class="sidebar">
      <div>
        <div class="logo-row"><div class="brand-logo">${renderLogo(26)}</div><span class="brand-word font-display">${(state.receiptSettings && state.receiptSettings.storeName) || "بُنّ"}</span></div>
        <div class="brand-sub">نظام إدارة المقهى</div>
        <nav class="nav">${items}</nav>
      </div>
      <div>
        ${renderLicenseBadge()}
        ${renderCloudBadge()}
        <div class="role-box">
          <div class="role-avatar">${icon(ROLE_ICON[state.role], 16)}</div>
          <div class="role-name">${displayName}</div>
        </div>
        <button class="logout-btn" onclick="${logoutHandler}">${icon("logout", 16)} ${logoutLabel}</button>
      </div>
    </aside>
  `;
}

function renderLogo(size) {
  if (state.receiptSettings && state.receiptSettings.logoDataUrl) {
    return `<img src="${state.receiptSettings.logoDataUrl}" class="custom-logo-img" style="width:${size}px;height:${size}px;object-fit:contain" />`;
  }
  return logoMark(size);
}

function renderTopBar() {
  const storeName = (state.receiptSettings && state.receiptSettings.storeName) || "بُنّ";
  const displayName = state.authRole ? (state.authRole.name || state.authRole.email) : ROLE_LABELS[state.role];
  const logoutHandler = state.authUser ? "logoutAccount()" : "switchRole()";

  // للكاشير فقط: إزالة صفحات التنقل، وترك شريط مبسط سريع مع زر خروج فقط لتسريع البيع
  if (state.role === "cashier") {
    return `
      <header class="topbar topbar-pos-minimal">
        <div class="topbar-brand">
          <div class="brand-logo">${renderLogo(22)}</div>
          <span class="brand-word font-display">${storeName}</span>
        </div>
        <div style="flex:1"></div>
        <div class="topbar-right">
          ${state.currentShift
            ? `<div class="topbar-shift-group">
                 <span class="topbar-shift-pill open">${icon("check", 13)} درج: <b>${money(state.currentShift.expectedCash)}</b></span>
                 <button class="topbar-btn" onclick="openExpenseModal()" title="تسجيل مصروف">${icon("money", 13)} مصروف</button>
               </div>`
            : `<div class="topbar-shift-group">
                 <span class="topbar-shift-pill closed">${icon("warning", 13, "icon-warning")} مغلقة</span>
                 <button class="topbar-btn topbar-btn-primary" onclick="openShiftModal()">${icon("lock", 13)} فتح وردية</button>
               </div>`
          }
          <button class="topbar-btn" onclick="openShortageModal()" title="الإبلاغ عن نقص بضاعة">${icon("warning", 13, "icon-warning")} نقص</button>
          <div class="topbar-role-chip">${icon(ROLE_ICON[state.role], 13)} ${displayName}</div>
          <button class="topbar-btn" onclick="${logoutHandler}" title="خروج من نقطة البيع"
            style="background:#1D4ED8;color:#fff;border-color:#1D4ED8;padding:7px 18px;font-size:13.5px;font-weight:800;gap:6px;border-radius:var(--radius-md);cursor:pointer;box-shadow:0 2px 8px rgba(29,78,216,0.25);">
            ← خروج
          </button>
        </div>
      </header>
    `;
  }

  // للأدمن والمدير: بقاء الصفحات والتنقل كاملاً كما هو
  const items = NAV_ITEMS.filter(n => n.roles.includes(state.role) && (n.key !== "staff" || auth.isAuthConfigured()));

  const shiftBadge = state.currentShift
    ? `<div class="topbar-shift-group">
         <span class="topbar-shift-pill open">${icon("check", 13)} درج: <b>${money(state.currentShift.expectedCash)}</b></span>
         <button class="topbar-btn" onclick="openExpenseModal()" title="تسجيل مصروف">${icon("money", 13)} مصروف</button>
         <button class="topbar-btn" onclick="openCloseShiftModal()" title="إغلاق الوردية">${icon("lock", 13)} إغلاق</button>
       </div>`
    : `<div class="topbar-shift-group">
         <span class="topbar-shift-pill closed">${icon("warning", 13, "icon-warning")} مغلقة</span>
         <button class="topbar-btn topbar-btn-primary" onclick="openShiftModal()">${icon("lock", 13)} فتح وردية</button>
       </div>`;

  return `
    <header class="topbar">
      <div class="topbar-brand">
        <div class="brand-logo">${renderLogo(22)}</div>
        <span class="brand-word font-display">${storeName}</span>
      </div>
      <nav class="topbar-nav">
        ${items.map(n => `
          <button class="topbar-nav-item ${state.view === n.key ? "active" : ""}" onclick="setView('${n.key}')" title="${n.label}">
            ${icon(n.icon, 16)}<span>${n.label}</span>
          </button>
        `).join("")}
      </nav>
      <div class="topbar-right">
        ${shiftBadge}
        <button class="topbar-btn" onclick="openShortageModal()" title="الإبلاغ عن نقص بضاعة">${icon("warning", 13, "icon-warning")} نقص</button>
        ${renderLicenseBadge(true)}
        ${renderCloudBadge(true)}
        <div class="topbar-role-chip">${icon(ROLE_ICON[state.role], 13)} ${displayName}</div>
        <button class="topbar-logout-btn" onclick="${logoutHandler}" title="${state.authUser ? "تسجيل الخروج" : "تبديل الدور"}">${icon("logout", 15)}</button>
      </div>
    </header>
  `;
}


function renderCloudBadge(compact) {
  const c = state.cloud;
  if (compact) {
    if (!c.configured) return `<div class="cloud-badge-compact cloud-off" title="المزامنة غير مُفعّلة">${icon("cloudOff", 15)}</div>`;
    if (c.error) return `<div class="cloud-badge-compact cloud-error" title="تعذر الاتصال بالسحابة" onclick="retryCloudSync()">${icon("alertCircle", 15)}</div>`;
    const cls = c.paused ? "cloud-paused" : c.online ? "cloud-online" : "cloud-offline";
    const title = c.paused ? "المزامنة متوقفة" : c.online ? "متصل ومتزامن" : "غير متصل";
    return `<div class="cloud-badge-compact ${cls}" title="${title}">${icon(c.online && !c.paused ? "cloudOn" : "cloudOff", 15)}</div>`;
  }
  if (!c.configured) return `<div class="cloud-badge cloud-off">${icon("cloudOff", 14)} المزامنة غير مُفعّلة</div>`;
  if (c.error) {
    return `
      <div class="cloud-badge cloud-error">${icon("alertCircle", 14)} تعذر الاتصال</div>
      <button class="logout-btn" style="margin-bottom:10px" onclick="retryCloudSync()">${icon("refresh", 15)} محاولة مجدداً</button>
    `;
  }
  const label = c.paused ? `${icon("pause", 13)} متوقفة` : c.online ? `${icon("cloudOn", 13)} متصل ومتزامن` : `${icon("cloudOff", 13)} غير متصل`;
  const cls = c.paused ? "cloud-paused" : c.online ? "cloud-online" : "cloud-offline";
  return `
    <div class="cloud-badge ${cls}">${label}</div>
    <button class="logout-btn" style="margin-bottom:10px" onclick="toggleSync()">${c.paused ? icon("play", 15) + " تفعيل" : icon("pause", 15) + " إيقاف"}</button>
  `;
}

function renderDashboard() {
  const lowStock = state.products.filter(p => p.trackStock !== false && p.stock <= p.threshold);
  const maxSales = Math.max(...(state.weekly.map(d => d.sales).concat([1])));
  const stats = [
    { label: "مبيعات الوردية الحالية", value: money(state.todaySales), bg: "var(--sage-soft)", icon: "money" },
    { label: "عدد الطلبات", value: state.ordersCount, bg: "var(--gold-soft)", icon: "receipt" },
    { label: "مبيعات الشهر", value: money(state.monthSales), bg: "var(--rust-soft)", icon: "trending" },
  ];
  if (state.currentShift) {
    stats.push({ label: "نقد الدرج المتوقع", value: money(state.currentShift.expectedCash), bg: "var(--sage-soft)", icon: "money" });
  } else if (state.role === "admin") {
    // ✅ صافي الربح الحقيقي = مبيعات الشهر - مشتريات الشهر - مصروفات الشهر
    const monthPurchases = (state.purchases || []).reduce((s, p) => s + (p.cost || 0), 0);
    const monthExpenses = (state.shiftExpenses || []).reduce((s, e) => s + (e.amount || 0), 0);
    const realProfit = Math.max(0, state.monthSales - monthPurchases - monthExpenses);
    stats.push({ label: "صافي الربح التقريبي (شهر)", value: money(realProfit), bg: "var(--sage-soft)", icon: "roleAdmin" });
  }

  return `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px">
      <h1 class="page-title">لوحة التحكم</h1>
      ${(state.role === "admin" || state.role === "manager") ? `
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn btn-outline" onclick="confirmCloseWeek()">${icon("lock", 15)} إغلاق الأسبوع</button>
          <button class="btn btn-outline" onclick="confirmCloseMonth()">${icon("lock", 15)} إغلاق الشهر</button>
          ${state.currentShift
            ? `<button class="btn btn-outline" onclick="openExpenseModal()">${icon("money", 14)} تسجيل مصروف درج</button>
               <button class="btn btn-primary" onclick="openCloseShiftModal()">${icon("lock", 15)} إغلاق الوردية</button>`
            : `<button class="btn btn-primary" onclick="openShiftModal()">${icon("lock", 15)} فتح وردية</button>`}
        </div>
      ` : ""}
    </div>
    <p class="page-sub">${state.currentShift ? `الوردية مفتوحة منذ ${new Date(state.currentShift.openedAt).toLocaleTimeString("ar-LY", { hour: "2-digit", minute: "2-digit" })} — كاش: ${money(state.currentShift.cashSales || 0)} · بطاقة: ${money(state.currentShift.cardSales || 0)}${state.currentShift.totalExpenses ? ` · مصروفات: -${money(state.currentShift.totalExpenses)}` : ""}` : "لا توجد وردية مفتوحة حاليًا"}</p>

    <div class="stat-grid">
      ${stats.map(s => `
        <div class="card stat-card">
          <div class="stat-icon" style="background:${s.bg}">${icon(s.icon, 17)}</div>
          <div class="stat-value">${s.value}</div>
          <div class="stat-label">${s.label}</div>
        </div>
      `).join("")}
    </div>

    <div class="dash-grid">
      <div class="card panel">
        <h3>مبيعات الأسبوع</h3>
        <div class="chart">
          ${state.weekly.map(d => `
            <div class="chart-col">
              <div class="chart-value">${d.sales}</div>
              <div class="chart-bar" style="height:${(d.sales / maxSales) * 100}%"></div>
              <div class="chart-label">${d.day}</div>
            </div>
          `).join("")}
        </div>
      </div>
      <div class="card panel">
        <h3>أفضل المنتجات مبيعًا</h3>
        ${state.topProducts.map(p => `
          <div class="list-row"><span class="td-with-icon">${icon("star", 14, "icon-star")} ${p.name}</span><span class="muted">${p.qty} قطعة</span></div>
        `).join("")}
      </div>
    </div>

    <div class="dash-grid-2">
      <div class="card panel">
        <h3>${icon("warning", 15, "icon-warning")} منتجات قليلة المخزون</h3>
        ${lowStock.length === 0 ? `<p class="muted" style="font-size:12px">لا توجد تنبيهات حاليًا</p>` :
          lowStock.map(p => `<div class="list-row"><span>${esc(p.name)}</span><span class="badge badge-low">${p.stock} متبقي</span></div>`).join("")}
      </div>
      <div class="card panel">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <h3>أحدث العمليات والمرتجعات</h3>
          <button class="btn btn-outline" style="padding:4px 8px;font-size:11px;" onclick="exportSalesToCSV()">تصدير CSV</button>
        </div>
        ${state.recentTx.map((t, idx) => `
          <div class="list-row" style="${t.refunded ? 'opacity:0.5;text-decoration:line-through;' : ''}">
            <span class="muted">
              ${t.invoiceNumber ? `<b style="color:var(--sage-dark)">#${t.invoiceNumber}</b> · ` : ""}${t.time || ""} · ${t.itemsCount || (t.items?t.items.length:0)} أصناف
            </span>
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="font-weight:800">${money(t.total)}</span>
              ${!t.refunded ? `<button class="icon-btn-sm icon-btn-danger" style="padding:2px 6px;" title="استرجاع الفاتورة" onclick="refundTransaction(${idx})">إرجاع</button>` : `<span class="badge badge-low">مسترجع</span>`}
            </div>
          </div>
        `).join("")}
      </div>
    </div>
  `;
}

function renderPOS() {
  return `
    <div class="pos-grid">
      <div class="pos-products-col">
        ${state.heldCarts.length > 0 ? `
          <div class="held-orders-bar">
            <span class="held-orders-label">${icon("heldOrders", 14)} طلبات معلقة (${state.heldCarts.length})</span>
            <div class="held-orders-list">
              ${state.heldCarts.map(h => `
                <div class="held-chip">
                  <span>${h.items.length} صنف · ${money(heldCartTotal(h))} <span class="held-chip-time">${h.time}</span></span>
                  <button onclick="resumeHeldCart(${h.id})" title="استكمال الطلب">${icon("resume", 13)}</button>
                  <button onclick="deleteHeldCart(${h.id})" title="حذف الطلب المعلّق" class="held-chip-delete">${icon("delete", 12)}</button>
                </div>
              `).join("")}
            </div>
          </div>
        ` : ""}
        <div class="pos-top-search-row">
          <div class="search-box">
            <input value="${state.search}" oninput="setSearch(this.value)" placeholder="ابحث بالاسم أو الباركود..." />
            <span class="search-icon">${icon("search", 16)}</span>
          </div>
        </div>
        <div class="pill-row">
          ${CATEGORIES.map(c => `<button class="pill ${state.category === c ? "active" : ""}" onclick="setCategory('${c}')">${c}</button>`).join("")}
        </div>
        <div class="product-grid-wrap">
          <div class="product-grid">
            ${renderProductGridInner()}
          </div>
        </div>
      </div>

      <div class="card cart-panel">
        ${renderCartPanelInner()}
      </div>
    </div>
  `;
}

function renderProducts() {
  return `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <div>
        <h1 class="page-title">المنتجات</h1>
        <p class="page-sub" style="margin:0">${state.products.length} منتج في القائمة</p>
      </div>
      <button class="btn btn-primary" onclick="openProductModal('new')">${icon("plus", 15)} إضافة منتج</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>المنتج</th><th>التصنيف</th><th>السعر</th><th>المخزون</th><th>إجراءات</th></tr></thead>
        <tbody>
          ${state.products.map(p => `
            <tr>
              <td class="td-with-icon">${icon(CAT_EMOJI[p.category], 16)} ${esc(p.name)}</td>
              <td class="muted">${esc(p.category)}</td>
              <td style="font-weight:800;color:var(--sage-dark)">${money(p.price)}</td>
              <td>${p.trackStock === false ? `<span class="badge badge-unlimited">غير محدود</span>` : `<span class="badge ${p.stock <= p.threshold ? "badge-low" : "badge-ok"}">${p.stock}</span>`}</td>
              <td>
                <div class="row-actions">
                  <button class="icon-btn" style="background:var(--gold-soft)" onclick="openProductModal('edit',${p.id})">${icon("edit", 14)}</button>
                  ${state.role === "admin" ? `<button class="icon-btn" style="background:var(--rust-soft)" onclick="askDelete(${p.id})">${icon("delete", 14)}</button>` : ""}
                </div>
              </td>
            </tr>
          `).join("")}
        </tbody>
      </table>
    </div>
  `;
}

function renderInventory() {
  const trackedProducts = state.products.filter(p => p.trackStock !== false);
  return `
    <h1 class="page-title">المخزون</h1>
    <p class="page-sub">متابعة الكميات وتنبيهات النقص</p>
    ${state.shortages.length > 0 ? `
      <div class="panel" style="margin-bottom:20px">
        <h3>${icon("warning", 15, "icon-warning")} بلاغات نقص بضاعة (${state.shortages.length})</h3>
        ${state.shortages.map(s => `
          <div class="list-row">
            <span>
              <b>${s.name}</b>${s.note ? ` <span class="muted">— ${s.note}</span>` : ""}
              <span class="muted"> · بلّغ ${s.reporter || "—"}</span>
            </span>
            <button class="btn btn-outline" style="padding:5px 12px;font-size:12px" onclick="resolveShortageItem(${s.id})">${icon("check", 13)} تم التوفير</button>
          </div>
        `).join("")}
      </div>
    ` : ""}
    <div class="inv-grid">
      ${trackedProducts.map(p => {
        const low = p.stock <= p.threshold;
        const pct = Math.min(100, Math.round((p.stock / (p.threshold * 2)) * 100));
        return `
          <div class="card inv-card">
            <div class="inv-head"><span class="td-with-icon">${icon(CAT_EMOJI[p.category], 16)} ${p.name}</span>${low ? icon("warning", 16, "icon-warning") : ""}</div>
            <div class="inv-bar-bg"><div class="inv-bar-fill" style="width:${pct}%;background:${low ? "var(--rust)" : "var(--sage)"}"></div></div>
            <div class="inv-meta"><span>الكمية الحالية: ${p.stock}</span><span>حد التنبيه: ${p.threshold}</span></div>
            <button class="inv-add-btn" onclick="openStockModal(${p.id})">${icon("addStock", 14)} إضافة مخزون</button>
          </div>
        `;
      }).join("")}
    </div>
  `;
}

function renderStaff() {
  return `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <div><h1 class="page-title">الموظفون</h1><p class="page-sub" style="margin:0">حسابات الدخول وصلاحياتها</p></div>
      <button class="btn btn-primary" onclick="openStaffModal()">${icon("plus", 15)} إضافة موظف</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الصلاحية</th></tr></thead>
        <tbody>
          ${state.staffList.map(s => `<tr><td>${s.name || "—"}</td><td class="muted">${s.email}</td><td><span class="badge badge-ok">${ROLE_LABELS[s.role] || s.role}</span></td></tr>`).join("")
            || `<tr><td colspan="3" class="muted" style="text-align:center;padding:24px">لا يوجد موظفون بعد</td></tr>`}
        </tbody>
      </table>
    </div>
  `;
}

function renderSettings() {
  return `
    <h1 class="page-title">إعدادات الفاتورة والتقارير</h1>
    <p class="page-sub">تخصيص البيانات التي تظهر على الفواتير المطبوعة</p>

    <div style="max-width:500px;" class="card panel">
      <h3 style="margin-bottom:12px">${icon("edit", 16)} الشعار</h3>
      <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px">
        <div class="logo-preview-box">
          ${state.receiptSettings.logoDataUrl
            ? `<img src="${state.receiptSettings.logoDataUrl}" alt="الشعار" />`
            : logoMark(34)}
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn btn-primary" type="button" onclick="pickLogo()">${icon("edit", 14)} ${state.receiptSettings.logoDataUrl ? "تغيير الشعار" : "رفع شعار"}</button>
          ${state.receiptSettings.logoDataUrl ? `<button class="btn btn-outline" type="button" onclick="removeLogo()">${icon("close", 14)} إزالة</button>` : ""}
        </div>
      </div>
      <p class="field-hint">الشعار يظهر بالشريط الجانبي، شاشة الدخول، وأعلى الفاتورة المطبوعة. الحد الأقصى 2 ميغابايت (PNG أو JPG).</p>
    </div>

    <div style="max-width:500px;margin-top:20px" class="card panel">
      <form onsubmit="saveReceiptSettings(event)">
        <div class="field"><label>اسم المقهى/المحل</label><input name="storeName" value="${state.receiptSettings.storeName}" required /></div>
        <div class="field"><label>العنوان</label><input name="address" value="${state.receiptSettings.address}" required /></div>
        <div class="field"><label>رقم الهاتف</label><input name="phone" type="tel" value="${state.receiptSettings.phone}" required /></div>
        <div class="field"><label>رسالة التذييل (نهاية الفاتورة)</label><input name="footerMsg" value="${state.receiptSettings.footerMsg}" required /></div>
        <div class="field">
          <label>عرض ورق الفاتورة</label>
          <select name="paperWidth">
            <option value="80" ${state.receiptSettings.paperWidth !== "40" ? "selected" : ""}>80 مم (الحجم الشائع بأغلب طابعات المقاهي)</option>
            <option value="40" ${state.receiptSettings.paperWidth === "40" ? "selected" : ""}>40 مم (طابعات صغيرة/محمولة)</option>
          </select>
          <p class="field-hint">اختر نفس عرض لفة الورق بطابعتك، حتى تطبع الفاتورة بحجم إيصال حقيقي مو صفحة كبيرة.</p>
        </div>
        <div class="field">
          <label>طابعة الفواتير</label>
          <select name="printerName">
            <option value="">اختر طابعة (بدونها يفتح مربع اختيار كل مرة)</option>
            ${state.availablePrinters.map(p => `<option value="${p.name}" ${p.name === state.receiptSettings.printerName ? "selected" : ""}>${p.displayName}${p.isDefault ? " (افتراضية بويندوز)" : ""}</option>`).join("")}
          </select>
          <p class="field-hint">لو اخترت طابعة هنا، الفاتورة تطبع تلقائيًا عليها بعد كل عملية بيع بدون أي نافذة تظهر. تأكد إنها فعلاً طابعة الفواتير الحرارية عندك.</p>
        </div>
        <button class="btn btn-primary" style="margin-top:12px;" type="submit">حفظ الإعدادات</button>
      </form>
    </div>

    <div style="max-width:500px;margin-top:20px" class="card panel">
      <h3 style="margin-bottom:6px">${icon("download", 16)} النسخ الاحتياطي</h3>
      <p class="field-hint" style="margin-bottom:14px">صدّر نسخة من كل بياناتك (المنتجات، المبيعات، الورديات) كملف تقدر تحفظه بمكان آمن، أو تستعيده لاحقًا على أي جهاز.</p>
      <div style="display:flex;gap:10px">
        <button class="btn btn-primary" style="flex:1;justify-content:center" onclick="exportBackup()">${icon("download", 15)} تصدير نسخة احتياطية</button>
        <button class="btn btn-outline" style="flex:1;justify-content:center" onclick="restoreBackup()">${icon("refresh", 15)} استعادة نسخة</button>
      </div>
    </div>

    <div style="max-width:500px;margin-top:20px" class="card panel">
      <h3 style="margin-bottom:12px">${icon("lock", 16)} حالة الترخيص والاشتراك</h3>
      ${(() => {
        const lic = getLicenseRemainingInfo();
        return `
          <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light)">
              <span class="muted">مفتاح الترخيص:</span>
              <span style="font-family:monospace;font-weight:700">${(state.licenseInfo && state.licenseInfo.licenseKey) || "غير محدد"}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light)">
              <span class="muted">العميل / المحل:</span>
              <span style="font-weight:700">${(state.licenseInfo && state.licenseInfo.lastValidCustomer) || "افتراضي"}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light)">
              <span class="muted">صلاحية الاشتراك:</span>
              <span style="font-weight:700;color:${lic ? lic.color : 'inherit'}">${lic ? lic.statusText : 'نشط'}</span>
            </div>
            ${lic && !lic.isLifetime ? `
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light)">
              <span class="muted">تفاصيل المدة:</span>
              <span>${lic.subText}</span>
            </div>
            ` : ""}
          </div>
          <button class="btn btn-outline" style="width:100%;justify-content:center" onclick="enterNewLicenseKey()">
            ${icon("refresh", 14)} تجديد الاشتراك أو إدخال مفتاح آخر
          </button>
        `;
      })()}
    </div>

    <div style="max-width:500px;margin-top:20px" class="card panel">
      <h3 style="margin-bottom:12px">${icon("cloudOn", 16)} تحديثات النظام عن بُعد</h3>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light)">
          <span class="muted">الإصدار الحالي:</span>
          <span style="font-family:monospace;font-weight:700">v${state.appVersion || "1.0.1"}</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light)">
          <span class="muted">حالة التحديث:</span>
          <span style="font-weight:700;color:${state.update && state.update.status === 'ready' ? 'var(--success)' : state.update && state.update.status === 'available' ? 'var(--rust)' : 'var(--text-dark)'}">
            ${(() => {
              const u = state.update;
              if (!u || u.status === 'idle') return 'جاهز للتحقق';
              if (u.status === 'checking') return 'جارٍ فحص التحديثات...';
              if (u.status === 'available') return `يتوفر إصدار جديد v${u.version}`;
              if (u.status === 'downloading') return `جارٍ التحميل (${u.percent || 0}%)`;
              if (u.status === 'ready') return `تم تنزيل الإصدار v${u.version || ''} وجاهز للتثبيت ✓`;
              if (u.status === 'latest') return 'البرنامج مُحدَّث لآخر إصدار ✓';
              if (u.status === 'error') return 'تعذّر الاتصال بخادم التحديثات';
              return 'جاهز';
            })()}
          </span>
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-primary" type="button" style="flex:1;justify-content:center" onclick="checkAppUpdatesManual()">
          ${icon("refresh", 14)} فحص التحديثات الآن
        </button>
        ${state.update && state.update.status === 'ready' ? `
          <button class="btn btn-primary" type="button" style="flex:1;justify-content:center;background:var(--success)" onclick="restartToUpdate()">
            ${icon("check", 14)} إعادة التشغيل وتثبيت التحديث
          </button>
        ` : ''}
      </div>
      <p class="field-hint" style="margin-top:8px">يتم فحص التحديثات وتنزيلها تلقائياً في الخلفية فور إطلاق أي إصدار جديد من بُعد.</p>
    </div>
  `;
}

function renderPurchases() {
  return `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <div><h1 class="page-title">المشتريات والموردون</h1><p class="page-sub" style="margin:0">تسجيل فواتير الشراء ومتابعة كشف حساب كل مورد</p></div>
      <button class="btn btn-primary" onclick="openPurchaseModal()">${icon("plus", 15)} تسجيل عملية شراء</button>
    </div>

    <div class="panel" style="margin-bottom:20px">
      <h3>${icon("staff", 15)} الموردون</h3>
      ${state.suppliers.length === 0 ? `<p class="muted" style="font-size:13px">لا يوجد موردون بعد. أضف موردًا جديدًا عند تسجيل أول عملية شراء.</p>` : `
        ${state.suppliers.map(s => `
          <div class="list-row">
            <span><b>${s.name}</b>${s.phone ? ` <span class="muted">· ${s.phone}</span>` : ""}
              <span class="muted"> · ${s.purchaseCount} عملية · إجمالي ${money(s.totalSpent)}</span>
            </span>
            <span style="display:flex;align-items:center;gap:10px">
              ${s.balance > 0.001
                ? `<span class="badge badge-low">متبقي عليك ${money(s.balance)}</span>`
                : `<span class="badge badge-ok">مسدد بالكامل</span>`}
              <button class="btn btn-outline" style="padding:5px 12px;font-size:12px" onclick="openPaymentModal(${s.id}, '${s.name.replace(/'/g, "\\'")}')">${icon("plus", 13)} تسجيل دفعة</button>
            </span>
          </div>
        `).join("")}
      `}
    </div>

    <div class="table-wrap">
      <table>
        <thead><tr><th>التاريخ</th><th>الوصف</th><th>المورد</th><th>التكلفة</th></tr></thead>
        <tbody>
          ${state.purchases.length === 0 ? `<tr><td colspan="4" class="muted" style="text-align:center;padding:24px">لا توجد مشتريات مسجّلة بعد</td></tr>` :
            state.purchases.map(p => `
              <tr>
                <td class="muted">${new Date(p.createdAt).toLocaleDateString("ar-LY")}</td>
                <td>${p.description}${p.restockQty ? ` <span class="muted">(+${p.restockQty} للمخزون)</span>` : ""}</td>
                <td class="muted">${p.supplierName}</td>
                <td style="font-weight:800">${money(p.cost)}</td>
              </tr>
            `).join("")}
        </tbody>
      </table>
    </div>
  `;
}

function renderLoginScreen() {
  return `
    <div class="gate">
      <div class="gate-logo-row">${renderLogo(52)}</div>
      <div class="gate-sub">نظام إدارة المقهى</div>
      <form class="modal" style="max-width:340px" onsubmit="submitLogin(event)">
        <div class="field"><label>البريد الإلكتروني</label><input name="email" type="email" required /></div>
        <div class="field"><label>كلمة المرور</label><input name="password" type="password" required /></div>
        ${state.authError ? `<p style="color:var(--rust);font-size:12px;margin:0 0 10px">${state.authError}</p>` : ""}
        <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">دخول</button>
      </form>
      <button class="gate-note" style="margin-top:18px;text-decoration:underline;background:none" onclick="useDemoMode()">المتابعة بدون تسجيل دخول (وضع تجريبي)</button>
    </div>
  `;
}

function renderUpdateBanner() {
  const u = state.update;
  if (!u || u.status === "idle" || u.status === "latest" || u.status === "error") return "";
  if (u.status === "checking") return "";
  if (u.status === "available") return `<div class="update-banner update-ready">${icon("bell", 16)} تحديث جديد متوفر (v${u.version}) <button onclick="downloadAppUpdate()">تحميل التحديث</button></div>`;
  if (u.status === "downloading") return `<div class="update-banner">${icon("download", 16)} يتم تحميل التحديث... ${u.percent || 0}%</div>`;
  if (u.status === "ready") return `<div class="update-banner update-ready">${icon("check", 16)} التحديث جاهز — <button onclick="restartToUpdate()">إعادة التشغيل الآن</button></div>`;
  return "";
}

function renderDayWarningBanner() {
  if (!state.dayNeedsClosing) return "";
  return `
    <div class="day-warning-banner">
      ${icon("warning", 16, "icon-warning")}
      <span>عندك وردية مفتوحة من يوم سابق لم يتم إغلاقها بعد.</span>
      <button onclick="openCloseShiftModal()">إغلاق الوردية الآن</button>
    </div>
  `;
}

// ================= النوافذ المنبثقة =================
function renderProductModal() {
  if (!state.productModal) return "";
  const isEdit = state.productModal.mode === "edit";
  const p = isEdit ? state.products.find(x => x.id === state.productModal.id) : { name: "", category: CATEGORIES[1], price: "", stock: "", threshold: 10, trackStock: true };
  const tracked = p ? (p.trackStock !== false) : true;
  return `
    <div class="overlay" onclick="if(event.target===this) closeProductModal()">
      <form class="modal" onsubmit="saveProduct(event)">
        <div class="modal-head"><h3>${isEdit ? "تعديل منتج" : "إضافة منتج جديد"}</h3><button type="button" onclick="closeProductModal()">${icon("close", 18)}</button></div>
        <div class="field"><label>اسم المنتج</label><input name="name" value="${p.name || ""}" required /></div>
        <div class="field"><label>التصنيف</label>
          <select name="category">${CATEGORIES.slice(1).map(c => `<option ${c === p.category ? "selected" : ""}>${c}</option>`).join("")}</select>
        </div>
        <div class="field"><label>السعر (د.ل)</label><input name="price" type="number" step="0.001" value="${p.price || ""}" required /></div>
        <div class="field track-stock-toggle">
          <label class="switch-row">
            <input type="checkbox" name="trackStock" ${tracked ? "checked" : ""} onchange="toggleStockFieldsVisibility(this)" />
            <span>تتبع المخزون لهذا المنتج</span>
          </label>
        </div>
        <div id="stockFieldsWrap" style="${tracked ? "" : "display:none"}">
          <div class="field"><label>الكمية بالمخزون</label><input name="stock" type="number" value="${p.stock || 0}" /></div>
          <div class="field"><label>حد التنبيه</label><input name="threshold" type="number" value="${p.threshold || 10}" /></div>
        </div>
        <button class="btn btn-primary" style="width:100%;justify-content:center;margin-top:6px" type="submit">${isEdit ? "حفظ التعديلات" : "إضافة المنتج"}</button>
      </form>
    </div>
  `;
}

function toggleStockFieldsVisibility(checkbox) {
  const wrap = document.getElementById("stockFieldsWrap");
  if (wrap) wrap.style.display = checkbox.checked ? "" : "none";
}

function renderDeleteModal() {
  if (!state.deleteId) return "";
  return `
    <div class="overlay" onclick="if(event.target===this) cancelDelete()">
      <div class="modal" style="max-width:340px">
        <div class="confirm-icon">${icon("warning", 26, "icon-warning")}</div>
        <div class="confirm-text">هل تريد حذف هذا المنتج نهائيًا؟</div>
        <div class="modal-actions">
          <button class="btn btn-outline" onclick="cancelDelete()">إلغاء</button>
          <button class="btn btn-danger" onclick="confirmDelete()">حذف</button>
        </div>
      </div>
    </div>
  `;
}

function renderStockModal() {
  if (!state.stockModal) return "";
  return `
    <div class="overlay" onclick="if(event.target===this) closeStockModal()">
      <form class="modal" style="max-width:340px" onsubmit="applyStockAdd(event)">
        <div class="modal-head"><h3>إضافة كمية للمخزون</h3><button type="button" onclick="closeStockModal()">${icon("close", 18)}</button></div>
        <div class="field"><input name="amount" type="number" placeholder="الكمية" autofocus required /></div>
        <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">تأكيد الإضافة</button>
      </form>
    </div>
  `;
}

function renderStaffModal() {
  if (!state.staffModal) return "";
  return `
    <div class="overlay" onclick="if(event.target===this) closeStaffModal()">
      <form class="modal" onsubmit="saveStaff(event)">
        <div class="modal-head"><h3>إضافة موظف جديد</h3><button type="button" onclick="closeStaffModal()">${icon("close", 18)}</button></div>
        <div class="field"><label>الاسم</label><input name="name" required /></div>
        <div class="field"><label>البريد الإلكتروني</label><input name="email" type="email" required /></div>
        <div class="field"><label>كلمة المرور المبدئية</label><input name="password" type="password" minlength="6" required /></div>
        <div class="field"><label>الصلاحية</label>
          <select name="role"><option value="cashier">الكاشير</option><option value="manager">مدير الفرع</option><option value="admin">مدير النظام</option></select>
        </div>
        <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">إنشاء الحساب</button>
      </form>
    </div>
  `;
}

function renderPaymentModal() {
  if (!state.paymentModal) return "";
  const supplier = state.suppliers.find(s => s.id === state.paymentModal.supplierId);
  const balance = supplier ? supplier.balance : 0;
  return `
    <div class="overlay" onclick="if(event.target===this) closePaymentModal()">
      <form class="modal" style="max-width:380px" onsubmit="submitSupplierPayment(event)">
        <div class="modal-head"><h3>${icon("plus", 16)} تسجيل دفعة — ${state.paymentModal.supplierName}</h3><button type="button" onclick="closePaymentModal()">${icon("close", 18)}</button></div>
        <div class="totals" style="border-top:none;padding-top:0;margin-bottom:14px">
          <div class="totals-row final"><span>المتبقي عليك حاليًا</span><span>${money(balance)}</span></div>
        </div>
        <div class="field"><label>المبلغ المدفوع (د.ل)</label><input name="amount" type="number" step="0.001" max="${balance > 0 ? balance : ""}" required autofocus /></div>
        <div class="field"><label>ملاحظة (اختياري)</label><input name="note" placeholder="رقم إيصال، طريقة الدفع..." /></div>
        <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">تأكيد الدفعة</button>
      </form>
    </div>
  `;
}

function renderPurchaseModal() {
  if (!state.purchaseModal) return "";
  const trackedProducts = state.products.filter(p => p.trackStock !== false);
  return `
    <div class="overlay" onclick="if(event.target===this) closePurchaseModal()">
      <form class="modal" onsubmit="savePurchase(event)">
        <div class="modal-head"><h3>${icon("plus", 16)} تسجيل عملية شراء</h3><button type="button" onclick="closePurchaseModal()">${icon("close", 18)}</button></div>
        <div class="field">
          <label>المورد</label>
          <input name="supplierName" list="supplierList" placeholder="اسم مورد موجود أو جديد" />
          <datalist id="supplierList">${state.suppliers.map(s => `<option value="${s.name}">`).join("")}</datalist>
        </div>
        <div class="field"><label>وصف المشتريات</label><input name="description" placeholder="مثلاً: بن أخضر 25 كيلو" required /></div>
        <div class="field"><label>التكلفة الإجمالية (د.ل)</label><input name="cost" type="number" step="0.001" required /></div>
        <div class="field">
          <label>ربط بمنتج بالمخزون (اختياري)</label>
          <select name="productId">
            <option value="">بدون ربط</option>
            ${trackedProducts.map(p => `<option value="${p.id}">${p.name} (المخزون الحالي: ${p.stock})</option>`).join("")}
          </select>
        </div>
        <div class="field"><label>الكمية المضافة للمخزون (لو ربطت منتج)</label><input name="restockQty" type="number" placeholder="مثلاً: 10" /></div>
        <button class="btn btn-primary" style="width:100%;justify-content:center;margin-top:6px" type="submit">حفظ عملية الشراء</button>
      </form>
    </div>
  `;
}

function renderShortageModal() {
  if (!state.shortageModal) return "";
  const hasCatalog = state.shortageCatalog.length > 0;
  return `
    <div class="overlay" onclick="if(event.target===this) closeShortageModal()">
      <form class="modal" onsubmit="reportShortage(event)">
        <div class="modal-head"><h3>${icon("warning", 16, "icon-warning")} الإبلاغ عن نقص بضاعة</h3><button type="button" onclick="closeShortageModal()">${icon("close", 18)}</button></div>
        <div class="field">
          <label>اسم الصنف الناقص</label>
          ${hasCatalog ? `
            <select name="shortageName" onchange="toggleShortageNameInput(this)" required>
              <option value="">اختر صنف...</option>
              ${state.shortageCatalog.map(c => `<option value="${c.name}">${c.name}</option>`).join("")}
              <option value="__new__">➕ صنف جديد (يُضاف للقائمة)</option>
            </select>
          ` : `
            <select name="shortageName" onchange="toggleShortageNameInput(this)">
              <option value="__new__" selected>➕ صنف جديد (يُضاف للقائمة)</option>
            </select>
          `}
        </div>
        <div class="field" id="shortageCustomWrap" style="${hasCatalog ? "display:none" : ""}">
          <label>اسم الصنف الجديد</label>
          <input name="shortageNameCustom" placeholder="مثلاً: أكواب ورقية، سكر، حليب" ${hasCatalog ? "" : "required"} />
        </div>
        <div class="field"><label>ملاحظة (اختياري)</label><input name="shortageNote" placeholder="كمية مطلوبة، مورد مقترح..." /></div>
        <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">إرسال البلاغ</button>
      </form>
    </div>
  `;
}

function renderShiftModal() {
  if (!state.shiftModal) return "";

  if (state.shiftModal.mode === "open") {
    return `
      <div class="overlay" onclick="if(event.target===this) closeShiftModal()">
        <form class="modal" style="max-width:380px" onsubmit="submitOpenShift(event)">
          <div class="modal-head"><h3>${icon("lock", 16)} فتح وردية جديدة</h3><button type="button" onclick="closeShiftModal()">${icon("close", 18)}</button></div>
          <div class="field"><label>المبلغ النقدي الافتتاحي (د.ل)</label><input name="openingCash" type="number" step="0.001" value="0" required autofocus /></div>
          <p class="field-hint" style="margin-bottom:14px">المبلغ الموجود بالدرج قبل بداية البيع.</p>
          <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">فتح الوردية</button>
        </form>
      </div>
    `;
  }

  const expected = state.currentShift ? state.currentShift.expectedCash : 0;
  return `
    <div class="overlay" onclick="if(event.target===this) closeShiftModal()">
      <div class="modal" style="max-width:420px">
        <div class="modal-head"><h3>${icon("lock", 16)} إغلاق الوردية</h3><button type="button" onclick="closeShiftModal()">${icon("close", 18)}</button></div>
        <div class="totals" style="border-top:none;padding-top:0;margin-bottom:12px">
          <div class="totals-row"><span>عدد الطلبات</span><span>${state.ordersCount}</span></div>
          <div class="totals-row"><span>إجمالي المبيعات</span><span>${money(state.todaySales)}</span></div>
          <div class="totals-row"><span>- مبيعات نقداً (كاش)</span><span>${money(state.currentShift ? state.currentShift.cashSales : 0)}</span></div>
          <div class="totals-row"><span>- مبيعات بطاقة / سداد</span><span>${money(state.currentShift ? state.currentShift.cardSales : 0)}</span></div>
          <div class="totals-row"><span>النقد الافتتاحي بالدرج</span><span>${money(state.currentShift ? state.currentShift.openingCash : 0)}</span></div>
          ${(state.currentShift && state.currentShift.totalExpenses > 0) ? `<div class="totals-row" style="color:var(--rust-dark)"><span>- مصروفات الدرج</span><span>-${money(state.currentShift.totalExpenses)}</span></div>` : ""}
          <div class="totals-row final"><span>النقد الفعلي المتوقع بالدرج</span><span>${money(expected)}</span></div>
        </div>
        ${state.productSales.length > 0 ? `
          <div class="closeday-breakdown">
            <div class="closeday-breakdown-title">مبيعات كل صنف بالوردية</div>
            <div class="closeday-breakdown-list">
              ${state.productSales.map(p => `<div class="list-row"><span>${p.name} <span class="muted">× ${p.qty}</span></span><span style="font-weight:800">${money(p.revenue)}</span></div>`).join("")}
            </div>
          </div>
        ` : ""}
        <form onsubmit="submitCloseShift(event)" style="margin-top:14px">
          <div class="field"><label>النقد الفعلي بعد الجرد (د.ل)</label><input name="actualCash" type="number" step="0.001" value="${expected}" required oninput="updateCashDiffPreview(this.value)" autofocus /></div>
          <p id="cashDiffPreview" class="field-hint" style="margin-bottom:14px"></p>
          <div class="modal-actions">
            <button class="btn btn-outline" type="button" onclick="closeShiftModal()">إلغاء</button>
            <button class="btn btn-primary" type="submit">تأكيد الإغلاق والطباعة</button>
          </div>
        </form>
      </div>
    </div>
  `;
}

function renderExpenseModal() {
  if (!state.expenseModal) return "";
  return `
    <div class="overlay" onclick="if(event.target===this) closeExpenseModal()">
      <form class="modal" style="max-width:380px" onsubmit="submitExpense(event)">
        <div class="modal-head">
          <h3>${icon("money", 16)} تسجيل مصروف من الدرج</h3>
          <button type="button" onclick="closeExpenseModal()">${icon("close", 18)}</button>
        </div>
        <div class="field">
          <label>المبلغ (د.ل)</label>
          <input name="amount" type="number" step="0.001" min="0.001" placeholder="مثلاً: 15.000" required autofocus />
        </div>
        <div class="field">
          <label>السبب / البيان</label>
          <input name="reason" placeholder="مثلاً: شراء حليب، مياه، منظفات..." required />
        </div>
        <p class="field-hint" style="margin-bottom:14px">سيتم خصم هذا المبلغ تلقائياً من النقد المتوقع بالدرج عند إغلاق الوردية.</p>
        <div class="modal-actions">
          <button class="btn btn-outline" type="button" onclick="closeExpenseModal()">إلغاء</button>
          <button class="btn btn-primary" type="submit">تسجيل المصروف</button>
        </div>
      </form>
    </div>
  `;
}

// ================= التقديم والتشغيل =================
function renderMain() {
  const mainEl = document.querySelector(".main");
  if (!mainEl) return render();
  const views = { dashboard: renderDashboard, pos: renderPOS, products: renderProducts, inventory: renderInventory, staff: renderStaff, settings: renderSettings, purchases: renderPurchases };
  mainEl.innerHTML = renderUpdateBanner() + renderLicenseWarningBanner() + renderDayWarningBanner() + views[state.view]();
}

function render() {
  const root = document.getElementById("app");

  if (state.licenseState === "checking") { root.innerHTML = renderLicenseChecking(); return; }
  if (state.licenseState === "needs-key") { root.innerHTML = renderLicenseKeyScreen(); return; }
  if (state.licenseState === "invalid") { root.innerHTML = renderLicenseBlockedScreen(); return; }

  if (!state.role) {
    root.innerHTML = (auth.isAuthConfigured() && !state.demoMode) ? renderLoginScreen() : renderRoleGate();
    return;
  }
  const views = { dashboard: renderDashboard, pos: renderPOS, products: renderProducts, inventory: renderInventory, staff: renderStaff, settings: renderSettings, purchases: renderPurchases };
  const isPOS = state.view === "pos";
  root.innerHTML = `
    <div id="shell" class="${isPOS ? "shell-topbar-mode" : ""}">
      ${isPOS ? renderTopBar() : renderSidebar()}
      <main class="main ${isPOS ? "main-full" : ""}">${renderUpdateBanner()}${renderLicenseWarningBanner()}${renderDayWarningBanner()}${views[state.view]()}</main>
    </div>
    ${renderProductModal()}
    ${renderDeleteModal()}
    ${renderStockModal()}
    ${renderStaffModal()}
    ${renderShortageModal()}
    ${renderPurchaseModal()}
    ${renderPaymentModal()}
    ${renderShiftModal()}
    ${renderExpenseModal()}
  `;
}

async function initLicense() {
  if (!window.electronAPI || !window.electronAPI.dbGetLicenseInfo) {
    state.licenseState = "invalid";
    state.licenseError = "لا يمكن تشغيل التطبيق خارج النسخة الرسمية المثبّتة";
    return;
  }

  const info = await window.electronAPI.dbGetLicenseInfo().catch(() => null);
  state.licenseInfo = info;

  if (!license.isFirebaseConfigured()) {
    state.licenseState = "invalid";
    state.licenseError = "خدمة التراخيص غير مُعدّة. تواصل مع مزوّد النظام.";
    return;
  }

  if (!info || !info.licenseKey) {
    state.licenseState = "needs-key";
    return;
  }

  const result = await license.checkLicense(info.licenseKey, info.deviceId).catch(() => ({ valid: false, reason: "network-error" }));

  if (result.valid) {
    const saved = await window.electronAPI.dbSaveLicenseValidation({
      key: info.licenseKey, customerName: result.customerName, expiresAt: result.expiresAt,
    }).catch(() => null);
    state.licenseInfo = saved || info;
    state.licenseState = "valid";
    return;
  }

  if (result.reason === "network-error") {
    // Offline grace is deliberately limited: the app must have completed a
    // successful server validation recently, and an expired subscription is
    // never allowed through the grace path.
    const tenDaysMs = 10 * 24 * 60 * 60 * 1000;
    const subscriptionIsCurrent = !info.lastValidExpiresAt || Date.now() <= info.lastValidExpiresAt;
    if (info.lastValidAt && subscriptionIsCurrent && (Date.now() - info.lastValidAt) <= tenDaysMs) {
      state.licenseState = "grace";
      return;
    }
    state.licenseState = "invalid";
    state.licenseError = "يتطلب التطبيق اتصالاً بالإنترنت للتحقق من الترخيص (انتهت مهلة العمل دون إنترنت: 10 أيام)";
    return;
  }

  const reasons = {
    "not-found": "مفتاح الترخيص غير صحيح",
    "revoked": "تم إلغاء تفعيل هذا الترخيص",
    "expired": "انتهت صلاحية هذا الاشتراك",
    "device-mismatch": "هذا المفتاح مُفعّل على جهاز آخر بالفعل",
    "permission-denied": "تم رفض الوصول من Firebase (تأكد من نشر قواعد firestore.rules)",
    "licensing-not-configured": "خدمة التراخيص غير مُعدّة. تواصل مع مزوّد النظام.",
  };
  state.licenseState = "invalid";
  state.licenseError = reasons[result.reason] || "تعذّر التحقق من صلاحية الترخيص";
}

async function submitLicenseKey(e) {
  e.preventDefault();
  const key = e.target.licenseKey.value.trim();
  if (!key) return;
  state.licenseState = "checking";
  render();

  const info = state.licenseInfo || (await window.electronAPI.dbGetLicenseInfo());
  const result = await license.checkLicense(key, info.deviceId).catch(() => ({ valid: false, reason: "network-error" }));

  if (result.valid) {
    const saved = await window.electronAPI.dbSaveLicenseValidation({ key, customerName: result.customerName, expiresAt: result.expiresAt });
    state.licenseInfo = saved;
    state.licenseState = "valid";
    render();
    initApp();
    return;
  }

  const reasons = {
    "not-found": "مفتاح الترخيص غير صحيح، تأكد من كتابته بالضبط",
    "revoked": "تم إلغاء تفعيل هذا الترخيص",
    "expired": "انتهت صلاحية هذا الاشتراك",
    "device-mismatch": "هذا المفتاح مُفعّل على جهاز آخر بالفعل",
    "permission-denied": "تم رفض الوصول من Firebase (تأكد من نشر قواعد firestore.rules في Console)",
    "network-error": "تعذّر الاتصال بالإنترنت للتحقق من المفتاح",
  };
  state.licenseError = reasons[result.reason] || "تعذّر التحقق من صلاحية الترخيص";
  state.licenseState = "needs-key";
  render();
}

async function retryLicenseCheck() {
  state.licenseState = "checking";
  render();
  await initLicense();
  if (state.licenseState === "valid" || state.licenseState === "grace") {
    await initApp();
  } else {
    render();
  }
}

function enterNewLicenseKey() {
  state.licenseError = null;
  state.licenseState = "needs-key";
  render();
}

function bypassLicenseForDev() {
  showToast("تم إيقاف الدخول التجريبي. يتطلب النظام مفتاح ترخيص صالح.");
}

async function initApp() {
  if (window.electronAPI && window.electronAPI.dbGetState) {
    try {
      const data = await window.electronAPI.dbGetState();
      if (data) {
        if (data.products) state.products = data.products;
        if (typeof data.todaySales === "number") state.todaySales = data.todaySales;
        if (typeof data.monthSales === "number") state.monthSales = data.monthSales;
        if (typeof data.ordersCount === "number") state.ordersCount = data.ordersCount;
        if (data.recentTx) state.recentTx = data.recentTx;
        if (data.heldCarts) state.heldCarts = data.heldCarts;
        if (data.productSales) state.productSales = data.productSales;
        if (data.topProducts) state.topProducts = data.topProducts;
        if (data.weekly) state.weekly = data.weekly;
        state.dayNeedsClosing = !!data.dayNeedsClosing;
        state.currentShift = data.currentShift || null;
        if (data.receiptSettings) state.receiptSettings = data.receiptSettings;
        if (data.shortages) state.shortages = data.shortages;
        if (data.shortageCatalog) state.shortageCatalog = data.shortageCatalog;
        if (data.suppliers) state.suppliers = data.suppliers;
        if (data.purchases) state.purchases = data.purchases;
      }
    } catch (e) {
      console.error("[App] Error reading DB:", e);
    }
  }

  if (window.electronAPI && window.electronAPI.getAppVersion) {
    state.appVersion = await window.electronAPI.getAppVersion().catch(() => "1.0.1");
  }

  const branchOverride = (state.licenseInfo && state.licenseInfo.licenseKey) ? state.licenseInfo.licenseKey.trim() : null;
  cloud.initCloud(handleCloudData, handleCloudStatus, branchOverride);
  render();
}

async function bootstrap() {
  await initLicense();
  if (state.licenseState === "valid" || state.licenseState === "grace") {
    await initApp();
    // فحص دوري مستمر كل 30 ثانية لضمان قفل النظام لحظة انتهاء الاشتراك
    setInterval(enforceLicenseExpiration, 30 * 1000);
    // فحص دوري كل ساعة من السيرفر للتأكد من عدم إلغاء المفتاح
    setInterval(async () => {
      if (state.licenseState === "valid" && state.licenseInfo && state.licenseInfo.licenseKey && navigator.onLine) {
        const res = await license.checkLicense(state.licenseInfo.licenseKey, state.licenseInfo.deviceId).catch(() => null);
        if (res && !res.valid) {
          state.licenseState = "invalid";
          const reasons = {
            "not-found": "مفتاح الترخيص غير صحيح",
            "revoked": "تم إلغاء تفعيل هذا الترخيص من الإدارة",
            "expired": "انتهت صلاحية هذا الاشتراك",
            "device-mismatch": "هذا المفتاح مُفعّل على جهاز آخر بالفعل",
          };
          state.licenseError = reasons[res.reason] || "انتهت صلاحية الترخيص";
          render();
        }
      }
    }, 60 * 60 * 1000);
  } else {
    render();
  }
}

bootstrap();// ================= اختصارات لوحة المفاتيح =================
document.addEventListener("keydown", (e) => {
  const isInputActive = e.target.tagName === "INPUT" || e.target.tagName === "SELECT" || e.target.tagName === "TEXTAREA";
  if (e.key === "Escape") {
    if (state.productModal)  { closeProductModal(); return; }
    if (state.deleteId)      { cancelDelete();       return; }
    if (state.stockModal)    { closeStockModal();    return; }
    if (state.staffModal)    { closeStaffModal();    return; }
    if (state.shiftModal)    { closeShiftModal();    return; }
    if (state.expenseModal)  { closeExpenseModal();  return; }
    if (state.shortageModal) { closeShortageModal(); return; }
  }
  if (e.key === "Enter" && !isInputActive) {
    if (state.view === "pos" && state.cart.length > 0) { 
      e.preventDefault();
      checkout(); 
    }
  }
});

Object.assign(window, {
  selectRole, switchRole, setView, setSearch, setCategory, addToCart, changeQty, setDiscount,
  checkout, setPaymentMethod, openExpenseModal, closeExpenseModal, submitExpense,
  openProductModal, closeProductModal, saveProduct, askDelete, cancelDelete, confirmDelete,
  openStockModal, closeStockModal, applyStockAdd, submitLogin, useDemoMode, logoutAccount,
  loadStaffIfNeeded, openStaffModal, closeStaffModal, saveStaff, restartToUpdate, downloadAppUpdate, toggleSync,
  openShiftModal, openCloseShiftModal, closeShiftModal, submitOpenShift, submitCloseShift, updateCashDiffPreview,
  confirmCloseWeek, confirmCloseMonth, retryCloudSync,
  confirmCancelCart, holdCurrentCart, resumeHeldCart, deleteHeldCart,
  toggleStockFieldsVisibility, refundTransaction, exportSalesToCSV, saveReceiptSettings,
  pickLogo, removeLogo,
  openShortageModal, closeShortageModal, reportShortage, resolveShortageItem,
  openPurchaseModal, closePurchaseModal, savePurchase, exportBackup, restoreBackup,
  submitLicenseKey, retryLicenseCheck, enterNewLicenseKey, bypassLicenseForDev,
  toggleShortageNameInput, openPaymentModal, closePaymentModal, submitSupplierPayment,
  numpadPress, numpadBackspace, numpadClear, checkAppUpdatesManual,
});

