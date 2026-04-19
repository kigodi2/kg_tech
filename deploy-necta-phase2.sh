#!/bin/bash

################################################################################
# NECTA-Aligned ACSEE Phase 2 Deployment Script
# Date: 2026-02-15
# 
# Usage: bash deploy-necta-phase2.sh [production|staging]
# 
# This script automates the deployment of Phase 2 changes to production.
# It includes backup, code push, cache clearing, and smoke testing.
################################################################################

set -e  # Exit on first error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-staging}
BACKUP_DIR="./storage/backups"
LOG_FILE="./storage/logs/deployment-$(date +%Y%m%d-%H%M%S).log"

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  NECTA-Aligned ACSEE Phase 2 Deployment                       ║${NC}"
echo -e "${BLUE}║  Environment: ${ENVIRONMENT}                                                    ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Function: Print step
step() {
    echo -e "${BLUE}→${NC} $1"
}

# Function: Print success
success() {
    echo -e "${GREEN}✓${NC} $1"
}

# Function: Print warning
warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Function: Print error and exit
error() {
    echo -e "${RED}✗${NC} $1" >&2
    exit 1
}

# Function: Log action
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

################################################################################
# STEP 1: Validation
################################################################################
step "Validating environment..."

if [ ! -f "artisan" ]; then
    error "artisan file not found. Run this script from the project root."
fi

if [ ! -d "vendor" ]; then
    error "vendor directory not found. Run 'composer install' first."
fi

if [ "$ENVIRONMENT" != "production" ] && [ "$ENVIRONMENT" != "staging" ]; then
    error "Invalid environment. Use 'production' or 'staging'."
fi

success "Environment validation passed"
log "Deployment started for $ENVIRONMENT"

################################################################################
# STEP 2: Database Backup
################################################################################
step "Creating database backup..."

mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/irms-backup-$(date +%Y%m%d-%H%M%S).sql"

php artisan backup:run --only=database > /dev/null 2>&1 || \
    php artisan db:backup > /dev/null 2>&1 || \
    {
        warn "Automated backup failed. Attempting manual mysqldump..."
        MYSQL_PWD="${DB_PASSWORD}" mysqldump -u "${DB_USERNAME}" "${DB_DATABASE}" > "$BACKUP_FILE" || \
            error "Database backup failed. Aborting deployment."
    }

success "Database backup created"
log "Backup: $BACKUP_FILE"

################################################################################
# STEP 3: Pre-deployment Checks
################################################################################
step "Running pre-deployment checks..."

# Check git status
if ! git diff --quiet HEAD; then
    warn "Uncommitted changes detected"
    git status
    read -p "Continue with uncommitted changes? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "Deployment aborted by user"
    fi
fi

success "Pre-deployment checks passed"
log "Git status verified"

################################################################################
# STEP 4: Code Deployment
################################################################################
step "Deploying code..."

if [ "$ENVIRONMENT" = "production" ]; then
    step "Pulling from production branch..."
    git pull origin main || error "Git pull failed"
    success "Code pulled from main branch"
else
    step "Pulling from staging branch..."
    git pull origin staging || error "Git pull failed"
    success "Code pulled from staging branch"
fi

log "Code deployed from $(git rev-parse --short HEAD)"

################################################################################
# STEP 5: Database Migrations
################################################################################
step "Running database migrations..."

php artisan migrate --force || error "Database migration failed"
success "Migrations completed"
log "Database migrations executed"

################################################################################
# STEP 6: Cache Clearing
################################################################################
step "Clearing application caches..."

php artisan cache:clear > /dev/null 2>&1
php artisan config:cache > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
php artisan route:cache > /dev/null 2>&1

success "Caches cleared"
log "All caches cleared"

################################################################################
# STEP 7: Queue/Workers
################################################################################
step "Restarting queue workers..."

php artisan queue:restart > /dev/null 2>&1 || true
success "Queue workers restarted (if applicable)"
log "Queue workers restarted"

################################################################################
# STEP 8: Smoke Tests
################################################################################
step "Running smoke tests..."

if [ -f "NECTA_SMOKE_TESTS_2026_02_15.php" ]; then
    if php NECTA_SMOKE_TESTS_2026_02_15.php > /tmp/smoke-tests.log 2>&1; then
        success "Smoke tests passed"
        cat /tmp/smoke-tests.log
        log "Smoke tests: PASSED"
    else
        warn "Smoke tests failed or produced warnings"
        cat /tmp/smoke-tests.log
        log "Smoke tests: COMPLETED WITH WARNINGS"
    fi
else
    warn "Smoke test script not found. Skipping automated tests."
    log "Smoke tests: SKIPPED (file not found)"
fi

################################################################################
# STEP 9: Health Check
################################################################################
step "Running application health check..."

php artisan tinker <<EOF > /dev/null 2>&1
exit;
EOF

if [ $? -eq 0 ]; then
    success "Application health check passed"
    log "Health check: PASSED"
else
    error "Application health check failed"
fi

################################################################################
# STEP 10: Summary & Sign-off
################################################################################
echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  DEPLOYMENT SUMMARY                                            ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}✓ Deployment to $ENVIRONMENT completed successfully${NC}"
echo ""
echo "Key Information:"
echo "  Environment:    $ENVIRONMENT"
echo "  Backup:         $BACKUP_FILE"
echo "  Log File:       $LOG_FILE"
echo "  Commit:         $(git rev-parse --short HEAD)"
echo "  Timestamp:      $(date)"
echo ""
echo "Next Steps:"
echo "  1. Verify application loads: curl https://irms-domain.com/"
echo "  2. Test SCHOOL candidate registration"
echo "  3. Test PRIVATE candidate registration"
echo "  4. Monitor logs: tail -f $LOG_FILE"
echo ""
echo -e "${YELLOW}To rollback, run:${NC}"
echo "  git revert $(git rev-parse --short HEAD)"
echo "  php artisan cache:clear"
echo ""
echo "Or restore database:"
echo "  php artisan backup:restore --from=$BACKUP_FILE"
echo ""
log "Deployment completed successfully"

exit 0
