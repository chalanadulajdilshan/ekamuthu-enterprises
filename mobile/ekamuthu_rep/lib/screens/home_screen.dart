import 'package:flutter/material.dart';

import 'bills_screen.dart';
import 'route_screen.dart';
import 'summary_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    // Screens are rebuilt on each tab switch so numbers are always fresh
    final Widget body = switch (_tab) {
      0 => const RouteScreen(),
      1 => BillsScreen(onOpenSummary: () => setState(() => _tab = 2)),
      _ => const SummaryScreen(),
    };
    return Scaffold(
      body: body,
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.route), label: 'My Route'),
          NavigationDestination(icon: Icon(Icons.list_alt), label: 'My Bills'),
          NavigationDestination(icon: Icon(Icons.insights), label: 'Day Summary'),
        ],
      ),
    );
  }
}
