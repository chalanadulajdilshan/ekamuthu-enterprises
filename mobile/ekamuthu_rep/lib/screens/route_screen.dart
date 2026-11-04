import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../models.dart';
import '../widgets.dart';
import 'bill_screen.dart';

// Day route planned by the office: ordered stops, opened in Google Maps
class RouteScreen extends StatefulWidget {
  const RouteScreen({super.key});

  @override
  State<RouteScreen> createState() => _RouteScreenState();
}

class _RouteScreenState extends State<RouteScreen> {
  DateTime _date = DateTime.now();
  List<RouteStop>? _stops;
  String? _error;

  bool get _isToday => ymd(_date) == ymd(DateTime.now());

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final requested = ymd(_date);
    try {
      final stops = await Api.route(requested);
      // Ignore late responses after the date was changed again
      if (!mounted || requested != ymd(_date)) return;
      setState(() {
        _stops = stops;
        _error = null;
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    }
  }

  void _setDate(DateTime d) {
    setState(() {
      _date = d;
      _stops = null;
      _error = null;
    });
    _load();
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2024),
      lastDate: now.add(const Duration(days: 60)),
    );
    if (picked != null) _setDate(picked);
  }

  Future<void> _openMaps(Uri uri) async {
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication) && mounted) {
      showMessage(context, 'Cannot open Google Maps', error: true);
    }
  }

  // Directions from the current location; Maps takes ~10 stops per link, so long routes are split
  List<(String, Uri)> _routeLinks(List<RouteStop> stops) {
    final places = stops.where((s) => !s.visited && s.location.isNotEmpty).map((s) => '${s.location}, Sri Lanka').toList();
    final links = <(String, Uri)>[];
    for (var i = 0; i < places.length; i += 10) {
      final part = places.sublist(i, i + 10 > places.length ? places.length : i + 10);
      final label = places.length > 10 ? 'Stops ${i + 1}-${i + part.length}' : 'Start route in Google Maps';
      links.add((
        label,
        Uri.https('www.google.com', '/maps/dir/', {
          'api': '1',
          'travelmode': 'driving',
          'destination': part.last,
          if (part.length > 1) 'waypoints': part.sublist(0, part.length - 1).join('|'),
        }),
      ));
    }
    return links;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Route')),
      body: RefreshIndicator(onRefresh: _load, child: _body()),
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
            onPressed: () => _setDate(_date.add(const Duration(days: 1))),
          ),
        ],
      ),
    );
  }

  Widget _body() {
    final children = <Widget>[_dateBar(), const SizedBox(height: 8)];

    if (_error != null && _stops == null) {
      return ListView(padding: const EdgeInsets.all(12), children: [
        ...children,
        SizedBox(height: 300, child: ErrorRetry(message: _error!, onRetry: _load)),
      ]);
    }
    if (_stops == null) {
      return ListView(padding: const EdgeInsets.all(12), children: [
        ...children,
        const Padding(padding: EdgeInsets.all(48), child: Center(child: CircularProgressIndicator())),
      ]);
    }

    final stops = _stops!;
    if (stops.isEmpty) {
      children.add(Padding(
        padding: const EdgeInsets.all(32),
        child: Text('No route planned for this day.\nCheck "My Bills" for all your assigned bills.',
            textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade700)),
      ));
      return ListView(padding: const EdgeInsets.all(12), children: children);
    }

    final done = stops.where((s) => s.visited).length;
    final collected = stops.fold<double>(0, (sum, s) => sum + s.collected);
    final links = _routeLinks(stops);

    children.addAll([
      Row(children: [
        Expanded(child: StatTile(label: 'Stops', value: '${stops.length}')),
        const SizedBox(width: 8),
        Expanded(child: StatTile(label: 'Visited', value: '$done / ${stops.length}', color: Colors.green.shade700)),
        const SizedBox(width: 8),
        Expanded(child: StatTile(label: 'Collected', value: money(collected), color: Colors.green.shade700)),
      ]),
      const SizedBox(height: 8),
      ...links.map((l) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: FilledButton.icon(
              style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(48), backgroundColor: Colors.green.shade700),
              onPressed: () => _openMaps(l.$2),
              icon: const Icon(Icons.directions),
              label: Text(l.$1),
            ),
          )),
      if (links.isEmpty && done == stops.length)
        Card(
          color: Colors.green.shade50,
          child: const ListTile(leading: Icon(Icons.check_circle, color: Colors.green), title: Text('All stops visited')),
        ),
      if (links.isNotEmpty)
        Padding(
          padding: const EdgeInsets.only(bottom: 4),
          child: Text('Visited stops are left out of the map route.',
              style: TextStyle(color: Colors.grey.shade600, fontSize: 12), textAlign: TextAlign.center),
        ),
      ...stops.map(_stopCard),
    ]);

    return ListView(padding: const EdgeInsets.all(12), children: children);
  }

  Widget _stopCard(RouteStop s) {
    final numberColor = s.visited ? Colors.green.shade600 : Theme.of(context).colorScheme.primary;
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () async {
          if (!s.stillAssigned) {
            showMessage(context, 'This bill is no longer assigned to you.', error: true);
            return;
          }
          await Navigator.push(context, MaterialPageRoute(builder: (_) => BillScreen(rentId: s.rentId)));
          _load();
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 16,
                backgroundColor: numberColor,
                child: s.visited
                    ? const Icon(Icons.check, color: Colors.white, size: 18)
                    : Text('${s.stopNo}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(s.customerName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                    Text('Bill #${s.billNumber} · ${s.mobileNumber}', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                    if (s.location.isNotEmpty) Text(s.location, style: const TextStyle(fontSize: 13)),
                    const SizedBox(height: 4),
                    Wrap(
                      spacing: 8,
                      runSpacing: 4,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        if (s.lateText != null)
                          LateBadge(text: s.lateText!, days: s.overdueDays)
                        else
                          Text('All items returned', style: TextStyle(color: Colors.green.shade700, fontSize: 12)),
                        if (s.lateText != null)
                          Text('Rs. ${money(s.outstanding)}',
                              style: TextStyle(color: Colors.red.shade700, fontWeight: FontWeight.bold, fontSize: 13)),
                      ],
                    ),
                    ...s.visits.map((v) => Text(
                          '✓ ${v.visitAt.length >= 16 ? v.visitAt.substring(11, 16) : ''} ${v.outcome}',
                          style: TextStyle(color: Colors.green.shade700, fontSize: 13),
                        )),
                    if (s.collected > 0)
                      Text('Collected Rs. ${money(s.collected)}',
                          style: TextStyle(color: Colors.green.shade700, fontSize: 13, fontWeight: FontWeight.w600)),
                    if (!s.stillAssigned)
                      Text('No longer assigned to you', style: TextStyle(color: Colors.red.shade700, fontSize: 12)),
                  ],
                ),
              ),
              if (s.location.isNotEmpty)
                IconButton(
                  tooltip: 'Directions to this stop',
                  icon: const Icon(Icons.directions),
                  color: Theme.of(context).colorScheme.primary,
                  onPressed: () => _openMaps(Uri.https('www.google.com', '/maps/dir/', {
                    'api': '1',
                    'travelmode': 'driving',
                    'destination': '${s.location}, Sri Lanka',
                  })),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
