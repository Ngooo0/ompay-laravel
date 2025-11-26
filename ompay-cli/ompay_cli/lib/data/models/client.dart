class Client {
  final int id;
  final String nom;
  final String prenom;
  final String name;
  final String email;
  final String telephone;
  final double balance;

  Client({
    required this.id,
    required this.nom,
    required this.prenom,
    required this.name,
    required this.email,
    required this.telephone,
    required this.balance,
  });

  factory Client.fromJson(Map<String, dynamic> json) {
    return Client(
      id: json['id'],
      nom: json['nom'],
      prenom: json['prenom'],
      name: json['name'],
      email: json['email'],
      telephone: json['telephone'],
      balance: (json['balance'] as num).toDouble(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nom': nom,
      'prenom': prenom,
      'name': name,
      'email': email,
      'telephone': telephone,
      'balance': balance,
    };
  }
}