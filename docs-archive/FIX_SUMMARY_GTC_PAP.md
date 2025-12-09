# GTC/PAP WiFi Authentication Fix - Session Summary

## Problem Identified

WiFi authentication using TTLS-GTC (as used by Aruba APs) was failing with the error:
```
pap: ERROR: You set 'Auth-Type = PAP' for a request that does not contain a User-Password attribute!
```

This occurred despite correct configuration elsewhere in the system. Real WiFi logs from an Aruba AP showed that the GTC module was never extracting the client password into the User-Password attribute.

### Root Cause Analysis

The issue was in `/configs/inner-tunnel` at lines 198-207 (AUTHORIZE section):

**BROKEN CONFIGURATION:**
```freeradius
if (&EAP-Type) {
    update control {
        Auth-Type := pap
    }
}
```

This code was setting `Auth-Type := pap` for all EAP requests in the AUTHORIZE section, which caused:

1. **Skipped EAP Authentication Module**: Setting Auth-Type in AUTHORIZE causes FreeRADIUS to skip directly to the AUTH section with that type, bypassing the `eap` module in the AUTHENTICATE section.

2. **Missing Password Extraction**: The GTC module (configured in `/configs/eap` with `auth_type = PAP`) only creates the User-Password attribute when it runs in the AUTHENTICATE section. By skipping that section, User-Password was never created.

3. **PAP Fails**: The PAP authentication then fails because the required User-Password attribute is missing.

### Correct Authentication Flow for TTLS-GTC

The proper flow should be:

1. **AUTHORIZE Phase**: EAP module processes the outer TTLS setup and EAP-Identity
   - Does NOT set Auth-Type, allowing continuation to AUTHENTICATE
   
2. **AUTHENTICATE Phase**: EAP module runs again
   - Processes GTC password response
   - GTC extracts password → Creates User-Password attribute
   - GTC's `auth_type = PAP` setting routes to PAP authentication
   - PAP module validates the password
   - LDAP fallback attempts Google Workspace authentication if PAP fails

## Solution Implemented

**File Modified**: `configs/inner-tunnel` lines 198-207

**FIXED CONFIGURATION:**
```freeradius
#  NOTE: For EAP (especially GTC inside TTLS/PEAP), we do NOT set Auth-Type here
#  The EAP module in the AUTHENTICATE section needs to run to:
#  1. Process GTC password response
#  2. Extract password to User-Password attribute  
#  3. Route through GTC's auth_type setting (which is set to PAP in eap.conf)
#  Setting Auth-Type := pap here would skip the EAP authentication module,
#  preventing GTC from extracting the User-Password attribute.
#
#  If no EAP and we have User-Password, try LDAP bind
if (User-Password && !(&reply:NT-Password || &control:NT-Password)) {
    update control {
        Auth-Type := ldap
    }
}
```

### Key Changes

1. **Removed**: The `if (&EAP-Type)` block that was setting `Auth-Type := pap`
2. **Kept**: The logic for non-EAP User-Password requests routing to LDAP
3. **Effect**: EAP requests now flow through the proper AUTHENTICATE section where GTC can extract passwords

## Verification

✅ Configuration deployed to Docker container
✅ No syntax errors during FreeRADIUS startup
✅ All Auth-Types compiled correctly:
- Auth-Type PAP (with LDAP fallback for GTC)
- Auth-Type CHAP
- Auth-Type MS-CHAP
- Auth-Type LDAP
- EAP module in AUTHENTICATE section

## Related Configuration

**File**: `configs/eap` (Line 128)
```freeradius
gtc {
    auth_type = PAP
}
```

This tells GTC to route extracted passwords to PAP for validation, which then has LDAP fallback for Google Workspace.

**File**: `configs/inner-tunnel` (Lines 328-333)
```freeradius
Auth-Type PAP {
    # Mac MIME password decoding...
    pap
    # FALLBACK: LDAP bind if PAP fails
    if (fail || notfound) {
        ldap
    }
}
```

This configures PAP with LDAP fallback, which is perfect for Google Workspace authentication where passwords aren't available through traditional RADIUS databases.

## Testing Recommendations

1. **WiFi EAP-TTLS Test**: Attempt authentication from Aruba AP or other TTLS-capable device
   - Expected result: Access-Accept with correct VLAN assignment
   - Test user: senthil.karuppusamy@krea.edu.in (krea.edu.in → VLAN 248)

2. **EAP-PEAP Test**: Verify PEAP also works with same GTC/PAP flow
   - Uses same inner-tunnel as TTLS

3. **Non-EAP Fallback**: Verify simple PAP still works for Windows devices
   - Uses LDAP bind for password validation

## Deployment Details

- **Docker Image Rebuilt**: Yes (--no-cache flag)
- **Containers Restarted**: Yes (docker-compose down && up -d)
- **Configuration File**: configs/inner-tunnel (copied to /etc/freeradius/sites-enabled/inner-tunnel in container)
- **FreeRADIUS Version**: 3.0.23
