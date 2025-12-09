# Login Credentials for RADIUS Dashboard

## ✅ Status: READY AND WORKING

All issues have been resolved:
- ✅ Database migration applied (password management columns added)
- ✅ Admin account created and configured
- ✅ User Management module working (email field fixed)
- ✅ Forced password change enabled for admin account
- ✅ VLAN attributes configuration added

## Test Accounts

### Admin Account (For Testing Forced Password Change)

```
URL: http://localhost:8080
Username: admin
Password: admin123
```

**Status**:
- Must change password on first login
- Will be redirected to change password page immediately after login
- Cannot access other pages until password is changed

### Administrator Account (Full Access)

```
URL: http://localhost:8080
Username: administrator
Password: [See database or contact system admin]
```

**Status**:
- Normal access (no forced password change)
- Full superadmin privileges

## Testing Forced Password Change

### Step-by-Step Test

1. **Navigate to Dashboard**
   ```
   http://localhost:8080
   ```

2. **Login with Test Account**
   ```
   Username: admin
   Password: admin123
   ```

3. **Expected Behavior**
   - Login succeeds
   - Immediately redirected to: `http://localhost:8080/public/index.php?page=users&action=change-password`
   - Warning banner displayed: "Password Change Required"
   - Message: "You must change your password before continuing. Your current password is a default password and must be changed for security reasons."

4. **Try to Navigate Away**
   - Attempt to click on any menu item (Dashboard, Reports, etc.)
   - Result: Redirected back to change password page
   - Only accessible pages: Change Password and Logout

5. **Change Password**
   ```
   Current Password: admin123
   New Password: [your-new-password]  (min 6 characters)
   Confirm Password: [your-new-password]
   ```

6. **After Password Change**
   - Success message displayed
   - Redirected to dashboard
   - Full access restored
   - Can navigate to all pages normally

## Verify Database Changes

After changing password, verify in database:

```sql
-- Connect to database
docker exec -it radius-mysql mysql -uroot -p'SecureRootPass2024!' radius

-- Check password change status
SELECT
    username,
    must_change_password,
    password_changed_at,
    updated_at
FROM operators
WHERE username = 'admin';

-- Expected results:
-- must_change_password: 0 (was 1 before change)
-- password_changed_at: [current timestamp]
-- updated_at: [current timestamp]
```

## Common Issues and Solutions

### Issue: Login fails with admin/admin123

**Solution**: Make sure the password hash is correctly set in database:
```sql
-- Check current hash
SELECT username, password FROM operators WHERE username = 'admin';

-- Should be: 0192023a7bbd73250516f069df18b500

-- If not, reset it:
UPDATE operators
SET password = '0192023a7bbd73250516f069df18b500',
    must_change_password = 1
WHERE username = 'admin';
```

### Issue: Not redirected to change password page

**Check must_change_password flag**:
```sql
SELECT username, must_change_password FROM operators WHERE username = 'admin';

-- Should be 1. If not:
UPDATE operators SET must_change_password = 1 WHERE username = 'admin';
```

### Issue: Still redirected after changing password

**Clear the session flag manually**:
```sql
UPDATE operators SET must_change_password = 0 WHERE username = 'admin';
```

Then logout and login again.

### Issue: Password change doesn't work

**Check webapp container logs**:
```bash
docker-compose logs webapp --tail=50
```

Look for PHP errors or database connection issues.

## Password Hashing Reference

The system uses MD5 hashing (due to varchar(32) column constraint):

```
admin      → MD5: 21232f297a57a5a743894a0e4a801fc3
admin123   → MD5: 0192023a7bbd73250516f069df18b500
password   → MD5: 5f4dcc3b5aa765d61d8327deb882cf99
test123    → MD5: cc03e747a6afbbcbf8be7668acfebee5
```

## Resetting Test Account

To reset the admin account for re-testing:

```sql
-- Reset to default password with forced change
UPDATE operators
SET
    password = '0192023a7bbd73250516f069df18b500',  -- admin123
    must_change_password = 1,
    password_changed_at = NULL
WHERE username = 'admin';
```

Then logout and login again with admin/admin123.

## Production Recommendations

1. **Remove test account** before going live:
   ```sql
   DELETE FROM operators WHERE username = 'admin' AND must_change_password = 1;
   ```

2. **Force password change for all accounts**:
   ```sql
   UPDATE operators SET must_change_password = 1, password_changed_at = NULL;
   ```

3. **Use strong passwords** (minimum 12 characters recommended)

4. **Consider upgrading** to bcrypt:
   ```sql
   ALTER TABLE operators MODIFY COLUMN password VARCHAR(255);
   ```
   Then update Auth.php to use `password_hash()` and `password_verify()`.

## Support

If you encounter issues:
- Check container status: `docker-compose ps`
- View logs: `docker-compose logs webapp --tail=100`
- Verify database: `docker exec radius-mysql mysql -uroot -p'SecureRootPass2024!' radius`
- Review session in browser dev tools (Application → Cookies/Session Storage)

---

**Last Updated**: December 5, 2025
**Test Account**: admin / admin123 (forced password change enabled)
