# Connection Pool Configuration Guide

## Overview

FreeRADIUS connection pools are now **fully configurable via environment variables**. The system automatically calculates optimal pool settings based on your deployment size.

## Environment Variables

### LDAP Connection Pool

```bash
LDAP_MAX_CONNECTIONS=100
```

**Controls**: Maximum number of LDAP connections to Google LDAP

**Auto-scaled settings**:
- `start`: 10% of max (initial connections at startup)
- `min`: 10% of max (minimum connections to keep open)
- `max`: 100% (maximum concurrent connections)
- `spare`: 20% of max (idle connections ready for bursts)

**Example**: With `LDAP_MAX_CONNECTIONS=100`:
- start = 10 connections
- min = 10 connections
- max = 100 connections
- spare = 20 connections

### RADIUS Thread Pool

```bash
RADIUS_MAX_CONNECTIONS=500
```

**Controls**: Maximum number of concurrent RADIUS worker threads

**Auto-scaled settings**:
- `start_servers`: 10% of max (initial threads at startup)
- `max_servers`: 100% (maximum concurrent threads)
- `min_spare_servers`: 5% of max (minimum idle threads)
- `max_spare_servers`: 20% of max (maximum idle threads)

**Example**: With `RADIUS_MAX_CONNECTIONS=500`:
- start_servers = 50 threads
- max_servers = 500 threads
- min_spare_servers = 25 threads
- max_spare_servers = 100 threads

## Recommended Values by Deployment Size

### Small Deployment (< 100 concurrent users)

```bash
RADIUS_MAX_CONNECTIONS=50
LDAP_MAX_CONNECTIONS=20
```

**Resources**:
- RADIUS: 5-50 threads (max 50)
- LDAP: 2-20 connections (max 20)
- Memory: ~200-500 MB
- Startup: 5-10 seconds

**Suitable for**:
- Small schools/offices
- Single building deployments
- Development/testing environments

### Medium Deployment (100-1000 concurrent users)

```bash
RADIUS_MAX_CONNECTIONS=150
LDAP_MAX_CONNECTIONS=50
```

**Resources**:
- RADIUS: 15-150 threads (max 150)
- LDAP: 5-50 connections (max 50)
- Memory: ~500 MB - 1 GB
- Startup: 10-20 seconds

**Suitable for**:
- Medium-sized enterprises
- Multi-building campus
- Standard production deployments

### Large Deployment (1000-3000 concurrent users)

```bash
RADIUS_MAX_CONNECTIONS=500
LDAP_MAX_CONNECTIONS=100
```

**Resources**:
- RADIUS: 50-500 threads (max 500)
- LDAP: 10-100 connections (max 100)
- Memory: ~1-2 GB
- Startup: 20-30 seconds

**Suitable for**:
- Large enterprises
- Universities
- Multi-campus deployments
- High-traffic environments

### Enterprise Deployment (3000+ concurrent users)

```bash
RADIUS_MAX_CONNECTIONS=1000
LDAP_MAX_CONNECTIONS=200
```

**Resources**:
- RADIUS: 100-1000 threads (max 1000)
- LDAP: 20-200 connections (max 200)
- Memory: ~2-4 GB
- Startup: 30-60 seconds

**Suitable for**:
- Very large enterprises
- Major universities
- Service providers
- Multi-tenant deployments

## Single LDAP Optimization Benefits

### Before (Multi-Proxy Setup)

```bash
# Old configuration with 4 domains:
# - Domain 1: 100 max connections
# - Domain 2: 100 max connections
# - Domain 3: 100 max connections
# - Domain 4: 100 max connections
# Total: 400 LDAP connections!
```

**Problems**:
- 10+ minute startup time
- 400 connection initialization overhead
- Connection pool exhaustion
- High memory usage

### After (Single Connection Pool)

```bash
LDAP_MAX_CONNECTIONS=100  # ONE pool for ALL domains!
```

**Benefits**:
- ✅ 10-30 second startup time
- ✅ 100 connections serve all domains efficiently
- ✅ 75% reduction in connection overhead
- ✅ Optimal connection reuse across domains

## Configuration Files

### Where Values Are Set

1. **Environment File** (`.env`):
   ```bash
   RADIUS_MAX_CONNECTIONS=500
   LDAP_MAX_CONNECTIONS=100
   ```

2. **Auto-replaced at Startup** ([init.sh](init.sh:51-83)):
   - Calculates pool settings from environment variables
   - Updates `radiusd.conf` with RADIUS thread pool settings
   - Updates `ldap` module with LDAP connection pool settings

3. **Configuration Templates**:
   - [configs/radiusd.conf](configs/radiusd.conf:652-697) - RADIUS thread pool placeholders
   - [configs/ldap](configs/ldap:610-632) - LDAP connection pool placeholders

### Placeholder Replacement

**RADIUS Thread Pool** (radiusd.conf):
```
start_servers = ENV_RADIUS_START_SERVERS           → replaced with calculated value
max_servers = ENV_RADIUS_MAX_SERVERS              → replaced with RADIUS_MAX_CONNECTIONS
min_spare_servers = ENV_RADIUS_MIN_SPARE_SERVERS  → replaced with 5% of max
max_spare_servers = ENV_RADIUS_MAX_SPARE_SERVERS  → replaced with 20% of max
```

**LDAP Connection Pool** (ldap):
```
start = ENV_LDAP_POOL_START    → replaced with 10% of LDAP_MAX_CONNECTIONS
min = ENV_LDAP_POOL_MIN        → replaced with 10% of LDAP_MAX_CONNECTIONS
max = ENV_LDAP_POOL_MAX        → replaced with LDAP_MAX_CONNECTIONS
spare = ENV_LDAP_POOL_SPARE    → replaced with 20% of LDAP_MAX_CONNECTIONS
```

## Calculation Formula

### LDAP Pool (init.sh calculation)

```bash
LDAP_MAX=${LDAP_MAX_CONNECTIONS:-100}
LDAP_START=$((LDAP_MAX / 10))   # 10%
LDAP_MIN=$((LDAP_MAX / 10))     # 10%
LDAP_SPARE=$((LDAP_MAX / 5))    # 20%
```

### RADIUS Thread Pool (init.sh calculation)

```bash
RADIUS_MAX=${RADIUS_MAX_CONNECTIONS:-500}
RADIUS_START=$((RADIUS_MAX / 10))        # 10%
RADIUS_MIN_SPARE=$((RADIUS_MAX / 20))    # 5%
RADIUS_MAX_SPARE=$((RADIUS_MAX / 5))     # 20%
```

## Monitoring & Tuning

### Check Current Settings

After container startup, verify the applied configuration:

```bash
# Check LDAP pool settings
docker exec freeradius grep -A 10 "pool {" /etc/freeradius/mods-available/ldap

# Check RADIUS thread pool settings
docker exec freeradius grep -A 15 "thread pool {" /etc/freeradius/radiusd.conf

# View startup messages
docker logs freeradius | grep -i "configuring.*pool"
# Should show:
# Configuring LDAP pool: start=10, min=10, max=100, spare=20
# Configuring RADIUS thread pool: start=50, max=500, min_spare=25, max_spare=100
```

### Monitor Pool Usage

#### LDAP Connection Pool

In FreeRADIUS debug mode (`radiusd -X`), look for:

```
rlm_ldap (ldap): Opening additional connection (5)
rlm_ldap (ldap): Closing connection (10) - idle timeout
rlm_ldap (ldap): 25 of 100 connections in use
```

**Indicators**:
- ✅ **Good**: Connections stay between min and max, spare connections available
- ⚠️ **Warning**: Frequently hitting max connections
- ❌ **Problem**: "Failed to open connection" errors

**Solution**: Increase `LDAP_MAX_CONNECTIONS`

#### RADIUS Thread Pool

In FreeRADIUS debug mode:

```
Thread 45 waiting to be assigned a request
Thread 50 handling request 123
Thread pool stats: total=100, active=45, idle=55, queue=0
```

**Indicators**:
- ✅ **Good**: Queue stays at 0, threads < max_servers
- ⚠️ **Warning**: Threads frequently at max_servers, queue > 0
- ❌ **Problem**: "Request queue full" errors

**Solution**: Increase `RADIUS_MAX_CONNECTIONS`

### Performance Tuning Tips

#### Too Many Connections/Threads

**Symptoms**:
- High memory usage
- Slow startup time
- Most threads/connections idle

**Solution**: Reduce `RADIUS_MAX_CONNECTIONS` and `LDAP_MAX_CONNECTIONS`

#### Too Few Connections/Threads

**Symptoms**:
- Authentication timeouts
- "Queue full" or "Max connections reached" errors
- High latency during peak hours

**Solution**: Increase `RADIUS_MAX_CONNECTIONS` and `LDAP_MAX_CONNECTIONS`

#### LDAP Connection Bottleneck

**Symptoms**:
- LDAP queries slow (> 1 second)
- Many "opening additional connection" messages
- Hitting `LDAP_MAX_CONNECTIONS` limit

**Solutions**:
1. Increase `LDAP_MAX_CONNECTIONS`
2. Enable LDAP caching (already enabled, check `CACHE_TTL` setting)
3. Check Google LDAP API quotas

#### RADIUS Thread Bottleneck

**Symptoms**:
- Authentication delays during peak hours
- Queue filling up
- NAS/AP reporting timeouts

**Solutions**:
1. Increase `RADIUS_MAX_CONNECTIONS`
2. Optimize database queries (check MySQL slow query log)
3. Increase SQL connection pool (`DB_MAX_CONNECTIONS`)

## Example Configurations

### Development Environment

```bash
# .env
RADIUS_MAX_CONNECTIONS=50
LDAP_MAX_CONNECTIONS=20
DB_MAX_CONNECTIONS=20
```

**Result**:
- Fast startup (< 10 seconds)
- Low resource usage
- Suitable for testing

### Production - Small Office (50 users)

```bash
# .env
RADIUS_MAX_CONNECTIONS=50
LDAP_MAX_CONNECTIONS=20
DB_MAX_CONNECTIONS=50
```

### Production - Medium Enterprise (500 users)

```bash
# .env
RADIUS_MAX_CONNECTIONS=150
LDAP_MAX_CONNECTIONS=50
DB_MAX_CONNECTIONS=100
```

### Production - Large University (2000 users)

```bash
# .env
RADIUS_MAX_CONNECTIONS=500
LDAP_MAX_CONNECTIONS=100
DB_MAX_CONNECTIONS=150
```

### Production - Service Provider (5000+ users)

```bash
# .env
RADIUS_MAX_CONNECTIONS=1000
LDAP_MAX_CONNECTIONS=200
DB_MAX_CONNECTIONS=200
```

## Relationship with Other Settings

### Database Connections

```bash
DB_MAX_CONNECTIONS=150
```

**Best Practice**: Set `DB_MAX_CONNECTIONS` to approximately **30-50% of RADIUS_MAX_CONNECTIONS**

**Why**: Not every RADIUS request requires a database query (e.g., cached LDAP lookups)

### Cache Settings

```bash
CACHE_TTL=600  # 10 minutes
```

**Impact**:
- Higher cache TTL = Fewer LDAP queries = Lower LDAP connection usage
- Lower cache TTL = More LDAP queries = Higher LDAP connection usage

### VLAN Attributes

The number of domains in `DOMAIN_CONFIG` does NOT affect connection pool sizing anymore (thanks to single LDAP optimization).

## Troubleshooting

### Issue: Slow Startup

**Cause**: Too many initial connections being created

**Check**:
```bash
docker logs freeradius | grep "Opening.*connection"
```

**Solution**: The startup calculation is already optimized (10% of max). If still slow:
1. Check Google LDAP API limits
2. Check network latency to ldap.google.com
3. Verify LDAP certificates are valid

### Issue: Authentication Failures Under Load

**Cause**: Connection/thread pool exhaustion

**Check**:
```bash
docker exec -it freeradius radiusd -X
# Look for: "Failed to get connection" or "Queue full"
```

**Solution**:
```bash
# Increase pools
RADIUS_MAX_CONNECTIONS=1000  # was 500
LDAP_MAX_CONNECTIONS=200     # was 100
```

### Issue: High Memory Usage

**Cause**: Too many idle connections/threads

**Check**:
```bash
docker stats freeradius
```

**Solution**:
```bash
# Reduce pools
RADIUS_MAX_CONNECTIONS=150  # was 500
LDAP_MAX_CONNECTIONS=50     # was 100
```

## Migration from Fixed Values

If you're upgrading from a version with hardcoded pool values:

### Before

Hardcoded in config files:
- LDAP: max=100 (fixed)
- RADIUS: max_servers=500 (fixed)

### After

Configurable via environment:
```bash
# .env
LDAP_MAX_CONNECTIONS=100      # Same as before
RADIUS_MAX_CONNECTIONS=500    # Same as before
```

**No changes needed** - defaults match previous values!

## Best Practices

1. ✅ **Start Conservative**: Use default values initially
2. ✅ **Monitor First**: Run for a week, check peak usage
3. ✅ **Scale Gradually**: Increase by 25-50% if needed
4. ✅ **Document Changes**: Note why you changed values
5. ✅ **Test Changes**: Verify in dev/staging first
6. ✅ **Keep Ratio**: LDAP ≈ 20% of RADIUS connections
7. ✅ **Cache Aggressively**: Higher CACHE_TTL = fewer LDAP queries

## Quick Reference

| Deployment Size | Concurrent Users | RADIUS_MAX | LDAP_MAX | DB_MAX |
|----------------|------------------|------------|----------|---------|
| Development    | < 10             | 50         | 20       | 20      |
| Small          | < 100            | 50         | 20       | 50      |
| Medium         | 100-1000         | 150        | 50       | 100     |
| Large          | 1000-3000        | 500        | 100      | 150     |
| Enterprise     | 3000+            | 1000       | 200      | 200     |

---

**Related Documentation**:
- [OPTIMIZATION-SUMMARY.md](OPTIMIZATION-SUMMARY.md) - Single LDAP connection optimization
- [VLAN-DYNAMIC-CONFIG.md](VLAN-DYNAMIC-CONFIG.md) - Dynamic VLAN configuration
- [.env.example](.env.example) - All environment variables

**Last Updated**: 2025-12-12
**Configuration Files**: init.sh, configs/ldap, configs/radiusd.conf
