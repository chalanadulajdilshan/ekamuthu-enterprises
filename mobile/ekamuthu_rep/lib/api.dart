import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import 'config.dart';
import 'models.dart';
import 'session.dart';

class ApiException implements Exception {
  final String message;

  ApiException(this.message);

  @override
  String toString() => message;
}

class Api {
  static const _timeout = Duration(seconds: 30);
  static const _uploadTimeout = Duration(seconds: 120);

  static Uri _uri(String action, [Map<String, String>? params]) =>
      Uri.parse(apiBase).replace(queryParameters: {'action': action, ...?params});

  static Map<String, String> get _headers => {
        'Accept': 'application/json',
        if (Session.instance.token != null) 'X-Auth-Token': Session.instance.token!,
      };

  static Future<Map<String, dynamic>> _handle(Future<http.Response> request) async {
    final http.Response res;
    try {
      res = await request;
    } on SocketException {
      throw ApiException('No internet connection. Please try again.');
    } on TimeoutException {
      throw ApiException('Server is taking too long. Please try again.');
    } on http.ClientException {
      throw ApiException('Cannot reach the server. Please try again.');
    }

    Map<String, dynamic> body;
    try {
      // Always UTF-8: item names are in Sinhala and the server may not send a charset
      body = jsonDecode(utf8.decode(res.bodyBytes)) as Map<String, dynamic>;
    } catch (_) {
      throw ApiException('Unexpected server response (${res.statusCode}).');
    }

    if (res.statusCode == 401 && Session.instance.token != null) {
      await Session.instance.clear();
    }
    if (body['status'] != 'success') {
      throw ApiException(toStr(body['message']).isEmpty ? 'Something went wrong.' : toStr(body['message']));
    }
    return body;
  }

  static Future<Map<String, dynamic>> get(String action, [Map<String, String>? params]) =>
      _handle(http.get(_uri(action, params), headers: _headers).timeout(_timeout));

  // Multipart so the same call can carry a proof photo
  static Future<Map<String, dynamic>> post(String action, Map<String, String> fields, {File? proof}) {
    final req = http.MultipartRequest('POST', _uri(action))
      ..headers.addAll(_headers)
      ..fields.addAll(fields);
    Future<http.Response> send() async {
      if (proof != null) {
        req.files.add(await http.MultipartFile.fromPath('proof', proof.path));
      }
      return http.Response.fromStream(await req.send());
    }

    return _handle(send().timeout(proof != null ? _uploadTimeout : _timeout));
  }

  // ---- Actions ----

  static Future<void> login(String username, String password) async {
    final body = await post('login', {'username': username, 'password': password, 'device': 'Android app'});
    final data = body['data'] as Map<String, dynamic>;
    await Session.instance.save(toStr(data['token']), Rep.fromJson(data['rep']), Meta.fromJson(data['meta']));
  }

  static Future<void> logout() async {
    try {
      await post('logout', {});
    } catch (_) {
      // Log out locally even if the server cannot be reached
    }
    await Session.instance.clear();
  }

  static Future<(List<BillSummary>, TodayStats)> bills() async {
    final data = (await get('bills'))['data'] as Map<String, dynamic>;
    final list = (data['bills'] as List).map((e) => BillSummary.fromJson(e)).toList();
    return (list, TodayStats.fromJson(data['today']));
  }

  static Future<BillDetail> bill(int rentId) async =>
      BillDetail.fromJson((await get('bill', {'id': '$rentId'}))['data']);

  static Future<String> saveVisit(Map<String, String> fields) async => toStr((await post('visit', fields))['message']);

  static Future<String> savePayment(Map<String, String> fields, File? proof) async =>
      toStr((await post('payment', fields, proof: proof))['message']);

  static Future<String> addProof(int collectionId, File proof) async =>
      toStr((await post('proof', {'collection_id': '$collectionId'}, proof: proof))['message']);

  static Future<DaySummary> summary(String date) async =>
      DaySummary.fromJson((await get('summary', {'date': date}))['data']);
}
