# Cloudflare 502 Bad Gateway - Diagnosis and Fix

**Problem**: Website (https://przem-podroze.pl/) returns 502 Bad Gateway error ~30% of the time
**Date**: 2025-12-02
**Status**: Fix prepared, awaiting deployment

---

## Problem Summary

### Symptoms
- ~30-40% of requests through Cloudflare return 502 Bad Gateway
- Direct IP access (http://161.35.66.105/) works perfectly
- Server responds in <1 second (not a timeout issue)
- Error header shows `server: cloudflare` (error originates from Cloudflare, not origin)
- **Nginx logs show NO Cloudflare IP addresses** (critical finding)

### Impact
- Website appears unstable to users
- Intermittent failures cause poor user experience
- No pattern to failures (random ~30% of requests)

---

## Root Cause Analysis

### Key Finding: Cloudflare IPs Missing from Nginx Logs

Nginx access logs only show bot traffic, **NOT Cloudflare IP ranges**:
```bash
# Expected Cloudflare IP ranges in logs:
172.68.0.0/16, 173.245.48.0/20, 104.21.0.0/16, etc.

# Actual: Only bot IPs like:
43.135.181.189, 52.167.144.230, etc.
```

**This indicates**: Cloudflare edge servers cannot consistently establish SSL connections to the origin server.

### Likely Root Causes

1. **SSL Certificate Validation Issue**
   - Current setup uses Let's Encrypt certificate
   - Certificate may be expired or invalid
   - Cloudflare SSL mode is "Full" but certificate validation fails intermittently

2. **SSL Handshake Failures**
   - Cloudflare edge cannot complete TLS handshake with origin
   - Results in 502 instead of passing request to origin
   - Explains why no Cloudflare IPs appear in logs (connection fails before HTTP request)

3. **OCSP Stapling Problems**
   - OCSP stapling enabled for Let's Encrypt certificate
   - OCSP responder may be unreachable or timing out
   - Causes some SSL handshakes to fail

---

## Solution: Switch to Cloudflare Origin Certificate

### Why Cloudflare Origin Certificate?

Cloudflare Origin Certificates are specifically designed for the Cloudflare → Origin connection:
- **Trusted by Cloudflare** (even though not publicly trusted)
- **15-year validity** (vs 90 days for Let's Encrypt)
- **No OCSP stapling required** (Cloudflare doesn't check OCSP for its own certs)
- **Eliminates certificate renewal issues**

### Implementation

The fix involves:
1. Generate Cloudflare Origin Certificate in Cloudflare Dashboard
2. Install certificate on origin server
3. Update Nginx configuration to use Cloudflare certificate
4. Disable OCSP stapling (not needed for Origin Certificates)
5. Restart Nginx to apply changes

---

## Deployment Instructions

### Prerequisites

You should have already generated the Cloudflare Origin Certificate in the previous troubleshooting session. Verify the files exist on the production server:

```bash
ssh deploy@przem-podroze.pl
cd /var/www/vibetravels

# Check if certificate files exist
ls -la ssl/cloudflare/
# Should show:
# cert.pem (Cloudflare Origin Certificate)
# key.pem (Private key)
```

If files don't exist, generate them:

1. Go to: https://dash.cloudflare.com/ → przem-podroze.pl → SSL/TLS → Origin Server
2. Click **Create Certificate**
3. Use default settings:
   - Let Cloudflare generate a private key and CSR
   - Hostnames: `*.przem-podroze.pl, przem-podroze.pl`
   - Certificate Validity: 15 years
   - Key format: RSA (2048)
4. Click **Create**
5. Copy the **Origin Certificate** to `ssl/cloudflare/cert.pem`
6. Copy the **Private Key** to `ssl/cloudflare/key.pem`

```bash
# On production server
mkdir -p /var/www/vibetravels/ssl/cloudflare/
nano /var/www/vibetravels/ssl/cloudflare/cert.pem
# Paste certificate, save (Ctrl+X, Y, Enter)

nano /var/www/vibetravels/ssl/cloudflare/key.pem
# Paste private key, save (Ctrl+X, Y, Enter)

# Set proper permissions
chmod 600 ssl/cloudflare/key.pem
chmod 644 ssl/cloudflare/cert.pem
```

### Step 1: Commit and Deploy Configuration Changes

On your local machine:

```bash
cd /home/global/projekty/10xdevs

# Review changes
git diff docker/nginx/production.conf

# Stage changes
git add docker/nginx/production.conf
git add scripts/fix-cloudflare-502.sh
git add CLOUDFLARE-502-FIX.md

# Commit
git commit -m "fix: switch to Cloudflare Origin Certificate to resolve 502 errors

- Update Nginx to use Cloudflare Origin Certificate instead of Let's Encrypt
- Disable OCSP stapling (not supported for Origin Certificates)
- Add diagnostic script to verify SSL configuration
- Root cause: Cloudflare edge servers failing SSL handshake with Let's Encrypt cert

Resolves: Sporadic 502 Bad Gateway errors (~30% of requests)"

# Push to repository
git push origin main
```

### Step 2: Deploy on Production Server

```bash
ssh deploy@przem-podroze.pl
cd /var/www/vibetravels

# Pull latest changes
git pull origin main

# Make diagnostic script executable
chmod +x scripts/fix-cloudflare-502.sh

# Run diagnostic and apply fix
./scripts/fix-cloudflare-502.sh
```

The script will:
1. ✅ Verify Cloudflare Origin Certificate files exist
2. ✅ Check current SSL certificate in use
3. ✅ Scan logs for SSL errors
4. ✅ Test SSL handshake
5. ✅ Validate Nginx configuration syntax
6. ✅ Restart Nginx container
7. ✅ Verify Cloudflare certificate is active
8. ✅ Test website accessibility (10 requests to check for 502s)

### Step 3: Verify Cloudflare Settings

Log in to Cloudflare Dashboard and verify:

**SSL/TLS Settings** (https://dash.cloudflare.com/ → przem-podroze.pl → SSL/TLS):
- ✅ SSL/TLS encryption mode: **Full** (not Full (strict))
- ✅ Minimum TLS Version: **1.2**
- ✅ Opportunistic Encryption: **On**
- ✅ TLS 1.3: **On**
- ✅ Automatic HTTPS Rewrites: **On**

**Important**: Use **"Full"** mode, not **"Full (strict)"**. Cloudflare Origin Certificates are not publicly trusted, so "Full (strict)" will fail validation.

**Caching** (https://dash.cloudflare.com/ → przem-podroze.pl → Caching):
- Purge Everything (to clear cached 502 responses)

### Step 4: Monitor and Verify

Wait 5-10 minutes for Cloudflare edge cache to update, then test:

```bash
# Test from your local machine
for i in {1..20}; do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://przem-podroze.pl/)
  echo "Request $i: $STATUS"
  sleep 2
done
```

**Expected result**: All requests should return `200` or `301` (no 502 errors)

Check Nginx logs on production:
```bash
ssh deploy@przem-podroze.pl
cd /var/www/vibetravels

# Watch access logs (you should now see Cloudflare IPs)
docker compose -f docker-compose.production.yml logs -f nginx | grep -E "172.68|173.245|104.21"

# Check for SSL errors
docker compose -f docker-compose.production.yml logs nginx --tail=100 | grep -i "ssl\|certificate"
```

---

## Expected Outcomes

### Before Fix
- ❌ ~30% of requests return 502 Bad Gateway
- ❌ No Cloudflare IP addresses in Nginx logs
- ❌ Intermittent SSL handshake failures
- ❌ Users experience unstable website

### After Fix
- ✅ 0% 502 errors (all requests succeed)
- ✅ Cloudflare IP addresses appear in Nginx logs
- ✅ Stable SSL connections between Cloudflare and origin
- ✅ Website performs reliably

### Technical Changes
| Setting | Before | After |
|---------|--------|-------|
| SSL Certificate | Let's Encrypt | Cloudflare Origin Certificate |
| Certificate Path | `/etc/letsencrypt/live/przem-podroze.pl/fullchain.pem` | `/var/www/ssl/cloudflare/cert.pem` |
| OCSP Stapling | Enabled | Disabled (not needed) |
| Certificate Validity | 90 days | 15 years |
| Cloudflare SSL Mode | Full | Full |

---

## Troubleshooting

### If 502 errors persist after deployment:

**1. Verify certificate is actually loaded**:
```bash
docker compose -f docker-compose.production.yml exec nginx nginx -T | grep ssl_certificate
# Should show: /var/www/ssl/cloudflare/cert.pem
```

**2. Check certificate files are accessible**:
```bash
docker compose -f docker-compose.production.yml exec nginx ls -la /var/www/ssl/cloudflare/
# Should list cert.pem and key.pem
```

**3. Test SSL handshake manually**:
```bash
echo | openssl s_client -connect 161.35.66.105:443 -servername przem-podroze.pl 2>/dev/null | openssl x509 -noout -subject -issuer
# Issuer should show: Cloudflare
```

**4. Check Nginx error logs**:
```bash
docker compose -f docker-compose.production.yml logs nginx --tail=50 | grep -i error
```

**5. Verify Cloudflare SSL mode**:
- Must be **"Full"** (not "Full (strict)" or "Flexible")
- Change at: https://dash.cloudflare.com/ → SSL/TLS → Overview

**6. Purge Cloudflare cache again**:
- Go to: https://dash.cloudflare.com/ → Caching → Configuration
- Click **Purge Everything**
- Wait 5 minutes

**7. Check firewall rules**:
```bash
# On production server
sudo ufw status
sudo iptables -L -n | grep -E "ACCEPT|DROP"
```

### If certificate files are missing:

Re-generate Cloudflare Origin Certificate following the steps in Prerequisites section above.

---

## Additional Notes

### Why Not Let's Encrypt?

Let's Encrypt certificates work fine with Cloudflare, but:
- ❌ Require renewal every 90 days (automation can fail)
- ❌ OCSP stapling can cause intermittent failures
- ❌ Certbot container adds complexity
- ❌ DNS challenges can fail

Cloudflare Origin Certificates:
- ✅ Valid for 15 years (set and forget)
- ✅ No OCSP stapling needed
- ✅ No renewal automation required
- ✅ Specifically designed for Cloudflare → Origin connection

### Security Considerations

**Q: Is it safe to disable OCSP stapling?**
A: Yes, for Cloudflare Origin Certificates. Cloudflare doesn't use OCSP to validate its own certificates when terminating SSL at the edge.

**Q: Why not use "Full (strict)" SSL mode?**
A: "Full (strict)" requires a publicly-trusted certificate (e.g., Let's Encrypt). Cloudflare Origin Certificates are only trusted by Cloudflare, not public CAs. Using "Full (strict)" with an Origin Certificate will cause validation failures.

**Q: Is the connection between Cloudflare and origin encrypted?**
A: Yes! "Full" mode means:
- User → Cloudflare: HTTPS (publicly trusted cert)
- Cloudflare → Origin: HTTPS (Cloudflare Origin Certificate)

Both connections are encrypted end-to-end.

---

## Related Documentation

- Cloudflare Origin Certificates: https://developers.cloudflare.com/ssl/origin-configuration/origin-ca/
- Cloudflare SSL/TLS Encryption Modes: https://developers.cloudflare.com/ssl/origin-configuration/ssl-modes/
- Previous deployment fix: `DEPLOYMENT-FIX.md`
- Nginx production config: `docker/nginx/production.conf`
- Docker Compose: `docker-compose.production.yml`

---

**Last Updated**: 2025-12-02
**Status**: Ready for deployment
