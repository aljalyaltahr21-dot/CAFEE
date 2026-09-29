import 'package:flutter_test/flutter_test.dart';
import 'package:cafe_pos_flutter/main.dart';

void main() {
  testWidgets('Cafe POS App basic smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const CafePosApp());
    expect(find.byType(CafePosApp), findsOneWidget);
  });
}
