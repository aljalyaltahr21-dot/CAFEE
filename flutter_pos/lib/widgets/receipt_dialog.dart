import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../controllers/pos_controller.dart';
import '../theme/app_theme.dart';

class ReceiptDialog extends StatelessWidget {
  final PosController controller;
  final String orderNumber;

  const ReceiptDialog({
    super.key,
    required this.controller,
    required this.orderNumber,
  });

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    final dateStr = DateFormat('yyyy/MM/dd - hh:mm a').format(now);

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
      child: Container(
        constraints: const BoxConstraints(maxWidth: 380),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.25),
              blurRadius: 30,
              offset: const Offset(0, 10),
            ),
          ],
        ),
        padding: const EdgeInsets.all(20),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Coffee Icon & Brand
              const Text('☕', style: TextStyle(fontSize: 32)),
              const SizedBox(height: 6),
              const Text(
                'كافيه دي بوينت',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                  color: AppTheme.primary,
                ),
              ),
              const Text(
                'فاتورة مبيعات ضريبية مبسطة',
                style: TextStyle(fontSize: 11, color: AppTheme.slate),
              ),
              const SizedBox(height: 12),

              // Receipt Box
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFFFFDF9),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: AppTheme.border,
                    style: BorderStyle.solid,
                  ),
                ),
                padding: const EdgeInsets.all(12),
                child: Column(
                  children: [
                    _buildMetaRow('رقم الطلب', orderNumber),
                    _buildMetaRow('التاريخ', dateStr),
                    _buildMetaRow('الكاشير', 'طاهر الجالي 🧑‍🍳'),
                    _buildMetaRow(
                      'طريقة الدفع',
                      controller.paymentMethod == 'card'
                          ? 'بطاقة مصرفية 💳'
                          : (controller.paymentMethod == 'cash'
                              ? 'نقدي 💵'
                              : 'محفظة سداد 📱'),
                    ),
                    const Divider(color: AppTheme.border, height: 16),

                    // Items List
                    ConstrainedBox(
                      constraints: const BoxConstraints(maxHeight: 180),
                      child: ListView.builder(
                        shrinkWrap: true,
                        itemCount: controller.cart.length,
                        itemBuilder: (context, idx) {
                          final item = controller.cart[idx];
                          return Padding(
                            padding: const EdgeInsets.symmetric(vertical: 3),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Text(
                                    '${item.product.name} (x${item.quantity})',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                    ),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                                Text(
                                  '${item.totalPrice.toStringAsFixed(2)} د.ل',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
                    ),

                    const Divider(color: AppTheme.border, height: 16),
                    _buildAmountRow('المجموع الفرعي', '${controller.subtotal.toStringAsFixed(2)} د.ل'),
                    _buildAmountRow('الضريبة (10%)', '${controller.tax.toStringAsFixed(2)} د.ل'),
                    const SizedBox(height: 4),
                    _buildAmountRow(
                      'المجموع الكلي',
                      '${controller.total.toStringAsFixed(2)} د.ل',
                      isTotal: true,
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 16),

              // Print and Close Buttons
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton(
                      onPressed: () {
                        controller.clearCart();
                        Navigator.of(context).pop();
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('تمت معالجة وطباعة الفاتورة بنجاح ✓'),
                            backgroundColor: AppTheme.green,
                            behavior: SnackBarBehavior.floating,
                            margin: EdgeInsets.symmetric(horizontal: 20, vertical: 10),
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
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      child: const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.print_rounded, size: 18),
                          SizedBox(width: 6),
                          Text(
                            'طباعة الآن',
                            style: TextStyle(fontWeight: FontWeight.w800),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  OutlinedButton(
                    onPressed: () => Navigator.of(context).pop(),
                    style: OutlinedButton.styleFrom(
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                    ),
                    child: const Text('إغلاق'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMetaRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 11, color: AppTheme.slate)),
          Text(value, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }

  Widget _buildAmountRow(String label, String value, {bool isTotal = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: isTotal ? 14 : 12,
              fontWeight: isTotal ? FontWeight.w900 : FontWeight.w600,
              color: isTotal ? AppTheme.navy : AppTheme.slate,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: isTotal ? 16 : 12.5,
              fontWeight: FontWeight.w900,
              color: isTotal ? AppTheme.primary : AppTheme.navy,
            ),
          ),
        ],
      ),
    );
  }
}
