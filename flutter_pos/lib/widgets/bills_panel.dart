import 'package:flutter/material.dart';
import '../controllers/pos_controller.dart';
import '../theme/app_theme.dart';
import 'receipt_dialog.dart';

class BillsPanel extends StatelessWidget {
  final PosController controller;

  const BillsPanel({super.key, required this.controller});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 420,
      decoration: const BoxDecoration(
        color: AppTheme.cardBg,
        border: Border(
          right: BorderSide(color: AppTheme.borderLight, width: 1.5),
        ),
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Cashier Bar
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: AppTheme.primaryLight,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppTheme.primaryBorder, width: 1.5),
                    ),
                    alignment: Alignment.center,
                    child: const Text('🧑‍🍳', style: TextStyle(fontSize: 22)),
                  ),
                  const SizedBox(width: 10),
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'أنا الكاشير ☕',
                        style: TextStyle(fontSize: 10.5, color: AppTheme.slate),
                      ),
                      Text(
                        'طاهر الجالي',
                        style: TextStyle(
                          fontSize: 13.5,
                          fontWeight: FontWeight.w800,
                          color: AppTheme.navy,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              IconButton(
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('لا توجد تنبيهات جديدة'),
                      duration: Duration(seconds: 1),
                    ),
                  );
                },
                icon: const Icon(Icons.notifications_none_rounded, color: AppTheme.slate),
                style: IconButton.styleFrom(
                  backgroundColor: AppTheme.bg,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                    side: const BorderSide(color: AppTheme.border),
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 16),

          // Title & Clear Action
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Text(
                    'الفاتورة',
                    style: TextStyle(
                      fontSize: 19,
                      fontWeight: FontWeight.w900,
                      color: AppTheme.navy,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryLight,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      '${controller.totalItemsCount} عناصر',
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.primary,
                      ),
                    ),
                  ),
                ],
              ),
              if (controller.cart.isNotEmpty)
                TextButton.icon(
                  onPressed: () => controller.clearCart(),
                  icon: const Icon(Icons.delete_outline_rounded, size: 16, color: AppTheme.red),
                  label: const Text(
                    'مسح',
                    style: TextStyle(color: AppTheme.red, fontSize: 12),
                  ),
                ),
            ],
          ),

          const SizedBox(height: 12),

          // Order Items List
          Expanded(
            child: controller.cart.isEmpty
                ? const Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text('☕', style: TextStyle(fontSize: 36)),
                        SizedBox(height: 8),
                        Text(
                          'لم تتم إضافة أي طلبات للفاتورة بعد',
                          style: TextStyle(
                            fontSize: 13,
                            color: AppTheme.slateLight,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  )
                : ListView.separated(
                    itemCount: controller.cart.length,
                    separatorBuilder: (context, index) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      final item = controller.cart[index];
                      final mods = [
                        item.selectedMood,
                        'حجم ${item.selectedSize}',
                        'سكر ${item.selectedSugar}',
                        'إضافة ${item.selectedExtra}',
                      ].where((m) => m.isNotEmpty).join(' | ');

                      return Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFAFAFA),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: AppTheme.borderLight),
                        ),
                        child: Row(
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(10),
                              child: Container(
                                width: 48,
                                height: 48,
                                color: AppTheme.borderLight,
                                child: Image.network(
                                  item.product.imageUrl,
                                  fit: BoxFit.cover,
                                  errorBuilder: (context, error, stackTrace) => const Center(
                                    child: Text('☕', style: TextStyle(fontSize: 20)),
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item.product.name,
                                    style: const TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w800,
                                      color: AppTheme.navy,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    mods,
                                    style: const TextStyle(
                                      fontSize: 10.5,
                                      color: AppTheme.slate,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  if (item.note.isNotEmpty) ...[
                                    const SizedBox(height: 2),
                                    Text(
                                      '↳ ${item.note}',
                                      style: const TextStyle(
                                        fontSize: 10,
                                        color: AppTheme.gold,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(
                                  '${item.totalPrice.toStringAsFixed(2)} د.ل',
                                  style: const TextStyle(
                                    fontSize: 13.5,
                                    fontWeight: FontWeight.w900,
                                    color: AppTheme.navy,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Container(
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF5F0EE),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      _buildQtyBtn('-', () => controller.updateQuantity(index, -1)),
                                      Padding(
                                        padding: const EdgeInsets.symmetric(horizontal: 6),
                                        child: Text(
                                          '${item.quantity}',
                                          style: const TextStyle(
                                            fontSize: 12,
                                            fontWeight: FontWeight.w900,
                                            color: AppTheme.primary,
                                          ),
                                        ),
                                      ),
                                      _buildQtyBtn('+', () => controller.updateQuantity(index, 1)),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      );
                    },
                  ),
          ),

          const SizedBox(height: 12),

          // Financial Summary
          Container(
            padding: const EdgeInsets.symmetric(vertical: 10),
            decoration: const BoxDecoration(
              border: Border(
                top: BorderSide(color: AppTheme.border, style: BorderStyle.solid),
              ),
            ),
            child: Column(
              children: [
                _buildSummaryRow('المجموع الفرعي:', '${controller.subtotal.toStringAsFixed(3)} د.ل'),
                const SizedBox(height: 4),
                _buildSummaryRow('الضريبة / الخدمة (10%):', '${controller.tax.toStringAsFixed(3)} د.ل'),
                const Divider(color: AppTheme.borderLight, height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'المجموع الكلي:',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w900,
                        color: AppTheme.navy,
                      ),
                    ),
                    Text(
                      '${controller.total.toStringAsFixed(3)} د.ل',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w900,
                        color: AppTheme.primary,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Payment Methods
          const Text(
            'طريقة الدفع',
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w700,
              color: AppTheme.slate,
            ),
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              _buildPayBtn('نقدي 💵', 'cash'),
              const SizedBox(width: 8),
              _buildPayBtn('بطاقة 💳', 'card'),
              const SizedBox(width: 8),
              _buildPayBtn('سداد 📱', 'wallet'),
            ],
          ),

          const SizedBox(height: 12),

          // Checkout & Print Button
          SizedBox(
            height: 48,
            child: ElevatedButton(
              onPressed: controller.cart.isEmpty
                  ? null
                  : () {
                      final orderNum = '#${1000 + DateTime.now().second * 97 % 9000}';
                      showDialog(
                        context: context,
                        builder: (_) => ReceiptDialog(
                          controller: controller,
                          orderNumber: orderNum,
                        ),
                      );
                    },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primary,
                foregroundColor: Colors.white,
                disabledBackgroundColor: AppTheme.border,
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.print_rounded, size: 20),
                  SizedBox(width: 8),
                  Text(
                    'طباعة وتأكيد الفاتورة',
                    style: TextStyle(
                      fontSize: 14.5,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 12, color: AppTheme.slate)),
        Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
      ],
    );
  }

  Widget _buildQtyBtn(String label, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(6),
      child: Container(
        width: 26,
        height: 26,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(6),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 4,
            ),
          ],
        ),
        child: Text(
          label,
          style: const TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w900,
            color: AppTheme.primary,
          ),
        ),
      ),
    );
  }

  Widget _buildPayBtn(String title, String method) {
    final isActive = controller.paymentMethod == method;
    return Expanded(
      child: InkWell(
        onTap: () => controller.setPaymentMethod(method),
        borderRadius: BorderRadius.circular(10),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          height: 42,
          decoration: BoxDecoration(
            color: isActive ? AppTheme.primaryLight : AppTheme.cardBg,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: isActive ? AppTheme.primary : AppTheme.border,
              width: isActive ? 1.8 : 1.2,
            ),
          ),
          alignment: Alignment.center,
          child: Text(
            title,
            style: TextStyle(
              fontSize: 11,
              fontWeight: isActive ? FontWeight.w800 : FontWeight.w600,
              color: isActive ? AppTheme.primary : AppTheme.slate,
            ),
          ),
        ),
      ),
    );
  }
}
