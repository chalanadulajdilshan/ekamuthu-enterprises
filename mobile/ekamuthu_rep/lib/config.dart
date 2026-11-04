// Server address. Override for local testing, e.g.
// flutter run --dart-define=API_BASE=http://10.0.2.2/ekamuthu-enterprises/api/rep/
const String apiBase = String.fromEnvironment(
  'API_BASE',
  defaultValue: 'https://ekamuthu-enterprises.sourcecode.lk/api/rep/',
);

// Site root, used for proof image URLs (stored as paths like uploads/rep-collections/...)
final String siteBase = apiBase.replaceFirst(RegExp(r'api/rep/?$'), '');
