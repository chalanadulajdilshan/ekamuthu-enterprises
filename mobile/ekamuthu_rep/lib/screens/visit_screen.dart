import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';

import '../api.dart';
import '../session.dart';
import '../widgets.dart';

class VisitScreen extends StatefulWidget {
  final int rentId;
  final String billNumber;

  const VisitScreen({super.key, required this.rentId, required this.billNumber});

  @override
  State<VisitScreen> createState() => _VisitScreenState();
}

class _VisitScreenState extends State<VisitScreen> {
  final _formKey = GlobalKey<FormState>();
  final _remark = TextEditingController();
  String? _outcome;
  DateTime? _promisedDate;
  Position? _position;
  String _gpsStatus = 'Getting location...';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _captureLocation();
  }

  @override
  void dispose() {
    _remark.dispose();
    super.dispose();
  }

  // GPS is optional; the visit is saved even without it
  Future<void> _captureLocation() async {
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        setState(() => _gpsStatus = 'Location is turned off (visit will still be saved).');
        return;
      }
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
        if (mounted) setState(() => _gpsStatus = 'Location permission not given (visit will still be saved).');
        return;
      }
      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.high, timeLimit: Duration(seconds: 20)),
      );
      if (mounted) {
        setState(() {
          _position = pos;
          _gpsStatus = 'Location captured.';
        });
      }
    } catch (_) {
      if (mounted) setState(() => _gpsStatus = 'Location not available (visit will still be saved).');
    }
  }

  Future<void> _pickPromisedDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _promisedDate ?? now.add(const Duration(days: 1)),
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _promisedDate = picked);
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final message = await Api.saveVisit({
        'rent_id': '${widget.rentId}',
        'outcome': _outcome!,
        'remark': _remark.text.trim(),
        if (_outcome == 'promised' && _promisedDate != null) 'promised_date': ymd(_promisedDate!),
        if (_position != null) 'latitude': '${_position!.latitude}',
        if (_position != null) 'longitude': '${_position!.longitude}',
      });
      if (mounted) Navigator.pop(context, message);
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Visit · Bill #${widget.billNumber}')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            DropdownButtonFormField<String>(
              initialValue: _outcome,
              decoration: const InputDecoration(labelText: 'What happened?'),
              items: Session.instance.meta.visitOutcomes
                  .map((o) => DropdownMenuItem(value: o.value, child: Text(o.label)))
                  .toList(),
              onChanged: (v) => setState(() => _outcome = v),
              validator: (v) => v == null ? 'Please select what happened' : null,
            ),
            if (_outcome == 'promised') ...[
              const SizedBox(height: 16),
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(48)),
                onPressed: _pickPromisedDate,
                icon: const Icon(Icons.event),
                label: Text(_promisedDate == null ? 'Select promised date' : 'Promised date: ${ymd(_promisedDate!)}'),
              ),
            ],
            const SizedBox(height: 16),
            TextFormField(
              controller: _remark,
              maxLines: 4,
              decoration: const InputDecoration(
                labelText: 'Remark',
                hintText: 'What did the customer say? Were items returned?',
                alignLabelWithHint: true,
              ),
              validator: (v) => (v ?? '').trim().isEmpty ? 'Please add a remark about the visit' : null,
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(_position != null ? Icons.gps_fixed : Icons.gps_not_fixed, size: 16, color: Colors.grey.shade700),
                const SizedBox(width: 6),
                Expanded(child: Text(_gpsStatus, style: TextStyle(color: Colors.grey.shade700, fontSize: 13))),
              ],
            ),
            const SizedBox(height: 24),
            FilledButton(
              style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Text('Save Visit', style: TextStyle(fontSize: 16)),
            ),
          ],
        ),
      ),
    );
  }
}
