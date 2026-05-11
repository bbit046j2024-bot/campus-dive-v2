#!/bin/bash
set -e

MYSQLD=/nix/store/s2lbn1axpc79kwnc829k5idkwabfq459-mysql-8.0.42/bin/mysqld
MYSQL=/nix/store/s2lbn1axpc79kwnc829k5idkwabfq459-mysql-8.0.42/bin/mysql
MYSQL_DIR=/home/runner/workspace/.mysql

# ── 1. Initialize MySQL data directory if needed ──────────────────────────────
if [ ! -d "$MYSQL_DIR/data/mysql" ]; then
    echo "[start.sh] Initializing MySQL data directory..."
    mkdir -p "$MYSQL_DIR/data" "$MYSQL_DIR/run" "$MYSQL_DIR/logs"
    $MYSQLD --initialize-insecure --datadir="$MYSQL_DIR/data" --user=runner 2>&1
fi

mkdir -p "$MYSQL_DIR/run" "$MYSQL_DIR/logs"

# ── 2. Write my.cnf ───────────────────────────────────────────────────────────
cat > "$MYSQL_DIR/my.cnf" << EOF
[mysqld]
datadir=$MYSQL_DIR/data
socket=$MYSQL_DIR/run/mysql.sock
pid-file=$MYSQL_DIR/run/mysql.pid
log-error=$MYSQL_DIR/logs/error.log
port=3306
bind-address=127.0.0.1
mysqlx=OFF
user=runner
[mysql]
socket=$MYSQL_DIR/run/mysql.sock
[client]
socket=$MYSQL_DIR/run/mysql.sock
EOF

# ── 3. Start MySQL if not already running ─────────────────────────────────────
if [ -f "$MYSQL_DIR/run/mysql.pid" ] && kill -0 "$(cat "$MYSQL_DIR/run/mysql.pid" 2>/dev/null)" 2>/dev/null; then
    echo "[start.sh] MySQL already running (PID $(cat "$MYSQL_DIR/run/mysql.pid"))"
else
    rm -f "$MYSQL_DIR/run/mysql.sock" "$MYSQL_DIR/run/mysql.sock.lock" "$MYSQL_DIR/run/mysql.pid"
    echo "[start.sh] Starting MySQL..."
    $MYSQLD --defaults-file="$MYSQL_DIR/my.cnf" --daemonize
    # Wait up to 30s for MySQL to be ready
    for i in $(seq 1 30); do
        if $MYSQL --defaults-file="$MYSQL_DIR/my.cnf" -u root -e "SELECT 1;" > /dev/null 2>&1; then
            echo "[start.sh] MySQL ready (attempt $i)"
            break
        fi
        sleep 1
    done
fi

# ── 4. Create DB and load schema if needed ────────────────────────────────────
DB_EXISTS=$($MYSQL --defaults-file="$MYSQL_DIR/my.cnf" -u root -e "SHOW DATABASES LIKE 'campus_recruitment';" 2>/dev/null | grep -c campus_recruitment || echo 0)
if [ "$DB_EXISTS" -eq 0 ]; then
    echo "[start.sh] Loading database schema..."
    $MYSQL --defaults-file="$MYSQL_DIR/my.cnf" -u root < /home/runner/workspace/setup_localhost.sql 2>&1 || echo "[start.sh] Schema load had warnings (may be OK)"
    echo "[start.sh] Database schema loaded."
else
    echo "[start.sh] Database campus_recruitment already exists, skipping schema load."
fi

# ── 5. Create .env if missing ─────────────────────────────────────────────────
if [ ! -f /home/runner/workspace/.env ]; then
    cat > /home/runner/workspace/.env << EOF
MYSQLHOST=127.0.0.1
MYSQLUSER=root
MYSQLPASSWORD=
MYSQLDATABASE=campus_recruitment
MYSQLPORT=3306
MYSQL_SOCKET=$MYSQL_DIR/run/mysql.sock
APP_ENV=development
APP_DEBUG=true
EOF
    echo "[start.sh] Created .env"
fi

# ── 6. Create api/.env if missing ─────────────────────────────────────────────
if [ ! -f /home/runner/workspace/api/.env ]; then
    cp /home/runner/workspace/.env /home/runner/workspace/api/.env 2>/dev/null || true
fi

# ── 7. Ensure uploads directories exist ───────────────────────────────────────
mkdir -p /home/runner/workspace/uploads /home/runner/workspace/api/uploads

# ── 8. Start PHP backend server on port 8000 ─────────────────────────────────
echo "[start.sh] Starting PHP backend server on port 8000..."
php -S 0.0.0.0:8000 -t /home/runner/workspace /home/runner/workspace/index.php &
PHP_PID=$!
echo "[start.sh] PHP server PID: $PHP_PID"

# ── 9. Start Vite frontend on port 5000 ───────────────────────────────────────
echo "[start.sh] Starting Vite frontend on port 5000..."
cd /home/runner/workspace/frontend
npm run dev

# Cleanup on exit
kill $PHP_PID 2>/dev/null || true
