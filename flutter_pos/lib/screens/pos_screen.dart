import 'package:flutter/material.dart';
import '../controllers/pos_controller.dart';
import '../theme/app_theme.dart';
import '../widgets/sidebar.dart';
import '../widgets/category_bar.dart';
import '../widgets/product_card.dart';
import '../widgets/bills_panel.dart';

class PosScreen extends StatefulWidget {
  const PosScreen({super.key});

  @override
  State<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends State<PosScreen> {
  final PosController _controller = PosController();
  final TextEditingController _searchController = TextEditingController();
  int _activeNavIndex = 1; // 1 = القائمة (Menu/POS)

  @override
  void initState() {
    super.initState();
    _controller.addListener(() {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _controller.dispose();
    super.dispose();
  }

  void _openMobileCart(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => FractionallySizedBox(
        heightFactor: 0.85,
        child: BillsPanel(controller: _controller, isBottomSheet: true),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isMobile = screenWidth < 900;
    final isSmallPhone = screenWidth < 500;

    // Mobile Phone / Small Screen Layout (Zero Overlap & Zero Collision)
    if (isMobile) {
      return Scaffold(
        backgroundColor: AppTheme.bg,
        drawer: Drawer(
          width: 260,
          child: SafeArea(
            child: PosSidebar(
              activeIndex: _activeNavIndex,
              onIndexChanged: (idx) {
                setState(() => _activeNavIndex = idx);
                Navigator.of(context).pop();
                if (idx != 1) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('القسم رقم $idx قيد المزامنة السحابية'),
                      duration: const Duration(seconds: 1),
                    ),
                  );
                }
              },
            ),
          ),
        ),
        appBar: AppBar(
          backgroundColor: AppTheme.cardBg,
          elevation: 0,
          surfaceTintColor: Colors.transparent,
          centerTitle: false,
          leading: Builder(
            builder: (ctx) => IconButton(
              icon: const Icon(Icons.menu_rounded, color: AppTheme.navy, size: 26),
              onPressed: () => Scaffold.of(ctx).openDrawer(),
              tooltip: 'القائمة الرئيسية',
            ),
          ),
          title: const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'كافيه دي بوينت ☕',
                style: TextStyle(
                  fontSize: 16.5,
                  fontWeight: FontWeight.w900,
                  color: AppTheme.navy,
                ),
              ),
              Text(
                'نقطة البيع - نسخة سامسونج',
                style: TextStyle(fontSize: 11, color: AppTheme.slate),
              ),
            ],
          ),
          actions: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
              decoration: BoxDecoration(
                color: AppTheme.primaryLight,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: AppTheme.primaryBorder),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('🧑‍🍳', style: TextStyle(fontSize: 15)),
                  SizedBox(width: 4),
                  Text(
                    'طاهر',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: AppTheme.primary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 8, 14, 80), // Padding to prevent overlap with FAB
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Full Width Search Box
                SizedBox(
                  height: 44,
                  child: TextField(
                    controller: _searchController,
                    onChanged: (val) => _controller.setSearchQuery(val),
                    decoration: InputDecoration(
                      hintText: 'ابحث في الأصناف والقائمة...',
                      hintStyle: const TextStyle(fontSize: 12.5, color: AppTheme.slateLight),
                      prefixIcon: const Icon(Icons.search_rounded, size: 20, color: AppTheme.slate),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear_rounded, size: 18),
                              onPressed: () {
                                _searchController.clear();
                                _controller.setSearchQuery('');
                              },
                            )
                          : null,
                      filled: true,
                      fillColor: Colors.white,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(25),
                        borderSide: const BorderSide(color: AppTheme.border),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(25),
                        borderSide: const BorderSide(color: AppTheme.border),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(25),
                        borderSide: const BorderSide(color: AppTheme.primary, width: 1.5),
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 12),

                // Category Filter Bar (Smooth horizontal scroll)
                CategoryBar(controller: _controller),

                const SizedBox(height: 10),

                // Section Title & Items Count
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      _controller.selectedCategory == 'الكل'
                          ? 'كافة الأصناف والمشروبات'
                          : 'قائمة ${_controller.selectedCategory}',
                      style: const TextStyle(
                        fontSize: 14.5,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.navy,
                      ),
                    ),
                    Text(
                      '${_controller.filteredProducts.length} صنف متوفر',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: AppTheme.slate,
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 10),

                // Non-Overlapping Products Grid (1 column on small phones, 2 on tablets)
                Expanded(
                  child: _controller.filteredProducts.isEmpty
                      ? const Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text('🔍', style: TextStyle(fontSize: 36)),
                              SizedBox(height: 8),
                              Text(
                                'لا توجد أصناف مطابقة للبحث أو التصنيف',
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w700,
                                  color: AppTheme.slate,
                                ),
                              ),
                            ],
                          ),
                        )
                      : GridView.builder(
                          physics: const BouncingScrollPhysics(),
                          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: isSmallPhone ? 1 : 2,
                            childAspectRatio: isSmallPhone ? 1.52 : 0.85,
                            mainAxisSpacing: 12,
                            crossAxisSpacing: 12,
                          ),
                          itemCount: _controller.filteredProducts.length,
                          itemBuilder: (context, index) {
                            final product = _controller.filteredProducts[index];
                            return ProductCard(
                              product: product,
                              controller: _controller,
                            );
                          },
                        ),
                ),
              ],
            ),
          ),
        ),
        // Floating Cart Bar (Bottom Sheet Trigger on Mobile)
        floatingActionButton: FloatingActionButton.extended(
          onPressed: () => _openMobileCart(context),
          backgroundColor: AppTheme.primary,
          foregroundColor: Colors.white,
          elevation: 6,
          icon: Badge(
            label: Text(
              '${_controller.totalItemsCount}',
              style: const TextStyle(fontWeight: FontWeight.w900),
            ),
            isLabelVisible: _controller.cart.isNotEmpty,
            child: const Icon(Icons.shopping_cart_rounded, size: 22),
          ),
          label: Row(
            children: [
              const Text(
                'عرض الفاتورة',
                style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5),
              ),
              if (_controller.cart.isNotEmpty) ...[
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${_controller.total.toStringAsFixed(2)} د.ل',
                    style: const TextStyle(
                      fontWeight: FontWeight.w900,
                      fontSize: 12.5,
                      color: Colors.white,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      );
    }

    // Desktop/Laptop Layout (Preserved 3-Panel)
    return Scaffold(
      backgroundColor: AppTheme.bg,
      body: Row(
        children: [
          // 1. Right/Start Vertical Sidebar
          PosSidebar(
            activeIndex: _activeNavIndex,
            onIndexChanged: (idx) {
              setState(() => _activeNavIndex = idx);
              if (idx != 1) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('القسم رقم $idx قيد المزامنة السحابية'),
                    duration: const Duration(seconds: 1),
                  ),
                );
              }
            },
          ),

          // 2. Center Main Products Workspace
          Expanded(
            child: Container(
              color: AppTheme.bg,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Top Header: Title & Search
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'كافيه دي بوينت ☕',
                            style: TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.w900,
                              color: AppTheme.navy,
                            ),
                          ),
                          Text(
                            'نظام الكاشير السحابي المطور (Flutter POS)',
                            style: TextStyle(fontSize: 12, color: AppTheme.slate),
                          ),
                        ],
                      ),
                      // Search Box
                      SizedBox(
                        width: 320,
                        height: 44,
                        child: TextField(
                          controller: _searchController,
                          onChanged: (val) => _controller.setSearchQuery(val),
                          decoration: InputDecoration(
                            hintText: 'ابحث في الأصناف أو القائمة...',
                            hintStyle: const TextStyle(fontSize: 12.5, color: AppTheme.slateLight),
                            prefixIcon: const Icon(Icons.search_rounded, size: 20, color: AppTheme.slate),
                            filled: true,
                            fillColor: Colors.white,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30),
                              borderSide: const BorderSide(color: AppTheme.border),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30),
                              borderSide: const BorderSide(color: AppTheme.border),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30),
                              borderSide: const BorderSide(color: AppTheme.primary, width: 1.5),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 18),

                  // Categories Filter Bar
                  CategoryBar(controller: _controller),

                  const SizedBox(height: 16),

                  // Section Title & Counter
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        _controller.selectedCategory == 'الكل'
                            ? 'كافة الأصناف والمشروبات'
                            : 'قائمة ${_controller.selectedCategory}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: AppTheme.navy,
                        ),
                      ),
                      Text(
                        '${_controller.filteredProducts.length} صنف متوفر',
                        style: const TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w600,
                          color: AppTheme.slate,
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 14),

                  // Symmetrical Products Grid
                  Expanded(
                    child: _controller.filteredProducts.isEmpty
                        ? const Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text('🔍', style: TextStyle(fontSize: 40)),
                                SizedBox(height: 8),
                                Text(
                                  'لا توجد أصناف مطابقة للبحث أو التصنيف',
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w700,
                                    color: AppTheme.slate,
                                  ),
                                ),
                              ],
                            ),
                          )
                        : GridView.builder(
                            physics: const BouncingScrollPhysics(),
                            gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                              maxCrossAxisExtent: 290,
                              mainAxisSpacing: 16,
                              crossAxisSpacing: 16,
                              childAspectRatio: 0.72,
                            ),
                            itemCount: _controller.filteredProducts.length,
                            itemBuilder: (context, index) {
                              final product = _controller.filteredProducts[index];
                              return ProductCard(
                                product: product,
                                controller: _controller,
                              );
                            },
                          ),
                  ),
                ],
              ),
            ),
          ),

          // 3. Left/End Bills and Cart Panel
          BillsPanel(controller: _controller),
        ],
      ),
    );
  }
}
