const config = require("./firebase-config");
const { getFirebaseApp } = require("./firebase-app");

function isAuthConfigured() {
  return !!(config && config.apiKey && !String(config.apiKey).includes("ضع-"));
}

async function login(email, password) {
  const { getAuth, signInWithEmailAndPassword } = require("firebase/auth");
  const auth = getAuth(getFirebaseApp());
  const cred = await signInWithEmailAndPassword(auth, email, password);
  return cred.user;
}

async function logout() {
  const { getAuth, signOut } = require("firebase/auth");
  await signOut(getAuth(getFirebaseApp()));
}

async function fetchRole(uid) {
  const { getFirestore, doc, getDoc } = require("firebase/firestore");
  const db = getFirestore(getFirebaseApp());
  const snap = await getDoc(doc(db, "staff", uid));
  return snap.exists() ? snap.data() : null; // { role, name, email }
}

// ينشئ حساب موظف جديد بدون تسجيل خروج المدير الحالي (يستخدم تطبيق Firebase فرعي مؤقت)
async function createStaffAccount(email, password, role, name) {
  const { initializeApp, deleteApp } = require("firebase/app");
  const { getAuth, createUserWithEmailAndPassword, signOut } = require("firebase/auth");
  const { getFirestore, doc, setDoc } = require("firebase/firestore");

  const secondary = initializeApp(config, "secondary-" + Date.now());
  const secAuth = getAuth(secondary);
  const cred = await createUserWithEmailAndPassword(secAuth, email, password);
  const db = getFirestore(secondary);
  await setDoc(doc(db, "staff", cred.user.uid), { email, role, name, createdAt: Date.now() });
  await signOut(secAuth);
  await deleteApp(secondary);
}

async function listStaff() {
  const { getFirestore, collection, getDocs } = require("firebase/firestore");
  const db = getFirestore(getFirebaseApp());
  const snap = await getDocs(collection(db, "staff"));
  return snap.docs.map(d => ({ id: d.id, ...d.data() }));
}

module.exports = { isAuthConfigured, login, logout, fetchRole, createStaffAccount, listStaff };
