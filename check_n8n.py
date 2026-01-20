#!/usr/bin/env python3
import paramiko
import sys

def ssh_connect_and_check():
    hostname = "80.93.60.187"
    username = "root"
    password = "4o7v2rg$9Fb6uiri"
    port = 22
    
    try:
        # Создаем SSH клиент
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        print(f"Подключение к {hostname}...")
        ssh.connect(hostname, port=port, username=username, password=password, timeout=10)
        print("Подключение успешно!\n")
        
        # Выполняем команды для проверки
        commands = [
            ("=== Статус n8n ===", "systemctl status n8n --no-pager | head -20 || echo 'n8n не найден как сервис'"),
            ("=== Проверка портов ===", "netstat -tlnp 2>/dev/null | grep -E ':(5678|80|443)' || ss -tlnp 2>/dev/null | grep -E ':(5678|80|443)' || echo 'Порты не найдены'"),
            ("=== Конфигурация nginx ===", "if [ -d /etc/nginx ]; then ls -la /etc/nginx/sites-enabled/; grep -r 'madfactory.ru' /etc/nginx/sites-enabled/ 2>/dev/null || echo 'Конфигурация для madfactory.ru не найдена'; else echo 'nginx не установлен'; fi"),
            ("=== Конфигурация n8n ===", "if [ -f ~/.n8n/config ]; then cat ~/.n8n/config; elif [ -f /root/.n8n/config ]; then cat /root/.n8n/config; else echo 'Конфигурационный файл не найден'; find /root -name 'config' -path '*n8n*' 2>/dev/null | head -3; fi"),
            ("=== Переменные окружения n8n ===", "systemctl show n8n 2>/dev/null | grep -E '(Environment|ExecStart)' || journalctl -u n8n -n 5 --no-pager 2>/dev/null | tail -5 || echo 'Информация о сервисе не найдена'"),
            ("=== DNS проверка ===", "nslookup madfactory.ru 2>/dev/null || dig madfactory.ru 2>/dev/null | grep -A 2 'ANSWER SECTION' || echo 'DNS проверка не удалась'"),
            ("=== Файрвол ===", "ufw status 2>/dev/null || (iptables -L -n 2>/dev/null | grep -E '(80|443|5678)' || echo 'Файрвол не настроен')"),
            ("=== Процессы n8n ===", "ps aux | grep n8n | grep -v grep || echo 'Процессы n8n не найдены'"),
        ]
        
        for title, command in commands:
            print(f"\n{title}")
            print("-" * 50)
            stdin, stdout, stderr = ssh.exec_command(command)
            output = stdout.read().decode('utf-8')
            error = stderr.read().decode('utf-8')
            if output:
                print(output)
            if error and 'grep' not in error.lower():
                print(f"Ошибки: {error}")
        
        ssh.close()
        print("\n\nПроверка завершена!")
        
    except paramiko.AuthenticationException:
        print("Ошибка аутентификации. Проверьте логин и пароль.")
        sys.exit(1)
    except paramiko.SSHException as e:
        print(f"Ошибка SSH: {e}")
        sys.exit(1)
    except Exception as e:
        print(f"Ошибка: {e}")
        sys.exit(1)

if __name__ == "__main__":
    ssh_connect_and_check()
