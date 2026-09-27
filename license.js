// التحقق المباشر من التراخيص والاشتراكات عبر Firestore بدون الحاجة لـ Cloud Functions
const { getFirebaseApp } = require("./firebase-app");

let config = null;
try { config = require("./firebase-config"); } catch (_) { config = null; }

function isFirebaseConfigured() {
  return !!(config && config.apiKey && !String(config.apiKey).includes("ضع-"));
}

async function hashDevice(deviceId) {
  if (!deviceId) return "";
  try {
    if (typeof crypto !== "undefined" && crypto.subtle) {
      const msgBuffer = new TextEncoder().encode(deviceId);
      const hashBuffer = await crypto.subtle.digest("SHA-256", msgBuffer);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      return hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
    }
  } catch (e) {
    console.warn("Device hashing error:", e);
  }
  return String(deviceId);
}

function asMillis(value) {
  if (!value) return null;
  if (typeof value === "number") return value;
  if (typeof value.toMillis === "function") return value.toMillis();
  if (value.seconds) return value.seconds * 1000;
  if (value instanceof Date) return value.getTime();
  return null;
}

async function checkLicense(key, deviceId, appVersion = "1.0.3") {
  if (!isFirebaseConfigured()) return { valid: false, reason: "licensing-not-configured" };
  const cleanKey = String(key || "").trim();
  if (!cleanKey) return { valid: false, reason: "not-found" };

  try {
    const { getFirestore, doc, getDoc, updateDoc, serverTimestamp } = require("firebase/firestore");
    const app = getFirebaseApp();
    const db = getFirestore(app);

    const docRef = doc(db, "licenses", cleanKey);
    const snap = await getDoc(docRef);

    if (!snap.exists()) {
      return { valid: false, reason: "not-found" };
    }

    const license = snap.data() || {};

    if (license.active === false) {
      return { valid: false, reason: "revoked" };
    }

    const expiresAt = asMillis(license.expiresAt);
    if (expiresAt && Date.now() > expiresAt) {
      return { valid: false, reason: "expired" };
    }

    const hashedDevice = await hashDevice(deviceId);
    if (license.deviceHash && license.deviceHash !== hashedDevice) {
      return { valid: false, reason: "device-mismatch" };
    }
    if (license.deviceId && license.deviceId !== deviceId) {
      return { valid: false, reason: "device-mismatch" };
    }

    // ربط الجهاز وتحديث تاريخ آخر تحقق ورقم الإصدار وحالة الاتصال
    const updates = {
      deviceHash: hashedDevice,
      appVersion: String(appVersion || "1.0.2"),
      lastValidatedAt: serverTimestamp(),
      lastActiveAt: serverTimestamp(),
      online: true,
    };
    if (!license.activatedAt) {
      updates.activatedAt = serverTimestamp();
    }

    // تحديث غير حاجب: إذا فشل التحديث، يستمر التطبيق كترخيص صالح
    updateDoc(docRef, updates).catch((err) => {
      console.warn("Could not record device binding on license:", err.message || err);
    });

    return {
      valid: true,
      customerName: String(license.customerName || ""),
      expiresAt: expiresAt || null,
    };
  } catch (error) {
    console.error("License validation failed:", error);
    if (error && error.code === "permission-denied") {
      return { valid: false, reason: "permission-denied" };
    }
    return { valid: false, reason: "network-error" };
  }
}

// نبض اتصال دوري لتسجيل حالة المقهى الأونلاين وآخر نشاط له
async function pingHeartbeat(key, appVersion = "1.0.3") {
  if (!isFirebaseConfigured()) return;
  const cleanKey = String(key || "").trim();
  if (!cleanKey) return;
  try {
    const { getFirestore, doc, updateDoc, serverTimestamp } = require("firebase/firestore");
    const app = getFirebaseApp();
    const db = getFirestore(app);
    const docRef = doc(db, "licenses", cleanKey);
    await updateDoc(docRef, {
      appVersion: String(appVersion || "1.0.2"),
      lastActiveAt: serverTimestamp(),
      online: true,
    });
  } catch (_) {
    // نبض الاتصال يتم في الخلفية بدون حجب
  }
}

module.exports = { checkLicense, pingHeartbeat, isFirebaseConfigured };
