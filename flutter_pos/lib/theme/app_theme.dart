import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  static const Color primary = Color(0xFF63262E);
  static const Color primaryDark = Color(0xFF4E1D24);
  static const Color primaryLight = Color(0xFFFDF2F0);
  static const Color primaryBorder = Color(0xFFE8D4D2);

  static const Color navy = Color(0xFF231815);
  static const Color navySurface = Color(0xFF382522);
  static const Color slate = Color(0xFF7E706D);
  static const Color slateLight = Color(0xFFA89B98);

  static const Color bg = Color(0xFFFAF8F6);
  static const Color cardBg = Color(0xFFFFFFFF);
  static const Color border = Color(0xFFEDE5E2);
  static const Color borderLight = Color(0xFFF4EFEB);

  static const Color green = Color(0xFF10B981);
  static const Color greenLight = Color(0xFFECFDF5);
  static const Color red = Color(0xFFEF4444);
  static const Color redLight = Color(0xFFFEE2E2);
  static const Color gold = Color(0xFFD97706);
  static const Color goldLight = Color(0xFFFEF3C7);

  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: bg,
      colorScheme: const ColorScheme(
        brightness: Brightness.light,
        primary: primary,
        onPrimary: Colors.white,
        secondary: gold,
        onSecondary: Colors.white,
        error: red,
        onError: Colors.white,
        surface: cardBg,
        onSurface: navy,
      ),
      fontFamily: GoogleFonts.cairo().fontFamily,
      appBarTheme: const AppBarTheme(
        backgroundColor: cardBg,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
      ),
    );
  }

  static TextStyle pacificoTitle({double size = 26, Color color = primary}) {
    return GoogleFonts.pacifico(
      fontSize: size,
      color: color,
      letterSpacing: -0.5,
    );
  }
}
