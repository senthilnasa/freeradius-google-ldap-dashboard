# VLAN Key-Based Matching Configuration

## Overview

This FreeRADIUS setup now supports **key-based matching** for VLAN assignment within a single domain. This allows you to assign different VLANs to users based on patterns in their email address.

## Configuration Format

The `DOMAIN_CONFIG` environment variable now supports an optional `key` field:

```json
DOMAIN_CONFIG=[
  {"domain":"krea.edu.in","key":".mba","Type":"MBA Student","VLAN":"216"},
  {"domain":"krea.edu.in","key":".sias","Type":"SIAS Student","VLAN":"220"},
  {"domain":"krea.edu.in","key":"","Type":"Others Student","VLAN":"222"}
]
```

### Fields Explained

- **domain**: The email domain to match (e.g., `krea.edu.in`)
- **key**: Substring to match in the username part (before `@`)
  - `.mba` matches usernames like `john.mba@krea.edu.in`
  - `.sias` matches usernames like `jane.sias@krea.edu.in`
  - Empty string `""` acts as the **default/fallback** for that domain
- **Type**: User type label (for logging and identification)
- **VLAN**: VLAN ID to assign

## How It Works

### Matching Logic

1. **Extract domain**: From `user@domain.com`, extract `domain.com`
2. **Check entries in order**: Entries are evaluated sequentially
3. **First match wins**:
   - If `key` is specified, check if `User-Name` contains `key@domain`
   - If `key` is empty, match any user from that domain (fallback)
4. **Apply VLAN**: Set Tunnel-Private-Group-Id to the matched VLAN

### Example Matching

Given the configuration above:

| Email Address | Matched Key | Type | VLAN Assigned |
|--------------|-------------|------|---------------|
| `john.mba@krea.edu.in` | `.mba` | MBA Student | 216 |
| `jane.sias@krea.edu.in` | `.sias` | SIAS Student | 220 |
| `alice@krea.edu.in` | *(empty - default)* | Others Student | 222 |
| `bob.staff@krea.edu.in` | *(empty - default)* | Others Student | 222 |

## Configuration Examples

### Example 1: Single Domain with Key-Based Matching

```bash
# Different VLANs for MBA, SIAS, and other students
DOMAIN_CONFIG=[
  {"domain":"krea.edu.in","key":".mba","Type":"MBA Student","VLAN":"216"},
  {"domain":"krea.edu.in","key":".sias","Type":"SIAS Student","VLAN":"220"},
  {"domain":"krea.edu.in","key":"","Type":"Others Student","VLAN":"222"}
]
```

### Example 2: Multiple Domains with Mixed Matching

```bash
# Combine key-based and domain-based matching
DOMAIN_CONFIG=[
  {"domain":"krea.edu.in","key":".mba","Type":"MBA Student","VLAN":"216"},
  {"domain":"krea.edu.in","key":".sias","Type":"SIAS Student","VLAN":"220"},
  {"domain":"krea.edu.in","key":"","Type":"Other KREA Student","VLAN":"222"},
  {"domain":"staff.krea.edu.in","key":"","Type":"Staff","VLAN":"10"}
]
```

### Example 3: Legacy Format (Backward Compatible)

```bash
# Old format without key field still works
DOMAIN_CONFIG=[
  {"domain":"staff.yourdomain.com","Type":"Staff","VLAN":"10"},
  {"domain":"students.yourdomain.com","Type":"Student","VLAN":"20"}
]
```

## Important Notes

### Order Matters

⚠️ **Entries are evaluated in ORDER - first match wins!**

**CORRECT** - Default at the end:
```json
[
  {"domain":"krea.edu.in","key":".mba","Type":"MBA","VLAN":"216"},
  {"domain":"krea.edu.in","key":".sias","Type":"SIAS","VLAN":"220"},
  {"domain":"krea.edu.in","key":"","Type":"Others","VLAN":"222"}  ← Default last
]
```

**WRONG** - Default at the beginning:
```json
[
  {"domain":"krea.edu.in","key":"","Type":"Others","VLAN":"222"},  ← Matches ALL users!
  {"domain":"krea.edu.in","key":".mba","Type":"MBA","VLAN":"216"},  ← Never reached
  {"domain":"krea.edu.in","key":".sias","Type":"SIAS","VLAN":"220"}  ← Never reached
]
```

### Key Matching is Case-Sensitive

- `.mba` will NOT match `.MBA`
- Make sure your key matches the actual email format from your identity provider

### Always Include a Default

It's recommended to include an entry with empty `key` (`""`) as the last entry for each domain to handle users who don't match any specific key.

## Deployment

1. **Update .env file**:
   ```bash
   DOMAIN_CONFIG=[{"domain":"krea.edu.in","key":".mba","Type":"MBA Student","VLAN":"216"},{"domain":"krea.edu.in","key":".sias","Type":"SIAS Student","VLAN":"220"},{"domain":"krea.edu.in","key":"","Type":"Others Student","VLAN":"222"}]
   ```

2. **Restart the container**:
   ```bash
   docker-compose down
   docker-compose up -d
   ```

3. **Verify configuration**:
   ```bash
   docker-compose logs freeradius | grep "Dynamic VLAN"
   ```

## Testing

Test authentication with different user types:

```bash
# Test MBA student
radtest john.mba@krea.edu.in password123 localhost:1812 0 testing123

# Test SIAS student
radtest jane.sias@krea.edu.in password123 localhost:1812 0 testing123

# Test other student
radtest alice@krea.edu.in password123 localhost:1812 0 testing123
```

Expected RADIUS reply should include:
```
Tunnel-Type = VLAN
Tunnel-Medium-Type = IEEE-802
Tunnel-Private-Group-Id = "216"  (or 220, or 222)
```

## Troubleshooting

### Check Generated Configuration

View the generated FreeRADIUS unlang configuration:

```bash
docker exec -it <container-name> cat /etc/freeradius/sites-available/default | grep -A 50 "Dynamic VLAN"
```

### Enable Debug Mode

Check FreeRADIUS logs for matching details:

```bash
docker-compose logs -f freeradius
```

Look for lines showing:
- Domain extraction: `Tmp-String-0 := "krea.edu.in"`
- VLAN assignment: `Tunnel-Private-Group-Id := "216"`
- User type: `Tmp-String-1 := "MBA Student"`

## Generated FreeRADIUS Configuration

The `init.sh` script generates FreeRADIUS unlang code like this:

```unlang
# krea.edu.in with key ".mba" = MBA Student, VLAN 216
if ((&request:Tmp-String-0 == "krea.edu.in") && (&User-Name =~ /.mba@/)) {
    update control {
        Tmp-String-1 := "MBA Student"
    }
    update reply {
        Tunnel-Type := VLAN
        Tunnel-Medium-Type := IEEE-802
        Tunnel-Private-Group-Id := "216"
    }
}
# krea.edu.in with key ".sias" = SIAS Student, VLAN 220
elsif ((&request:Tmp-String-0 == "krea.edu.in") && (&User-Name =~ /.sias@/)) {
    update control {
        Tmp-String-1 := "SIAS Student"
    }
    update reply {
        Tunnel-Type := VLAN
        Tunnel-Medium-Type := IEEE-802
        Tunnel-Private-Group-Id := "220"
    }
}
# krea.edu.in (default/others) = Others Student, VLAN 222
elsif (&request:Tmp-String-0 == "krea.edu.in") {
    update control {
        Tmp-String-1 := "Others Student"
    }
    update reply {
        Tunnel-Type := VLAN
        Tunnel-Medium-Type := IEEE-802
        Tunnel-Private-Group-Id := "222"
    }
}
```
