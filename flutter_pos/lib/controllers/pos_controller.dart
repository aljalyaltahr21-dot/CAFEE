import 'package:flutter/material.dart';
import '../models/product.dart';

class PosController extends ChangeNotifier {
  final List<Product> _catalog = [
    const Product(
      id: 1,
      name: "كراميل فرابتشينو",
      nameEn: "Caramel Frappuccino",
      desc: "سيروب كراميل ذهبي مع اسبريسو وحليب وكريمة مخفوقة غنية",
      price: 3.95,
      category: "قهوة",
      imageUrl: "https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=400&auto=format&fit=crop&q=80",
      moodLabel: "المزاج",
      moods: ["🔥 ساخن", "❄️ بارد"],
      sizeLabel: "الحجم",
      sizes: ["S", "M", "L"],
      sugarLabel: "السكر",
      sugarLevels: ["30%", "50%", "70%"],
      extraLabel: "الثلج / الإضافة",
      extras: ["خفيف", "وسط", "إكسترا"],
    ),
    const Product(
      id: 2,
      name: "شوكولاتة فرابتشينو",
      nameEn: "Chocolate Frappuccino",
      desc: "شوكولاتة داكنة غنية مع قهوة مثلجة وكريمة شوكولا",
      price: 4.50,
      category: "قهوة",
      imageUrl: "https://images.unsplash.com/photo-1541167760496-1628856ab772?w=400&auto=format&fit=crop&q=80",
      moodLabel: "المزاج",
      moods: ["🔥 ساخن", "❄️ بارد"],
      sizeLabel: "الحجم",
      sizes: ["S", "M", "L"],
      sugarLabel: "السكر",
      sugarLevels: ["30%", "50%", "70%"],
      extraLabel: "الثلج / الإضافة",
      extras: ["خفيف", "وسط", "إكسترا"],
    ),
    const Product(
      id: 3,
      name: "نعناع ماكياتو",
      nameEn: "Peppermint Macchiato",
      desc: "نعناع منعش مع قهوة اسبريسو وكريمة حليب مخفوقة",
      price: 5.30,
      category: "قهوة",
      imageUrl: "https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=400&auto=format&fit=crop&q=80",
      moodLabel: "المزاج",
      moods: ["🔥 ساخن", "❄️ بارد"],
      sizeLabel: "الحجم",
      sizes: ["S", "M", "L"],
      sugarLabel: "السكر",
      sugarLevels: ["30%", "50%", "70%"],
      extraLabel: "الثلج / الإضافة",
      extras: ["خفيف", "وسط", "إكسترا"],
    ),
    const Product(
      id: 4,
      name: "كافيه لاتيه",
      nameEn: "Coffee Latte",
      desc: "خلطة اسبريسو خاصة مع كريمة الشوكولاتة وحليب طازج",
      price: 4.70,
      category: "قهوة",
      imageUrl: "https://images.unsplash.com/photo-1534778101976-62847782c213?w=400&auto=format&fit=crop&q=80",
      moodLabel: "المزاج",
      moods: ["🔥 ساخن", "❄️ بارد"],
      sizeLabel: "الحجم",
      sizes: ["S", "M", "L"],
      sugarLabel: "السكر",
      sugarLevels: ["30%", "50%", "70%"],
      extraLabel: "الثلج / الإضافة",
      extras: ["خفيف", "وسط", "إكسترا"],
    ),
    const Product(
      id: 5,
      name: "اسبريسو كلاسيك",
      nameEn: "Classic Espresso",
      desc: "جرعة اسبريسو نقية وموزونة برغوة ذهبية دافئة",
      price: 2.50,
      category: "قهوة",
      imageUrl: "https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?w=400&auto=format&fit=crop&q=80",
      moodLabel: "النوع",
      moods: ["دبل 🔥", "سينجل ☕"],
      sizeLabel: "الحجم",
      sizes: ["S", "M"],
      sugarLabel: "السكر",
      sugarLevels: ["بدون", "وسط", "زيادة"],
      extraLabel: "الرغوة",
      extras: ["ماء بارد", "رغوة غنية", "سادة"],
    ),
    const Product(
      id: 6,
      name: "كابتشينو إيطالي",
      nameEn: "Italian Cappuccino",
      desc: "توازن مثالي بين الإسبريسو ورغوة الحليب الناعمة",
      price: 3.50,
      category: "قهوة",
      imageUrl: "https://images.unsplash.com/photo-1577968897966-3d4325b36b61?w=400&auto=format&fit=crop&q=80",
      moodLabel: "المزاج",
      moods: ["🔥 ساخن", "❄️ بارد"],
      sizeLabel: "الحجم",
      sizes: ["S", "M", "L"],
      sugarLabel: "السكر",
      sugarLevels: ["بدون", "وسط", "زيادة"],
      extraLabel: "الرغوة",
      extras: ["خفيفة", "وسط", "كثيفة"],
    ),
    const Product(
      id: 7,
      name: "عصير برتقال طبيعي",
      nameEn: "Fresh Orange Juice",
      desc: "عصير برتقال طازج 100% معصور فورياً بدون إضافات",
      price: 3.80,
      category: "عصائر",
      imageUrl: "https://images.unsplash.com/photo-1613478223719-2ab802602423?w=400&auto=format&fit=crop&q=80",
      moodLabel: "النوع",
      moods: ["طازج 🍊", "سموذي 🍹"],
      sizeLabel: "الحجم",
      sizes: ["M", "L"],
      sugarLabel: "السكر",
      sugarLevels: ["طبيعي", "وسط", "زيادة"],
      extraLabel: "الثلج",
      extras: ["بدون", "وسط", "مثلج"],
    ),
    const Product(
      id: 8,
      name: "كولد برو منعش",
      nameEn: "Cold Brew Special",
      desc: "قهوة مقطرة ببطء على البارد لمدة 18 ساعة نكهة سلسة",
      price: 4.90,
      category: "قهوة",
      imageUrl: "https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&auto=format&fit=crop&q=80",
      moodLabel: "المزاج",
      moods: ["❄️ بارد", "🍋 ليمون"],
      sizeLabel: "الحجم",
      sizes: ["M", "L"],
      sugarLabel: "السكر",
      sugarLevels: ["بدون", "وسط", "سيروب"],
      extraLabel: "الثلج",
      extras: ["خفيف", "وسط", "مكعبات"],
    ),
    const Product(
      id: 9,
      name: "سبانش لاتيه بالحليب",
      nameEn: "Iced Spanish Latte",
      desc: "مزيج الحليب المكثف مع الإسبريسو والحليب الطازج",
      price: 5.00,
      category: "حليب",
      imageUrl: "https://images.unsplash.com/photo-1559496417-e7f25cb247f3?w=400&auto=format&fit=crop&q=80",
      moodLabel: "المزاج",
      moods: ["❄️ بارد", "🔥 ساخن"],
      sizeLabel: "الحجم",
      sizes: ["S", "M", "L"],
      sugarLabel: "الحلاوة",
      sugarLevels: ["خفيف", "وسط", "مكثف"],
      extraLabel: "نوع الحليب",
      extras: ["كامل الدسم", "لوز", "شوفان"],
    ),
    const Product(
      id: 10,
      name: "ميلك شيك كراميل",
      nameEn: "Caramel Milkshake",
      desc: "ميلك شيك سميك مع صوص الكراميل ورغوة الكريمة المخفوقة",
      price: 5.20,
      category: "حليب",
      imageUrl: "https://images.unsplash.com/photo-1579954115545-a95591f28bfc?w=400&auto=format&fit=crop&q=80",
      moodLabel: "التقديم",
      moods: ["❄️ مثلج", "🍦 كريمة"],
      sizeLabel: "الحجم",
      sizes: ["M", "L"],
      sugarLabel: "السكر",
      sugarLevels: ["وسط", "حلو", "إكسترا"],
      extraLabel: "الصوص",
      extras: ["كراميل", "شوكولا", "لوتس"],
    ),
    const Product(
      id: 11,
      name: "ساندويتش كلوب مشوي",
      nameEn: "Grilled Club Sandwich",
      desc: "خبز توست مقرمش مع صدور الديك الرومي والجبنة الذائبة",
      price: 6.50,
      category: "وجبات",
      imageUrl: "https://images.unsplash.com/photo-1528735602780-2552fd46c7af?w=400&auto=format&fit=crop&q=80",
      moodLabel: "التحضير",
      moods: ["🔥 ساخن", "🥪 توست"],
      sizeLabel: "الوجبة",
      sizes: ["عادي", "كومبو 🍟"],
      sugarLabel: "الصلصة",
      sugarLevels: ["مايونيز", "خردل", "كاتشب"],
      extraLabel: "الجبنة",
      extras: ["شيدر", "موزاريلا", "بدون"],
    ),
    const Product(
      id: 12,
      name: "كرواسون فرنسي بالزبدة",
      nameEn: "Butter Croissant",
      desc: "كرواسون فرنسي طازج مقرمش بالزبدة الطبيعية الفاخرة",
      price: 3.00,
      category: "سناكس",
      imageUrl: "https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=400&auto=format&fit=crop&q=80",
      moodLabel: "التقديم",
      moods: ["🔥 مسخن", "🥐 طازج"],
      sizeLabel: "الكمية",
      sizes: ["قطعة", "قطعتين"],
      sugarLabel: "الحشوة",
      sugarLevels: ["سادة", "جبنة", "شوكولا"],
      extraLabel: "الإضافة",
      extras: ["سادة", "عسل", "مربى"],
    ),
    const Product(
      id: 13,
      name: "تشيز كيك التوت",
      nameEn: "Berry Cheesecake",
      desc: "قطعة تشيز كيك نيويورك مخبوزة مع صوص التوت البري",
      price: 5.50,
      category: "حلويات",
      imageUrl: "https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=400&auto=format&fit=crop&q=80",
      moodLabel: "التقديم",
      moods: ["❄️ بارد", "🍰 مخبوز"],
      sizeLabel: "القطعة",
      sizes: ["عادي", "دبل"],
      sugarLabel: "الصوص",
      sugarLevels: ["توت بري", "فراولة", "لوتس"],
      extraLabel: "الإضافة",
      extras: ["كريمة", "صوص إضافي", "سادة"],
    ),
    const Product(
      id: 14,
      name: "براونيز شوكولاتة فدج",
      nameEn: "Chocolate Fudge Brownie",
      desc: "كيكة براونيز دافئة غنية بالشوكولاتة البلجيكية الفاخرة",
      price: 4.80,
      category: "حلويات",
      imageUrl: "https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=400&auto=format&fit=crop&q=80",
      moodLabel: "التقديم",
      moods: ["🔥 دافئ", "❄️ مع آيسكريم"],
      sizeLabel: "الحجم",
      sizes: ["قطعة", "حجم كبير"],
      sugarLabel: "الصوص",
      sugarLevels: ["نوتيلا", "كراميل", "مكسرات"],
      extraLabel: "الإضافة",
      extras: ["سادة", "فانيليا", "إكسترا"],
    ),
  ];

  String _selectedCategory = 'الكل';
  String _searchQuery = '';
  final Map<int, Map<String, String>> _selections = {};
  final List<CartItem> _cart = [];
  String _paymentMethod = 'card';

  PosController() {
    _initSelections();
  }

  void _initSelections() {
    for (final p in _catalog) {
      _selections[p.id] = {
        'mood': p.moods.isNotEmpty ? p.moods.first : '',
        'size': p.sizes.length > 1 ? p.sizes[1] : (p.sizes.isNotEmpty ? p.sizes.first : 'M'),
        'sugar': p.sugarLevels.length > 1 ? p.sugarLevels[1] : (p.sugarLevels.isNotEmpty ? p.sugarLevels.first : '50%'),
        'extra': p.extras.length > 1 ? p.extras[1] : (p.extras.isNotEmpty ? p.extras.first : 'وسط'),
      };
    }
  }

  List<String> get categories => const [
    'الكل',
    'قهوة',
    'عصائر',
    'حليب',
    'سناكس',
    'وجبات',
    'حلويات',
  ];

  String getCategoryIcon(String cat) {
    switch (cat) {
      case 'قهوة': return '☕';
      case 'عصائر': return '🍹';
      case 'حليب': return '🥛';
      case 'سناكس': return '🥞';
      case 'وجبات': return '🥪';
      case 'حلويات': return '🍰';
      default: return '🍻';
    }
  }

  String get selectedCategory => _selectedCategory;
  String get searchQuery => _searchQuery;
  List<CartItem> get cart => List.unmodifiable(_cart);
  String get paymentMethod => _paymentMethod;

  void selectCategory(String cat) {
    _selectedCategory = cat;
    notifyListeners();
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    notifyListeners();
  }

  void setPaymentMethod(String method) {
    _paymentMethod = method;
    notifyListeners();
  }

  Map<String, String> getProductSelection(int productId) {
    return _selections[productId] ?? {
      'mood': '',
      'size': 'M',
      'sugar': '50%',
      'extra': 'وسط',
    };
  }

  void updateProductOption(int productId, String key, String value) {
    if (!_selections.containsKey(productId)) {
      _selections[productId] = {};
    }
    _selections[productId]![key] = value;
    notifyListeners();
  }

  List<Product> get filteredProducts {
    return _catalog.where((p) {
      final matchesCategory = _selectedCategory == 'الكل' || p.category == _selectedCategory;
      final query = _searchQuery.trim().toLowerCase();
      final matchesQuery = query.isEmpty ||
          p.name.toLowerCase().contains(query) ||
          p.nameEn.toLowerCase().contains(query) ||
          p.desc.toLowerCase().contains(query);
      return matchesCategory && matchesQuery;
    }).toList();
  }

  void addToCart(Product product) {
    final sel = getProductSelection(product.id);
    double extraPrice = 0.0;
    if (sel['size'] == 'L' || sel['size'] == 'كومبو 🍟' || sel['size'] == 'دبل' || sel['size'] == 'قطعتين') {
      extraPrice = 0.50;
    } else if (sel['size'] == 'S') {
      extraPrice = -0.30;
    }
    final unitPrice = (product.price + extraPrice).clamp(1.0, 999.0);

    final itemKey = '${product.id}_${sel['mood']}_${sel['size']}_${sel['sugar']}_${sel['extra']}';
    final existingIndex = _cart.indexWhere((item) => item.key == itemKey);

    if (existingIndex >= 0) {
      _cart[existingIndex].quantity += 1;
    } else {
      _cart.add(
        CartItem(
          key: itemKey,
          product: product,
          selectedMood: sel['mood'] ?? '',
          selectedSize: sel['size'] ?? '',
          selectedSugar: sel['sugar'] ?? '',
          selectedExtra: sel['extra'] ?? '',
          unitPrice: unitPrice,
          quantity: 1,
        ),
      );
    }
    notifyListeners();
  }

  void updateQuantity(int index, int delta) {
    if (index >= 0 && index < _cart.length) {
      _cart[index].quantity += delta;
      if (_cart[index].quantity <= 0) {
        _cart.removeAt(index);
      }
      notifyListeners();
    }
  }

  void removeItem(int index) {
    if (index >= 0 && index < _cart.length) {
      _cart.removeAt(index);
      notifyListeners();
    }
  }

  void setItemNote(int index, String note) {
    if (index >= 0 && index < _cart.length) {
      _cart[index].note = note;
      notifyListeners();
    }
  }

  void clearCart() {
    _cart.clear();
    notifyListeners();
  }

  double get subtotal {
    return _cart.fold(0.0, (sum, item) => sum + item.totalPrice);
  }

  double get tax => subtotal * 0.10; // 10%
  double get total => subtotal + tax;
  int get totalItemsCount => _cart.fold(0, (sum, item) => sum + item.quantity);
}
