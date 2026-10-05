import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/app_state.dart';
import '../models/data_models.dart';
import '../services/api_services.dart';
import '../theme/app_theme.dart';
import '../widgets/subject_tile.dart';

class TextbooksScreen extends StatefulWidget {
  const TextbooksScreen({super.key});

  @override
  State<TextbooksScreen> createState() => _TextbooksScreenState();
}

class _TextbooksScreenState extends State<TextbooksScreen> {
  Future<List<SubjectModel>>? _subjectsFuture;
  String? _currentBoard;
  String? _currentMedium;
  int? _currentStandard;
  String? _currentStreamSlug;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final appState = Provider.of<BookBankoAppState>(context);
    final board = appState.selectedBoard.isNotEmpty ? appState.selectedBoard : 'GSEB';
    final medium = appState.selectedMedium;
    final standard = appState.activeStandardNumber;
    final streamSlug = appState.selectedStreamSlug ?? appState.selectedStream?.displayName.toLowerCase();

    if (_currentBoard != board ||
        _currentMedium != medium ||
        _currentStandard != standard ||
        _currentStreamSlug != streamSlug ||
        _subjectsFuture == null) {
      _currentBoard = board;
      _currentMedium = medium;
      _currentStandard = standard;
      _currentStreamSlug = streamSlug;
      _loadSubjects();
    }
  }

  void _loadSubjects() {
    if (_currentBoard != null && _currentMedium != null && _currentStandard != null) {
      _subjectsFuture = ApiService.fetchSubjects(
        boardCode: _currentBoard!,
        standardNumber: _currentStandard!,
        mediumCode: _currentMedium!,
        streamSlug: _currentStreamSlug,
      );
    }
  }

  Future<void> _refresh() async {
    setState(() {
      _loadSubjects();
    });
  }

  @override
  Widget build(BuildContext context) {
    final appState = Provider.of<BookBankoAppState>(context);
    final activeStd = appState.activeStandardNumber;
    final board = appState.selectedBoard;
    final moduleTitle = appState.selectedModuleTitle;
    final streamName = (activeStd == 11 || activeStd == 12) ? appState.selectedStreamName : null;

    final bannerText = streamName != null && streamName.isNotEmpty
        ? '$board • ${appState.selectedMediumName} • $streamName • $moduleTitle'
        : '$board • ${appState.selectedMediumName} • $moduleTitle';

    return Scaffold(
      appBar: AppBar(
        title: Text(
          '$moduleTitle - Standard $activeStd',
          style: const TextStyle(
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
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 12.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: AppColors.lightBlueBg,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  bannerText,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primaryBlue,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Expanded(
                child: FutureBuilder<List<SubjectModel>>(
                  future: _subjectsFuture,
                  builder: (context, snapshot) {
                    if (snapshot.connectionState == ConnectionState.waiting) {
                      return const Center(
                        child: CircularProgressIndicator(color: AppColors.primaryBlue),
                      );
                    }

                    final subjects = snapshot.data ?? [];

                    if (subjects.isEmpty) {
                      return RefreshIndicator(
                        onRefresh: _refresh,
                        color: AppColors.primaryBlue,
                        child: ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: [
                            const SizedBox(height: 80),
                            Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(
                                    Icons.menu_book_outlined,
                                    size: 56,
                                    color: AppColors.textMuted,
                                  ),
                                  const SizedBox(height: 12),
                                  const Text(
                                    'No Subjects Found',
                                    style: TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.textPrimary,
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    'No subjects found for Standard $activeStd (${appState.selectedMediumName}).\nAdd them in the Admin Panel to display here.',
                                    textAlign: TextAlign.center,
                                    style: const TextStyle(
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
                          ],
                        ),
                      );
                    }

                    return RefreshIndicator(
                      onRefresh: _refresh,
                      color: AppColors.primaryBlue,
                      child: ListView.builder(
                        physics: const AlwaysScrollableScrollPhysics(),
                        itemCount: subjects.length,
                        itemBuilder: (context, index) {
                          final subject = subjects[index];
                          return SubjectTile(
                            title: subject.name,
                            icon: subject.icon,
                            onTap: () {
                              appState.selectSubject(subject);
                              Navigator.pushNamed(context, '/chapters');
                            },
                          );
                        },
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
