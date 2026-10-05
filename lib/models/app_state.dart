import 'package:flutter/foundation.dart';
import 'data_models.dart';

class BookBankoAppState extends ChangeNotifier {
  String _selectedBoard = 'GSEB';
  String _selectedMedium = 'english';
  String _selectedMediumName = 'English Medium';
  String _selectedModuleSlug = 'textbooks';
  String _selectedModuleTitle = 'Textbooks';
  final List<int> _myStandards = [];
  int _activeStandardNumber = 0;
  StreamType? _selectedStream = StreamType.science;
  StreamModel? _selectedStreamModel;
  String? _selectedStreamSlug;
  String? _selectedStreamName;
  SubjectModel? _selectedSubject;
  ChapterModel? _selectedChapter;

  String get selectedBoard => _selectedBoard;
  String get selectedMedium => _selectedMedium;
  String get selectedMediumName => _selectedMediumName;
  String get selectedModuleSlug => _selectedModuleSlug;
  String get selectedModuleTitle => _selectedModuleTitle;
  List<int> get myStandards => List.unmodifiable(_myStandards);
  int get activeStandardNumber => _activeStandardNumber;
  StreamType? get selectedStream => _selectedStream;
  StreamModel? get selectedStreamModel => _selectedStreamModel;
  String? get selectedStreamSlug => _selectedStreamSlug ?? (_selectedStreamModel?.slug ?? _selectedStream?.name.toLowerCase());
  String? get selectedStreamName => _selectedStreamName ?? (_selectedStreamModel?.name ?? _selectedStream?.displayName);
  SubjectModel? get selectedSubject => _selectedSubject;
  ChapterModel? get selectedChapter => _selectedChapter;

  void selectModule({required String slug, required String title}) {
    _selectedModuleSlug = slug;
    _selectedModuleTitle = title;
    notifyListeners();
  }

  void selectBoard(String board) {
    _selectedBoard = board;
    notifyListeners();
  }

  void selectMedium(String mediumCode, {String? mediumName}) {
    _selectedMedium = mediumCode;
    if (mediumName != null && mediumName.isNotEmpty) {
      _selectedMediumName = mediumName;
    } else {
      _selectedMediumName = '${mediumCode.substring(0, 1).toUpperCase()}${mediumCode.substring(1)} Medium';
    }
    notifyListeners();
  }

  // Pick a single standard (e.g. from SelectStandardScreen onboarding)
  void setSingleStandard(int number) {
    _myStandards.clear();
    _myStandards.add(number);
    _activeStandardNumber = number;
    notifyListeners();
  }

  void setActiveStandard(int number) {
    _activeStandardNumber = number;
    if (!_myStandards.contains(number)) {
      _myStandards.add(number);
    }
    notifyListeners();
  }

  bool addStandard(int number) {
    if (_myStandards.contains(number)) return false;
    _myStandards.add(number);
    _activeStandardNumber = number;
    notifyListeners();
    return true;
  }

  void removeStandard(int number) {
    if (_myStandards.length <= 1) return;
    _myStandards.remove(number);
    if (_activeStandardNumber == number) {
      _activeStandardNumber = _myStandards.first;
    }
    notifyListeners();
  }

  void toggleStandard(int number) {
    if (_myStandards.contains(number)) {
      if (_myStandards.length > 1) {
        removeStandard(number);
      }
    } else {
      addStandard(number);
    }
  }

  void selectStreamModel(StreamModel stream) {
    _selectedStreamModel = stream;
    _selectedStreamSlug = stream.slug;
    _selectedStreamName = stream.name;
    final lower = stream.slug.toLowerCase();
    if (lower.contains('sci')) {
      _selectedStream = StreamType.science;
    } else if (lower.contains('comm')) {
      _selectedStream = StreamType.commerce;
    } else if (lower.contains('art')) {
      _selectedStream = StreamType.arts;
    }
    notifyListeners();
  }

  void selectStream(StreamType stream) {
    _selectedStream = stream;
    _selectedStreamSlug = stream.name.toLowerCase();
    _selectedStreamName = stream.displayName;
    notifyListeners();
  }

  void selectSubject(SubjectModel subject) {
    _selectedSubject = subject;
    notifyListeners();
  }

  void selectChapter(ChapterModel chapter) {
    _selectedChapter = chapter;
    notifyListeners();
  }
}
