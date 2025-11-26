import 'dart:io';
import 'package:dio/dio.dart';
import '../../core/config.dart';

class ApiService {
  final Dio _dio;

  ApiService() : _dio = Dio(BaseOptions(
    baseUrl: Config.apiBaseUrl,
    connectTimeout: const Duration(seconds: 10),
    receiveTimeout: const Duration(seconds: 10),
  ));

  Future<String?> getToken() async {
    try {
      final file = File(Config.tokenFile);
      if (await file.exists()) {
        return await file.readAsString();
      }
    } catch (e) {
      // Ignore errors
    }
    return null;
  }

  Future<void> saveToken(String token) async {
    final file = File(Config.tokenFile);
    await file.writeAsString(token);
  }

  Future<void> clearToken() async {
    final file = File(Config.tokenFile);
    if (await file.exists()) {
      await file.delete();
    }
  }

  // Auth endpoints
  Future<Map<String, dynamic>> login(String phone, String password) async {
    final response = await _dio.post('/api/auth/login', data: {
      'phone': phone,
      'password': password,
    });
    return response.data;
  }

  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) async {
    final response = await _dio.post('/api/auth/verify-otp', data: {
      'phone': phone,
      'otp_code': otp,
    });
    return response.data;
  }

  // Transaction endpoints
  Future<Map<String, dynamic>> deposit(double amount) async {
    final token = await getToken();
    final response = await _dio.post('/api/transactions/deposit',
      data: {'amount': amount},
      options: Options(headers: {'Authorization': 'Bearer $token'}),
    );
    return response.data;
  }

  Future<Map<String, dynamic>> withdraw(double amount) async {
    final token = await getToken();
    final response = await _dio.post('/api/transactions/withdraw',
      data: {'amount': amount},
      options: Options(headers: {'Authorization': 'Bearer $token'}),
    );
    return response.data;
  }

  Future<Map<String, dynamic>> transfer(String toPhone, double amount) async {
    final token = await getToken();
    final response = await _dio.post('/api/transactions/transfer',
      data: {'to': toPhone, 'amount': amount},
      options: Options(headers: {'Authorization': 'Bearer $token'}),
    );
    return response.data;
  }

  Future<Map<String, dynamic>> payMerchant(String merchantCode, double amount) async {
    final token = await getToken();
    final response = await _dio.post('/api/transactions/pay',
      data: {'merchant_code': merchantCode, 'amount': amount},
      options: Options(headers: {'Authorization': 'Bearer $token'}),
    );
    return response.data;
  }

  // Balance and info endpoints
  Future<Map<String, dynamic>> getBalance() async {
    final token = await getToken();
    final response = await _dio.get('/api/comptes',
      options: Options(headers: {'Authorization': 'Bearer $token'}),
    );
    return response.data;
  }

  Future<Map<String, dynamic>> getTransactions({int limit = 10}) async {
    final token = await getToken();
    final response = await _dio.get('/api/comptes/transactions',
      options: Options(headers: {'Authorization': 'Bearer $token'}),
    );
    return response.data;
  }
}