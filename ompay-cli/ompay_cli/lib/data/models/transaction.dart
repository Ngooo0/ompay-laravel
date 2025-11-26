class Transaction {
  final int id;
  final String uuid;
  final int clientId;
  final int? beneficiaryId;
  final double amount;
  final String type;
  final String description;
  final String status;
  final String? clientPhone;
  final DateTime createdAt;

  Transaction({
    required this.id,
    required this.uuid,
    required this.clientId,
    this.beneficiaryId,
    required this.amount,
    required this.type,
    required this.description,
    required this.status,
    this.clientPhone,
    required this.createdAt,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) {
    return Transaction(
      id: json['id'],
      uuid: json['uuid'],
      clientId: json['client_id'],
      beneficiaryId: json['beneficiary_id'],
      amount: (json['amount'] as num).toDouble(),
      type: json['type'],
      description: json['description'],
      status: json['status'],
      clientPhone: json['client_phone'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'uuid': uuid,
      'client_id': clientId,
      'beneficiary_id': beneficiaryId,
      'amount': amount,
      'type': type,
      'description': description,
      'status': status,
      'client_phone': clientPhone,
      'created_at': createdAt.toIso8601String(),
    };
  }
}