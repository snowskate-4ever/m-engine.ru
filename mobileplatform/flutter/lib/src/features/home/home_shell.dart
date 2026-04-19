import 'package:flutter/material.dart';

import '../../services/api_client.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({
    super.key,
    required this.apiClient,
    required this.token,
    required this.displayName,
    required this.onLogout,
  });

  final ApiClient apiClient;
  final String token;
  final String? displayName;
  final Future<void> Function() onLogout;

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final tabs = <Widget>[
      _ProfilesTab(apiClient: widget.apiClient, token: widget.token),
      _ResourcesTab(apiClient: widget.apiClient, token: widget.token),
      _PlanningTab(apiClient: widget.apiClient, token: widget.token),
      _MessengerTab(apiClient: widget.apiClient, token: widget.token),
      _WorkTab(apiClient: widget.apiClient, token: widget.token),
      const _MoreTab(),
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text('M-Engine: ${widget.displayName ?? 'user'}'),
        actions: [
          IconButton(
            tooltip: 'Logout',
            onPressed: widget.onLogout,
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: tabs[_index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (value) => setState(() => _index = value),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.person), label: 'Profiles'),
          NavigationDestination(icon: Icon(Icons.apps), label: 'Resources'),
          NavigationDestination(icon: Icon(Icons.event), label: 'Planning'),
          NavigationDestination(icon: Icon(Icons.chat), label: 'Messenger'),
          NavigationDestination(icon: Icon(Icons.checklist), label: 'Work'),
          NavigationDestination(icon: Icon(Icons.more_horiz), label: 'More'),
        ],
      ),
    );
  }
}

class _ProfilesTab extends StatefulWidget {
  const _ProfilesTab({required this.apiClient, required this.token});

  final ApiClient apiClient;
  final String token;

  @override
  State<_ProfilesTab> createState() => _ProfilesTabState();
}

class _ProfilesTabState extends State<_ProfilesTab> {
  late Future<List<ActorOption>> _future;
  int? _selectedId;
  bool _updating = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _future = widget.apiClient.getActorOptions(widget.token);
  }

  Future<void> _select(ActorOption option) async {
    setState(() {
      _updating = true;
      _error = null;
    });
    try {
      await widget.apiClient.setActor(
        widget.token,
        type: option.type,
        id: option.id,
      );
      setState(() => _selectedId = option.id);
    } catch (e) {
      setState(() => _error = '$e');
    } finally {
      if (mounted) {
        setState(() => _updating = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<ActorOption>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return _ErrorView(
            message: '${snapshot.error}',
            onRetry: () => setState(() => _future = widget.apiClient.getActorOptions(widget.token)),
          );
        }
        final items = snapshot.data ?? const <ActorOption>[];
        if (items.isEmpty) {
          return const _EmptyState(text: 'No profiles available yet.');
        }
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const Text('Profile selection', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            if (_error != null)
              Text(
                _error!,
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            for (final option in items)
              Card(
                child: ListTile(
                  title: Text(option.label),
                  subtitle: Text(option.type),
                  trailing: _selectedId == option.id ? const Icon(Icons.check_circle) : null,
                  onTap: _updating ? null : () => _select(option),
                ),
              ),
          ],
        );
      },
    );
  }
}

class _ResourcesTab extends StatefulWidget {
  const _ResourcesTab({required this.apiClient, required this.token});

  final ApiClient apiClient;
  final String token;

  @override
  State<_ResourcesTab> createState() => _ResourcesTabState();
}

class _ResourcesTabState extends State<_ResourcesTab> {
  late Future<List<ResourceSection>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.apiClient.getResourceSections(widget.token);
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<ResourceSection>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return _ErrorView(
            message: '${snapshot.error}',
            onRetry: () => setState(() => _future = widget.apiClient.getResourceSections(widget.token)),
          );
        }
        final sections = snapshot.data ?? const <ResourceSection>[];
        if (sections.isEmpty) {
          return const _EmptyState(text: 'No resources returned by API.');
        }

        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: sections.length,
          itemBuilder: (context, index) {
            final section = sections[index];
            return Card(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('${section.label} (${section.totalCount})'),
                    const SizedBox(height: 6),
                    for (final item in section.items) Text('- $item'),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }
}

class _PlanningTab extends StatefulWidget {
  const _PlanningTab({required this.apiClient, required this.token});

  final ApiClient apiClient;
  final String token;

  @override
  State<_PlanningTab> createState() => _PlanningTabState();
}

class _PlanningTabState extends State<_PlanningTab> {
  late Future<List<MatchingRequest>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.apiClient.getMatchingFeed(widget.token);
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<MatchingRequest>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return _ErrorView(
            message: '${snapshot.error}',
            onRetry: () => setState(() => _future = widget.apiClient.getMatchingFeed(widget.token)),
          );
        }
        final requests = snapshot.data ?? const <MatchingRequest>[];
        if (requests.isEmpty) {
          return const _EmptyState(text: 'No matching requests found.');
        }
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            for (final request in requests)
              Card(
                child: ListTile(
                  title: Text(request.searchGoal),
                  subtitle: Text('Status: ${request.status}\nInitiator: ${request.initiatorLabel}'),
                  isThreeLine: true,
                ),
              ),
          ],
        );
      },
    );
  }
}

class _MessengerTab extends StatefulWidget {
  const _MessengerTab({required this.apiClient, required this.token});

  final ApiClient apiClient;
  final String token;

  @override
  State<_MessengerTab> createState() => _MessengerTabState();
}

class _MessengerTabState extends State<_MessengerTab> {
  late Future<List<ConversationItem>> _conversationsFuture;
  ConversationItem? _activeConversation;
  Future<List<ChatMessage>>? _messagesFuture;
  final _messageController = TextEditingController();
  String? _error;

  @override
  void initState() {
    super.initState();
    _conversationsFuture = widget.apiClient.getConversations(widget.token);
  }

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  void _openConversation(ConversationItem item) {
    setState(() {
      _activeConversation = item;
      _messagesFuture = widget.apiClient.getMessages(
        widget.token,
        conversationId: item.id,
      );
      _error = null;
    });
  }

  Future<void> _send() async {
    final body = _messageController.text.trim();
    if (body.isEmpty || _activeConversation == null) {
      return;
    }
    try {
      await widget.apiClient.sendMessage(
        widget.token,
        conversationId: _activeConversation!.id,
        body: body,
      );
      _messageController.clear();
      setState(() {
        _messagesFuture = widget.apiClient.getMessages(
          widget.token,
          conversationId: _activeConversation!.id,
        );
      });
    } catch (e) {
      setState(() => _error = '$e');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_activeConversation == null) {
      return FutureBuilder<List<ConversationItem>>(
        future: _conversationsFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return _ErrorView(
              message: '${snapshot.error}',
              onRetry: () => setState(() => _conversationsFuture = widget.apiClient.getConversations(widget.token)),
            );
          }
          final conversations = snapshot.data ?? const <ConversationItem>[];
          if (conversations.isEmpty) {
            return const _EmptyState(text: 'No conversations yet.');
          }
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              for (final item in conversations)
                Card(
                  child: ListTile(
                    title: Text(item.title),
                    subtitle: Text('Unread: ${item.unreadCount}'),
                    onTap: () => _openConversation(item),
                  ),
                ),
            ],
          );
        },
      );
    }

    return Column(
      children: [
        ListTile(
          title: Text(_activeConversation!.title),
          leading: IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: () => setState(() => _activeConversation = null),
          ),
        ),
        Expanded(
          child: FutureBuilder<List<ChatMessage>>(
            future: _messagesFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Center(child: CircularProgressIndicator());
              }
              if (snapshot.hasError) {
                return _ErrorView(
                  message: '${snapshot.error}',
                  onRetry: () => setState(() {
                    _messagesFuture = widget.apiClient.getMessages(
                      widget.token,
                      conversationId: _activeConversation!.id,
                    );
                  }),
                );
              }
              final messages = snapshot.data ?? const <ChatMessage>[];
              return ListView(
                reverse: true,
                padding: const EdgeInsets.symmetric(horizontal: 12),
                children: [
                  for (final message in messages.reversed)
                    Card(
                      child: ListTile(
                        title: Text(message.authorName ?? (message.userId == null ? 'System' : 'You')),
                        subtitle: Text(message.body),
                      ),
                    ),
                ],
              );
            },
          ),
        ),
        if (_error != null)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            child: Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _messageController,
                  decoration: const InputDecoration(
                    hintText: 'Message...',
                    border: OutlineInputBorder(),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              IconButton(
                onPressed: _send,
                icon: const Icon(Icons.send),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _WorkTab extends StatefulWidget {
  const _WorkTab({required this.apiClient, required this.token});

  final ApiClient apiClient;
  final String token;

  @override
  State<_WorkTab> createState() => _WorkTabState();
}

class _WorkTabState extends State<_WorkTab> {
  late Future<List<TaskItem>> _tasksFuture;
  late Future<List<EventItem>> _eventsFuture;
  String? _error;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  void _reload() {
    _tasksFuture = widget.apiClient.getTasks(widget.token);
    _eventsFuture = widget.apiClient.getEvents(
      widget.token,
      dateFrom: '2020-01-01',
      dateTo: '2035-01-01',
    );
  }

  Future<void> _createTask() async {
    final title = await _showInputDialog(
      context,
      title: 'New task',
      label: 'Task title',
    );
    if (title == null || title.trim().isEmpty) {
      return;
    }
    try {
      await widget.apiClient.createTask(widget.token, title: title.trim());
      setState(_reload);
    } catch (e) {
      setState(() => _error = '$e');
    }
  }

  Future<void> _createEvent() async {
    final title = await _showInputDialog(
      context,
      title: 'New event',
      label: 'Event name',
    );
    if (title == null || title.trim().isEmpty) {
      return;
    }
    final now = DateTime.now().toUtc();
    final end = now.add(const Duration(hours: 1));
    try {
      await widget.apiClient.createEvent(
        widget.token,
        name: title.trim(),
        startAt: now.toIso8601String(),
        endAt: end.toIso8601String(),
      );
      setState(_reload);
    } catch (e) {
      setState(() => _error = '$e');
    }
  }

  Future<void> _changeTaskStatus(TaskItem task, String status) async {
    try {
      await widget.apiClient.updateTaskStatus(
        widget.token,
        taskId: task.id,
        status: status,
      );
      setState(_reload);
    } catch (e) {
      setState(() => _error = '$e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async {
        setState(_reload);
      },
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Work: tasks & events',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
                ),
              ),
              IconButton(
                tooltip: 'Create task',
                onPressed: _createTask,
                icon: const Icon(Icons.add_task),
              ),
              IconButton(
                tooltip: 'Create event',
                onPressed: _createEvent,
                icon: const Icon(Icons.event_available),
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (_error != null)
            Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          const SizedBox(height: 8),
          const Text('Tasks', style: TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 6),
          FutureBuilder<List<TaskItem>>(
            future: _tasksFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Padding(
                  padding: EdgeInsets.symmetric(vertical: 24),
                  child: Center(child: CircularProgressIndicator()),
                );
              }
              if (snapshot.hasError) {
                return _ErrorCard(message: '${snapshot.error}');
              }
              final tasks = snapshot.data ?? const <TaskItem>[];
              if (tasks.isEmpty) {
                return const _EmptyState(text: 'No tasks yet.');
              }
              return Column(
                children: [
                  for (final task in tasks)
                    Card(
                      child: ListTile(
                        title: Text(task.title),
                        subtitle: Text(
                          'Status: ${task.status}${task.assigneeName == null ? '' : '\nAssignee: ${task.assigneeName}'}',
                        ),
                        isThreeLine: task.assigneeName != null,
                        trailing: PopupMenuButton<String>(
                          onSelected: (status) => _changeTaskStatus(task, status),
                          itemBuilder: (context) => const [
                            PopupMenuItem(value: 'planned', child: Text('planned')),
                            PopupMenuItem(value: 'in_progress', child: Text('in_progress')),
                            PopupMenuItem(value: 'done', child: Text('done')),
                          ],
                        ),
                      ),
                    ),
                ],
              );
            },
          ),
          const SizedBox(height: 12),
          const Text('Events', style: TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 6),
          FutureBuilder<List<EventItem>>(
            future: _eventsFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Padding(
                  padding: EdgeInsets.symmetric(vertical: 24),
                  child: Center(child: CircularProgressIndicator()),
                );
              }
              if (snapshot.hasError) {
                return _ErrorCard(message: '${snapshot.error}');
              }
              final events = snapshot.data ?? const <EventItem>[];
              if (events.isEmpty) {
                return const _EmptyState(text: 'No events yet.');
              }
              return Column(
                children: [
                  for (final event in events)
                    Card(
                      child: ListTile(
                        title: Text(event.name),
                        subtitle: Text(
                          'Status: ${event.status}\n'
                          'Start: ${event.startAt ?? '-'}\n'
                          'Booking: ${event.isBooking ? 'yes' : 'no'}',
                        ),
                        isThreeLine: true,
                      ),
                    ),
                ],
              );
            },
          ),
        ],
      ),
    );
  }
}

class _MoreTab extends StatelessWidget {
  const _MoreTab();

  @override
  Widget build(BuildContext context) {
    const items = [
      'Blog and comments',
      'Contracts and payments',
      'Integration API tokens',
      'Calendar sync and connectors',
      'Music memberships and search requests',
      'AI preferences and AI subscriptions',
    ];

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Text(
          'Site parity roadmap',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        const Text(
          'These modules are planned next to fully duplicate site functionality.',
        ),
        const SizedBox(height: 10),
        for (final item in items)
          Card(
            child: ListTile(
              leading: const Icon(Icons.pending_actions),
              title: Text(item),
            ),
          ),
      ],
    );
  }
}

class _ErrorCard extends StatelessWidget {
  const _ErrorCard({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Text(
          message,
          style: TextStyle(color: Theme.of(context).colorScheme.error),
        ),
      ),
    );
  }
}

Future<String?> _showInputDialog(
  BuildContext context, {
  required String title,
  required String label,
}) async {
  final controller = TextEditingController();
  final result = await showDialog<String>(
    context: context,
    builder: (dialogContext) {
      return AlertDialog(
        title: Text(title),
        content: TextField(
          controller: controller,
          decoration: InputDecoration(labelText: label),
          autofocus: true,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(dialogContext).pop(controller.text),
            child: const Text('Save'),
          ),
        ],
      );
    },
  );
  controller.dispose();
  return result;
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({
    required this.message,
    required this.onRetry,
  });

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              message,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            OutlinedButton(
              onPressed: onRetry,
              child: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Text(text, textAlign: TextAlign.center),
      ),
    );
  }
}
