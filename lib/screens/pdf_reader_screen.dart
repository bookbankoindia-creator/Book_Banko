import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:syncfusion_flutter_pdf/pdf.dart' as sf_pdf;
import 'package:syncfusion_flutter_pdfviewer/pdfviewer.dart';
import 'package:url_launcher/url_launcher.dart';

import '../config/app_config.dart';
import '../models/app_state.dart';
import '../models/data_models.dart';
import '../services/api_services.dart';
import '../theme/app_theme.dart';

class PdfReaderScreen extends StatefulWidget {
  const PdfReaderScreen({super.key});

  @override
  State<PdfReaderScreen> createState() => _PdfReaderScreenState();
}

class _PdfReaderScreenState extends State<PdfReaderScreen> {
  late PdfViewerController _pdfViewerController;
  Uint8List? _pdfBytes;
  bool _isLoading = true;
  String? _errorMessage;
  int _currentPage = 1;
  int _totalPages = 0;
  String _currentLoadedUrl = '';

  @override
  void initState() {
    super.initState();
    _pdfViewerController = PdfViewerController();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final appState = Provider.of<BookBankoAppState>(context, listen: false);
    final chapter = appState.selectedChapter ?? ChapterModel.mathChapters[0];

    if (_currentLoadedUrl != chapter.pdfUrl) {
      _currentLoadedUrl = chapter.pdfUrl;
      _loadPdf(chapter.pdfUrl, chapter);
    }
  }

  void _showJumpToPageDialog() {
    if (_totalPages <= 1) return;
    final textController = TextEditingController(text: '$_currentPage');

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.edit, color: AppColors.primaryBlue),
            SizedBox(width: 8),
            Text(
              'Go to Page',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Enter page number (1 - $_totalPages):',
              style: const TextStyle(
                fontSize: 14,
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: textController,
              keyboardType: TextInputType.number,
              autofocus: true,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
              decoration: InputDecoration(
                hintText: 'e.g. 15',
                filled: true,
                fillColor: AppColors.lightBlueBg,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: AppColors.borderLight),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(
                    color: AppColors.primaryBlue,
                    width: 1.5,
                  ),
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 12,
                ),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text(
              'Cancel',
              style: TextStyle(color: AppColors.textSecondary),
            ),
          ),
          ElevatedButton(
            onPressed: () {
              final page = int.tryParse(textController.text.trim());
              if (page != null && page >= 1 && page <= _totalPages) {
                Navigator.pop(ctx);
                _pdfViewerController.jumpToPage(page);
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primaryBlue,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: const Text(
              'Go',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }

  String _normalizePdfUrl(String rawUrl) {
    if (rawUrl.isEmpty) return rawUrl;

    if (rawUrl.contains('supabase.co')) {
      return rawUrl;
    }

    final activeBase = ApiService.baseUrl;
    final backendRoot = activeBase.replaceAll('/api', '');

    if (rawUrl.contains('localhost/Book_Banko/backend')) {
      return rawUrl.replaceAll(
        'http://localhost/Book_Banko/backend',
        backendRoot,
      );
    }
    if (rawUrl.contains('192.168.0.102/Book_Banko/backend')) {
      return rawUrl.replaceAll(
        'http://192.168.0.102/Book_Banko/backend',
        backendRoot,
      );
    }
    if (rawUrl.contains('10.0.2.2/Book_Banko/backend')) {
      return rawUrl.replaceAll(
        'http://10.0.2.2/Book_Banko/backend',
        backendRoot,
      );
    }
    if (rawUrl.contains('127.0.0.1/Book_Banko/backend')) {
      return rawUrl.replaceAll(
        'http://127.0.0.1/Book_Banko/backend',
        backendRoot,
      );
    }
    return rawUrl;
  }

  Future<void> _loadPdf(String url, [ChapterModel? chapter]) async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      Uint8List? downloadedBytes;
      final normalizedUrl = _normalizePdfUrl(url);

      if (normalizedUrl.startsWith('http://') ||
          normalizedUrl.startsWith('https://')) {
        try {
          final response = await http
              .get(Uri.parse(normalizedUrl))
              .timeout(const Duration(seconds: 120));
          if (response.statusCode == 200 && response.bodyBytes.isNotEmpty) {
            downloadedBytes = response.bodyBytes;
          }
        } catch (e) {
          debugPrint('Primary PDF download error: $e');
        }
      }

      // Fallback 1: Direct Supabase public CDN URL
      if (downloadedBytes == null && !normalizedUrl.contains('supabase.co')) {
        final filename = url.split('/').last.split('?').first;
        if (filename.isNotEmpty) {
          final cdnUrl =
              '${AppConfig.supabaseUrl}/storage/v1/object/public/pdfs/$filename';
          try {
            final res = await http
                .get(Uri.parse(cdnUrl))
                .timeout(const Duration(seconds: 60));
            if (res.statusCode == 200 && res.bodyBytes.isNotEmpty) {
              downloadedBytes = res.bodyBytes;
            }
          } catch (_) {}
        }
      }

      // Fallback 2: Serverless uploads URL
      if (downloadedBytes == null) {
        final filename = url.split('/').last.split('?').first;
        final fallbackUrl = _normalizePdfUrl(
          '${ApiService.baseUrl.replaceAll('/api', '')}/uploads/pdfs/$filename',
        );
        try {
          final res = await http
              .get(Uri.parse(fallbackUrl))
              .timeout(const Duration(seconds: 120));
          if (res.statusCode == 200 && res.bodyBytes.isNotEmpty) {
            downloadedBytes = res.bodyBytes;
          }
        } catch (e) {
          debugPrint('Fallback PDF download error: $e');
        }
      }

      if (downloadedBytes == null || downloadedBytes.isEmpty) {
        throw Exception(
          'Could not connect to PDF cloud server. Please check your internet connection.',
        );
      }

      if (!mounted) return;

      setState(() {
        _pdfBytes = downloadedBytes;
        _currentPage = 1;
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _errorMessage = 'Could not load PDF document.\n$e';
      });
    }
  }

  @override
  void dispose() {
    _pdfViewerController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final appState = Provider.of<BookBankoAppState>(context);
    final chapter = appState.selectedChapter ?? ChapterModel.mathChapters[0];
    final subject = appState.selectedSubject;
    final subjectTitle = subject?.name.isNotEmpty == true
        ? subject!.name
        : chapter.title;
    final standardText = appState.activeStandardNumber > 0
        ? 'ધોરણ – ${appState.activeStandardNumber}'
        : 'ધોરણ – 10';

    return Scaffold(
      backgroundColor: AppColors.canvasBg, // App theme background
      appBar: AppBar(
        backgroundColor: AppColors.primaryBlue, // App theme primary blue
        elevation: 0,
        centerTitle: false,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.white, size: 24),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              subjectTitle,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.2,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 1),
            Text(
              standardText,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.85),
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          // 1. Centered PDF Viewport with upper & lower gap margins
          Expanded(
            child: Container(
              width: double.infinity,
              height: double.infinity,
              color: AppColors.canvasBg,
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
              alignment: Alignment.center,
              child: _buildPdfView(chapter),
            ),
          ),

          // 2. Fixed Bottom Navigation Bar in App Theme Color
          _buildBottomNavigationBar(),
        ],
      ),
    );
  }

  Widget _buildPdfView(ChapterModel chapter) {
    if (_isLoading) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            CircularProgressIndicator(color: AppColors.primaryBlue),
            SizedBox(height: 16),
            Text(
              'Loading PDF Document...',
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w600,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      );
    }

    if (_errorMessage != null || _pdfBytes == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const Icon(
                Icons.picture_as_pdf_rounded,
                size: 64,
                color: AppColors.error,
              ),
              const SizedBox(height: 16),
              const Text(
                'Failed to Load PDF',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _errorMessage ?? 'Unable to render the uploaded PDF file.',
                style: const TextStyle(
                  fontSize: 13,
                  color: AppColors.textSecondary,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () => _loadPdf(_currentLoadedUrl, chapter),
                icon: const Icon(Icons.refresh),
                label: const Text('Try Again'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primaryBlue,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 24,
                    vertical: 12,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    // Pure Syncfusion Flutter PDF Viewer: renders the PDF page and all hyperlinks embedded inside the PDF
    return Center(
      child: ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: SfPdfViewer.memory(
          _pdfBytes!,
          controller: _pdfViewerController,
          enableDoubleTapZooming: true,
          pageSpacing: 8,
          canShowScrollHead: false,
          canShowScrollStatus: false,
          canShowPaginationDialog: false,
          canShowHyperlinkDialog: false,
          enableHyperlinkNavigation: true,
          pageLayoutMode: PdfPageLayoutMode.single,
          scrollDirection: PdfScrollDirection.horizontal,
          onDocumentLoaded: (PdfDocumentLoadedDetails details) {
            setState(() {
              _totalPages = details.document.pages.count;
            });
          },
          onPageChanged: (PdfPageChangedDetails details) {
            setState(() {
              _currentPage = details.newPageNumber;
            });
          },
          onHyperlinkClicked: (PdfHyperlinkClickedDetails details) async {
            final uri = Uri.tryParse(details.uri);
            if (uri != null) {
              try {
                await launchUrl(uri, mode: LaunchMode.externalApplication);
              } catch (e) {
                debugPrint('Hyperlink launch error: $e');
              }
            }
          },
        ),
      ),
    );
  }

  Widget _buildBottomNavigationBar() {
    return Container(
      height: 58,
      padding: const EdgeInsets.symmetric(horizontal: 16),
      decoration: const BoxDecoration(
        color: AppColors.primaryBlue, // Theme color for navigation bar
        boxShadow: [
          BoxShadow(
            color: Color(0x2A000000),
            blurRadius: 6,
            offset: Offset(0, -2),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Left: Page Count Indicator (e.g. 1 / 173) + Edit icon
          Row(
            children: [
              GestureDetector(
                onTap: _showJumpToPageDialog,
                child: Text(
                  '$_currentPage / ${_totalPages > 0 ? _totalPages : 1}',
                  style: const TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              IconButton(
                icon: const Icon(Icons.edit, color: Colors.white, size: 22),
                tooltip: 'Go to Page',
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: _showJumpToPageDialog,
              ),
            ],
          ),

          // Right: Previous (<) and Next (>) Page Navigation buttons
          Row(
            children: [
              IconButton(
                icon: Icon(
                  Icons.chevron_left_rounded,
                  color: _currentPage > 1 ? Colors.white : Colors.white38,
                  size: 38,
                ),
                tooltip: 'Previous Page',
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: _currentPage > 1
                    ? () {
                        _pdfViewerController.previousPage();
                      }
                    : null,
              ),
              const SizedBox(width: 16),
              IconButton(
                icon: Icon(
                  Icons.chevron_right_rounded,
                  color: _currentPage < _totalPages
                      ? Colors.white
                      : Colors.white38,
                  size: 38,
                ),
                tooltip: 'Next Page',
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: _currentPage < _totalPages
                    ? () {
                        _pdfViewerController.nextPage();
                      }
                    : null,
              ),
            ],
          ),
        ],
      ),
    );
  }
}
