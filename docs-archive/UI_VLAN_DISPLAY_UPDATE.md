# VLAN Display in UI - December 8, 2024

## Summary

Added VLAN ID display to Authentication Log and Daily Authentication Report in the web dashboard, allowing administrators to easily track VLAN assignments and troubleshoot network segmentation.

## Changes Made

### 1. Authentication Log Page

**File**: [radius-gui/app/controllers/AuthLogController.php](radius-gui/app/controllers/AuthLogController.php)

Added `vlan` column to SQL queries:

```php
// Main query (line 39)
$sql = "SELECT
            id,
            username,
            reply,
            reply_message,
            error_type,
            vlan,              // ← NEW
            authdate,
            authdate_utc,
            CONVERT_TZ(authdate_utc, '+00:00', '+05:30') as authdate_ist
        FROM radpostauth
        WHERE DATE(authdate) BETWEEN ? AND ?";

// CSV export query (line 118)
$sql = "SELECT
            username,
            reply,
            reply_message,
            error_type,
            vlan,              // ← NEW
            authdate,
            authdate_utc
        FROM radpostauth
        WHERE DATE(authdate) BETWEEN ? AND ?";
```

**CSV Export Headers Updated**:
```php
$headers = [
    'Date & Time (IST)',
    'UTC Time',
    'Username',
    'Result',
    'VLAN',              // ← NEW
    'Error Type',
    'Message'
];
```

**File**: [radius-gui/app/views/auth-log/index.php](radius-gui/app/views/auth-log/index.php)

Added VLAN column to table display:

```php
<thead>
    <tr>
        <th>Date & Time (IST)</th>
        <th>UTC Time</th>
        <th>Username</th>
        <th>Result</th>
        <th>VLAN</th>          <!-- NEW -->
        <th>Error Type</th>
        <th>Message</th>
    </tr>
</thead>
<tbody>
    ...
    <td>
        <?php if (!empty($log['vlan'])): ?>
            <span class="badge bg-info">
                <i class="fas fa-network-wired"></i> <?= Utils::e($log['vlan']) ?>
            </span>
        <?php else: ?>
            <span class="text-muted">-</span>
        <?php endif; ?>
    </td>
    ...
</tbody>
```

### 2. Daily Authentication Report

**File**: [radius-gui/app/controllers/ReportsController.php](radius-gui/app/controllers/ReportsController.php)

Added two new statistical queries:

#### A. VLAN Breakdown Query (lines 101-113)

```php
// Get VLAN breakdown for successful authentications
$vlanStats = $this->db->fetchAll(
    "SELECT
        vlan,
        COUNT(*) as auth_count,
        COUNT(DISTINCT username) as unique_users
    FROM radpostauth
    WHERE DATE(authdate) = ?
      AND reply = 'Access-Accept'
      AND vlan IS NOT NULL
      AND vlan != ''
    GROUP BY vlan
    ORDER BY auth_count DESC",
    [$date]
);
```

#### B. Error Type Breakdown Query (lines 117-129)

```php
// Get error type breakdown for failed authentications
$errorStats = $this->db->fetchAll(
    "SELECT
        error_type,
        COUNT(*) as error_count,
        COUNT(DISTINCT username) as affected_users
    FROM radpostauth
    WHERE DATE(authdate) = ?
      AND reply != 'Access-Accept'
      AND error_type IS NOT NULL
      AND error_type != ''
    GROUP BY error_type
    ORDER BY error_count DESC",
    [$date]
);
```

**File**: [radius-gui/app/views/reports/daily-auth.php](radius-gui/app/views/reports/daily-auth.php)

Added two new sections:

#### A. VLAN Assignments Section

```php
<!-- VLAN Statistics -->
<?php if (!empty($vlanStats)): ?>
<div class="card mt-4">
    <div class="card-header">
        <i class="fas fa-network-wired"></i> VLAN Assignments
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>VLAN ID</th>
                        <th>Authentications</th>
                        <th>Unique Users</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Shows VLAN distribution with progress bars -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
```

#### B. Failed Authentication Breakdown Section

```php
<!-- Error Type Statistics -->
<?php if (!empty($errorStats)): ?>
<div class="card mt-4">
    <div class="card-header">
        <i class="fas fa-exclamation-triangle"></i> Failed Authentication Breakdown
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Error Type</th>
                        <th>Count</th>
                        <th>Affected Users</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Shows error type distribution with progress bars -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
```

## UI Screenshots Description

### Authentication Log Page

**URL**: `http://localhost:8080/index.php?page=auth-log`

**Features**:
- ✅ New "VLAN" column between "Result" and "Error Type"
- ✅ VLAN IDs displayed as blue badges with network icon
- ✅ Shows "-" for failed authentications (no VLAN assigned)
- ✅ VLAN data included in CSV exports
- ✅ Sortable and filterable like other columns

**Example Display**:
```
Date & Time         | Username              | Result  | VLAN      | Error Type        | Message
2024-12-08 17:30:15 | user@krea.edu.in     | Success | [🔌 156]  | -                 | Authenticated as Staff
2024-12-08 17:25:42 | invaliduser          | Failed  | -         | Invalid Domain    | Invalid username format
```

### Daily Authentication Report

**URL**: `http://localhost:8080/index.php?page=reports&action=daily-auth`

**New Sections Added**:

#### 1. VLAN Assignments Table
Displays VLAN usage statistics:
- VLAN ID (badge with icon)
- Number of authentications per VLAN
- Unique users per VLAN
- Percentage bar showing distribution

**Example**:
```
┌──────────┬─────────────────┬──────────────┬────────────────────────┐
│ VLAN ID  │ Authentications │ Unique Users │ Percentage             │
├──────────┼─────────────────┼──────────────┼────────────────────────┤
│ 🔌 156   │ 450             │ 120          │ ████████████░░░░░ 65%  │
│ 🔌 216   │ 180             │ 45           │ ████░░░░░░░░░░░░░ 25%  │
│ 🔌 224   │ 70              │ 18           │ ██░░░░░░░░░░░░░░░ 10%  │
└──────────┴─────────────────┴──────────────┴────────────────────────┘
```

#### 2. Failed Authentication Breakdown
Shows error type distribution:
- Error Type (badge)
- Error count
- Number of affected users
- Percentage bar

**Example**:
```
┌────────────────────────┬───────┬─────────────────┬────────────────────────┐
│ Error Type             │ Count │ Affected Users  │ Percentage             │
├────────────────────────┼───────┼─────────────────┼────────────────────────┤
│ Authentication Failed  │ 85    │ 42              │ ██████████░░░░░░░ 60%  │
│ Invalid Domain         │ 40    │ 28              │ █████░░░░░░░░░░░░ 28%  │
│ Password Wrong         │ 17    │ 12              │ ██░░░░░░░░░░░░░░░ 12%  │
└────────────────────────┴───────┴─────────────────┴────────────────────────┘
```

## Benefits

### For Network Administrators

1. **Quick VLAN Verification**
   - Instantly see which VLAN was assigned to each user
   - Verify VLAN assignments match domain/user type policies
   - Troubleshoot connectivity issues related to VLAN segmentation

2. **Usage Analytics**
   - Track VLAN utilization across different user groups
   - Identify heavily-used vs. underutilized VLANs
   - Plan capacity based on VLAN distribution

3. **Export Capabilities**
   - CSV exports include VLAN data for offline analysis
   - Share reports with network teams
   - Archive for compliance/audit purposes

### For Security Teams

1. **Access Pattern Analysis**
   - Monitor which VLANs are being accessed
   - Detect unusual VLAN assignment patterns
   - Track user movement across network segments

2. **Error Correlation**
   - Link authentication failures to network segments
   - Identify if specific VLANs have higher failure rates
   - Troubleshoot network-specific auth issues

3. **Audit Trail**
   - Complete history of VLAN assignments per user
   - Timestamped records with UTC and local time
   - Meets compliance requirements for network access logging

## Usage Examples

### Example 1: Verify User's VLAN Assignment

**Scenario**: User reports connectivity issues, need to verify VLAN

**Steps**:
1. Navigate to **Authentication Log**
2. Enter username in filter
3. Click Search
4. Check the **VLAN** column for their assignments

**Result**: Quickly confirm if user is being placed on correct VLAN (e.g., VLAN 156 for staff)

### Example 2: Daily VLAN Usage Report

**Scenario**: Generate daily report showing VLAN distribution

**Steps**:
1. Navigate to **Reports → Daily Authentication Summary**
2. Select date
3. Scroll to **VLAN Assignments** section

**Result**: See complete breakdown:
- 65% of users on VLAN 156 (Staff)
- 25% of users on VLAN 216 (MBA Students)
- 10% of users on VLAN 224 (SIAS Students)

### Example 3: Export Authentication Data with VLAN

**Scenario**: Need to provide network team with auth data including VLANs

**Steps**:
1. Navigate to **Authentication Log**
2. Set date range filter
3. Click **Export CSV**
4. Share CSV file with network team

**Result**: CSV includes VLAN column for all authentications

### Example 4: Troubleshoot Domain-VLAN Mapping

**Scenario**: Verify that `krea.edu.in` users get VLAN 156

**Steps**:
1. Navigate to **Authentication Log**
2. Filter: username = `@krea.edu.in`, Result = Success
3. Review VLAN column

**Result**: Confirm all matching users show VLAN 156 badge

### Example 5: Identify Failed Auth Patterns

**Scenario**: Many failed authentications, need to understand why

**Steps**:
1. Navigate to **Reports → Daily Authentication Summary**
2. Select today's date
3. Review **Failed Authentication Breakdown**

**Result**: See categorized errors:
- 60% = Authentication Failed (wrong password/user not found)
- 28% = Invalid Domain (unsupported domains)
- 12% = Password Wrong (LDAP bind failures)

## Technical Details

### Badge Styling

VLAN badges use Bootstrap's `bg-info` class with custom icon:

```html
<span class="badge bg-info">
    <i class="fas fa-network-wired"></i> 156
</span>
```

**Colors**:
- VLAN badges: Blue (`bg-info`)
- Success badges: Green (`bg-success`)
- Error badges: Yellow (`bg-warning`)
- Failed badges: Red (`bg-danger`)

### Progress Bars

Percentage display uses Bootstrap progress bars:

```html
<div class="progress" style="height: 20px;">
    <div class="progress-bar bg-info" role="progressbar"
         style="width: 65%;"
         aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">
        65%
    </div>
</div>
```

### Query Performance

All queries use indexed columns:
- `authdate` - indexed for date filtering
- `vlan` - indexed for VLAN queries
- `error_type` - indexed for error filtering
- `reply` - indexed for success/fail filtering

## Files Modified

1. **[radius-gui/app/controllers/AuthLogController.php](radius-gui/app/controllers/AuthLogController.php)**
   - Added `vlan` to main query (line 39)
   - Added `vlan` to CSV export query (line 118)
   - Added `vlan` to CSV headers (line 153)

2. **[radius-gui/app/views/auth-log/index.php](radius-gui/app/views/auth-log/index.php)**
   - Added VLAN column header (line 80)
   - Added VLAN cell display with badge (lines 104-111)

3. **[radius-gui/app/controllers/ReportsController.php](radius-gui/app/controllers/ReportsController.php)**
   - Added VLAN statistics query (lines 101-113)
   - Added error statistics query (lines 117-129)
   - Updated PDF export call signature (line 134)

4. **[radius-gui/app/views/reports/daily-auth.php](radius-gui/app/views/reports/daily-auth.php)**
   - Added VLAN Assignments section (lines 82-128)
   - Added Failed Authentication Breakdown section (lines 131-177)

## Backward Compatibility

✅ **Fully backward compatible**
- Existing pages continue to work
- VLAN column shows "-" for old records without VLAN
- CSV exports work with or without VLAN data
- Reports gracefully hide VLAN/error sections if no data

## Testing Checklist

- [x] Authentication Log page loads correctly
- [x] VLAN column displays for successful authentications
- [x] VLAN shows "-" for failed authentications
- [x] CSV export includes VLAN column
- [x] Daily report shows VLAN statistics
- [x] Daily report shows error type breakdown
- [x] Progress bars display correctly
- [x] Badges styled consistently
- [x] Responsive design works on mobile
- [x] No PHP errors in logs

## Future Enhancements

Potential future additions:

1. **VLAN Filter**
   - Add VLAN dropdown to Auth Log filters
   - Filter by specific VLAN ID
   - Show all users on a particular VLAN

2. **VLAN Trend Charts**
   - Line chart showing VLAN usage over time
   - Stacked area chart for multi-VLAN comparison
   - Peak usage times per VLAN

3. **VLAN Assignment Reports**
   - Dedicated VLAN utilization report
   - Per-domain VLAN assignment summary
   - VLAN capacity planning data

4. **Dashboard Widgets**
   - Real-time VLAN distribution widget
   - Top VLANs by user count
   - VLAN usage sparklines

5. **Alerts**
   - Alert when VLAN not assigned (null)
   - Notify on unexpected VLAN assignments
   - Threshold alerts for VLAN capacity

## Documentation References

Related documentation:

- [VLAN_ERROR_LOGGING_UPDATE.md](VLAN_ERROR_LOGGING_UPDATE.md) - Backend logging implementation
- [VLAN_ATTRIBUTES_GUIDE.md](VLAN_ATTRIBUTES_GUIDE.md) - VLAN attribute configuration
- [VLAN_QUICK_START.md](VLAN_QUICK_START.md) - Quick setup guide

---

**Implementation Date**: December 8, 2024
**Status**: ✅ Complete and Deployed
**Containers Rebuilt**: webapp
**Breaking Changes**: None
**UI Changes**: Additive only
