<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Тестовая страница</title>
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
        <h1>Тестовая страница</h1>

        <div class="section">
            <h2>Авторизация VK ID</h2>
            <div style="margin-bottom: 15px; padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 4px; font-size: 14px;">
                <strong>⚠️ Настройка домена для VK ID:</strong>
                <br><br>
                @if(config('services.vk.tunnel_url'))
                    <div style="background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 10px; border: 1px solid #28a745;">
                        <strong>✅ Туннелинг активен:</strong> Используется HTTPS URL <code>{{ config('services.vk.tunnel_url') }}</code><br>
                        Убедитесь, что этот URL добавлен в настройки VK ID на <a href="https://dev.vk.com/apps?act=manage" target="_blank">https://dev.vk.com/apps?act=manage</a>
                    </div>
                @else
                        <div style="background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 10px; border: 1px solid #dc3545;">
                        <strong>⚠️ Проблема CSP:</strong> VK ID требует HTTPS для localhost.<br>
                        Текущий домен: <code>{{ $vkRedirectUrl ?? url('/') }}</code><br><br>
                        <strong>📋 Решение через туннелинг (localtunnel):</strong>
                        <ol style="margin: 10px 0; padding-left: 20px; font-size: 13px;">
                            <li><strong>Запустите туннель:</strong> Откройте новый терминал и выполните:<br>
                                <code style="background: #f4f4f4; padding: 5px; border-radius: 3px;">lt --port 80</code><br>
                                (Если порт 80 занят, используйте порт вашего сервера, например: <code>lt --port 8000</code>)</li>
                            <li><strong>Скопируйте HTTPS URL:</strong> После запуска будет показан URL вида <code>https://xxxxx.loca.lt</code></li>
                            <li><strong>Добавьте в .env:</strong> Откройте файл <code>.env</code> и добавьте строку:<br>
                                <code style="background: #f4f4f4; padding: 5px; border-radius: 3px;">VK_TUNNEL_URL=https://xxxxx.loca.lt</code><br>
                                (Замените <code>xxxxx</code> на ваш реальный URL)</li>
                            <li><strong>Перезапустите сервер Laravel</strong> (Ctrl+C и снова <code>php artisan serve --port=80</code>)</li>
                            <li><strong>Откройте HTTPS URL в браузере:</strong> Используйте туннель URL (например, <code>https://xxxxx.loca.lt/admin/test</code>), а НЕ <code>http://localhost</code></li>
                            <li><strong>Добавьте URL в настройки VK ID:</strong> Перейдите на <a href="https://dev.vk.com/apps?act=manage" target="_blank">https://dev.vk.com/apps?act=manage</a>, откройте приложение ID 54418904, добавьте ваш HTTPS URL в "Базовые домены"</li>
                        </ol>
                        <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                            <strong>💡 Важно:</strong> После настройки туннеля вы должны открывать приложение через HTTPS URL туннеля, а не через <code>http://localhost</code>!
                        </div>
                    </div>
                @endif
                <strong>Важно:</strong> 
                <ul style="margin: 5px 0; padding-left: 20px;">
                    <li>Домен должен <strong>точно совпадать</strong> с адресом в адресной строке браузера</li>
                    <li>После добавления домена подождите 2-3 минуты для применения настроек</li>
                    <li>Если используете туннелинг, убедитесь, что туннель запущен</li>
                </ul>
            </div>
            <div id="vkAuthContainer">
                <div>
                    <script src="https://unpkg.com/@vkid/sdk@<3.0.0/dist-sdk/umd/index.js"></script>
                    <script type="text/javascript">
                        let vkUserToken = null;
                        let vkUserId = null;
                        let vkApiToken = sessionStorage.getItem('vk_api_token');

                        if ('VKIDSDK' in window) {
                            const VKID = window.VKIDSDK;

                            const redirectUrl = '{{ $vkRedirectUrl ?? (config('services.vk.tunnel_url') ?: config('services.vk.redirect_url', url('/'))) }}';
                            
                            console.log('VK ID Config:', {
                                app: {{ config('services.vk.app_id', '54418904') }},
                                redirectUrl: redirectUrl,
                                currentOrigin: window.location.origin
                            });
                            
                            VKID.Config.init({
                                app: {{ config('services.vk.app_id', '54418904') }},
                                redirectUrl: redirectUrl,
                                responseMode: VKID.ConfigResponseMode.Callback,
                                source: VKID.ConfigSource.LOWCODE,
                                scope: '', // Заполните нужными доступами по необходимости
                            });

                            const floatingOneTap = new VKID.FloatingOneTap();

                            floatingOneTap.render({
                                appName: 'm-engine',
                                showAlternativeLogin: true
                            })
                            .on(VKID.WidgetEvents.LOADED, function() {
                                console.log('VK ID FloatingOneTap loaded');
                            })
                            .on(VKID.WidgetEvents.ERROR, function(error) {
                                console.error('VK ID Widget Error:', error);
                                vkidOnError(error);
                            })
                            .on(VKID.FloatingOneTapInternalEvents.LOGIN_SUCCESS, function (payload) {
                                console.log('VK ID Login Success Payload:', payload);
                                const code = payload.code;
                                const deviceId = payload.device_id;

                                VKID.Auth.exchangeCode(code, deviceId)
                                    .then(function(data) {
                                        console.log('VK ID Token Exchange Success:', data);
                                        vkidOnSuccess(data);
                                    })
                                    .catch(function(error) {
                                        console.error('VK ID Token Exchange Error:', error);
                                        vkidOnError(error);
                                    });
                            });
                        
                            function vkidOnSuccess(data) {
                                floatingOneTap.close();

                                const token = data.access_token || data.token;
                                const userId = data.user?.id || data.user_id;

                                if (!token) {
                                    document.getElementById('vkAuthStatus').innerHTML =
                                        '<div class="error-message">Ошибка: VK ID не вернул access_token</div>';
                                    return;
                                }
                                
                                vkUserToken = token;
                                vkUserId = userId;

                                // Сохраняем токен на сервере
                                fetch('{{ route("admin.test.vk-token") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({
                                        token: token,
                                        user_id: userId
                                    })
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
                                        document.getElementById('vkAuthStatus').innerHTML = 
                                            '<div class="success-message">✓ Авторизация успешна! Токен сохранен.</div>';
                                        document.getElementById('getVkGroupsBtn').disabled = false;
                                    } else {
                                        const errorText = result.data?.message || 'Ошибка сохранения токена';
                                        document.getElementById('vkAuthStatus').innerHTML = 
                                            '<div class="error-message">' + errorText + '</div>';
                                    }
                                })
                                .catch(error => {
                                    document.getElementById('vkAuthStatus').innerHTML = 
                                        '<div class="error-message">Ошибка: ' + error.message + '</div>';
                                });
                            }
                        
                            function vkidOnError(error) {
                                let errorMessage = 'Неизвестная ошибка';
                                let errorCode = null;
                                let errorText = null;
                                
                                // Правильно извлекаем сообщение об ошибке из объекта
                                if (error && typeof error === 'object') {
                                    if (error.text) {
                                        errorText = error.text;
                                    }
                                    if (error.code !== undefined) {
                                        errorCode = error.code;
                                    }
                                    if (error.message) {
                                        errorMessage = error.message;
                                    } else if (error.error) {
                                        errorMessage = typeof error.error === 'string' ? error.error : JSON.stringify(error.error);
                                    } else if (errorText) {
                                        errorMessage = errorText;
                                        if (errorCode !== null) {
                                            errorMessage = 'Код: ' + errorCode + ' - ' + errorText;
                                        }
                                    } else {
                                        // Пытаемся найти любое текстовое поле в объекте
                                        errorMessage = JSON.stringify(error, null, 2);
                                    }
                                } else if (typeof error === 'string') {
                                    errorMessage = error;
                                }
                                
                                // Специальная обработка для ошибки "New tab has been closed"
                                if (errorCode === 2 && errorText === 'New tab has been closed') {
                                    const currentOrigin = window.location.origin;
                                    document.getElementById('vkAuthStatus').innerHTML = 
                                        '<div class="error-message" style="background: #fff3cd; border-color: #ffc107;">' +
                                        '<strong>⚠️ Вкладка авторизации была закрыта</strong><br><br>' +
                                        'Вы закрыли окно авторизации VK ID. Попробуйте авторизоваться снова.<br><br>' +
                                        '<strong>Важно:</strong> Если проблема повторяется, это может быть связано с ошибкой CSP (Content Security Policy).<br>' +
                                        'VK ID требует HTTPS для localhost. Текущий домен: <code>' + currentOrigin + '</code><br><br>' +
                                        '<strong>Решение:</strong> Используйте HTTPS для localhost или туннелинг сервис (localtunnel, ngrok).' +
                                        '</div>';
                                    return;
                                }
                                
                                console.error('VK ID Auth Error:', error);
                                
                                const currentOrigin = window.location.origin;
                                let solutionText = '';
                                
                                // Специальная обработка для ошибки CSP
                                // Если timeout на http://localhost, это скорее всего CSP проблема
                                const isCSPError = errorMessage.includes('Content Security Policy') || errorMessage.includes('frame-ancestors');
                                const isTimeoutOnHttpLocalhost = (errorCode === 0 && errorText === 'timeout' && currentOrigin === 'http://localhost');
                                
                                if (isCSPError || isTimeoutOnHttpLocalhost) {
                                    // Обработка CSP ошибки
                                    solutionText = 
                                        '<div class="error-message" style="background: #ffebee; border-color: #f44336;">' +
                                        '<strong>❌ Ошибка Content Security Policy (CSP):</strong><br><br>' +
                                        'VK ID блокирует загрузку iframe для домена <code>' + currentOrigin + '</code>.<br><br>' +
                                        '<strong>Проблема:</strong> VK ID требует HTTPS для localhost, но вы используете HTTP.<br><br>' +
                                        '<strong>Решения:</strong>' +
                                        '<ul style="margin: 10px 0; padding-left: 20px;">' +
                                        '<li><strong>Вариант 1 (рекомендуется):</strong> Используйте HTTPS для localhost' +
                                        '<ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">' +
                                        '<li>Настройте локальный SSL сертификат или используйте Laragon/XAMPP с HTTPS</li>' +
                                        '<li>Добавьте домен <code>https://localhost</code> в настройки VK ID</li>' +
                                        '</ul></li>' +
                                        '<li><strong>Вариант 2:</strong> Используйте туннелинг сервис (ngrok, localtunnel)' +
                                        '<ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">' +
                                        '<li>Установите: <code>npm install -g localtunnel</code></li>' +
                                        '<li>Запустите: <code>lt --port 80 --subdomain m-engine</code></li>' +
                                        '<li>Используйте полученный HTTPS URL</li>' +
                                        '</ul></li>' +
                                        '<li><strong>Вариант 3:</strong> Настройте локальный домен через hosts файл' +
                                        '<ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">' +
                                        '<li>Добавьте в <code>C:\\Windows\\System32\\drivers\\etc\\hosts</code>: <code>127.0.0.1 m-engine.local</code></li>' +
                                        '<li>Настройте SSL для <code>m-engine.local</code></li>' +
                                        '<li>Добавьте <code>https://m-engine.local</code> в настройки VK ID</li>' +
                                        '</ul></li>' +
                                        '</ul>' +
                                        '<br><strong>Отладочная информация:</strong>' +
                                        '<ul style="margin: 10px 0; padding-left: 20px; font-size: 12px; color: #666;">' +
                                        '<li>Текущий домен: <code>' + currentOrigin + '</code></li>' +
                                        '<li>Redirect URL: <code>' + redirectUrl + '</code></li>' +
                                        '<li>Ошибка: ' + errorMessage + '</li>' +
                                        '</ul>' +
                                        '</div>';
                                    document.getElementById('vkAuthStatus').innerHTML = solutionText;
                                    return; // Выходим, так как CSP ошибка уже обработана
                                } else if (errorText === 'timeout' || errorMessage.includes('timeout')) {
                                    // Специальная обработка для ошибки timeout (если не CSP ошибка)
                                    solutionText = 
                                        '<div class="error-message" style="background: #fff3cd; border-color: #ffc107;">' +
                                        '<strong>Ошибка таймаута:</strong> Превышено время ожидания ответа от VK ID.<br><br>' +
                                        '<strong>Отладочная информация:</strong>' +
                                        '<ul style="margin: 10px 0; padding-left: 20px; font-size: 12px; color: #666;">' +
                                        '<li>Текущий домен: <code>' + currentOrigin + '</code></li>' +
                                        '<li>Redirect URL: <code>' + redirectUrl + '</code></li>' +
                                        '<li>Проверьте консоль браузера (F12) для подробных ошибок</li>' +
                                        '</ul><br>' +
                                        '<strong>Возможные причины и решения:</strong>' +
                                        '<ul style="margin: 10px 0; padding-left: 20px;">' +
                                        '<li>Проверьте подключение к интернету</li>' +
                                        '<li>Убедитесь, что домен <code>' + currentOrigin + '</code> добавлен в настройки VK ID</li>' +
                                        '<li>Домен должен точно совпадать с адресом в адресной строке (включая порт, если указан)</li>' +
                                        '<li>Подождите 2-3 минуты после добавления домена и обновите страницу</li>' +
                                        '<li>Очистите кеш браузера (Ctrl+F5) или откройте страницу в режиме инкогнито</li>' +
                                        '<li>Проверьте консоль браузера (F12 → Console) на наличие ошибок CORS или загрузки ресурсов</li>' +
                                        '<li>Попробуйте использовать другой браузер</li>' +
                                        '</ul>' +
                                        '</div>';
                                    document.getElementById('vkAuthStatus').innerHTML = solutionText;
                                } else {
                                    // Общая обработка других ошибок
                                    solutionText = 
                                        '<div class="error-message">' +
                                        '<strong>Ошибка авторизации:</strong> ' + errorMessage +
                                        '<br><br><strong>Решение:</strong>' +
                                        '<ul style="margin: 10px 0; padding-left: 20px;">' +
                                        '<li>Добавьте домен <code>' + currentOrigin + '</code> в настройки приложения VK ID</li>' +
                                        '<li>Домен должен точно совпадать с адресом в адресной строке (включая порт, если указан)</li>' +
                                        '<li>Подождите 2-3 минуты после добавления домена</li>' +
                                        '<li>Очистите кеш браузера и обновите страницу</li>' +
                                        '</ul>' +
                                        '</div>';
                                    document.getElementById('vkAuthStatus').innerHTML = solutionText;
                                }
                            }
                        } else {
                            // Пробуем загрузить SDK с обработкой ошибок
                            setTimeout(function() {
                                if (!('VKIDSDK' in window)) {
                                    document.getElementById('vkAuthStatus').innerHTML = 
                                        '<div class="error-message">VK ID SDK не загружен. Проверьте подключение к интернету и консоль браузера на наличие ошибок.</div>';
                                }
                            }, 2000);
                        }
                        
                        function vkApiJsonp(method, params = {}) {
                            return new Promise((resolve, reject) => {
                                const callbackName = `vkJsonp_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
                                const script = document.createElement('script');
                                const timeoutId = setTimeout(() => {
                                    cleanup();
                                    reject(new Error('VK API timeout'));
                                }, 10000);

                                function cleanup() {
                                    delete window[callbackName];
                                    script.remove();
                                    clearTimeout(timeoutId);
                                }

                                window[callbackName] = function(data) {
                                    cleanup();
                                    resolve(data);
                                };

                                const url = new URL(`https://api.vk.com/method/${method}`);
                                Object.entries(params).forEach(([key, value]) => {
                                    if (value !== undefined && value !== null && value !== '') {
                                        url.searchParams.set(key, String(value));
                                    }
                                });
                                url.searchParams.set('callback', callbackName);

                                script.src = url.toString();
                                script.onerror = () => {
                                    cleanup();
                                    reject(new Error('Ошибка загрузки VK API'));
                                };

                                document.body.appendChild(script);
                            });
                        }

                        function setVkApiToken(token) {
                            vkApiToken = token;
                            if (token) {
                                sessionStorage.setItem('vk_api_token', token);
                            } else {
                                sessionStorage.removeItem('vk_api_token');
                            }
                        }

                        // Обработка ошибок загрузки скрипта
                        window.addEventListener('error', function(e) {
                            if (e.target && e.target.src && e.target.src.includes('@vkid/sdk')) {
                                document.getElementById('vkAuthStatus').innerHTML = 
                                    '<div class="error-message">Ошибка загрузки VK ID SDK: ' + (e.message || 'Неизвестная ошибка') + '</div>';
                            }
                        }, true);
                    </script>
                </div>
                <div id="vkAuthStatus" style="margin-top: 15px;"></div>
            </div>
        </div>

        <div class="section">
            <h2>VK API - Группы пользователя</h2>
            <button id="vkApiAuthBtn" class="btn">Получить VK API токен</button>
            <button id="getVkGroupsBtn" class="btn" disabled>Получить группы ВК</button>
            <div style="margin-top: 10px; color: #666; font-size: 14px;">
                Для групп нужен VK API токен с доступом <code>groups</code>.
            </div>
            <div id="vkApiTokenStatus" style="margin-top: 10px;"></div>
            
            <div id="vkGroupsResults" class="results"></div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('getVkGroupsBtn');
            const vkApiAuthBtn = document.getElementById('vkApiAuthBtn');
            const vkApiTokenStatus = document.getElementById('vkApiTokenStatus');
            const resultsDiv = document.getElementById('vkGroupsResults');

            function updateVkApiStatus() {
                if (vkApiToken) {
                    vkApiTokenStatus.innerHTML =
                        '<div class="success-message">✓ VK API токен сохранен для текущей сессии.</div>';
                    btn.disabled = false;
                } else {
                    vkApiTokenStatus.innerHTML =
                        '<div class="error-message">VK API токен не получен.</div>';
                    btn.disabled = true;
                }
            }

            const hashParams = new URLSearchParams(window.location.hash.slice(1));
            const apiTokenFromHash = hashParams.get('access_token');
            if (apiTokenFromHash) {
                setVkApiToken(apiTokenFromHash);
                history.replaceState(null, document.title, window.location.pathname + window.location.search);
            }

            updateVkApiStatus();

            vkApiAuthBtn.addEventListener('click', function() {
                const redirectUri = window.location.origin + '/admin/test';
                const authUrl = new URL('https://oauth.vk.com/authorize');
                authUrl.searchParams.set('client_id', '{{ config('services.vk.app_id', '54418904') }}');
                authUrl.searchParams.set('redirect_uri', redirectUri);
                authUrl.searchParams.set('scope', 'groups');
                authUrl.searchParams.set('response_type', 'token');
                authUrl.searchParams.set('v', '{{ config('services.vk.api_version', '5.131') }}');
                authUrl.searchParams.set('display', 'page');
                window.location.href = authUrl.toString();
            });

            btn.addEventListener('click', async function() {
                btn.disabled = true;
                btn.classList.add('btn-loading');
                resultsDiv.classList.remove('show', 'success', 'error');
                resultsDiv.innerHTML = '<div class="loading">Загрузка...</div>';

                try {
                    if (!vkApiToken) {
                                        resultsDiv.classList.add('show', 'error');
                                        resultsDiv.innerHTML = `
                                            <div class="error-message">
                                <strong>Ошибка:</strong> Нужен VK API токен с доступом <code>groups</code>
                                            </div>
                                        `;
                                        return;
                                    }

                                    const data = await vkApiJsonp('groups.get', {
                        access_token: vkApiToken,
                                        v: '{{ config('services.vk.api_version', '5.131') }}',
                                        extended: 1,
                                        user_id: vkUserId || undefined,
                                    });

                    if (data.error) {
                                        resultsDiv.classList.add('show', 'error');
                                        resultsDiv.innerHTML = `
                                            <div class="error-message">
                                                <strong>Ошибка:</strong> ${data.error.error_msg || 'Неизвестная ошибка'}
                                                ${data.error.error_code ? `<br>Код ошибки: ${data.error.error_code}` : ''}
                                ${data.error.error_code === 1051 ? '<br><small>Токен VK ID не подходит для VK API. Получите VK API токен кнопкой выше.</small>' : ''}
                                            </div>
                                        `;
                                        return;
                                    }

                                    if (data.response) {
                                        resultsDiv.classList.add('show', 'success');
                                        const groups = data.response.items || data.response || [];
                                        const count = data.response.count || groups.length;

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

                        resultsDiv.innerHTML = html;
                                    } else {
                                        resultsDiv.classList.add('show', 'error');
                                        resultsDiv.innerHTML = `
                                            <div class="error-message">
                                                <strong>Ошибка:</strong> Неожиданный формат ответа VK API
                                            </div>
                                        `;
                                    }
                } catch (error) {
                    resultsDiv.classList.add('show', 'error');
                    resultsDiv.innerHTML = `
                        <div class="error-message">
                                            <strong>Ошибка запроса:</strong> ${error.message}
                                            <br><small>Если ошибка CORS — используйте серверный запрос или прокси</small>
                        </div>
                    `;
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('btn-loading');
                }
            });
        });
    </script>
</body>
</html>

