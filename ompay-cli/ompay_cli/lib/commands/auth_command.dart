import 'dart:io';
import 'package:args/command_runner.dart';
import '../data/services/api_service.dart';

class AuthCommand extends Command {
  @override
  final name = 'auth';
  @override
  final description = 'Gestion de l\'authentification';

  AuthCommand() {
    addSubcommand(LoginCommand());
    addSubcommand(VerifyOtpCommand());
    addSubcommand(LogoutCommand());
  }
}

class LoginCommand extends Command {
  @override
  final name = 'login';
  @override
  final description = 'Connexion avec téléphone et mot de passe';

  LoginCommand() {
    argParser
      ..addOption('phone', abbr: 'p', mandatory: true, help: 'Numéro de téléphone')
      ..addOption('password', abbr: 'w', mandatory: true, help: 'Mot de passe');
  }

  @override
  Future<void> run() async {
    final phone = argResults?['phone'] as String;
    final password = argResults?['password'] as String;

    final apiService = ApiService();

    try {
      print('🔐 Connexion en cours...');
      final response = await apiService.login(phone, password);

      if (response['status'] == 'success') {
        if (response['data']['requires_otp'] == true) {
          print('✅ Code OTP envoyé à $phone');
          print('💡 Utilisez: ompay auth verify-otp --phone $phone --otp <code>');
        }
      } else {
        print('❌ Erreur: ${response['message']}');
        exit(1);
      }
    } catch (e) {
      print('❌ Erreur de connexion: $e');
      exit(1);
    }
  }
}

class VerifyOtpCommand extends Command {
  @override
  final name = 'verify-otp';
  @override
  final description = 'Vérification du code OTP';

  VerifyOtpCommand() {
    argParser
      ..addOption('phone', abbr: 'p', mandatory: true, help: 'Numéro de téléphone')
      ..addOption('otp', abbr: 'o', mandatory: true, help: 'Code OTP');
  }

  @override
  Future<void> run() async {
    final phone = argResults?['phone'] as String;
    final otp = argResults?['otp'] as String;

    final apiService = ApiService();

    try {
      print('🔍 Vérification du code OTP...');
      final response = await apiService.verifyOtp(phone, otp);

      if (response['status'] == 'success') {
        final token = response['access_token'] as String;
        await apiService.saveToken(token);
        print('✅ Authentification réussie ! Token sauvegardé.');
      } else {
        print('❌ Erreur: ${response['message']}');
        exit(1);
      }
    } catch (e) {
      print('❌ Erreur de vérification: $e');
      exit(1);
    }
  }
}

class LogoutCommand extends Command {
  @override
  final name = 'logout';
  @override
  final description = 'Déconnexion et suppression du token';

  @override
  Future<void> run() async {
    final apiService = ApiService();
    await apiService.clearToken();
    print('✅ Déconnexion réussie. Token supprimé.');
  }
}