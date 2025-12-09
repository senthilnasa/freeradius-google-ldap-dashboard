# User Type Distribution Report - December 9, 2025

## Status: ✅ COMPLETE

I've successfully created a comprehensive **User Type Distribution Report** in the Reports Hub that analyzes authentication patterns and user distribution by type.

---

## Overview

The User Type Distribution Report provides detailed insights into how different user types (Student-MBA, Student-SIAS, Staff, etc.) are authenticating to your network. This helps with capacity planning, security monitoring, and understanding usage patterns across different user segments.

---

## Features Implemented

### 1. **User Type Distribution Table** ✅
Shows comprehensive statistics for each user type:
- Total authentications
- Unique users
- Active days (days with at least one authentication)
- Average authentications per user
- Visual percentage distribution bars

### 2. **User Type & VLAN Correlation** ✅
Displays the relationship between user types and assigned VLANs:
- Verifies domain configuration is working correctly
- Shows which VLAN each user type is assigned to
- Helps identify misconfigured users

### 3. **Failed Authentications by User Type** ✅
Tracks failed login attempts categorized by inferred user type:
- Infers user type from username patterns (e.g., `.mba@krea.ac.in` → Student-MBA)
- Breaks down failures by error type
- Helps identify security issues or configuration problems

### 4. **Daily Breakdown** ✅
Shows authentication trends over time:
- Day-by-day breakdown of authentications by user type
- Helps identify peak usage times
- Tracks user activity patterns

### 5. **Summary Cards** ✅
Quick overview statistics:
- Total user types detected
- Total authentications in date range
- Unique users
- Average authentications per user

### 6. **PDF Export** ✅
Export capability for:
- Sharing reports with management
- Archiving for compliance
- Offline analysis

---

## Access

**URL:** http://localhost:8080/?page=reports&action=user-type-distribution

**Navigation Path:**
1. Login to dashboard
2. Click **"Reports"** in main navigation
3. Click **"User Type Distribution Report"** card (blue/info colored)

---

## How to Use

### Step 1: Access the Report
Navigate to **Reports Hub** → **User Type Distribution Report**

### Step 2: Select Date Range
- **From Date:** Start date for analysis (default: 30 days ago)
- **To Date:** End date for analysis (default: today)
- Click **"Generate Report"**

### Step 3: Review Statistics

The report displays:

#### **Summary Cards** (Top Row)
```
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│ Total User Types│ Total Auths     │ Unique Users    │ Avg Auths/User  │
│       5         │     3,456       │      450        │      7.7        │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

#### **User Type Distribution Table**
| User Type | Authentications | Unique Users | Active Days | Avg Auths/User | Distribution |
|-----------|----------------|--------------|-------------|----------------|--------------|
| 🔵 Staff | 1,500 | 45 | 28 | 33.3 | ████████████ 43.4% |
| 🔵 Student-MBA | 800 | 120 | 25 | 6.7 | ██████ 23.1% |
| 🔵 Student-SIAS | 600 | 95 | 22 | 6.3 | █████ 17.4% |
| 🔵 Student-BBA | 400 | 60 | 20 | 6.7 | ███ 11.6% |
| 🔵 Student-Ph D | 156 | 15 | 18 | 10.4 | █ 4.5% |

#### **User Type & VLAN Correlation**
| User Type | VLAN | Authentications |
|-----------|------|----------------|
| 🔵 Staff | 🔵 248 | 1,500 |
| 🔵 Student-MBA | 🔵 216 | 800 |
| 🔵 Student-SIAS | 🔵 224 | 600 |
| 🔵 Student-BBA | 🔵 240 | 400 |
| 🔵 Student-Ph D | 🔵 232 | 156 |

#### **Failed Authentications** (Inferred from username patterns)
| Inferred Type | Error Type | Failure Count |
|---------------|------------|---------------|
| Staff | password_wrong | 45 |
| Student-MBA | authentication_failed | 23 |
| Student-SIAS | ldap_connection_failed | 12 |

### Step 4: Export (Optional)
Click **"Export PDF"** to download the report as a PDF file.

---

## SQL Queries Used

### Main User Type Distribution Query
```sql
SELECT
    user_type,
    COUNT(*) as auth_count,
    COUNT(DISTINCT username) as unique_users,
    COUNT(DISTINCT DATE(authdate)) as active_days
FROM radpostauth
WHERE DATE(authdate) BETWEEN ? AND ?
  AND reply = 'Access-Accept'
  AND user_type IS NOT NULL
  AND user_type != ''
GROUP BY user_type
ORDER BY auth_count DESC
```

### User Type & VLAN Correlation Query
```sql
SELECT
    user_type,
    vlan,
    COUNT(*) as count
FROM radpostauth
WHERE DATE(authdate) BETWEEN ? AND ?
  AND reply = 'Access-Accept'
  AND user_type IS NOT NULL
  AND user_type != ''
GROUP BY user_type, vlan
ORDER BY user_type, count DESC
```

### Failed Authentications (Inferred) Query
```sql
SELECT
    CASE
        WHEN username LIKE '%.mba@%' THEN 'Student-MBA'
        WHEN username LIKE '%.sias@%' THEN 'Student-SIAS'
        WHEN username LIKE '%.bba@%' THEN 'Student-BBA'
        WHEN username LIKE '%.phd@%' THEN 'Student-Ph D'
        WHEN username LIKE '%@krea.edu.in' THEN 'Staff'
        ELSE 'Others'
    END as inferred_type,
    error_type,
    COUNT(*) as failure_count
FROM radpostauth
WHERE DATE(authdate) BETWEEN ? AND ?
  AND reply != 'Access-Accept'
GROUP BY inferred_type, error_type
ORDER BY failure_count DESC
```

### Daily Breakdown Query
```sql
SELECT
    DATE(authdate) as date,
    user_type,
    COUNT(*) as auth_count
FROM radpostauth
WHERE DATE(authdate) BETWEEN ? AND ?
  AND reply = 'Access-Accept'
  AND user_type IS NOT NULL
  AND user_type != ''
GROUP BY DATE(authdate), user_type
ORDER BY date DESC, auth_count DESC
```

---

## Files Created/Modified

### 1. **ReportsController.php** ✅
**File:** [`radius-gui/app/controllers/ReportsController.php`](radius-gui/app/controllers/ReportsController.php)
**Method Added:** `userTypeDistributionAction()` (Lines 316-412)

**What it does:**
- Retrieves user type statistics from database
- Calculates totals and percentages
- Fetches VLAN correlation data
- Generates daily breakdown
- Infers user types for failed authentications
- Handles PDF export

### 2. **user-type-distribution.php View** ✅
**File:** [`radius-gui/app/views/reports/user-type-distribution.php`](radius-gui/app/views/reports/user-type-distribution.php)

**What it displays:**
- Date range filter form
- Summary cards (4 metrics)
- User type distribution table with progress bars
- User type & VLAN correlation table
- Failed authentications table
- Daily breakdown table
- PDF export button

### 3. **Reports Index** ✅
**File:** [`radius-gui/app/views/reports/index.php`](radius-gui/app/views/reports/index.php)
**Lines Added:** 104-131

**What was added:**
- New report card on Reports Hub page
- Blue/info colored border
- User tag icon
- Date range inputs (last 30 days by default)
- "View Report" button

---

## Use Cases

### 1. Capacity Planning
**Scenario:** Network team needs to understand user distribution

**How to use:**
1. Run report for last 90 days
2. Review "User Type Distribution" table
3. Identify user types with highest auth counts
4. Plan network capacity accordingly

**Example Insights:**
- Staff accounts for 43% of authentications but only 10% of users
- MBA students are the largest user group (120 users)
- PhD students have highest average auths/user (10.4)

### 2. Security Monitoring
**Scenario:** Detect unusual authentication patterns

**How to use:**
1. Run report for last 7 days
2. Review "Failed Authentications" section
3. Check for spikes in specific user types
4. Investigate high failure counts

**Example Alerts:**
- Staff accounts have 45 password failures → possible brute force
- Student-MBA has 23 auth failures → credential sharing?
- LDAP connection errors affecting all SIAS students → server issue

### 3. VLAN Verification
**Scenario:** Verify domain configuration is working correctly

**How to use:**
1. Run report for any date range
2. Review "User Type & VLAN Correlation" table
3. Verify each user type maps to expected VLAN
4. Identify any misconfigurations

**Expected Mapping:**
```
Student-MBA  → VLAN 216
Student-SIAS → VLAN 224
Student-Ph D → VLAN 232
Student-BBA  → VLAN 240
Staff        → VLAN 248
```

### 4. Usage Trend Analysis
**Scenario:** Understand authentication patterns over time

**How to use:**
1. Run report for last 30 days
2. Review "Daily Breakdown" table
3. Identify peak usage days
4. Track user activity trends

**Example Insights:**
- Weekdays have 3x more auths than weekends
- Staff authentications spike on Mondays
- Student authentications drop during holidays

### 5. Compliance Reporting
**Scenario:** Generate reports for auditing

**How to use:**
1. Run report for compliance period (e.g., last month)
2. Review all statistics
3. Export as PDF
4. Archive for compliance records

---

## Sample Report Output

### Summary Statistics
```
Date Range: 2025-11-09 to 2025-12-09

Total User Types: 5
Total Authentications: 3,456
Unique Users: 450
Average Authentications per User: 7.7
```

### User Type Breakdown
```
1. Staff
   - Authentications: 1,500 (43.4%)
   - Unique Users: 45
   - Active Days: 28
   - Avg Auths/User: 33.3
   - VLAN: 248

2. Student-MBA
   - Authentications: 800 (23.1%)
   - Unique Users: 120
   - Active Days: 25
   - Avg Auths/User: 6.7
   - VLAN: 216

3. Student-SIAS
   - Authentications: 600 (17.4%)
   - Unique Users: 95
   - Active Days: 22
   - Avg Auths/User: 6.3
   - VLAN: 224

4. Student-BBA
   - Authentications: 400 (11.6%)
   - Unique Users: 60
   - Active Days: 20
   - Avg Auths/User: 6.7
   - VLAN: 240

5. Student-Ph D
   - Authentications: 156 (4.5%)
   - Unique Users: 15
   - Active Days: 18
   - Avg Auths/User: 10.4
   - VLAN: 232
```

---

## Important Notes

### Data Availability
⚠️ **User Type Data Requirements:**
- User type logging only captures **successful authentications** (Access-Accept)
- Historical data before user_type column was added will show NULL
- Failed authentications don't receive user type assignments (shown as NULL in database)
- Failed auth report uses **inferred** user types based on username patterns

### No Data Scenarios
If the report shows **"No user type data available"**, possible reasons:
1. No successful authentications in selected date range
2. All authentications occurred before user_type logging was implemented
3. Domain configuration not properly set up
4. Users authenticating from domains not in [`domain-config.json`](domain-config.json)

### Inferred vs. Actual User Types
- **Actual User Types:** From successful authentications (stored in database)
- **Inferred User Types:** From failed authentications (pattern matching on username)

**Example:**
- `john.mba@krea.ac.in` failed auth → **Inferred** as "Student-MBA"
- `john.mba@krea.ac.in` successful auth → **Actual** "Student-MBA" (from database)

---

## Customization

### Adding More User Types
To track additional user types, update [`domain-config.json`](domain-config.json):

```json
{
  "domain": "krea.ac.in",
  "key": ".executive",
  "Type": "Executive-MBA",
  "VLAN": "250"
}
```

The report will automatically detect and display the new type.

### Modifying Inferred Type Logic
To change how failed authentications are categorized, edit the CASE statement in [`ReportsController.php`](radius-gui/app/controllers/ReportsController.php:378-386):

```php
CASE
    WHEN username LIKE '%.mba@%' THEN 'Student-MBA'
    WHEN username LIKE '%.sias@%' THEN 'Student-SIAS'
    WHEN username LIKE '%.executive@%' THEN 'Executive-MBA'  // Add new pattern
    // ... more patterns
END as inferred_type
```

---

## Technical Details

### Performance Optimization
The report uses indexed columns for fast queries:
- `idx_authdate` - Speeds up date range filtering
- `idx_reply` - Speeds up Access-Accept filtering
- `idx_user_type` - Speeds up user type grouping
- `idx_vlan` - Speeds up VLAN correlation

### Caching
- No caching currently implemented
- Report queries run on demand
- For large datasets (>1M records), consider adding caching

### Export Format
PDF export includes:
- All tables from the web report
- Date range and generation timestamp
- Summary statistics
- Styled headers and branding

---

## Troubleshooting

### Issue: "No user type data available"
**Solution:**
1. Check date range - ensure it includes recent data
2. Verify successful authentications exist:
   ```sql
   SELECT COUNT(*) FROM radpostauth
   WHERE reply = 'Access-Accept'
     AND user_type IS NOT NULL;
   ```
3. Run a test authentication to generate data

### Issue: VLAN column shows unexpected values
**Solution:**
1. Verify domain configuration in [`domain-config.json`](domain-config.json)
2. Check FreeRADIUS is assigning VLANs correctly
3. Restart FreeRADIUS container if config was changed

### Issue: Failed auth section empty
**Solution:**
- This is normal if there are no failed authentications
- Failed auths only appear when authentication failures occur

---

## Summary

✅ **Report Created:** User Type Distribution Report
✅ **Location:** Reports Hub → User Type Distribution
✅ **Features:** 5 sections (distribution, VLAN correlation, failed auths, daily breakdown, summary)
✅ **Export:** PDF support included
✅ **Performance:** Indexed queries for fast results
✅ **UI:** Responsive design with progress bars and badges

**Access URL:** http://localhost:8080/?page=reports&action=user-type-distribution

---

**Implementation Date:** December 9, 2025
**Status:** ✅ COMPLETE AND DEPLOYED
**Files Modified:** 2 (ReportsController.php, reports/index.php)
**Files Created:** 1 (reports/user-type-distribution.php)
**Webapp Status:** Restarted and healthy

The User Type Distribution Report is now live and ready to use!
