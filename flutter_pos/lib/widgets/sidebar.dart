import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import '../services/update_service.dart';
import '../services/license_service.dart';
import '../screens/license_screen.dart';

class PosSidebar extends StatelessWidget {
  final int activeIndex;
  final ValueChanged<int> onIndexChanged;
  final bool isDrawer;

  const PosSidebar({
    super.key,
    required this.activeIndex,
    required this.onIndexChanged,
    this.isDrawer = false,
  });

  @override
  Widget build(BuildContext context) {
    if (isDrawer) {
      return Container(
        color: AppTheme.cardBg,
        child: Column(
          children: [
            // Drawer Luxury Header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(20, 32, 20, 24),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [AppTheme.primary, Color(0xFF42161D)],
                  begin: Alignment.topRight,
                  end: Alignment.bottomLeft,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'coffee',
                        style: AppTheme.pacificoTitle(size: 26).copyWith(color: Colors.white),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Text(
                          'سامسونج 📱',
                          style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    LicenseService.cafeName ?? 'كافيه دي بوينت',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w900,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'العميل: ${LicenseService.customerName ?? "طاهر الجالي"} 🧑‍🍳',
                    style: const TextStyle(color: Color(0xFFF0D9DC), fontSize: 12),
                  ),
                ],
              ),
            ),

            // Navigation List
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 12),
                children: [
                  _buildDrawerTile(
                    context: context,
                    index: 0,
                    icon: Icons.dashboard_rounded,
                    title: 'لوحة التحكم',
                  ),
                  _buildDrawerTile(
                    context: context,
                    index: 1,
                    icon: Icons.restaurant_menu_rounded,
                    title: 'القائمة ونقطة البيع (POS)',
                  ),
                  _buildDrawerTile(
                    context: context,
                    index: 2,
                    icon: Icons.receipt_long_rounded,
                    title: 'سجل الفواتير والمبيعات',
                  ),
                  _buildDrawerTile(
                    context: context,
                    index: 3,
                    icon: Icons.inventory_2_rounded,
                    title: 'المخزون والمنتجات',
                  ),
                  _buildDrawerTile(
                    context: context,
                    index: 4,
                    icon: Icons.settings_rounded,
                    title: 'إعدادات النظام',
                  ),
                ],
              ),
            ),

            // Bottom Version & Logout
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    InkWell(
                      onTap: () => UpdateService.checkManually(context),
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: AppTheme.greenLight,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppTheme.green.withValues(alpha: 0.25)),
                        ),
                        child: const Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.system_update_alt_rounded, size: 16, color: AppTheme.green),
                            SizedBox(width: 6),
                            Text(
                              'فحص تحديثات سامسونج 🔄',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                                color: AppTheme.green,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    ListTile(
                      onTap: () async {
                        final confirm = await showDialog<bool>(
                          context: context,
                          builder: (ctx) => AlertDialog(
                            title: const Text('فك ربط الترخيص'),
                            content: const Text('هل تريد إلغاء تنشيط الترخيص الحالي والعودة لشاشة التنشيط؟'),
                            actions: [
                              TextButton(
                                onPressed: () => Navigator.of(ctx).pop(false),
                                child: const Text('إلغاء'),
                              ),
                              ElevatedButton(
                                onPressed: () => Navigator.of(ctx).pop(true),
                                style: ElevatedButton.styleFrom(backgroundColor: AppTheme.red),
                                child: const Text('تأكيد', style: TextStyle(color: Colors.white)),
                              ),
                            ],
                          ),
                        );
                        if (confirm == true && context.mounted) {
                          await LicenseService.removeLicense();
                          if (!context.mounted) return;
                          Navigator.of(context).pushAndRemoveUntil(
                            MaterialPageRoute(builder: (_) => const LicenseScreen()),
                            (route) => false,
                          );
                        }
                      },
                      leading: const Icon(Icons.key_off_rounded, color: AppTheme.slate),
                      title: Text(
                        'الرخصة: ${LicenseService.activeLicenseKey ?? "نشطة"}',
                        style: const TextStyle(
                          color: AppTheme.slate,
                          fontWeight: FontWeight.w700,
                          fontSize: 12,
                        ),
                      ),
                      subtitle: const Text('اضغط لتغيير الرخصة', style: TextStyle(fontSize: 10, color: AppTheme.slateLight)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    ListTile(
                      onTap: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('تم تسجيل الخروج بنجاح'),
                            backgroundColor: AppTheme.primary,
                          ),
                        );
                      },
                      leading: const Icon(Icons.logout_rounded, color: AppTheme.red),
                      title: const Text(
                        'تسجيل الخروج',
                        style: TextStyle(
                          color: AppTheme.red,
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                        ),
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
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

    // Desktop/Tablet Sidebar
    return Container(
      width: 86,
      decoration: const BoxDecoration(
        color: AppTheme.cardBg,
        border: Border(
          left: BorderSide(color: AppTheme.borderLight, width: 1.5),
        ),
      ),
      padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 8),
      child: Column(
        children: [
          // Brand Logo
          Padding(
            padding: const EdgeInsets.only(bottom: 24),
            child: Text(
              'coffee',
              style: AppTheme.pacificoTitle(size: 26),
            ),
          ),

          // Nav Items
          Expanded(
            child: Column(
              children: [
                _buildNavItem(
                  index: 0,
                  icon: Icons.dashboard_rounded,
                  label: 'الرئيسية',
                ),
                const SizedBox(height: 12),
                _buildNavItem(
                  index: 1,
                  icon: Icons.restaurant_menu_rounded,
                  label: 'القائمة',
                ),
                const SizedBox(height: 12),
                _buildNavItem(
                  index: 2,
                  icon: Icons.receipt_long_rounded,
                  label: 'السجل',
                ),
                const SizedBox(height: 12),
                _buildNavItem(
                  index: 3,
                  icon: Icons.inventory_2_rounded,
                  label: 'المخزون',
                ),
                const SizedBox(height: 12),
                _buildNavItem(
                  index: 4,
                  icon: Icons.settings_rounded,
                  label: 'الإعدادات',
                ),
              ],
            ),
          ),

          // Update Check Badge
          InkWell(
            onTap: () => UpdateService.checkManually(context),
            borderRadius: BorderRadius.circular(10),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
              decoration: BoxDecoration(
                color: AppTheme.greenLight,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppTheme.green.withValues(alpha: 0.3)),
              ),
              child: const Column(
                children: [
                  Icon(Icons.system_update_alt_rounded, size: 16, color: AppTheme.green),
                  SizedBox(height: 2),
                  Text(
                    'تحديث 🔄',
                    style: TextStyle(
                      fontSize: 9.5,
                      fontWeight: FontWeight.w800,
                      color: AppTheme.green,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 8),

          // Bottom Logout
          const Divider(color: AppTheme.borderLight, height: 16),
          _buildActionButton(
            icon: Icons.logout_rounded,
            label: 'خروج',
            color: AppTheme.slate,
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('تم تسجيل الخروج بنجاح'),
                  backgroundColor: AppTheme.primary,
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildDrawerTile({
    required BuildContext context,
    required int index,
    required IconData icon,
    required String title,
  }) {
    final isActive = activeIndex == index;
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: ListTile(
        onTap: () => onIndexChanged(index),
        selected: isActive,
        selectedTileColor: AppTheme.primaryLight,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: BorderSide(
            color: isActive ? AppTheme.primaryBorder : Colors.transparent,
          ),
        ),
        leading: Icon(
          icon,
          color: isActive ? AppTheme.primary : AppTheme.slate,
        ),
        title: Text(
          title,
          style: TextStyle(
            color: isActive ? AppTheme.primary : AppTheme.navy,
            fontWeight: isActive ? FontWeight.w800 : FontWeight.w600,
            fontSize: 13.5,
          ),
        ),
        trailing: isActive
            ? const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: AppTheme.primary)
            : null,
      ),
    );
  }

  Widget _buildNavItem({
    required int index,
    required IconData icon,
    required String label,
  }) {
    final isActive = activeIndex == index;
    return InkWell(
      onTap: () => onIndexChanged(index),
      borderRadius: BorderRadius.circular(16),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        width: 66,
        height: 64,
        decoration: BoxDecoration(
          color: isActive ? AppTheme.primary : Colors.transparent,
          borderRadius: BorderRadius.circular(16),
          boxShadow: isActive
              ? [
                  BoxShadow(
                    color: AppTheme.primary.withValues(alpha: 0.3),
                    blurRadius: 16,
                    offset: const Offset(0, 6),
                  ),
                ]
              : null,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 22,
              color: isActive ? Colors.white : AppTheme.slate,
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: isActive ? Colors.white : AppTheme.slate,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionButton({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: SizedBox(
        width: 64,
        height: 56,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 20, color: color),
            const SizedBox(height: 3),
            Text(
              label,
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w700,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
