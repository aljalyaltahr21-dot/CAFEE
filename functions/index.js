const { onCall, HttpsError } = require("firebase-functions/v2/https");
const { logger } = require("firebase-functions");
const admin = require("firebase-admin");
const crypto = require("crypto");

admin.initializeApp();
const firestore = admin.firestore();

function deviceHash(deviceId) {
  return crypto.createHash("sha256").update(deviceId).digest("hex");
}

function asMillis(value) {
  if (!value) return null;
  if (typeof value === "number") return value;
  if (typeof value.toMillis === "function") return value.toMillis();
  return null;
}

exports.validateLicense = onCall({ timeoutSeconds: 20, region: "us-central1" }, async (request) => {
  const key = String(request.data?.key || "").trim();
  const deviceId = String(request.data?.deviceId || "").trim();
  if (key.length < 12 || key.length > 128 || !deviceId) {
    throw new HttpsError("invalid-argument", "Invalid license request");
  }

  const ref = firestore.collection("licenses").doc(key);
  const result = await firestore.runTransaction(async (transaction) => {
    const snapshot = await transaction.get(ref);
    if (!snapshot.exists) return { valid: false, reason: "not-found" };

    const license = snapshot.data();
    if (license.active === false) return { valid: false, reason: "revoked" };
    const expiresAt = asMillis(license.expiresAt);
    if (expiresAt && Date.now() > expiresAt) return { valid: false, reason: "expired" };

    const hashedDevice = deviceHash(deviceId);
    if (license.deviceHash && license.deviceHash !== hashedDevice) return { valid: false, reason: "device-mismatch" };
    if (license.deviceId && license.deviceId !== deviceId) return { valid: false, reason: "device-mismatch" };

    transaction.set(ref, {
      deviceHash: hashedDevice,
      activatedAt: license.activatedAt || admin.firestore.FieldValue.serverTimestamp(),
      lastValidatedAt: admin.firestore.FieldValue.serverTimestamp(),
      deviceId: admin.firestore.FieldValue.delete(),
    }, { merge: true });

    return { valid: true, customerName: String(license.customerName || ""), expiresAt: expiresAt || null };
  });

  logger.info("License validation", { valid: result.valid, reason: result.reason || "valid" });
  return result;
});
