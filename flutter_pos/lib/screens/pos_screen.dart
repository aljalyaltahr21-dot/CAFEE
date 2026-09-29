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
        heightFactor: 0.88,
        child: BillsPanel(controller: _controller, isBottomSheet: true),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isMobile = screenWidth < 850;

    // Mobile Phone / Tablet Portrait Layout (Zero Overlap & Zero Overflow)
    if (isMobile) {
      return Scaffold(
        backgroundColor: AppTheme.bg,
        drawer: Drawer(
          width: 280,
          child: PosSidebar(
            activeIndex: _activeNavIndex,
            isDrawer: true,
            onIndexChanged: (idx) {
              setState(() => _activeNavIndex = idx);
              Navigator.of(context).pop();
              if (idx != 1) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('القسم رقم $idx قيد المزامنة السحابية'),
                    duration: const Duration(seconds: 1),
                    behavior: SnackBarBehavior.floating,
                    margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                  ),
                );
              }
            },
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
                'نقطة البيع - نسخة سامسونج وأندرويد',
                style: TextStyle(fontSize: 11, color: AppTheme.slate),
              ),
            ],
          ),
          actions: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
              decoration: BoxDecoration(
                color: AppTheme.primaryLight,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: AppTheme.primaryBorder),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('🧑‍🍳', style: TextStyle(fontSize: 14)),
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
          bottom: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 8, 14, 76), // Padding prevents overlap with Floating Cart Button
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
                      hintText: 'ابحث في الأصناف والمشروبات...',
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

                const SizedBox(height: 10),

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
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.navy,
                      ),
                    ),
                    Text(
                      '${_controller.filteredProducts.length} صنف متوفر',
                      style: const TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w600,
                        color: AppTheme.slate,
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 10),

                // Dynamic Intrinsic Products List (Eliminates RenderFlex Overflow & Overlap)
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
                      : ListView.separated(
                          physics: const BouncingScrollPhysics(),
                          itemCount: _controller.filteredProducts.length,
                          separatorBuilder: (context, index) => const SizedBox(height: 12),
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
        // Floating Cart Bar (Centered Bottom Trigger for Cashier Thumb Reach)
        floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
        floatingActionButton: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: SizedBox(
            width: double.infinity,
            height: 52,
            child: FloatingActionButton.extended(
              onPressed: () => _openMobileCart(context),
              backgroundColor: AppTheme.primary,
              foregroundColor: Colors.white,
              elevation: 6,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
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
                    'عرض الفاتورة والطلب',
                    style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
                  ),
                  if (_controller.cart.isNotEmpty) ...[
                    const SizedBox(width: 12),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.22),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        '${_controller.total.toStringAsFixed(2)} د.ل',
                        style: const TextStyle(
                          fontWeight: FontWeight.w900,
                          fontSize: 13,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
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
                    behavior: SnackBarBehavior.floating,
                    margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
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
                              childAspectRatio: 0.70,
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
