# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v2.1.0] - 2025-11-08

### 🎯 **Added**

#### **🕐 MySQL Timezone Configuration Enhancement**
- **Environment-driven timezone settings** - MySQL timezone is now fully configurable through environment variables
- **Flexible timezone offset support** - Added `MYSQL_TIMEZONE_OFFSET` environment variable for precise timezone control
- **Comprehensive timezone examples** - Updated `.env.example` with common timezone offsets for global deployment

**New Environment Variables:**
```env
# MySQL timezone setting
MYSQL_TIMEZONE=Asia/Kolkata

# MySQL timezone offset (use format: +HH:MM or -HH:MM)
MYSQL_TIMEZONE_OFFSET=+05:30
```

**Supported Timezone Offsets:**
- `+05:30` (IST - India Standard Time)
- `+00:00` (UTC - Coordinated Universal Time)
- `-05:00` (EST - Eastern Standard Time)
- `+01:00` (CET - Central European Time)
- `+09:00` (JST - Japan Standard Time)
- `+08:00` (CST - China Standard Time)
- `-08:00` (PST - Pacific Standard Time)

**Benefits:**
- ✅ **Database timestamps** now reflect local timezone
- ✅ **Authentication logs** show accurate local time
- ✅ **Accounting records** use configured timezone
- ✅ **Easy deployment** across different geographic regions
- ✅ **Production ready** with environment-specific timezone settings

**Implementation Details:**
- Updated `docker-compose.yml` to use `${MYSQL_TIMEZONE_OFFSET}` environment variable
- Enhanced `.env.example` with comprehensive timezone documentation
- Added MySQL server configuration: `--default-time-zone='${MYSQL_TIMEZONE_OFFSET}'`
- Container timezone synchronization with `TZ=${MYSQL_TIMEZONE}` environment variable

**Usage:**
1. Set desired timezone in `.env`:
   ```env
   MYSQL_TIMEZONE_OFFSET=+09:00  # For Japan Standard Time
   ```
2. Restart MySQL container:
   ```bash
   docker-compose restart mysql
   ```
3. Verify timezone setting:
   ```bash
   docker exec radius-mysql mysql -u root -p -e "SELECT @@time_zone, NOW();"
   ```

### 🔧 **Enhanced**
- **Documentation improvements** - Updated README.md with cleaner formatting and left-aligned installation commands
- **GitHub badges optimization** - Fixed duplicate badges and arranged them in a single row layout
- **Environment configuration** - Improved `.env.example` with better timezone documentation

### 🐛 **Fixed**
- **README formatting issues** - Resolved duplicated content and corrupted HTML formatting
- **Badge layout problems** - Fixed GitHub stars/forks badges appearing in multiple rows
- **Installation command alignment** - Made all bash commands properly left-aligned for better readability

---

## [v2.0.0] - 2025-11-07

### 🎯 **Added**
- **Production-ready deployment** - Complete Docker-based solution with health checks
- **Google Workspace LDAP integration** - Seamless authentication with Google Secure LDAP
- **Multi-domain VLAN support** - Automatic VLAN assignment based on domain classification
- **Admin dashboard** - Real-time monitoring and user management interface
- **Enterprise security features** - Bcrypt password hashing, JWT sessions, audit trails

### 🔧 **Enhanced**
- **Network configuration** - Custom Docker network with proper isolation
- **Database optimization** - MySQL 8.0 with performance tuning
- **Environment variables** - Comprehensive configuration system
- **Certificate management** - Secure Google LDAP certificate handling

### 🛡️ **Security**
- **Production hardening** - Removed development tools from production stack
- **Password management** - Secure password reset tools for Linux/Mac and Windows
- **Network isolation** - Proper firewall and access control configuration

---

## [v1.0.0] - 2025-11-06

### 🎯 **Initial Release**
- **FreeRADIUS server** - Basic RADIUS authentication setup
- **MySQL database** - User and session management
- **Docker containerization** - Basic Docker setup
- **Google LDAP support** - Initial Google Workspace integration

---

## 🚀 **Upcoming Features**

### **Planned for v2.2.0**
- [ ] **Multi-language dashboard** - Support for multiple languages
- [ ] **Advanced analytics** - Enhanced reporting and statistics
- [ ] **API endpoints** - RESTful API for external integrations
- [ ] **Backup automation** - Automated database backup solutions

### **Future Enhancements**
- [ ] **High availability** - Load balancing and failover support
- [ ] **Container orchestration** - Kubernetes deployment manifests
- [ ] **Monitoring integration** - Prometheus and Grafana support
- [ ] **Mobile app** - Mobile dashboard for administrators

---

## 📋 **Migration Guide**

### **Upgrading to v2.1.0**

If you're upgrading from a previous version, follow these steps:

1. **Update environment files:**
   ```bash
   # Add new timezone variables to your .env file
   echo "MYSQL_TIMEZONE=Asia/Kolkata" >> .env
   echo "MYSQL_TIMEZONE_OFFSET=+05:30" >> .env
   ```

2. **Update Docker Compose:**
   ```bash
   # Pull the latest changes
   git pull origin master
   
   # Restart services with new configuration
   docker-compose down
   docker-compose up -d
   ```

3. **Verify timezone configuration:**
   ```bash
   docker exec radius-mysql mysql -u root -p -e "SELECT @@time_zone, NOW();"
   ```

### **Breaking Changes**
- **None** - This release is fully backward compatible

---

## 🤝 **Contributing**

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

## 📄 **License**

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 **Support**

- **Issues**: [GitHub Issues](https://github.com/senthilnasa/freeradius-google-ldap-dashboard/issues)