# SSL Setup - Deployment Instructions

> Step-by-step guide to deploy SSL certificates on production server

## 📦 What Was Pushed to Repository

The following files are now in the `main` branch:
- ✅ `init-letsencrypt.sh` - SSL certificate initialization script
- ✅ `check-ssl-status.sh` - SSL certificate status checker
- ✅ `SSL-SETUP.md` - Quick reference guide
- ✅ `DEPLOYMENT.md` - Updated with full SSL section

---

## 🚀 Deployment Methods

### Method 1: Automatic Deployment (GitHub Actions) ✅ RECOMMENDED

**The scripts are already included in the repository**, so when you deploy via GitHub Actions:

1. **Push triggers deployment**:
   ```bash
   # Already done! Changes are in main branch
   git log -1 --oneline
   # Should show: 5f1afc5 feat: add SSL certificate setup with Let's Encrypt automation
   ```

2. **GitHub Actions will automatically**:
   - Pull latest code to server (including SSL scripts)
   - Scripts will be in `/var/www/vibetravels/`
   - Scripts are already executable (permissions preserved in git)

3. **After deployment, SSH to server**:
   ```bash
   ssh deploy@YOUR_SERVER_IP
   cd /var/www/vibetravels

   # Verify scripts are there
   ls -la *.sh
   # Should show:
   # -rwxr-xr-x 1 deploy deploy ... check-ssl-status.sh
   # -rwxr-xr-x 1 deploy deploy ... init-letsencrypt.sh
   ```

4. **Run SSL setup**:
   ```bash
   # First check if certificates exist
   ./check-ssl-status.sh

   # If certificates don't exist, configure email and run:
   nano init-letsencrypt.sh  # Change EMAIL="your-email@example.com"
   ./init-letsencrypt.sh
   ```

---

### Method 2: Manual SSH Upload (Alternative)

If you need to manually upload scripts before git deployment:

```bash
# From your LOCAL machine (in project directory):

# Upload SSL scripts to server
scp init-letsencrypt.sh deploy@YOUR_SERVER_IP:/var/www/vibetravels/
scp check-ssl-status.sh deploy@YOUR_SERVER_IP:/var/www/vibetravels/

# SSH to server
ssh deploy@YOUR_SERVER_IP

# Go to project directory
cd /var/www/vibetravels

# Make scripts executable (if needed)
chmod +x init-letsencrypt.sh
chmod +x check-ssl-status.sh

# Configure email
nano init-letsencrypt.sh  # Change EMAIL line

# Run setup
./init-letsencrypt.sh
```

---

### Method 3: Git Pull on Server (Manual Deployment)

If you deploy manually via git pull:

```bash
# SSH to server
ssh deploy@YOUR_SERVER_IP

# Go to project directory
cd /var/www/vibetravels

# Pull latest changes
git pull origin main

# Verify scripts
ls -la *.sh

# If scripts are not executable:
chmod +x init-letsencrypt.sh check-ssl-status.sh

# Configure email
nano init-letsencrypt.sh  # Change EMAIL line

# Run setup
./init-letsencrypt.sh
```

---

## ✅ Complete Deployment Workflow

### Step 1: Trigger Deployment

```bash
# Option A: Push to main (automatic deployment)
git push origin main
# GitHub Actions will deploy automatically

# Option B: Manual deployment trigger
# Go to: https://github.com/pwaszkis/10xdevs/actions
# Select: Deploy to Production
# Click: Run workflow
```

### Step 2: Wait for Deployment

Monitor GitHub Actions:
- https://github.com/pwaszkis/10xdevs/actions
- Wait for "Deploy to Production" to complete (2-3 minutes)

### Step 3: SSH to Server

```bash
ssh deploy@YOUR_SERVER_IP
cd /var/www/vibetravels
```

### Step 4: Verify Scripts Are Present

```bash
ls -la *.sh
# Should show:
# -rwxr-xr-x ... check-ssl-status.sh
# -rwxr-xr-x ... init-letsencrypt.sh
```

### Step 5: Check SSL Status

```bash
./check-ssl-status.sh
```

**If certificates already exist** → You're done! ✅

**If certificates don't exist** → Continue to Step 6

### Step 6: Configure Email and Run Setup

```bash
# Edit script
nano init-letsencrypt.sh

# Find this line:
EMAIL="your-email@example.com"

# Change to your actual email:
EMAIL="hello@vibetravels.com"  # or your email

# Save: Ctrl+X, Y, Enter

# Run setup
./init-letsencrypt.sh
```

### Step 7: Wait for Completion

The script will:
1. Create directories
2. Download TLS parameters
3. Create dummy certificate
4. Start Nginx
5. Request real certificate from Let's Encrypt
6. Replace dummy with real certificate
7. Reload Nginx
8. Start Certbot auto-renewal

**Expected time**: 2-3 minutes

### Step 8: Verify SSL Works

```bash
# Check certificate status
./check-ssl-status.sh

# Test HTTPS locally
curl -I https://localhost -k

# From your browser
# Visit: https://vibetravels.com
# Should show green padlock 🔒
```

---

## 📋 Pre-Deployment Checklist

Before running SSL setup on server, ensure:

- ✅ **DNS is configured**: `dig vibetravels.com +short` returns server IP
- ✅ **Ports are open**: 80 and 443 allowed in firewall
- ✅ **Application is deployed**: Docker containers running
- ✅ **HTTP works**: `curl http://vibetravels.com` returns response
- ✅ **Domain propagated**: Wait 1-2 hours after DNS change

**Check DNS propagation:**
```bash
# From your local machine
dig vibetravels.com +short
# Should return: YOUR_SERVER_IP

# Test from different DNS servers
dig vibetravels.com @8.8.8.8 +short
dig vibetravels.com @1.1.1.1 +short
# Both should return same IP
```

---

## 🔧 Troubleshooting

### Scripts Not Executable

```bash
chmod +x init-letsencrypt.sh check-ssl-status.sh
```

### Scripts Not Found After Deployment

```bash
# Pull manually
cd /var/www/vibetravels
git pull origin main

# Or check if in different directory
find /var/www -name "init-letsencrypt.sh"
```

### DNS Not Propagating

```bash
# Wait longer (up to 24-48 hours)
# Check with online tools:
# https://www.whatsmydns.net/#A/vibetravels.com
```

### Port 80 Blocked

```bash
sudo ufw status
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

### Certificate Request Failed

```bash
# View logs
docker compose -f docker-compose.production.yml logs certbot

# Use staging mode first (test)
nano init-letsencrypt.sh
# Change: STAGING=1
./init-letsencrypt.sh

# Then use production
nano init-letsencrypt.sh
# Change: STAGING=0
./init-letsencrypt.sh
```

---

## 📞 Need Help?

1. **Check detailed guide**: `SSL-SETUP.md`
2. **Full deployment docs**: `DEPLOYMENT.md` (SSL Certificate Setup section)
3. **View logs**: `docker compose -f docker-compose.production.yml logs certbot`
4. **Check status**: `./check-ssl-status.sh`

---

## 🎯 Quick Commands Summary

```bash
# After deployment, on server:

# 1. Check if scripts exist
ls -la *.sh

# 2. Check SSL status
./check-ssl-status.sh

# 3. If needed, setup SSL
nano init-letsencrypt.sh  # Update EMAIL
./init-letsencrypt.sh

# 4. Verify
./check-ssl-status.sh
curl https://vibetravels.com
```

---

**That's it! Your SSL certificates will be automatically managed. 🎉**
