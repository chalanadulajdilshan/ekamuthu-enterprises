import 'package:flutter_test/flutter_test.dart';

import 'package:ekamuthu_rep/models.dart';

void main() {
  test('number helpers accept PHP ints, floats and numeric strings', () {
    expect(toDouble(5), 5.0);
    expect(toDouble('2500.50'), 2500.5);
    expect(toDouble(null), 0);
    expect(toInt('12'), 12);
  });

  test('bill summary parses API payload', () {
    final b = BillSummary.fromJson({
      'rent_id': 714,
      'bill_number': '477',
      'customer_name': 'A.M.S.K. CONSTRUCTION',
      'customer_code': 'CM/10/01024',
      'mobile_number': '0717912280',
      'location': 'MADAMPE',
      'due_date': '2020-03-02',
      'overdue_days': 2437,
      'late_text': '6 years 8 months 1 day',
      'item_count': 4,
      'pending_qty': 2200,
      'outstanding': 15252000,
      'visited_today': false,
      'last_visit': {'visit_at': '2026-11-03 15:45:12', 'outcome': 'Promised', 'remark': 'Next week'},
    });
    expect(b.rentId, 714);
    expect(b.outstanding, 15252000.0);
    expect(b.lastVisit?.outcome, 'Promised');
    expect(b.searchText.contains('madampe'), isTrue);
  });
}
