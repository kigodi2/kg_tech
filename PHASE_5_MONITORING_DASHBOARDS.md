# Phase 5: Monitoring Dashboards & Health Checks

**Status**: 📊 PRODUCTION MONITORING  
**Document**: Dashboard Setup & Configuration  
**Audience**: Operations Team, DevOps Engineers  
**Last Updated**: 2026-02-13

---

## Monitoring Architecture

```
Application Metrics
  ↓
Prometheus (scrapes metrics)
  ↓
Grafana (visualizes data)
  ↓
Alert Manager (sends alerts)
  ↓
Operations Team
```

---

## Dashboard 1: Application Health

### Metrics to Display

**Page Load Time**
- Current: [time in seconds]
- Average (last hour): [avg time]
- Peak (last hour): [max time]
- Target: < 3 seconds ✅

```
Graph Type: Line Chart
Time Range: Last 24 hours
Threshold: 3 seconds (red line)
Alert: If > 5 seconds for 5 minutes
```

**Request Success Rate**
- Current: [% of successful requests]
- Target: > 99.9%

```
Graph Type: Gauge
Range: 0-100%
Color: Green if > 99%, Yellow if 99-95%, Red if < 95%
Alert: If < 95% for 10 minutes
```

**Active Users**
- Current: [number online now]
- Peak: [today's peak]
- Average: [24-hour average]

```
Graph Type: Line Chart
Time Range: Last 24 hours
Threshold: 100 users (warning), 500 users (critical)
Alert: If > 500 concurrent users
```

**Error Rate**
- Current: [errors per minute]
- Target: < 0.1%

```
Graph Type: Gauge
Unit: errors/minute
Color: Green if < 0.1%, Yellow if 0.1-1%, Red if > 1%
Alert: If > 1% for 5 minutes
```

**API Response Time**
- Login: [avg response time]
- Upload: [avg response time]
- Moderation: [avg response time]
- Export: [avg response time]

```
Graph Type: Multi-series Line Chart
Time Range: Last 1 hour
Threshold: 1 second (each)
Alert: If any > 2 seconds
```

---

## Dashboard 2: Database Performance

### Metrics to Display

**Query Response Time**
- Current: [avg query time]
- P95: [95th percentile]
- P99: [99th percentile]
- Target: < 500ms average

```
Graph Type: Heatmap
Time Range: Last 1 hour
Slow Query Threshold: 1 second
Alert: If P95 > 2 seconds
```

**Database Connections**
- Current Active: [number]
- Max: [configured max]
- Available: [remaining slots]
- Target: < 70% of max

```
Graph Type: Gauge
Range: 0-100 connections
Color: Green if < 70, Yellow if 70-90, Red if > 90
Alert: If > 80% for 10 minutes
```

**Query Throughput**
- Queries per second: [current]
- Average: [24-hour avg]
- Peak: [peak today]

```
Graph Type: Line Chart
Time Range: Last 24 hours
Alert: If spike > 2x average without explanation
```

**Transaction Wait Time**
- Current: [avg wait]
- Max: [longest wait today]
- Target: < 100ms

```
Graph Type: Line Chart
Time Range: Last 1 hour
Threshold: 100ms (warning), 500ms (critical)
Alert: If > 500ms for 5 minutes
```

**Slow Queries**
- Count: [number running > 1 second]
- Slowest: [query name + time]

```
Graph Type: Table
Time Range: Last 1 hour
Update Interval: Every 30 seconds
Alert: If > 5 slow queries simultaneously
```

**Database CPU**
- Current: [% usage]
- Average: [24-hour avg]
- Target: < 70%

```
Graph Type: Gauge
Color: Green if < 70%, Yellow if 70-85%, Red if > 85%
Alert: If > 80% for 10 minutes
```

**Database Memory**
- Current: [% usage]
- Average: [24-hour avg]
- Target: < 80%

```
Graph Type: Gauge
Color: Green if < 80%, Yellow if 80-90%, Red if > 90%
Alert: If > 85% for 10 minutes
```

---

## Dashboard 3: Cache Performance

### Metrics to Display

**Cache Hit Rate**
- Current: [% of requests hitting cache]
- Target: > 80%

```
Graph Type: Gauge
Color: Green if > 80%, Yellow if 60-80%, Red if < 60%
Alert: If < 70% (indicates cache not working well)
```

**Cache Size**
- Current: [MB used]
- Max: [512 MB]
- Percent full: [%]

```
Graph Type: Gauge
Range: 0-512 MB
Color: Green if < 70%, Yellow if 70-90%, Red if > 90%
Alert: If > 80% (cache getting full)
```

**Cache Operations**
- Gets per second: [number]
- Sets per second: [number]
- Deletes per second: [number]

```
Graph Type: Multi-series Line Chart
Time Range: Last 1 hour
Monitor for: Unusual spikes
```

**Memory Usage by Cache Key**
- Top 10 keys by size
- Show which are using most cache

```
Graph Type: Bar Chart
Time Range: Current snapshot
Action: If single key > 50% of cache, investigate
```

---

## Dashboard 4: Infrastructure Health

### Metrics to Display

**CPU Usage**
- User: [%]
- System: [%]
- I/O Wait: [%]
- Total: [%]
- Target: < 70%

```
Graph Type: Stacked Area Chart
Time Range: Last 24 hours
Threshold: 70% (warning), 85% (critical)
Alert: If > 80% for 30 minutes
```

**Memory Usage**
- Used: [GB]
- Free: [GB]
- Percent Used: [%]
- Target: < 80%

```
Graph Type: Gauge + Line Chart
Range: 0-32 GB (or server's RAM)
Alert: If > 85% for 15 minutes
```

**Disk Usage**
- Root (/): [%]
- Data (/var): [%]
- Backup (/backups): [%]
- Target: All < 80%

```
Graph Type: Three Gauges
Color: Green if < 80%, Yellow if 80-90%, Red if > 90%
Alert: If any > 85% (critical, may block writes)
```

**Network Traffic**
- Inbound: [MB/s]
- Outbound: [MB/s]
- Peak today: [MB/s]

```
Graph Type: Line Chart
Time Range: Last 24 hours
Monitor for: Unusual spikes or sustained high traffic
```

**Disk I/O**
- Read latency: [ms]
- Write latency: [ms]
- Target: < 10ms

```
Graph Type: Line Chart
Time Range: Last 1 hour
Threshold: 10ms (warning), 50ms (critical)
Alert: If > 50ms for 10 minutes
```

---

## Dashboard 5: Application Logs & Errors

### Metrics to Display

**Error Count**
- Last hour: [count]
- Last 24 hours: [count]
- By type: [ERROR, WARNING, NOTICE breakdown]

```
Graph Type: Bar Chart + Pie Chart
Time Range: Last 24 hours
Alert: If ERROR count > 10 in 1 hour
```

**Error Trend**
- Current trend: [increasing/stable/decreasing]
- Most common error: [error type]

```
Graph Type: Line Chart + Table
Time Range: Last 7 days
Action: If increasing trend, investigate
```

**Slow Operation Log**
- Slow queries: [count]
- Slow exports: [count]
- Slow uploads: [count]
- Slow PDFs: [count]

```
Graph Type: Table
Time Range: Last 1 hour
Update: Every 5 minutes
Action: If any > 10 in last hour, investigate
```

**Authentication Events**
- Successful logins: [count]
- Failed logins: [count]
- By user: [top users logged in]

```
Graph Type: Line Chart + Table
Time Range: Last 24 hours
Alert: If failed logins > 20 in 1 hour (possible brute force)
```

---

## Dashboard 6: Business Metrics

### Metrics to Display

**Mark Batches**
- Total created: [count]
- Validated: [count]
- Pending moderation: [count]
- Approved: [count]
- Submitted: [count]

```
Graph Type: Stacked Bar Chart + Pie Chart
Time Range: Last 24 hours
Show: Progress through workflow
```

**User Activity**
- Teachers uploading: [count]
- HODs moderating: [count]
- Admins working: [count]
- Concurrent users: [current number]

```
Graph Type: Multi-series Line Chart
Time Range: Last 24 hours
Show: Peaks during working hours
```

**CSV Upload Success Rate**
- Total uploads: [count]
- Successful: [count]
- Failed: [count]
- Success rate: [%]

```
Graph Type: Gauge + Line Chart
Target: > 95%
Alert: If < 90%
```

**Data Processing**
- Marks processed: [count, last 24h]
- Average marks per upload: [number]
- Largest upload: [size]

```
Graph Type: Table + Statistics
Time Range: Last 24 hours
Show: Volume trends
```

---

## Alert Rules

### Critical Alerts (Page on-call immediately)

```
1. Database connection failed
   - Condition: Cannot connect to database for 1 minute
   - Action: Page on-call engineer
   - Escalation: Call CTO if engineer doesn't respond in 10 minutes

2. Application down
   - Condition: /health endpoint returns non-200 for 2 minutes
   - Action: Page on-call engineer
   - Escalation: Immediate

3. Disk full
   - Condition: Any disk > 95%
   - Action: Page on-call engineer
   - Priority: Immediate (can block writes)

4. CPU sustained high
   - Condition: CPU > 90% for 15 minutes
   - Action: Page on-call engineer
   - Check: Runaway process or unusual load
```

### High Priority Alerts (Email + Slack)

```
1. High memory usage
   - Condition: Memory > 85% for 15 minutes
   - Action: Send alert
   - Response: Check for memory leaks

2. Database performance degradation
   - Condition: Query response time > 2 seconds for 10 minutes
   - Action: Send alert
   - Response: Investigate slow queries

3. High error rate
   - Condition: Error rate > 1% for 10 minutes
   - Action: Send alert
   - Response: Review error logs

4. Cache not working
   - Condition: Cache hit rate < 70%
   - Action: Send alert
   - Response: Investigate cache issues

5. Upload success rate low
   - Condition: Success rate < 90% for 1 hour
   - Action: Send alert
   - Response: Check upload system
```

### Medium Priority Alerts (Slack only)

```
1. Page load time slow
   - Condition: Average > 3 seconds for 15 minutes
   - Action: Slack notification
   - Response: Monitor, investigate if persists

2. Concurrent users high
   - Condition: > 100 concurrent users
   - Action: Slack notification
   - Response: Monitor for issues

3. Disk space low
   - Condition: Any disk > 80%
   - Action: Slack notification
   - Response: Plan cleanup/expansion

4. Slow API endpoints
   - Condition: API response > 1 second
   - Action: Slack notification
   - Response: Monitor

5. Backup failure
   - Condition: Daily backup didn't complete
   - Action: Slack notification
   - Response: Investigate and fix
```

---

## Setting Up Prometheus

### Installation

```bash
# Download and install
wget https://github.com/prometheus/prometheus/releases/download/v2.33.0/prometheus-2.33.0.linux-amd64.tar.gz
tar xvfz prometheus-2.33.0.linux-amd64.tar.gz
sudo mv prometheus-2.33.0.linux-amd64 /opt/prometheus
sudo chown -R prometheus:prometheus /opt/prometheus
```

### Configuration

```yaml
# /etc/prometheus/prometheus.yml

global:
  scrape_interval: 15s
  evaluation_interval: 15s

alerting:
  alertmanagers:
    - static_configs:
        - targets:
            - localhost:9093

rule_files:
  - /etc/prometheus/rules.yml

scrape_configs:
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']

  - job_name: 'node'
    static_configs:
      - targets: ['localhost:9100']

  - job_name: 'postgres'
    static_configs:
      - targets: ['localhost:9187']

  - job_name: 'redis'
    static_configs:
      - targets: ['localhost:9121']

  - job_name: 'laravel'
    static_configs:
      - targets: ['localhost:9090']
    metrics_path: '/metrics'
```

---

## Setting Up Grafana

### Installation

```bash
sudo apt-get install -y grafana-server
sudo systemctl start grafana-server
sudo systemctl enable grafana-server

# Access at: http://localhost:3000
# Default: admin/admin
```

### Create Dashboards

**Via Grafana UI**:
1. Log in to http://localhost:3000
2. Click "Create" → "Dashboard"
3. Add panels with queries
4. Configure alerts
5. Save dashboard

**Or Import JSON**:
1. Get JSON from templates
2. Dashboard → Import → Paste JSON
3. Select Prometheus data source
4. Import

### Key Dashboards to Create

1. **Overview** - High-level system health
2. **Application** - App-specific metrics
3. **Database** - PostgreSQL performance
4. **Infrastructure** - CPU, memory, disk
5. **Business** - User activity, data volume
6. **Errors** - Error tracking and trends
7. **Performance** - Latency and throughput

---

## Health Check Procedures

### Hourly Health Check (Automated)

```bash
#!/bin/bash
# runs every hour

# 1. Application health
curl -s http://localhost/health > /dev/null && echo "APP_OK" || echo "APP_FAIL"

# 2. Database
psql -U irms_app -d irms_production -c "SELECT 1" > /dev/null && echo "DB_OK" || echo "DB_FAIL"

# 3. Cache
redis-cli -a PASSWORD ping | grep PONG > /dev/null && echo "CACHE_OK" || echo "CACHE_FAIL"

# 4. Disk space
df / | awk 'NR==2 {if ($5 < 90) print "DISK_OK"; else print "DISK_WARN"}'

# Report to monitoring system
```

### Daily Health Report (8 AM)

```
System Health Report
Date: [date]

Application:
- Uptime: [hours]
- Error rate: [%]
- Avg response: [ms]
- Peak users: [count]

Database:
- Size: [MB]
- Queries/sec: [avg]
- Avg query time: [ms]
- Slow queries: [count]

Infrastructure:
- CPU avg: [%]
- Memory avg: [%]
- Disk usage: [%]
- Network: [Mbps avg]

Issues identified:
- [Issue 1]
- [Issue 2]

Actions taken:
- [Action 1]
- [Action 2]
```

---

## Performance Baselines

Record at go-live (Friday after deployment):

| Metric | Baseline | Target | Warning | Critical |
|--------|----------|--------|---------|----------|
| Page Load | 1.2s | < 3s | > 3s | > 5s |
| API Response | 200ms | < 1s | > 1s | > 2s |
| Database Query | 150ms | < 1s | > 1s | > 2s |
| Cache Hit Rate | 87% | > 80% | < 80% | < 70% |
| Error Rate | 0.02% | < 0.1% | > 0.1% | > 1% |
| CPU Usage | 45% | < 70% | > 70% | > 85% |
| Memory Usage | 62% | < 80% | > 80% | > 90% |
| Disk Usage | 34% | < 80% | > 80% | > 90% |
| Concurrent Users | [peak 80] | 100 | 200 | 500 |

---

## Troubleshooting via Dashboards

**If metric goes red**:

1. **Check Graph**: What changed? Spike or steady?
2. **Check Time**: When did it start?
3. **Cross-reference**: Did other metrics change?
4. **Check Logs**: Are there errors related?
5. **Investigate**: Use troubleshooting runbook
6. **Resolve**: Apply solution
7. **Document**: Record what happened

---

## Dashboard Access

**Who has access**:
- Operations Team: Full access (create/edit/delete)
- On-Call Engineer: View-only
- Engineering Lead: View-only
- Management: Summary dashboard only

**Sharing**:
- Public dashboards: Summary view
- Team dashboards: Team members only
- On-call dashboards: On-call rotation only

---

## Monitoring Tools Alternatives

If not using Prometheus/Grafana:

**Option 1: New Relic**
- Cloud-hosted monitoring
- No setup needed
- Cost: $$$
- Includes APM, logs, alerts

**Option 2: DataDog**
- Similar to New Relic
- Cloud-hosted
- Cost: $$$
- Very comprehensive

**Option 3: ELK Stack** (Elasticsearch, Logstash, Kibana)
- Self-hosted
- Free (open source)
- More complex setup
- Full control

**Recommended for Phase 5**: Prometheus + Grafana (free, good for our size)

---

## Monitoring Checklist

- [ ] Prometheus installed and running
- [ ] Grafana installed and configured
- [ ] Data source connected (Prometheus)
- [ ] All 7 dashboards created
- [ ] All alert rules configured
- [ ] Alert channels tested (Slack, email, SMS)
- [ ] On-call access verified
- [ ] Team trained on dashboards
- [ ] Baseline metrics recorded
- [ ] Monitoring started before go-live

---

**Status**: ✅ MONITORING SYSTEM READY FOR PRODUCTION

**Next**: Day 1 - Infrastructure Setup → Configure monitoring as part of setup
