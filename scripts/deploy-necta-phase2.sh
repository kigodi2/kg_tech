#!/bin/bash

################################################################################
# NECTA Phase 2 Deployment Script
# Usage: ./scripts/deploy-necta-phase2.sh [production|staging|local]
# 
# Automates deployment of NECTA Phase 2 changes with safety checks.
# Supports: production, staging, local environments.
################################################################################

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
ENVIRONMENT="${1:-local}"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG_FILE="${PROJECT_ROOT}/storage/logs/deployment-$(date +%Y%m%d-%H%M%S).log"

# Color functions
step() {
    echo -e "${BLUE}→${NC} $1"
}

success() {
    echo -e "${GREEN}✓${NC} $1"
}

warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

error() {
    echo -e "${RED}✗${NC} $1" >&2
    log "ERROR: $1"
    exit 1
}

log() {
    mkdir -p "$(dirname "$LOG_FILE")"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

header() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║ NECTA Phase 2 Deployment Script${NC}"
    echo -e "${BLUE}║ Environment: ${ENVIRONMENT}${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

################################################################################
# VALIDATION
################################################################################

header
log "Deployment started for: $ENVIRONMENT"

step "Validating environment..."

# Validate environment argument
if [[ "$ENVIRONMENT" != "production" && "$ENVIRONMENT" != "staging" && "$ENVIRONMENT" != "local" ]]; then
    error "Invalid environment: $ENVIRONMENT. Use: production, staging, or local"
fi

# Check project root
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    error "artisan not found at $PROJECT_ROOT/artisan"
fi

# Check PHP version
PHP_VERSION=$(php -v | head -n1 | grep -oP 'PHP \K[0-9]+\.[0-9]+')
if (( $(echo "$PHP_VERSION < 7.4" | bc -l) )); then
    error "PHP 7.4+ required, found: $PHP_VERSION"
fi
success "PHP version: $PHP_VERSION"

# Check Laravel
if [ ! -d "$PROJECT_ROOT/vendor" ]; then
    error "Vendor directory not found. Run: composer install"
fi
success "Dependencies found"

log "Environment validation passed"

################################################################################
# PRE-DEPLOYMENT
################################################################################

step "Checking Git status..."

cd "$PROJECT_ROOT"

# Check for uncommitted changes
if ! git diff --quiet HEAD 2>/dev/null; then
    if [ "$ENVIRONMENT" = "production" ]; then
        error "Uncommitted changes detected in production. Commit or stash first."
    else
        warn "Uncommitted changes detected"
        echo "  Changes:"
        git diff --name-only HEAD | sed 's/^/    /'
        read -p "Continue anyway? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            error "Deployment cancelled by user"
        fi
    fi
fi

# Check for untracked files
if [ -n "$(git ls-files --others --exclude-standard)" ]; then
    warn "Untracked files present (will not be deployed)"
fi

success "Git status checked"
log "Git status verified"

################################################################################
# DATABASE BACKUP
################################################################################

if [ "$ENVIRONMENT" = "production" ]; then
    step "Creating database backup (production)..."
    
    if php artisan backup:run --only=database 2>/dev/null; then
        BACKUP_FILE=$(ls -t "$PROJECT_ROOT/storage/backups"/*.sql 2>/dev/null | head -1)
        if [ -n "$BACKUP_FILE" ]; then
            success "Database backed up: $(basename "$BACKUP_FILE")"
            log "Database backup: $BACKUP_FILE"
        else
            warn "Backup command ran but file not found. Using manual backup."
            BACKUP_FILE="$PROJECT_ROOT/storage/backups/irms-backup-$(date +%Y%m%d-%H%M%S).sql"
            mkdir -p "$(dirname "$BACKUP_FILE")"
            DB_HOST=$(grep DB_HOST "$PROJECT_ROOT/.env" | cut -d= -f2)
            DB_USER=$(grep DB_USERNAME "$PROJECT_ROOT/.env" | cut -d= -f2)
            DB_PASS=$(grep DB_PASSWORD "$PROJECT_ROOT/.env" | cut -d= -f2)
            DB_NAME=$(grep DB_DATABASE "$PROJECT_ROOT/.env" | cut -d= -f2)
            MYSQL_PWD="$DB_PASS" mysqldump -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" > "$BACKUP_FILE" || error "Backup failed"
            success "Manual backup created: $(basename "$BACKUP_FILE")"
            log "Manual backup: $BACKUP_FILE"
        fi
    else
        error "Database backup failed"
    fi
else
    step "Skipping backup (non-production environment)"
fi

################################################################################
# CODE PULL
################################################################################

step "Pulling latest code..."

if [ "$ENVIRONMENT" = "production" ]; then
    BRANCH="main"
elif [ "$ENVIRONMENT" = "staging" ]; then
    BRANCH="staging"
else
    BRANCH="develop"
fi

if git pull origin "$BRANCH" 2>&1 | tee -a "$LOG_FILE"; then
    COMMIT=$(git rev-parse --short HEAD)
    success "Code pulled from $BRANCH (commit: $COMMIT)"
    log "Code pulled: $COMMIT from $BRANCH"
else
    error "Git pull failed"
fi

################################################################################
# COMPOSER INSTALL
################################################################################

# Check if composer.json changed
if git diff HEAD~1 composer.json >/dev/null 2>&1; then
    step "Installing Composer dependencies (composer.json changed)..."
    
    if [ "$ENVIRONMENT" = "production" ]; then
        if composer install --no-dev --optimize-autoloader 2>&1 | tee -a "$LOG_FILE"; then
            success "Composer dependencies installed (production mode)"
            log "Composer install: production"
        else
            error "Composer install failed"
        fi
    else
        if composer install 2>&1 | tee -a "$LOG_FILE"; then
            success "Composer dependencies installed"
            log "Composer install: standard"
        else
            error "Composer install failed"
        fi
    fi
else
    success "composer.json unchanged, skipping install"
fi

################################################################################
# CACHE CLEARING
################################################################################

step "Clearing caches..."

if php artisan optimize:clear 2>&1 | tee -a "$LOG_FILE"; then
    success "Caches cleared"
    log "Cache: cleared"
else
    warn "Cache clearing had warnings, continuing..."
    log "Cache: clear with warnings"
fi

################################################################################
# MIGRATIONS
################################################################################

if [ "$ENVIRONMENT" = "production" ]; then
    step "Running database migrations..."
    
    if php artisan migrate --force 2>&1 | tee -a "$LOG_FILE"; then
        success "Migrations executed"
        log "Migrations: success"
    else
        error "Migrations failed"
    fi
else
    step "Running migrations (non-production)..."
    if php artisan migrate 2>&1 | tee -a "$LOG_FILE"; then
        success "Migrations executed"
        log "Migrations: success"
    else
        error "Migrations failed"
    fi
fi

################################################################################
# PRODUCTION CACHE BUILDING
################################################################################

if [ "$ENVIRONMENT" = "production" ]; then
    step "Building production caches..."
    
    if php artisan config:cache 2>&1 | tee -a "$LOG_FILE"; then
        success "Config cache built"
    else
        error "Config cache failed"
    fi
    
    if php artisan route:cache 2>&1 | tee -a "$LOG_FILE"; then
        success "Route cache built"
    else
        error "Route cache failed"
    fi
    
    if php artisan view:cache 2>&1 | tee -a "$LOG_FILE"; then
        success "View cache built"
    else
        warn "View cache had issues, continuing..."
    fi
    
    log "Production caches: built"
else
    step "Skipping cache building (non-production)"
fi

################################################################################
# SMOKE TESTS
################################################################################

step "Running smoke tests..."

SMOKE_TEST_SCRIPT="$PROJECT_ROOT/NECTA_SMOKE_TESTS_2026_02_15.php"

if [ ! -f "$SMOKE_TEST_SCRIPT" ]; then
    warn "Smoke test script not found at $SMOKE_TEST_SCRIPT"
    log "Smoke tests: SKIPPED (file not found)"
else
    if php "$SMOKE_TEST_SCRIPT" 2>&1 | tee -a "$LOG_FILE"; then
        success "Smoke tests passed"
        log "Smoke tests: PASSED"
    else
        error "Smoke tests failed. Review output above and logs."
    fi
fi

################################################################################
# COMPLETION
################################################################################

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║ DEPLOYMENT COMPLETE${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo "Deployment Summary:"
echo "  Environment:    $ENVIRONMENT"
echo "  Commit:         $COMMIT"
echo "  Log File:       $LOG_FILE"
if [ "$ENVIRONMENT" = "production" ] && [ -n "$BACKUP_FILE" ]; then
    echo "  Backup:         $BACKUP_FILE"
fi
echo ""

echo "Next Steps:"
if [ "$ENVIRONMENT" = "production" ]; then
    echo "  1. Verify application loads: curl https://<your-domain>/admin"
    echo "  2. Test SCHOOL candidate workflow"
    echo "  3. Test PRIVATE candidate workflow"
    echo "  4. Monitor logs: tail -f $PROJECT_ROOT/storage/logs/laravel.log"
    echo ""
    echo "Rollback (if needed):"
    echo "  git revert HEAD --no-edit && php artisan optimize:clear"
elif [ "$ENVIRONMENT" = "staging" ]; then
    echo "  1. Test in staging environment"
    echo "  2. Monitor for issues"
    echo "  3. Schedule production deployment"
else
    echo "  1. Test locally"
    echo "  2. Run: php artisan serve"
    echo "  3. Navigate to: http://localhost:8000"
fi

echo ""
success "Deployment script completed successfully"
log "Deployment completed successfully"

exit 0
