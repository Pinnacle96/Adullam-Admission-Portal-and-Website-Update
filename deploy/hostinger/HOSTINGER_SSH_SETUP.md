# Hostinger SSH Access - Diagnostic Checklist

## For Hostinger Support Ticket

**Issue:** GitHub Actions CI/CD cannot connect to deploy server via SSH
**Error:** `dial tcp [HOST]:[PORT]: i/o timeout`
**Workflow:** Deploy to Hostinger (appleboy/scp-action@v0.1.7)

## Server Information Needed

- [ ] Hostinger Account Email: ________________
- [ ] VPS/Hosting Plan: ________________
- [ ] Server Hostname: ________________
- [ ] SSH Port: ________________
- [ ] SSH Username: ________________

## Required Configuration

**1. SSH Port Status**
- Confirm Port 22 (or custom SSH port) is OPEN and accessible
- Check: Is it blocked by default firewall?
- Allow external connections for GitHub Actions

**2. Firewall Rules**
- SSH port must accept connections from:
  - GitHub Actions IP ranges (140.82.112.0/20, 143.55.64.0/20, etc.)
  - OR allow all (0.0.0.0/0) for initial testing

**3. SSH Key Setup**
- Public key has been added to `~/.ssh/authorized_keys` on server
- Permissions: 
  - `~/.ssh/`: 700
  - `~/.ssh/authorized_keys`: 600
  - `~/.ssh/id_rsa` (if password-based): 400

**4. SSH Authentication**
- Password authentication disabled (use key-based only)
- Root login disabled (use regular user account)
- X11 Forwarding: disabled

## Quick Test Command

```bash
# From your local machine:
ssh -i hostinger_adullam_deploy -p [PORT] [USERNAME]@[HOSTNAME] "echo 'SSH Access OK'"
```

If this works locally, but GitHub Actions fails, the issue is likely:
- Hostinger firewall blocking GitHub IPs
- SSH key authentication disabled
- Firewall rule for port 22

## Support Ticket Template

---

**Subject:** Enable SSH Access for GitHub Actions CI/CD Deployment

**Description:**
I need to configure GitHub Actions CI/CD for my Adullam Admission Portal website. The deployment requires SSH/SCP access to transfer files.

**Current Issue:**
GitHub Actions workflow times out when trying to connect via SSH:
```
error copy file to dest: [HOST]:[PORT], error message: dial tcp [HOST]:[PORT]: i/o timeout
```

**What I Need:**
1. Confirm Port 22 (SSH) is open and accessible from external networks
2. Whitelist GitHub Actions IP ranges if using IP filtering
3. Verify that key-based SSH authentication is enabled
4. Confirm my SSH public key is properly authorized

**My SSH Public Key (for authorized_keys):**
```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAILyMl7ZCqMi3oAT4yEIqW4Fsx5cmLk1v2n/vqNDVxulK github-actions@adullam
```

Please confirm once SSH access is properly configured.

---
