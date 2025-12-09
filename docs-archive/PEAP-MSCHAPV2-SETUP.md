# How to Enable PEAP-MSCHAPv2 Support

## Overview

The system now supports **hybrid authentication**:
- **TTLS-PAP**: Uses LDAP bind (no password hash needed) ✓
- **PEAP-GTC**: Uses LDAP bind inside GTC (no password hash needed) ✓  
- **PEAP-MSCHAPv2**: Uses NT-Password hash from database (requires setup below)

## Why MSCHAPv2 Needs Extra Setup

MSCHAPv2 requires password hashes because it validates passwords client-side:
1. Client computes response using NT-Password hash
2. Server compares client's response with expected response
3. Server must have the hash to compute the expected response

Google Workspace LDAP doesn't expose password hashes, so we must:
- Store hashes in the FreeRADIUS `radcheck` table
- OR use TTLS/GTC which validates via LDAP bind instead

## Method 1: Manual NT-Password Hash Entry (Best for Testing)

### Option A: PowerShell (Windows)

```powershell
# First, run the hash generator script
cd C:\Development\freeradius-google-ldap-dashboard
.\helper-scripts\generate-nt-password.ps1 -Username "user@krea.edu.in" -Password "MyPassword123"
```

Output:
```
====================
NT-Password Hash Generated
====================

Username: user@krea.edu.in
NT-Hash:  [HEX_STRING_HERE]

SQL to insert into radcheck:
INSERT INTO radcheck (username, attribute, op, value) VALUES
  ('user@krea.edu.in', 'NT-Password', ':=', '0x[HEX_STRING]');
```

Then run the INSERT:
```powershell
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e `
"INSERT INTO radcheck (username, attribute, op, value) VALUES ('user@krea.edu.in', 'NT-Password', ':=', '0x[HEX_STRING]');"
```

### Option B: Linux/Mac (Bash)

```bash
cd freeradius-google-ldap-dashboard
./helper-scripts/generate-nt-password.sh user@krea.edu.in MyPassword123
```

Then run the INSERT:
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius << EOF
INSERT INTO radcheck (username, attribute, op, value) VALUES
  ('user@krea.edu.in', 'NT-Password', ':=', '0x[HEX_STRING]');
EOF
```

### Option C: Direct SQL

```bash
# Connect to MySQL and insert hash manually
docker exec -it radius-mysql mysql -u radius -pRadiusDbPass2024! radius

# Then run:
# INSERT INTO radcheck (username, attribute, op, value) VALUES
#   ('user@krea.edu.in', 'NT-Password', ':=', '0x[HEX_STRING]');
```

## Method 2: Automatic Sync from Google Workspace (Enterprise)

For production, sync passwords from Google Workspace to FreeRADIUS:

### Step 1: Create Admin Sync Account in Google Workspace
1. Go to Google Admin Console
2. Create service account with Directory API access
3. Delegate domain-wide authority for user profile access

### Step 2: Set Up Password Sync Script

(Example Python script for syncing users)

```python
#!/usr/bin/env python3
import subprocess
import hashlib
import mysql.connector
from google.oauth2 import service_account
from googleapiclient.discovery import build

# Google Workspace API setup
SCOPES = ['https://www.googleapis.com/auth/admin.directory.user']
credentials = service_account.Credentials.from_service_account_file(
    'service-account.json', scopes=SCOPES)
service = build('admin', 'directory_v1', credentials=credentials)

# MySQL setup
db = mysql.connector.connect(
    host="localhost",
    user="radius",
    password="RadiusDbPass2024!",
    database="radius"
)
cursor = db.cursor()

# Get all users from Google Workspace
results = service.users().list(domain='krea.edu.in').execute()

for user in results.get('users', []):
    email = user['primaryEmail']
    password = input(f"Password for {email}: ")
    
    # Generate NT-Password hash
    nt_hash = hashlib.new('md4', password.encode('utf-16-le')).hexdigest().upper()
    
    # Insert into radcheck
    sql = "INSERT INTO radcheck (username, attribute, op, value) VALUES (%s, %s, %s, %s)"
    values = (email, 'NT-Password', ':=', f'0x{nt_hash}')
    cursor.execute(sql, values)

db.commit()
db.close()
```

**NOTE**: Never store plaintext passwords! This is just for generating hashes.

## Verification

### Verify Hash is in Database

```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e \
"SELECT username, attribute, value FROM radcheck WHERE attribute='NT-Password';"
```

Should show:
```
+----------------------+-------------+------------------------------------------+
| username             | attribute   | value                                    |
+----------------------+-------------+------------------------------------------+
| user@krea.edu.in     | NT-Password | 0x[HEX_STRING_HERE]                      |
+----------------------+-------------+------------------------------------------+
```

### Test PEAP-MSCHAPv2 Authentication

1. **Configure Windows WiFi**:
   - Network: TEST_RADIUS
   - Security: WPA2-Enterprise
   - EAP Method: PEAP
   - Phase 2: MSCHAPv2
   - Username: user@krea.edu.in
   - Password: MyPassword123

2. **Or configure Mac**:
   - Network: TEST_RADIUS
   - Security: WPA2 Enterprise
   - EAP: PEAP
   - Inner Identity: MSCHAPv2
   - Username: user@krea.edu.in
   - Password: MyPassword123

3. **Or Aruba AP**: Configure PEAP with MSCHAPv2

### Check Authentication Logs

```bash
docker logs freeradius-google-ldap 2>&1 | grep -A 5 "user@krea.edu.in" | tail -20
```

Look for:
- ✅ `mschap: ... AUTH-RESPONSE is correct` (Success)
- ❌ `mschap: ERROR: MS-CHAP2-Response is incorrect` (Wrong password)
- ❌ `mschap: WARNING: No Cleartext-Password` (No hash - use LDAP)

### Check Database Log

```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e \
"SELECT username, reply, authdate FROM radpostauth WHERE username='user@krea.edu.in' ORDER BY authdate DESC LIMIT 5;"
```

Should show:
```
+--------------------+-----------+---------------------+
| username           | reply     | authdate            |
+--------------------+-----------+---------------------+
| user@krea.edu.in   | Access-Accept | 2025-12-08 13:30:00 |
+--------------------+-----------+---------------------+
```

## Authentication Precedence

The system will try authentication methods in this order:

1. **NT-Password hash found** → Use MSCHAPv2 (if client supports it)
2. **EAP-Type detected** → Use LDAP bind with GTC
3. **User-Password available** → Use LDAP bind with PAP

This means:
- **Windows PEAP-MSCHAPv2** → Works if hash synced ✓
- **Windows TTLS-PAP** → Always works (uses LDAP) ✓
- **Mac/Linux PEAP** → Works with LDAP (uses GTC) ✓
- **Aruba AP (flexible)** → Works with any method ✓

## Hybrid Operation Benefits

| Scenario | PEAP-MSCHAPv2 | TTLS-PAP | PEAP-GTC | Status |
|----------|---|---|---|---|
| User has NT-hash in radcheck | ✓ Works | Fallback to LDAP | Fallback to LDAP | **MSCHAPv2** |
| User has NO NT-hash | ❌ Fails | ✓ Works | ✓ Works | **LDAP** |
| First login (no hash yet) | ❌ Fails | ✓ Works | ✓ Works | **LDAP** |
| Hash out of sync with password | ❌ Fails | ✓ Works | ✓ Works | **LDAP (Safe)** |

## Security Notes

1. **Never transmit plaintext passwords** to generate hashes
2. **Store hashes securely** (they're in MySQL, access via VPN)
3. **Sync hashes regularly** if using password sync
4. **Fallback to LDAP** is safer if hashes might be out of sync
5. **LDAP bind** validates real-time against Google Workspace

## Configuration Details

### Current Configuration

File: `/etc/freeradius/sites-enabled/inner-tunnel`

```
# If EAP detected, use LDAP bind
if (&EAP-Type) {
    Auth-Type := ldap
}
# If no EAP but password available and no hash, use LDAP bind
elsif (User-Password && !(&reply:NT-Password || &control:NT-Password)) {
    Auth-Type := ldap
}
```

This means:
- MSCHAPv2 can still use hashes if available (mschap module in authenticate)
- Anything else falls back to LDAP for safety

### Authenticate Section

```
Auth-Type PAP {
    # ... password decoding ...
    ldap
}
Auth-Type LDAP {
    ldap
}
# EAP (includes MSCHAPv2) handled separately
eap
```

This ensures:
- MSCHAPv2 clients with hashes work
- Everyone else uses LDAP
- No service disruption if hashes missing

