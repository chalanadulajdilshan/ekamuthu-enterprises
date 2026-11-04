import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../api.dart';
import '../session.dart';
import '../widgets.dart';

class PaymentScreen extends StatefulWidget {
  final int rentId;
  final String billNumber;

  const PaymentScreen({super.key, required this.rentId, required this.billNumber});

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amount = TextEditingController();
  final _reference = TextEditingController();
  final _remark = TextEditingController();
  String? _method;
  File? _proof;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final methods = Session.instance.meta.paymentMethods;
    _method = methods.isNotEmpty ? methods.first.value : null;
  }

  @override
  void dispose() {
    _amount.dispose();
    _reference.dispose();
    _remark.dispose();
    super.dispose();
  }

  Future<void> _pickProof() async {
    final photo = await pickProofPhoto(context);
    if (photo != null) setState(() => _proof = photo);
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    if (_proof == null) {
      final ok = await showDialog<bool>(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Save without proof?'),
          content: const Text('This payment will NOT be confirmed until you add a proof photo.'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: const Text('Add proof')),
            FilledButton(onPressed: () => Navigator.pop(dialogContext, true), child: const Text('Save anyway')),
          ],
        ),
      );
      if (ok != true) {
        if (ok == false) _pickProof();
        return;
      }
    }

    setState(() => _saving = true);
    try {
      final message = await Api.savePayment({
        'rent_id': '${widget.rentId}',
        'amount': _amount.text.trim(),
        'payment_method': _method!,
        'reference': _reference.text.trim(),
        'remark': _remark.text.trim(),
      }, _proof);
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
      appBar: AppBar(title: Text('Payment · Bill #${widget.billNumber}')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(
              controller: _amount,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}'))],
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              decoration: const InputDecoration(labelText: 'Amount', prefixText: 'Rs. '),
              validator: (v) => (double.tryParse(v ?? '') ?? 0) <= 0 ? 'Please enter the amount' : null,
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              initialValue: _method,
              decoration: const InputDecoration(labelText: 'Method'),
              items: Session.instance.meta.paymentMethods
                  .map((o) => DropdownMenuItem(value: o.value, child: Text(o.label)))
                  .toList(),
              onChanged: (v) => setState(() => _method = v),
              validator: (v) => v == null ? 'Please select the payment method' : null,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _reference,
              maxLength: 100,
              decoration: const InputDecoration(labelText: 'Reference / Cheque No (optional)'),
            ),
            const SizedBox(height: 8),
            const Text('Proof photo (receipt / slip / cheque)', style: TextStyle(fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            if (_proof != null)
              Stack(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.file(_proof!, height: 200, width: double.infinity, fit: BoxFit.cover),
                  ),
                  Positioned(
                    right: 4,
                    top: 4,
                    child: IconButton.filledTonal(
                      icon: const Icon(Icons.close),
                      onPressed: () => setState(() => _proof = null),
                    ),
                  ),
                ],
              ),
            OutlinedButton.icon(
              style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(48)),
              onPressed: _pickProof,
              icon: const Icon(Icons.add_a_photo),
              label: Text(_proof == null ? 'Add proof photo' : 'Change photo'),
            ),
            if (_proof == null)
              Padding(
                padding: const EdgeInsets.only(top: 6),
                child: Text('Without a proof photo the payment is saved as NOT confirmed.',
                    style: TextStyle(color: Colors.red.shade700, fontSize: 13)),
              ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _remark,
              maxLength: 255,
              decoration: const InputDecoration(labelText: 'Remark (optional)'),
            ),
            const SizedBox(height: 16),
            FilledButton(
              style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52), backgroundColor: Colors.green.shade700),
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Text('Save Payment', style: TextStyle(fontSize: 16)),
            ),
          ],
        ),
      ),
    );
  }
}
