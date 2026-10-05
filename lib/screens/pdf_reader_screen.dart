import 'package:book_banko/config/app_config.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:pdfx/pdfx.dart';
import 'package:provider/provider.dart';

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
  PdfControllerPinch? _pdfController;
  bool _isLoading = true;
  String? _errorMessage;
  int _currentPage = 1;
  int _totalPages = 0;
  String _currentLoadedUrl = '';
  bool _isHorizontalScroll = true; // Horizontal book-flip mode by default

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final appState = Provider.of<BookBankoAppState>(context, listen: false);
    final chapter = appState.selectedChapter ?? ChapterModel.mathChapters[0];

    if (_currentLoadedUrl != chapter.pdfUrl) {
      _currentLoadedUrl = chapter.pdfUrl;
      _loadPdf(chapter.pdfUrl);
    }
  }

  void _toggleScrollDirection() {
    setState(() {
      _isHorizontalScroll = !_isHorizontalScroll;
    });
    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          _isHorizontalScroll
              ? 'Switched to Horizontal (Book Flip) Mode'
              : 'Switched to Vertical (Continuous Scroll) Mode',
        ),
        duration: const Duration(seconds: 1),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _showJumpToPageDialog() {
    if (_totalPages <= 1) return;
    final textController = TextEditingController(text: '$_currentPage');

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Row(
          children: [
            Icon(Icons.menu_book_rounded, color: AppColors.primaryBlue),
            SizedBox(width: 8),
            Text('Go to Page', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Enter page number between 1 and $_totalPages:'),
            const SizedBox(height: 12),
            TextField(
              controller: textController,
              keyboardType: TextInputType.number,
              autofocus: true,
              decoration: InputDecoration(
                hintText: 'e.g. 15',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              final page = int.tryParse(textController.text.trim());
              if (page != null && page >= 1 && page <= _totalPages) {
                Navigator.pop(ctx);
                _pdfController?.animateToPage(
                  pageNumber: page,
                  duration: const Duration(milliseconds: 300),
                  curve: Curves.easeInOut,
                );
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primaryBlue,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Go'),
          ),
        ],
      ),
    );
  }

  String _normalizePdfUrl(String rawUrl) {
    if (rawUrl.isEmpty) return rawUrl;

    // If it's already a public cloud URL (e.g. Supabase Storage CDN)
    if (rawUrl.contains('supabase.co')) {
      return rawUrl;
    }

    // If running on a physical Android device or emulator, replace localhost with active backend host
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      final activeBase = ApiService.baseUrl;
      final backendRoot = activeBase.replaceAll('/api', '');

      if (rawUrl.contains('localhost/Book_Banko/backend')) {
        return rawUrl.replaceAll(
          'http://localhost/Book_Banko/backend',
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
    }
    return rawUrl;
  }

  Future<void> _loadPdf(String url) async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      if (_pdfController != null) {
        _pdfController!.dispose();
        _pdfController = null;
      }

      Uint8List? pdfBytes;
      final normalizedUrl = _normalizePdfUrl(url);

      if (normalizedUrl.startsWith('http://') ||
          normalizedUrl.startsWith('https://')) {
        try {
          final response = await http
              .get(Uri.parse(normalizedUrl))
              .timeout(const Duration(seconds: 120));
          if (response.statusCode == 200 && response.bodyBytes.isNotEmpty) {
            pdfBytes = response.bodyBytes;
          } else {
            debugPrint('Primary PDF request returned HTTP ${response.statusCode}');
          }
        } catch (e) {
          debugPrint('Primary PDF download failed: $e');
        }
      }

      // Fallback 1: Supabase CDN
      if (pdfBytes == null && !normalizedUrl.contains('supabase.co')) {
        final filename = url.split('/').last.split('?').first;
        if (filename.isNotEmpty) {
          final cdnUrl =
              '${AppConfig.supabaseUrl}/storage/v1/object/public/pdfs/$filename';
          try {
            final res = await http
                .get(Uri.parse(cdnUrl))
                .timeout(const Duration(seconds: 60));
            if (res.statusCode == 200 && res.bodyBytes.isNotEmpty) {
              pdfBytes = res.bodyBytes;
            }
          } catch (_) {}
        }
      }

      // Fallback 2: Local backend host
      if (pdfBytes == null) {
        final filename = url.split('/').last.split('?').first;
        final fallbackUrl = _normalizePdfUrl(
          '${ApiService.baseUrl.replaceAll('/api', '')}/uploads/pdfs/$filename',
        );
        try {
          final res = await http
              .get(Uri.parse(fallbackUrl))
              .timeout(const Duration(seconds: 120));
          if (res.statusCode == 200 && res.bodyBytes.isNotEmpty) {
            pdfBytes = res.bodyBytes;
          }
        } catch (e) {
          debugPrint('Local backend PDF fallback failed: $e');
        }
      }

      if (pdfBytes == null || pdfBytes.isEmpty) {
        throw Exception(
          'Could not connect to PDF server. Please check your network connection.',
        );
      }

      final document = await PdfDocument.openData(pdfBytes);
      if (!mounted) return;

      _pdfController = PdfControllerPinch(
        document: Future.value(document),
        initialPage: 1,
      );

      setState(() {
        _totalPages = document.pagesCount;
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
    _pdfController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final appState = Provider.of<BookBankoAppState>(context);
    final chapter = appState.selectedChapter ?? ChapterModel.mathChapters[0];

    return Scaffold(
      backgroundColor: const Color(0xFF1E242E), // Sleek reader dark background
      appBar: AppBar(
        title: Text(
          chapter.title,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 16,
            fontWeight: FontWeight.w700,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        centerTitle: false,
        backgroundColor: AppColors.primaryBlue,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios_new_rounded,
            color: Colors.white,
            size: 20,
          ),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          // Jump to Page Button
          if (!_isLoading && _totalPages > 0)
            IconButton(
              icon: const Icon(Icons.pin_invoke_rounded, color: Colors.white, size: 22),
              tooltip: 'Go to Page',
              onPressed: _showJumpToPageDialog,
            ),
          // Scroll Direction Mode Toggle (Horizontal / Vertical)
          if (!_isLoading && _totalPages > 0)
            IconButton(
              icon: Icon(
                _isHorizontalScroll ? Icons.swap_vert_rounded : Icons.swap_horiz_rounded,
                color: Colors.white,
                size: 24,
              ),
              tooltip: _isHorizontalScroll
                  ? 'Switch to Vertical Continuous Scroll'
                  : 'Switch to Horizontal Book Flip',
              onPressed: _toggleScrollDirection,
            ),
          const SizedBox(width: 6),
        ],
      ),
      body: SafeArea(
        bottom: false,
        child: _buildBody(),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: const [
            CircularProgressIndicator(color: Colors.white),
            SizedBox(height: 16),
            Text(
              'Loading PDF Document...',
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w600,
                color: Colors.white70,
              ),
            ),
          ],
        ),
      );
    }

    if (_errorMessage != null || _pdfController == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
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
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _errorMessage ?? 'Unable to render the uploaded PDF file.',
                style: const TextStyle(
                  fontSize: 13,
                  color: Colors.white70,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () => _loadPdf(_currentLoadedUrl),
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
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Stack(
      children: [
        // Pinch-Zoomable, Aspect-Ratio-Preserving PDF Viewer (Full Page View without any cropping)
        Positioned.fill(
          child: PdfViewPinch(
            key: ValueKey('pdf_pinch_${_isHorizontalScroll ? 'h' : 'v'}'),
            controller: _pdfController!,
            scrollDirection: _isHorizontalScroll ? Axis.horizontal : Axis.vertical,
            onDocumentLoaded: (document) {
              setState(() {
                _totalPages = document.pagesCount;
              });
            },
            onPageChanged: (page) {
              setState(() {
                _currentPage = page;
              });
            },
            builders: PdfViewPinchBuilders<DefaultBuilderOptions>(
              options: const DefaultBuilderOptions(
                loaderSwitchDuration: Duration(milliseconds: 200),
              ),
              documentLoaderBuilder: (_) => const Center(
                child: CircularProgressIndicator(color: Colors.white),
              ),
              pageLoaderBuilder: (_) => const Center(
                child: CircularProgressIndicator(color: Colors.white),
              ),
              errorBuilder: (context, error) => Center(
                child: Text(
                  'Error loading page: $error',
                  style: const TextStyle(color: Colors.white70),
                ),
              ),
            ),
          ),
        ),

        // Floating Bottom HUD Indicator (Page X of Y • Tap to jump)
        Positioned(
          left: 0,
          right: 0,
          bottom: 24,
          child: Center(
            child: GestureDetector(
              onTap: _showJumpToPageDialog,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.75),
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.2),
                    width: 1,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.3),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      _isHorizontalScroll
                          ? Icons.swap_horiz_rounded
                          : Icons.swap_vert_rounded,
                      size: 16,
                      color: const Color(0xFF60A5FA),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      'Page $_currentPage of $_totalPages',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: AppColors.primaryBlue,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Text(
                        'Jump',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),

        // Left Floating Navigation Arrow (<) - Quick Page Turn in Horizontal Mode
        if (_isHorizontalScroll && _currentPage > 1)
          Positioned(
            left: 12,
            top: 0,
            bottom: 0,
            child: Center(
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: () {
                    _pdfController?.previousPage(
                      curve: Curves.easeInOut,
                      duration: const Duration(milliseconds: 250),
                    );
                  },
                  borderRadius: BorderRadius.circular(24),
                  child: Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.black.withValues(alpha: 0.65),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.25),
                        width: 1,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.25),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.chevron_left_rounded,
                      size: 30,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            ),
          ),

        // Right Floating Navigation Arrow (>) - Quick Page Turn in Horizontal Mode
        if (_isHorizontalScroll && _currentPage < _totalPages)
          Positioned(
            right: 12,
            top: 0,
            bottom: 0,
            child: Center(
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: () {
                    _pdfController?.nextPage(
                      curve: Curves.easeInOut,
                      duration: const Duration(milliseconds: 250),
                    );
                  },
                  borderRadius: BorderRadius.circular(24),
                  child: Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: AppColors.primaryBlue.withValues(alpha: 0.9),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.3),
                        width: 1,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.primaryBlue.withValues(alpha: 0.4),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.chevron_right_rounded,
                      size: 30,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}
