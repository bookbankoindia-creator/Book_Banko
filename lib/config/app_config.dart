class AppConfig {
  // Production / Deployed Backend API Endpoint
  static const String customApiUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: '',
  );

  // Supabase Project REST & Storage URL
  static const String supabaseUrl = String.fromEnvironment(
    'SUPABASE_URL',
    defaultValue: 'https://rmwxhaxusmpuhwryseab.supabase.co',
  );

  static const String supabaseAnonKey = String.fromEnvironment(
    'SUPABASE_ANON_KEY',
    defaultValue: 'sb_publishable_yyGNxmGHxkJQHpwQCCjz5w_4NAYSEmi',
  );

  // Default candidate host endpoints for development
  static const List<String> defaultCandidateUrls = [
    'http://192.168.0.102/Book_Banko/backend/api',
    'http://localhost/Book_Banko/backend/api',
    'http://10.0.2.2/Book_Banko/backend/api',
    'http://127.0.0.1/Book_Banko/backend/api',
    'http://192.168.0.103/Book_Banko/backend/api',
  ];
}
