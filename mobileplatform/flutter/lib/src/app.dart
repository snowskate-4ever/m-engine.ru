import 'package:flutter/material.dart';

import 'features/auth/login_page.dart';
import 'features/home/home_shell.dart';
import 'services/api_client.dart';
import 'services/session_store.dart';

class MEngineApp extends StatefulWidget {
  const MEngineApp({super.key});

  @override
  State<MEngineApp> createState() => _MEngineAppState();
}

class _MEngineAppState extends State<MEngineApp> {
  final _apiClient = ApiClient();

  SessionStore? _sessionStore;
  String? _token;
  String? _displayName;
  String? _authError;
  bool _loading = true;
  bool _loginInProgress = false;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final sessionStore = await SessionStore.create();
    final token = sessionStore.token;
    String? validToken;

    if (token != null && token.isNotEmpty) {
      final isValid = await _apiClient.hasValidSession(token);
      if (isValid) {
        validToken = token;
      } else {
        await sessionStore.clear();
      }
    }

    if (!mounted) {
      return;
    }

    setState(() {
      _sessionStore = sessionStore;
      _token = validToken;
      _displayName = validToken == null ? null : sessionStore.displayName;
      _loading = false;
    });
  }

  Future<void> _signIn(String email, String password) async {
    setState(() {
      _authError = null;
      _loginInProgress = true;
    });
    try {
      final result = await _apiClient.login(email: email, password: password);
      await _sessionStore!.saveSession(
        token: result.token,
        displayName: result.displayName,
      );
      if (!mounted) {
        return;
      }
      setState(() {
        _token = result.token;
        _displayName = result.displayName;
      });
    } catch (e) {
      if (!mounted) {
        return;
      }
      setState(() => _authError = '$e');
    } finally {
      if (mounted) {
        setState(() => _loginInProgress = false);
      }
    }
  }

  Future<void> _logout() async {
    await _sessionStore?.clear();
    if (!mounted) {
      return;
    }
    setState(() {
      _token = null;
      _displayName = null;
      _authError = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'M-Engine Flutter',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.indigo),
        useMaterial3: true,
      ),
      home: _loading || _sessionStore == null
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : _token == null
              ? LoginPage(
                  onSubmit: _signIn,
                  errorText: _authError,
                  isLoading: _loginInProgress,
                )
              : HomeShell(
                  apiClient: _apiClient,
                  token: _token!,
                  displayName: _displayName,
                  onLogout: _logout,
                ),
    );
  }
}
