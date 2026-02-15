# Phase 5: Day 1 - Infrastructure Setup (Execution Log)

**Date**: 2026-02-13 (Planning) / 2026-02-16 (Execution - Monday)  
**Status**: 📋 READY TO EXECUTE  
**Duration**: 8 hours (8 AM - 5 PM)  
**Team**: Database Admin, System Admin, Technical Lead

---

## Task Checklist

### Morning Session (8 AM - 12 PM)

#### Task 1.1: PostgreSQL Installation ⏳
- [ ] Update package manager
- [ ] Install PostgreSQL 14+
- [ ] Verify installation
- [ ] Start service
- [ ] Enable auto-start

#### Task 1.2: Create Production Database ⏳
- [ ] Create irms_production database
- [ ] Create irms_staging database
- [ ] Create irms_app user
- [ ] Grant permissions
- [ ] Verify access

#### Task 1.3: PostgreSQL Configuration ⏳
- [ ] Edit postgresql.conf
- [ ] Configure memory settings
- [ ] Configure connection settings
- [ ] Restart PostgreSQL
- [ ] Verify configuration

### Afternoon Session (1 PM - 5 PM)

#### Task 1.4: PgBouncer Installation ⏳
- [ ] Install PgBouncer
- [ ] Create configuration
- [ ] Start service
- [ ] Test connection pooling
- [ ] Enable auto-start

#### Task 1.5: Redis Installation ⏳
- [ ] Install Redis
- [ ] Start service
- [ ] Enable auto-start
- [ ] Verify PING response

#### Task 1.6: Redis Configuration ⏳
- [ ] Edit redis.conf
- [ ] Set maxmemory
- [ ] Set eviction policy
- [ ] Add password
- [ ] Restart service

#### Task 1.7: Monitoring Setup ⏳
- [ ] Install monitoring agent
- [ ] Configure metrics
- [ ] Set up alerting
- [ ] Test alerts

---

## Execution Details

### Task 1.1: PostgreSQL Installation

**Estimated Time**: 30 minutes  
**Responsible**: Database Administrator

```bash
# Step 1: Update package manager
sudo apt-get update
sudo apt-get upgrade -y

# Step 2: Install PostgreSQL
sudo apt-get install -y postgresql postgresql-contrib

# Step 3: Verify installation
psql --version
# Expected: psql (PostgreSQL) 14.x or higher

# Step 4: Check service status
sudo systemctl status postgresql
# Expected: active (running)

# Step 5: Start and enable
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

**✅ Success Criteria**:
- PostgreSQL version 14+
- Service running and enabled
- Can connect to PostgreSQL

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 1.2: Create Production Database

**Estimated Time**: 15 minutes  
**Responsible**: Database Administrator

```bash
# Step 1: Connect to PostgreSQL
sudo -u postgres psql

# Step 2: Create databases
CREATE DATABASE irms_production;
CREATE DATABASE irms_staging;
CREATE DATABASE irms_test;

# Step 3: Create application user
CREATE USER irms_app WITH PASSWORD '<SECURE_PASSWORD_HERE>';

# Step 4: Grant permissions
ALTER USER irms_app CREATEDB;
GRANT ALL PRIVILEGES ON DATABASE irms_production TO irms_app;
GRANT ALL PRIVILEGES ON DATABASE irms_staging TO irms_app;
GRANT ALL PRIVILEGES ON DATABASE irms_test TO irms_app;

# Step 5: List databases
\l

# Step 6: List users
\du

# Step 7: Exit
\q

# Step 8: Test connection as irms_app user
psql -U irms_app -d irms_production -h localhost
# Should connect without error
# Then exit with: \q
```

**✅ Success Criteria**:
- irms_production database created
- irms_staging database created
- irms_app user created
- User has all permissions
- Can connect as irms_app user

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 1.3: PostgreSQL Configuration

**Estimated Time**: 20 minutes  
**Responsible**: Database Administrator

```bash
# Step 1: Backup original config
sudo cp /etc/postgresql/14/main/postgresql.conf \
        /etc/postgresql/14/main/postgresql.conf.backup

# Step 2: Edit configuration
sudo nano /etc/postgresql/14/main/postgresql.conf

# Key settings to modify (find and uncomment/change):

# Connection settings
listen_addresses = '*'
max_connections = 200

# Memory settings
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB

# Performance tuning
random_page_cost = 1.1
effective_io_concurrency = 200
wal_buffers = 16MB
checkpoint_completion_target = 0.9

# Logging
log_min_duration_statement = 1000
log_connections = on
log_disconnections = on

# Step 3: Edit pg_hba.conf for network access
sudo nano /etc/postgresql/14/main/pg_hba.conf

# Add line for PgBouncer (if on same machine):
# local   irms_production   irms_app   md5
# local   irms_staging      irms_app   md5

# Add line for network access (if needed):
# host    irms_production   irms_app   127.0.0.1/32   md5

# Step 4: Restart PostgreSQL
sudo systemctl restart postgresql

# Step 5: Verify configuration
sudo -u postgres psql -c "SHOW shared_buffers"
sudo -u postgres psql -c "SHOW max_connections"

# Step 6: Check for errors
sudo tail -20 /var/log/postgresql/postgresql-14-main.log
```

**✅ Success Criteria**:
- Configuration file edited
- No syntax errors
- PostgreSQL restarted successfully
- Settings applied (verified with SHOW commands)

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 1.4: PgBouncer Installation (Connection Pooling)

**Estimated Time**: 30 minutes  
**Responsible**: Database Administrator

```bash
# Step 1: Install PgBouncer
sudo apt-get install -y pgbouncer

# Step 2: Backup original config
sudo cp /etc/pgbouncer/pgbouncer.ini \
        /etc/pgbouncer/pgbouncer.ini.backup

# Step 3: Create new pgbouncer.ini
sudo tee /etc/pgbouncer/pgbouncer.ini > /dev/null << 'EOF'
[databases]
irms_production = host=127.0.0.1 port=5432 dbname=irms_production user=irms_app password=<PASSWORD>
irms_staging = host=127.0.0.1 port=5432 dbname=irms_staging user=irms_app password=<PASSWORD>
irms_test = host=127.0.0.1 port=5432 dbname=irms_test user=irms_app password=<PASSWORD>

[pgbouncer]
pool_mode = transaction
max_client_conn = 1000
default_pool_size = 50
min_pool_size = 10
reserve_pool_size = 5
reserve_pool_timeout = 3
max_db_connections = 100
max_user_connections = unlimited
listen_port = 6432
listen_addr = 127.0.0.1
logfile = /var/log/pgbouncer/pgbouncer.log
pidfile = /var/run/pgbouncer/pgbouncer.pid
admin_users = irms_app
EOF

# Step 4: Set proper permissions
sudo chown postgres:postgres /etc/pgbouncer/pgbouncer.ini
sudo chmod 600 /etc/pgbouncer/pgbouncer.ini

# Step 5: Create log directory
sudo mkdir -p /var/log/pgbouncer
sudo chown postgres:postgres /var/log/pgbouncer

# Step 6: Start PgBouncer
sudo systemctl start pgbouncer
sudo systemctl enable pgbouncer

# Step 7: Verify service
sudo systemctl status pgbouncer
# Expected: active (running)

# Step 8: Test connection through PgBouncer
psql -h 127.0.0.1 -p 6432 -U irms_app -d irms_production

# If prompt appears, test a query:
SELECT 1;
\q
```

**✅ Success Criteria**:
- PgBouncer installed
- Configuration file created
- Service running and enabled
- Can connect through PgBouncer (port 6432)
- Query executes successfully

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 1.5: Redis Installation

**Estimated Time**: 15 minutes  
**Responsible**: System Administrator

```bash
# Step 1: Install Redis
sudo apt-get install -y redis-server

# Step 2: Verify installation
redis-server --version
# Expected: Redis server v=6.x or higher

# Step 3: Check service status
sudo systemctl status redis-server
# Expected: active (running)

# Step 4: Start and enable
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Step 5: Test PING
redis-cli ping
# Expected output: PONG

# Step 6: Stop for configuration
sudo systemctl stop redis-server
```

**✅ Success Criteria**:
- Redis installed (version 6+)
- Service responds to PING
- Service can be started/stopped
- Service enabled for auto-start

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 1.6: Redis Configuration

**Estimated Time**: 20 minutes  
**Responsible**: System Administrator

```bash
# Step 1: Backup original config
sudo cp /etc/redis/redis.conf /etc/redis/redis.conf.backup

# Step 2: Edit Redis configuration
sudo nano /etc/redis/redis.conf

# Key settings to modify:

# Network
port 6379
bind 127.0.0.1
protected-mode yes

# Memory management
maxmemory 512mb
maxmemory-policy allkeys-lru

# Authentication
requirepass <STRONG_PASSWORD_HERE>

# Persistence
save 900 1
save 300 10
save 60 10000

# Logging
loglevel notice
logfile /var/log/redis/redis-server.log

# Step 3: Restart Redis
sudo systemctl start redis-server

# Step 4: Verify running
sudo systemctl status redis-server

# Step 5: Test with password
redis-cli -a <PASSWORD> ping
# Expected: PONG

# Step 6: Check memory configuration
redis-cli -a <PASSWORD> CONFIG GET maxmemory
# Expected: ["maxmemory", "536870912"]

# Step 7: Check info
redis-cli -a <PASSWORD> INFO memory
# Shows memory usage
```

**✅ Success Criteria**:
- Configuration file edited
- Redis restarted
- Responds to PING with password
- Memory limit set to 512MB
- Eviction policy configured

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 1.7: Monitoring Setup (Optional but Recommended)

**Estimated Time**: 1 hour  
**Responsible**: Operations Engineer

#### Option A: New Relic Agent

```bash
# Step 1: Download and install New Relic PHP agent
curl -L https://download.newrelic.com/php_agent/archive/10.10.0.318/newrelic-php5-10.10.0.318-linux.tar.gz | tar -xz
cd newrelic-php5-10.10.0.318-linux
sudo ./newrelic-install install

# Step 2: Configure agent
sudo nano /etc/php/8.1/fpm/conf.d/newrelic.ini

# Key settings:
# newrelic.appname = "IRMS Production"
# newrelic.license = "<YOUR_LICENSE_KEY>"

# Step 3: Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Step 4: Verify in New Relic dashboard
# Check: https://one.newrelic.com/
```

#### Option B: Prometheus + Grafana

```bash
# Step 1: Install Prometheus
sudo apt-get install -y prometheus

# Step 2: Configure Prometheus
sudo nano /etc/prometheus/prometheus.yml

# Add scrape configs for:
# - PostgreSQL exporter
# - Redis exporter
# - Node exporter

# Step 3: Start Prometheus
sudo systemctl start prometheus
sudo systemctl enable prometheus

# Step 4: Install Grafana
sudo apt-get install -y grafana-server

# Step 5: Start Grafana
sudo systemctl start grafana-server
sudo systemctl enable grafana-server

# Step 6: Access Grafana
# Navigate to: http://localhost:3000
# Default: admin/admin
```

**✅ Success Criteria**:
- Monitoring agent installed
- Metrics being collected
- Dashboard accessible
- Alerts configured

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

## End of Day 1 Verification

### Service Status Check

```bash
# Verify all services running
echo "=== PostgreSQL ==="
sudo systemctl status postgresql | grep Active

echo "=== PgBouncer ==="
sudo systemctl status pgbouncer | grep Active

echo "=== Redis ==="
sudo systemctl status redis-server | grep Active

# Test connections
echo "=== PostgreSQL Direct ==="
psql -U irms_app -d irms_production -h 127.0.0.1 -c "SELECT 1;"

echo "=== Via PgBouncer ==="
psql -U irms_app -d irms_production -h 127.0.0.1 -p 6432 -c "SELECT 1;"

echo "=== Redis ==="
redis-cli -a <PASSWORD> ping
```

### Final Checklist

- [ ] PostgreSQL installed and running
- [ ] Databases created (production, staging, test)
- [ ] irms_app user created with permissions
- [ ] PostgreSQL configuration optimized
- [ ] PgBouncer installed and running
- [ ] Connection pooling verified
- [ ] Redis installed and running
- [ ] Redis password configured
- [ ] Memory limits configured
- [ ] All services enabled for auto-start
- [ ] Monitoring configured
- [ ] Backups of configs created

### Issues Encountered

```
Issue 1: _______________________
Solution: ______________________
Status: [ ] Resolved [ ] Pending

Issue 2: _______________________
Solution: ______________________
Status: [ ] Resolved [ ] Pending
```

### Notes & Observations

```
_________________________________
_________________________________
_________________________________
```

---

## Day 1 Summary

**Start Time**: 8:00 AM  
**End Time**: _____ PM  
**Total Duration**: _____ hours  
**Tasks Completed**: _____ / 7  
**Issues**: _____ Critical, _____ Minor  

**Status**: ✅ READY FOR DAY 2

---

## Sign-Off

**Infrastructure Team Lead**: _________________ Date: _______  
**Database Administrator**: _________________ Date: _______  
**System Administrator**: _________________ Date: _______

---

**Next**: [Day 2: Database Migration & Testing](PHASE_5_DAY2_DATABASE_MIGRATION.md)
