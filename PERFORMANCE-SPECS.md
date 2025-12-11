# FreeRADIUS Performance Specifications for 2k-3k Concurrent Users

## System Overview

This document outlines the hardware, configuration, and performance specifications for running FreeRADIUS with Google LDAP authentication to support **2,000-3,000 concurrent WiFi users**.

## Hardware Requirements

### Recommended Server Specifications

#### Production Environment (2k-3k Concurrent Users)

**FreeRADIUS Container:**
- **CPU:** 8-16 cores (Intel Xeon or AMD EPYC recommended)
- **RAM:** 8-16 GB
- **Storage:** 100 GB SSD (NVMe preferred for logs and cache)
- **Network:** 1 Gbps minimum, 10 Gbps recommended

**MySQL Database Container:**
- **CPU:** 4-8 cores
- **RAM:** 4-8 GB
- **Storage:** 200-500 GB SSD (NVMe preferred)
  - RAID 10 recommended for production
  - Separate disks for data and logs
- **Network:** 1 Gbps minimum

**Total System Requirements:**
- **CPU:** 12-24 cores
- **RAM:** 16-32 GB
- **Storage:** 300-600 GB SSD (NVMe)
- **Network:** 1-10 Gbps
- **OS:** Linux (Ubuntu 20.04/22.04 LTS, CentOS 8, RHEL 8+)

### Minimum Server Specifications

For development/testing or smaller deployments (500-1000 users):

- **CPU:** 4-8 cores
- **RAM:** 8-16 GB
- **Storage:** 100 GB SSD
- **Network:** 1 Gbps

## Performance Configuration

### FreeRADIUS Configuration

#### Thread Pool Settings
Location: `configs/radiusd.conf`

```
thread pool {
    wait = yes
    start_servers = 50          # Initial threads (2k-3k users)
    max_servers = 500           # Maximum threads
    min_spare_servers = 25      # Minimum idle threads
    max_spare_servers = 100     # Maximum idle threads
    max_queue_size = 131072     # Request queue size
}

max_request_time = 60           # Maximum request processing time (seconds)
cleanup_delay = 10              # Reply cache cleanup delay (seconds)
max_requests = 65536            # Maximum tracked requests
```

**Rationale:**
- **500 max threads** can handle 500 concurrent authentication requests
- With 3-5 second average auth time, supports ~3,000 auths/minute
- Queue size of 131,072 handles burst traffic spikes

#### LDAP Connection Pool Settings
Location: `configs/ldap`

```
pool {
    start = 10                  # Initial connections
    min = 10                    # Minimum connections
    max = 100                   # Maximum connections per LDAP module
    spare = 20                  # Spare connections
    idle_timeout = 900          # 15 minutes
    retry_delay = 10            # Retry delay after failure
}

options {
    res_timeout = 15            # LDAP query timeout
    srv_timelimit = 15          # Server-side time limit
    net_timeout = 10            # Network timeout
}
```

**Total LDAP Capacity:**
- 4 domain modules × 100 connections = **400 total LDAP connections**
- Supports 400 concurrent LDAP authentications
- Google LDAP rate limits: ~1000 queries/second per project

#### Caching Configuration
Location: `configs/cache`

```
cache ldap_cache {
    ttl = 600                   # 10 minutes (user attributes)
    max_entries = 100000        # Cache up to 100k users
}

cache auth_cache {
    ttl = 300                   # 5 minutes (authentication results)
    max_entries = 10000         # Recent authentication cache
}
```

**Cache Hit Ratio:**
- With 600s TTL and typical re-auth every 15-30 minutes
- Expected cache hit ratio: 60-80%
- Reduces LDAP queries by 60-80% under steady load

#### MySQL Database Settings
Location: `docker-compose.yml`

```
MySQL Command Options:
    --max-connections=1000
    --innodb-buffer-pool-size=2G
    --innodb-log-file-size=512M
    --table-open-cache=8000
    --thread-cache-size=200
    --innodb-io-capacity=8000
```

**SQL Connection Pool:**
- FreeRADIUS SQL module: 100 connections
- Supports 100 concurrent accounting operations
- MySQL can handle 1000 total connections

### Environment Variables

#### Essential Environment Variables (.env file)

```bash
# === LDAP Configuration ===
LDAP_SERVER=ldaps://ldap.google.com
LDAP_IDENTITY=ldap-read@yourdomain.com
LDAP_PASSWORD=your-google-ldap-password
LDAP_BASE_DN=dc=yourdomain,dc=com

# Multi-Domain Configuration (JSON format)
DOMAIN_CONFIG=[{"domain":"yourdomain.edu.in","type":"staff","vlan":"248"},{"domain":"yourdomain.ac.in","type":"student","vlan":"144"}]

# === Performance Tuning ===
RADIUS_MAX_CONNECTIONS=500      # Thread pool max_servers
LDAP_MAX_CONNECTIONS=100        # LDAP pool max per module
DB_MAX_CONNECTIONS=100          # SQL connection pool

# === Caching ===
CACHE_TTL=600                   # LDAP cache TTL (seconds)
ENABLE_LDAP_CACHE=yes
ENABLE_AUTH_CACHE=yes

# === Database ===
DB_HOST=mysql
DB_PORT=3306
DB_NAME=radius
DB_USER=radius
DB_PASSWORD=your-secure-password
DB_ROOT_PASSWORD=your-root-password

# === RADIUS ===
RADIUS_SECRET=your-shared-secret
RADIUS_AUTH_PORT=1812
RADIUS_ACCT_PORT=1813

# === Logging ===
LOG_LEVEL=info
ENABLE_SQL_TRACE=no
ENABLE_LDAP_DEBUG=no
LOG_AUTH_SUCCESS=yes
LOG_AUTH_FAILURE=yes
LOG_PASSWORDS=no                # Security: never log passwords in production
```

## Performance Benchmarks

### Expected Performance

#### Authentication Throughput

| Metric | Value | Notes |
|--------|-------|-------|
| **Peak Auth Rate** | 500 auths/second | With caching enabled |
| **Sustained Auth Rate** | 300 auths/second | 24/7 operation |
| **Concurrent Users** | 2,000-3,000 | Active WiFi sessions |
| **Daily Authentications** | 500,000-1,000,000 | Including re-auths |
| **Average Auth Latency** | 100-300ms | With cache hit |
| **Average Auth Latency (no cache)** | 500-1500ms | LDAP query required |

#### Resource Usage Estimates

**Normal Load (1,500 concurrent users):**
- FreeRADIUS CPU: 30-50%
- FreeRADIUS RAM: 2-4 GB
- MySQL CPU: 20-40%
- MySQL RAM: 2-4 GB
- Network: 100-300 Mbps

**Peak Load (3,000 concurrent users):**
- FreeRADIUS CPU: 60-80%
- FreeRADIUS RAM: 4-8 GB
- MySQL CPU: 40-60%
- MySQL RAM: 4-6 GB
- Network: 300-600 Mbps

## Network Requirements

### Bandwidth Requirements

- **Per Authentication:** 2-5 KB (RADIUS packets)
- **Per Accounting Update:** 1-3 KB
- **With 500 auths/second:** ~2.5 MB/s = 20 Mbps
- **With accounting:** ~5 MB/s = 40 Mbps
- **Recommended:** 1 Gbps connection with QoS

### Firewall Rules

```
ALLOW UDP 1812 (RADIUS Authentication)
ALLOW UDP 1813 (RADIUS Accounting)
ALLOW TCP 3306 (MySQL) - internal network only
ALLOW TCP 80/443 (Web Dashboard) - secure networks only
ALLOW TCP 636 (LDAPS to Google) - outbound only
```

### Network Architecture

```
[Access Points] ---UDP 1812/1813---> [FreeRADIUS Server]
                                            |
                                            |---TCP 636---> [Google LDAP]
                                            |
                                            |---TCP 3306---> [MySQL Database]
                                            |
                                            |---TCP 80/443---> [Web Dashboard]
```

## Scalability

### Vertical Scaling (Single Server)

| User Count | CPU Cores | RAM | Expected Performance |
|------------|-----------|-----|---------------------|
| 500-1,000 | 4-8 | 8-16 GB | Excellent |
| 1,000-2,000 | 8-12 | 16-24 GB | Very Good |
| 2,000-3,000 | 12-16 | 24-32 GB | Good |
| 3,000+ | 16+ | 32+ GB | Requires testing |

### Horizontal Scaling (Multiple Servers)

For deployments exceeding 3,000 concurrent users:

1. **Load Balancer Configuration:**
   - Use DNS round-robin or hardware load balancer
   - Configure multiple FreeRADIUS servers
   - Share MySQL database (with replication)

2. **High Availability:**
   - Deploy 2-3 FreeRADIUS servers
   - MySQL master-slave replication
   - Keepalived for failover

3. **Geographic Distribution:**
   - Regional FreeRADIUS servers
   - Replicated MySQL databases
   - LDAP caching layer

## Monitoring and Optimization

### Key Performance Indicators (KPIs)

Monitor these metrics:

1. **Authentication Metrics:**
   - Authentication success rate (target: >95%)
   - Average authentication latency (target: <500ms)
   - Peak authentication rate

2. **System Metrics:**
   - CPU utilization (alert: >80%)
   - RAM utilization (alert: >85%)
   - Thread pool usage (alert: >400 threads)
   - LDAP connection pool usage (alert: >80 connections)

3. **Cache Metrics:**
   - Cache hit ratio (target: >70%)
   - Cache memory usage
   - Expired entries per minute

4. **Database Metrics:**
   - Query latency (target: <100ms)
   - Connection pool usage (alert: >80)
   - Slow queries (alert: >1s)

### Monitoring Tools

**Recommended:**
- **Prometheus + Grafana** - Metrics collection and visualization
- **FreeRADIUS status server** - Real-time RADIUS statistics
- **MySQL Performance Schema** - Database query analysis
- **Docker stats** - Container resource monitoring
- **ELK Stack** - Log aggregation and analysis

### Performance Testing

Before production deployment:

1. **Load Testing:**
   ```bash
   # Use radperf or radload for RADIUS load testing
   radperf -s <radius-server> -A <auth-port> -n 1000 -r 100
   ```

2. **Stress Testing:**
   - Simulate 3,000 concurrent authentications
   - Monitor system resources
   - Verify no authentication failures

3. **Failover Testing:**
   - Test server restart under load
   - Verify MySQL failover
   - Test LDAP connection recovery

## Optimization Tips

### Performance Tuning

1. **Enable Caching:**
   - Set `CACHE_TTL=600` for 10-minute cache
   - Monitor cache hit ratio
   - Adjust TTL based on user behavior

2. **Optimize LDAP Queries:**
   - Use specific LDAP filters
   - Enable connection pooling
   - Monitor Google LDAP quotas

3. **Database Optimization:**
   - Regular ANALYZE TABLE on radacct
   - Archive old accounting records
   - Use InnoDB compression for large tables

4. **Log Rotation:**
   - Configure logrotate for FreeRADIUS logs
   - Keep last 30 days of logs
   - Compress archived logs

### Security Considerations

1. **RADIUS Secret:**
   - Use strong 20+ character secrets
   - Different secrets per access point
   - Rotate secrets quarterly

2. **LDAP Credentials:**
   - Use dedicated read-only LDAP account
   - Restrict to necessary OUs
   - Rotate credentials quarterly

3. **Database Security:**
   - Strong MySQL root password
   - Restrict MySQL network access
   - Regular security updates

4. **Network Security:**
   - Isolate RADIUS on management VLAN
   - Firewall rules per device
   - Monitor for authentication anomalies

## Industry Standards Compliance

This configuration follows:

- **RFC 2865** - RADIUS Authentication
- **RFC 2866** - RADIUS Accounting
- **RFC 2868** - RADIUS Tunnel Attributes
- **RFC 5080** - RADIUS Accounting Reliability
- **IEEE 802.1X** - Network Access Control
- **Eduroam** - Educational Roaming Best Practices

## Troubleshooting

### Common Performance Issues

1. **High Authentication Latency (>2s):**
   - Check LDAP connection pool usage
   - Verify Google LDAP quota
   - Enable caching
   - Check network latency to Google

2. **Authentication Failures:**
   - Check FreeRADIUS logs
   - Verify LDAP credentials
   - Check thread pool exhaustion
   - Verify domain configuration

3. **Database Slow Queries:**
   - Analyze MySQL slow query log
   - Add indexes to radacct table
   - Increase innodb_buffer_pool_size
   - Archive old records

4. **Memory Issues:**
   - Reduce cache max_entries
   - Lower LDAP connection pool
   - Reduce MySQL buffer pool size
   - Add swap space (not recommended for production)

### Support Resources

- **FreeRADIUS Documentation:** https://freeradius.org/documentation/
- **Google LDAP Guide:** https://support.google.com/a/answer/9048516
- **MySQL Performance Tuning:** https://dev.mysql.com/doc/refman/8.0/en/optimization.html

## Conclusion

This configuration is designed to support **2,000-3,000 concurrent WiFi users** using industry-standard best practices. The system is:

- **Scalable:** Can grow with your user base
- **Reliable:** Caching and connection pooling for high availability
- **Performant:** Optimized for low-latency authentication
- **Secure:** Following RFC standards and security best practices
- **Maintainable:** Environment-driven configuration for easy updates

For deployments exceeding 3,000 users, consider horizontal scaling with multiple FreeRADIUS servers and MySQL replication.
