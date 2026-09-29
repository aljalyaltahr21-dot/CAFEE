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

  @override
  Widget build(BuildContext context) {
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
                              childAspectRatio: 0.72, // Perfect proportion for equal cards
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
