import 'package:flutter/material.dart';

import 'bills_screen.dart';
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
    return Scaffold(
      // Screens are rebuilt on each tab switch so numbers are always fresh
      body: _tab == 0 ? BillsScreen(onOpenSummary: () => setState(() => _tab = 1)) : const SummaryScreen(),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.list_alt), label: 'My Bills'),
          NavigationDestination(icon: Icon(Icons.insights), label: 'Day Summary'),
        ],
      ),
    );
  }
}
