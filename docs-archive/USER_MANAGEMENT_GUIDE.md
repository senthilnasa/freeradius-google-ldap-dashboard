# User Management and Password Security Guide

## Overview

The RADIUS Dashboard now includes comprehensive user management features with forced password changes for security.

## Features Added

### 1. User Management Module
- **Location**: Navigation menu → "User Management"
- **Access**: Superadmin only
- **Capabilities**:
  - View all operator accounts
  - Create new operators
  - Edit existing operators
  - Delete operators (with safeguards)
  - Manage permissions

### 2. Password Change System
- **Location**: Navigation menu → "Change Password"
- **Access**: All authenticated users
- **Features**:
  - Secure password change interface
  - Current password verification
  - Password strength requirements
  - Confirmation matching

### 3. Forced Password Change on First Login
- **Trigger**: Users with default passwords are automatically flagged
- **Behavior**:
  - Users are redirected to change password page immediately after login
  - Cannot access any other pages until password is changed
  - Only logout and change password are accessible
- **Security**: Prevents use of default/weak passwords

## Database Enhancements

New columns added to `operators` table:

| Column | Type | Description |
|--------|------|-------------|
| `must_change_password` | TINYINT(1) | Flag requiring password change (1=yes, 0=no) |
| `password_changed_at` | DATETIME | Timestamp of last password change |
| `created_at` | DATETIME | Account creation timestamp |
| `updated_at` | DATETIME | Last modification timestamp |
| `is_active` | TINYINT(1) | Account status (1=active, 0=inactive) |

## Testing the System

### Test Account

For testing, an admin account has been configured:

**Username**: `admin`
**Password**: `admin` (default - will be forced to change)

### Testing Steps

#### 1. Test Forced Password Change

```bash
# Access the dashboard
http://localhost:8080

# Login with test credentials
Username: admin
Password: admin

# Expected behavior:
# - Login succeeds
# - Immediately redirected to /index.php?page=users&action=change-password
# - Warning message displayed: "You must change your password before continuing"
# - Cannot navigate to any other page
```

#### 2. Change Password

```
Current Password: admin
New Password: <your-new-password>
Confirm Password: <your-new-password>

# Requirements:
# - Minimum 6 characters
# - Must match confirmation
# - Current password must be correct
```

#### 3. Verify Normal Access After Password Change

```
# After successful password change:
# - Redirected to dashboard
# - Full access restored
# - must_change_password flag cleared
# - password_changed_at timestamp updated
```

## Password Hashing

**Important**: The `operators.password` column is `varchar(32)`, which limits us to MD5 hashing.

### Current Implementation
- **Hash Algorithm**: MD5
- **Column Size**: 32 characters
- **Supported Hashes**: MD5 only (SHA-256 would require 64 characters)

### Future Upgrade Path (Optional)
To use stronger hashing (bcrypt/SHA-256):
```sql
ALTER TABLE operators MODIFY COLUMN password VARCHAR(255);
```
Then update the code to use `password_hash()` and `password_verify()`.

## Security Features

### 1. Authentication Required
- All user management functions require authentication
- Superadmin role required for user CRUD operations
- Regular users can only change their own password

### 2. Password Requirements
- Minimum 6 characters
- Password confirmation required
- Current password verification for changes

### 3. Account Safeguards
- Cannot delete your own account
- Cannot delete the last superadmin account
- Cannot disable your own account if last superadmin

### 4. Forced Password Change
- Automatic detection of default passwords
- Immediate redirect after login
- Blocks access to all features except logout and password change

## Navigation Menu Updates

The sidebar navigation now includes:

```
Dashboard
Online Users
Auth Log
User History
Reports
Settings
User Management ← NEW (Superadmin only)
─────────────────
Change Password ← NEW (All users)
Logout
```

## File Structure

### Controllers
- `radius-gui/app/controllers/UsersController.php`
  - `indexAction()` - List all users
  - `createAction()` - Create new user
  - `editAction()` - Edit existing user
  - `deleteAction()` - Delete user
  - `changePasswordAction()` - Change current user's password ← NEW

### Views
- `radius-gui/app/views/users/index.php` - User list (existing)
- `radius-gui/app/views/users/create.php` - Add user form (existing)
- `radius-gui/app/views/users/edit.php` - Edit user form (existing)
- `radius-gui/app/views/users/change-password.php` - Password change form ← NEW

### Core Files Modified
1. **Auth Helper** (`radius-gui/app/helpers/Auth.php`)
   - Added `must_change_password` session flag on login
   - Lines 92-95

2. **Main Router** (`radius-gui/public/index.php`)
   - Added forced password change redirect logic
   - Lines 48-58

3. **Navigation** (`radius-gui/app/views/layouts/header.php`)
   - Added User Management link
   - Added Change Password link
   - Lines 223-228

4. **Database Schema** (`sql/04-enhance-operators-table.sql`)
   - Migration to add new password management columns

## Admin Interface

### User Management Page

```
User Management
┌─────────────────────────────────────────────────────┐
│ [+ Add New User]                     [Search: ____] │
├─────────────────────────────────────────────────────┤
│ Username    | Name          | Email       | Actions │
│─────────────────────────────────────────────────────│
│ admin       | Admin User    | admin@...   | [Edit]  │
│ administrator| Default Admin| admin@...   | [Edit]  │
└─────────────────────────────────────────────────────┘
```

### Change Password Page

```
Change Password
┌─────────────────────────────────────┐
│ ⚠ Password Change Required          │
│ You must change your password...    │
├─────────────────────────────────────┤
│ Current Password: [______________]  │
│ New Password:     [______________]  │
│ Confirm Password: [______________]  │
│                                     │
│ [Change Password]      [Cancel]     │
└─────────────────────────────────────┘

Password Requirements:
• Minimum 6 characters
• Use mix of letters, numbers, symbols
• Avoid common words
```

## Error Handling

### Common Errors and Solutions

#### 1. "Current password is incorrect"
- **Cause**: Wrong current password entered
- **Solution**: Verify you're using the correct password

#### 2. "Passwords do not match"
- **Cause**: New password and confirmation don't match
- **Solution**: Ensure both fields have identical values

#### 3. "Password must be at least 6 characters"
- **Cause**: New password too short
- **Solution**: Use a password with 6+ characters

#### 4. "Access Denied: Only superadmins can manage users"
- **Cause**: Non-superadmin trying to access User Management
- **Solution**: Only superadmin accounts can manage users

## Production Deployment

### Before Going Live

1. **Remove Test Account**
   ```sql
   DELETE FROM operators WHERE username = 'admin' AND must_change_password = 1;
   ```

2. **Set All Existing Accounts to Change Password**
   ```sql
   UPDATE operators SET must_change_password = 1, password_changed_at = NULL;
   ```

3. **Review Password Policy**
   - Consider increasing minimum length requirement
   - Add complexity requirements if needed
   - Implement password expiry if required

4. **Upgrade to Stronger Hashing** (Recommended)
   ```sql
   ALTER TABLE operators MODIFY COLUMN password VARCHAR(255);
   ```
   Then update code to use bcrypt via `password_hash()`.

## Troubleshooting

### User Stuck in Password Change Loop

If a user gets stuck being redirected to change password after successfully changing it:

```sql
-- Clear the flag manually
UPDATE operators SET must_change_password = 0 WHERE username = 'username';
```

### Default Password Detection Not Working

The system checks for MD5 hash of "admin":
```sql
-- Check current password hash
SELECT username, password, must_change_password FROM operators;

-- Hash of 'admin': 21232f297a57a5a743894a0e4a801fc3
```

### Cannot Access Any Pages After Login

This is expected behavior if `must_change_password = 1`. Change your password to restore access.

## API Reference

### Session Variables

After login with forced password change:

```php
$_SESSION = [
    'user_id' => 1,
    'username' => 'admin',
    'logged_in' => true,
    'must_change_password' => true,  // ← Triggers redirect
    // ... other fields
];
```

After successful password change:

```php
unset($_SESSION['must_change_password']);
// User can now access all pages
```

## Security Best Practices

1. **Change All Default Passwords Immediately**
2. **Use Strong, Unique Passwords**
3. **Regularly Review User Accounts**
4. **Disable Inactive Accounts**
5. **Monitor Password Change Timestamps**
6. **Consider Password Rotation Policies**
7. **Upgrade to Bcrypt When Possible**

## Support

For issues or questions:
- Check the logs: `docker-compose logs webapp`
- Review database state: `SELECT * FROM operators;`
- Verify session state in browser dev tools

---

**Last Updated**: December 5, 2025
**Version**: 1.0.0
