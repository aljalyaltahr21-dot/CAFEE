// ===========================================================
// إعدادات المزامنة السحابية (Firebase)
// ===========================================================
// 1. روح إلى https://console.firebase.google.com وسوّي مشروع جديد (مجاني).
// 2. من داخل المشروع: Build > Firestore Database > Create database (وضع Test mode للتجربة).
// 3. من إعدادات المشروع (⚙️ Project settings) > "Your apps" >, اختر Web app (</>) وسمّه أي اسم.
// 4. انسخ القيم اللي تظهر لك وحطها مكان القيم أدناه.
//
// إذا تركت القيم كما هي (بدون تعديل)، التطبيق يشتغل محليًا فقط بدون مزامنة سحابية.
// ===========================================================

module.exports = {
  apiKey: "AIzaSyBlk_6OFKNwVoQnlZwU2halQ2xc354v5u8",
  authDomain: "cafe-pos-49008.firebaseapp.com",
  projectId: "cafe-pos-49008",
  storageBucket: "cafe-pos-49008.firebasestorage.app",
  messagingSenderId: "212226423803",
  appId: "1:212226423803:web:e7c8a1210606552ad8695e",
  measurementId: "G-61TE1V2J7Q",

  // معرّف الفرع/المقهى - إذا عندك أكثر من فرع، اعطِ كل فرع اسمًا مختلفًا هنا
  // حتى تفصل بيانات كل فرع عن الآخر. اتركه "main" إذا فرع واحد فقط.
  branchId: "main",
};
