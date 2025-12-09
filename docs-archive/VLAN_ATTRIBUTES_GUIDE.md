# VLAN Attributes Configuration Guide

## Overview

FreeRADIUS can return VLAN assignments to Network Access Servers (NAS) like wireless controllers and switches using various RADIUS attributes. This guide explains how to configure which VLAN attributes are returned.

## Supported VLAN Attributes

### Standard VLAN Attributes (RFC 2868)

These attributes work with most vendors:

- **Tunnel-Type** (type 64): Set to `VLAN` (13)
- **Tunnel-Medium-Type** (type 65): Set to `IEEE-802` (6)
- **Tunnel-Private-Group-ID** (type 81): VLAN ID as string

### Vendor-Specific Attributes (VSA)

#### Aruba/HPE Networks

For Aruba wireless controllers and HPE campus switches:

- **Aruba-User-VLAN** (integer): VLAN ID as integer
- **Aruba-Named-User-VLAN** (string): VLAN name (e.g., "VLAN100")
- **Aruba-User-Role** (string): User role name

#### Cisco

- **Cisco-AVPair** (string): Format `"vlan=XXX"`

## Configuration

### Environment Variable

Set the `VLAN_ATTRIBUTES` environment variable in your `.env` file:

```bash
# Single attribute (default)
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID

# Aruba only
VLAN_ATTRIBUTES=Aruba-User-VLAN

# Multiple attributes (recommended for Aruba)
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN

# Cisco
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Cisco-AVPair
```

### Examples by Vendor

#### Generic / Multi-Vendor Setup

```bash
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID
```

Returns:
```
Tunnel-Type = VLAN
Tunnel-Medium-Type = IEEE-802
Tunnel-Private-Group-Id = "100"
```

#### Aruba Wireless Controllers

```bash
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN
```

Returns:
```
Tunnel-Type = VLAN
Tunnel-Medium-Type = IEEE-802
Tunnel-Private-Group-Id = "100"
Aruba-User-VLAN = 100
Aruba-Named-User-VLAN = "VLAN100"
```

#### Cisco Switches/Controllers

```bash
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Cisco-AVPair
```

Returns:
```
Tunnel-Type = VLAN
Tunnel-Medium-Type = IEEE-802
Tunnel-Private-Group-Id = "100"
Cisco-AVPair = "vlan=100"
```

## How It Works

1. **VLAN Assignment**: VLANs are assigned based on the `DOMAIN_CONFIG` JSON in `.env`
2. **Attribute Selection**: The `VLAN_ATTRIBUTES` variable determines which attributes to return
3. **Dynamic Generation**: During container startup, `init.sh` generates FreeRADIUS configuration
4. **Multiple Attributes**: You can return multiple attributes simultaneously (comma-separated)

## VLAN Assignment Flow

```
User Authentication
       ↓
Extract domain from username (user@domain.com)
       ↓
Match domain + optional key in DOMAIN_CONFIG
       ↓
Get VLAN value (e.g., "100")
       ↓
Generate RADIUS attributes based on VLAN_ATTRIBUTES
       ↓
Return Access-Accept with VLAN attributes
```

## Testing VLAN Assignment

### Using radtest

```bash
echo "User-Name = 'test.mba@krea.ac.in', User-Password = 'password123'" | \
  radclient -x localhost:1812 auth testing123
```

### Expected Output

With `VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN`:

```
Access-Accept
    Tunnel-Type:0 = VLAN
    Tunnel-Medium-Type:0 = IEEE-802
    Tunnel-Private-Group-Id:0 = "216"
    Aruba-User-VLAN = 216
```

## Custom Dictionary

The Aruba vendor attributes are defined in `configs/dictionary.custom`:

```
VENDOR      Aruba               14823

BEGIN-VENDOR    Aruba

ATTRIBUTE   Aruba-User-Role         1   string
ATTRIBUTE   Aruba-User-VLAN         2   integer
ATTRIBUTE   Aruba-Named-User-VLAN   3   string
ATTRIBUTE   Aruba-AP-Group          4   string

END-VENDOR  Aruba
```

## Troubleshooting

### VLAN Not Being Assigned

1. **Check Container Logs**:
   ```bash
   docker-compose logs freeradius | grep -i vlan
   ```

2. **Verify Configuration**:
   ```bash
   docker exec freeradius-google-ldap cat /etc/freeradius/sites-enabled/default | grep -A 20 "Tunnel-"
   ```

3. **Check VLAN Attributes Setting**:
   ```bash
   docker-compose logs freeradius | grep "VLAN Attributes:"
   ```

   Should show:
   ```
   VLAN Attributes: Tunnel-Private-Group-ID,Aruba-User-VLAN
   ```

### Wrong VLAN Being Assigned

1. **Verify DOMAIN_CONFIG**:
   ```bash
   docker-compose logs freeradius | grep "Domain Config:"
   ```

2. **Check Domain Matching**:
   - Ensure username matches domain pattern
   - Check key matching if using key-based assignment
   - Review logs for domain extraction

### Aruba Controller Not Recognizing VLAN

1. **Enable VSA on Aruba Controller**:
   - Go to Configuration → Authentication → RADIUS
   - Enable "Use VLAN from RADIUS"
   - Enable "Use User-Role from RADIUS" (if using roles)

2. **Check Aruba RADIUS Profile**:
   ```
   aaa authentication dot1x default-aruba
   aaa authentication-server radius radius-server
   ```

3. **Verify VSA is enabled**:
   ```
   show aaa authentication-server statistics
   ```

## Advanced Configuration

### Named VLANs (Aruba)

For named VLANs, use `Aruba-Named-User-VLAN`:

```bash
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-Named-User-VLAN
```

The system automatically prefixes VLAN IDs with "VLAN":
- VLAN 100 → "VLAN100"
- VLAN 216 → "VLAN216"

### Multiple Vendor Support

To support both Aruba and generic devices:

```bash
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN
```

- Generic devices use `Tunnel-Private-Group-ID`
- Aruba devices can use either attribute

## Best Practices

1. **Always include Tunnel-Private-Group-ID** for maximum compatibility
2. **Add vendor-specific attributes** only if needed
3. **Test with radclient** before deploying to production
4. **Document your VLAN assignments** in DOMAIN_CONFIG comments
5. **Use consistent VLAN numbering** across your organization

## Production Checklist

- [ ] Set appropriate `VLAN_ATTRIBUTES` for your NAS vendor
- [ ] Test VLAN assignment with `radclient`
- [ ] Verify VLANs exist on switches/controllers
- [ ] Test with actual client device
- [ ] Monitor authentication logs for VLAN attributes
- [ ] Document VLAN to department/user type mappings

## Reference

### DOMAIN_CONFIG Example with VLANs

```bash
DOMAIN_CONFIG=[
  {"domain":"staff.company.com","key":"","Type":"Staff","VLAN":"10"},
  {"domain":"student.company.com","key":"","Type":"Student","VLAN":"20"},
  {"domain":"guest.company.com","key":"","Type":"Guest","VLAN":"30"}
]

VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN
```

### Attribute Type Reference

| Attribute | Type | Example Value |
|-----------|------|---------------|
| Tunnel-Type | enum | VLAN (13) |
| Tunnel-Medium-Type | enum | IEEE-802 (6) |
| Tunnel-Private-Group-Id | string | "100" |
| Aruba-User-VLAN | integer | 100 |
| Aruba-Named-User-VLAN | string | "VLAN100" |
| Cisco-AVPair | string | "vlan=100" |

---

**Last Updated**: December 8, 2024
**Version**: 1.0.0
