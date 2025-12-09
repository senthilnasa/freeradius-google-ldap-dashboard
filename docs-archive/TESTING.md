# Testing Guide - FreeRADIUS Google LDAP Dashboard

Comprehensive testing documentation for the FreeRADIUS Google LDAP Dashboard project.

---

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Test Environment Setup](#test-environment-setup)
4. [Running Tests](#running-tests)
5. [Test Suite Details](#test-suite-details)
6. [Writing New Tests](#writing-new-tests)
7. [Continuous Integration](#continuous-integration)
8. [Manual Testing](#manual-testing)
9. [Troubleshooting](#troubleshooting)

---

## Overview

This project includes a comprehensive automated testing suite for:

- **RADIUS Authentication**: Testing successful and failed authentication scenarios
- **RADIUS Accounting**: Testing accounting start, interim-update, and stop packets
- **Web Application**: Testing dashboard functionality, exports, and user management
- **Error Tracking**: Verifying enhanced error logging and categorization
- **Database Integration**: Ensuring proper data storage and retrieval

### Test Coverage

| Component | Test Coverage | Status |
|-----------|--------------|--------|
| RADIUS Authentication | ✅ Comprehensive | 4 test scenarios |
| RADIUS Accounting | ✅ Full | Start/Interim/Stop |
| Web Application | ✅ Extensive | 10+ endpoint tests |
| Error Tracking | ✅ Complete | All error types |
| Database Integration | ✅ Full | All tables |

---

## Quick Start

**Run all tests with a single command:**

```bash
./test.sh
```

That's it! The script will:
1. Start the test environment (Docker containers)
2. Wait for all services to be ready
3. Run all tests
4. Display results
5. Clean up the environment

### Quick Start Options

```bash
# Keep environment running after tests (for debugging)
./test.sh --keep-running

# Rebuild Docker images before testing
./test.sh --rebuild

# Show verbose Docker output
./test.sh --verbose

# Show help
./test.sh --help
```

---

## Test Environment Setup

### Architecture

The test environment uses Docker Compose to orchestrate multiple services:

```
┌─────────────────────────────────────────────────────┐
│                Test Environment                      │
├─────────────────────────────────────────────────────┤
│                                                       │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────┐ │
│  │   MySQL      │  │  FreeRADIUS  │  │  WebApp   │ │
│  │   Test DB    │◄─┤   Server     │◄─┤  (PHP)    │ │
│  │  Port 3307   │  │  Ports 1812/ │  │ Port 8080 │ │
│  └──────────────┘  │   1813 (UDP) │  └───────────┘ │
│         ▲          └──────────────┘        ▲        │
│         │                 ▲                 │        │
│         │                 │                 │        │
│         │          ┌──────┴────────┐        │        │
│         └──────────┤ Test Client   ├────────┘        │
│                    │ (radclient,   │                 │
│                    │  curl, mysql) │                 │
│                    └───────────────┘                 │
│                                                       │
└─────────────────────────────────────────────────────┘
```

### Services

**1. MySQL Test Database** (`mysql-test`)
- Image: `mysql:8.0`
- Port: `3307` (external), `3306` (internal)
- Initialized with:
  - Main RADIUS schema (`00-init-radius-schema.sql`)
  - Error tracking columns (`01-add-error-tracking-columns.sql`)
  - Operators table (`02-create-operators-table.sql`)
  - Test data (`setup-test-data.sql`)

**2. FreeRADIUS Server** (`freeradius-test`)
- Build from: `Dockerfile`
- Ports: `1812/UDP` (auth), `1813/UDP` (accounting)
- Debug mode enabled (`freeradius -X`)
- Connected to MySQL test database

**3. Web Application** (`webapp-test`)
- Build from: `radius-gui/Dockerfile`
- Port: `8080`
- Connected to MySQL test database
- Debug mode enabled

**4. Test Client** (`radclient-test`)
- Image: `ubuntu:22.04`
- Tools installed:
  - `freeradius-utils` (radclient command)
  - `curl` (HTTP testing)
  - `jq` (JSON processing)
  - `mysql-client` (database verification)

### Configuration Files

```
docker-compose.test.yml   # Test environment orchestration
tests/
├── setup-test-data.sql   # Test data initialization
├── test-auth-success.sh  # Authentication success test
├── test-auth-password-wrong.sh
├── test-auth-user-not-found.sh
├── test-accounting.sh    # Accounting test
├── test-webapp.sh        # Web application test
└── run-all-tests.sh      # Master test runner
```

---

## Running Tests

### Method 1: Automated Test Runner (Recommended)

```bash
# Run all tests
./test.sh

# Example output:
# Step 1: Checking prerequisites...
#   ✓ Docker found
#   ✓ Docker Compose found
# Step 2: Cleaning up previous environment...
# Step 3: Starting test environment...
# Step 4: Waiting for services...
# Step 5: Running tests...
# [Test output]
# Result: ALL TESTS PASSED ✓
```

### Method 2: Manual Test Execution

```bash
# Start test environment
docker-compose -f docker-compose.test.yml up -d

# Wait for services to be ready (check health)
docker ps

# Run all tests
docker exec radius-client-test /tests/run-all-tests.sh

# Run individual test
docker exec radius-client-test /tests/test-auth-success.sh

# Stop test environment
docker-compose -f docker-compose.test.yml down -v
```

### Method 3: Interactive Testing

```bash
# Start environment and keep it running
./test.sh --keep-running

# Access test client container
docker exec -it radius-client-test bash

# Now you can run tests manually:
cd /tests
./test-auth-success.sh
./test-accounting.sh
./test-webapp.sh

# Or test RADIUS manually:
echo "User-Name=testuser1@example.com,User-Password=password123" | \
    radclient freeradius-test:1812 auth testing123

# Query database directly:
mysql -h mysql-test -u radius -pradiuspass radius -e "SELECT * FROM radpostauth ORDER BY authdate DESC LIMIT 5;"

# Test web application:
curl http://webapp-test/

# Exit and clean up when done
exit
docker-compose -f docker-compose.test.yml down -v
```

---

## Test Suite Details

### RADIUS Authentication Tests

#### Test 1: Successful Authentication
**File:** `tests/test-auth-success.sh`

**Purpose:** Verify that valid credentials result in Access-Accept and proper database logging.

**Test Steps:**
1. Send Access-Request with valid credentials
   - User: `testuser1@example.com`
   - Password: `password123`
2. Verify Access-Accept response
3. Check database for `reply='Access-Accept'`
4. Verify `error_type` is NULL (success)

**Expected Results:**
- RADIUS responds with Access-Accept
- Database entry created with correct reply
- No error_type set

#### Test 2: Wrong Password
**File:** `tests/test-auth-password-wrong.sh`

**Purpose:** Verify that wrong password results in Access-Reject with `error_type='password_wrong'`.

**Test Steps:**
1. Send Access-Request with wrong password
   - User: `testuser1@example.com`
   - Password: `wrongpassword`
2. Verify Access-Reject response
3. Check database for `error_type='password_wrong'`
4. Verify reply_message contains password error

**Expected Results:**
- RADIUS responds with Access-Reject
- Database entry has `error_type='password_wrong'`
- Reply message contains appropriate error text

#### Test 3: User Not Found
**File:** `tests/test-auth-user-not-found.sh`

**Purpose:** Verify that non-existent user results in Access-Reject with `error_type='user_not_found'`.

**Test Steps:**
1. Send Access-Request for non-existent user
   - User: `nonexistent_[timestamp]@example.com`
   - Password: `anypassword`
2. Verify Access-Reject response
3. Check database for appropriate error_type
4. Verify reply_message

**Expected Results:**
- RADIUS responds with Access-Reject
- Database entry logged
- Appropriate error type set

### RADIUS Accounting Tests

#### Test 4: Accounting Flow
**File:** `tests/test-accounting.sh`

**Purpose:** Verify complete accounting flow (Start → Interim → Stop).

**Test Steps:**
1. **Accounting-Start:**
   - Send Accounting-Start packet
   - Verify Accounting-Response
   - Check database for new radacct entry with NULL acctstoptime

2. **Accounting-Interim-Update:**
   - Send Interim-Update packet with session time and data
   - Verify Accounting-Response
   - Check database for updated session time and octets

3. **Accounting-Stop:**
   - Send Accounting-Stop packet with terminate cause
   - Verify Accounting-Response
   - Check database for acctstoptime set
   - Verify terminate cause stored

**Expected Results:**
- All accounting packets accepted
- Database records created and updated correctly
- Session lifecycle tracked properly

### Web Application Tests

#### Test 5: Web Application Functionality
**File:** `tests/test-webapp.sh`

**Purpose:** Verify web dashboard functionality end-to-end.

**Test Steps:**
1. **Server Running:** Check HTTP response
2. **Login Page:** Verify login page loads
3. **Authentication:** Login with admin credentials
4. **Session Management:** Verify session cookie set
5. **Dashboard Access:** Access authenticated pages
6. **Online Users:** Verify page loads
7. **Authentication Log:** Verify page loads
8. **CSV Export:** Test export functionality
9. **Reports Page:** Verify reports accessible
10. **Settings Page:** Verify superadmin pages load
11. **Logout:** Verify logout and session termination

**Expected Results:**
- All pages load successfully
- Authentication works correctly
- Exports function properly
- Session management works

---

## Writing New Tests

### Test Script Template

```bash
#!/bin/bash
# =====================================================
# Test: [Test Name]
# =====================================================
# [Test Description]
# =====================================================

set -e

TEST_NAME="[Test Name]"
PASSED=0
FAILED=0

echo "========================================"
echo "Test: $TEST_NAME"
echo "========================================"

# Test configuration
RADIUS_SERVER="${RADIUS_SERVER:-freeradius-test}"
RADIUS_SECRET="${RADIUS_SECRET:-testing123}"

# Test implementation
echo "Test step 1..."
# [Your test code here]

if [ condition ]; then
    echo "✓ PASS: [What passed]"
    ((PASSED++))
else
    echo "✗ FAIL: [What failed]"
    ((FAILED++))
fi

# Print summary
echo ""
echo "========================================"
echo "Test Summary: $TEST_NAME"
echo "  Passed: $PASSED"
echo "  Failed: $FAILED"
echo "========================================"
echo ""

# Exit with appropriate code
if [ $FAILED -gt 0 ]; then
    exit 1
else
    exit 0
fi
```

### Adding Tests to Master Runner

Edit `tests/run-all-tests.sh` and add your test:

```bash
# Add your test
run_test "/tests/your-new-test.sh" "Your Test Name"
```

### Test Best Practices

1. **Use Unique Identifiers:** Generate unique test data (timestamps, UUIDs)
2. **Clean Up:** Remove test data after completion (if needed)
3. **Descriptive Output:** Clear pass/fail messages
4. **Exit Codes:** 0 for success, 1 for failure
5. **Database Verification:** Always verify data was stored correctly
6. **Error Messages:** Include actual vs expected values in failures

---

## Continuous Integration

### GitHub Actions Example

Create `.github/workflows/test.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v2

    - name: Run Tests
      run: |
        chmod +x test.sh
        ./test.sh

    - name: Archive Test Logs
      if: always()
      uses: actions/upload-artifact@v2
      with:
        name: test-logs
        path: logs/
```

---

## Manual Testing

### Testing RADIUS Authentication Manually

```bash
# Successful authentication
echo "User-Name=testuser1@example.com,User-Password=password123" | \
    radclient -x localhost:1812 auth testing123

# Failed authentication (wrong password)
echo "User-Name=testuser1@example.com,User-Password=wrongpass" | \
    radclient -x localhost:1812 auth testing123

# Non-existent user
echo "User-Name=unknown@example.com,User-Password=anypass" | \
    radclient -x localhost:1812 auth testing123
```

### Testing Accounting Manually

```bash
# Accounting Start
echo "User-Name=testuser@example.com,Acct-Status-Type=Start,Acct-Session-Id=test123,NAS-IP-Address=10.10.10.1" | \
    radclient -x localhost:1813 acct testing123

# Accounting Stop
echo "User-Name=testuser@example.com,Acct-Status-Type=Stop,Acct-Session-Id=test123,Acct-Session-Time=3600,Acct-Terminate-Cause=User-Request" | \
    radclient -x localhost:1813 acct testing123
```

### Testing Web Dashboard Manually

```bash
# Test login page
curl http://localhost:8080/

# Test login (saves session cookie)
curl -c cookies.txt -X POST http://localhost:8080/?page=login \
    -d "username=admin" \
    -d "password=password"

# Test authenticated page
curl -b cookies.txt http://localhost:8080/?page=dashboard

# Test CSV export
curl -b cookies.txt -O http://localhost:8080/?page=online-users&export=csv
```

---

## Troubleshooting

### Tests Fail to Start

**Problem:** `docker-compose.test.yml` won't start

**Solutions:**
1. Check Docker is running: `docker ps`
2. Check port conflicts: `netstat -an | grep 3307`
3. Remove old containers: `docker-compose -f docker-compose.test.yml down -v`

### Database Connection Issues

**Problem:** Tests report database connection errors

**Solutions:**
1. Check MySQL is healthy: `docker ps`
2. Wait longer for MySQL to initialize (takes 30-60 seconds)
3. Check MySQL logs: `docker-compose -f docker-compose.test.yml logs mysql-test`

### RADIUS Server Not Responding

**Problem:** Authentication tests timeout

**Solutions:**
1. Check FreeRADIUS is running: `docker ps | grep freeradius-test`
2. Check FreeRADIUS logs: `docker-compose -f docker-compose.test.yml logs freeradius-test`
3. Verify UDP ports: FreeRADIUS uses UDP, not TCP

### Web Application 404 Errors

**Problem:** Web tests fail with 404

**Solutions:**
1. Check webapp container: `docker ps | grep webapp-test`
2. Check Apache is running: `docker exec webapp-test ps aux | grep apache`
3. Check file permissions: `docker exec webapp-test ls -la /var/www/html/radius-gui/public/`

### Test Data Issues

**Problem:** Tests fail because test data is missing

**Solutions:**
1. Verify test data was loaded:
   ```bash
   docker exec radius-client-test mysql -h mysql-test -u radius -pradiuspass radius -e "SELECT COUNT(*) FROM radcheck;"
   ```
2. Manually load test data:
   ```bash
   docker exec -i mysql-test mysql -u radius -pradiuspass radius < tests/setup-test-data.sql
   ```

---

## Performance Benchmarking

### RADIUS Performance Test

```bash
# Install radperf (if not already installed)
apt-get install freeradius-utils

# Run performance test (1000 requests)
radperf -f requests.txt -p 1812 -s testing123 -n 1000 localhost

# Monitor FreeRADIUS performance
docker stats freeradius-test
```

### Web Application Load Testing

```bash
# Install Apache Bench
apt-get install apache2-utils

# Run load test (100 requests, 10 concurrent)
ab -n 100 -c 10 http://localhost:8080/
```

---

## Test Reports

### Generating Test Coverage Report

```bash
# Run tests with coverage
./test.sh --keep-running

# Generate report
docker exec radius-client-test /tests/run-all-tests.sh | tee test-report.txt

# View summary
grep -E "PASS|FAIL" test-report.txt
```

---

## Resources

- **FreeRADIUS Testing:** https://wiki.freeradius.org/guide/HOWTO
- **Docker Testing:** https://docs.docker.com/compose/
- **Bash Testing:** https://github.com/bats-core/bats-core

---

**Testing Version:** 1.0
**Last Updated:** December 2024
**Maintained By:** FreeRADIUS Google LDAP Dashboard Team
