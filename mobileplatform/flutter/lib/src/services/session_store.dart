import 'package:shared_preferences/shared_preferences.dart';

class SessionStore {
  SessionStore._(this._prefs);

  static const _tokenKey = 'session_token';
  static const _displayNameKey = 'display_name';

  final SharedPreferences _prefs;

  static Future<SessionStore> create() async {
    final prefs = await SharedPreferences.getInstance();
    return SessionStore._(prefs);
  }

  String? get token => _prefs.getString(_tokenKey);

  String? get displayName => _prefs.getString(_displayNameKey);

  Future<void> saveSession({
    required String token,
    required String? displayName,
  }) async {
    await _prefs.setString(_tokenKey, token);
    if (displayName != null && displayName.trim().isNotEmpty) {
      await _prefs.setString(_displayNameKey, displayName.trim());
    } else {
      await _prefs.remove(_displayNameKey);
    }
  }

  Future<void> clear() async {
    await _prefs.remove(_tokenKey);
    await _prefs.remove(_displayNameKey);
  }
}
