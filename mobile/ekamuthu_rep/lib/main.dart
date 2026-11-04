import 'package:flutter/material.dart';

import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'session.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Session.instance.load();
  runApp(const RepApp());
}

class RepApp extends StatelessWidget {
  const RepApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Ekamuthu Rep',
      debugShowCheckedModeBanner: false,
      navigatorKey: navigatorKey,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF3B5DE7)),
        scaffoldBackgroundColor: const Color(0xFFF3F4F8),
        appBarTheme: const AppBarTheme(backgroundColor: Color(0xFF3B5DE7), foregroundColor: Colors.white),
        cardTheme: CardThemeData(
          elevation: 0.5,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
        inputDecorationTheme: const InputDecorationTheme(border: OutlineInputBorder()),
      ),
      home: ValueListenableBuilder<bool>(
        valueListenable: Session.instance.loggedIn,
        builder: (_, loggedIn, _) => loggedIn ? const HomeScreen() : const LoginScreen(),
      ),
    );
  }
}
