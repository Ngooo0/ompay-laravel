import 'dart:io';
import 'package:args/command_runner.dart';
import '../lib/commands/auth_command.dart';
import '../lib/commands/transaction_command.dart';
import '../lib/commands/balance_command.dart';

void main(List<String> arguments) {
  final runner = CommandRunner('ompay', 'OMPAY CLI - Système de paiement')
    ..addCommand(AuthCommand())
    ..addCommand(TransactionCommand())
    ..addCommand(BalanceCommand());

  runner.run(arguments).catchError((error) {
    print('❌ Erreur: $error');
    exit(1);
  });
}
