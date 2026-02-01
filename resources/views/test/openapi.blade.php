<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VK Open API тест</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .section h2 {
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .btn-loading {
            position: relative;
            padding-left: 40px;
        }
        .btn-loading::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }
        .results {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 4px;
            border: 1px solid #ddd;
            display: none;
        }
        .results.show {
            display: block;
        }
        .results.success {
            border-color: #4CAF50;
            background: #f1f8f4;
        }
        .results.error {
            border-color: #f44336;
            background: #ffebee;
        }
        .group-item {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-left: 3px solid #4CAF50;
            border-radius: 4px;
        }
        .chat-item {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-left: 3px solid #2196f3;
            border-radius: 4px;
        }
        .chat-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .chat-info {
            font-size: 14px;
            color: #666;
        }
        .group-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .group-info {
            font-size: 14px;
            color: #666;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .error-message {
            color: #f44336;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
            margin-top: 10px;
        }
        .success-message {
            color: #4CAF50;
            padding: 10px;
            background: #f1f8f4;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>VK Open API тест</h1>

        <div class="section">
            <h2>Авторизация VK Open API</h2>
            <button id="vkOpenApiLoginBtn" class="btn" disabled>Войти через VK (виджет)</button>
            <a href="{{ route('admin.test.vk-oauth-start') }}" class="btn" style="display: inline-block; text-decoration: none; margin-left: 8px;">Войти через OAuth</a>
            <button id="vkOpenApiGroupsBtn" class="btn" disabled>Получить группы ВК</button>
            <button id="vkOpenApiNewsBtn" class="btn" disabled>Получить ленту новостей</button>
            <div style="margin-top: 15px;">
                <label for="vkOpenApiGroupSelect" style="display: block; margin-bottom: 6px; color: #555;">
                    Группа для отслеживания
                </label>
                <select id="vkOpenApiGroupSelect" class="btn" style="background: #fff; color: #333; border: 1px solid #ddd;">
                    <option value="">Выберите группу</option>
                    @foreach(($vkTrackings ?? []) as $tracking)
                        <option value="{{ $tracking->screen_name }}" data-group-id="{{ $tracking->group_id }}">
                            {{ $tracking->name }} ({{ $tracking->screen_name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button id="vkOpenApiGroupNewsBtn" class="btn" disabled style="margin-top: 10px;">Новости выбранной группы</button>
            <div id="vkOpenApiStatus" style="margin-top: 10px;"></div>
            @if(!empty($vkApiError))
                <div class="error-message" style="margin-top: 10px;">{{ $vkApiError }}</div>
            @endif
            @if(!empty($vkOAuthTokenSaved))
                <div class="success-message" style="margin-top: 10px;">✓ Токен получен через OAuth. Нажмите «Получить группы (через сервер)» ниже.</div>
                <button type="button" id="vkOAuthGroupsBtn" class="btn" style="margin-top: 8px;">Получить группы (через сервер)</button>
            @endif
            <div style="margin-top: 10px; color: #666; font-size: 14px;">
                Если вход не завершается, разрешите pop-up окна и сторонние cookies для домена <code>vk.com</code>.
            </div>
            <div style="margin-top: 8px; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 13px;">
                <strong>«Выбранный способ авторизации не доступен для приложения»</strong> — это сообщение от VK (виджет может быть отключён для приложения). Используйте кнопку <strong>«Войти через OAuth»</strong> — она ведёт на VK и обратно по вашему «Доверенный redirect URI» (<code>https://m-engine.ru/vk-oauth</code>). В .env на сервере должен быть задан <code>VK_CLIENT_SECRET</code> (Client Secret из настроек приложения).
            </div>
            <div id="vkOpenApiGroupsResults" class="results"></div>
            <div id="vkOpenApiNewsResults" class="results"></div>
            <div id="vkOpenApiGroupNewsResults" class="results"></div>
            <div id="vkOpenApiGroupNewsMore" class="results">
                <button id="vkOpenApiGroupNewsMoreBtn" class="btn" disabled>Загрузить еще</button>
            </div>
        </div>

        <div class="section">
            <h2>VK API - Группы и чаты (рабочий способ)</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 12px;">
                Старый виджет «Войти через VK» больше не поддерживается VK. Используйте OAuth ниже.
            </p>
            <a id="vkApiAuthBtn" class="btn" href="{{ route('admin.test.vk-oauth-start') }}">Получить VK API токен</a>
            <button id="getVkGroupsApiBtn" class="btn" {{ !($vkApiTokenSaved ?? false) ? 'disabled' : '' }}>Получить группы ВК</button>
            <button id="getVkChatsBtn" class="btn">Получить чаты ВК</button>
            <div style="margin-top: 10px; color: #666; font-size: 14px;">
                Чаты получаются по VK токену пользователя <code>mad.md@yandex.ru</code>.
            </div>
            <div style="margin-top: 10px; display: flex; gap: 12px; flex-wrap: wrap;">
                <label style="font-size: 14px; color: #555;">
                    Offset:
                    <input id="vkChatsOffset" type="number" min="0" value="0" style="margin-left: 6px; padding: 6px; width: 90px;">
                </label>
                <label style="font-size: 14px; color: #555;">
                    Count:
                    <input id="vkChatsCount" type="number" min="1" max="200" value="20" style="margin-left: 6px; padding: 6px; width: 90px;">
                </label>
                <label style="font-size: 14px; color: #555;">
                    Filter:
                    <input id="vkChatsFilter" type="text" placeholder="all, unread" style="margin-left: 6px; padding: 6px; width: 150px;">
                </label>
            </div>
            <div id="vkApiTokenHint" style="margin-top: 10px; color: #666; font-size: 14px;"></div>
            <div id="vkGroupsApiResults" class="results"></div>
            <div id="vkChatsResults" class="results"></div>
        </div>

        <div class="section">
            <h2>Результаты системных тестов</h2>
            <div id="testResults">
                @if(isset($results))
                    @foreach($results as $testName => $testResult)
                        <div style="margin-bottom: 15px;">
                            <strong>{{ ucfirst(str_replace('_', ' ', $testName)) }}:</strong>
                            @if(is_array($testResult))
                                @if(isset($testResult['status']))
                                    <span style="color: {{ $testResult['status'] === 'success' ? '#4CAF50' : '#f44336' }}">
                                        {{ $testResult['status'] === 'success' ? '✓' : '✗' }} {{ $testResult['message'] ?? '' }}
                                    </span>
                                @else
                                    <ul style="margin: 5px 0; padding-left: 20px;">
                                        @foreach($testResult as $key => $value)
                                            <li>
                                                <strong>{{ $key }}:</strong>
                                                @if(is_array($value))
                                                    @if(isset($value['status']))
                                                        <span style="color: {{ $value['status'] === 'success' ? '#4CAF50' : '#f44336' }}">
                                                            {{ $value['status'] === 'success' ? '✓' : '✗' }} {{ $value['message'] ?? '' }}
                                                        </span>
                                                    @else
                                                        {{ json_encode($value) }}
                                                    @endif
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            @else
                                {{ $testResult }}
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
            <div style="margin-top: 15px; color: #666; font-size: 14px;">
                Время выполнения: {{ $timestamp ?? 'N/A' }}
            </div>
        </div>
    </div>

    <div id="vk_api_transport"></div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const appId = @json($vkOpenApiAppId);
            const vkApiTokenSaved = @json($vkApiTokenSaved ?? false);
            const loginBtn = document.getElementById('vkOpenApiLoginBtn');
            const groupsBtn = document.getElementById('vkOpenApiGroupsBtn');
            const newsBtn = document.getElementById('vkOpenApiNewsBtn');
            const groupNewsBtn = document.getElementById('vkOpenApiGroupNewsBtn');
            const groupNewsMoreBtn = document.getElementById('vkOpenApiGroupNewsMoreBtn');
            const groupNewsMoreWrap = document.getElementById('vkOpenApiGroupNewsMore');
            const groupSelect = document.getElementById('vkOpenApiGroupSelect');
            const statusDiv = document.getElementById('vkOpenApiStatus');
            const resultsDiv = document.getElementById('vkOpenApiGroupsResults');
            const getVkGroupsApiBtn = document.getElementById('getVkGroupsApiBtn');
            const vkGroupsApiResults = document.getElementById('vkGroupsApiResults');
            const vkApiTokenHint = document.getElementById('vkApiTokenHint');
            const chatsBtn = document.getElementById('getVkChatsBtn');
            const chatsResultsDiv = document.getElementById('vkChatsResults');
            const chatsOffsetInput = document.getElementById('vkChatsOffset');
            const chatsCountInput = document.getElementById('vkChatsCount');
            const chatsFilterInput = document.getElementById('vkChatsFilter');

            function showError(message) {
                statusDiv.innerHTML = `<div class="error-message">${message}</div>`;
            }

            function showSuccess(message) {
                statusDiv.innerHTML = `<div class="success-message">${message}</div>`;
            }

            function updateStatusFromVk(response) {
                if (!response) {
                    showError('VK не вернул данные авторизации. Проверьте всплывающие окна и попробуйте снова.');
                    return;
                }

                if (response.session) {
                    showSuccess('✓ Пользователь авторизован в VK.');
                    return;
                }

                if (response.status === 'not_authorized') {
                    showError('Пользователь вошел в VK, но не разрешил доступ приложению.');
                    return;
                }

                if (response.status === 'unknown') {
                    showError('Пользователь не авторизован в VK или закрыл окно входа. Проверьте блокировку pop-up и cookies для vk.com.');
                    return;
                }

                showError('Авторизация не завершена. Попробуйте снова.');
            }

            window.vkAsyncInit = function() {
                if (!appId) {
                    showError('VK_APP_ID не настроен.');
                    return;
                }

                VK.init({ apiId: appId });
                loginBtn.disabled = false;
                VK.Auth.getLoginStatus(function(response) {
                    updateStatusFromVk(response);
                });
            };

            setTimeout(function() {
                const el = document.createElement('script');
                el.type = 'text/javascript';
                el.src = 'https://vk.com/js/api/openapi.js?169';
                el.async = true;
                el.onerror = function() {
                    showError('Не удалось загрузить VK Open API.');
                };
                document.getElementById('vk_api_transport').appendChild(el);
            }, 0);

            loginBtn.addEventListener('click', function() {
                if (!window.VK || !VK.Auth) {
                    showError('VK Open API не инициализирован.');
                    return;
                }

                VK.Auth.login(function(response) {
                    if (!response || !response.session) {
                        console.warn('VK Auth login response:', response);
                        updateStatusFromVk(response);
                        return;
                    }

                    const session = response.session;
                    fetch('{{ route("admin.vktest.session") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ session: session })
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type') || '';
                        if (contentType.includes('application/json')) {
                            return { ok: response.ok, data: await response.json() };
                        }
                        return { ok: response.ok, data: null };
                    })
                    .then(result => {
                        if (result.ok && result.data?.success) {
                            showSuccess('✓ Авторизация успешна! Сессия сохранена.');
                            groupsBtn.disabled = false;
                            newsBtn.disabled = false;
                            groupNewsBtn.disabled = false;
                            groupNewsMoreBtn.disabled = false;
                        } else {
                            showError(result.data?.message || 'Ошибка сохранения сессии.');
                        }
                    })
                    .catch(error => {
                        showError('Ошибка: ' + error.message);
                    });
                }, 270338);
            });

            const vkOAuthGroupsBtn = document.getElementById('vkOAuthGroupsBtn');
            if (vkOAuthGroupsBtn) {
                vkOAuthGroupsBtn.addEventListener('click', function() {
                    resultsDiv.classList.remove('show', 'success', 'error');
                    resultsDiv.innerHTML = '<div style="color: #666;">Загрузка...</div>';
                    resultsDiv.classList.add('show');
                    fetch('{{ route("admin.test.vk-groups") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin'
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success || data.data === undefined) {
                            resultsDiv.classList.add('error');
                            resultsDiv.innerHTML = `<div class="error-message">${data.message || 'Ошибка запроса'}</div>`;
                            return;
                        }
                        const groups = data.data.groups || [];
                        const count = data.data.count != null ? data.data.count : groups.length;
                        let html = `<div class="success-message">Успешно получено групп: ${count}</div>`;
                        if (groups.length > 0) {
                            html += '<div style="margin-top: 15px;">';
                            groups.forEach(g => {
                                html += `<div class="group-item"><div class="group-name">${g.name || 'Без названия'}</div><div class="group-info">ID: ${g.id || 'N/A'} | Тип: ${g.type || 'N/A'} | Участников: ${g.members_count || 'N/A'}</div></div>`;
                            });
                            html += '</div>';
                        }
                        resultsDiv.classList.add('success');
                        resultsDiv.innerHTML = html;
                    })
                    .catch(err => {
                        resultsDiv.classList.add('error');
                        resultsDiv.innerHTML = `<div class="error-message">Ошибка: ${err.message}</div>`;
                    });
                });
            }

            groupsBtn.addEventListener('click', function() {
                resultsDiv.classList.remove('show', 'success', 'error');
                resultsDiv.innerHTML = '';

                VK.Api.call('groups.get', { extended: 1, v: '5.131' }, function(r) {
                    if (!r || r.error) {
                        resultsDiv.classList.add('show', 'error');
                        const errorText = r?.error?.error_msg || 'Неизвестная ошибка VK API';
                        resultsDiv.innerHTML = `<div class="error-message">${errorText}</div>`;
                        return;
                    }

                    const groups = r.response?.items || [];
                    const count = r.response?.count || groups.length;
                    let html = `<div class="success-message">Успешно получено групп: ${count}</div>`;

                    if (groups.length > 0) {
                        html += '<div style="margin-top: 15px;">';
                        groups.forEach(group => {
                            html += `
                                <div class="group-item">
                                    <div class="group-name">${group.name || 'Без названия'}</div>
                                    <div class="group-info">
                                        ID: ${group.id || 'N/A'} |
                                        Тип: ${group.type || 'N/A'} |
                                        Участников: ${group.members_count || 'N/A'}
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    } else {
                        html += '<div style="margin-top: 15px; color: #666;">Группы не найдены</div>';
                    }

                    resultsDiv.classList.add('show', 'success');
                    resultsDiv.innerHTML = html;
                });
            });

            newsBtn.addEventListener('click', function() {
                const newsResults = document.getElementById('vkOpenApiNewsResults');
                newsResults.classList.remove('show', 'success', 'error');
                newsResults.innerHTML = '';

                VK.Api.call('newsfeed.get', { filters: 'post', count: 20, v: '5.131' }, function(r) {
                    if (!r || r.error) {
                        newsResults.classList.add('show', 'error');
                        const errorText = r?.error?.error_msg || 'Неизвестная ошибка VK API';
                        newsResults.innerHTML = `<div class="error-message">${errorText}</div>`;
                        return;
                    }

                    const items = r.response?.items || [];
                    let html = `<div class="success-message">Успешно получено записей: ${items.length}</div>`;

                    if (items.length > 0) {
                        html += '<div style="margin-top: 15px;">';
                        items.forEach(item => {
                            const text = (item.text || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            html += `
                                <div class="group-item">
                                    <div class="group-name">Запись ${item.id || ''}</div>
                                    <div class="group-info">${text || 'Без текста'}</div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    } else {
                        html += '<div style="margin-top: 15px; color: #666;">Записей не найдено</div>';
                    }

                    newsResults.classList.add('show', 'success');
                    newsResults.innerHTML = html;
                });
            });

            let groupNewsNextFrom = null;
            let currentGroupId = null;
            function formatUnixDate(value) {
                if (!value) {
                    return '';
                }
                const date = new Date(value * 1000);
                return date.toLocaleString('ru-RU');
            }
            function renderGroupNews(items, target) {
                if (!items || items.length === 0) {
                    target.innerHTML += '<div style="margin-top: 15px; color: #666;">Записей не найдено</div>';
                    return;
                }

                let html = '<div style="margin-top: 15px;">';
                items.forEach(item => {
                    const text = (item.text || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const createdAt = formatUnixDate(item.date);
                    html += `
                        <div class="group-item">
                            <div class="group-name">Запись ${item.id || ''}${createdAt ? ` • ${createdAt}` : ''}</div>
                            <div class="group-info">${text || 'Без текста'}</div>
                        </div>
                    `;
                });
                html += '</div>';
                target.innerHTML += html;
            }

            groupNewsBtn.addEventListener('click', function() {
                const groupNewsResults = document.getElementById('vkOpenApiGroupNewsResults');
                groupNewsResults.classList.remove('show', 'success', 'error');
                groupNewsResults.innerHTML = '';
                groupNewsNextFrom = null;
                currentGroupId = null;
                groupNewsMoreWrap.classList.remove('show');
                groupNewsMoreBtn.disabled = true;

                const selected = groupSelect?.value || '';
                if (!selected) {
                    showError('Выберите группу для загрузки новостей.');
                    return;
                }

                const option = groupSelect.options[groupSelect.selectedIndex];
                const storedGroupId = option?.dataset?.groupId ? Number(option.dataset.groupId) : null;
                const loadNews = function(groupId) {
                    currentGroupId = groupId;
                    VK.Api.call('newsfeed.get', { filters: 'post', source_ids: `-${groupId}`, count: 20, v: '5.131' }, function(nr) {
                        if (!nr || nr.error) {
                            groupNewsResults.classList.add('show', 'error');
                            const errorText = nr?.error?.error_msg || 'Неизвестная ошибка VK API';
                            groupNewsResults.innerHTML = `<div class="error-message">${errorText}</div>`;
                            return;
                        }

                        const items = nr.response?.items || [];
                        groupNewsNextFrom = nr.response?.next_from || null;
                        groupNewsResults.classList.add('show', 'success');
                        groupNewsMoreWrap.classList.add('show');
                        groupNewsMoreBtn.disabled = !groupNewsNextFrom;
                        groupNewsResults.innerHTML = `<div class="success-message">Успешно получено записей: ${items.length}</div>`;
                        renderGroupNews(items, groupNewsResults);
                    });
                };

                if (storedGroupId) {
                    loadNews(storedGroupId);
                    return;
                }

                VK.Api.call('groups.getById', { group_id: selected, v: '5.131' }, function(r) {
                    if (!r || r.error || !r.response || !r.response[0]) {
                        groupNewsResults.classList.add('show', 'error');
                        const errorText = r?.error?.error_msg || 'Не удалось получить данные группы';
                        groupNewsResults.innerHTML = `<div class="error-message">${errorText}</div>`;
                        return;
                    }

                    const groupId = r.response[0].id;
                    loadNews(groupId);
                });
            });

            groupNewsMoreBtn.addEventListener('click', function() {
                if (!groupNewsNextFrom || !currentGroupId) {
                    showError('Больше записей нет или лента еще не загружена.');
                    return;
                }

                const groupNewsResults = document.getElementById('vkOpenApiGroupNewsResults');
                VK.Api.call('newsfeed.get', { filters: 'post', source_ids: `-${currentGroupId}`, start_from: groupNewsNextFrom, count: 20, v: '5.131' }, function(nr) {
                    if (!nr || nr.error) {
                        const errorText = nr?.error?.error_msg || 'Неизвестная ошибка VK API';
                        showError(errorText);
                        return;
                    }

                    const items = nr.response?.items || [];
                    groupNewsNextFrom = nr.response?.next_from || null;
                    renderGroupNews(items, groupNewsResults);
                    groupNewsMoreBtn.disabled = !groupNewsNextFrom;
                });
            });

            async function fetchVkChats() {
                chatsBtn.disabled = true;
                chatsBtn.classList.add('btn-loading');
                chatsResultsDiv.classList.remove('show', 'success', 'error');
                chatsResultsDiv.innerHTML = '<div class="loading">Загрузка...</div>';

                const offset = parseInt(chatsOffsetInput.value || '0', 10);
                const count = parseInt(chatsCountInput.value || '20', 10);
                const filter = (chatsFilterInput.value || '').trim();

                try {
                    const response = await fetch('{{ route("admin.test.vk-chats") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            offset: Number.isNaN(offset) ? 0 : offset,
                            count: Number.isNaN(count) ? 20 : count,
                            filter: filter || undefined
                        })
                    });

                    const contentType = response.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        const isAuthRedirect = response.redirected || response.url.includes('/admin/login');
                        chatsResultsDiv.classList.add('show', 'error');
                        chatsResultsDiv.innerHTML = `
                            <div class="error-message">
                                <strong>Ошибка:</strong> ${isAuthRedirect ? 'Требуется авторизация в админке' : 'Ответ сервера не в формате JSON'}
                                <br><small>Перезайдите в админку и попробуйте снова</small>
                            </div>
                        `;
                        return;
                    }

                    const data = await response.json();
                    if (data.success) {
                        chatsResultsDiv.classList.add('show', 'success');
                        const conversations = data.data.conversations || [];
                        const total = data.data.count || 0;

                        let html = `<div class="success-message">Успешно получено бесед: ${total}</div>`;
                        if (conversations.length > 0) {
                            html += '<div style="margin-top: 15px;">';
                            conversations.forEach(item => {
                                const conversation = item.conversation || {};
                                const peer = conversation.peer || {};
                                const chatSettings = conversation.chat_settings || {};
                                const title = chatSettings.title
                                    || (peer.type === 'user' ? `Диалог с пользователем ${peer.id || 'N/A'}` : `Диалог ${peer.id || 'N/A'}`);
                                const lastMessage = item.last_message || {};
                                const lastText = lastMessage.text ? lastMessage.text : 'Нет текста';

                                html += `
                                    <div class="chat-item">
                                        <div class="chat-title">${title}</div>
                                        <div class="chat-info">
                                            Peer ID: ${peer.id || 'N/A'} | Тип: ${peer.type || 'N/A'}
                                        </div>
                                        <div class="chat-info" style="margin-top: 4px;">
                                            Последнее сообщение: ${lastText}
                                        </div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                        } else {
                            html += '<div style="margin-top: 15px; color: #666;">Беседы не найдены</div>';
                        }

                        chatsResultsDiv.innerHTML = html;
                    } else {
                        chatsResultsDiv.classList.add('show', 'error');
                        chatsResultsDiv.innerHTML = `
                            <div class="error-message">
                                <strong>Ошибка:</strong> ${data.message || 'Неизвестная ошибка'}
                                ${data.codError ? `<br>Код ошибки: ${data.codError}` : ''}
                            </div>
                        `;
                    }
                } catch (error) {
                    chatsResultsDiv.classList.add('show', 'error');
                    chatsResultsDiv.innerHTML = `
                        <div class="error-message">
                            <strong>Ошибка запроса:</strong> ${error.message}
                            <br><small>Если ошибка CORS — используйте серверный запрос или прокси</small>
                        </div>
                    `;
                } finally {
                    chatsBtn.disabled = false;
                    chatsBtn.classList.remove('btn-loading');
                }
            }

            chatsBtn.addEventListener('click', function() {
                fetchVkChats();
            });

            if (vkApiTokenSaved) {
                vkApiTokenHint.innerHTML = '<span class="success-message" style="display:inline-block;padding:6px 10px;">✓ Токен есть. Можно получить группы.</span>';
                if (getVkGroupsApiBtn) getVkGroupsApiBtn.disabled = false;
            } else {
                vkApiTokenHint.innerHTML = '<span style="color:#f57c00;">Сначала получите VK API токен (кнопка выше). OAuth перенаправит на другую страницу — после возврата токен будет в сессии.</span>';
            }

            async function fetchVkGroupsApi() {
                if (!getVkGroupsApiBtn) return;
                getVkGroupsApiBtn.disabled = true;
                getVkGroupsApiBtn.classList.add('btn-loading');
                vkGroupsApiResults.classList.remove('show', 'success', 'error');
                vkGroupsApiResults.innerHTML = '<div class="loading">Загрузка...</div>';
                vkGroupsApiResults.classList.add('show');

                try {
                    const response = await fetch('{{ route("admin.test.vk-groups") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ extended: 1 })
                    });

                    const contentType = response.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        vkGroupsApiResults.classList.add('error');
                        vkGroupsApiResults.innerHTML = '<div class="error-message">Ответ не JSON. Проверьте авторизацию.</div>';
                        return;
                    }

                    const data = await response.json();
                    if (data.success) {
                        vkGroupsApiResults.classList.add('success');
                        const groups = data.data?.groups || [];
                        const count = data.data?.count ?? groups.length;
                        let html = '<div class="success-message">Групп: ' + count + '</div>';
                        if (Array.isArray(groups) && groups.length > 0) {
                            html += '<div style="margin-top:15px;">';
                            groups.forEach(function(g) {
                                html += '<div class="group-item"><div class="group-name">' + (g.name || 'Без названия') + '</div><div class="group-info">ID: ' + (g.id || 'N/A') + ' | ' + (g.screen_name || '') + ' | Участников: ' + (g.members_count || 'N/A') + '</div></div>';
                            });
                            html += '</div>';
                        } else {
                            html += '<div style="margin-top:15px;color:#666;">Группы не найдены</div>';
                        }
                        vkGroupsApiResults.innerHTML = html;
                    } else {
                        vkGroupsApiResults.classList.add('error');
                        vkGroupsApiResults.innerHTML = '<div class="error-message"><strong>Ошибка:</strong> ' + (data.message || 'Неизвестная ошибка') + (data.codError ? ' (код: ' + data.codError + ')' : '') + '</div>';
                    }
                } catch (err) {
                    vkGroupsApiResults.classList.add('error');
                    vkGroupsApiResults.innerHTML = '<div class="error-message">Ошибка запроса: ' + err.message + '</div>';
                } finally {
                    getVkGroupsApiBtn.disabled = false;
                    getVkGroupsApiBtn.classList.remove('btn-loading');
                }
            }

            if (getVkGroupsApiBtn) {
                getVkGroupsApiBtn.addEventListener('click', fetchVkGroupsApi);
            }
        });
    </script>
</body>
</html>
