# FreeRADIUS Authentication Fix - PEAP with Google Workspace LDAP

## Problem

FreeRADIUS authentication was failing for PEAP clients attempting to authenticate with Google Workspace LDAP. The error in the logs was:

```
mschap: WARNING: No Cleartext-Password configured.  Cannot create NT-Password
mschap: ERROR: FAILED: No NT-Password.  Cannot perform authentication
mschap: ERROR: MS-CHAP2-Response is incorrect
```

### Root Cause

The configuration was attempting to use **MSCHAPv2** as the inner-tunnel authentication method for PEAP. MSCHAPv2 requires either:
1. A cleartext password stored in the RADIUS database (`radcheck` table), OR  
2. An NT-Password hash (MD4 of UTF-16 encoded password)

However, the system is configured to use **Google Workspace LDAP for user authentication**, which:
- ✅ Can lookup user information
- ✅ Can validate passwords via LDAP bind (sending username/password to LDAP server)
- ❌ Does NOT expose password hashes in a format that MSCHAPv2 can use

This created an impossible situation: PEAP was trying to use MSCHAPv2, which required a password hash, but the only password source (Google LDAP) couldn't provide one.

## Solution

**Route all EAP authentication through LDAP bind validation instead of password-hash-based methods.**

### Configuration Change

In `/etc/freeradius/sites-enabled/inner-tunnel`, added the following logic to the `authorize` section (after LDAP lookup):

```
#
#  For Google LDAP: Use LDAP bind authentication for all EAP methods
#  This is required because Google LDAP doesn't expose NT-Password
#  When EAP-Type has been set (by EAP module), we need to route to LDAP
#  instead of trying to use password hashes
#
if (&EAP-Type) {
    update control {
        Auth-Type := ldap
    }
}
```

### How It Works

1. **PEAP Outer Tunnel**: Client initiates PEAP with TLS (still uses MSCHAPv2 request format in the tunnel)
2. **Inner-Tunnel Phase 2**: When the inner-tunnel request arrives with an `EAP-Type` attribute set
3. **EAP Detection**: This new condition detects that we're in an EAP flow
4. **LDAP Binding**: Sets `Auth-Type := ldap`, which routes to the LDAP authentication module in the `authenticate` section
5. **Password Validation**: The LDAP module validates the password by attempting a bind to Google Workspace LDAP using the provided username and password
6. **Success/Failure**: If LDAP bind succeeds, authentication succeeds; if it fails, authentication fails

### Why This Works

- **Google LDAP doesn't require password hashes**: It validates passwords in real-time via LDAP bind
- **PEAP inner method**: The EAP configuration already specifies `default_eap_type = gtc` (Generic Token Card) for PEAP
- **GTC + LDAP**: GTC sends the password to the `authenticate` section for validation, and our new rule routes that to LDAP bind
- **Transparent to clients**: Clients still use PEAP; the authentication method change is internal to FreeRADIUS

## Configuration Stack

**PEAP Authentication Flow (After Fix)**:

```
1. Client initiates PEAP connection
   ↓
2. OUTER LAYER: TLS tunnel established (TLS 1.2)
   ↓
3. INNER TUNNEL Phase 2:
   - EAP module processes the request
   - Sets EAP-Type attribute
   - NEW: Inner-tunnel authorize section detects &EAP-Type
   - Sets Auth-Type := ldap
   ↓
4. LDAP Bind Authentication:
   - User-Password extracted from EAP message
   - LDAP module binds to Google Workspace with (username, password)
   - If bind succeeds: user authenticated
   - If bind fails: user not authenticated
   ↓
5. VLAN Assignment:
   - If auth succeeded, VLAN attributes applied (Tunnel-Type, Tunnel-Medium-Type, Tunnel-Private-Group-Id)
   - VLAN attributes copied to outer session state
   ↓
6. Access-Accept sent to client
   - Client receives VLAN assignment
   - Client joins specified VLAN (e.g., VLAN 248 for krea.edu.in staff)
```

## Files Modified

1. **`configs/inner-tunnel`** - Added EAP-Type detection and LDAP Auth-Type routing

## Testing

To test the fixed configuration:

```bash
# Test with a real PEAP client (e.g., Windows, Mac, Aruba AP)
# User: <username>@krea.edu.in
# Password: <Google Workspace password>
# Network: TEST_RADIUS (or your PEAP SSID)
# EAP Type: PEAP
# Inner authentication: Automatic (defaults to GTC)
```

**Expected Results**:
- ✅ TLS tunnel negotiates successfully
- ✅ Inner-tunnel authentication completes (LDAP bind succeeds)
- ✅ Client assigned to correct VLAN (e.g., 248 for staff)
- ✅ Client receives IP address in that VLAN
- ✅ Database logs show `reply='Access-Accept'` with correct `vlan=248`

## Logs to Verify

Look for in FreeRADIUS logs:

**Good sign** (authentication succeeding):
```
(17) ldap: Attempting to bind as user
(17) ldap: ... (binding in progress)
(17) ldap: Bind successful, trying next failover from "ldap"
(17) ldap: ... (user lookup)
[mschap|ldap|pap]: ok
(17) eap: Sending EAP Success (code 2) ID X length Y
```

**Bad sign** (still trying MSCHAPv2):
```
(17) mschap: WARNING: No Cleartext-Password configured
(17) mschap: ERROR: FAILED: No NT-Password
```
(This means our fix didn't apply, or the rebuild wasn't done properly)

## Deployment Steps

1. Modify `/etc/freeradius/sites-available/inner-tunnel` in the `authorize` section
2. Rebuild the Docker image: `docker-compose build --no-cache freeradius`
3. Start the new container: `docker-compose up -d freeradius`
4. Verify container is running: `docker ps | grep freeradius`
5. Check logs for successful authentications

## Troubleshooting

### Issue: "No Cleartext-Password" errors still appearing

**Solution**: Ensure the container was rebuilt with the new configuration:
```bash
docker-compose build --no-cache freeradius
docker-compose up -d freeradius
```

### Issue: LDAP bind failing (authentication rejected)

**Solution**: Verify LDAP credentials and connectivity:
- Check LDAP_BIND_DN and LDAP_BIND_PASSWORD in environment
- Verify LDAP_SERVER connectivity (ldaps://ldap.google.com:636)
- Test manually: `ldapwhoami -H ldaps://ldap.google.com:636 -D "uid=user,ou=Staff,ou=Users,dc=krea,dc=edu,dc=in" -W`

### Issue: VLAN still not assigned to client

**Solution**: Check that:
1. Authentication succeeded (logs show Access-Accept)
2. Domain mapping configured correctly in `domain-config.json`
3. VLAN attributes are in the reply (logs show Tunnel-Type, Tunnel-Medium-Type, Tunnel-Private-Group-Id)
4. Client supports VLAN assignment (most enterprise APs do)

## Related Configuration

**PEAP Method Configuration** (`configs/eap`):
```
peap {
    tls = tls-common
    default_eap_type = gtc      # ← GTC for Google LDAP compatibility
    use_tunneled_reply = yes     # ← Ensure VLAN reaches outer tunnel
    virtual_server = "inner-tunnel"
}
```

**GTC Method Configuration** (`configs/eap`):
```
gtc {
    auth_type = PAP              # ← Use PAP (password authentication protocol)
}
```

**LDAP Module** (`configs/ldap`):
- Configured to bind as admin to Google Workspace
- Queries user object for group membership
- Returns user details for authorization

**Inner-Tunnel Authorize** (`configs/inner-tunnel`):
- NEW: If &EAP-Type, set Auth-Type := ldap
- If User-Password, set Auth-Type := ldap
- VLAN assignment based on domain from username

## Version History

- **2024-12-08**: Initial fix for PEAP+Google LDAP authentication
  - Identified MSCHAPv2 as incompatible with LDAP-only password retrieval
  - Implemented EAP-Type based LDAP routing
  - Verified VLAN assignment still working (was never broken)
  - Docker image rebuilt with updated configuration
