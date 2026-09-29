import 'package:flutter/material.dart';
import '../models/product.dart';
import '../controllers/pos_controller.dart';
import '../theme/app_theme.dart';

class ProductCard extends StatelessWidget {
  final Product product;
  final PosController controller;

  const ProductCard({
    super.key,
    required this.product,
    required this.controller,
  });

  @override
  Widget build(BuildContext context) {
    final sel = controller.getProductSelection(product.id);

    // Compute dynamic price based on size
    double extra = 0.0;
    final currentSize = sel['size'] ?? '';
    if (currentSize == 'L' || currentSize == 'كومبو 🍟' || currentSize == 'دبل' || currentSize == 'قطعتين') {
      extra = 0.50;
    } else if (currentSize == 'S') {
      extra = -0.30;
    }
    final currentPrice = (product.price + extra).clamp(1.0, 999.0);

    return Container(
      decoration: BoxDecoration(
        color: AppTheme.cardBg,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppTheme.borderLight, width: 1.5),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Top: Image + Info
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: Container(
                  width: 76,
                  height: 76,
                  color: AppTheme.borderLight,
                  child: Image.network(
                    product.imageUrl,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) => const Center(
                      child: Text('☕', style: TextStyle(fontSize: 32)),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      product.name,
                      style: const TextStyle(
                        fontSize: 14.5,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.navy,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      product.desc,
                      style: const TextStyle(
                        fontSize: 11,
                        color: AppTheme.slate,
                        height: 1.25,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    Text(
                      '${currentPrice.toStringAsFixed(2)} د.ل',
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w900,
                        color: AppTheme.primary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 10),

          // Symmetrical 4-Filter Options Grid (2x2)
          Container(
            decoration: BoxDecoration(
              color: const Color(0xFFFDFBF9),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppTheme.borderLight, width: 1.2),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            child: Column(
              children: [
                // Row 1: Mood & Size
                Row(
                  children: [
                    Expanded(
                      child: _buildOptionGroup(
                        label: product.moodLabel,
                        options: product.moods,
                        selectedValue: sel['mood'] ?? '',
                        onSelected: (val) => controller.updateProductOption(product.id, 'mood', val),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildOptionGroup(
                        label: product.sizeLabel,
                        options: product.sizes,
                        selectedValue: sel['size'] ?? '',
                        onSelected: (val) => controller.updateProductOption(product.id, 'size', val),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                // Row 2: Sugar & Extra
                Row(
                  children: [
                    Expanded(
                      child: _buildOptionGroup(
                        label: product.sugarLabel,
                        options: product.sugarLevels,
                        selectedValue: sel['sugar'] ?? '',
                        onSelected: (val) => controller.updateProductOption(product.id, 'sugar', val),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildOptionGroup(
                        label: product.extraLabel,
                        options: product.extras,
                        selectedValue: sel['extra'] ?? '',
                        onSelected: (val) => controller.updateProductOption(product.id, 'extra', val),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 10),

          // Add to Bill Button
          SizedBox(
            height: 42,
            child: ElevatedButton(
              onPressed: () {
                controller.addToCart(product);
                ScaffoldMessenger.of(context).hideCurrentSnackBar();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('تمت إضافة "${product.name}" إلى الفاتورة ✓'),
                    duration: const Duration(milliseconds: 1200),
                    backgroundColor: AppTheme.primary,
                    behavior: SnackBarBehavior.floating,
                    width: 320,
                  ),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primary,
                foregroundColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                padding: const EdgeInsets.symmetric(horizontal: 14),
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    'أضف للفاتورة',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  SizedBox(width: 6),
                  Icon(Icons.add_shopping_cart_rounded, size: 17),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildOptionGroup({
    required String label,
    required List<String> options,
    required String selectedValue,
    required ValueChanged<String> onSelected,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 10.5,
            fontWeight: FontWeight.w700,
            color: AppTheme.slate,
          ),
        ),
        const SizedBox(height: 4),
        Row(
          children: options.map((opt) {
            final isSelected = selectedValue == opt;
            return Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 1.5),
                child: InkWell(
                  onTap: () => onSelected(opt),
                  borderRadius: BorderRadius.circular(8),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 150),
                    height: 26,
                    decoration: BoxDecoration(
                      color: isSelected ? AppTheme.primary : AppTheme.cardBg,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: isSelected ? AppTheme.primary : AppTheme.border,
                        width: 1.0,
                      ),
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      opt,
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                        color: isSelected ? Colors.white : AppTheme.slate,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}
