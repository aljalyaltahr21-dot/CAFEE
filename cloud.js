// ===========================================================
// وحدة المزامنة السحابية
// تعتمد على Firestore الذي يدعم أصلًا: العمل بدون نت (يحفظ محليًا)
// ثم يزامن تلقائيًا فور عودة الاتصال، بدون أي كود إضافي معقّد.
// ===========================================================

let config = null;
try { config = require("./firebase-config"); } catch (e) { config = null; }

let db = null;
let docRef = null;
let firestoreApi = null;
let ready = false;
let unsubscribe = null;

function isConfigured() {
  return !!(config && config.apiKey && !String(config.apiKey).includes("ضع-"));
}

let currentBranchId = null;

// onData(data)      -> يستدعى كل مرة توصل بيانات جديدة (محليًا أو من السحابة)
// onStatus(status)  -> { configured, online, hasPendingWrites, error }
function initCloud(onData, onStatus, branchIdOverride) {
  if (branchIdOverride) currentBranchId = String(branchIdOverride).trim();
  if (!isConfigured()) {
    onStatus({ configured: false, online: false, hasPendingWrites: false });
    return;
  }

  // تأخير قصير لضمان أن بيئة المتصفح جاهزة تماماً (مهم في Electron)
  setTimeout(() => _doInit(onData, onStatus), 100);
}

function _doInit(onData, onStatus) {
  try {
    const { getFirebaseApp } = require("./firebase-app");

    const {
      initializeFirestore,
      persistentLocalCache,
      persistentSingleTabManager,   // ✅ أكثر استقراراً في Electron من persistentMultipleTabManager
      memoryLocalCache,             // fallback إذا فشل IndexedDB
      doc,
      onSnapshot,
      setDoc,
      enableNetwork,
      disableNetwork,
    } = require("firebase/firestore");

    firestoreApi = { enableNetwork, disableNetwork };

    const app = getFirebaseApp();

    // محاولة تفعيل الـ cache المحلي (IndexedDB) - يعمل بدون إنترنت ويزامن تلقائيًا
    // persistentSingleTabManager هو الأنسب لـ Electron (نافذة واحدة)
    let initSuccess = false;
    try {
      db = initializeFirestore(app, {
        localCache: persistentLocalCache({
          tabManager: persistentSingleTabManager({ forceOwnership: true }),
        }),
      });
      initSuccess = true;
      console.log("[Cloud] Firestore initialized with persistent cache (IndexedDB)");
    } catch (initErr) {
      console.warn("[Cloud] persistentLocalCache failed, trying memoryLocalCache:", initErr.message || initErr);
    }

    // إذا فشل persistent cache، نجرب memory cache
    if (!initSuccess) {
      try {
        db = initializeFirestore(app, {
          localCache: memoryLocalCache(),
        });
        initSuccess = true;
        console.log("[Cloud] Firestore initialized with memory cache");
      } catch (e2) {
        console.warn("[Cloud] memoryLocalCache failed, trying getFirestore:", e2.message || e2);
      }
    }

    // الخيار الأخير: getFirestore الافتراضي
    if (!initSuccess) {
      const { getFirestore } = require("firebase/firestore");
      db = getFirestore(app);
      console.log("[Cloud] Firestore initialized with default settings");
    }

    const targetBranch = currentBranchId || config.branchId || "main";
    docRef = doc(db, "cafe_branches", targetBranch);
    console.log("[Cloud] Subscribed to cafe_branches doc:", targetBranch);

    unsubscribe = onSnapshot(
      docRef,
      { includeMetadataChanges: true },
      (snap) => {
        onStatus({
          configured: true,
          online: !snap.metadata.fromCache,
          hasPendingWrites: snap.metadata.hasPendingWrites,
          error: false,
          errorMessage: null
        });
        if (snap.exists()) onData(snap.data());
      },
      (err) => {
        console.error("[Cloud] Firestore snapshot error:", err.code, err.message);
        let msg = "تعذّر الاتصال بالسحابة";
        if (err.code === 'permission-denied') {
          msg = "تم رفض الإذن (Permission Denied) - يرجى نشر القواعد العامة في Firebase Console";
        }
        onStatus({ configured: true, online: false, hasPendingWrites: false, error: true, errorMessage: msg });
      }
    );

    ready = true;
    console.log("[Cloud] Firebase sync ready ✅");
  } catch (e) {
    console.error("[Cloud] Firestore init error:", e);
    onStatus({ configured: true, online: false, hasPendingWrites: false, error: true });
  }
}

// يرسل تحديثًا للسحابة. إذا كان الجهاز بدون نت، Firestore يحفظه محليًا
// ويرسله تلقائيًا بمجرد رجوع الاتصال — بدون أي انتظار من المستخدم.
function pushCloudState(partialData) {
  if (!ready || !docRef) return;
  const { setDoc } = require("firebase/firestore");
  setDoc(docRef, { ...partialData, updatedAt: Date.now() }, { merge: true }).catch((e) => {
    console.error("[Cloud] pushCloudState error:", e.code, e.message);
  });
}

// إيقاف/تفعيل المزامنة يدويًا
function setSyncEnabled(enabled) {
  if (!ready || !firestoreApi || !db) return;
  if (enabled) firestoreApi.enableNetwork(db).catch(() => {});
  else firestoreApi.disableNetwork(db).catch(() => {});
}

// إعادة محاولة الاتصال بعد خطأ
function retryInit(onData, onStatus) {
  ready = false;
  db = null;
  docRef = null;
  firestoreApi = null;
  if (unsubscribe) { try { unsubscribe(); } catch(e) {} unsubscribe = null; }
  console.log("[Cloud] Retrying Firebase connection...");
  initCloud(onData, onStatus, currentBranchId);
}
// دالة جديدة لحفظ الفاتورة كـ Document منفصل في مجموعات المبيعات
function saveSaleToCloud(saleData) {
  if (!ready || !db) return;
  try {
    const { collection, addDoc } = require("firebase/firestore");
    
    // حفظ الفاتورة في مجموعة فرعية داخل الفرع أو في مجموعة المبيعات العامة
    const targetBranch = currentBranchId || config.branchId || "main";
    const salesRef = collection(db, "cafe_branches", targetBranch, "sales");
    
    addDoc(salesRef, {
      ...saleData,
      createdAt: Date.now()
    }).then(() => {
      console.log("[Cloud] Sale recorded successfully in Firestore ✅");
    }).catch((e) => {
      console.error("[Cloud] Error saving sale to Firestore:", e.code, e.message);
    });
  } catch (e) {
    console.error("[Cloud] Exception in saveSaleToCloud:", e);
  }
}

module.exports = { 
  initCloud, 
  retryInit, 
  pushCloudState, 
  saveSaleToCloud, // <-- إضافة الدالة هنا
  setSyncEnabled, 
  isConfigured 
};
