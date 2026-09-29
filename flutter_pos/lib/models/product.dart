class ProductOption {
  final String label;
  final List<String> values;
  final String defaultValue;

  const ProductOption({
    required this.label,
    required this.values,
    required this.defaultValue,
  });
}

class Product {
  final int id;
  final String name;
  final String nameEn;
  final String desc;
  final double price;
  final String category;
  final String imageUrl;
  final String moodLabel;
  final List<String> moods;
  final String sizeLabel;
  final List<String> sizes;
  final String sugarLabel;
  final List<String> sugarLevels;
  final String extraLabel;
  final List<String> extras;

  const Product({
    required this.id,
    required this.name,
    required this.nameEn,
    required this.desc,
    required this.price,
    required this.category,
    required this.imageUrl,
    this.moodLabel = 'المزاج',
    this.moods = const ['🔥 ساخن', '❄️ بارد'],
    this.sizeLabel = 'الحجم',
    this.sizes = const ['S', 'M', 'L'],
    this.sugarLabel = 'السكر',
    this.sugarLevels = const ['30%', '50%', '70%'],
    this.extraLabel = 'الثلج / الإضافة',
    this.extras = const ['خفيف', 'وسط', 'إكسترا'],
  });
}

class CartItem {
  final String key;
  final Product product;
  final String selectedMood;
  final String selectedSize;
  final String selectedSugar;
  final String selectedExtra;
  final double unitPrice;
  int quantity;
  String note;

  CartItem({
    required this.key,
    required this.product,
    required this.selectedMood,
    required this.selectedSize,
    required this.selectedSugar,
    required this.selectedExtra,
    required this.unitPrice,
    this.quantity = 1,
    this.note = '',
  });

  double get totalPrice => unitPrice * quantity;
}
