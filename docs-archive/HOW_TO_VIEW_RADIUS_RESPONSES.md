# How to View RADIUS Response Attributes

This guide shows you how to see what information FreeRADIUS is sending back to your AP Controllers (Access Points) during authentication.

---

## Method 1: Enable FreeRADIUS Debug Mode (Most Detailed)

### Step 1: Stop FreeRADIUS
```bash
docker-compose stop freeradius
```

### Step 2: Run in Debug Mode
```bash
docker-compose run --rm freeradius freeradius -X
```

This shows **everything** in real-time, including:
- All incoming RADIUS packets from APs
- Processing steps
- **All reply attributes** being sent back
- VLAN assignments
- Session timeouts
- Error messages

**Example Output**:
```
(12) Sent Access-Accept Id 156 from 10.10.10.2:1812 to 192.168.1.100:49832 length 0
(12)   Tunnel-Type:0 = VLAN
(12)   Tunnel-Medium-Type:0 = IEEE-802
(12)   Tunnel-Private-Group-Id:0 = "156"        ← VLAN being sent
(12)   Class = "Staff"                          ← User type
(12)   Reply-Message = "Authenticated as Staff"
(12)   Session-Timeout = 28800                  ← 8 hours
(12)   Idle-Timeout = 1800                      ← 30 minutes
(12)   MS-MPPE-Recv-Key = <encrypted>
(12)   MS-MPPE-Send-Key = <encrypted>
(12)   EAP-Message = ...
(12)   Message-Authenticator = ...
```

### Step 3: Stop Debug Mode
Press `Ctrl+C` to stop

### Step 4: Restart Normal Mode
```bash
docker-compose up -d freeradius
```

---

## Method 2: View Recent Logs (Quick Check)

### Check Last 100 Lines of Logs
```bash
docker logs freeradius-google-ldap --tail 100
```

### Search for Access-Accept Responses
```bash
docker logs freeradius-google-ldap 2>&1 | grep -A 30 "Access-Accept"
```

### Search for Specific Attributes
```bash
# Look for VLAN assignments
docker logs freeradius-google-ldap 2>&1 | grep "Tunnel-Private-Group-Id"

# Look for session timeouts
docker logs freeradius-google-ldap 2>&1 | grep "Session-Timeout"

# Look for reply messages
docker logs freeradius-google-ldap 2>&1 | grep "Reply-Message"
```

---

## Method 3: Check Specific Authentication

### Find Recent Authentication by Username
```bash
docker logs freeradius-google-ldap 2>&1 | grep -A 50 "arun.kathirvel@krea.edu.in" | grep "reply:"
```

### Example Output:
```
(13) &reply:Tunnel-Type := VLAN
(13) &reply:Tunnel-Medium-Type := IEEE-802
(13) &reply:Tunnel-Private-Group-Id := "156"
(13) &reply:Reply-Message := "Authenticated as Staff"
(13) &reply:Class := "Staff"
(13) &reply:Session-Timeout := 28800
(13) &reply:Idle-Timeout := 1800
```

---

## Method 4: Monitor Live Authentication (Real-Time)

### Watch Logs in Real-Time
```bash
docker logs -f freeradius-google-ldap
```

Then trigger an authentication from a device and watch the output.

**Look for lines like:**
```
Sending Access-Accept Id 123 from 10.10.10.2:1812 to 192.168.1.100:54321
```

Followed by the reply attributes.

---

## Method 5: Check Database for What Was Logged

### View Recent Authentications with All Info
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "
SELECT
    username,
    reply,
    vlan,
    reply_message,
    error_type,
    authdate
FROM radpostauth
ORDER BY authdate DESC
LIMIT 10"
```

**Note**: The database only logs:
- Username
- Reply type (Access-Accept/Access-Reject)
- VLAN ID
- Reply message
- Error type (if failed)

It does **NOT** log:
- Session-Timeout
- Idle-Timeout
- Encryption keys
- Other RADIUS attributes

To see ALL attributes sent to AP, use Method 1 (Debug Mode).

---

## Common RADIUS Reply Attributes

Here's what FreeRADIUS typically sends to AP Controllers for successful authentication:

### For All Users (WPA2-Enterprise)

| Attribute | Value | Purpose |
|-----------|-------|---------|
| **Tunnel-Type** | VLAN | Indicates VLAN tagging |
| **Tunnel-Medium-Type** | IEEE-802 | VLAN uses 802.1Q |
| **Tunnel-Private-Group-Id** | "156" | **VLAN ID** to assign |
| **Reply-Message** | "Authenticated as Staff" | Success message |
| **Class** | "Staff" | User role/type |
| **Session-Timeout** | 28800 | 8 hours (in seconds) |
| **Idle-Timeout** | 1800 | 30 minutes (in seconds) |

### For EAP (Encrypted Tunnel)

| Attribute | Purpose |
|-----------|---------|
| **MS-MPPE-Send-Key** | Encryption key for data to client |
| **MS-MPPE-Recv-Key** | Encryption key for data from client |
| **EAP-Message** | EAP protocol data |
| **Message-Authenticator** | Packet integrity check |

### For Failed Authentications

| Attribute | Value |
|-----------|-------|
| **Reply-Message** | "Authentication failed: Invalid username or password" |

---

## Quick Commands Reference

### View Last Authentication Response
```bash
docker logs freeradius-google-ldap 2>&1 | \
  grep -E "Sending Access-Accept|reply:" | \
  tail -50
```

### View VLAN Assignments Being Sent
```bash
docker logs freeradius-google-ldap 2>&1 | \
  grep "Tunnel-Private-Group-Id" | \
  tail -20
```

### View All Reply Attributes for Latest Auth
```bash
docker logs freeradius-google-ldap 2>&1 | \
  tail -500 | \
  grep -A 20 "Sending Access-Accept" | \
  head -40
```

### Check If Specific VLAN Is Being Sent
```bash
docker logs freeradius-google-ldap 2>&1 | \
  grep "Tunnel-Private-Group-Id.*156"
```

---

## Troubleshooting: VLAN Not Showing Up on AP

### Check 1: Verify VLAN in FreeRADIUS Reply
```bash
docker logs freeradius-google-ldap 2>&1 | \
  grep -E "Tunnel-Private-Group-Id|Tunnel-Type" | \
  tail -10
```

**Expected**:
```
(13)   &reply:Tunnel-Type := VLAN
(13)   &reply:Tunnel-Private-Group-Id := "156"
```

**If missing**: VLAN not being set by FreeRADIUS

### Check 2: Verify Database Has VLAN
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT username, vlan, authdate FROM radpostauth ORDER BY authdate DESC LIMIT 5"
```

**Expected**: VLAN column shows "156"

### Check 3: Verify AP Supports VLAN Attributes

Some AP controllers require specific RADIUS attributes. Check your AP documentation for:
- VLAN assignment attribute name
- Required format (string vs integer)
- Additional attributes needed

**Common AP VLAN Attributes**:
- **Standard**: `Tunnel-Private-Group-Id` (what we use)
- **Aruba**: `Aruba-User-VLAN` or `Aruba-Named-User-VLAN`
- **Cisco**: May use `tunnel-private-group-id` or vendor-specific attributes

### Check 4: View Full Debug Output

If VLAN still not working:

```bash
# Stop FreeRADIUS
docker-compose stop freeradius

# Run in debug mode
docker-compose run --rm freeradius freeradius -X

# Trigger authentication from a device
# Watch the output for "Sending Access-Accept"
# Verify all attributes are present

# Stop with Ctrl+C
# Restart normal mode
docker-compose up -d freeradius
```

---

## Example: Complete Authentication Flow

### Successful Authentication with VLAN

```
(13) Received Access-Request Id 156 from 192.168.1.100:49832 to 10.10.10.2:1812 length 245
(13)   User-Name = "arun.kathirvel@krea.edu.in"
(13)   NAS-IP-Address = 192.168.1.100
(13)   NAS-Port = 1
...
(13) # Executing section authorize from file /etc/freeradius/sites-enabled/inner-tunnel
(13)   if (&User-Name =~ /@(.*)$/) {
(13)   if (&User-Name =~ /@(.*)$/)  -> TRUE
(13)     update request {
(13)       Tmp-String-0 := "krea.edu.in"
(13)     } # update request = noop
(13)   } # if (&User-Name =~ /@(.*)$/)  = noop
(13)   if (&request:Tmp-String-0) {
(13)   if (&request:Tmp-String-0)  -> TRUE
(13)     if (&request:Tmp-String-0 == "krea.edu.in") {
(13)     if (&request:Tmp-String-0 == "krea.edu.in")  -> TRUE
(13)       update control {
(13)         Tmp-String-1 := "Staff"
(13)       } # update control = noop
(13)       update reply {
(13)         &reply:Tunnel-Type := VLAN
(13)         &reply:Tunnel-Medium-Type := IEEE-802
(13)         &reply:Tunnel-Private-Group-Id := "156"    ← VLAN SET
(13)       } # update reply = noop
...
(13) # Executing section post-auth from file /etc/freeradius/sites-enabled/default
(13)   if (&control:Tmp-String-1) {
(13)   if (&control:Tmp-String-1)  -> TRUE
(13)     update reply {
(13)       &reply:Reply-Message := "Authenticated as Staff"
(13)       &reply:Class := "Staff"
(13)     } # update reply = noop
(13)     update reply {
(13)       &reply:Session-Timeout := 28800
(13)       &reply:Idle-Timeout := 1800
(13)     } # update reply = noop
...
(13) Sent Access-Accept Id 156 from 10.10.10.2:1812 to 192.168.1.100:49832 length 0
(13)   Tunnel-Type:0 = VLAN                             ← SENT TO AP
(13)   Tunnel-Medium-Type:0 = IEEE-802                  ← SENT TO AP
(13)   Tunnel-Private-Group-Id:0 = "156"                ← SENT TO AP (VLAN ID)
(13)   Class = "Staff"                                  ← SENT TO AP
(13)   Reply-Message = "Authenticated as Staff"         ← SENT TO AP
(13)   Session-Timeout = 28800                          ← SENT TO AP (8 hours)
(13)   Idle-Timeout = 1800                              ← SENT TO AP (30 min)
(13)   MS-MPPE-Recv-Key = <encrypted>                   ← SENT TO AP
(13)   MS-MPPE-Send-Key = <encrypted>                   ← SENT TO AP
(13)   EAP-Message = <EAP data>                         ← SENT TO AP
(13)   Message-Authenticator = <hash>                   ← SENT TO AP
(13) Finished request
```

---

## Configuration: Current VLAN Settings

Your current configuration from `domain-config.json`:

```json
[
  {"domain":"krea.edu.in","Type":"Staff","VLAN":"156"},
  {"domain":"krea.ac.in","Type":"Student","VLAN":"156"},
  {"domain":"ifmr.ac.in","Type":"Other Center","VLAN":"156"}
]
```

**All domains currently assigned to VLAN 156**

To change VLANs:
1. Edit the DOMAIN_CONFIG in `.env` file
2. Rebuild FreeRADIUS: `docker-compose build freeradius`
3. Restart: `docker-compose up -d freeradius`

---

## Additional Debugging Commands

### Check FreeRADIUS Configuration
```bash
# Verify configuration syntax
docker exec freeradius-google-ldap radiusd -C

# View current VLAN configuration
docker exec freeradius-google-ldap sh -c "cat /etc/freeradius/sites-enabled/default | grep -A 5 'Tunnel-Private-Group-Id'"
```

### Check Inner-Tunnel Configuration
```bash
docker exec freeradius-google-ldap sh -c "cat /etc/freeradius/sites-enabled/inner-tunnel | grep -A 5 'Tunnel-Private-Group-Id'"
```

### Test RADIUS Locally
```bash
# Test authentication (replace with actual credentials)
echo "User-Name = 'user@krea.edu.in', User-Password = 'password'" | \
  radclient -x localhost:1812 auth KreaRadiusSecret20252024!
```

---

## Summary

To see what FreeRADIUS is sending to your AP Controllers:

1. **Quick check**: `docker logs freeradius-google-ldap 2>&1 | grep -A 20 "Sending Access-Accept"`
2. **Detailed view**: Run FreeRADIUS in debug mode: `docker-compose run --rm freeradius freeradius -X`
3. **Monitor live**: `docker logs -f freeradius-google-ldap`
4. **Check database**: `docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "SELECT * FROM radpostauth ORDER BY authdate DESC LIMIT 5"`

The most important attributes being sent are:
- **Tunnel-Private-Group-Id** = VLAN ID (156)
- **Session-Timeout** = 28800 seconds (8 hours)
- **Idle-Timeout** = 1800 seconds (30 minutes)
- **Reply-Message** = User-friendly authentication message

---

**Need Help?**
- Check logs: `docker logs freeradius-google-ldap`
- View config: `docker exec freeradius-google-ldap cat /etc/freeradius/sites-enabled/default`
- Test auth: Use debug mode (`freeradius -X`)
