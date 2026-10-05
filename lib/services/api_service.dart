import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../config/app_config.dart';
import '../models/data_models.dart';

class ApiService {
  static String get baseUrl {
    if (AppConfig.customApiUrl.isNotEmpty) {
      return AppConfig.customApiUrl;
    }
    if (kIsWeb) {
      return 'http://localhost/Book_Banko/backend/api';
    } else if (defaultTargetPlatform == TargetPlatform.android) {
      return 'http://192.168.0.102/Book_Banko/backend/api';
    } else {
      return 'http://localhost/Book_Banko/backend/api';
    }
  }

  // 1. Fetch All Active Boards
  static Future<List<Map<String, dynamic>>> getBoards() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/get_boards.php'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          return List<Map<String, dynamic>>.from(data['data']);
        }
      }
    } catch (e) {
      debugPrint('Error fetching boards: $e');
    }
    return [];
  }

  // 2. Fetch Standards (6 to 12)
  static Future<List<StandardModel>> getStandards() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/get_standards.php'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          final List list = data['data'];
          return list.map((item) {
            return StandardModel(
              number: item['standard_number'] as int,
              name: item['name'] as String,
              requiresStream: item['requires_stream'] == true || item['requires_stream'] == 1,
            );
          }).toList();
        }
      }
    } catch (e) {
      debugPrint('Error fetching standards: $e');
    }
    return []; // Return only standards from Admin Panel API
  }

  // 3. Fetch Subjects by Board, Standard, and Stream
  static Future<List<SubjectModel>> getSubjects({
    required String boardCode,
    required int standardNumber,
    String? streamSlug,
  }) async {
    try {
      final queryParams = {
        'board_code': boardCode,
        'standard_number': standardNumber.toString(),
        if (streamSlug != null && streamSlug.isNotEmpty) 'stream_slug': streamSlug,
      };

      final uri = Uri.parse('$baseUrl/get_subjects.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          final List list = data['data'];
          return list.map((item) {
            return SubjectModel(
              id: item['code'] ?? item['id'].toString(),
              name: item['name'],
              icon: _mapIconStringToIconData(item['icon']),
              chapterCount: item['chapter_count'] ?? 10,
            );
          }).toList();
        }
      }
    } catch (e) {
      debugPrint('Error fetching subjects: $e');
    }
    return SubjectModel.standard9Subjects; // fallback
  }

  // 4. Fetch Chapters & PDF Material for a Subject
  static Future<List<ChapterModel>> getChapters({
    required int subjectId,
    int? moduleId,
  }) async {
    try {
      final queryParams = {
        'subject_id': subjectId.toString(),
        if (moduleId != null) 'module_id': moduleId.toString(),
      };

      final uri = Uri.parse('$baseUrl/get_chapters.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          final List list = data['data'];
          return list.map((item) {
            return ChapterModel(
              number: item['chapter_number'] as int,
              title: item['title'] as String,
              pdfUrl: item['full_pdf_url'] ?? item['pdf_file_path'] ?? '',
              pageCount: item['page_count'] as int? ?? 30,
            );
          }).toList();
        }
      }
    } catch (e) {
      debugPrint('Error fetching chapters: $e');
    }
    return ChapterModel.mathChapters; // fallback
  }

  // 5. Fetch App Config, Notice Banners, and Version Info
  static Future<Map<String, dynamic>?> getAppConfig() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/get_app_config.php'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          return Map<String, dynamic>.from(data['data']);
        }
      }
    } catch (e) {
      debugPrint('Error fetching app config: $e');
    }
    return null;
  }

  // 6. Fetch Extra Material PDFs
  static Future<List<ExtraMaterialModel>> getExtraMaterials({
    String categorySlug = 'extra_material',
    String? boardCode,
    String? mediumCode,
    int? standardNumber,
    int? subjectId,
    String? search,
  }) async {
    try {
      final queryParams = <String, String>{};
      if (categorySlug.isNotEmpty) queryParams['category_slug'] = categorySlug;
      if (boardCode != null && boardCode.isNotEmpty) queryParams['board_code'] = boardCode;
      if (mediumCode != null && mediumCode.isNotEmpty) queryParams['medium_code'] = mediumCode;
      if (standardNumber != null && standardNumber > 0) queryParams['standard_number'] = standardNumber.toString();
      if (subjectId != null && subjectId > 0) queryParams['subject_id'] = subjectId.toString();
      if (search != null && search.isNotEmpty) queryParams['search'] = search;

      final uri = Uri.parse('$baseUrl/get_extra_materials.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          final List list = data['data'];
          return list.map((item) => ExtraMaterialModel.fromJson(item)).toList();
        }
      }
    } catch (e) {
      debugPrint('Error fetching extra materials: $e');
    }
    return [];
  }

  // 7. Fetch Study Material Products & Stationery
  static Future<List<StudyProductModel>> getStudyProducts({
    String? search,
    String? tag,
  }) async {
    try {
      final queryParams = <String, String>{};
      if (search != null && search.isNotEmpty) queryParams['search'] = search;
      if (tag != null && tag.isNotEmpty) queryParams['tag'] = tag;

      final uri = Uri.parse('$baseUrl/get_study_products.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          final List list = data['data'];
          return list.map((item) => StudyProductModel.fromJson(item)).toList();
        }
      }
    } catch (e) {
      debugPrint('Error fetching study products: $e');
    }
    return [];
  }

  // Icon string mapping helper
  static dynamic _mapIconStringToIconData(String? iconName) {
    // Default fallback icon
    return null;
  }
}
