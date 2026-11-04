import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'models.dart';

final navigatorKey = GlobalKey<NavigatorState>();

// Logged-in rep, persisted so the rep stays logged in between app launches
class Session {
  Session._();

  static final Session instance = Session._();

  final ValueNotifier<bool> loggedIn = ValueNotifier(false);
  String? token;
  Rep? rep;
  Meta meta = Meta.empty();

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    token = prefs.getString('token');
    final repJson = prefs.getString('rep');
    final metaJson = prefs.getString('meta');
    if (token != null && repJson != null) {
      rep = Rep.fromJson(jsonDecode(repJson));
      meta = metaJson != null ? Meta.fromJson(jsonDecode(metaJson)) : Meta.empty();
      loggedIn.value = true;
    }
  }

  Future<void> save(String newToken, Rep newRep, Meta newMeta) async {
    token = newToken;
    rep = newRep;
    meta = newMeta;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', newToken);
    await prefs.setString('rep', jsonEncode(newRep.toJson()));
    await prefs.setString('meta', jsonEncode(newMeta.toJson()));
    loggedIn.value = true;
  }

  Future<void> clear() async {
    token = null;
    rep = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    await prefs.remove('rep');
    await prefs.remove('meta');
    // Drop any open screens so the login screen is shown
    navigatorKey.currentState?.popUntil((route) => route.isFirst);
    loggedIn.value = false;
  }
}
