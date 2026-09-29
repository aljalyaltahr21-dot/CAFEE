import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';
import '../theme/app_theme.dart';

class UpdateService {
  static const String currentVersion = '1.0.0';

  // Server endpoints to check
  static const List<String> serverUrls = [
    'http://192.168.105.63:8000/api.php?action=check_mobile_update',
    'http://localhost:8000/api.php?action=check_mobile_update',
  ];

  static Future<Map<String, dynamic>?> checkServerForUpdate() async {
    for (final baseUrl in serverUrls) {
      try {
        final uri = Uri.parse('$baseUrl&version=$currentVersion');
        final response = await http.get(uri).timeout(const Duration(seconds: 4));
        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          if (data is Map<String, dynamic> && data['ok'] == true) {
            return data;
          }
        }
      } catch (_) {
        // Try next URL
      }
    }
    return null;
  }

  /// Automatically called on app launch
  static Future<void> checkOnAppStart(BuildContext context) async {
    await Future.delayed(const Duration(milliseconds: 1500));
    if (!context.mounted) return;

    final updateData = await checkServerForUpdate();
    if (updateData != null && updateData['hasUpdate'] == true && context.mounted) {
      showUpdateDialog(
        context: context,
        latestVersion: updateData['latestVersion'] ?? '1.0.1',
        releaseNotes: updateData['releaseNotes'] ?? 'تحسينات جديدة في الأداء والواجهات.',
        downloadUrl: updateData['downloadUrl'] ??
            'https://github.com/aljalyaltahr21-dot/CAFEE/releases/download/flutter-apk-latest/app-release.apk',
        localDownloadUrl: updateData['localDownloadUrl'],
      );
    }
  }

  /// Manually triggered by the cashier
  static Future<void> checkManually(BuildContext context) async {
    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Row(
          children: [
            SizedBox(
              width: 16,
              height: 16,
              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
            ),
            SizedBox(width: 12),
            Text('جاري التحقق من التحديثات السحابية...'),
          ],
        ),
        duration: Duration(seconds: 2),
        behavior: SnackBarBehavior.floating,
        margin: EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      ),
    );

    final updateData = await checkServerForUpdate();
    if (!context.mounted) return;

    ScaffoldMessenger.of(context).hideCurrentSnackBar();

    if (updateData != null && updateData['hasUpdate'] == true) {
      showUpdateDialog(
        context: context,
        latestVersion: updateData['latestVersion'] ?? '1.0.1',
        releaseNotes: updateData['releaseNotes'] ?? 'تحسينات جديدة في الأداء والواجهات.',
        downloadUrl: updateData['downloadUrl'] ??
            'https://github.com/aljalyaltahr21-dot/CAFEE/releases/download/flutter-apk-latest/app-release.apk',
        localDownloadUrl: updateData['localDownloadUrl'],
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('أنت تستخدم أحدث إصدار (v$currentVersion) ✓'),
          backgroundColor: AppTheme.green,
          behavior: SnackBarBehavior.floating,
          margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
        ),
      );
    }
  }

  static void showUpdateDialog({
    required BuildContext context,
    required String latestVersion,
    required String releaseNotes,
    required String downloadUrl,
    String? localDownloadUrl,
  }) {
    showDialog(
      context: context,
      barrierDismissible: true,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
        child: Container(
          constraints: const BoxConstraints(maxWidth: 400),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.25),
                blurRadius: 28,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          padding: const EdgeInsets.all(22),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Top Icon Header
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [AppTheme.primary, Color(0xFF42161D)],
                    begin: Alignment.topRight,
                    end: Alignment.bottomLeft,
                  ),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: AppTheme.primary.withValues(alpha: 0.35),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                alignment: Alignment.center,
                child: const Text('🚀', style: TextStyle(fontSize: 32)),
              ),

              const SizedBox(height: 16),

              const Text(
                'تحديث جديد متوفر!',
                style: TextStyle(
                  fontSize: 19,
                  fontWeight: FontWeight.w900,
                  color: AppTheme.navy,
                ),
              ),

              const SizedBox(height: 6),

              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppTheme.primaryLight,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppTheme.primaryBorder),
                ),
                child: Text(
                  'نسخة سامسونج وأندرويد v$latestVersion',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    color: AppTheme.primary,
                  ),
                ),
              ),

              const SizedBox(height: 14),

              // Release Notes Box
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFFF9F7F5),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppTheme.borderLight),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'ما الجديد في هذا التحديث:',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.navy,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      releaseNotes,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppTheme.slate,
                        height: 1.4,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // Action Buttons
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton(
                      onPressed: () async {
                        Navigator.of(ctx).pop();
                        // Open Download URL
                        final target = Uri.parse(downloadUrl);
                        if (await canLaunchUrl(target)) {
                          await launchUrl(target, mode: LaunchMode.externalApplication);
                        } else if (localDownloadUrl != null) {
                          final localUri = Uri.parse('http://192.168.105.63:8000/$localDownloadUrl');
                          await launchUrl(localUri, mode: LaunchMode.externalApplication);
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primary,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 13),
                      ),
                      child: const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.download_rounded, size: 20),
                          SizedBox(width: 8),
                          Text(
                            'تحديث الآن (APK)',
                            style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  OutlinedButton(
                    onPressed: () => Navigator.of(ctx).pop(),
                    style: OutlinedButton.styleFrom(
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 13, horizontal: 16),
                    ),
                    child: const Text('لاحقاً', style: TextStyle(fontWeight: FontWeight.w700)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
