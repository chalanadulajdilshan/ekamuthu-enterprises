import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import '../session.dart';
import '../widgets.dart';
import 'bill_screen.dart';

class BillsScreen extends StatefulWidget {
  final VoidCallback onOpenSummary;

  const BillsScreen({super.key, required this.onOpenSummary});

  @override
  State<BillsScreen> createState() => _BillsScreenState();
}

class _BillsScreenState extends State<BillsScreen> {
  List<BillSummary>? _bills;
  TodayStats? _today;
  String? _error;
  String _query = '';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final (bills, today) = await Api.bills();
      if (!mounted) return;
      setState(() {
        _bills = bills;
        _today = today;
        _error = null;
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    }
  }

  Future<void> _confirmLogout() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Log out?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(dialogContext, true), child: const Text('Log out')),
        ],
      ),
    );
    if (ok == true) await Api.logout();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('My Bills'),
            Text(Session.instance.rep?.name ?? '', style: const TextStyle(fontSize: 12)),
          ],
        ),
        actions: [IconButton(icon: const Icon(Icons.logout), tooltip: 'Log out', onPressed: _confirmLogout)],
      ),
      body: RefreshIndicator(onRefresh: _load, child: _body()),
    );
  }

  Widget _body() {
    if (_error != null && _bills == null) {
      return ErrorRetry(message: _error!, onRetry: _load);
    }
    if (_bills == null) {
      return const Center(child: CircularProgressIndicator());
    }

    final today = _today!;
    final q = _query.trim().toLowerCase();
    final bills = q.isEmpty ? _bills! : _bills!.where((b) => b.searchText.contains(q)).toList();

    return ListView(
      padding: const EdgeInsets.all(12),
      children: [
        Row(
          children: [
            Expanded(child: StatTile(label: 'Assigned', value: '${today.assigned}')),
            const SizedBox(width: 8),
            Expanded(child: StatTile(label: 'Visited today', value: '${today.visited}')),
            const SizedBox(width: 8),
            Expanded(
              child: StatTile(label: 'Collected today', value: money(today.collected), color: Colors.green.shade700),
            ),
          ],
        ),
        if (today.noProofCount > 0)
          Card(
            color: Colors.orange.shade50,
            margin: const EdgeInsets.only(top: 12),
            child: ListTile(
              leading: Icon(Icons.warning_amber, color: Colors.orange.shade800),
              title: Text('${today.noProofCount} payment(s) without proof (Rs. ${money(today.noProofAmount)})'),
              subtitle: const Text('Not counted as paid until you add proof. Tap to add.'),
              onTap: widget.onOpenSummary,
            ),
          ),
        const SizedBox(height: 12),
        TextField(
          decoration: const InputDecoration(
            hintText: 'Search customer, bill no, location...',
            prefixIcon: Icon(Icons.search),
            filled: true,
            fillColor: Colors.white,
            isDense: true,
          ),
          onChanged: (v) => setState(() => _query = v),
        ),
        const SizedBox(height: 12),
        if (bills.isEmpty)
          Padding(
            padding: const EdgeInsets.all(32),
            child: Text(
              _bills!.isEmpty ? 'No bills assigned to you right now.' : 'No bills match your search.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade700),
            ),
          ),
        ...bills.map(_billCard),
      ],
    );
  }

  Widget _billCard(BillSummary b) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () async {
          await Navigator.push(context, MaterialPageRoute(builder: (_) => BillScreen(rentId: b.rentId)));
          _load();
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(b.customerName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                  ),
                  LateBadge(text: b.lateText, days: b.overdueDays),
                ],
              ),
              const SizedBox(height: 2),
              Text('Bill #${b.billNumber} · ${b.mobileNumber}', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
              if (b.location.isNotEmpty)
                Row(
                  children: [
                    Icon(Icons.place, size: 14, color: Colors.grey.shade700),
                    const SizedBox(width: 2),
                    Expanded(child: Text(b.location, style: const TextStyle(fontSize: 13))),
                  ],
                ),
              const SizedBox(height: 4),
              Row(
                children: [
                  Expanded(
                    child: Text('${b.itemCount} item(s), qty ${_qty(b.pendingQty)} pending', style: const TextStyle(fontSize: 13)),
                  ),
                  Text('Rs. ${money(b.outstanding)}',
                      style: TextStyle(color: Colors.red.shade700, fontWeight: FontWeight.bold)),
                ],
              ),
              if (b.visitedToday)
                Text('✓ Visited today', style: TextStyle(color: Colors.green.shade700, fontSize: 13))
              else if (b.lastVisit != null)
                Text(
                  'Last visit ${b.lastVisit!.visitAt.substring(0, 10)}: ${b.lastVisit!.outcome}',
                  style: TextStyle(color: Colors.grey.shade700, fontSize: 13),
                ),
            ],
          ),
        ),
      ),
    );
  }

  String _qty(double q) => q == q.roundToDouble() ? q.toInt().toString() : q.toString();
}
