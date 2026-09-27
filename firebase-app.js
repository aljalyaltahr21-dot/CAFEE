let app = null;

function getFirebaseApp() {
  if (app) return app;
  const config = require("./firebase-config");
  const { initializeApp, getApps, getApp } = require("firebase/app");
  if (getApps().length) {
    app = getApp();
    console.log("[Firebase] Using existing app:", app.name);
  } else {
    // نحذف branchId لأنه ليس ضمن إعدادات Firebase الرسمية
    const { branchId, ...firebaseConfig } = config;
    app = initializeApp(firebaseConfig);
    console.log("[Firebase] App initialized:", app.options.projectId);
  }
  return app;
}

module.exports = { getFirebaseApp };
