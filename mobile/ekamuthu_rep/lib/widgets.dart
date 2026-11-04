import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import 'config.dart';
import 'models.dart';

final _money = NumberFormat('#,##0.00');

String money(double v) => _money.format(v);

String ymd(DateTime d) => DateFormat('yyyy-MM-dd').format(d);

void showMessage(BuildContext context, String message, {bool error = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(
      content: Text(message),
      backgroundColor: error ? Colors.red.shade700 : null,
    ));
}

Color lateColor(int days) {
  if (days > 90) return Colors.red.shade700;
  if (days > 30) return Colors.orange.shade800;
  return Colors.blue.shade700;
}

class LateBadge extends StatelessWidget {
  final String text;
  final int days;

  const LateBadge({super.key, required this.text, required this.days});

  @override
  Widget build(BuildContext context) {
    final color = lateColor(days);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(12)),
      child: Text('$text late', style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}

class StatusChip extends StatelessWidget {
  final String status;
  final String label;

  const StatusChip({super.key, required this.status, required this.label});

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'no_proof' => Colors.orange.shade800,
      'rejected' => Colors.red.shade700,
      'verified' => Colors.green.shade700,
      _ => Colors.blue.shade700,
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(12)),
      child: Text(label, style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}

class StatTile extends StatelessWidget {
  final String label;
  final String value;
  final Color? color;

  const StatTile({super.key, required this.label, required this.value, this.color});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: color)),
            ),
            const SizedBox(height: 2),
            Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
          ],
        ),
      ),
    );
  }
}

class ErrorRetry extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const ErrorRetry({super.key, required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return ListView(
      // Scrollable so pull-to-refresh still works on the error state
      padding: const EdgeInsets.all(32),
      children: [
        Icon(Icons.cloud_off, size: 48, color: Colors.grey.shade500),
        const SizedBox(height: 12),
        Text(message, textAlign: TextAlign.center),
        const SizedBox(height: 12),
        Center(child: FilledButton.tonal(onPressed: onRetry, child: const Text('Try again'))),
      ],
    );
  }
}

class SectionTitle extends StatelessWidget {
  final String text;
  final Color? color;

  const SectionTitle(this.text, {super.key, this.color});

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(4, 16, 4, 8),
        child: Text(text, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: color)),
      );
}

class ProofThumb extends StatelessWidget {
  final String path;

  const ProofThumb(this.path, {super.key});

  @override
  Widget build(BuildContext context) {
    final url = '$siteBase$path';
    return GestureDetector(
      onTap: () => showDialog(
        context: context,
        builder: (_) => Dialog(child: InteractiveViewer(child: Image.network(url))),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(6),
        child: Image.network(url, width: 64, height: 64, fit: BoxFit.cover,
            errorBuilder: (_, _, _) => const SizedBox(width: 64, height: 64, child: Icon(Icons.broken_image))),
      ),
    );
  }
}

// Camera or gallery; photos are resized so uploads stay small on mobile data
Future<File?> pickProofPhoto(BuildContext context) async {
  final source = await showModalBottomSheet<ImageSource>(
    context: context,
    builder: (sheetContext) => SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: const Icon(Icons.photo_camera),
            title: const Text('Take photo'),
            onTap: () => Navigator.pop(sheetContext, ImageSource.camera),
          ),
          ListTile(
            leading: const Icon(Icons.photo_library),
            title: const Text('Choose from gallery'),
            onTap: () => Navigator.pop(sheetContext, ImageSource.gallery),
          ),
        ],
      ),
    ),
  );
  if (source == null) return null;
  final picked = await ImagePicker().pickImage(source: source, maxWidth: 1600, maxHeight: 1600, imageQuality: 75);
  return picked == null ? null : File(picked.path);
}

class CollectionTile extends StatelessWidget {
  final Collection collection;
  final bool showCustomer;
  final VoidCallback? onAddProof;

  const CollectionTile({super.key, required this.collection, this.showCustomer = false, this.onAddProof});

  @override
  Widget build(BuildContext context) {
    final c = collection;
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      shape: c.needsProof
          ? RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12), side: BorderSide(color: Colors.orange.shade300))
          : null,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text('Rs. ${money(c.amount)}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                ),
                StatusChip(status: c.status, label: c.statusLabel),
              ],
            ),
            if (showCustomer) Text('${c.customerName} · Bill #${c.billNumber}'),
            Text(
              [c.collectedAt.length >= 16 ? c.collectedAt.substring(0, 16) : c.collectedAt, c.methodLabel, if (c.reference.isNotEmpty) c.reference]
                  .join(' · '),
              style: TextStyle(color: Colors.grey.shade700, fontSize: 13),
            ),
            if (c.adminRemark.isNotEmpty) Text('Office: ${c.adminRemark}', style: const TextStyle(fontSize: 13)),
            if (c.proofImage != null) Padding(padding: const EdgeInsets.only(top: 8), child: ProofThumb(c.proofImage!)),
            if (c.needsProof && onAddProof != null)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: FilledButton.icon(
                  style: FilledButton.styleFrom(backgroundColor: Colors.orange.shade700),
                  onPressed: onAddProof,
                  icon: const Icon(Icons.add_a_photo),
                  label: const Text('Add Proof'),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class VisitTile extends StatelessWidget {
  final Visit visit;
  final bool showCustomer;

  const VisitTile({super.key, required this.visit, this.showCustomer = false});

  @override
  Widget build(BuildContext context) {
    final v = visit;
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text(v.outcomeLabel, style: const TextStyle(fontWeight: FontWeight.bold))),
                Text(v.visitAt.length >= 16 ? v.visitAt.substring(0, 16) : v.visitAt,
                    style: TextStyle(color: Colors.grey.shade700, fontSize: 12)),
              ],
            ),
            if (showCustomer) Text('${v.customerName} · Bill #${v.billNumber}'),
            if (v.promisedDate.isNotEmpty) Text('Promised: ${v.promisedDate}'),
            if (v.remark.isNotEmpty) Text(v.remark, style: const TextStyle(fontSize: 13)),
            if (!showCustomer && v.repName.isNotEmpty)
              Text(v.repName, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
          ],
        ),
      ),
    );
  }
}
