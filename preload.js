const { contextBridge, ipcRenderer } = require("electron");

contextBridge.exposeInMainWorld("electronAPI", {
  // قاعدة البيانات المحلية SQLite
  dbGetState: () => ipcRenderer.invoke("db-get-state"),
  dbCheckout: (payload) => ipcRenderer.invoke("db-checkout", payload),
  dbSaveProduct: (product) => ipcRenderer.invoke("db-save-product", product),
  dbDeleteProduct: (id) => ipcRenderer.invoke("db-delete-product", id),
  dbAdjustStock: (data) => ipcRenderer.invoke("db-adjust-stock", data),
  dbSyncProductsFromCloud: (products) => ipcRenderer.invoke("db-sync-products-from-cloud", products),
  dbSaveHeldCarts: (carts) => ipcRenderer.invoke("db-save-held-carts", carts),

  // المبيعات والمرتجع
  dbRefundTransaction: (id) => ipcRenderer.invoke("db-refund-transaction", id),

  // الإغلاقات
  dbOpenShift: (data) => ipcRenderer.invoke("db-open-shift", data),
  dbCloseShift: (data) => ipcRenderer.invoke("db-close-shift", data),
  dbListShiftHistory: () => ipcRenderer.invoke("db-list-shift-history"),
  closeWeek: () => ipcRenderer.invoke("db-close-week"),
  closeMonth: () => ipcRenderer.invoke("db-close-month"),

  // مصروفات الدرج (Petty Cash)
  dbAddExpense: (data) => ipcRenderer.invoke("db-add-expense", data),

  // الموردون والمشتريات
  dbAddSupplier: (data) => ipcRenderer.invoke("db-add-supplier", data),
  dbAddPurchase: (data) => ipcRenderer.invoke("db-add-purchase", data),
  dbAddSupplierPayment: (data) => ipcRenderer.invoke("db-add-supplier-payment", data),
  dbListSupplierPayments: (supplierId) => ipcRenderer.invoke("db-list-supplier-payments", supplierId),

  // قائمة أصناف النواقص الجاهزة
  dbAddShortageCatalogItem: (name) => ipcRenderer.invoke("db-add-shortage-catalog-item", name),
  dbDeleteShortageCatalogItem: (id) => ipcRenderer.invoke("db-delete-shortage-catalog-item", id),

  // النسخ الاحتياطي
  backupExport: () => ipcRenderer.invoke("backup-export"),
  backupRestore: () => ipcRenderer.invoke("backup-restore"),

  // إعدادات الفاتورة
  dbSaveReceiptSettings: (settings) => ipcRenderer.invoke("db-save-receipt-settings", settings),
  pickLogoImage: () => ipcRenderer.invoke("pick-logo-image"),

  // الترخيص
  dbGetLicenseInfo: () => ipcRenderer.invoke("db-get-license-info"),
  dbSaveLicenseValidation: (data) => ipcRenderer.invoke("db-save-license-validation", data),
  dbClearLicense: () => ipcRenderer.invoke("db-clear-license"),

  // نواقص البضاعة
  dbAddShortage: (data) => ipcRenderer.invoke("db-add-shortage", data),
  dbResolveShortage: (id) => ipcRenderer.invoke("db-resolve-shortage", id),

  // الطباعة
  printReceipt: (receipt) => ipcRenderer.invoke("print-receipt", receipt),
  printDayReport: (report) => ipcRenderer.invoke("print-day-report", report),
  printPeriodReport: (report) => ipcRenderer.invoke("print-period-report", report),
  listPrinters: () => ipcRenderer.invoke("list-printers"),

  // البريد الإلكتروني
  sendDayReport: (report) => ipcRenderer.invoke("send-day-report", report),

  // التحديثات التلقائية — listener مُسجَّل مرة واحدة فقط (لا تراكم)
  onUpdateStatus: (callback) => {
    // نزيل أي listener قديم أولاً قبل تسجيل الجديد
    ipcRenderer.removeAllListeners("update-status");
    ipcRenderer.on("update-status", (event, data) => callback(data));
  },
  downloadUpdate: () => ipcRenderer.invoke("download-update"),
  checkForUpdate: () => ipcRenderer.invoke("check-for-update"),
  getAppVersion: () => ipcRenderer.invoke("get-app-version"),

  // النظام
  restartApp: () => ipcRenderer.send("restart-app"),
  relaunchApp: () => ipcRenderer.send("relaunch-app"),
});
