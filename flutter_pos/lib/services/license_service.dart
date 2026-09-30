import 'dart:convert';
import 'dart:io' show File;
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

class LicenseService {
  // Primary online hosting server
  static const String defaultServerUrl = 'https://soc-tza1.freehosting.dev';
  static const List<String> fallbackServerUrls = [
    'https://soc-tza1.freehosting.dev',
    'http://192.168.105.63:8000',
    'http://localhost:8000',
  ];

  static String currentServerUrl = defaultServerUrl;
  static String? activeLicenseKey;
  static String? cafeName;
  static String? customerName;
  static bool isActivated = false;
  static List<dynamic> branchProducts = [];

  static const String _licenseFileName = '.pos_license_key.txt';

  /// Check if a license was saved locally on device
  static Future<bool> loadSavedLicense() async {
    try {
      String? savedKey;
      if (!kIsWeb) {
        // On Mobile / Desktop: load from local file
        final file = File(_licenseFileName);
        if (await file.exists()) {
          savedKey = (await file.readAsString()).trim();
        }
      }

      if (savedKey != null && savedKey.isNotEmpty) {
        final res = await verifyLicense(savedKey);
        return res['ok'] == true;
      }
    } catch (_) {}
    return false;
  }

  /// Save license key to local storage
  static Future<void> _saveLocally(String key) async {
    try {
      if (!kIsWeb) {
        final file = File(_licenseFileName);
        await file.writeAsString(key.trim());
      }
    } catch (_) {}
  }

  /// Remove saved license key
  static Future<void> removeLicense() async {
    try {
      if (!kIsWeb) {
        final file = File(_licenseFileName);
        if (await file.exists()) {
          await file.delete();
        }
      }
    } catch (_) {}
    isActivated = false;
    activeLicenseKey = null;
    cafeName = null;
    customerName = null;
    branchProducts.clear();
  }

  /// Verify license key against hosting server
  static Future<Map<String, dynamic>> verifyLicense(String rawKey) async {
    final key = rawKey.trim().toUpperCase();
    if (key.isEmpty) {
      return {'ok': false, 'error': 'يرجى إدخال مفتاح الترخيص'};
    }

    // Try primary hosting server first, then fallbacks
    for (final base in [currentServerUrl, ...fallbackServerUrls]) {
      try {
        final uri = Uri.parse('$base/api.php?action=verify_license&key=$key');
        final response = await http.get(uri).timeout(const Duration(seconds: 6));

        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          if (data is Map<String, dynamic> && data['ok'] == true) {
            isActivated = true;
            activeLicenseKey = key;
            cafeName = data['cafeName'] ?? 'كافيه دي بوينت';
            customerName = data['customerName'] ?? 'عميل معتمد';
            currentServerUrl = base;

            if (data['products'] is List) {
              branchProducts = data['products'];
            }

            await _saveLocally(key);

            return {
              'ok': true,
              'cafeName': cafeName,
              'customerName': customerName,
              'message': data['message'] ?? 'تم تنشيط الترخيص بنجاح ✓',
            };
          } else {
            return {
              'ok': false,
              'error': data['error'] ?? 'مفتاح الترخيص غير صالح أو تم إيقافه.',
            };
          }
        } else if (response.statusCode == 403 || response.statusCode == 404) {
          try {
            final data = jsonDecode(response.body);
            return {'ok': false, 'error': data['error'] ?? 'الترخيص غير صالح أو منتهي الصلاحية.'};
          } catch (_) {
            return {'ok': false, 'error': 'تم رفض الترخيص من قبل السيرفر (كود ${response.statusCode})'};
          }
        }
      } catch (_) {
        // Try next fallback URL
      }
    }

    return {
      'ok': false,
      'error': 'تعذر الاتصال بسيرفر الاستضافة ($currentServerUrl). يرجى التأكد من اتصال الإنترنت.',
    };
  }
}
