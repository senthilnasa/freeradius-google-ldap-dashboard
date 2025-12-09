# VLAN Configuration Update - December 8, 2024

## Summary

Added support for configurable VLAN RADIUS attributes, allowing you to specify which VLAN attributes are returned to Network Access Servers (NAS) during authentication.

## What Was Added

### 1. New Environment Variable: `VLAN_ATTRIBUTES`

Configure which RADIUS attributes to return for VLAN assignment via a comma-separated list in `.env`:

```bash
# Examples:
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID                                          # Standard only
VLAN_ATTRIBUTES=Aruba-User-VLAN                                                  # Aruba only
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN  # Multiple
```

### 2. Supported VLAN Attributes

#### Standard (RFC 2868)
- `Tunnel-Private-Group-ID` - Works with all vendors (default)

#### Aruba/HPE
- `Aruba-User-VLAN` - VLAN ID as integer
- `Aruba-Named-User-VLAN` - VLAN name as string (e.g., "VLAN100")

#### Cisco
- `Cisco-AVPair` - Format: "vlan=XXX"

### 3. Aruba Vendor Dictionary

Added Aruba vendor-specific attributes to [configs/dictionary.custom](configs/dictionary.custom):

```
VENDOR      Aruba               14823

BEGIN-VENDOR    Aruba
ATTRIBUTE   Aruba-User-Role         1   string
ATTRIBUTE   Aruba-User-VLAN         2   integer
ATTRIBUTE   Aruba-Named-User-VLAN   3   string
ATTRIBUTE   Aruba-AP-Group          4   string
ATTRIBUTE   Aruba-Framed-IPv6-Address   5   string
ATTRIBUTE   Aruba-Device-Type       6   string
END-VENDOR  Aruba
```

### 4. Dynamic VLAN Attribute Generation

Modified [init.sh](init.sh) to dynamically generate VLAN attribute assignments based on the `VLAN_ATTRIBUTES` configuration:

- Added `generate_vlan_attributes()` function (lines 87-119)
- Updated VLAN assignment for key-based matching (line 191, 207)
- Updated VLAN assignment for legacy format (line 237)
- Added logging for configured VLAN attributes (line 126)

## Files Modified

1. **`.env.example`** (lines 119-139)
   - Added `VLAN_ATTRIBUTES` configuration section
   - Documented all supported attributes
   - Provided vendor-specific examples

2. **`configs/dictionary.custom`** (lines 8-26)
   - Added Aruba vendor definition (vendor ID: 14823)
   - Added 6 Aruba-specific attributes
   - Properly formatted with BEGIN-VENDOR/END-VENDOR blocks

3. **`init.sh`** (lines 82-119, 191, 207, 237)
   - Added `generate_vlan_attributes()` helper function
   - Replaced hardcoded `Tunnel-Private-Group-Id` with dynamic generation
   - Added support for multiple simultaneous VLAN attributes
   - Added logging for VLAN attribute configuration

## Files Created

1. **`VLAN_ATTRIBUTES_GUIDE.md`**
   - Complete guide for VLAN attribute configuration
   - Examples for each vendor type
   - Testing procedures
   - Troubleshooting section

2. **`VLAN_CONFIGURATION_UPDATE.md`** (this file)
   - Implementation summary
   - Usage examples
   - Migration guide

## How It Works

### Before (Hardcoded)

```unlang
update reply {
    Tunnel-Type := VLAN
    Tunnel-Medium-Type := IEEE-802
    Tunnel-Private-Group-Id := "100"
}
```

### After (Configurable)

With `VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN`:

```unlang
update reply {
    Tunnel-Type := VLAN
    Tunnel-Medium-Type := IEEE-802
    Tunnel-Private-Group-Id := "100"
    Aruba-User-VLAN := 100
    Aruba-Named-User-VLAN := "VLAN100"
}
```

## Usage Examples

### Example 1: Aruba Wireless Controller

```bash
# .env file
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN

DOMAIN_CONFIG=[
  {"domain":"students.university.edu","key":"","Type":"Student","VLAN":"100"},
  {"domain":"staff.university.edu","key":"","Type":"Staff","VLAN":"200"}
]
```

**Result for student@students.university.edu:**
```
Access-Accept
    Tunnel-Type = VLAN
    Tunnel-Medium-Type = IEEE-802
    Tunnel-Private-Group-Id = "100"
    Aruba-User-VLAN = 100
    Aruba-Named-User-VLAN = "VLAN100"
```

### Example 2: Generic Controller (Standard Only)

```bash
# .env file
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID

DOMAIN_CONFIG=[
  {"domain":"company.com","key":"","Type":"Employee","VLAN":"10"}
]
```

**Result:**
```
Access-Accept
    Tunnel-Type = VLAN
    Tunnel-Medium-Type = IEEE-802
    Tunnel-Private-Group-Id = "10"
```

### Example 3: Cisco Infrastructure

```bash
# .env file
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Cisco-AVPair

DOMAIN_CONFIG=[
  {"domain":"corp.example.com","key":"","Type":"Corporate","VLAN":"50"}
]
```

**Result:**
```
Access-Accept
    Tunnel-Type = VLAN
    Tunnel-Medium-Type = IEEE-802
    Tunnel-Private-Group-Id = "50"
    Cisco-AVPair = "vlan=50"
```

## Migration Guide

### If You're Already Using This System

1. **No changes required** - defaults to existing behavior (`Tunnel-Private-Group-ID`)
2. **Optional**: Add `VLAN_ATTRIBUTES=Tunnel-Private-Group-ID` to `.env` for explicitness

### To Enable Aruba Support

1. **Edit `.env`**:
   ```bash
   VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN
   ```

2. **Rebuild FreeRADIUS container**:
   ```bash
   docker-compose build freeradius
   docker-compose up -d freeradius
   ```

3. **Verify in logs**:
   ```bash
   docker-compose logs freeradius | grep "VLAN Attributes:"
   ```

   Should show:
   ```
   VLAN Attributes: Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN
   ```

## Testing

### Test VLAN Assignment

```bash
# Test authentication with radtest
echo "User-Name = 'user@domain.com', User-Password = 'password'" | \
  radclient -x localhost:1812 auth testing123
```

### Verify VLAN Attributes in Response

Look for:
```
Access-Accept
    Tunnel-Type:0 = VLAN
    Tunnel-Medium-Type:0 = IEEE-802
    Tunnel-Private-Group-Id:0 = "100"
    Aruba-User-VLAN = 100              # If Aruba-User-VLAN configured
    Aruba-Named-User-VLAN = "VLAN100"  # If Aruba-Named-User-VLAN configured
```

## Benefits

1. **Vendor Flexibility**: Support multiple NAS vendors without code changes
2. **Backward Compatible**: Defaults to standard Tunnel-Private-Group-ID
3. **Multiple Attributes**: Can return several VLAN attributes simultaneously
4. **Easy Configuration**: Simple comma-separated list in `.env`
5. **Documented**: Complete vendor dictionary with all attributes

## Aruba Controller Configuration

### Enable VLAN Assignment from RADIUS

On Aruba controller:

```
aaa authentication dot1x default-aruba
aaa authentication-server radius radius-server
  host <RADIUS_SERVER_IP>
  key <SHARED_SECRET>

aaa profile dot1x-profile
  default-role guest
  set-role condition role-derivation-rules evaluate-role-before-user-role
```

### Enable VSA Processing

```
aaa server-group radius-servers
  auth-server radius-server
  set session-acct interim
```

### Verify VLAN Assignment

```
show user-table
show aaa authentication-server statistics
```

## Technical Details

### Function: `generate_vlan_attributes()`

**Location**: `init.sh` lines 87-119

**Purpose**: Generate FreeRADIUS unlang code for VLAN attribute assignment

**Input**: VLAN ID (e.g., "100")

**Output**: Unlang attribute assignment statements

**Logic**:
1. Always includes `Tunnel-Type` and `Tunnel-Medium-Type` (RFC compliance)
2. Parses comma-separated `VLAN_ATTRIBUTES` environment variable
3. For each configured attribute, generates appropriate unlang statement
4. Handles integer vs string types correctly

## Support

For questions or issues:

1. **Review**: [VLAN_ATTRIBUTES_GUIDE.md](VLAN_ATTRIBUTES_GUIDE.md)
2. **Check logs**: `docker-compose logs freeradius | grep -i vlan`
3. **Test**: Use `radclient` to verify VLAN attributes
4. **Verify**: Check generated config in container

## Future Enhancements

Potential future additions:

- Additional vendor support (Juniper, Extreme, etc.)
- VLAN priority/QoS attributes
- Dynamic VLAN based on device type
- VLAN pooling support
- Integration with VLAN management dashboard

---

**Implementation Date**: December 8, 2024
**Status**: ✅ Complete and Tested
**Backward Compatible**: Yes
**Breaking Changes**: None
