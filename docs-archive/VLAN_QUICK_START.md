# VLAN Attributes - Quick Start Guide

## ✅ Feature Status: READY

The VLAN attributes configuration feature is now active and working!

## Quick Setup

### Step 1: Choose Your Configuration

Edit your `.env` file and add the `VLAN_ATTRIBUTES` variable:

```bash
# For Aruba Controllers
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN

# For Cisco Equipment
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Cisco-AVPair

# For Generic/Mixed Environment (default)
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID
```

### Step 2: Rebuild FreeRADIUS Container

```bash
docker-compose build freeradius
docker-compose up -d freeradius
```

### Step 3: Verify Configuration

Check that your attributes are loaded:

```bash
docker logs freeradius-google-ldap | grep "VLAN Attributes:"
```

Expected output:
```
VLAN Attributes: Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN
```

## Example Configurations

### Aruba Wireless Controller

**`.env` configuration:**
```bash
# VLAN attributes for Aruba
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN

# Domain and VLAN mappings
DOMAIN_CONFIG=[{"domain":"students.university.edu","key":"","Type":"Student","VLAN":"100"},{"domain":"staff.university.edu","key":"","Type":"Staff","VLAN":"200"},{"domain":"guest.university.edu","key":"","Type":"Guest","VLAN":"300"}]
```

**RADIUS Response for student@students.university.edu:**
```
Access-Accept
    Tunnel-Type = VLAN
    Tunnel-Medium-Type = IEEE-802
    Tunnel-Private-Group-Id = "100"
    Aruba-User-VLAN = 100
    Aruba-Named-User-VLAN = "VLAN100"
```

### Current Configuration (Your Setup)

Based on your current DOMAIN_CONFIG:

```bash
# Default - works with all vendors
VLAN_ATTRIBUTES=Tunnel-Private-Group-ID

# Your VLANs:
# - krea.edu.in → VLAN 156 (Staff)
# - krea.ac.in → VLAN 156 (Student)
# - ifmr.ac.in → VLAN 156 (Other Center)
```

### To Enable Aruba Support

1. **Edit `.env`** - add this line:
   ```bash
   VLAN_ATTRIBUTES=Tunnel-Private-Group-ID,Aruba-User-VLAN,Aruba-Named-User-VLAN
   ```

2. **Rebuild**:
   ```bash
   docker-compose build freeradius
   docker-compose up -d freeradius
   ```

3. **Test** (if you have test credentials):
   ```bash
   echo "User-Name = 'user@krea.edu.in', User-Password = 'testpass'" | \
     radclient -x localhost:1812 auth testing123
   ```

## Supported RADIUS Attributes

### Standard (All Vendors)
- `Tunnel-Private-Group-ID` - VLAN ID as string (always included)

### Aruba/HPE
- `Aruba-User-VLAN` - VLAN ID as integer
- `Aruba-Named-User-VLAN` - VLAN name ("VLAN100", "VLAN200", etc.)

### Cisco
- `Cisco-AVPair` - Cisco format ("vlan=100")

## How VLAN Assignment Works

```
1. User authenticates (e.g., john@krea.edu.in)
   ↓
2. System extracts domain (krea.edu.in)
   ↓
3. Matches domain in DOMAIN_CONFIG
   ↓
4. Gets VLAN value (156)
   ↓
5. Returns VLAN using configured attributes:
   - Tunnel-Private-Group-ID: "156"
   - Aruba-User-VLAN: 156 (if configured)
   - Aruba-Named-User-VLAN: "VLAN156" (if configured)
```

## Testing Your Configuration

### Test 1: Check Logs

```bash
# View initialization logs
docker logs freeradius-google-ldap | grep "VLAN"

# Watch authentication in real-time
docker logs -f freeradius-google-ldap
```

### Test 2: Authenticate Test User

If you have test credentials:

```bash
# Test with radclient
echo "User-Name = 'testuser@krea.edu.in', User-Password = 'password'" | \
  radclient -x localhost:1812 auth testing123
```

Look for VLAN attributes in the `Access-Accept` response.

## Troubleshooting

### Container Not Starting

```bash
# Check container status
docker-compose ps

# View error logs
docker logs freeradius-google-ldap --tail=50
```

### VLAN Not Being Assigned

1. **Check domain matching**:
   ```bash
   docker logs freeradius-google-ldap | grep "Supported domains"
   ```

2. **Verify VLAN attributes**:
   ```bash
   docker logs freeradius-google-ldap | grep "VLAN Attributes"
   ```

3. **Test authentication**:
   ```bash
   # Enable debug mode
   docker exec -it freeradius-google-ldap radiusd -X
   ```

### Dictionary Errors

If you see "Duplicate attribute" errors:
- ✅ Already fixed! FreeRADIUS includes Aruba dictionary by default
- The system uses built-in Aruba attributes automatically

## Complete Documentation

For detailed information, see:

- **[VLAN_ATTRIBUTES_GUIDE.md](VLAN_ATTRIBUTES_GUIDE.md)** - Complete configuration guide
- **[VLAN_CONFIGURATION_UPDATE.md](VLAN_CONFIGURATION_UPDATE.md)** - Implementation details
- **[.env.example](.env.example)** - All configuration options

## Current System Status

✅ **FreeRADIUS**: Running and healthy
✅ **VLAN Attributes**: Configured (Tunnel-Private-Group-ID - default)
✅ **Dictionary**: Using built-in Aruba attributes (vendor ID: 14823)
✅ **Domain Config**: Loaded (3 domains configured)

## Need Help?

1. Check the complete guides listed above
2. Review container logs: `docker-compose logs freeradius`
3. Test with `radclient` before production deployment
4. Verify NAS (controller/switch) is configured to accept RADIUS VLANs

---

**Quick Reference Commands:**

```bash
# View current configuration
docker logs freeradius-google-ldap | grep "VLAN Attributes"

# Rebuild after changes
docker-compose build freeradius && docker-compose up -d freeradius

# Watch authentication logs
docker logs -f freeradius-google-ldap

# Check all containers
docker-compose ps

# Test authentication
echo "User-Name = 'user@domain', User-Password = 'pass'" | \
  radclient -x localhost:1812 auth testing123
```

---

**Last Updated**: December 8, 2024
**Status**: ✅ Working and Tested
