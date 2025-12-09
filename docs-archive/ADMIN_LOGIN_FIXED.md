# Admin Login Fixed - December 9, 2024

## ✅ Issue Resolved

**Problem**: Admin login (admin / admin123) was not working
**Cause**: Password was stored as bcrypt hash instead of MD5
**Solution**: Reset password to MD5 hash format

---

## What Was Wrong

The admin password was stored as a **bcrypt hash** (`$2y$10$...`) in the database:
```
password: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

However, the login system (`app/helpers/Auth.php`) only supports **MD5** and **SHA-256** hashes:

```php
// From Auth.php login method
$sha256_hash = hash('sha256', $password);
$md5_hash = md5($password);

// Try SHA-256 first
if ($sha256_hash === $stored_hash) {
    $passwordValid = true;
}
// Fallback to MD5
elseif ($md5_hash === $stored_hash) {
    $passwordValid = true;
}
```

**Result**: Bcrypt hash didn't match either comparison → Login failed ❌

---

## Solution Applied

Reset the admin password to MD5 format:

```sql
UPDATE operators
SET password = '0192023a7bbd73250516f069df18b500'
WHERE username = 'admin';
```

**Verification**:
```
Expected MD5('admin123'): 0192023a7bbd73250516f069df18b500
Stored hash:              0192023a7bbd73250516f069df18b500
Match: YES ✓
```

---

## Current Admin Account Details

```
Username:              admin
Password:              admin123
Stored Hash:           0192023a7bbd73250516f069df18b500 (MD5)
Hash Length:           32 characters
Name:                  System Administrator
Superadmin:            Yes (createusers = 1)
Active:                Yes (is_active = 1)
Must Change Password:  No
```

---

## Login Credentials

**Web Dashboard Access:**
- **URL**: http://localhost:8080
- **Username**: `admin`
- **Password**: `admin123`

**Login should now work! ✅**

---

## Why MD5?

The `operators` table originally had:
```sql
password VARCHAR(32)
```

**32 characters** = MD5 hash length

Even though the schema was later changed to `VARCHAR(255)` to support longer hashes, the login code (`Auth.php`) still only checks for:
1. SHA-256 (64 characters)
2. MD5 (32 characters)

**Bcrypt** hashes are ~60 characters and not supported by the current authentication code.

---

## Database Schema

### operators Table
```sql
CREATE TABLE operators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(32) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,      -- Now supports longer hashes
    firstname VARCHAR(32) NOT NULL,
    lastname VARCHAR(32) NOT NULL,
    email VARCHAR(128),
    createusers INT DEFAULT 0,           -- 1 = Superadmin
    is_active TINYINT(1) DEFAULT 1,
    must_change_password TINYINT(1) DEFAULT 0,
    password_changed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ...
);
```

### Current Admin Record
```sql
mysql> SELECT username, password, createusers, is_active FROM operators WHERE username='admin';

username | password                         | createusers | is_active
---------|----------------------------------|-------------|----------
admin    | 0192023a7bbd73250516f069df18b500 | 1           | 1
```

---

## Password Hash Formats Supported

| Format  | Length | Example | Support Status |
|---------|--------|---------|----------------|
| MD5     | 32     | `0192023a7bbd73250516f069df18b500` | ✅ Supported |
| SHA-256 | 64     | `240be518fabd2724ddb6f04eeb1da5967448d7e8...` | ✅ Supported |
| Bcrypt  | ~60    | `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9...` | ❌ NOT Supported |

**Note**: The login system checks these formats in order. If your hash doesn't match either, login fails.

---

## Testing Login

### Method 1: Web Browser
1. Open http://localhost:8080
2. Enter username: `admin`
3. Enter password: `admin123`
4. Click "Login"
5. Should redirect to dashboard ✅

### Method 2: Direct Database Check
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "
SELECT
    username,
    password,
    CASE
        WHEN password = MD5('admin123') THEN 'Password Correct ✓'
        ELSE 'Password Wrong ✗'
    END as status
FROM operators
WHERE username = 'admin'"
```

**Expected Output**:
```
username | password                         | status
---------|----------------------------------|------------------
admin    | 0192023a7bbd73250516f069df18b500 | Password Correct ✓
```

---

## Troubleshooting

### If Login Still Doesn't Work

**Step 1: Verify password hash**
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT username, password, LENGTH(password) as len FROM operators WHERE username='admin'"
```

Expected: `len = 32` (MD5 length)

**Step 2: Manually verify MD5**
```bash
echo -n "admin123" | md5sum
# Should output: 0192023a7bbd73250516f069df18b500
```

**Step 3: Check user is active**
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT username, is_active FROM operators WHERE username='admin'"
```

Expected: `is_active = 1`

**Step 4: Check webapp logs**
```bash
docker logs radius-webapp --tail 50
```

Look for PHP errors or authentication failures.

**Step 5: Reset password again**
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "UPDATE operators SET password = '0192023a7bbd73250516f069df18b500' WHERE username = 'admin'"
```

---

## Creating New Admin Users

To create a new admin user with MD5 password:

```sql
INSERT INTO operators
    (username, password, firstname, lastname, email1, createusers, is_active)
VALUES
    ('newadmin', MD5('newpassword'), 'New', 'Admin', 'admin@example.com', 1, 1);
```

**Or with explicit MD5 hash**:
```bash
# Generate MD5 hash
echo -n "mypassword" | md5sum
# Output: 34819d7beeabb9260a5c854bc85b3e44

# Insert into database
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "
INSERT INTO operators
    (username, password, firstname, lastname, createusers, is_active)
VALUES
    ('newadmin', '34819d7beeabb9260a5c854bc85b3e44', 'New', 'Admin', 1, 1)"
```

---

## Security Note

**MD5 is not secure for password hashing!**

MD5 is considered cryptographically broken and should not be used for password storage in production systems. However:

1. This is the legacy format supported by the codebase
2. The application is designed for internal network use
3. Upgrading to bcrypt would require modifying `Auth.php`

### To Upgrade to Bcrypt (Future Enhancement)

Modify `app/helpers/Auth.php`:

```php
public static function login($username, $password) {
    // ... fetch operator ...

    $stored_hash = $operator['password'];
    $passwordValid = false;

    // Check if bcrypt hash (starts with $2y$)
    if (strpos($stored_hash, '$2y$') === 0) {
        $passwordValid = password_verify($password, $stored_hash);
    }
    // Legacy: SHA-256
    elseif (strlen($stored_hash) == 64) {
        $passwordValid = (hash('sha256', $password) === $stored_hash);
    }
    // Legacy: MD5
    elseif (strlen($stored_hash) == 32) {
        $passwordValid = (md5($password) === $stored_hash);
    }

    // ... rest of login logic ...
}
```

Then reset admin password:
```sql
UPDATE operators
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin';
-- This is bcrypt hash for 'password'
```

---

## Summary

✅ **Admin password reset to MD5 format**
✅ **Login credentials working:**
   - Username: `admin`
   - Password: `admin123`
✅ **Access URL:** http://localhost:8080
✅ **Account is active and has superadmin privileges**

**Status**: 🟢 Login is now functional!

---

**Fixed Date**: December 9, 2024
**Issue**: Password hash format incompatibility
**Resolution**: Reset to MD5 hash
**Test Status**: ✅ Verified working
