#!/bin/bash
MYSQLD=/nix/store/s2lbn1axpc79kwnc829k5idkwabfq459-mysql-8.0.42/bin/mysqld
MYSQL_DIR=/home/runner/workspace/.mysql

# Clean up old socket files if MySQL isn't running
if [ ! -f "$MYSQL_DIR/run/mysql.pid" ] || ! kill -0 $(cat "$MYSQL_DIR/run/mysql.pid" 2>/dev/null) 2>/dev/null; then
    rm -f "$MYSQL_DIR/run/mysql.sock" "$MYSQL_DIR/run/mysql.sock.lock" "$MYSQL_DIR/run/mysql.pid"
fi

# Only start if not already running
if [ -f "$MYSQL_DIR/run/mysql.pid" ] && kill -0 $(cat "$MYSQL_DIR/run/mysql.pid" 2>/dev/null) 2>/dev/null; then
    echo "MySQL already running with PID $(cat $MYSQL_DIR/run/mysql.pid)"
else
    echo "Starting MySQL..."
    $MYSQLD --defaults-file=$MYSQL_DIR/my.cnf --daemonize 2>&1
    sleep 3
    if [ -f "$MYSQL_DIR/run/mysql.pid" ]; then
        echo "MySQL started with PID $(cat $MYSQL_DIR/run/mysql.pid)"
    else
        echo "MySQL failed to start, check $MYSQL_DIR/logs/error.log"
        cat "$MYSQL_DIR/logs/error.log" | tail -10
    fi
fi
