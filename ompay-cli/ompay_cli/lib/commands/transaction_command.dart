import 'dart:io';
import 'package:args/command_runner.dart';
import '../data/services/api_service.dart';

class TransactionCommand extends Command {
  @override
  final name = 'transaction';
  @override
  final description = 'Gestion des transactions';

  TransactionCommand() {
    addSubcommand(DepositCommand());
    addSubcommand(WithdrawCommand());
    addSubcommand(TransferCommand());
    addSubcommand(PayCommand());
    addSubcommand(ListCommand());
  }
}

class DepositCommand extends Command {
  @override
  final name = 'deposit';
  @override
  final description = 'Dépôt d\'argent';

  DepositCommand() {
    argParser
      ..addOption('amount', abbr: 'a', mandatory: true, help: 'Montant à déposer');
  }

  @override
  Future<void> run() async {
    final amount = double.parse(argResults?['amount'] as String);

    final apiService = ApiService();

    try {
      print('💰 Dépôt de ${amount}FCFA en cours...');
      final response = await apiService.deposit(amount);

      if (response['status'] == 'success') {
        print('✅ Dépôt réussi !');
      } else {
        print('❌ Erreur: ${response['message']}');
        exit(1);
      }
    } catch (e) {
      print('❌ Erreur de dépôt: $e');
      exit(1);
    }
  }
}

class WithdrawCommand extends Command {
  @override
  final name = 'withdraw';
  @override
  final description = 'Retrait d\'argent';

  WithdrawCommand() {
    argParser
      ..addOption('amount', abbr: 'a', mandatory: true, help: 'Montant à retirer');
  }

  @override
  Future<void> run() async {
    final amount = double.parse(argResults?['amount'] as String);

    final apiService = ApiService();

    try {
      print('💸 Retrait de ${amount}FCFA en cours...');
      final response = await apiService.withdraw(amount);

      if (response['status'] == 'success') {
        print('✅ Retrait réussi !');
      } else {
        print('❌ Erreur: ${response['message']}');
        exit(1);
      }
    } catch (e) {
      print('❌ Erreur de retrait: $e');
      exit(1);
    }
  }
}

class TransferCommand extends Command {
  @override
  final name = 'transfer';
  @override
  final description = 'Transfert d\'argent';

  TransferCommand() {
    argParser
      ..addOption('to', abbr: 't', mandatory: true, help: 'Numéro du destinataire')
      ..addOption('amount', abbr: 'a', mandatory: true, help: 'Montant à transférer');
  }

  @override
  Future<void> run() async {
    final to = argResults?['to'] as String;
    final amount = double.parse(argResults?['amount'] as String);

    final apiService = ApiService();

    try {
      print('🔄 Transfert de ${amount}FCFA vers $to en cours...');
      final response = await apiService.transfer(to, amount);

      if (response['status'] == 'success') {
        print('✅ Transfert réussi !');
      } else {
        print('❌ Erreur: ${response['message']}');
        exit(1);
      }
    } catch (e) {
      print('❌ Erreur de transfert: $e');
      exit(1);
    }
  }
}

class PayCommand extends Command {
  @override
  final name = 'pay';
  @override
  final description = 'Paiement à un marchand';

  PayCommand() {
    argParser
      ..addOption('merchant', abbr: 'm', mandatory: true, help: 'Code du marchand')
      ..addOption('amount', abbr: 'a', mandatory: true, help: 'Montant à payer');
  }

  @override
  Future<void> run() async {
    final merchant = argResults?['merchant'] as String;
    final amount = double.parse(argResults?['amount'] as String);

    final apiService = ApiService();

    try {
      print('🛒 Paiement de ${amount}FCFA au marchand $merchant en cours...');
      final response = await apiService.payMerchant(merchant, amount);

      if (response['status'] == 'success') {
        print('✅ Paiement réussi !');
      } else {
        print('❌ Erreur: ${response['message']}');
        exit(1);
      }
    } catch (e) {
      print('❌ Erreur de paiement: $e');
      exit(1);
    }
  }
}

class ListCommand extends Command {
  @override
  final name = 'list';
  @override
  final description = 'Liste des transactions';

  ListCommand() {
    argParser
      ..addOption('limit', abbr: 'l', defaultsTo: '10', help: 'Nombre de transactions à afficher');
  }

  @override
  Future<void> run() async {
    final limit = int.parse(argResults?['limit'] as String);

    final apiService = ApiService();

    try {
      print('📋 Récupération des transactions...');
      final response = await apiService.getTransactions(limit: limit);

      if (response.containsKey('transactions')) {
        final transactions = response['transactions'] as List;
        print('\n📊 Vos ${transactions.length} dernières transactions:');
        print('─' * 80);

        for (var tx in transactions) {
          final type = tx['type'];
          final amount = tx['montant_envoye'];
          final to = tx['numero_destinataire'];
          final date = tx['date'];
          final time = tx['heure'];

          final icon = type == 'deposit' ? '💰' : type == 'withdraw' ? '💸' : '🔄';
          final action = type == 'deposit' ? 'Dépôt' : type == 'withdraw' ? 'Retrait' : 'Transfert';

          print('$icon $action: ${amount}FCFA → $to ($date $time)');
        }
      } else {
        print('❌ Erreur: ${response['message']}');
        exit(1);
      }
    } catch (e) {
      print('❌ Erreur de récupération: $e');
      exit(1);
    }
  }
}