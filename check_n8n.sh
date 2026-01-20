#!/bin/bash

# Скрипт для проверки конфигурации n8n на удаленном сервере

SERVER="80.93.60.187"
USER="root"
PASSWORD="4o7v2rg\$9Fb6uiri"

echo "Проверка статуса n8n..."
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no "$USER@$SERVER" << 'EOF'
echo "=== Проверка статуса n8n ==="
systemctl status n8n --no-pager | head -20

echo ""
echo "=== Проверка портов ==="
netstat -tlnp | grep -E ':(5678|80|443)' || ss -tlnp | grep -E ':(5678|80|443)'

echo ""
echo "=== Проверка конфигурации nginx ==="
if [ -d /etc/nginx ]; then
    ls -la /etc/nginx/sites-enabled/
    echo ""
    echo "Конфигурация для madfactory.ru:"
    grep -r "madfactory.ru" /etc/nginx/sites-enabled/ 2>/dev/null || echo "Конфигурация не найдена"
fi

echo ""
echo "=== Проверка конфигурации n8n ==="
if [ -f ~/.n8n/config ]; then
    cat ~/.n8n/config
elif [ -f /root/.n8n/config ]; then
    cat /root/.n8n/config
else
    echo "Конфигурационный файл n8n не найден"
    find / -name "config" -path "*n8n*" 2>/dev/null | head -5
fi

echo ""
echo "=== Проверка переменных окружения n8n ==="
systemctl show n8n | grep -E "(Environment|ExecStart)" || journalctl -u n8n -n 5 --no-pager

echo ""
echo "=== Проверка DNS ==="
nslookup madfactory.ru || dig madfactory.ru

echo ""
echo "=== Проверка файрвола ==="
ufw status || iptables -L -n | grep -E '(80|443|5678)' || echo "Файрвол не настроен или не активен"
EOF
