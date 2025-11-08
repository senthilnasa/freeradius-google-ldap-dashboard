# FreeRadius + Google Secure LDAP + MySQL + Simple Dashboard

With all respects to major designer jongoldsz

This is a comprehensive Docker-based solution that provides a production-ready FreeRADIUS server with Google Secure LDAP authentication, MySQL database integration, and a web-based admin dashboard. This setup is tested with UniFi and Aerohive successfully and includes VLAN support for network segmentation.

Note: At time of writing this guide, you will need G Suite Enterprise, G Suite Enterprise for Education, G Suite Education, or Cloud Identity Premium licensing to use Google's Secure LDAP service. If you don't have this licensing, you will not be able to get authentication working by following this guide.

# FreeRADIUS + Google LDAP + MySQL + Dashboard

🚀 **Complete authentication stack with FreeRADIUS, Google Secure LDAP, MySQL database, and web admin dashboard with VLAN support.**

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://www.docker.com/)
[![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-3.0.23-green)](https://freeradius.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange?logo=mysql)](https://www.mysql.com/)
[![Google LDAP](https://img.shields.io/badge/Google_LDAP-Secure-4285f4?logo=google)](https://cloud.google.com/identity)
[![Dashboard](https://img.shields.io/badge/Dashboard-Web_Admin-purple)](http://localhost:8080)
[![License](https://img.shields.io/badge/License-MIT-yellow)](LICENSE)

## ✨ Features

- **🔐 Google Secure LDAP Integration** - Direct authentication with Google Workspace
- **🏢 Multi-domain Support** - Support for multiple organizational domains (senthilnasa.ac.in, senthilnasa.com, senthilnasa.me)
- **🌐 VLAN Network Segmentation** - Automatic VLAN assignment based on domain and user type
- **📊 MySQL Database Integration** - Complete SQL logging and session management
- **💻 Web Admin Dashboard** - Real-time monitoring and user management interface
- **🐳 Docker Containerization** - Easy deployment with Docker Compose
- **⚙️ Environment Configuration** - Single .env file for all settings (no hardcoded values)
- **🔧 Management Tools** - Password reset utilities and admin controls
- **📈 Performance Optimization** - Configurable connection pools and caching
- **🐛 Debug Controls** - SQL tracing and comprehensive logging
- **🔒 Security Features** - Bcrypt password hashing, session management, secure cookies

## 🏗️ Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   RADIUS Client │ -> │   FreeRADIUS     │ -> │ Google Secure   │
│  (WiFi/Network) │    │     Server       │    │     LDAP        │
│   + VLAN Tags   │    │  + VLAN Logic    │    │   Workspace     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │  MySQL Database  │
                       │  + SQL Logging   │
                       │  + Session Mgmt  │
                       └──────────────────┘
                                │
                    ┌───────────┼───────────┐
                    ▼           ▼           ▼
           ┌─────────────┐ ┌──────────┐ ┌─────────────┐
           │    Admin    │ │  MySQL   │ │  Container  │
           │  Dashboard  │ │ phpMyAdmin│ │   Health    │
           │   :8080     │ │   :8081   │ │   Checks    │
           └─────────────┘ └──────────┘ └─────────────┘
```

## 🚀 Quick Start

### Prerequisites

- Docker and Docker Compose
- Google Workspace admin access
- Domain certificates (if using TLS)

### 1. Clone Repository

```bash
git clone https://github.com/senthilnasa/freeradius-google-ldap-dashboard.git
cd freeradius-google-ldap-dashboard
```

### 2. Setup Google LDAP Certificates

Place your Google LDAP certificates in the `certs/` directory:

```bash
# Create certs directory if it doesn't exist
mkdir -p certs

# Copy your Google LDAP certificates (downloaded from Google Admin Console)
# Rename them to the required names:
cp /path/to/your/google-ldap-cert.crt certs/ldap-client.crt
cp /path/to/your/google-ldap-key.key certs/ldap-client.key

# Set proper permissions
chmod 644 certs/ldap-client.crt
chmod 600 certs/ldap-client.key
```

**📋 Required Certificate Files:**
- `certs/ldap-client.crt` - Google LDAP client certificate
- `certs/ldap-client.key` - Google LDAP client private key

> **💡 How to get Google LDAP certificates:**
> 1. Go to [Google Admin Console](https://admin.google.com)
> 2. Navigate to **Security** → **API controls** → **Domain-wide delegation**
> 3. Follow [Google's LDAP setup guide](https://support.google.com/a/answer/9048434)
> 4. Download the certificate bundle and extract the `.crt` and `.key` files

### 3. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and configure your settings:

```bash
nano .env
```

**Required Configuration:**
- `LDAP_SERVER` - Your Google LDAP server
- `LDAP_IDENTITY` - LDAP bind username  
- `LDAP_PASSWORD` - LDAP bind password
- `LDAP_BASE_DN` - LDAP base DN
- `DB_ROOT_PASSWORD` - MySQL root password
- `DB_PASSWORD` - Database password
- `ADMIN_PASSWORD` - Dashboard admin password

### 4. Deploy Services

```bash
docker-compose up -d
```

### 5. Verify Deployment

Check all services are running:

```bash
docker-compose ps
```

### 6. Access Dashboard

- **Admin Dashboard**: http://localhost:8080

**Default Login:**
- Username: `admin`
- Password: `admin123` (change on first login)

### 7. Optional: Enable phpMyAdmin for Local Testing

⚠️ **For Development/Testing Only - Never use in production!**

If you need database access for local testing, debugging, or development:

```bash
# Start phpMyAdmin for local testing
docker-compose -f docker-phpmyadmin.yml up -d

# Access phpMyAdmin
# URL: http://localhost:8081
# Username: radius (your DB_USER from .env)
# Password: your DB_PASSWORD from .env

# Stop when testing is complete
docker-compose -f docker-phpmyadmin.yml down
```

**When to use phpMyAdmin for testing:**
- 🔍 **Debug authentication issues** - Check `radpostauth` table for failed logins
- 📊 **Monitor active sessions** - View `radacct` table for current connections
- 🛠️ **Test user management** - Create/modify test users and groups
- 📈 **Performance testing** - Analyze SQL query performance
- 💾 **Backup test data** - Export/import test database content
- 🔧 **Schema exploration** - Understand database structure during development

**Security Notes for Testing:**
- ✅ Uses limited database user (not root)
- ✅ Only connects to configured MySQL server
- ✅ Separate from production stack
- ✅ Easy start/stop for testing sessions
- ❌ **Never deploy to production environments**

## 🔧 Configuration

### Environment Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| **LDAP Configuration** | | | |
| `LDAP_SERVER` | Google LDAP server | `ldaps://ldap.google.com:636` | Yes |
| `LDAP_IDENTITY` | LDAP bind user | - | Yes |
| `LDAP_PASSWORD` | LDAP bind password | - | Yes |
| `LDAP_BASE_DN` | LDAP base DN | - | Yes |
| `LDAP_BIND_DN` | LDAP bind DN | - | Yes |
| **Database Configuration** | | | |
| `DB_HOST` | MySQL server hostname | `mysql` | No |
| `DB_PORT` | MySQL server port | `3306` | No |
| `DB_NAME` | Database name | `radius` | No |
| `DB_USER` | Database username | `radius` | No |
| `DB_PASSWORD` | Database password | `radiuspass` | Yes |
| `DB_ROOT_PASSWORD` | MySQL root password | `rootpass` | Yes |
| `DB_MAX_CONNECTIONS` | SQL connection pool size | `20` | No |
| **RADIUS Configuration** | | | |
| `RADIUS_SECRET` | RADIUS shared secret | `testing123` | Yes |
| `RADIUS_AUTH_PORT` | Authentication port | `1812` | No |
| `RADIUS_ACCT_PORT` | Accounting port | `1813` | No |
| **Dashboard Configuration** | | | |
| `ADMIN_USERNAME` | Dashboard username | `admin` | No |
| `ADMIN_PASSWORD` | Dashboard password | `admin123` | Yes |
- `ADMIN_EMAIL` | Admin email address | `admin@senthilnasa.ac.in` | No |
| `DASHBOARD_PORT` | Dashboard web port | `8080` | No |
| **Security & Performance** | | | |
| `ENABLE_SQL_TRACE` | Enable SQL debugging | `false` | No |
| `FORCE_PASSWORD_CHANGE` | Force password change on first login | `true` | No |
| `SESSION_COOKIE_SECURE` | Use secure cookies | `true` | No |
| `BCRYPT_ROUNDS` | Password hashing rounds | `12` | No |

### Domain Configuration

The system supports three domains by default with VLAN segmentation:
- **senthilnasa.ac.in** (Staff) - VLAN 10
- **senthilnasa.com** (Student) - VLAN 20  
- **senthilnasa.me** (Guest) - VLAN 30

Configure domains in `.env`:
```bash
DOMAIN_CONFIG=[{"domain":"senthilnasa.ac.in","Type":"Staff","VLAN":"10"},{"domain":"senthilnasa.com","Type":"Student","VLAN":"20"},{"domain":"senthilnasa.me","Type":"Guest","VLAN":"30"}]

# Legacy format (for compatibility)
DOMAIN_1=senthilnasa.ac.in
DOMAIN_2=senthilnasa.com
DOMAIN_3=senthilnasa.me
STAFF_DOMAINS=senthilnasa.ac.in
STUDENT_DOMAINS=senthilnasa.com
GUEST_DOMAINS=senthilnasa.me
```

## 🔐 Security

### Production Security Checklist

**⚠️ CRITICAL: Before deploying to production:**

1. **Remove phpMyAdmin** (included for development only)
   ```bash
   # phpMyAdmin is NOT production-safe as configured
   # Remove or secure properly with HTTPS, IP restrictions, and limited user access
   ```

2. **Secure Database Access**
   ```bash
   # Use direct MySQL client instead of phpMyAdmin
   docker exec -it radius-mysql mysql -u radius -p
   
   # Or use SSH tunnel for remote access
   ssh -L 3306:localhost:3306 user@your-server
   ```

3. **SSL/TLS Configuration**
   - Place certificates in `certs/` directory
   - `ldap-client.crt` - LDAP client certificate
   - `ldap-client.key` - LDAP client private key
   - Enable HTTPS for dashboard in production

4. **Network Security**
   - Restrict `RADIUS_CLIENT_IPADDR` to specific network ranges
   - Use firewall rules to limit access to ports 1812/1813
   - Enable VPN access for administrative interfaces

5. **Password Security**
   - Change all default passwords before deployment
   - Use strong, unique passwords (minimum 16 characters)
   - Enable 2FA where possible
   - Passwords are hashed using bcrypt (12 rounds)
   - Force password change on first login
   - Session timeout protection

6. **Environment Variables**
   ```bash
   # Set secure production values
   SESSION_COOKIE_SECURE=true
   FORCE_PASSWORD_CHANGE=true
   BCRYPT_ROUNDS=12
   ```

## 📊 Monitoring

### Admin Dashboard Features

- **Real-time Statistics**: Online users, session counts, success rates
- **Domain Analytics**: Per-domain authentication statistics
- **Active Sessions**: View and manage current user sessions
- **Authentication Logs**: Recent authentication attempts
- **User Management**: Password reset and account management

### Database Tables

- `radpostauth` - Authentication logs
- `radacct` - Accounting/session data
- `admin_users` - Dashboard user accounts

## 🛠️ Management

### Optional: phpMyAdmin for Development

If you need a web interface for database management during development (e.g., viewing logs, managing users, debugging SQL queries):

```bash
# Start phpMyAdmin (development only)
docker-compose -f docker-phpmyadmin.yml up -d

# Access at: http://localhost:8081
# Username: radius (from .env DB_USER)
# Password: Your DB_PASSWORD from .env

# Stop when done
docker-compose -f docker-phpmyadmin.yml down
```

**Common development use cases:**
- Viewing authentication logs in `radpostauth` table
- Checking active sessions in `radacct` table
- Managing user accounts and groups
- Debugging SQL queries and performance
- Backing up/restoring test data

⚠️ **IMPORTANT**: This is for development only - never use in production!

### Reset Admin Password

#### Linux/Mac:
```bash
./reset-password.sh
```

#### Windows:
```batch
reset-password.bat
```

### View Logs

```bash
# FreeRADIUS logs
docker logs freeradius-google-ldap

# MySQL logs  
docker logs radius-mysql

# Dashboard logs
docker logs radius-dashboard
```

### Database Access

Access MySQL directly:
```bash
docker exec -it radius-mysql mysql -u radius -p radius
```

## 🧪 Testing

### Test Authentication

```bash
# Test authentication with different domains and VLANs
radtest user@senthilnasa.ac.in password localhost 1812 testing123

# Test with different domains
radtest staff@senthilnasa.ac.in password localhost 1812 testing123      # VLAN 10
radtest student@senthilnasa.com password localhost 1812 testing123      # VLAN 20
radtest guest@senthilnasa.me password localhost 1812 testing123         # VLAN 30
```

### Verify SQL Logging

**Method 1: Direct MySQL (Production-safe)**
```bash
docker exec -it radius-mysql mysql -u radius -p radius
```

**Method 2: phpMyAdmin (Local testing only)**
```bash
# Start phpMyAdmin for testing
docker-compose -f docker-phpmyadmin.yml up -d
# Access: http://localhost:8081
```

**Test Queries:**
```sql
-- Check authentication logs
SELECT * FROM radpostauth ORDER BY authdate DESC LIMIT 10;

-- Check active sessions
SELECT * FROM radacct WHERE acctstoptime IS NULL;

-- View VLAN assignments in recent authentications
SELECT username, reply, authdate, 
       CASE 
         WHEN username LIKE '%@senthilnasa.ac.in' THEN 'VLAN 10 (Staff)'
         WHEN username LIKE '%@senthilnasa.com' THEN 'VLAN 20 (Student)'
         WHEN username LIKE '%@senthilnasa.me' THEN 'VLAN 30 (Guest)'
       END as expected_vlan
FROM radpostauth ORDER BY authdate DESC LIMIT 10;
```

**Stop phpMyAdmin after testing:**
```bash
docker-compose -f docker-phpmyadmin.yml down
```

## 🔧 Troubleshooting

### Common Issues

1. **LDAP Connection Failed**
   - Verify LDAP credentials in `.env`
   - Check network connectivity to Google LDAP
   - Validate certificates in `certs/` directory

2. **Certificate Issues**
   - **Missing certificates**: Ensure `ldap-client.crt` and `ldap-client.key` exist in `certs/` directory
   - **Wrong file names**: Files must be named exactly `ldap-client.crt` and `ldap-client.key`
   - **Permission issues**: Run `chmod 644 certs/ldap-client.crt && chmod 600 certs/ldap-client.key`
   - **Invalid certificates**: Re-download from Google Admin Console if expired
   - **Path issues**: Verify container can access `/certs` volume mount

3. **Database Connection Error**
   - Ensure MySQL container is running
   - Verify database credentials in `.env`
   - Check Docker network connectivity

4. **Authentication Failures**
   - Check user exists in Google Directory
   - Verify domain configuration
   - Review FreeRADIUS logs

### Database Debugging with phpMyAdmin (Local Testing)

For troubleshooting authentication and database issues during development:

```bash
# Start phpMyAdmin for debugging
docker-compose -f docker-phpmyadmin.yml up -d
# Access: http://localhost:8081 (username: radius, password: from .env)

# Check authentication logs
SELECT * FROM radpostauth ORDER BY authdate DESC LIMIT 20;

# View active sessions
SELECT * FROM radacct WHERE acctstoptime IS NULL;

# Check failed authentications
SELECT * FROM radpostauth WHERE reply = 'Access-Reject' ORDER BY authdate DESC LIMIT 10;

# Stop when debugging is complete
docker-compose -f docker-phpmyadmin.yml down
```

**Common SQL Queries for Debugging:**

```sql
-- Recent authentication attempts
SELECT username, reply, authdate FROM radpostauth ORDER BY authdate DESC LIMIT 10;

-- Active user sessions with VLAN info
SELECT username, nasipaddress, acctstarttime, framedipaddress 
FROM radacct WHERE acctstoptime IS NULL;

-- Authentication success rate by domain
SELECT 
    SUBSTRING_INDEX(username, '@', -1) as domain,
    COUNT(*) as total_attempts,
    SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) as successful,
    ROUND(SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
FROM radpostauth 
WHERE authdate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY domain;
```

### Debug Mode

Enable debug logging:
```bash
# Set in .env
LOG_LEVEL=debug
ENABLE_SQL_TRACE=true
ENABLE_LDAP_DEBUG=true

# Restart containers
docker-compose restart
```

### Log Locations

- FreeRADIUS: `/var/log/freeradius/`
- MySQL: Container logs via `docker logs`
- Dashboard: Apache error/access logs

## � Production-Ready Features

### 🔐 Security
- **Bcrypt password hashing** (12 rounds)
- **JWT session management** with timeout protection
- **Forced password change** on first login
- **Secure cookie settings** for HTTPS environments
- **Environment variable configuration** (no hardcoded secrets)

### 📊 Monitoring & Management
- **Real-time dashboard** with authentication statistics
- **Domain-based analytics** for multi-domain support
- **Active session management** with disconnect capability
- **SQL logging** with MySQL integration
- **Admin interface** with user management

### 🏗️ Infrastructure
- **Docker containerization** with health checks
- **MySQL 8.0** with performance optimization
- **Network isolation** with custom Docker network
- **Volume management** for persistent data
- **Comprehensive logging** system

### 🔧 Dynamic Configuration
- **Environment-based configuration** (40+ variables)
- **Multi-domain support** (krea.edu.in, krea.ac.in, ifmr.ac.in)
- **Google LDAP integration** with TLS
- **Role-based authentication** (Staff/Student)
- **Performance tuning** options

## �📈 Performance Tuning

### Production Settings

Configure in `.env` for production:

```bash
# Connection pools
RADIUS_MAX_CONNECTIONS=50
LDAP_MAX_CONNECTIONS=10
DB_MAX_CONNECTIONS=20

# Performance
MYSQL_MAX_CONNECTIONS=200
MYSQL_INNODB_BUFFER_POOL_SIZE=256M

# Security
SESSION_COOKIE_SECURE=true
BCRYPT_ROUNDS=12
ENABLE_SQL_TRACE=false
```

### Scaling

For high-load environments:
- Use external MySQL cluster
- Deploy multiple FreeRADIUS instances
- Implement load balancing
- Use Redis for session storage

## 🔧 SQL Configuration Details

The system uses dynamic SQL configuration with environment variables:

### Database Connection
```toml
server = "${ENV_DB_HOST}"
port = ${ENV_DB_PORT}
login = "${ENV_DB_USER}"
password = "${ENV_DB_PASSWORD}"
radius_db = "${ENV_DB_NAME}"
sqltrace = ENV_ENABLE_SQL_TRACE
num_sql_socks = ENV_DB_MAX_CONNECTIONS
```

### Environment Variable Replacement
The `init.sh` script automatically replaces placeholders with actual values:
- Runtime configuration updates
- Default values for missing variables
- Support for debugging and performance tuning

## 📋 Maintenance

### Backup

```bash
# Backup database
docker exec radius-mysql mysqldump -u root -p radius > backup_$(date +%Y%m%d).sql

# Backup configuration
tar -czf config_backup_$(date +%Y%m%d).tar.gz .env configs/ certs/
```

### Updates

```bash
# Update containers
docker-compose pull
docker-compose up -d
```

## 🎉 Deployment Status

### ✅ All Tasks Completed Successfully

This repository has been fully optimized and is production-ready:

1. **✅ Repository Cleanup** - Removed all test files and deprecated documentation
2. **✅ Environment Variable System** - 40+ configurable variables
3. **✅ Docker Compose Enhancement** - Health checks and proper networking
4. **✅ Dashboard Authentication** - Secure login with password management
5. **✅ Password Reset Tools** - Scripts for Linux/Mac and Windows
6. **✅ SQL Configuration** - Dynamic database configuration
7. **✅ Production Deployment** - Validated and tested system

### 🎯 Ready for Production

The system includes:
- ✅ Clean codebase (no unwanted files)
- ✅ Environment variable configuration
- ✅ Secure authentication system
- ✅ Easy deployment process
- ✅ Comprehensive documentation
- ✅ Password management tools
- ✅ Performance optimizations

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

- **Issues**: [GitHub Issues](https://github.com/senthilnasa/freeradius-google-ldap-dashboard/issues)
- **Documentation**: This README contains all necessary information
- **Email**: support@senthilnasa.ac.in

## 🔗 Links

- [FreeRADIUS Documentation](https://freeradius.org/documentation/)
- [Google Cloud Directory](https://cloud.google.com/identity/docs/how-to/setup-ldap)
- [Docker Compose Reference](https://docs.docker.com/compose/)

---

**🌟 The system is ready for production deployment!**
**⭐ If this project helped you, please give it a star on GitHub!**  

## Supported Domains & Roles

| Domain | Role | VLAN | Session Timeout | Idle Timeout | Class |
|--------|------|------|-----------------|--------------|-------|
| `senthilnasa.ac.in` | STAFF | 10 | 12 hours | 1 hour | STAFF |
| `senthilnasa.com` | STUDENT | 20 | 8 hours | 30 minutes | STUDENT |
| `senthilnasa.me` | GUEST | 30 | 4 hours | 15 minutes | GUEST |

## Prerequisites

- Google Workspace with G Suite Enterprise, G Suite Enterprise for Education, G Suite Education, or Cloud Identity Premium
- Docker and Docker Compose
- Google Secure LDAP certificates

## Quick Start

### 1. Configure Google Secure LDAP

Follow Google's guide: https://support.google.com/a/answer/9048434

1. Enable Secure LDAP in your Google Admin Console
2. Download the LDAP client certificates
3. Generate access credentials (username/password)

### 2. Setup Environment

1. Place your Google LDAP certificates in the `certs/` directory:
   - `ldap-client.crt` 
   - `ldap-client.key`

2. Configure `.env`:
   ```env
   # Domain Configuration with VLAN Support
   DOMAIN_CONFIG=[{"domain":"senthilnasa.ac.in","Type":"Staff","VLAN":"10"},{"domain":"senthilnasa.com","Type":"Student","VLAN":"20"},{"domain":"senthilnasa.me","Type":"Guest","VLAN":"30"}]
   
   # Network Configuration
   ACCESS_ALLOWED_CIDR=10.10.0.0/16
   SHARED_SECRET=your_shared_secret
   
   # Google LDAP Configuration
   GOOGLE_LDAP_USERNAME=your_google_username
   GOOGLE_LDAP_PASSWORD=your_google_password
   GOOGLE_LDAPTLS_CERT=/etc/freeradius/certs/ldap-client.crt
   GOOGLE_LDAPTLS_KEY=/etc/freeradius/certs/ldap-client.key
   ```

### 3. Deploy

```bash
# Start the container
docker-compose up -d

# Check logs
docker-compose logs -f

# Test authentication
docker exec -it freeradius-ldap-freeradius-ldap-1 radtest 'user@senthilnasa.ac.in' 'password' localhost 0 testing123
```

## Configuration Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `ACCESS_ALLOWED_CIDR` | IP range allowed to access RADIUS server | `10.10.0.0/16` |
| `SHARED_SECRET` | RADIUS shared secret | `testing123` |
| `BASE_DOMAIN` | Primary domain name | `senthilnasa` |
| `DOMAIN_EXTENSION` | Domain extension | `ac.in` |
| `GOOGLE_LDAP_USERNAME` | Google LDAP username | `CaringStro` |
| `GOOGLE_LDAP_PASSWORD` | Google LDAP password | `secure_password` |

## File Structure

```
├── certs/                  # Google LDAP certificates
│   ├── ldap-client.crt
│   └── ldap-client.key
├── configs/                # FreeRADIUS configuration files
│   ├── clients.conf       # RADIUS clients
│   ├── default            # Main virtual server (multi-domain logic)
│   ├── eap                # EAP configuration
│   ├── inner-tunnel       # Inner tunnel configuration
│   ├── ldap               # LDAP module configuration
│   └── proxy.conf         # Proxy configuration
├── .env                   # Environment configuration file
├── .env.example           # Environment configuration template
├── docker-compose.yml     # Main production-ready setup
├── docker-phpmyadmin.yml  # Optional phpMyAdmin for development
├── Dockerfile            # Container build instructions
└── init.sh              # Container initialization script
```

## Authentication Response

### Successful Authentication Examples:

**Staff Authentication (senthilnasa.ac.in):**
```
Access-Accept
Reply-Message = "Authenticated as staff"
Class = STAFF
Session-Timeout = 43200
Idle-Timeout = 3600
Tunnel-Type := VLAN
Tunnel-Medium-Type := IEEE-802
Tunnel-Private-Group-Id := 10
```

**Student Authentication (senthilnasa.com):**
```
Access-Accept
Reply-Message = "Authenticated as student"
Class = STUDENT
Session-Timeout = 28800
Idle-Timeout = 1800
Tunnel-Type := VLAN
Tunnel-Medium-Type := IEEE-802
Tunnel-Private-Group-Id := 20
```

**Guest Authentication (senthilnasa.me):**
```
Access-Accept
Reply-Message = "Authenticated as guest"
Class = GUEST
Session-Timeout = 14400
Idle-Timeout = 900
Tunnel-Type := VLAN
Tunnel-Medium-Type := IEEE-802
Tunnel-Private-Group-Id := 30
```

## Troubleshooting

### Enable Debug Mode
Uncomment the debug line in `docker-compose.yml`:
```yaml
command: freeradius -X
```

### Check Container Logs
```bash
docker logs freeradius-ldap-freeradius-ldap-1
```

### Test Authentication
```bash
# Test from inside container with different domains
docker exec -it freeradius-ldap-freeradius-ldap-1 radtest 'user@senthilnasa.ac.in' 'password' localhost 0 testing123
docker exec -it freeradius-ldap-freeradius-ldap-1 radtest 'user@senthilnasa.com' 'password' localhost 0 testing123
docker exec -it freeradius-ldap-freeradius-ldap-1 radtest 'user@senthilnasa.me' 'password' localhost 0 testing123
```

## 🏭 Production Deployment

### ⚠️ Security Warnings

**NEVER use the default configuration in production:**

1. **phpMyAdmin Removed**: The current Docker Compose removes phpMyAdmin for security
2. **Database Access**: Use secure methods for database administration
3. **Change All Defaults**: Update passwords, secrets, and certificates

### Production Checklist

```bash
# 1. Remove development services
# phpMyAdmin has been removed from docker-compose.yml

# 2. Secure database access
docker exec -it radius-mysql mysql -u radius -p

# 3. Use environment-specific configuration
cp .env.example .env.production
# Edit .env.production with production values

# 4. Deploy with production settings
docker-compose --env-file .env.production up -d
```

### Secure Database Administration

**Option 1: Direct MySQL Client**
```bash
docker exec -it radius-mysql mysql -u radius -p
```

**Option 2: SSH Tunnel**
```bash
ssh -L 3306:localhost:3306 user@production-server
mysql -h localhost -u radius -p
```

**Option 3: Development phpMyAdmin (for development only)**
```bash
# Start phpMyAdmin for development (optional)
docker-compose -f docker-phpmyadmin.yml up -d

# Access at http://localhost:8081
# Login with DB_USER and DB_PASSWORD from .env

# Stop when done
docker-compose -f docker-phpmyadmin.yml down
```
⚠️ **Never use this in production - development only**

## Support

For issues and questions:
1. Check container logs for authentication errors
2. Verify Google LDAP credentials and certificates
3. Ensure network connectivity between access points and RADIUS server

---

**Version**: Multi-Domain Production Ready  
**Last Updated**: November 2025

## Configure your Google secure LDAP environment
Follow steps 1-3 in Google's guide. https://support.google.com/a/answer/9048434?hl=en&ref_topic=9173976
When you download the certificates archive, extract the files and remember this location, we need to give the certificates to the docker container.
Be sure to click on the "Generate access credentials" link in step 3, to generate values for identity and password required for the next step. This password is only shown once, so be careful not to close the window yet!

## Configuration
In order to successfully run the container, following environment variables are passed on to the container. If not all parameters are provided, the container will fail. Copy the `.env.example` file to `.env` and make the necessary changes.

- `ACCESS_ALLOWED_CIDR` : The CIDR (e.g. 192.168.1.1/24) which is allowed access to the freeradius server. This will probably be the IP range of your Wifi Access Points.
- `BASE_DOMAIN`: The first part of your domain name used in the Google suite: `senthilnasa` if your domain name is `senthilnasa.ac.in`
- `DOMAIN_EXTENSTION`: The last part of your domain name used in the Google suite: `ac.in` if your domain name is `senthilnasa.ac.in`
- `GOOGLE_LDAP_USERNAME`: The username Google gave you when configuring the Client credentials
- `GOOGLE_LDAP_PASSWORD`: The password Google gave you when configuring the Client credentials 
- `SHARED_SECRET`: The shared secret needed to be able to talk to the FreeRADIUS server

In order to run the container also needs the directory where the certificates you received (and extracted) from Google are located. These files need to be mounted to the `/certs` folder and renamed to `ldap-client.crt` and `ldap-client.key` respectively.
Run the following command to spin up the freeradius-gsuite container:

`docker-compose up`

If you encounter problems, it might be interesting to follow the output of the container by uncommenting the `command: -X` line in `docker-compose.yaml`


Now the freeradius should be up and running and accepting connections using the `user@senthilnasa.ac.in`, `user@senthilnasa.com`, `user@senthilnasa.me` and `user` as login names with automatic VLAN assignment.


## Adding a custom certificate for the EAP authentication

In order to change the `Example Server certificate` which you need to accept before logging in to the Freeradius server, you need to create your own Certificate Authority and server certificate. More info can be found in the container in the `/etc/raddb/certs/README` file.

You can use the container to create your new certificates as documented in the README file. When done you should need at least following files copied to the `./certs` directory here. Do not change their names!

- `ca.key`
- `ca.pem`
- `dh`
- `server.crt`
- `server.csr`
- `server.key`
- `server.p12`
- `server.pem`

Now you can restart the container and the new certificates will be used

Have fun!

---

Special thanks to Hacor!
