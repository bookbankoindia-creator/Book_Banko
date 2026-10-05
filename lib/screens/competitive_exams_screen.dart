import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/app_state.dart';
import '../models/data_models.dart';
import '../services/api_services.dart';
import '../theme/app_theme.dart';

class CompetitiveExamsScreen extends StatefulWidget {
  final String? initialExamSlug;

  const CompetitiveExamsScreen({
    super.key,
    this.initialExamSlug,
  });

  @override
  State<CompetitiveExamsScreen> createState() => _CompetitiveExamsScreenState();
}

class _CompetitiveExamsScreenState extends State<CompetitiveExamsScreen> {
  late Future<List<CompetitiveExamMaterialModel>> _materialsFuture;

  @override
  void initState() {
    super.initState();
    _loadMaterials();
  }

  void _loadMaterials() {
    _materialsFuture = ApiService.fetchCompetitiveExamMaterials(
      examSlug: widget.initialExamSlug,
    );
  }

  Future<void> _refresh() async {
    setState(() {
      _loadMaterials();
    });
  }

  void _openPdf(CompetitiveExamMaterialModel item, BookBankoAppState appState) {
    if (item.pdfUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No PDF file attached to this material.'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    // Set chapter in AppState so PdfReaderScreen loads it seamlessly
    appState.selectChapter(item.toChapterModel());
    Navigator.pushNamed(context, '/pdf_reader');
  }

  @override
  Widget build(BuildContext context) {
    final appState = Provider.of<BookBankoAppState>(context);

    return Scaffold(
      backgroundColor: AppColors.canvasBg,
      appBar: AppBar(
        title: const Text(
          'Competitive Exams',
          style: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.w700,
          ),
        ),
        centerTitle: false,
        backgroundColor: AppColors.primaryBlue,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Material List Content
            Expanded(
              child: RefreshIndicator(
                onRefresh: _refresh,
                color: AppColors.primaryBlue,
                child: FutureBuilder<List<CompetitiveExamMaterialModel>>(
                  future: _materialsFuture,
                  builder: (context, snapshot) {
                    if (snapshot.connectionState == ConnectionState.waiting) {
                      return const Center(
                        child: CircularProgressIndicator(color: AppColors.primaryBlue),
                      );
                    }

                    final materials = snapshot.data ?? [];

                    if (materials.isEmpty) {
                      return ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: [
                          const SizedBox(height: 80),
                          Center(
                            child: Padding(
                              padding: const EdgeInsets.all(24.0),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(20),
                                    decoration: const BoxDecoration(
                                      color: AppColors.lightBlueBg,
                                      shape: BoxShape.circle,
                                    ),
                                    child: const Icon(
                                      Icons.picture_as_pdf_rounded,
                                      size: 48,
                                      color: AppColors.primaryBlue,
                                    ),
                                  ),
                                  const SizedBox(height: 16),
                                  const Text(
                                    'No Competitive Exam Materials uploaded yet',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      fontSize: 17,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.textPrimary,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  const Text(
                                    'Upload PDFs for Competitive Exams in your Book Banko Admin Panel to display here.',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      fontSize: 13,
                                      color: AppColors.textSecondary,
                                    ),
                                  ),
                                  const SizedBox(height: 20),
                                  ElevatedButton.icon(
                                    onPressed: _refresh,
                                    icon: const Icon(Icons.refresh),
                                    label: const Text('Refresh'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppColors.primaryBlue,
                                      foregroundColor: Colors.white,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      );
                    }

                    return ListView.builder(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                      itemCount: materials.length,
                      itemBuilder: (context, index) {
                        final item = materials[index];

                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          child: Material(
                            color: Colors.transparent,
                            child: InkWell(
                              onTap: () => _openPdf(item, appState),
                              borderRadius: BorderRadius.circular(16),
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 16,
                                ),
                                decoration: BoxDecoration(
                                  color: AppColors.surfaceWhite,
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(color: AppColors.borderLight),
                                  boxShadow: const [AppShadows.subtleShadow],
                                ),
                                child: Row(
                                  children: [
                                    // PDF Icon Badge
                                    Container(
                                      width: 44,
                                      height: 44,
                                      decoration: BoxDecoration(
                                        color: AppColors.lightBlueBg,
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: const Center(
                                        child: Icon(
                                          Icons.picture_as_pdf_rounded,
                                          color: AppColors.primaryBlue,
                                          size: 24,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 14),

                                    // Material Title
                                    Expanded(
                                      child: Text(
                                        item.title,
                                        style: const TextStyle(
                                          fontSize: 15,
                                          fontWeight: FontWeight.w600,
                                          color: AppColors.textPrimary,
                                        ),
                                      ),
                                    ),

                                    // Chevron Icon
                                    const Icon(
                                      Icons.chevron_right_rounded,
                                      color: AppColors.textSecondary,
                                      size: 24,
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
