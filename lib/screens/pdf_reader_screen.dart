import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:pdfx/pdfx.dart';
import 'package:provider/provider.dart';

import '../config/app_config.dart';
import '../models/app_state.dart';
import '../models/data_models.dart';
import '../services/api_services.dart';

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
  bool _isHorizontalScroll = true; // Horizontal book-flip mode matching the reference design

  // Theme golden yellow color from user's reference design
  static const Color headerGolden = Color(0xFFFBBF24);
  static const Color bottomBarGolden = Color(0xFFFBBF24);

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
            Icon(Icons.edit, color: Colors.black87),
            SizedBox(width: 8),
            Text(
              'Go to Page',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
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
              style: const TextStyle(fontSize: 14, color: Colors.black87),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: textController,
              keyboardType: TextInputType.number,
              autofocus: true,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              decoration: InputDecoration(
                hintText: 'e.g. 15',
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                ),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            onPressed: () {
              final page = int.tryParse(textController.text.trim());
              if (page != null && page >= 1 && page <= _totalPages) {
                Navigator.pop(ctx);
                _pdfController?.animateToPage(
                  pageNumber: page,
                  duration: const Duration(milliseconds: 250),
                  curve: Curves.easeInOut,
                );
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: headerGolden,
              foregroundColor: Colors.black,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Go', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  void _showInfoDialog(BookBankoAppState appState, ChapterModel chapter) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: headerGolden.withValues(alpha: 0.3),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.info_outline_rounded, color: Colors.black87),
            ),
            const SizedBox(width: 10),
            const Text(
              'Chapter Details',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildInfoRow('Chapter:', chapter.title),
            const SizedBox(height: 8),
            _buildInfoRow('Subject:', appState.selectedSubject?.name ?? 'General Science'),
            const SizedBox(height: 8),
            _buildInfoRow('Board & Std:', '${appState.selectedBoard} • Std ${appState.activeStandardNumber}'),
            const SizedBox(height: 8),
            _buildInfoRow('Total Pages:', '$_totalPages Pages'),
            const Divider(height: 24),
            // JEE & NEET Practice banner
            InkWell(
              onTap: () {
                Navigator.pop(ctx);
                Navigator.pushNamed(context, '/competitive_exams');
              },
              borderRadius: BorderRadius.circular(10),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF3C7),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFFDE68A)),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.workspace_premium_rounded, color: Color(0xFFD97706), size: 24),
                    SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'JEE & NEET Questions',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF92400E)),
                          ),
                          Text(
                            'Click here for competitive practice sets',
                            style: TextStyle(fontSize: 11, color: Color(0xFFB45309)),
                          ),
                        ],
                      ),
                    ),
                    Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Color(0xFFD97706)),
                  ],
                ),
              ),
            ),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx),
            style: ElevatedButton.styleFrom(
              backgroundColor: headerGolden,
              foregroundColor: Colors.black,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Close', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 90,
          child: Text(
            label,
            style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.black54, fontSize: 13),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.w700, color: Colors.black87, fontSize: 13),
          ),
        ),
      ],
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
      return rawUrl.replaceAll('http://localhost/Book_Banko/backend', backendRoot);
    }
    if (rawUrl.contains('192.168.0.102/Book_Banko/backend')) {
      return rawUrl.replaceAll('http://192.168.0.102/Book_Banko/backend', backendRoot);
    }
    if (rawUrl.contains('10.0.2.2/Book_Banko/backend')) {
      return rawUrl.replaceAll('http://10.0.2.2/Book_Banko/backend', backendRoot);
    }
    if (rawUrl.contains('127.0.0.1/Book_Banko/backend')) {
      return rawUrl.replaceAll('http://127.0.0.1/Book_Banko/backend', backendRoot);
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

      if (normalizedUrl.startsWith('http://') || normalizedUrl.startsWith('https://')) {
        try {
          final response = await http.get(Uri.parse(normalizedUrl)).timeout(const Duration(seconds: 120));
          if (response.statusCode == 200 && response.bodyBytes.isNotEmpty) {
            pdfBytes = response.bodyBytes;
          }
        } catch (e) {
          debugPrint('Primary PDF download error: $e');
        }
      }

      // Fallback 1: Direct Supabase public CDN URL
      if (pdfBytes == null && !normalizedUrl.contains('supabase.co')) {
        final filename = url.split('/').last.split('?').first;
        if (filename.isNotEmpty) {
          final cdnUrl = '${AppConfig.supabaseUrl}/storage/v1/object/public/pdfs/$filename';
          try {
            final res = await http.get(Uri.parse(cdnUrl)).timeout(const Duration(seconds: 60));
            if (res.statusCode == 200 && res.bodyBytes.isNotEmpty) {
              pdfBytes = res.bodyBytes;
            }
          } catch (_) {}
        }
      }

      // Fallback 2: Serverless uploads URL
      if (pdfBytes == null) {
        final filename = url.split('/').last.split('?').first;
        final fallbackUrl = _normalizePdfUrl('${ApiService.baseUrl.replaceAll('/api', '')}/uploads/pdfs/$filename');
        try {
          final res = await http.get(Uri.parse(fallbackUrl)).timeout(const Duration(seconds: 120));
          if (res.statusCode == 200 && res.bodyBytes.isNotEmpty) {
            pdfBytes = res.bodyBytes;
          }
        } catch (e) {
          debugPrint('Fallback PDF download error: $e');
        }
      }

      if (pdfBytes == null || pdfBytes.isEmpty) {
        throw Exception('Could not connect to PDF cloud server. Please check your internet connection.');
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
    final subject = appState.selectedSubject;
    final subjectTitle = subject?.name.isNotEmpty == true ? subject!.name : chapter.title;
    final standardText = appState.activeStandardNumber > 0 ? 'ધોરણ – ${appState.activeStandardNumber}' : 'ધોરણ – 10';

    return Scaffold(
      backgroundColor: Colors.white, // Pure white background matching reference image
      appBar: AppBar(
        backgroundColor: headerGolden, // Golden Yellow matching reference image
        elevation: 0,
        centerTitle: false,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black, size: 24),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              subjectTitle,
              style: const TextStyle(
                color: Colors.black,
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
              style: const TextStyle(
                color: Colors.black87,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        actions: [
          // Info Details Dialog Button
          IconButton(
            icon: const Icon(Icons.info_outline_rounded, color: Colors.black, size: 24),
            tooltip: 'Chapter Details',
            onPressed: () => _showInfoDialog(appState, chapter),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            // 1. PDF Canvas Viewport (Edge-to-edge white background without dark margins)
            Expanded(
              child: Container(
                color: Colors.white,
                child: _buildBody(),
              ),
            ),

            // 2. Fixed Bottom Navigation Bar matching reference design
            _buildBottomNavigationBar(),
          ],
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: const [
            CircularProgressIndicator(color: headerGolden),
            SizedBox(height: 16),
            Text(
              'Loading PDF Document...',
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w600,
                color: Colors.black54,
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
                color: Colors.redAccent,
              ),
              const SizedBox(height: 16),
              const Text(
                'Failed to Load PDF',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _errorMessage ?? 'Unable to render the uploaded PDF file.',
                style: const TextStyle(
                  fontSize: 13,
                  color: Colors.black54,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () => _loadPdf(_currentLoadedUrl),
                icon: const Icon(Icons.refresh),
                label: const Text('Try Again'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: headerGolden,
                  foregroundColor: Colors.black,
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
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

    // Pinch-Zoomable, aspect-ratio-preserving PDF Viewer on seamless white canvas
    return PdfViewPinch(
      key: ValueKey('pdf_pinch_${_isHorizontalScroll ? 'h' : 'v'}'),
      controller: _pdfController!,
      scrollDirection: _isHorizontalScroll ? Axis.horizontal : Axis.vertical,
      backgroundDecoration: const BoxDecoration(
        color: Colors.white,
      ),
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
          loaderSwitchDuration: Duration(milliseconds: 150),
        ),
        documentLoaderBuilder: (_) => const Center(
          child: CircularProgressIndicator(color: headerGolden),
        ),
        pageLoaderBuilder: (_) => const Center(
          child: CircularProgressIndicator(color: headerGolden),
        ),
        errorBuilder: (context, error) => Center(
          child: Text(
            'Error loading page: $error',
            style: const TextStyle(color: Colors.black54),
          ),
        ),
      ),
    );
  }

  Widget _buildBottomNavigationBar() {
    return Container(
      height: 58,
      padding: const EdgeInsets.symmetric(horizontal: 16),
      decoration: const BoxDecoration(
        color: bottomBarGolden,
        boxShadow: [
          BoxShadow(
            color: Color(0x1A000000),
            blurRadius: 4,
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
                    color: Colors.black,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              IconButton(
                icon: const Icon(Icons.edit, color: Colors.black, size: 22),
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
                  color: _currentPage > 1 ? Colors.black : Colors.black26,
                  size: 38,
                ),
                tooltip: 'Previous Page',
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: _currentPage > 1
                    ? () {
                        _pdfController?.previousPage(
                          curve: Curves.easeInOut,
                          duration: const Duration(milliseconds: 200),
                        );
                      }
                    : null,
              ),
              const SizedBox(width: 16),
              IconButton(
                icon: Icon(
                  Icons.chevron_right_rounded,
                  color: _currentPage < _totalPages ? Colors.black : Colors.black26,
                  size: 38,
                ),
                tooltip: 'Next Page',
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: _currentPage < _totalPages
                    ? () {
                        _pdfController?.nextPage(
                          curve: Curves.easeInOut,
                          duration: const Duration(milliseconds: 200),
                        );
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
