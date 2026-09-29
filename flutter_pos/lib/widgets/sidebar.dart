import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class PosSidebar extends StatelessWidget {
  final int activeIndex;
  final ValueChanged<int> onIndexChanged;

  const PosSidebar({
    super.key,
    required this.activeIndex,
    required this.onIndexChanged,
  });

  @override
  Widget build(BuildContext context) {
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
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('نسخة سامسونج محدثة بالكامل (الإصدار v1.0.0) ✓'),
                  backgroundColor: AppTheme.green,
                  behavior: SnackBarBehavior.floating,
                  width: 320,
                ),
              );
            },
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
                    'v1.0.0',
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
