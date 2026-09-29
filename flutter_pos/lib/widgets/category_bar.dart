import 'package:flutter/material.dart';
import '../controllers/pos_controller.dart';
import '../theme/app_theme.dart';

class CategoryBar extends StatelessWidget {
  final PosController controller;

  const CategoryBar({super.key, required this.controller});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 86,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        physics: const BouncingScrollPhysics(),
        itemCount: controller.categories.length,
        separatorBuilder: (context, index) => const SizedBox(width: 12),
        itemBuilder: (context, index) {
          final cat = controller.categories[index];
          final isActive = controller.selectedCategory == cat;
          final icon = controller.getCategoryIcon(cat);

          return InkWell(
            onTap: () => controller.selectCategory(cat),
            borderRadius: BorderRadius.circular(16),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              width: 96,
              height: 86,
              decoration: BoxDecoration(
                color: isActive ? AppTheme.primaryLight : AppTheme.cardBg,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: isActive ? AppTheme.primary : AppTheme.border,
                  width: isActive ? 2.0 : 1.5,
                ),
                boxShadow: [
                  BoxShadow(
                    color: isActive
                        ? AppTheme.primary.withValues(alpha: 0.16)
                        : Colors.black.withValues(alpha: 0.02),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    icon,
                    style: TextStyle(
                      fontSize: isActive ? 26 : 24,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    cat,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: isActive ? FontWeight.w800 : FontWeight.w700,
                      color: isActive ? AppTheme.primary : AppTheme.slate,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
