// PHP sends numbers as int, float or numeric strings; normalise them here.
double toDouble(dynamic v) {
  if (v is num) return v.toDouble();
  return double.tryParse('${v ?? ''}') ?? 0;
}

int toInt(dynamic v) {
  if (v is num) return v.toInt();
  return int.tryParse('${v ?? ''}') ?? 0;
}

String toStr(dynamic v) => v == null ? '' : '$v';

class Rep {
  final int id;
  final String code;
  final String name;

  Rep({required this.id, required this.code, required this.name});

  factory Rep.fromJson(Map<String, dynamic> j) =>
      Rep(id: toInt(j['id']), code: toStr(j['code']), name: toStr(j['name']));

  Map<String, dynamic> toJson() => {'id': id, 'code': code, 'name': name};
}

class Option {
  final String value;
  final String label;

  Option(this.value, this.label);

  factory Option.fromJson(Map<String, dynamic> j) => Option(toStr(j['value']), toStr(j['label']));

  Map<String, dynamic> toJson() => {'value': value, 'label': label};
}

class Meta {
  final List<Option> visitOutcomes;
  final List<Option> paymentMethods;

  Meta({required this.visitOutcomes, required this.paymentMethods});

  factory Meta.empty() => Meta(visitOutcomes: [], paymentMethods: []);

  factory Meta.fromJson(Map<String, dynamic> j) => Meta(
        visitOutcomes: (j['visit_outcomes'] as List? ?? []).map((e) => Option.fromJson(e)).toList(),
        paymentMethods: (j['payment_methods'] as List? ?? []).map((e) => Option.fromJson(e)).toList(),
      );

  Map<String, dynamic> toJson() => {
        'visit_outcomes': visitOutcomes.map((e) => e.toJson()).toList(),
        'payment_methods': paymentMethods.map((e) => e.toJson()).toList(),
      };
}

class LastVisit {
  final String visitAt;
  final String outcome;
  final String remark;

  LastVisit({required this.visitAt, required this.outcome, required this.remark});

  static LastVisit? fromJson(dynamic j) => j is Map<String, dynamic>
      ? LastVisit(visitAt: toStr(j['visit_at']), outcome: toStr(j['outcome']), remark: toStr(j['remark']))
      : null;
}

class BillSummary {
  final int rentId;
  final String billNumber;
  final String customerName;
  final String customerCode;
  final String mobileNumber;
  final String location;
  final String dueDate;
  final int overdueDays;
  final String lateText;
  final int itemCount;
  final double pendingQty;
  final double outstanding;
  final bool visitedToday;
  final LastVisit? lastVisit;

  BillSummary.fromJson(Map<String, dynamic> j)
      : rentId = toInt(j['rent_id']),
        billNumber = toStr(j['bill_number']),
        customerName = toStr(j['customer_name']),
        customerCode = toStr(j['customer_code']),
        mobileNumber = toStr(j['mobile_number']),
        location = toStr(j['location']),
        dueDate = toStr(j['due_date']),
        overdueDays = toInt(j['overdue_days']),
        lateText = toStr(j['late_text']),
        itemCount = toInt(j['item_count']),
        pendingQty = toDouble(j['pending_qty']),
        outstanding = toDouble(j['outstanding']),
        visitedToday = j['visited_today'] == true,
        lastVisit = LastVisit.fromJson(j['last_visit']);

  String get searchText => '$billNumber $customerName $customerCode $location $mobileNumber'.toLowerCase();
}

class TodayStats {
  final int assigned;
  final int visited;
  final double collected;
  final int noProofCount;
  final double noProofAmount;

  TodayStats.fromJson(Map<String, dynamic> j)
      : assigned = toInt(j['assigned']),
        visited = toInt(j['visited']),
        collected = toDouble(j['collected']),
        noProofCount = toInt(j['no_proof_count']),
        noProofAmount = toDouble(j['no_proof_amount']);
}

class BillItem {
  final String item;
  final String subEquipment;
  final double pendingQty;

  BillItem.fromJson(Map<String, dynamic> j)
      : item = toStr(j['item']),
        subEquipment = toStr(j['sub_equipment']),
        pendingQty = toDouble(j['pending_qty']);
}

class BillInfo {
  final String customerName;
  final String customerCode;
  final List<String> phones;
  final String location;
  final String billDate;
  final String dueDate;
  final int overdueDays;
  final String lateText;
  final double paidTotal;
  final double projectedOutstanding;
  final double recordedOutstanding;
  final List<BillItem> items;

  BillInfo.fromJson(Map<String, dynamic> j)
      : customerName = toStr(j['customer_name']),
        customerCode = toStr(j['customer_code']),
        phones = (j['phones'] as List? ?? []).map((e) => toStr(e)).toList(),
        location = toStr(j['location']),
        billDate = toStr(j['bill_date']),
        dueDate = toStr(j['due_date']),
        overdueDays = toInt(j['overdue_days']),
        lateText = toStr(j['late_text']),
        paidTotal = toDouble(j['paid_total']),
        projectedOutstanding = toDouble(j['projected_outstanding']),
        recordedOutstanding = toDouble(j['recorded_outstanding']),
        items = (j['items'] as List? ?? []).map((e) => BillItem.fromJson(e)).toList();
}

class Collection {
  final int id;
  final int rentId;
  final String billNumber;
  final String customerName;
  final double amount;
  final String methodLabel;
  final String reference;
  final String status;
  final String statusLabel;
  final String? proofImage;
  final String adminRemark;
  final String collectedAt;

  Collection.fromJson(Map<String, dynamic> j)
      : id = toInt(j['id']),
        rentId = toInt(j['rent_id']),
        billNumber = toStr(j['bill_number']),
        customerName = toStr(j['customer_name']),
        amount = toDouble(j['amount']),
        methodLabel = toStr(j['method_label']),
        reference = toStr(j['reference']),
        status = toStr(j['status']),
        statusLabel = toStr(j['status_label']),
        proofImage = j['proof_image'] == null ? null : toStr(j['proof_image']),
        adminRemark = toStr(j['admin_remark']),
        collectedAt = toStr(j['collected_at']);

  bool get needsProof => status == 'no_proof';
}

class Visit {
  final String billNumber;
  final String customerName;
  final String repName;
  final String visitAt;
  final String outcomeLabel;
  final String promisedDate;
  final String remark;

  Visit.fromJson(Map<String, dynamic> j)
      : billNumber = toStr(j['bill_number']),
        customerName = toStr(j['customer_name']),
        repName = toStr(j['rep_name']),
        visitAt = toStr(j['visit_at']),
        outcomeLabel = toStr(j['outcome_label']),
        promisedDate = toStr(j['promised_date']),
        remark = toStr(j['remark']);
}

class BillDetail {
  final int rentId;
  final String billNumber;
  final BillInfo? bill;
  final List<Collection> myCollections;
  final List<Visit> visits;

  BillDetail.fromJson(Map<String, dynamic> j)
      : rentId = toInt(j['rent_id']),
        billNumber = toStr(j['bill_number']),
        bill = j['bill'] is Map<String, dynamic> ? BillInfo.fromJson(j['bill']) : null,
        myCollections = (j['my_collections'] as List? ?? []).map((e) => Collection.fromJson(e)).toList(),
        visits = (j['visits'] as List? ?? []).map((e) => Visit.fromJson(e)).toList();
}

class DaySummary {
  final String date;
  final double collected;
  final double confirmed;
  final int noProofCount;
  final double noProofAmount;
  final List<Collection> collections;
  final List<Visit> visits;

  DaySummary.fromJson(Map<String, dynamic> j)
      : date = toStr(j['date']),
        collected = toDouble(j['totals']?['collected']),
        confirmed = toDouble(j['totals']?['confirmed']),
        noProofCount = toInt(j['totals']?['no_proof_count']),
        noProofAmount = toDouble(j['totals']?['no_proof_amount']),
        collections = (j['collections'] as List? ?? []).map((e) => Collection.fromJson(e)).toList(),
        visits = (j['visits'] as List? ?? []).map((e) => Visit.fromJson(e)).toList();
}
