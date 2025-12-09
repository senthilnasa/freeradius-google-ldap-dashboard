# VLAN Fix for Failed Authentications - December 9, 2024

## Issue

VLAN ID (248) was being logged in the database even for **Access-Reject** (failed authentications). This is incorrect - VLAN should only be assigned for successful authentications.

### Evidence

```sql
mysql> SELECT id, username, reply, vlan, authdate FROM radpostauth ORDER BY authdate DESC LIMIT 5;
+----+----------------------------------------+---------------+------+---------------------+
| id | username                               | reply         | vlan | authdate            |
+----+----------------------------------------+---------------+------+---------------------+
| 17 | shivakumar.ghantasala@krea.edu.in      | Access-Reject | 248  | 2025-12-09 08:38:49 | ← WRONG!
| 16 | senthil.karuppusamy@krea.edu.in        | Access-Accept | 248  | 2025-12-08 23:09:21 | ← Correct
| 15 | senthil.karuppusamy@krea.edu.in        | Access-Accept | 248  | 2025-12-08 23:06:57 | ← Correct
| 14 | shivakumar.ghantasala@krea.edu.in      | Access-Reject | 248  | 2025-12-08 23:04:48 | ← WRONG!
+----+----------------------------------------+---------------+------+---------------------+
```

**Problem**: Failed authentications (Access-Reject) should have VLAN = NULL or empty, not "248".

---

## Root Cause

VLAN attributes were being set in the **authorize** section (before authentication completes), and they were not being cleared when authentication failed.

**Flow**:
1. User attempts authentication
2. **Authorize section** runs → VLAN set to "248"
3. Authentication fails (wrong password)
4. **Post-Auth-Type REJECT** runs → VLAN attributes still in reply
5. SQL logging → VLAN "248" stored in database ❌

---

## Solution

Clear VLAN attributes from the reply in the **Post-Auth-Type REJECT** section for both inner-tunnel and default site.

### Fix #1: Inner-Tunnel REJECT Handler

Modified **[configs/inner-tunnel](configs/inner-tunnel)** lines 479-492:

```unlang
Post-Auth-Type REJECT {
    # Clear VLAN attributes from reply for failed authentications
    # VLAN should only be assigned for successful authentications
    update reply {
        Tunnel-Type !* ANY
        Tunnel-Medium-Type !* ANY
        Tunnel-Private-Group-Id !* ANY
    }
    # Also clear from session-state
    update session-state {
        Tunnel-Type !* ANY
        Tunnel-Medium-Type !* ANY
        Tunnel-Private-Group-Id !* ANY
    }

    # log failed authentications in SQL, too.
    -sql

    # ... rest of error handling ...
}
```

**Explanation**:
- `!* ANY` means "delete all instances of this attribute"
- Clear from both `reply` and `session-state` to ensure no VLAN leaks through
- This runs BEFORE SQL logging, so database gets empty VLAN

---

### Fix #2: Default Site REJECT Handler

Modified **[configs/default](configs/default)** lines 955-962:

```unlang
Post-Auth-Type REJECT {
    # Clear VLAN attributes from reply for failed authentications
    # VLAN should only be assigned for successful authentications
    update reply {
        Tunnel-Type !* ANY
        Tunnel-Medium-Type !* ANY
        Tunnel-Private-Group-Id !* ANY
    }

    # ... rest of error handling ...
}
```

**Note**: For default site, we don't need to clear session-state because the comment says "The 'session-state' attributes are not available here."

---

## Expected Result

After this fix, the database should show:

```sql
+----+----------------------------------------+---------------+------+---------------------+
| id | username                               | reply         | vlan | authdate            |
+----+----------------------------------------+---------------+------+---------------------+
| 20 | wrong.user@krea.edu.in                 | Access-Reject | NULL | 2025-12-09 09:00:00 | ← Fixed!
| 19 | valid.user@krea.edu.in                 | Access-Accept | 248  | 2025-12-09 08:59:00 | ← Correct
+----+----------------------------------------+---------------+------+---------------------+
```

**Rules**:
- ✅ **Access-Accept** (successful auth) → VLAN = "248" (or configured VLAN)
- ✅ **Access-Reject** (failed auth) → VLAN = NULL (empty)

---

## Testing

### Test 1: Successful Authentication

**Action**: Authenticate with correct credentials

**Expected Database Entry**:
```sql
username: user@krea.edu.in
reply: Access-Accept
vlan: 248
```

**Check**:
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT username, reply, vlan FROM radpostauth WHERE reply='Access-Accept' ORDER BY authdate DESC LIMIT 3"
```

---

### Test 2: Failed Authentication

**Action**: Authenticate with wrong password

**Expected Database Entry**:
```sql
username: user@krea.edu.in
reply: Access-Reject
vlan: NULL (or empty)
```

**Check**:
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT username, reply, vlan FROM radpostauth WHERE reply='Access-Reject' ORDER BY authdate DESC LIMIT 3"
```

**Expected Output**:
```
username                        reply           vlan
user@krea.edu.in                Access-Reject   NULL
```

---

### Test 3: Check FreeRADIUS Logs

**View what's being sent to AP for failed auth**:
```bash
docker logs freeradius-google-ldap 2>&1 | grep -B 2 -A 10 "Access-Reject"
```

**Expected**: Should NOT see `Tunnel-Private-Group-Id` in the reject response.

---

## Deployment Status

✅ **Fixed and Deployed**

### Changes Made:
1. ✅ Updated `configs/inner-tunnel` - Added VLAN clearing in Post-Auth-Type REJECT
2. ✅ Updated `configs/default` - Added VLAN clearing in Post-Auth-Type REJECT
3. ✅ Rebuilt FreeRADIUS container
4. ✅ Restarted containers

### Current Status:
```bash
$ docker ps
NAMES                    STATUS
freeradius-google-ldap   Up 2 minutes (healthy)
radius-webapp            Up 12 hours (healthy)
radius-mysql             Up 12 hours (healthy)
```

All containers running and healthy ✅

---

## Files Modified

| File | Lines | Change |
|------|-------|--------|
| [configs/inner-tunnel](configs/inner-tunnel) | 479-492 | Clear VLAN attributes in Post-Auth-Type REJECT |
| [configs/default](configs/default) | 955-962 | Clear VLAN attributes in Post-Auth-Type REJECT |

---

## Why This Matters

### Security
- Failed authentications should not receive network access attributes
- VLAN assignment implies successful authentication and network authorization
- Prevents confusion about who was granted network access

### Reporting Accuracy
- Reports show correct VLAN distribution for successful authentications only
- Failed auth logs don't pollute VLAN statistics
- Clear distinction between successful and failed authentications

### Compliance
- Audit logs accurately reflect network access grants
- VLAN presence indicates authorization was granted
- Empty VLAN for failures shows access was denied

---

## Quick Verification Commands

### Check Recent Authentications
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "
SELECT
    username,
    reply,
    COALESCE(vlan, 'NULL') as vlan,
    authdate
FROM radpostauth
ORDER BY authdate DESC
LIMIT 10"
```

### Count Success vs Failure with VLAN
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "
SELECT
    reply,
    COUNT(*) as total,
    SUM(CASE WHEN vlan IS NOT NULL AND vlan != '' THEN 1 ELSE 0 END) as has_vlan,
    SUM(CASE WHEN vlan IS NULL OR vlan = '' THEN 1 ELSE 0 END) as no_vlan
FROM radpostauth
GROUP BY reply"
```

**Expected Output**:
```
reply           total   has_vlan   no_vlan
Access-Accept   100     100        0       ← All successes should have VLAN
Access-Reject   50      0          50      ← All failures should have no VLAN
```

---

## Troubleshooting

### If VLAN still appears for Access-Reject:

**Check 1: Verify fix was applied**
```bash
docker exec freeradius-google-ldap sh -c "grep -A 10 'Post-Auth-Type REJECT' /etc/freeradius/sites-enabled/inner-tunnel | grep -i tunnel"
```

Should show the `Tunnel-Type !* ANY` lines.

**Check 2: Container was rebuilt**
```bash
docker inspect freeradius-google-ldap | grep Created
```

Should show recent creation time (within last few minutes).

**Check 3: Check FreeRADIUS logs**
```bash
docker logs freeradius-google-ldap 2>&1 | tail -100 | grep -i "Post-Auth-Type REJECT"
```

Should show REJECT handler executing.

**If still not working**:
```bash
# Rebuild and restart
cd /path/to/freeradius-google-ldap-dashboard
docker-compose build freeradius
docker-compose up -d freeradius

# Wait for startup
sleep 5

# Test authentication
# Check database for new entries
```

---

## Related Documentation

- [VLAN_LOGGING_FIX.md](VLAN_LOGGING_FIX.md) - Original fix for VLAN not being logged
- [HOW_TO_VIEW_RADIUS_RESPONSES.md](HOW_TO_VIEW_RADIUS_RESPONSES.md) - How to view what's sent to APs
- [UI_VLAN_DISPLAY_UPDATE.md](UI_VLAN_DISPLAY_UPDATE.md) - VLAN display in web UI
- [IMPLEMENTATION_COMPLETE.md](IMPLEMENTATION_COMPLETE.md) - Complete implementation summary

---

## Summary

**Before Fix**:
- ❌ Access-Accept → VLAN = "248" ✓
- ❌ Access-Reject → VLAN = "248" ✗ (Wrong!)

**After Fix**:
- ✅ Access-Accept → VLAN = "248" ✓
- ✅ Access-Reject → VLAN = NULL ✓

**Implementation Date**: December 9, 2024
**Status**: ✅ Fixed and Deployed
**Breaking Changes**: None
**Backward Compatible**: Yes

The fix ensures that VLAN attributes are only assigned to successful authentications, providing accurate logging and reporting.
