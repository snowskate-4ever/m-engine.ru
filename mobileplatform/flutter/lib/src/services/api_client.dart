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
      throw ApiException('Invalid email or password');
    }
    throw ApiException('Login failed (${response.statusCode})');
  }

  Future<bool> hasValidSession(String token) async {
    final response = await _httpClient.get(
      _uri('api/messenger/conversations'),
      headers: _auth(token),
    );
    return response.statusCode == 200;
  }

  Future<List<ActorOption>> getActorOptions(String token) async {
    final response = await _httpClient.get(
      _uri('api/music/actor-context'),
      headers: _auth(token),
    );
    _throwIfError(response);
    final data = _decodeMap(response.body)['data'];
    final actors = data is Map<String, dynamic> ? data['actors'] : null;
    if (actors is! List) {
      return const [];
    }
    return actors.whereType<Map<String, dynamic>>().map((row) {
      return ActorOption(
        type: row['type']?.toString() ?? 'unknown',
        id: _toInt(row['id']),
        label: row['label']?.toString() ?? 'No name',
      );
    }).toList();
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
      final typeName = typeRow['name']?.toString() ?? 'Unknown';
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
        initiatorLabel: event['name']?.toString() ?? 'Unknown',
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
        title: row['title']?.toString() ?? 'Chat',
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
        title: row['title']?.toString() ?? 'Untitled task',
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
        name: row['name']?.toString() ?? 'Untitled event',
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
    throw ApiException(message ?? 'Request failed (${response.statusCode})');
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
      throw ApiException('Invalid response format');
    }
    return decoded;
  }

  int _toInt(Object? value) {
    if (value is int) {
      return value;
    }
    return int.tryParse('$value') ?? 0;
  }
}
