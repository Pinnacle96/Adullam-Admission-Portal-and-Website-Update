#!/bin/bash
# CI/CD SSH Connection Diagnostic Script
# Run this on your Hostinger server to verify setup

echo "=== Adullam Admission CI/CD Diagnostic ==="
echo "Date: $(date)"
echo ""

# 1. Check SSH service
echo "[1] SSH Service Status:"
systemctl status ssh 2>/dev/null || systemctl status sshd 2>/dev/null || echo "Cannot determine SSH status"
echo ""

# 2. Check SSH config
echo "[2] SSH Configuration:"
grep -E "^Port|^PermitRootLogin|^PubkeyAuthentication" /etc/ssh/sshd_config || echo "Could not read SSH config"
echo ""

# 3. Check authorized_keys
echo "[3] Authorized Keys:"
if [ -f ~/.ssh/authorized_keys ]; then
    echo "✅ File exists: ~/.ssh/authorized_keys"
    echo "Number of keys: $(wc -l < ~/.ssh/authorized_keys)"
    echo "Keys present:"
    grep -o 'github-actions@\|root@\|user@' ~/.ssh/authorized_keys | sort | uniq -c
else
    echo "❌ ~/.ssh/authorized_keys NOT FOUND"
fi
echo ""

# 4. Check SSH permissions
echo "[4] SSH Directory Permissions:"
ls -ld ~/.ssh 2>/dev/null || echo "~/.ssh directory not found"
ls -l ~/.ssh/ 2>/dev/null | head -5 || echo "Cannot list ~/.ssh"
echo ""

# 5. Check Firewall
echo "[5] Firewall Status (Port 22):"
if command -v ufw &> /dev/null; then
    ufw status | grep 22 || echo "UFW enabled but Port 22 status unknown"
elif command -v firewall-cmd &> /dev/null; then
    firewall-cmd --list-ports | grep 22 || echo "Firewall-cmd found but port 22 not in list"
else
    echo "No standard firewall found"
fi
echo ""

# 6. Test connectivity
echo "[6] Network Test:"
echo "Listening on SSH ports:"
netstat -tlnp 2>/dev/null | grep ssh || ss -tlnp 2>/dev/null | grep ssh || echo "Cannot determine listening ports"
echo ""

# 7. Check logs
echo "[7] Recent SSH Logs:"
tail -10 /var/log/auth.log 2>/dev/null || tail -10 /var/log/secure 2>/dev/null || echo "Cannot read SSH logs"
