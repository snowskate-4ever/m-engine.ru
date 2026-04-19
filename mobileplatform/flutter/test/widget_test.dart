import 'package:flutter_test/flutter_test.dart';

import 'package:flutter/material.dart';
import 'package:m_engine_flutter/src/features/auth/login_page.dart';

void main() {
  testWidgets('renders login form', (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: LoginPage(
          onSubmit: (_, __) async {},
        ),
      ),
    );

    expect(find.text('Sign in'), findsWidgets);
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
  });
}
