import 'dart:convert';

import 'package:http/http.dart' as http;

class ApiException implements Exception {
  ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

class LoginResult {
  LoginResult({
    required this.token,
    required this.displayName,
  });

  final String token;
  final String? displayName;
}

class ActorOption {
  ActorOption({
    required this.type,
    required this.id,
    required this.label,
  });

  final String type;
  final int id;
  final String label;

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }
    return other is ActorOption && other.type == type && other.id == id;
  }

  @override
  int get hashCode => Object.hash(type, id);
}

class ActorContext {
  ActorContext({
    required this.actors,
    this.currentType,
    this.currentId,
  });

  final List<ActorOption> actors;
  final String? currentType;
  final int? currentId;
}

class MusicProfileOption {
  const MusicProfileOption({
    required this.key,
    required this.label,
  });

  final String key;
  final String label;
}

class MusicProfilesData {
  MusicProfilesData({
    required this.enabled,
    required this.available,
    required this.supportsEnabledState,
    required this.supportsProfileUpdate,
  });

  final List<String> enabled;
  final List<MusicProfileOption> available;
  final bool supportsEnabledState;
  final bool supportsProfileUpdate;
}

class ResourceSection {
  ResourceSection({
    required this.label,
    required this.totalCount,
    required this.items,
  });

  final String label;
  final int totalCount;
  final List<String> items;
}

class MatchingRequest {
  MatchingRequest({
    required this.id,
    required this.searchGoal,
    required this.status,
    required this.initiatorLabel,
  });

  final int id;
  final String searchGoal;
  final String status;
  final String initiatorLabel;
}

class ConversationItem {
  ConversationItem({
    required this.id,
    required this.title,
    required this.unreadCount,
  });

  final int id;
  final String title;
  final int unreadCount;
}

class ChatMessage {
  ChatMessage({
    required this.id,
    required this.body,
    required this.authorName,
    required this.userId,
  });

  final int id;
  final String body;
  final String? authorName;
  final int? userId;
}

class TaskItem {
  TaskItem({
    required this.id,
    required this.title,
    required this.status,
    required this.description,
    required this.assigneeName,
  });

  final int id;
  final String title;
  final String status;
  final String? description;
  final String? assigneeName;
}

class EventItem {
  EventItem({
    required this.id,
    required this.name,
    required this.status,
    required this.startAt,
    required this.endAt,
    required this.isBooking,
  });

  final int id;
  final String name;
  final String status;
  final String? startAt;
  final String? endAt;
  final bool isBooking;
}

class ApiClient {
  ApiClient({
    http.Client? httpClient,
    this.baseUrl = 'https://m-engine.ru',
  }) : _httpClient = httpClient ?? http.Client();

  final http.Client _httpClient;
  final String baseUrl;

  Uri _uri(String path, [Map<String, String>? query]) {
    final normalizedBase = baseUrl.endsWith('/')
        ? baseUrl.substring(0, baseUrl.length - 1)
        : baseUrl;
    return Uri.parse('$normalizedBase/$path').replace(queryParameters: query);
  }

  Future<LoginResult> login({
    required String email,
    required String password,
  }) async {
    final response = await _httpClient.post(
      _uri('api/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'email': email.trim(),
        'password': password,
      }),
    );

    final envelope = _decodeMap(response.body);
    final ok = envelope['success'] == true;
    final data = envelope['data'];

    if (response.statusCode == 200 && ok && data is Map<String, dynamic>) {
      final token = (data['token'] ?? '').toString();
      if (token.isNotEmpty) {
        return LoginResult(
          token: token,
          displayName: data['name']?.toString(),
        );
      }
    }

    final message = envelope['message']?.toString();
    if (message != null && message.isNotEmpty) {
      throw ApiException(message);
    }
    if (response.statusCode == 401) {
      throw ApiException('Неверный email или пароль');
    }
    throw ApiException('Не удалось выполнить вход (${response.statusCode})');
  }

  Future<bool> hasValidSession(String token) async {
    final response = await _httpClient.get(
      _uri('api/messenger/conversations'),
      headers: _auth(token),
    );
    return response.statusCode == 200;
  }

  Future<ActorContext> getActorContext(String token) async {
    final response = await _httpClient.get(
      _uri('api/music/actor-context'),
      headers: _auth(token),
    );
    _throwIfError(response);
    final body = _decodeMap(response.body);
    final data = body['data'];
    final list = data is List
        ? data.whereType<Map<String, dynamic>>().map((row) {
            return ActorOption(
              type: row['type']?.toString() ?? 'unknown',
              id: _toInt(row['id']),
              label: row['label']?.toString() ?? 'Без названия',
            );
          }).toList()
        : const <ActorOption>[];

    final current = body['current'];
    final currentType =
        current is Map<String, dynamic> ? current['type']?.toString() : null;
    final currentId = current is Map<String, dynamic>
        ? _toInt(current['id'])
        : null;

    return ActorContext(
      actors: list,
      currentType: currentType,
      currentId: currentId,
    );
  }

  Future<List<ActorOption>> getActorOptions(String token) async {
    final context = await getActorContext(token);
    return context.actors;
  }

  Future<ActorOption?> getCurrentActorOption(String token) async {
    final context = await getActorContext(token);
    if (context.currentType == null || context.currentId == null) {
      return null;
    }
    for (final actor in context.actors) {
      if (actor.type == context.currentType && actor.id == context.currentId) {
        return actor;
      }
    }
    return null;
  }

  Future<MusicProfilesData> getMusicProfiles(String token) async {
    http.Response response;
    try {
      response = await _httpClient.get(
        _uri('api/music/profiles'),
        headers: _auth(token),
      );
      _throwIfError(response);
    } on ApiException catch (e) {
      if (!_isNotFoundError(e)) {
        rethrow;
      }

      // Backward compatibility: older backend may not expose /api/music/profiles.
      return MusicProfilesData(
        enabled: const <String>[],
        available: _fallbackMusicProfileCatalog(),
        supportsEnabledState: false,
        supportsProfileUpdate: false,
      );
    }

    final body = _decodeMap(response.body);
    final payload = body['data'] is Map<String, dynamic>
        ? body['data'] as Map<String, dynamic>
        : body;
    final enabled = _parseEnabledProfiles(payload);
    final available = _parseAvailableProfiles(payload);

    return MusicProfilesData(
      enabled: enabled,
      available: available.isEmpty ? _fallbackMusicProfileCatalog() : available,
      supportsEnabledState: true,
      supportsProfileUpdate: true,
    );
  }

  Future<void> setMusicProfileEnabled(
    String token, {
    required String profile,
    required bool enabled,
  }) async {
    try {
      final response = await _httpClient.patch(
        _uri('api/music/profiles'),
        headers: {
          ..._auth(token),
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'profile': profile,
          'enabled': enabled,
        }),
      );
      _throwIfError(response);
    } on ApiException catch (e) {
      if (!_isNotFoundError(e)) {
        rethrow;
      }
      final parts = profile.split(':');
      if (parts.length != 2) {
        throw ApiException(
          'Текущая версия API не поддерживает изменение music_profiles из мобильного приложения',
        );
      }
      final type = parts[0];
      final id = int.tryParse(parts[1]);
      if (type.isEmpty || id == null) {
        throw ApiException('Некорректный профиль');
      }
      if (enabled) {
        await setActor(token, type: type, id: id);
      } else {
        final current = await getActorContext(token);
        if (current.currentType == type && current.currentId == id) {
          await setActor(token, type: null, id: null);
        }
      }
    }
  }

  Future<void> setActor(String token, {String? type, int? id}) async {
    final response = await _httpClient.patch(
      _uri('api/music/actor-context'),
      headers: {
        ..._auth(token),
        'Content-Type': 'application/json',
      },
      body: jsonEncode({'type': type, 'id': id}),
    );
    _throwIfError(response);
  }

  Future<List<ResourceSection>> getResourceSections(String token) async {
    final typesResp = await _httpClient.get(_uri('api/types'), headers: _auth(token));
    _throwIfError(typesResp);
    final resourcesResp = await _httpClient.get(_uri('api/resources'), headers: _auth(token));
    _throwIfError(resourcesResp);

    final types = _extractList(typesResp.body, 'types');
    final resources = _extractList(resourcesResp.body, 'resources');

    final byType = <int, List<Map<String, dynamic>>>{};
    for (final row in resources) {
      byType.putIfAbsent(_toInt(row['type_id']), () => []).add(row);
    }

    return types.map((typeRow) {
      final typeId = _toInt(typeRow['id']);
      final typeName = typeRow['name']?.toString() ?? 'Неизвестно';
      final rows = byType[typeId] ?? const <Map<String, dynamic>>[];
      return ResourceSection(
        label: typeName,
        totalCount: rows.length,
        items: rows.take(10).map((resource) {
          final id = _toInt(resource['id']);
          final date = (resource['start_at'] ?? 'no-date').toString();
          final shortDate = date.length >= 10 ? date.substring(0, 10) : date;
          return '$typeName #$id ($shortDate)';
        }).toList(),
      );
    }).toList();
  }

  Future<List<MatchingRequest>> getMatchingFeed(String token) async {
    final response = await _httpClient.get(
      _uri(
        'api/events',
        {
          'date_from': '2020-01-01',
          'date_to': '2035-01-01',
        },
      ),
      headers: _auth(token),
    );
    _throwIfError(response);

    final events = _extractList(response.body, 'events');
    return events.where((event) {
      final hasSpace = event['matching_space_id'] != null;
      final hasProposed = (event['matching_proposed_start_at'] ?? '').toString().isNotEmpty;
      final hasConfirmed = (event['matching_booking_confirmed_at'] ?? '').toString().isNotEmpty;
      return hasSpace || hasProposed || hasConfirmed;
    }).map((event) {
      final hasConfirmed = (event['matching_booking_confirmed_at'] ?? '').toString().isNotEmpty;
      final hasProposed = (event['matching_proposed_start_at'] ?? '').toString().isNotEmpty;
      return MatchingRequest(
        id: _toInt(event['id']),
        searchGoal: event['matching_space_type']?.toString() ?? 'matching',
        status: hasConfirmed
            ? 'confirmed'
            : hasProposed
                ? 'proposed'
                : (event['status']?.toString() ?? 'pending'),
        initiatorLabel: event['name']?.toString() ?? 'Неизвестно',
      );
    }).toList();
  }

  Future<List<ConversationItem>> getConversations(String token) async {
    final response = await _httpClient.get(
      _uri('api/messenger/conversations'),
      headers: _auth(token),
    );
    _throwIfError(response);
    final body = _decodeMap(response.body);
    final list = body['data'];
    if (list is! List) {
      return const [];
    }
    return list.whereType<Map<String, dynamic>>().map((row) {
      return ConversationItem(
        id: _toInt(row['id']),
        title: row['title']?.toString() ?? 'Чат',
        unreadCount: _toInt(row['unread_count']),
      );
    }).toList();
  }

  Future<List<ChatMessage>> getMessages(
    String token, {
    required int conversationId,
    int? beforeId,
    int? afterId,
    int perPage = 20,
  }) async {
    final query = <String, String>{'per_page': '$perPage'};
    if (beforeId != null) {
      query['before_id'] = '$beforeId';
    }
    if (afterId != null) {
      query['after_id'] = '$afterId';
    }

    final response = await _httpClient.get(
      _uri('api/messenger/conversations/$conversationId/messages', query),
      headers: _auth(token),
    );
    _throwIfError(response);
    final body = _decodeMap(response.body);
    final data = body['data'];
    final list = data is Map<String, dynamic> ? data['messages'] : null;
    if (list is! List) {
      return const [];
    }
    return list.whereType<Map<String, dynamic>>().map((row) {
      final author = row['author'];
      return ChatMessage(
        id: _toInt(row['id']),
        body: row['body']?.toString() ?? '',
        authorName: author is Map<String, dynamic> ? author['name']?.toString() : null,
        userId: row['user_id'] == null ? null : _toInt(row['user_id']),
      );
    }).toList();
  }

  Future<void> sendMessage(
    String token, {
    required int conversationId,
    required String body,
  }) async {
    final response = await _httpClient.post(
      _uri('api/messenger/conversations/$conversationId/messages'),
      headers: {
        ..._auth(token),
        'Content-Type': 'application/json',
      },
      body: jsonEncode({'body': body}),
    );
    _throwIfError(response, allowedCodes: {200, 201});
  }

  Future<List<TaskItem>> getTasks(String token) async {
    final response = await _httpClient.get(
      _uri('api/tasks'),
      headers: _auth(token),
    );
    _throwIfError(response);
    final tasks = _extractList(response.body, 'tasks');
    return tasks.map((row) {
      final assignee = row['assignee'];
      return TaskItem(
        id: _toInt(row['id']),
        title: row['title']?.toString() ?? 'Задача без названия',
        status: row['status']?.toString() ?? 'planned',
        description: row['description']?.toString(),
        assigneeName: assignee is Map<String, dynamic> ? assignee['name']?.toString() : null,
      );
    }).toList();
  }

  Future<void> createTask(
    String token, {
    required String title,
    String? description,
    String status = 'planned',
  }) async {
    final response = await _httpClient.post(
      _uri('api/tasks'),
      headers: {
        ..._auth(token),
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'title': title,
        'description': description,
        'status': status,
      }),
    );
    _throwIfError(response, allowedCodes: {200, 201});
  }

  Future<void> updateTaskStatus(
    String token, {
    required int taskId,
    required String status,
  }) async {
    final response = await _httpClient.put(
      _uri('api/tasks/$taskId'),
      headers: {
        ..._auth(token),
        'Content-Type': 'application/json',
      },
      body: jsonEncode({'status': status}),
    );
    _throwIfError(response, allowedCodes: {200});
  }

  Future<List<EventItem>> getEvents(
    String token, {
    required String dateFrom,
    required String dateTo,
  }) async {
    final response = await _httpClient.get(
      _uri('api/events', {'date_from': dateFrom, 'date_to': dateTo}),
      headers: _auth(token),
    );
    _throwIfError(response);
    final events = _extractList(response.body, 'events');
    return events.map((row) {
      return EventItem(
        id: _toInt(row['id']),
        name: row['name']?.toString() ?? 'Событие без названия',
        status: row['status']?.toString() ?? 'pending',
        startAt: row['start_at']?.toString(),
        endAt: row['end_at']?.toString(),
        isBooking: row['is_booking'] == true,
      );
    }).toList();
  }

  Future<void> createEvent(
    String token, {
    required String name,
    required String startAt,
    required String endAt,
    String status = 'pending',
  }) async {
    final response = await _httpClient.post(
      _uri('api/events'),
      headers: {
        ..._auth(token),
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'name': name,
        'start_at': startAt,
        'end_at': endAt,
        'status': status,
      }),
    );
    _throwIfError(response, allowedCodes: {200, 201});
  }

  Map<String, String> _auth(String token) {
    return {'Authorization': 'Bearer $token'};
  }

  void _throwIfError(http.Response response, {Set<int> allowedCodes = const {200}}) {
    if (allowedCodes.contains(response.statusCode)) {
      return;
    }
    final text = response.body;
    String? message;
    try {
      final map = _decodeMap(text);
      message = map['message']?.toString();
    } catch (_) {
      message = null;
    }
    throw ApiException(message ?? 'Запрос завершился ошибкой (${response.statusCode})');
  }

  List<Map<String, dynamic>> _extractList(String body, String key) {
    final envelope = _decodeMap(body);
    final data = envelope['data'];
    final list = data is Map<String, dynamic> ? data[key] : null;
    if (list is! List) {
      return const [];
    }
    return list.whereType<Map<String, dynamic>>().toList();
  }

  Map<String, dynamic> _decodeMap(String body) {
    final decoded = jsonDecode(body);
    if (decoded is! Map<String, dynamic>) {
      throw ApiException('Некорректный формат ответа');
    }
    return decoded;
  }

  int _toInt(Object? value) {
    if (value is int) {
      return value;
    }
    return int.tryParse('$value') ?? 0;
  }

  bool _isNotFoundError(ApiException error) {
    return error.message.contains('(404)');
  }

  List<String> _parseEnabledProfiles(Map<String, dynamic> payload) {
    final candidates = <Object?>[
      payload['enabled'],
      payload['enabled_profiles'],
      payload['music_profiles'],
      payload['profiles'],
    ];
    for (final raw in candidates) {
      if (raw is List) {
        final list = raw
            .map((item) {
              if (item is Map<String, dynamic>) {
                return item['key']?.toString() ??
                    item['profile']?.toString() ??
                    item['value']?.toString() ??
                    '';
              }
              return item.toString();
            })
            .where((item) => item.isNotEmpty)
            .toList();
        if (list.isNotEmpty) {
          return list;
        }
      }
    }
    return const <String>[];
  }

  List<MusicProfileOption> _parseAvailableProfiles(Map<String, dynamic> payload) {
    final raw = payload['available'] ?? payload['available_profiles'] ?? payload['catalog'];
    if (raw is List) {
      final list = raw.whereType<Map<String, dynamic>>().map((row) {
        final key = row['key']?.toString() ??
            row['profile']?.toString() ??
            row['value']?.toString() ??
            '';
        final label = row['label']?.toString() ??
            row['name']?.toString() ??
            key;
        return MusicProfileOption(key: key, label: label);
      }).where((row) => row.key.isNotEmpty).toList();
      if (list.isNotEmpty) {
        return list;
      }
    }
    if (raw is Map<String, dynamic>) {
      final list = raw.entries.map((entry) {
        return MusicProfileOption(
          key: entry.key,
          label: entry.value?.toString() ?? entry.key,
        );
      }).toList();
      if (list.isNotEmpty) {
        return list;
      }
    }
    return const <MusicProfileOption>[];
  }

  List<MusicProfileOption> _fallbackMusicProfileCatalog() {
    return const [
      MusicProfileOption(key: 'musician', label: 'Музыкант'),
      MusicProfileOption(key: 'teacher', label: 'Преподаватель'),
      MusicProfileOption(key: 'event_organizer', label: 'Организатор мероприятий'),
      MusicProfileOption(key: 'manager', label: 'Менеджер'),
      MusicProfileOption(key: 'session_musician', label: 'Сессионный музыкант'),
      MusicProfileOption(key: 'agent', label: 'Агент'),
      MusicProfileOption(key: 'sound_engineer', label: 'Звукорежиссер'),
      MusicProfileOption(key: 'arranger', label: 'Аранжировщик'),
      MusicProfileOption(key: 'live_sound', label: 'Концертный звук'),
      MusicProfileOption(key: 'lighting_designer', label: 'Светорежиссер'),
      MusicProfileOption(key: 'videographer', label: 'Видеограф'),
      MusicProfileOption(key: 'photographer', label: 'Фотограф'),
      MusicProfileOption(key: 'journalist', label: 'Журналист'),
      MusicProfileOption(key: 'venue_manager', label: 'Менеджер площадки'),
      MusicProfileOption(key: 'merchandiser', label: 'Мерчендайзер'),
      MusicProfileOption(key: 'tour_manager', label: 'Тур-менеджер'),
      MusicProfileOption(key: 'promoter', label: 'Промоутер'),
      MusicProfileOption(key: 'recording_engineer', label: 'Инженер записи'),
      MusicProfileOption(key: 'mastering_engineer', label: 'Мастеринг-инженер'),
      MusicProfileOption(key: 'session_producer', label: 'Сессионный продюсер'),
      MusicProfileOption(key: 'tech_rider', label: 'Технический райдер'),
      MusicProfileOption(key: 'backline_tech', label: 'Бэклайн-техник'),
      MusicProfileOption(key: 'graphic_designer', label: 'Графический дизайнер'),
      MusicProfileOption(key: 'smm_manager', label: 'SMM-менеджер'),
      MusicProfileOption(key: 'music_lawyer', label: 'Музыкальный юрист'),
      MusicProfileOption(key: 'accountant', label: 'Бухгалтер'),
    ];
  }
}
