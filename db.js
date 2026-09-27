// ===========================================================
// قاعدة بيانات محلية حقيقية (SQLite) بدل ملف JSON مسطّح
// تُستخدم عبر sql.js (WebAssembly) عمدًا: تعمل فورًا بعد npm install
// على أي جهاز بدون أي حاجة لمترجم ++C أو Visual Studio Build Tools،
// بعكس مكتبات SQLite الأخرى التي تحتاج ترجمة أثناء التثبيت.
// ===========================================================

const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const initSqlJs = require("sql.js");

let SQL = null;
let db = null;
let dbFilePath = "";

const DEFAULT_PRODUCTS = [
  { id: 1, name: "اسبريسو", category: "قهوة ساخنة", price: 12, stock: 0, threshold: 0, trackStock: false },
  { id: 2, name: "كابتشينو", category: "قهوة ساخنة", price: 18, stock: 0, threshold: 0, trackStock: false },
  { id: 3, name: "لاتيه", category: "قهوة ساخنة", price: 20, stock: 0, threshold: 0, trackStock: false },
  { id: 4, name: "أمريكانو", category: "قهوة ساخنة", price: 14, stock: 0, threshold: 0, trackStock: false },
  { id: 5, name: "آيس لاتيه", category: "قهوة باردة", price: 22, stock: 0, threshold: 0, trackStock: false },
  { id: 6, name: "كولد برو", category: "قهوة باردة", price: 20, stock: 0, threshold: 0, trackStock: false },
  { id: 7, name: "فرابيه", category: "قهوة باردة", price: 24, stock: 0, threshold: 0, trackStock: false },
  { id: 8, name: "عصير برتقال", category: "مشروبات", price: 15, stock: 0, threshold: 0, trackStock: false },
  { id: 9, name: "شاي أحمر", category: "مشروبات", price: 10, stock: 0, threshold: 0, trackStock: false },
  { id: 10, name: "تشيز كيك", category: "حلويات", price: 25, stock: 4, threshold: 8, trackStock: true },
  { id: 11, name: "كوكيز", category: "حلويات", price: 8, stock: 60, threshold: 15, trackStock: true },
  { id: 12, name: "كروسان", category: "حلويات", price: 10, stock: 5, threshold: 10, trackStock: true },
];

const SCHEMA_SQL = `
  CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    category TEXT NOT NULL,
    price REAL NOT NULL,
    stock INTEGER NOT NULL DEFAULT 0,
    threshold INTEGER NOT NULL DEFAULT 10,
    track_stock INTEGER NOT NULL DEFAULT 1
  );
  CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number INTEGER,
    created_at INTEGER NOT NULL,
    time_label TEXT NOT NULL,
    items_count INTEGER NOT NULL,
    total REAL NOT NULL,
    items_json TEXT NOT NULL,
    shift_id INTEGER,
    payment_method TEXT NOT NULL DEFAULT 'cash',
    refunded INTEGER NOT NULL DEFAULT 0
  );
  CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY,
    shift_id INTEGER,
    amount REAL NOT NULL,
    reason TEXT NOT NULL,
    created_by TEXT,
    created_at INTEGER NOT NULL
  );
  CREATE TABLE IF NOT EXISTS held_carts (
    id INTEGER PRIMARY KEY,
    items_json TEXT NOT NULL,
    discount REAL NOT NULL DEFAULT 0,
    time_label TEXT NOT NULL,
    created_at INTEGER NOT NULL
  );
  CREATE TABLE IF NOT EXISTS shortages (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    note TEXT,
    reporter TEXT,
    created_at INTEGER NOT NULL,
    resolved INTEGER NOT NULL DEFAULT 0
  );
  CREATE TABLE IF NOT EXISTS shifts (
    id INTEGER PRIMARY KEY,
    opened_at INTEGER NOT NULL,
    closed_at INTEGER,
    opening_cash REAL NOT NULL DEFAULT 0,
    closing_cash_actual REAL,
    expected_cash REAL,
    difference REAL,
    opened_by TEXT,
    closed_by TEXT,
    status TEXT NOT NULL DEFAULT 'open'
  );
  CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    phone TEXT,
    note TEXT,
    created_at INTEGER NOT NULL
  );
  CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY,
    supplier_id INTEGER,
    description TEXT NOT NULL,
    cost REAL NOT NULL,
    product_id INTEGER,
    restock_qty INTEGER,
    created_by TEXT,
    created_at INTEGER NOT NULL
  );
  CREATE TABLE IF NOT EXISTS supplier_payments (
    id INTEGER PRIMARY KEY,
    supplier_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    note TEXT,
    created_by TEXT,
    created_at INTEGER NOT NULL
  );
  CREATE TABLE IF NOT EXISTS shortage_catalog (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL UNIQUE
  );
  CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
  );
`;

function persist() {
  if (!db || !dbFilePath) return;
  try {
    const bytes = db.export();
    fs.mkdirSync(path.dirname(dbFilePath), { recursive: true });
    fs.writeFileSync(dbFilePath, Buffer.from(bytes));
  } catch (err) {
    console.error("خطأ أثناء حفظ قاعدة البيانات إلى القرص:", err);
  }
}

function run(sql, params) {
  if (!db) return;
  try {
    const safeParams = Array.isArray(params)
      ? params.map(p => (p === undefined ? null : p))
      : (params || []);
    db.run(sql, safeParams);
  } catch (err) {
    console.error("Database run error:", sql, params, err);
    throw err;
  }
}

function all(sql, params) {
  if (!db) return [];
  let stmt = null;
  try {
    stmt = db.prepare(sql);
    if (params) {
      const safeParams = Array.isArray(params)
        ? params.map(p => (p === undefined ? null : p))
        : params;
      stmt.bind(safeParams);
    }
    const rows = [];
    while (stmt.step()) rows.push(stmt.getAsObject());
    return rows;
  } catch (err) {
    console.error("Database query error in all():", sql, params, err);
    return [];
  } finally {
    if (stmt) {
      try { stmt.free(); } catch (_) {}
    }
  }
}

function one(sql, params) {
  const rows = all(sql, params);
  return (rows && rows.length > 0) ? rows[0] : null;
}

function getSetting(key, fallback) {
  const row = one("SELECT value FROM settings WHERE key = ?", [key]);
  return row ? row.value : fallback;
}

function setSetting(key, value) {
  run("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value", [key, String(value)]);
}

function startOfMonthMs() {
  const d = new Date();
  d.setDate(1);
  d.setHours(0, 0, 0, 0);
  return d.getTime();
}

// يجمع مبيعات كل صنف من مجموعة صفوف items_json (الاسم والسعر محفوظين وقت البيع نفسه،
// فتبقى التقارير التاريخية صحيحة حتى لو تغيّر اسم المنتج أو سعره لاحقًا أو انحذف)
function aggregateProductSales(rows) {
  const map = {};
  for (const t of rows) {
    let items;
    try { items = JSON.parse(t.items_json); } catch (e) { items = []; }
    for (const it of items) {
      const key = it.name || ("منتج #" + it.id);
      if (!map[key]) map[key] = { name: key, qty: 0, revenue: 0 };
      map[key].qty += it.qty;
      map[key].revenue += (typeof it.price === "number" ? it.price : 0) * it.qty;
    }
  }
  return Object.values(map).sort((a, b) => b.revenue - a.revenue);
}

function getProductSalesSince(sinceTs) {
  return aggregateProductSales(all("SELECT items_json FROM transactions WHERE created_at >= ? AND (refunded IS NULL OR refunded = 0)", [sinceTs]));
}

function getProductSalesForShift(shiftId) {
  return aggregateProductSales(all("SELECT items_json FROM transactions WHERE shift_id = ? AND (refunded IS NULL OR refunded = 0)", [shiftId]));
}

// مبيعات آخر 7 أيام فعليًا (بيانات حقيقية لرسم لوحة التحكم)
function getWeeklySales() {
  const days = [];
  const now = new Date();
  const dayNames = ["الأحد", "الاثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"];
  const weekClosedAt = Number(getSetting("week_closed_at", 0)) || 0;
  for (let i = 6; i >= 0; i--) {
    const d = new Date(now);
    d.setDate(d.getDate() - i);
    d.setHours(0, 0, 0, 0);
    const start = Math.max(d.getTime(), weekClosedAt);
    const end = d.getTime() + 24 * 60 * 60 * 1000;
    let sales = 0;
    if (end > weekClosedAt) {
      const agg = one("SELECT COALESCE(SUM(total),0) as sales FROM transactions WHERE created_at >= ? AND created_at < ? AND (refunded IS NULL OR refunded = 0)", [start, end]);
      sales = agg ? agg.sales : 0;
    }
    days.push({ day: dayNames[d.getDay()], sales });
  }
  return days;
}

// هل تاريخ ما مختلف عن تاريخ اليوم التقويمي؟
function isDifferentCalendarDay(ts) {
  const a = new Date(ts);
  const b = new Date();
  return a.toDateString() !== b.toDateString();
}

function migrateSchema() {
  try {
    const productCols = all("PRAGMA table_info(products)");
    if (productCols && !productCols.some(c => c.name === "track_stock")) {
      run("ALTER TABLE products ADD COLUMN track_stock INTEGER NOT NULL DEFAULT 1");
    }
  } catch (e) {
    console.warn("Migration warning (products.track_stock):", e.message);
  }

  try {
    const txCols = all("PRAGMA table_info(transactions)");
    if (txCols) {
      if (!txCols.some(c => c.name === "shift_id")) {
        run("ALTER TABLE transactions ADD COLUMN shift_id INTEGER");
      }
      if (!txCols.some(c => c.name === "refunded")) {
        run("ALTER TABLE transactions ADD COLUMN refunded INTEGER NOT NULL DEFAULT 0");
      }
      if (!txCols.some(c => c.name === "payment_method")) {
        run("ALTER TABLE transactions ADD COLUMN payment_method TEXT NOT NULL DEFAULT 'cash'");
      }
      if (!txCols.some(c => c.name === "invoice_number")) {
        run("ALTER TABLE transactions ADD COLUMN invoice_number INTEGER");
        // ترقيم الفواتير القديمة بأثر رجعي بناءً على الترتيب الزمني
        run(`UPDATE transactions SET invoice_number = (
          SELECT COUNT(*) FROM transactions t2 WHERE t2.id <= transactions.id
        ) WHERE invoice_number IS NULL`);
      }
      if (!txCols.some(c => c.name === "order_token")) {
        run("ALTER TABLE transactions ADD COLUMN order_token INTEGER");
        run(`UPDATE transactions SET order_token = invoice_number WHERE order_token IS NULL`);
      }
      if (!txCols.some(c => c.name === "buzzer_note")) {
        run("ALTER TABLE transactions ADD COLUMN buzzer_note TEXT");
      }
    }
  } catch (e) {
    console.warn("Migration warning (transactions columns):", e.message);
  }

  try {
    run(`
      CREATE TABLE IF NOT EXISTS expenses (
        id INTEGER PRIMARY KEY,
        shift_id INTEGER,
        amount REAL NOT NULL,
        reason TEXT NOT NULL,
        created_by TEXT,
        created_at INTEGER NOT NULL
      )
    `);
  } catch (e) {
    console.warn("Migration warning (expenses table):", e.message);
  }

  persist();
}

function seedIfEmpty() {
  const countRow = one("SELECT COUNT(*) as c FROM products");
  const count = countRow ? countRow.c : 0;
  if (count > 0) return;
  for (const p of DEFAULT_PRODUCTS) {
    run("INSERT INTO products (id,name,category,price,stock,threshold,track_stock) VALUES (?,?,?,?,?,?,?)",
      [p.id, p.name, p.category, p.price, p.stock, p.threshold, p.trackStock === false ? 0 : 1]);
  }
  persist();
}

async function initDatabase(userDataPath) {
  SQL = await initSqlJs();
  fs.mkdirSync(userDataPath, { recursive: true });
  dbFilePath = path.join(userDataPath, "cafe-pos.sqlite");

  if (fs.existsSync(dbFilePath)) {
    try {
      const fileBuffer = fs.readFileSync(dbFilePath);
      if (fileBuffer.length > 0) {
        db = new SQL.Database(fileBuffer);
      } else {
        db = new SQL.Database();
      }
    } catch (err) {
      console.error("فشل قراءة ملف قاعدة البيانات الحالي، جاري إنشاء قاعدة جديدة والاحتفاظ بنسخة احتياطية:", err);
      try {
        fs.renameSync(dbFilePath, dbFilePath + ".bak." + Date.now());
      } catch (_) {}
      db = new SQL.Database();
    }
  } else {
    db = new SQL.Database();
  }
  db.run(SCHEMA_SQL);
  migrateSchema();
  seedIfEmpty();

  // أول تشغيل: لازم يكون فيه وردية مفتوحة دايمًا حتى يشتغل النظام
  if (!getOpenShift()) {
    openShift(0, "افتتاح تلقائي");
  }
}

function getDbFilePath() {
  return dbFilePath;
}

function mapProduct(row) {
  return { ...row, trackStock: !!row.track_stock };
}

// ================= الورديات =================
function getOpenShift() {
  return one("SELECT * FROM shifts WHERE status = 'open' ORDER BY opened_at DESC LIMIT 1");
}

function openShift(openingCash, openedBy) {
  const cur = getOpenShift();
  if (cur) return { error: "shift-already-open" };
  const id = Date.now();
  run("INSERT INTO shifts (id, opened_at, opening_cash, opened_by, status) VALUES (?,?,?,?, 'open')",
    [id, Date.now(), Number(openingCash) || 0, openedBy || ""]);
  persist();
  return getState();
}

function closeShift(actualCash, closedBy) {
  const cur = getOpenShift();
  if (!cur) return getState();
  const cashSalesAgg = one("SELECT COALESCE(SUM(total),0) as sales FROM transactions WHERE shift_id = ? AND (refunded IS NULL OR refunded = 0) AND (payment_method IS NULL OR payment_method = 'cash')", [cur.id]) || { sales: 0 };
  const expAgg = one("SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE shift_id = ?", [cur.id]) || { total: 0 };
  const cashSales = cashSalesAgg.sales || 0;
  const expTotal = expAgg.total || 0;
  const expected = (cur.opening_cash || 0) + cashSales - expTotal;
  const actual = Number(actualCash) || 0;
  const diff = actual - expected;
  run(
    "UPDATE shifts SET closed_at=?, closing_cash_actual=?, expected_cash=?, difference=?, closed_by=?, status='closed' WHERE id=?",
    [Date.now(), actual, expected, diff, closedBy || "", cur.id]
  );
  persist();
  return getState();
}

function listShiftHistory(limit) {
  return all("SELECT * FROM shifts WHERE status = 'closed' ORDER BY closed_at DESC LIMIT ?", [limit || 20]).map(s => ({
    id: s.id, openedAt: s.opened_at, closedAt: s.closed_at, openingCash: s.opening_cash,
    closingCashActual: s.closing_cash_actual, expectedCash: s.expected_cash, difference: s.difference,
    openedBy: s.opened_by, closedBy: s.closed_by,
  }));
}

function getState() {
  const shift = getOpenShift();
  const monthStart = startOfMonthMs();
  const monthClosedAt = Number(getSetting("month_closed_at", 0)) || 0;
  const effectiveMonthStart = Math.max(monthStart, monthClosedAt);

  const products = all("SELECT * FROM products ORDER BY id").map(mapProduct);

  let todayAgg = { sales: 0, cnt: 0 };
  let recentTx = [];
  let productSales = [];
  let currentShift = null;
  let shiftNeedsClosing = false;
  let shiftExpenses = [];

  if (shift) {
    todayAgg = one("SELECT COALESCE(SUM(total),0) as sales, COUNT(*) as cnt FROM transactions WHERE shift_id = ? AND (refunded IS NULL OR refunded = 0)", [shift.id]) || { sales: 0, cnt: 0 };
    const cashSalesAgg = one("SELECT COALESCE(SUM(total),0) as sales FROM transactions WHERE shift_id = ? AND (refunded IS NULL OR refunded = 0) AND (payment_method IS NULL OR payment_method = 'cash')", [shift.id]) || { sales: 0 };
    const cardSalesAgg = one("SELECT COALESCE(SUM(total),0) as sales FROM transactions WHERE shift_id = ? AND (refunded IS NULL OR refunded = 0) AND payment_method != 'cash'", [shift.id]) || { sales: 0 };
    const expAgg = one("SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE shift_id = ?", [shift.id]) || { total: 0 };
    shiftExpenses = all("SELECT * FROM expenses WHERE shift_id = ? ORDER BY created_at DESC", [shift.id]);

    recentTx = all("SELECT id, invoice_number, order_token as orderToken, buzzer_note as buzzerNote, created_at, time_label as time, items_count as itemsCount, total, items_json as itemsJson, payment_method as paymentMethod, refunded FROM transactions WHERE shift_id = ? ORDER BY created_at DESC LIMIT 20", [shift.id]).map(tx => {
      let items = [];
      try { items = JSON.parse(tx.itemsJson); } catch (e) { items = []; }
      return {
        id: tx.id,
        invoiceNumber: tx.invoice_number || tx.id,
        orderToken: tx.orderToken || tx.invoice_number || tx.id,
        buzzerNote: tx.buzzerNote || "",
        time: tx.time,
        itemsCount: tx.itemsCount || items.length,
        items: items,
        total: tx.total || 0,
        paymentMethod: tx.paymentMethod || "cash",
        refunded: !!tx.refunded,
      };
    });

    productSales = getProductSalesForShift(shift.id);
    const cashSalesVal = cashSalesAgg.sales || 0;
    const cardSalesVal = cardSalesAgg.sales || 0;
    const expVal = expAgg.total || 0;
    currentShift = {
      id: shift.id,
      openedAt: shift.opened_at,
      openingCash: shift.opening_cash || 0,
      openedBy: shift.opened_by,
      cashSales: cashSalesVal,
      cardSales: cardSalesVal,
      totalExpenses: expVal,
      expectedCash: (shift.opening_cash || 0) + cashSalesVal - expVal,
    };
    shiftNeedsClosing = isDifferentCalendarDay(shift.opened_at) && (todayAgg.cnt || 0) > 0;
  }

  // رقم المناداة للطلب التالي
  let nextToken = 1;
  if (shift) {
    const tCount = one("SELECT COUNT(*) as cnt FROM transactions WHERE shift_id = ? AND (refunded IS NULL OR refunded = 0)", [shift.id]);
    nextToken = (tCount ? (tCount.cnt || 0) : 0) + 1;
  } else {
    const startOfDay = new Date().setHours(0,0,0,0);
    const tCount = one("SELECT COUNT(*) as cnt FROM transactions WHERE created_at >= ? AND (refunded IS NULL OR refunded = 0)", [startOfDay]);
    nextToken = (tCount ? (tCount.cnt || 0) : 0) + 1;
  }

  const monthAgg = one("SELECT COALESCE(SUM(total),0) as sales FROM transactions WHERE created_at >= ? AND (refunded IS NULL OR refunded = 0)", [effectiveMonthStart]) || { sales: 0 };
  const topProducts = [...getProductSalesSince(effectiveMonthStart)].sort((a, b) => b.qty - a.qty).slice(0, 5);

  const heldRows = all("SELECT * FROM held_carts ORDER BY created_at");
  const heldCarts = heldRows.map(h => {
    let items = [];
    try { items = JSON.parse(h.items_json); } catch (_) { items = []; }
    return {
      id: h.id,
      items,
      discount: h.discount || 0,
      time: h.time_label || "",
    };
  });

  return {
    products,
    todaySales: todayAgg.sales || 0,
    ordersCount: todayAgg.cnt || 0,
    nextOrderToken: nextToken,
    monthSales: monthAgg.sales || 0,
    recentTx,
    heldCarts,
    productSales,
    topProducts,
    weekly: getWeeklySales(),
    dayNeedsClosing: shiftNeedsClosing,
    currentShift,
    shiftExpenses,
    receiptSettings: getReceiptSettings(),
    shortages: listShortages(),
    shortageCatalog: listShortageCatalog(),
    suppliers: listSuppliers(),
    purchases: listPurchases(),
  };
}

function checkout(lines, total, paymentMethod, buzzerNote) {
  const shift = getOpenShift();
  const shiftId = shift ? shift.id : null;
  const timeLabel = new Date().toLocaleTimeString("ar", { hour: "2-digit", minute: "2-digit" });
  try {
    run("BEGIN");
    for (const l of (lines || [])) {
      if (!l || !l.id) continue;
      const p = one("SELECT track_stock FROM products WHERE id = ?", [l.id]);
      if (p && p.track_stock) {
        run("UPDATE products SET stock = stock - ? WHERE id = ?", [l.qty || 1, l.id]);
      }
    }
    // رقم الفاتورة التسلسلي: عدد الفواتير الكلي + 1
    const countRow = one("SELECT COUNT(*) as cnt FROM transactions");
    const invoiceNumber = (countRow ? (countRow.cnt || 0) : 0) + 1;

    // رقم المناداة للزبون: يبدأ من 1 لكل وردية أو يوم
    let tokenRow;
    if (shiftId) {
      tokenRow = one("SELECT COUNT(*) as cnt FROM transactions WHERE shift_id = ? AND (refunded IS NULL OR refunded = 0)", [shiftId]);
    } else {
      const startOfDay = new Date().setHours(0,0,0,0);
      tokenRow = one("SELECT COUNT(*) as cnt FROM transactions WHERE created_at >= ? AND (refunded IS NULL OR refunded = 0)", [startOfDay]);
    }
    const orderToken = (tokenRow ? (tokenRow.cnt || 0) : 0) + 1;

    run(
      "INSERT INTO transactions (invoice_number, order_token, buzzer_note, created_at, time_label, items_count, total, items_json, shift_id, payment_method, refunded) VALUES (?,?,?,?,?,?,?,?,?,?,0)",
      [invoiceNumber, orderToken, buzzerNote || null, Date.now(), timeLabel, (lines || []).length, Number(total) || 0, JSON.stringify(lines || []), shiftId, paymentMethod || "cash"]
    );
    run("COMMIT");
  } catch (e) {
    try { run("ROLLBACK"); } catch (_) {}
    throw e;
  }
  persist();
  return getState();
}

function refundTransaction(id) {
  const tx = one("SELECT * FROM transactions WHERE id = ?", [id]);
  if (!tx || tx.refunded) return getState();
  try {
    run("BEGIN");
    let items = [];
    try { items = JSON.parse(tx.items_json); } catch (e) { items = []; }
    for (const it of items) {
      if (it.id) {
        const p = one("SELECT track_stock FROM products WHERE id = ?", [it.id]);
        if (p && p.track_stock) {
          run("UPDATE products SET stock = stock + ? WHERE id = ?", [it.qty || 1, it.id]);
        }
      } else if (it.name) {
        const p = one("SELECT id, track_stock FROM products WHERE name = ?", [it.name]);
        if (p && p.track_stock) {
          run("UPDATE products SET stock = stock + ? WHERE id = ?", [it.qty || 1, p.id]);
        }
      }
    }
    run("UPDATE transactions SET refunded = 1 WHERE id = ?", [id]);
    run("COMMIT");
  } catch (e) {
    try { run("ROLLBACK"); } catch (_) {}
    throw e;
  }
  persist();
  return getState();
}

function closeWeek() {
  setSetting("week_closed_at", Date.now());
  persist();
  return getState();
}

function closeMonth() {
  setSetting("month_closed_at", Date.now());
  persist();
  return getState();
}

function addExpense(amount, reason, createdBy) {
  const shift = getOpenShift();
  const shiftId = shift ? shift.id : null;
  run(
    "INSERT INTO expenses (id, shift_id, amount, reason, created_by, created_at) VALUES (?,?,?,?,?,?)",
    [Date.now(), shiftId, Number(amount) || 0, (reason || "مصروف درج").trim(), createdBy || "", Date.now()]
  );
  persist();
  return getState();
}

function listExpenses(shiftId) {
  if (shiftId) {
    return all("SELECT * FROM expenses WHERE shift_id = ? ORDER BY created_at DESC", [shiftId]);
  }
  return all("SELECT * FROM expenses ORDER BY created_at DESC LIMIT 50");
}

function saveProduct(product) {
  run(`
    INSERT INTO products (id,name,category,price,stock,threshold,track_stock) VALUES (?,?,?,?,?,?,?)
    ON CONFLICT(id) DO UPDATE SET name=excluded.name, category=excluded.category, price=excluded.price, stock=excluded.stock, threshold=excluded.threshold, track_stock=excluded.track_stock
  `, [product.id, product.name, product.category, product.price, product.stock, product.threshold, product.trackStock === false ? 0 : 1]);
  persist();
  return all("SELECT * FROM products ORDER BY id").map(mapProduct);
}

function deleteProduct(id) {
  run("DELETE FROM products WHERE id = ?", [id]);
  persist();
  return all("SELECT * FROM products ORDER BY id").map(mapProduct);
}

function adjustStock(id, amount) {
  run("UPDATE products SET stock = stock + ? WHERE id = ?", [amount, id]);
  persist();
  return all("SELECT * FROM products ORDER BY id").map(mapProduct);
}

function syncProductsFromCloud(products) {
  if (Array.isArray(products) && products.length > 0) {
    run("BEGIN");
    try {
      for (const product of products) {
        if (!product || !product.id) continue;
        run(`
          INSERT INTO products (id,name,category,price,stock,threshold,track_stock) VALUES (?,?,?,?,?,?,?)
          ON CONFLICT(id) DO UPDATE SET name=excluded.name, category=excluded.category, price=excluded.price, stock=excluded.stock, threshold=excluded.threshold, track_stock=excluded.track_stock
        `, [product.id, product.name, product.category, product.price, product.stock, product.threshold, product.trackStock === false ? 0 : 1]);
      }
      run("COMMIT");
    } catch (e) {
      try { run("ROLLBACK"); } catch (_) {}
      console.error("Error syncing products from cloud:", e);
    }
    persist();
  }
  return all("SELECT * FROM products ORDER BY id").map(mapProduct);
}


function saveHeldCarts(heldCarts) {
  run("BEGIN");
  try {
    run("DELETE FROM held_carts");
    for (const h of heldCarts) {
      run("INSERT INTO held_carts (id, items_json, discount, time_label, created_at) VALUES (?,?,?,?,?)",
        [h.id, JSON.stringify(h.items), h.discount || 0, h.time || "", h.id]);
    }
    run("COMMIT");
  } catch (e) {
    run("ROLLBACK");
    throw e;
  }
  persist();
}

// ================= إعدادات الفاتورة =================
function getReceiptSettings() {
  return {
    storeName: getSetting("receipt_store_name", "مقهى بُنّ"),
    address: getSetting("receipt_address", ""),
    phone: getSetting("receipt_phone", ""),
    footerMsg: getSetting("receipt_footer_msg", "شكرًا لزيارتكم"),
    logoDataUrl: getSetting("receipt_logo", ""),
    printerName: getSetting("receipt_printer", ""),
    paperWidth: getSetting("receipt_paper_width", "80"),
  };
}

function saveReceiptSettings(settings) {
  if (!settings) return getReceiptSettings();
  if (typeof settings.storeName === "string") setSetting("receipt_store_name", settings.storeName.trim());
  if (typeof settings.address === "string") setSetting("receipt_address", settings.address.trim());
  if (typeof settings.phone === "string") setSetting("receipt_phone", settings.phone.trim());
  if (typeof settings.footerMsg === "string") setSetting("receipt_footer_msg", settings.footerMsg.trim());
  if (typeof settings.logoDataUrl === "string") setSetting("receipt_logo", settings.logoDataUrl);
  if (typeof settings.printerName === "string") setSetting("receipt_printer", settings.printerName);
  if (typeof settings.paperWidth === "string") setSetting("receipt_paper_width", settings.paperWidth);
  persist();
  return getReceiptSettings();
}

// ================= نواقص البضاعة =================
function addShortage(name, note, reporter) {
  run("INSERT INTO shortages (id, name, note, reporter, created_at, resolved) VALUES (?,?,?,?,?,0)",
    [Date.now(), name, note || "", reporter || "", Date.now()]);
  // نحفظ الصنف بقائمة النواقص المعروفة تلقائيًا حتى يظهر كخيار جاهز المرة الجاية
  try { run("INSERT OR IGNORE INTO shortage_catalog (id, name) VALUES (?,?)", [Date.now() + 1, name.trim()]); } catch (e) {}
  persist();
  return { shortages: listShortages(), catalog: listShortageCatalog() };
}

function listShortages() {
  return all("SELECT * FROM shortages WHERE resolved = 0 ORDER BY created_at DESC").map(r => ({
    id: r.id, name: r.name, note: r.note, reporter: r.reporter, createdAt: r.created_at,
  }));
}

function resolveShortage(id) {
  run("UPDATE shortages SET resolved = 1 WHERE id = ?", [id]);
  persist();
  return listShortages();
}

// ================= قائمة أصناف النواقص الجاهزة (اختصار الإدخال) =================
function listShortageCatalog() {
  return all("SELECT * FROM shortage_catalog ORDER BY name").map(r => ({ id: r.id, name: r.name }));
}

function addShortageCatalogItem(name) {
  const trimmed = (name || "").trim();
  if (trimmed) {
    try { run("INSERT OR IGNORE INTO shortage_catalog (id, name) VALUES (?,?)", [Date.now(), trimmed]); persist(); } catch (e) {}
  }
  return listShortageCatalog();
}

function deleteShortageCatalogItem(id) {
  run("DELETE FROM shortage_catalog WHERE id = ?", [id]);
  persist();
  return listShortageCatalog();
}

// ================= الموردون والمشتريات =================
function addSupplier(name, phone, note) {
  run("INSERT INTO suppliers (id, name, phone, note, created_at) VALUES (?,?,?,?,?)",
    [Date.now(), name, phone || "", note || "", Date.now()]);
  persist();
  return listSuppliers();
}

function listSuppliers() {
  const suppliers = all("SELECT * FROM suppliers ORDER BY name");
  return suppliers.map(s => {
    const agg = one("SELECT COALESCE(SUM(cost),0) as total, COUNT(*) as cnt FROM purchases WHERE supplier_id = ?", [s.id]) || { total: 0, cnt: 0 };
    const paidAgg = one("SELECT COALESCE(SUM(amount),0) as total FROM supplier_payments WHERE supplier_id = ?", [s.id]) || { total: 0 };
    const totalSpent = agg.total || 0;
    const totalPaid = paidAgg.total || 0;
    return {
      id: s.id, name: s.name, phone: s.phone, note: s.note,
      totalSpent, purchaseCount: agg.cnt || 0,
      totalPaid, balance: totalSpent - totalPaid,
    };
  });
}

function addSupplierPayment(supplierId, amount, note, createdBy) {
  run("INSERT INTO supplier_payments (id, supplier_id, amount, note, created_by, created_at) VALUES (?,?,?,?,?,?)",
    [Date.now(), supplierId, Number(amount) || 0, note || "", createdBy || "", Date.now()]);
  persist();
  return listSuppliers();
}

function listSupplierPayments(supplierId) {
  return all("SELECT * FROM supplier_payments WHERE supplier_id = ? ORDER BY created_at DESC", [supplierId]).map(p => ({
    id: p.id, amount: p.amount, note: p.note, createdBy: p.created_by, createdAt: p.created_at,
  }));
}

function addPurchase(purchase) {
  const id = Date.now();
  run(
    "INSERT INTO purchases (id, supplier_id, description, cost, product_id, restock_qty, created_by, created_at) VALUES (?,?,?,?,?,?,?,?)",
    [id, purchase.supplierId || null, purchase.description, Number(purchase.cost) || 0, purchase.productId || null, purchase.restockQty || null, purchase.createdBy || "", Date.now()]
  );
  if (purchase.productId && purchase.restockQty) {
    run("UPDATE products SET stock = stock + ? WHERE id = ?", [Number(purchase.restockQty), purchase.productId]);
  }
  persist();
  return { purchases: listPurchases(), products: all("SELECT * FROM products ORDER BY id").map(mapProduct) };
}

function listPurchases(limit) {
  const rows = all(`
    SELECT p.*, s.name as supplier_name FROM purchases p
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    ORDER BY p.created_at DESC LIMIT ?
  `, [limit || 100]);
  return rows.map(r => ({
    id: r.id, supplierId: r.supplier_id, supplierName: r.supplier_name || "بدون مورد",
    description: r.description, cost: r.cost, productId: r.product_id, restockQty: r.restock_qty,
    createdBy: r.created_by, createdAt: r.created_at,
  }));
}

// ================= الترخيص =================
function getLicenseInfo() {
  let deviceId = getSetting("device_id", "");
  if (!deviceId) {
    deviceId = crypto.randomUUID();
    setSetting("device_id", deviceId);
    persist();
  }
  return {
    deviceId,
    licenseKey: getSetting("license_key", ""),
    lastValidAt: Number(getSetting("license_last_valid_at", 0)) || 0,
    lastValidCustomer: getSetting("license_customer", ""),
    lastValidExpiresAt: Number(getSetting("license_expires_at", 0)) || null,
  };
}

function saveLicenseValidation(key, customerName, expiresAt) {
  setSetting("license_key", key);
  setSetting("license_last_valid_at", Date.now());
  setSetting("license_customer", customerName || "");
  setSetting("license_expires_at", expiresAt || "");
  persist();
  return getLicenseInfo();
}

function clearLicense() {
  setSetting("license_key", "");
  persist();
  return getLicenseInfo();
}

function closeDay() {
  setSetting("day_start_time", Date.now());
  persist();
  return getState();
}

module.exports = {
  initDatabase, getState, checkout, refundTransaction, saveProduct, deleteProduct,
  adjustStock, syncProductsFromCloud, saveHeldCarts, closeDay,
  getReceiptSettings, saveReceiptSettings,
  addShortage, listShortages, resolveShortage,
  listShortageCatalog, addShortageCatalogItem, deleteShortageCatalogItem,
  openShift, closeShift, getOpenShift, listShiftHistory, closeWeek, closeMonth,
  addExpense, listExpenses,
  addSupplier, listSuppliers, addPurchase, listPurchases,
  addSupplierPayment, listSupplierPayments,
  getLicenseInfo, saveLicenseValidation, clearLicense,
  getDbFilePath,
};
