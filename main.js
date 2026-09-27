const { app, BrowserWindow, ipcMain, dialog } = require("electron");
const path = require("path");
const fs = require("fs");
const { autoUpdater } = require("electron-updater");
const nodemailer = require("nodemailer");
const db = require("./db");

let mainWindow = null;

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1280,
    height: 800,
    minWidth: 900,
    minHeight: 600,
    title: "نظام إدارة المقهى - بُنّ",
    autoHideMenuBar: true,
    webPreferences: {
      preload: path.join(__dirname, "preload.js"),
      contextIsolation: true,
      nodeIntegration: false,
      spellcheck: false,
      focusOpenWindows: true
    },
  });

  mainWindow.webContents.on('did-finish-load', () => {
    mainWindow.focus();
  });

  mainWindow.loadFile("index.html");

  if (app.isPackaged) {
    setupAutoUpdater();
  }
}

function setupAutoUpdater() {
  // تفعيل التحميل التلقائي في الخلفية وتثبيت التحديث عند إغلاق التطبيق
  autoUpdater.autoDownload = true;
  autoUpdater.autoInstallOnAppQuit = true;
  const send = (data) => { if (mainWindow) mainWindow.webContents.send("update-status", data); };

  autoUpdater.on("checking-for-update", () => send({ status: "checking" }));
  autoUpdater.on("update-available", (info) => send({ status: "available", version: info.version }));
  autoUpdater.on("update-not-available", (info) => send({ status: "latest", version: info ? info.version : app.getVersion() }));
  autoUpdater.on("download-progress", (p) => send({ status: "downloading", percent: Math.round(p.percent) }));
  autoUpdater.on("update-downloaded", (info) => send({ status: "ready", version: info.version }));
  autoUpdater.on("error", (error) => {
    console.error("Update error:", error);
    send({ status: "error", message: "تعذّر فحص أو تنزيل التحديث من السيرفر." });
  });

  // فحص أولي عند التشغيل
  autoUpdater.checkForUpdates().catch(() => {});

  // فحص دوري تلقائي كل ساعة للتحقق من أي إصدار جديد ترسله من بعيد
  setInterval(() => {
    autoUpdater.checkForUpdates().catch(() => {});
  }, 60 * 60 * 1000);
}

ipcMain.handle("get-app-version", () => app.getVersion());

ipcMain.handle("check-for-update", async () => {
  if (!app.isPackaged) return { ok: false, error: "التحديثات التلقائية تعمل في النسخة المثبتة الرسمية (Packaged)" };
  try {
    const res = await autoUpdater.checkForUpdates();
    return { ok: true, updateInfo: res && res.updateInfo };
  } catch (error) {
    console.error("Manual check error:", error);
    return { ok: false, error: String(error.message || error) };
  }
});

ipcMain.handle("download-update", async () => {
  if (!app.isPackaged) return { ok: false, error: "updates-only-in-packaged-app" };
  try {
    await autoUpdater.downloadUpdate();
    return { ok: true };
  } catch (error) {
    console.error("Download update error:", error);
    return { ok: false, error: String(error.message || error) };
  }
});

function createAutoBackup() {
  try {
    const userData = app.getPath("userData");
    const backupsDir = path.join(userData, "backups");
    if (!fs.existsSync(backupsDir)) {
      fs.mkdirSync(backupsDir, { recursive: true });
    }
    const today = new Date().toISOString().slice(0, 10);
    const backupFile = path.join(backupsDir, `cafe-backup-${today}.sqlite`);
    const dbFile = db.getDbFilePath();
    if (fs.existsSync(dbFile)) {
      fs.copyFileSync(dbFile, backupFile);
      console.log("[Backup] Auto backup created:", backupFile);
    }
    const files = fs.readdirSync(backupsDir)
      .filter(f => f.startsWith("cafe-backup-") && f.endsWith(".sqlite"))
      .sort();
    while (files.length > 14) {
      const oldest = files.shift();
      try { fs.unlinkSync(path.join(backupsDir, oldest)); } catch (_) {}
    }
  } catch (err) {
    console.warn("[Backup] Auto backup failed:", err);
  }
}

app.whenReady().then(async () => {
  try {
    await db.initDatabase(app.getPath("userData"));
    createAutoBackup();
    createWindow();
  } catch (err) {
    console.error("خطأ أثناء تهيئة قاعدة البيانات:", err);
  }

  app.on("activate", () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on("window-all-closed", () => {
  if (process.platform !== "darwin") app.quit();
});

// ================= معالجات أحداث IPC لربط SQLite =================

ipcMain.handle("db-get-state", async () => db.getState());
ipcMain.handle("db-checkout", async (event, { lines, total, paymentMethod, buzzerNote }) => db.checkout(lines, total, paymentMethod, buzzerNote));
ipcMain.handle("db-refund-transaction", async (event, id) => db.refundTransaction(id));
ipcMain.handle("db-save-product", async (event, product) => db.saveProduct(product));
ipcMain.handle("db-delete-product", async (event, id) => db.deleteProduct(id));
ipcMain.handle("db-adjust-stock", async (event, { id, amount }) => db.adjustStock(id, amount));
ipcMain.handle("db-sync-products-from-cloud", async (event, products) => db.syncProductsFromCloud(products));
ipcMain.handle("db-save-held-carts", async (event, heldCarts) => {
  db.saveHeldCarts(heldCarts);
  return true;
});
ipcMain.handle("db-open-shift", async (event, { openingCash, openedBy }) => db.openShift(openingCash, openedBy));
ipcMain.handle("db-close-shift", async (event, { actualCash, closedBy }) => {
  const res = await db.closeShift(actualCash, closedBy);
  createAutoBackup();
  return res;
});
ipcMain.handle("db-list-shift-history", async () => db.listShiftHistory());
ipcMain.handle("db-close-week", async () => db.closeWeek());
ipcMain.handle("db-close-month", async () => db.closeMonth());
ipcMain.handle("db-add-expense", async (event, { amount, reason, createdBy }) => db.addExpense(amount, reason, createdBy));

ipcMain.handle("db-add-supplier", async (event, { name, phone, note }) => db.addSupplier(name, phone, note));
ipcMain.handle("db-add-purchase", async (event, purchase) => db.addPurchase(purchase));
ipcMain.handle("db-add-supplier-payment", async (event, { supplierId, amount, note, createdBy }) => db.addSupplierPayment(supplierId, amount, note, createdBy));
ipcMain.handle("db-list-supplier-payments", async (event, supplierId) => db.listSupplierPayments(supplierId));

ipcMain.handle("db-add-shortage-catalog-item", async (event, name) => db.addShortageCatalogItem(name));
ipcMain.handle("db-delete-shortage-catalog-item", async (event, id) => db.deleteShortageCatalogItem(id));

ipcMain.handle("pick-logo-image", async () => {
  const result = await dialog.showOpenDialog(mainWindow, {
    title: "اختر صورة الشعار",
    filters: [{ name: "صور", extensions: ["png", "jpg", "jpeg", "webp"] }],
    properties: ["openFile"],
  });
  if (result.canceled || !result.filePaths || !result.filePaths[0]) return { ok: false };
  const filePath = result.filePaths[0];
  try {
    const stat = fs.statSync(filePath);
    if (stat.size > 2 * 1024 * 1024) {
      return { ok: false, error: "الصورة كبيرة جدًا (الحد الأقصى 2 ميغابايت) — اختر صورة أصغر" };
    }
    const ext = path.extname(filePath).slice(1).toLowerCase();
    const mime = ext === "jpg" ? "jpeg" : ext;
    const base64 = fs.readFileSync(filePath).toString("base64");
    return { ok: true, dataUrl: `data:image/${mime};base64,${base64}` };
  } catch (e) {
    return { ok: false, error: String(e.message || e) };
  }
});

ipcMain.handle("backup-export", async () => {
  const defaultName = `نسخة-احتياطية-${new Date().toISOString().slice(0, 10)}.sqlite`;
  const result = await dialog.showSaveDialog(mainWindow, {
    title: "حفظ نسخة احتياطية",
    defaultPath: defaultName,
    filters: [{ name: "قاعدة بيانات SQLite", extensions: ["sqlite"] }],
  });
  if (result.canceled || !result.filePath) return { ok: false };
  try {
    fs.copyFileSync(db.getDbFilePath(), result.filePath);
    return { ok: true, path: result.filePath };
  } catch (e) {
    return { ok: false, error: String(e.message || e) };
  }
});

ipcMain.handle("backup-restore", async () => {
  const result = await dialog.showOpenDialog(mainWindow, {
    title: "استعادة نسخة احتياطية",
    filters: [{ name: "قاعدة بيانات SQLite", extensions: ["sqlite"] }],
    properties: ["openFile"],
  });
  if (result.canceled || !result.filePaths || !result.filePaths[0]) return { ok: false };
  try {
    fs.copyFileSync(result.filePaths[0], db.getDbFilePath());
    return { ok: true, needsRestart: true };
  } catch (e) {
    return { ok: false, error: String(e.message || e) };
  }
});

ipcMain.handle("open-backups-folder", async () => {
  const { shell } = require("electron");
  const userData = app.getPath("userData");
  const backupsDir = path.join(userData, "backups");
  if (!fs.existsSync(backupsDir)) {
    fs.mkdirSync(backupsDir, { recursive: true });
  }
  await shell.openPath(backupsDir);
  return { ok: true, path: backupsDir };
});

ipcMain.handle("db-save-receipt-settings", async (event, settings) => db.saveReceiptSettings(settings));

ipcMain.handle("db-get-license-info", async () => db.getLicenseInfo());
ipcMain.handle("db-save-license-validation", async (event, { key, customerName, expiresAt }) => db.saveLicenseValidation(key, customerName, expiresAt));
ipcMain.handle("db-clear-license", async () => db.clearLicense());

ipcMain.handle("db-add-shortage", async (event, { name, note, reporter }) => db.addShortage(name, note, reporter));
ipcMain.handle("db-resolve-shortage", async (event, id) => db.resolveShortage(id));

// ================= مهام الطباعة (مُعدّلة ومُؤمّنة) =================

function buildReceiptHtml(receipt) {
  const width = receipt.paperWidth === "40" ? 40 : 80;
  const is40 = width === 40;
  const fontSize = is40 ? 11 : 13;

  const itemsHtml = (receipt.items || [])
    .map(
      (item) => `
    <tr style="border-bottom: 1px dashed #000000;">
      <td style="text-align:right; padding: 5px 0; font-size: ${is40 ? 11 : 12.5}px; font-weight: 800; color: #000000;">${item.name}</td>
      <td style="text-align:center; padding: 5px 0; font-size: ${is40 ? 11.5 : 13}px; font-weight: 900; color: #000000;">×${item.qty}</td>
      <td style="text-align:left; padding: 5px 0; font-size: ${is40 ? 11 : 12.5}px; font-weight: 900; color: #000000;">${item.lineTotal}</td>
    </tr>`
    )
    .join("");

  return `
    <html dir="rtl">
    <head>
      <meta charset="utf-8">
      <style>
        @page { size: ${width}mm auto; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, Arial, 'Helvetica Neue', sans-serif;
          width: 100%;
          max-width: ${is40 ? "38mm" : "74mm"};
          margin: 0 auto;
          padding: ${is40 ? "2mm 1mm" : "4mm 2mm"};
          font-size: ${fontSize}px;
          line-height: 1.35;
          color: #000000;
          background: #FFFFFF;
          -webkit-font-smoothing: antialiased;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .solid-line { border-bottom: 2px solid #000000; margin: 6px 0; }
        .dashed-line { border-bottom: 1.5px dashed #000000; margin: 6px 0; }
        .double-line { border-bottom: 3px double #000000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        .meta-row { display: flex; justify-content: space-between; font-size: ${is40 ? 10 : 11.5}px; font-weight: 700; margin: 2px 0; }
      </style>
    </head>
    <body>
      ${receipt.logoDataUrl ? `<div class="text-center" style="margin-bottom:6px;"><img src="${receipt.logoDataUrl}" style="max-width:${is40 ? 30 : 50}mm;max-height:${is40 ? 18 : 28}mm;object-fit:contain;filter:contrast(150%);" /></div>` : ""}
      
      <!-- اسم المقهى والعنوان -->
      <div class="text-center" style="font-size: ${is40 ? 15 : 18}px; font-weight: 900; letter-spacing: 0.5px; margin-bottom: 2px;">
        ${receipt.storeName || "نظام إدارة المقهى"}
      </div>
      ${receipt.address ? `<div class="text-center" style="font-size: ${is40 ? 10 : 11}px; font-weight: 700;">${receipt.address}</div>` : ""}
      ${receipt.phone ? `<div class="text-center" style="font-size: ${is40 ? 10 : 11}px; font-weight: 700;">هاتف: ${receipt.phone}</div>` : ""}

      <!-- كرت رقم المناداة الواضح للزبون والباريستا -->
      <div style="border: 2.5px solid #000000; border-radius: 6px; padding: 6px 4px; margin: 8px 0; text-align: center;">
        <div style="font-size: ${is40 ? 10.5 : 12}px; font-weight: 900; letter-spacing: 1px;">رقم المناداة / TOKEN</div>
        <div style="font-size: ${is40 ? 28 : 36}px; font-weight: 900; line-height: 1.1; margin: 3px 0; font-family: monospace, sans-serif;">
          # ${receipt.orderToken || receipt.invoiceNumber || 1}
        </div>
        ${receipt.buzzerNote ? `<div style="font-size: ${is40 ? 11 : 12.5}px; font-weight: 900;">جهاز البيجر: <b>${receipt.buzzerNote}</b></div>` : ""}
        <div style="font-size: ${is40 ? 9.5 : 10.5}px; font-weight: 800;">يرجى الانتظار حتى مناداة رقمك ☕</div>
      </div>

      <!-- تفاصيل الفاتورة -->
      <div style="border-top: 1.5px solid #000000; border-bottom: 1.5px solid #000000; padding: 4px 0; margin: 6px 0;">
        <div class="meta-row">
          <span>التاريخ: ${receipt.date || ""} ${receipt.time || ""}</span>
          <span>فاتورة: <b>#${receipt.invoiceNumber || ""}</b></span>
        </div>
        <div class="meta-row">
          <span>الكاشير: <b>${receipt.cashier || ""}</b></span>
          <span>الدفع: <b>${receipt.paymentMethodLabel || "نقداً"}</b></span>
        </div>
      </div>

      <!-- جدول الأصناف -->
      <table style="margin: 6px 0;">
        <thead>
          <tr style="border-bottom: 2px solid #000000;">
            <th style="text-align:right; padding: 4px 0; font-size: ${is40 ? 11 : 12}px; font-weight: 900;">الصنف</th>
            <th style="text-align:center; padding: 4px 0; font-size: ${is40 ? 11 : 12}px; font-weight: 900; width: 45px;">العدد</th>
            <th style="text-align:left; padding: 4px 0; font-size: ${is40 ? 11 : 12}px; font-weight: 900; width: 68px;">الإجمالي</th>
          </tr>
        </thead>
        <tbody>
          ${itemsHtml}
        </tbody>
      </table>

      <!-- المجاميع والخصم -->
      <div style="margin-top: 6px; font-size: ${is40 ? 11 : 12}px; font-weight: 800;">
        <div style="display:flex; justify-content:space-between; padding: 2px 0;">
          <span>المجموع الفرعي:</span>
          <span>${receipt.subtotal || 0}</span>
        </div>
        ${receipt.discountAmount && receipt.discountAmount !== "0.000 د.ل" ? `
        <div style="display:flex; justify-content:space-between; padding: 2px 0;">
          <span>الخصم:</span>
          <span>-${receipt.discountAmount}</span>
        </div>` : ""}

        <!-- الإجمالي النهائي البارز -->
        <div style="border-top: 2.5px solid #000000; border-bottom: 2.5px solid #000000; display:flex; justify-content:space-between; padding: 6px 0; margin-top: 5px; font-size: ${is40 ? 15 : 18}px; font-weight: 900;">
          <span>الصافي المطلوب:</span>
          <span>${receipt.total || 0}</span>
        </div>
      </div>

      <!-- رسالة النهاية -->
      <div class="text-center" style="margin-top: 10px; font-size: ${is40 ? 10.5 : 11.5}px; font-weight: 800;">
        ${receipt.footerMsg || "شكرًا لزيارتكم! نأمل رؤيتكم قريباً."}
      </div>
      <div class="text-center" style="font-size: 13px; letter-spacing: 3px; font-weight: 900; margin-top: 5px;">* * * * * * * * *</div>
    </body>
    </html>
  `;
}

function buildDayReportHtml(report) {
  const width = (report.receiptSettings && report.receiptSettings.paperWidth === "40") ? 40 : 80;
  const is40 = width === 40;
  const fontSize = is40 ? 11 : 13;

  const itemsHtml = (report.productSales || [])
    .map(
      (p) => `
    <tr style="border-bottom: 1px dashed #000000;">
      <td style="text-align:right; padding: 4px 0; font-weight: 800; font-size: ${is40 ? 11 : 12.5}px;">${p.name}</td>
      <td style="text-align:center; padding: 4px 0; font-weight: 900; font-size: ${is40 ? 11.5 : 13}px;">×${p.qty}</td>
      <td style="text-align:left; padding: 4px 0; font-weight: 900; font-size: ${is40 ? 11 : 12.5}px;">${p.revenue}</td>
    </tr>`
    )
    .join("");

  const expensesHtml = (report.expensesList || [])
    .map(
      (e) => `
    <tr style="border-bottom: 1px dashed #000000;">
      <td style="text-align:right; padding: 3px 0; font-weight: 700; font-size: ${is40 ? 10.5 : 12}px;">${e.reason}</td>
      <td style="text-align:left; padding: 3px 0; font-weight: 900; font-size: ${is40 ? 10.5 : 12}px;">-${e.amount}</td>
    </tr>`
    )
    .join("");

  return `
    <html dir="rtl">
    <head>
      <meta charset="utf-8">
      <style>
        @page { size: ${width}mm auto; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, Arial, 'Helvetica Neue', sans-serif;
          width: 100%;
          max-width: ${is40 ? "38mm" : "74mm"};
          margin: 0 auto;
          padding: ${is40 ? "2mm 1mm" : "4mm 2mm"};
          font-size: ${fontSize}px;
          line-height: 1.35;
          color: #000000;
          background: #FFFFFF;
          -webkit-font-smoothing: antialiased;
        }
        .text-center { text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        .row { display: flex; justify-content: space-between; font-size: ${is40 ? 11 : 12}px; font-weight: 700; margin: 3px 0; }
        .row.bold { font-weight: 900; font-size: ${is40 ? 13 : 14.5}px; }
        .diff-box {
          border: 2px solid #000000;
          padding: 6px 4px;
          margin: 8px 0;
          text-align: center;
          font-weight: 900;
          font-size: ${is40 ? 13 : 15}px;
        }
      </style>
    </head>
    <body>
      ${report.receiptSettings && report.receiptSettings.logoDataUrl ? `<div class="text-center" style="margin-bottom:6px;"><img src="${report.receiptSettings.logoDataUrl}" style="max-width:${is40 ? 30 : 50}mm;max-height:${is40 ? 18 : 28}mm;object-fit:contain;filter:contrast(150%);" /></div>` : ""}
      <div class="text-center" style="font-size: ${is40 ? 15 : 18}px; font-weight: 900; margin-bottom: 2px;">
        ${(report.receiptSettings && report.receiptSettings.storeName) || "نظام إدارة المقهى"}
      </div>
      <div style="border: 2px solid #000000; padding: 4px; margin: 6px auto; text-align:center; font-weight: 900; font-size: ${is40 ? 11.5 : 13}px;">
        فاتورة إغلاق الوردية (التوكة)
      </div>
      <div style="border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 4px 0; margin: 6px 0; font-size: ${is40 ? 10 : 11.5}px; font-weight: 700;">
        <div style="display:flex; justify-content:space-between;">
          <span>التاريخ: ${report.date}</span>
          <span>المسؤول: <b>${report.closedBy || "-"}</b></span>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:2px;">
          <span>فتح: ${report.openedAt || "-"}</span>
          <span>إغلاق: ${report.time || "-"}</span>
        </div>
      </div>
      
      <!-- جرد النقدية بالدرج -->
      <div class="text-center" style="font-weight:900; font-size: ${is40 ? 11.5 : 13}px; margin: 6px 0 4px;">[ جرد النقدية بالدرج ]</div>
      <div class="row"><span>نقد البداية (الافتتاحي):</span><span>${report.openingCash || "0.000 د.ل"}</span></div>
      <div class="row"><span>+ مبيعات نقداً (كاش):</span><span>${report.cashSales || "0.000 د.ل"}</span></div>
      ${report.expenses ? `<div class="row"><span>- مصروفات الدرج:</span><span>-${report.expenses}</span></div>` : ""}
      
      <div style="border-top: 1.5px dashed #000; margin: 4px 0;"></div>
      <div class="row bold"><span>= النقد المتوقع بالدرج:</span><span>${report.expectedCash || "0.000 د.ل"}</span></div>
      <div class="row bold"><span>النقد الفعلي (المعدود):</span><span>${report.actualCash || "0.000 د.ل"}</span></div>
      
      <div class="diff-box">
        نتيجة الجرد: ${report.diffFormatted || "مطابق تماماً ✓"}
      </div>

      <!-- ملخص المبيعات -->
      <div style="border-top: 2px solid #000; padding-top: 6px; margin-top: 6px;">
        <div class="text-center" style="font-weight:900; font-size: ${is40 ? 11.5 : 13}px; margin-bottom:4px;">[ ملخص مبيعات الوردية ]</div>
        <div class="row"><span>مبيعات نقداً:</span><span>${report.cashSales || "0.000 د.ل"}</span></div>
        <div class="row"><span>مبيعات بطاقة / سداد:</span><span>${report.cardSales || "0.000 د.ل"}</span></div>
        <div class="row"><span>عدد الطلبات:</span><span>${report.ordersCount || 0}</span></div>
        <div class="row bold" style="border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px 0; margin-top: 5px; font-size: ${is40 ? 14 : 16.5}px;">
          <span>إجمالي المبيعات:</span>
          <span>${report.todaySales || "0.000 د.ل"}</span>
        </div>
      </div>

      <!-- تفصيل مبيعات الأصناف -->
      ${itemsHtml.length > 0 ? `
        <div style="margin-top: 8px;">
          <div class="text-center" style="font-weight:900; font-size: ${is40 ? 11.5 : 13}px; margin-bottom:3px;">[ تفصيل مبيعات الأصناف ]</div>
          <table style="margin: 4px 0;">
            <thead>
              <tr style="border-bottom: 2px solid #000;">
                <th style="text-align:right; font-size: ${is40 ? 10.5 : 12}px; font-weight: 900; padding: 3px 0;">الصنف</th>
                <th style="text-align:center; font-size: ${is40 ? 10.5 : 12}px; font-weight: 900; padding: 3px 0; width: 45px;">العدد</th>
                <th style="text-align:left; font-size: ${is40 ? 10.5 : 12}px; font-weight: 900; padding: 3px 0; width: 68px;">الإيراد</th>
              </tr>
            </thead>
            <tbody>
              ${itemsHtml}
            </tbody>
          </table>
        </div>
      ` : ""}

      <!-- مصروفات الدرج -->
      ${expensesHtml.length > 0 ? `
        <div style="margin-top: 8px;">
          <div class="text-center" style="font-weight:900; font-size: ${is40 ? 11.5 : 13}px; margin-bottom:3px;">[ مصروفات الدرج ]</div>
          <table>
            <tbody>
              ${expensesHtml}
            </tbody>
          </table>
        </div>
      ` : ""}

      <div style="border-top: 2px solid #000; margin-top: 10px; padding-top: 10px;">
        <div style="display:flex; justify-content:space-between; font-size: ${is40 ? 10 : 11}px; font-weight: 700;">
          <span>توقيع الكاشير: ..........</span>
          <span>توقيع المستلم: ..........</span>
        </div>
      </div>
      <div class="text-center" style="margin-top: 8px; font-size: 10px; font-weight: 800;">نظام إدارة المقهى — تم إغلاق التوكة بنجاح</div>
    </body>
    </html>
  `;
}

function buildPeriodReportHtml(report) {
  const width = (report.receiptSettings && report.receiptSettings.paperWidth === "40") ? 40 : 80;
  const fontSize = width === 40 ? 10 : 12;
  return `
    <html dir="rtl">
    <head>
      <meta charset="utf-8">
      <style>
        @page { size: ${width}mm auto; margin: ${width === 40 ? 1 : 2}mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; width: 100%; margin: 0; padding: 0; font-size: ${fontSize}px; }
        .title { text-align: center; font-size: 15px; font-weight: bold; margin-bottom: 3px; }
        .subtitle { text-align: center; font-size: 11px; margin-bottom: 8px; }
        .text-center { text-align: center; }
        .line { border-bottom: 1px dashed #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; font-size: 13px; margin: 6px 0; }
        .row.bold { font-weight: bold; font-size: 14px; }
      </style>
    </head>
    <body>
      <div class="title">تقرير إغلاق ${report.periodName || "الفترة"}</div>
      <div class="subtitle">التاريخ: ${report.date || ""}</div>
      <div class="line"></div>
      <div class="row bold"><span>إجمالي المبيعات:</span><span>${report.totalSales || 0}</span></div>
      <div class="line"></div>
      <div class="subtitle" style="margin-top:12px;">تم طباعة التقرير بواسطة نظام إدارة المقهى</div>
    </body>
    </html>
  `;
}

async function printHtml(html, deviceName) {
  let printWin = new BrowserWindow({
    show: false,
    webPreferences: { contextIsolation: true, nodeIntegration: false }
  });
  try {
    await printWin.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(html)}`);
    await new Promise((resolve) => {
      if (deviceName) {
        // طابعة محددة مسبقًا بالإعدادات: طباعة صامتة تلقائية بدون أي مربع حوار
        printWin.webContents.print({ silent: true, printBackground: true, deviceName }, () => resolve());
      } else {
        // ما فيه طابعة محددة بعد: يفتح مربع الاختيار مرة واحدة حتى يختار المستخدم طابعته
        printWin.webContents.print({ silent: false, printBackground: true }, () => resolve());
      }
    });
  } finally {
    if (printWin) printWin.destroy();
  }
  return { ok: true };
}

ipcMain.handle("list-printers", async () => {
  try {
    const printers = await mainWindow.webContents.getPrintersAsync();
    return printers.map(p => ({ name: p.name, displayName: p.displayName || p.name, isDefault: !!p.isDefault }));
  } catch (e) {
    return [];
  }
});

ipcMain.handle("print-receipt", async (event, receipt) => printHtml(buildReceiptHtml(receipt), receipt.printerName));
ipcMain.handle("print-day-report", async (event, report) => printHtml(buildDayReportHtml(report), report.receiptSettings && report.receiptSettings.printerName));
ipcMain.handle("print-period-report", async (event, report) => printHtml(buildPeriodReportHtml(report), report.printerName || (report.receiptSettings && report.receiptSettings.printerName)));

// ================= إرسال تقرير إغلاق اليوم بالبريد (اختياري) =================

function buildReportEmailHtml(r) {
  const money = (n) => (Number(n) || 0).toLocaleString("ar-LY", { minimumFractionDigits: 3, maximumFractionDigits: 3 }) + " د.ل";
  const rows = (r.recentTx || []).map(t =>
    `<tr><td style="padding:6px;border:1px solid #ddd">${t.time}</td><td style="padding:6px;border:1px solid #ddd">${t.items}</td><td style="padding:6px;border:1px solid #ddd">${money(t.total)}</td></tr>`
  ).join("");
  const productRows = (r.productSales || []).map(p =>
    `<tr><td style="padding:6px;border:1px solid #ddd">${p.name}</td><td style="padding:6px;border:1px solid #ddd;text-align:center">${p.qty}</td><td style="padding:6px;border:1px solid #ddd">${money(p.revenue)}</td></tr>`
  ).join("");
  const lowStockList = (r.lowStock || []).map(p => `<li>${p.name} — ${p.stock} متبقي</li>`).join("");
  return `
    <div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;color:#1B2434">
      <h2>تقرير إغلاق اليوم — ${r.date}</h2>
      <p>عدد الطلبات: <b>${r.ordersCount}</b></p>
      <p>إجمالي المبيعات: <b>${money(r.todaySales)}</b></p>
      <h3>مبيعات كل صنف</h3>
      <table style="border-collapse:collapse;width:100%;font-size:13px">
        <tr><th style="padding:6px;border:1px solid #ddd;background:#EAF1FE">الصنف</th><th style="padding:6px;border:1px solid #ddd;background:#EAF1FE">الكمية</th><th style="padding:6px;border:1px solid #ddd;background:#EAF1FE">الإجمالي</th></tr>
        ${productRows || `<tr><td colspan="3" style="padding:10px;text-align:center">لا توجد مبيعات</td></tr>`}
      </table>
      <h3>آخر العمليات</h3>
      <table style="border-collapse:collapse;width:100%;font-size:13px">
        <tr><th style="padding:6px;border:1px solid #ddd;background:#EAF1FE">الوقت</th><th style="padding:6px;border:1px solid #ddd;background:#EAF1FE">عدد الأصناف</th><th style="padding:6px;border:1px solid #ddd;background:#EAF1FE">الإجمالي</th></tr>
        ${rows || `<tr><td colspan="3" style="padding:10px;text-align:center">لا توجد عمليات</td></tr>`}
      </table>
      ${lowStockList ? `<h3 style="color:#DC4C4C">⚠️ تنبيهات مخزون منخفض</h3><ul>${lowStockList}</ul>` : ""}
      <p style="color:#7C8798;font-size:12px;margin-top:20px">تم الإرسال تلقائيًا من نظام إدارة المقهى</p>
    </div>
  `;
}

ipcMain.handle("send-day-report", async (event, report) => {
  let emailConfig;
  try { emailConfig = require("./email-config"); } catch (e) { return { ok: false, error: "not-configured" }; }

  if (!emailConfig.user || String(emailConfig.user).includes("ضع-") || !emailConfig.pass || String(emailConfig.pass).includes("ضع-")) {
    return { ok: false, error: "not-configured" };
  }

  try {
    const transporter = nodemailer.createTransport({
      host: emailConfig.host,
      port: emailConfig.port,
      secure: emailConfig.secure,
      auth: { user: emailConfig.user, pass: emailConfig.pass },
    });
    await transporter.sendMail({
      from: `"${emailConfig.fromName || "نظام إدارة المقهى"}" <${emailConfig.user}>`,
      to: emailConfig.to || emailConfig.user,
      subject: `تقرير إغلاق اليوم - ${report.date}`,
      html: buildReportEmailHtml(report),
    });
    return { ok: true };
  } catch (e) {
    return { ok: false, error: String((e && e.message) || e) };
  }
});

ipcMain.on("restart-app", () => {
  if (app.isPackaged) {
    autoUpdater.quitAndInstall();
  } else {
    app.relaunch();
    app.exit(0);
  }
});

ipcMain.on("relaunch-app", () => {
  app.relaunch();
  app.exit(0);
});
