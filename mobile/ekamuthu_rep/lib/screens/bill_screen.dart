import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../models.dart';
import '../widgets.dart';
import 'payment_screen.dart';
import 'visit_screen.dart';

class BillScreen extends StatefulWidget {
  final int rentId;

  const BillScreen({super.key, required this.rentId});

  @override
  State<BillScreen> createState() => _BillScreenState();
}

class _BillScreenState extends State<BillScreen> {
  BillDetail? _detail;
  String? _error;
  bool _uploading = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final detail = await Api.bill(widget.rentId);
      if (!mounted) return;
      setState(() {
        _detail = detail;
        _error = null;
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    }
  }

  Future<void> _open(Uri uri) async {
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication) && mounted) {
      showMessage(context, 'Cannot open $uri', error: true);
    }
  }

  // Opens a form screen; on save it returns the server message
  Future<void> _openForm(Widget screen) async {
    final message = await Navigator.push<String>(context, MaterialPageRoute(builder: (_) => screen));
    if (message != null && mounted) {
      showMessage(context, message);
      _load();
    }
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
      appBar: AppBar(title: Text(_detail != null ? 'Bill #${_detail!.billNumber}' : 'Bill')),
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

  Widget _body() {
    if (_error != null && _detail == null) return ErrorRetry(message: _error!, onRetry: _load);
    if (_detail == null) return const Center(child: CircularProgressIndicator());

    final d = _detail!;
    final b = d.bill;

    return ListView(
      padding: const EdgeInsets.all(12),
      children: [
        if (b == null)
          Card(
            color: Colors.green.shade50,
            child: const ListTile(
              leading: Icon(Icons.check_circle, color: Colors.green),
              title: Text('All items on this bill have been returned. Nothing is overdue.'),
            ),
          )
        else
          _infoCard(b),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: FilledButton.icon(
                style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
                onPressed: () => _openForm(VisitScreen(rentId: d.rentId, billNumber: d.billNumber)),
                icon: const Icon(Icons.pin_drop),
                label: const Text('Record Visit'),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: FilledButton.icon(
                style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52), backgroundColor: Colors.green.shade700),
                onPressed: () => _openForm(PaymentScreen(rentId: d.rentId, billNumber: d.billNumber)),
                icon: const Icon(Icons.payments),
                label: const Text('Add Payment'),
              ),
            ),
          ],
        ),
        if (d.myCollections.isNotEmpty) ...[
          const SectionTitle('My payments on this bill'),
          ...d.myCollections.map((c) => CollectionTile(collection: c, onAddProof: () => _addProof(c))),
        ],
        if (d.visits.isNotEmpty) ...[
          const SectionTitle('Visit history'),
          ...d.visits.map((v) => VisitTile(visit: v)),
        ],
      ],
    );
  }

  Widget _infoCard(BillInfo b) {
    Widget row(String label, String value, {Color? color}) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 2),
          child: Row(children: [
            Expanded(child: Text(label)),
            Text(value, style: TextStyle(fontWeight: FontWeight.bold, color: color)),
          ]),
        );

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(b.customerName, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            Text('${b.customerCode} · Bill date ${b.billDate}', style: TextStyle(color: Colors.grey.shade700)),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 4,
              children: [
                ...b.phones.map((p) => OutlinedButton.icon(
                      onPressed: () => _open(Uri(scheme: 'tel', path: p.replaceAll(RegExp(r'[^0-9+]'), ''))),
                      icon: const Icon(Icons.call, size: 18),
                      label: Text(p),
                    )),
                if (b.location.isNotEmpty)
                  OutlinedButton.icon(
                    onPressed: () => _open(Uri.https('www.google.com', '/maps/search/', {'api': '1', 'query': b.location})),
                    icon: const Icon(Icons.map, size: 18),
                    label: const Text('Map'),
                  ),
              ],
            ),
            if (b.location.isNotEmpty) Padding(padding: const EdgeInsets.only(top: 4), child: Text(b.location)),
            const Divider(height: 24),
            row('Due date', b.dueDate),
            row('Late', '${b.lateText} (${b.overdueDays} days)', color: lateColor(b.overdueDays)),
            row('Paid so far', 'Rs. ${money(b.paidTotal)}', color: Colors.green.shade700),
            row('Overdue rent (projected)', 'Rs. ${money(b.projectedOutstanding)}', color: Colors.red.shade700),
            if (b.recordedOutstanding > 0)
              row('Unpaid from returns', 'Rs. ${money(b.recordedOutstanding)}', color: Colors.red.shade700),
            const SizedBox(height: 8),
            const Text('Items not returned', style: TextStyle(fontWeight: FontWeight.w600)),
            ...b.items.map((it) => Text(
                  '• ${it.item}${it.subEquipment.isNotEmpty ? ' / ${it.subEquipment}' : ''} × ${_qty(it.pendingQty)}',
                  style: const TextStyle(fontSize: 13),
                )),
          ],
        ),
      ),
    );
  }

  String _qty(double q) => q == q.roundToDouble() ? q.toInt().toString() : q.toString();
}
