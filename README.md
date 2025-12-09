# FreeRADIUS Google LDAP Dashboard

A comprehensive FreeRADIUS authentication system with Google LDAP integration and a modern web-based management dashboard.

## Features

### FreeRADIUS Server
- **Google LDAP Integration**: Seamless authentication against Google Workspace Directory
- **EAP-TTLS/PEAP Support**: Industry-standard wireless authentication protocols
- **Dynamic VLAN Assignment**: Automatic VLAN assignment based on user groups/departments
- **User Type Classification**: Categorize users by type (Student-MBA, Student-SIAS, Staff, etc.)
- **Comprehensive Logging**: Detailed authentication and accounting logs with error categorization

### Web Dashboard
- **Real-time Monitoring**: Live authentication logs with VLAN and user type information
- **Advanced Reporting**:
  - Daily Authentication Summary
  - Monthly Usage Reports
  - Failed Login Analysis
  - User Type Distribution
  - System Health Metrics
- **User Management**: Full operator/admin management with role-based access control
- **CSV Export**: Export authentication logs and reports
- **Responsive UI**: Modern Bootstrap 5 interface

## Quick Start

### Prerequisites
- Docker and Docker Compose
- Google Workspace account with LDAP enabled
- Network access to Google LDAP servers

### Installation

1. **Clone the repository**
```bash
git clone <repository-url>
cd freeradius-google-ldap-dashboard
```

2. **Configure Environment**
```bash
cp .env.example .env
```

Edit `.env` with your configuration:
```env
# Google LDAP Configuration
LDAP_HOST=ldap.google.com
LDAP_PORT=636
LDAP_BASE_DN=dc=example,dc=com
LDAP_BIND_DN=uid=radius,ou=service-accounts,dc=example,dc=com
LDAP_BIND_PASSWORD=your_ldap_password

# RADIUS Configuration
RADIUS_SHARED_SECRET=your_radius_secret

# Database Configuration
DB_ROOT_PASSWORD=rootpassword123
DB_PASSWORD=radius123

# Application Configuration
APP_URL=http://localhost:8080
APP_TIMEZONE=Asia/Kolkata
```

3. **Configure Domain Mapping**

Edit `freeradius/config/domain-config.json`:
```json
{
  "domains": {
    "example.com": {
      "Name": "Example Organization",
      "Base_DN": "dc=example,dc=com",
      "Search_Filter": "(mail=%u)",
      "Groups": {
        "student.mba@example.com": {
          "VLAN": "100",
          "Type": "Student-MBA"
        },
        "staff@example.com": {
          "VLAN": "10",
          "Type": "Staff"
        }
      }
    }
  }
}
```

4. **Start the Services**
```bash
docker-compose up -d
```

5. **Access the Dashboard**
- URL: http://localhost:8080
- Username: `admin`
- Password: `admin123`
- **Note**: You will be forced to change the password on first login

### Service Ports
- **FreeRADIUS**: UDP 1812 (Authentication), 1813 (Accounting)
- **Web Dashboard**: HTTP 8080
- **MySQL**: TCP 3306

## Configuration

### VLAN and User Type Assignment

VLAN and user type are assigned based on the user's email domain and group membership as defined in `domain-config.json`.

**Example Configuration:**
```json
{
  "domains": {
    "krea.edu.in": {
      "Name": "Krea University",
      "Base_DN": "dc=krea,dc=edu,dc=in",
      "Search_Filter": "(mail=%u)",
      "Groups": {
        "*.mba@krea.edu.in": {
          "VLAN": "256",
          "Type": "Student-MBA"
        },
        "*@krea.edu.in": {
          "VLAN": "10",
          "Type": "Staff"
        }
      }
    }
  }
}
```

**Matching Rules:**
- Exact match: `student@example.com`
- Domain wildcard: `*@example.com`
- Subdomain wildcard: `*.mba@example.com`
- Priority: More specific patterns match first

### LDAP Certificate Configuration

For production use with Google LDAP:

1. Download Google LDAP certificate
2. Place in `freeradius/certs/google-ldap.crt`
3. Update `freeradius/config/ldap` configuration

### Firewall Configuration

Allow the following ports:
```bash
# RADIUS Authentication
sudo ufw allow 1812/udp

# RADIUS Accounting
sudo ufw allow 1813/udp

# Web Dashboard (optional - use reverse proxy in production)
sudo ufw allow 8080/tcp
```

## Dashboard Features

### Authentication Logs
- Real-time authentication attempts
- VLAN assignment tracking
- User type classification
- Error categorization (password_wrong, invalid_domain, ldap_error, etc.)
- Date range filtering
- CSV export

### Reports

#### Daily Authentication Summary
- Total attempts, success/failure counts
- Hourly breakdown
- VLAN distribution
- Error type analysis

#### Monthly Usage Report
- Daily session statistics
- Data usage tracking
- Unique user counts

#### Failed Login Report
- Failed authentication patterns
- Threshold-based filtering
- Error type grouping

#### User Type Distribution
- Authentication patterns by user type
- VLAN correlation analysis
- Daily breakdown
- Failed authentication trends

#### System Health
- Database statistics
- Performance metrics
- Active sessions
- NAS device activity

### User Management

**Roles:**
- **Superadmin**: Full system access including user management
- **Netadmin**: User management and all reports
- **Helpdesk**: Read-only access to reports and logs

**Features:**
- Create/Edit/Delete operators
- Role-based permissions
- Forced password changes
- Audit trail

## Database Schema

### Core RADIUS Tables
- `radpostauth`: Authentication attempts with VLAN and user type
- `radacct`: Accounting/session data
- `radcheck`: User credentials
- `radreply`: User attributes
- `nas`: Network Access Servers

### Dashboard Tables
- `operators`: Dashboard users
- `daily_stats`: Pre-aggregated daily statistics

### Enhanced Logging Fields
- `vlan`: Assigned VLAN ID (only for successful authentications)
- `user_type`: User classification (Student-MBA, Staff, etc.)
- `error_type`: Categorized error reasons
- `reply_message`: Detailed authentication result
- `authdate_utc`: UTC timestamp for cross-timezone consistency

## Troubleshooting

### Authentication Issues

**Check RADIUS logs:**
```bash
docker-compose logs -f freeradius
```

**Common issues:**
1. **LDAP Connection Failed**: Verify LDAP credentials and firewall rules
2. **Certificate Errors**: Ensure Google LDAP certificate is properly installed
3. **No VLAN Assigned**: Check domain-config.json pattern matching
4. **User Not Found**: Verify LDAP search filter and base DN

### Dashboard Issues

**Check webapp logs:**
```bash
docker-compose logs -f webapp
```

**Reset Admin Password:**
```bash
docker-compose exec mysql mysql -uroot -prootpassword123 radius -e \
  "UPDATE operators SET password = MD5('admin123'), must_change_password = 1 WHERE username = 'admin';"
```

### Database Issues

**Access MySQL console:**
```bash
docker-compose exec mysql mysql -uroot -prootpassword123 radius
```

**Reinitialize database:**
```bash
docker-compose down -v
docker-compose up -d
```

## Performance Optimization

### LDAP Caching

LDAP responses are cached for 5 minutes to reduce load:
```
cache {
    size = 2000
    ttl = 300
    max_entries = 2000
}
```

### Database Indexes

All critical query fields are indexed:
- `idx_username`, `idx_authdate`, `idx_reply`
- `idx_error_type`, `idx_vlan`, `idx_user_type`
- Composite indexes for common query patterns

### Connection Pooling

LDAP connection pooling is configured for optimal performance:
```
pool {
    start = 5
    min = 5
    max = 20
    spare = 10
}
```

## Security Best Practices

1. **Change Default Credentials**: Always change admin password on first login
2. **Use Strong RADIUS Secrets**: Generate cryptographically secure shared secrets
3. **Enable HTTPS**: Use reverse proxy (nginx/Apache) with SSL/TLS in production
4. **Restrict Database Access**: Firewall MySQL port in production
5. **Regular Updates**: Keep Docker images and dependencies updated
6. **Monitor Logs**: Review authentication logs for suspicious activity
7. **Backup Database**: Implement regular database backups

## Production Deployment

### Using Reverse Proxy (Recommended)

**Nginx Example:**
```nginx
server {
    listen 443 ssl;
    server_name radius.example.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Environment Variables

Update `.env` for production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://radius.example.com
```

### Resource Limits

Adjust Docker resource limits in `docker-compose.yml`:
```yaml
services:
  freeradius:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
```

## Maintenance

### Backup

**Database backup:**
```bash
docker-compose exec mysql mysqldump -uroot -prootpassword123 radius > backup_$(date +%Y%m%d).sql
```

**Configuration backup:**
```bash
tar -czf config_backup_$(date +%Y%m%d).tar.gz freeradius/config .env
```

### Log Rotation

Logs are managed by Docker's logging driver. Configure in `docker-compose.yml`:
```yaml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

### Database Cleanup

Archive old authentication logs:
```sql
-- Archive records older than 90 days
INSERT INTO radpostauth_archive
SELECT * FROM radpostauth WHERE authdate < DATE_SUB(NOW(), INTERVAL 90 DAY);

DELETE FROM radpostauth WHERE authdate < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

## API Integration

### Querying Authentication Logs

The dashboard doesn't expose a REST API, but you can query the database directly:

```sql
-- Recent authentications for a user
SELECT username, authdate, reply, vlan, user_type, error_type
FROM radpostauth
WHERE username = 'user@example.com'
ORDER BY authdate DESC
LIMIT 10;

-- Daily success rate
SELECT
    DATE(authdate) as date,
    COUNT(*) as total,
    SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) as successful,
    ROUND(SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate
FROM radpostauth
WHERE DATE(authdate) BETWEEN '2025-01-01' AND '2025-01-31'
GROUP BY DATE(authdate);
```

## Support and Documentation

- **Archived Documentation**: See `docs-archive/` for detailed implementation guides
- **FreeRADIUS Documentation**: https://freeradius.org/documentation/
- **Google LDAP**: https://support.google.com/a/answer/9048434

## License

[Specify your license here]

## Contributors

[List contributors here]

## Changelog

See `docs-archive/` for detailed change history.

---

**Last Updated**: December 2025
**Version**: 2.0.0
