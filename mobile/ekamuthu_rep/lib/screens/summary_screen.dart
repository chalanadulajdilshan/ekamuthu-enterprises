import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import '../widgets.dart';

class SummaryScreen extends StatefulWidget {
  const SummaryScreen({super.key});

  @override
  State<SummaryScreen> createState() => _SummaryScreenState();
}

class _SummaryScreenState extends State<SummaryScreen> {
  DateTime _date = DateTime.now();
  DaySummary? _summary;
  String? _error;
  bool _uploading = false;

  bool get _isToday => ymd(_date) == ymd(DateTime.now());

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final requested = ymd(_date);
    try {
      final summary = await Api.summary(requested);
      // Ignore late responses after the date was changed again
      if (!mounted || requested != ymd(_date)) return;
      setState(() {
        _summary = summary;
        _error = null;
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    }
  }

  void _setDate(DateTime d) {
    setState(() {
      _date = d;
      _summary = null;
      _error = null;
    });
    _load();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2024),
      lastDate: DateTime.now(),
    );
    if (picked != null) _setDate(picked);
  }

  Future<void> _addProof(Collection c) async {
    final photo = await pickProofPhoto(context);
    if (photo == null || !mounted) return;
    setState(() => _uploading = true);
    try {
      final message = await Api.addProof(c.id, photo);
      if (mounted) showMessage(context, message);
      await _load();
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Day Summary')),
      body: Stack(
        children: [
          RefreshIndicator(onRefresh: _load, child: _body()),
          if (_uploading)
            Container(
              color: Colors.black26,
              alignment: Alignment.center,
              child: const Card(
                child: Padding(
                  padding: EdgeInsets.all(20),
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    CircularProgressIndicator(),
                    SizedBox(width: 16),
                    Text('Uploading proof...'),
                  ]),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _dateBar() {
    return Card(
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.chevron_left),
            onPressed: () => _setDate(_date.subtract(const Duration(days: 1))),
          ),
          Expanded(
            child: TextButton.icon(
              onPressed: _pickDate,
              icon: const Icon(Icons.calendar_today, size: 18),
              label: Text(_isToday ? 'Today (${ymd(_date)})' : ymd(_date), style: const TextStyle(fontSize: 16)),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.chevron_right),
            onPressed: _isToday ? null : () => _setDate(_date.add(const Duration(days: 1))),
          ),
        ],
      ),
    );
  }

  Widget _body() {
    final children = <Widget>[_dateBar(), const SizedBox(height: 8)];

    if (_error != null && _summary == null) {
      return ListView(padding: const EdgeInsets.all(12), children: [
        ...children,
        SizedBox(height: 300, child: ErrorRetry(message: _error!, onRetry: _load)),
      ]);
    }
    if (_summary == null) {
      return ListView(padding: const EdgeInsets.all(12), children: [
        ...children,
        const Padding(padding: EdgeInsets.all(48), child: Center(child: CircularProgressIndicator())),
      ]);
    }

    final s = _summary!;
    final noProof = s.collections.where((c) => c.needsProof).toList();

    children.addAll([
      Row(children: [
        Expanded(child: StatTile(label: 'Visits', value: '${s.visits.length}')),
        const SizedBox(width: 8),
        Expanded(child: StatTile(label: 'Payments entered', value: '${s.collections.length}')),
      ]),
      const SizedBox(height: 8),
      Row(children: [
        Expanded(child: StatTile(label: 'Total collected (Rs.)', value: money(s.collected), color: Colors.green.shade700)),
        const SizedBox(width: 8),
        Expanded(child: StatTile(label: 'Confirmed with proof (Rs.)', value: money(s.confirmed), color: Colors.blue.shade700)),
      ]),
      const SizedBox(height: 8),
      StatTile(
        label: 'Without proof (not confirmed)',
        value: '${s.noProofCount} payment(s) · Rs. ${money(s.noProofAmount)}',
        color: s.noProofCount > 0 ? Colors.orange.shade800 : null,
      ),
    ]);

    if (noProof.isNotEmpty) {
      children.add(SectionTitle('Add proof for these payments', color: Colors.orange.shade800));
      children.addAll(noProof.map((c) => CollectionTile(collection: c, showCustomer: true, onAddProof: () => _addProof(c))));
    }

    children.add(const SectionTitle('Payments'));
    if (s.collections.isEmpty) {
      children.add(Text('No payments on this day.', style: TextStyle(color: Colors.grey.shade700)));
    }
    children.addAll(s.collections.where((c) => !c.needsProof).map((c) => CollectionTile(collection: c, showCustomer: true)));

    children.add(const SectionTitle('Visits'));
    if (s.visits.isEmpty) {
      children.add(Text('No visits on this day.', style: TextStyle(color: Colors.grey.shade700)));
    }
    children.addAll(s.visits.map((v) => VisitTile(visit: v, showCustomer: true)));

    return ListView(padding: const EdgeInsets.all(12), children: children);
  }
}
