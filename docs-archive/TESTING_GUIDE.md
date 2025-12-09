# Complete Testing Guide - User Management & Forced Password Change

## ✅ All Issues Fixed

1. ✅ Database::execute() method error - FIXED (using Database::update())
2. ✅ Undefined user_id session error - FIXED (added login check)
3. ✅ Admin login password - UPDATED (now using admin123)

## Test Credentials

```
URL: http://localhost:8080
Username: admin
Password: admin123
```

## Complete Test Flow

### Step 1: Initial Login

1. **Open browser** and navigate to http://localhost:8080
2. **You should see** the login page
3. **Enter credentials**:
   - Username: `admin`
   - Password: `admin123`
4. **Click** "Login"

### Step 2: Forced Password Change Redirect

**Expected Result:**
- ✅ Login succeeds
- ✅ Immediately redirected to: `http://localhost:8080/public/index.php?page=users&action=change-password`
- ✅ Page displays: "Change Password" heading
- ✅ Warning banner shows: "⚠ Password Change Required"
- ✅ Message states: "You must change your password before continuing..."

### Step 3: Test Access Restriction

**Try to navigate to other pages:**
- Click "Dashboard" in menu → Redirected back to change password ✓
- Click "Reports" in menu → Redirected back to change password ✓
- Click "Online Users" in menu → Redirected back to change password ✓
- Click "User Management" in menu → Redirected back to change password ✓

**Only these should work:**
- ✅ "Change Password" page (current page)
- ✅ "Logout" link

### Step 4: Change Password

1. **Fill in the form:**
   ```
   Current Password: admin123
   New Password: YourNewPassword123  (minimum 6 characters)
   Confirm Password: YourNewPassword123
   ```

2. **Click** "Change Password" button

**Expected Result:**
- ✅ Success message displayed
- ✅ Redirected to dashboard
- ✅ Full access restored to all pages
- ✅ Can now navigate freely

### Step 5: Verify Database Changes

```bash
# Connect to database
docker exec -it radius-mysql mysql -uroot -p'SecureRootPass2024!' radius

# Check the changes
SELECT
    username,
    must_change_password,
    password_changed_at,
    updated_at
FROM operators
WHERE username = 'admin';
```

**Expected Results:**
- `must_change_password`: 0 (was 1 before)
- `password_changed_at`: [timestamp of when you changed password]
- `updated_at`: [timestamp of when you changed password]

### Step 6: Test New Password

1. **Logout** (click Logout in menu)
2. **Login again** with:
   - Username: `admin`
   - Password: `YourNewPassword123` (the new password you set)
3. **Expected Result:**
   - ✅ Login succeeds
   - ✅ Goes directly to dashboard (NO forced password change)
   - ✅ Full access to all pages

## Additional Tests

### Test 1: Password Requirements

Try changing password with invalid inputs:

**Test Case A: Password too short**
```
Current Password: admin123
New Password: test  (only 4 characters)
Confirm Password: test
```
**Expected:** Error: "New password must be at least 6 characters"

**Test Case B: Passwords don't match**
```
Current Password: admin123
New Password: password123
Confirm Password: password456
```
**Expected:** Error: "New passwords do not match"

**Test Case C: Wrong current password**
```
Current Password: wrongpassword
New Password: newpass123
Confirm Password: newpass123
```
**Expected:** Error: "Current password is incorrect"

### Test 2: User Management Access

**As regular user (after changing password from forced state):**
1. Try to access: http://localhost:8080/public/index.php?page=users
2. **Expected:** Access Denied message (only superadmins can manage users)

**To test as superadmin:**
- Login with the `administrator` account (if you know the password)
- OR promote current user to superadmin in database

### Test 3: Change Password from Menu

**After completing forced password change:**
1. **Click** "Change Password" in the navigation menu
2. **Should see** the same change password form (but without the warning banner)
3. **Change password** to another value
4. **Expected:** Works normally without redirecting after

## Troubleshooting

### Issue: Can't login with admin/admin123

**Check password in database:**
```sql
SELECT username, password, must_change_password
FROM operators
WHERE username = 'admin';

-- Password should be: 0192023a7bbd73250516f069df18b500 (MD5 of 'admin123')
-- If not, run:
UPDATE operators
SET password = '0192023a7bbd73250516f069df18b500',
    must_change_password = 1
WHERE username = 'admin';
```

### Issue: Page shows "undefined user_id" error

**This means you're not logged in.**
- Clear browser cookies/cache
- Go back to http://localhost:8080
- Login again with admin/admin123

### Issue: Stuck in password change loop

**Clear the flag manually:**
```sql
UPDATE operators
SET must_change_password = 0
WHERE username = 'admin';
```

Then logout and login again.

### Issue: Password change doesn't work

**Check webapp logs:**
```bash
docker-compose logs webapp --tail=100
```

Look for PHP errors or database errors.

## Reset Test Account

To reset and re-test the complete flow:

```sql
-- Reset admin account to default state
UPDATE operators
SET
    password = '0192023a7bbd73250516f069df18b500',  -- admin123
    must_change_password = 1,
    password_changed_at = NULL
WHERE username = 'admin';
```

Then logout and login with admin/admin123 to test again.

## Production Checklist

Before deploying to production:

- [ ] Remove or disable test account
- [ ] Force password change for all accounts
- [ ] Verify all operators have strong passwords
- [ ] Test with actual production users
- [ ] Enable HTTPS/SSL
- [ ] Review session security settings
- [ ] Consider implementing password expiry
- [ ] Set up password complexity requirements
- [ ] Implement account lockout after failed attempts

## Container Status

Verify all services are running:

```bash
docker-compose ps

# Should show:
# freeradius-google-ldap   Up (healthy)
# radius-mysql             Up (healthy)
# radius-webapp            Up (healthy)
```

## Feature Checklist

✅ User Management Module
✅ Password Change Functionality
✅ Forced Password Change on First Login
✅ Password Validation (min 6 characters)
✅ Password Confirmation Matching
✅ Current Password Verification
✅ Session Management
✅ Access Restriction While Password Change Required
✅ Database Updates (must_change_password, password_changed_at)
✅ Success/Error Messages
✅ Redirect to Dashboard After Success

---

**Last Updated:** December 5, 2025
**Status:** All Features Working ✅
**Test Account:** admin / admin123 (forced password change enabled)
