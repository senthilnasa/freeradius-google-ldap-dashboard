# VLAN Logging Fix - Quick Summary

## Problem Identified ✅

**Issue**: VLAN IDs were not being logged to the database despite configuration being correct.

**Cause**: For EAP-TTLS/PEAP authentication:
- VLAN attributes set in **outer** authentication (default site)
- SQL logging happens in **inner-tunnel**
- VLAN attributes from outer auth were not accessible in inner tunnel
- Result: `vlan` column always NULL

**Evidence**:
```
FreeRADIUS Log:
(56) sql: --> INSERT INTO radpostauth (..., vlan, ...) VALUES (..., '', ...)
                                                                      ^^^ EMPTY!

Database:
| username                   | vlan |
|----------------------------|------|
| arun.kathirvel@krea.edu.in | NULL |  ← Should be 156
```

---

## Solution Implemented ✅

### Fix #1: Copy VLAN to Session-State

Modified **[init.sh](init.sh)** (3 locations: lines 195-208, 215-228, 249-262):

```bash
# Added session-state update after reply update
update reply {
    Tunnel-Type := VLAN
    Tunnel-Medium-Type := IEEE-802
    Tunnel-Private-Group-Id := "156"
}
# NEW: Copy to session-state for inner tunnel
update session-state {
    Tunnel-Type := VLAN
    Tunnel-Medium-Type := IEEE-802
    Tunnel-Private-Group-Id := "156"
}
```

### Fix #2: Update SQL Query

Modified **[configs/queries.conf](configs/queries.conf)** line 665:

**Before**:
```sql
'%{reply:Tunnel-Private-Group-Id:0}',
```

**After**:
```sql
'%{%{session-state:Tunnel-Private-Group-Id:0}:-%{reply:Tunnel-Private-Group-Id:0}}',
```

This checks session-state first (for tunneled auth), then falls back to reply (for non-tunneled auth).

---

## Deployment Status ✅

1. ✅ **init.sh** - Modified to add session-state updates
2. ✅ **configs/queries.conf** - Updated SQL query to check session-state
3. ✅ **FreeRADIUS Container** - Rebuilt with changes
4. ✅ **Container Running** - Up and healthy
5. ⏳ **Testing** - Waiting for next authentication to verify fix

---

## Verification Steps

### Step 1: Check Configuration ✅

```bash
docker exec freeradius-google-ldap sh -c \
  "grep -A 5 'Copy VLAN to session-state' /etc/freeradius/sites-enabled/default"
```

**Result**: ✅ session-state update blocks present

### Step 2: Check SQL Query ✅

```bash
docker exec freeradius-google-ldap sh -c \
  "grep 'session-state:Tunnel-Private-Group-Id' /etc/freeradius/mods-config/sql/main/mysql/queries.conf"
```

**Result**: ✅ Query uses session-state with fallback

### Step 3: Wait for Authentication ⏳

**Current Status**:
- Last authentication: 2025-12-08 17:30:16 (before fix)
- Fix deployed: 2025-12-08 ~17:45 (after rebuild)
- **Need new authentication to test**

**To verify after next auth**:
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT username, vlan, authdate FROM radpostauth ORDER BY authdate DESC LIMIT 5"
```

**Expected Result**:
```
| username                   | vlan | authdate            |
|----------------------------|------|---------------------|
| arun.kathirvel@krea.edu.in | 156  | 2025-12-08 18:XX:XX |  ← VLAN should be 156!
```

---

## What Changed

### Before Fix:
```
Outer Auth (default site):
  → Set reply:Tunnel-Private-Group-Id = "156"
  → Skip SQL logging (tunneled auth)

Inner Auth (inner-tunnel):
  → SQL logging executes
  → Query: %{reply:Tunnel-Private-Group-Id:0}
  → Result: "" (empty - attribute not in inner tunnel)
  → Database: vlan = NULL ❌
```

### After Fix:
```
Outer Auth (default site):
  → Set reply:Tunnel-Private-Group-Id = "156"
  → Set session-state:Tunnel-Private-Group-Id = "156"  ← NEW!
  → Skip SQL logging (tunneled auth)

Inner Auth (inner-tunnel):
  → SQL logging executes
  → Query: %{session-state:Tunnel-Private-Group-Id:0}
  → Result: "156" (from session-state) ✅
  → Database: vlan = 156 ✅
```

---

## Files Modified

| File | Lines | Purpose |
|------|-------|---------|
| [init.sh](init.sh) | 203-206 | Add session-state for key-based domains |
| [init.sh](init.sh) | 223-226 | Add session-state for domain+key matching |
| [init.sh](init.sh) | 257-260 | Add session-state for legacy format |
| [configs/queries.conf](configs/queries.conf) | 665 | Update SQL query to check session-state |

---

## Next Steps

### For User:

1. **Wait for next authentication** (or authenticate manually)
2. **Check VLAN in UI**:
   - Navigate to http://localhost:8080/index.php?page=auth-log
   - Look for VLAN column showing "156" instead of "-"
3. **Check database**:
   ```bash
   docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
     -e "SELECT username, vlan, authdate FROM radpostauth ORDER BY authdate DESC LIMIT 5"
   ```

### If VLAN Still NULL:

1. Check FreeRADIUS logs:
   ```bash
   docker logs freeradius-google-ldap --tail 200 | grep -i "tunnel-private-group-id"
   ```

2. Verify container rebuild worked:
   ```bash
   docker logs freeradius-google-ldap | grep "VLAN Attributes"
   # Should show: VLAN Attributes: Tunnel-Private-Group-ID,Tunnel-Type,Tunnel-Medium-Type
   ```

3. Manual test (if you have valid credentials):
   ```bash
   echo "User-Name = 'user@krea.edu.in', User-Password = 'password'" | \
     radclient -x localhost:1812 auth KreaRadiusSecret20252024!
   ```

---

## Documentation Created

1. **[VLAN_LOGGING_FIX.md](VLAN_LOGGING_FIX.md)** - Detailed technical documentation
2. **[VLAN_FIX_SUMMARY.md](VLAN_FIX_SUMMARY.md)** - This summary (quick reference)

---

## Expected Outcome

After the next user authentication:

- ✅ VLAN "156" will appear in Authentication Log UI
- ✅ VLAN "156" will be in database (`radpostauth.vlan`)
- ✅ Daily Reports will show VLAN statistics
- ✅ CSV exports will include VLAN data

---

**Status**: ✅ Fix Deployed - Waiting for Test Authentication
**Date**: December 8, 2024
**Time**: ~17:45 IST
**Containers**: All healthy and running
**Breaking Changes**: None
**Backward Compatible**: Yes
