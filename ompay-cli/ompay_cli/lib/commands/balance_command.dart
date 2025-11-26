import 'dart:io';
import 'package:args/command_runner.dart';
import '../data/services/api_service.dart';

class BalanceCommand extends Command {
  @override
  final name = 'balance';
  @override
  final description = 'Gestion du solde et informations compte';

  BalanceCommand() {
    addSubcommand(ShowCommand());
  }
}

class ShowCommand extends Command {
  @override
  final name = 'show';
  @override
  final description = 'Afficher le solde et les informations du compte';

  @override
  Future<void> run() async {
    final apiService = ApiService();

    try {
      print('📊 Récupération des informations du compte...');
      final response = await apiService.getBalance();

      if (response['status'] == 'success') {
        final client = response['client'];
        final balance = client['balance'];
        final nom = client['nom'];
        final prenom = client['prenom'];
        final telephone = client['telephone'];

        print('\n💳 Informations du compte:');
        print('─' * 40);
        print('👤 Nom: $nom $prenom');
        print('📞 Téléphone: $telephone');
        print('💰 Solde: ${balance}FCFA');
        print('─' * 40);

        if (response.containsKey('recent_transactions')) {
          final transactions = response['recent_transactions'] as List;
          if (transactions.isNotEmpty) {
            print('\n📈 Dernières transactions:');
            print('─' * 60);

            for (var tx in transactions.take(5)) {
              final type = tx['type'];
              final amount = tx['amount'];
              final description = tx['description'];
              final date = tx['created_at'];

              final icon = type == 'deposit' ? '💰' : type == 'withdraw' ? '💸' : '🔄';
              final sign = type == 'deposit' ? '+' : '-';

              print('$icon $sign${amount}FCFA - $description');
              print('   📅 ${DateTime.parse(date).toLocal()}');
              print('');
            }
          }
        }
      } else {
        print('❌ Erreur: ${response['message']}');
        exit(1);
      }
    } catch (e) {
      print('❌ Erreur de récupération: $e');
      print('💡 Assurez-vous d\'être connecté: ompay auth login --phone XXX --password XXX');
      exit(1);
    }
  }
}