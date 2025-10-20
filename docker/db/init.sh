#!/bin/bash
set -e

echo "Initializing database '${MYSQL_DATABASE}'..."

mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
  -- Ensure we are in the right database
  CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}\`;
  USE \`${MYSQL_DATABASE}\`;

  -- Make sure the app and migration users exists
  CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
  CREATE USER IF NOT EXISTS '${MYSQL_MIGRATION_USER}'@'%' IDENTIFIED BY '${MYSQL_MIGRATION_PASSWORD}';

  -- Strip any lingering privileges and reapply minimal required ones
  REVOKE ALL PRIVILEGES, GRANT OPTION FROM '${MYSQL_USER}'@'%';
  GRANT SELECT, INSERT, UPDATE, DELETE
    ON \`${MYSQL_DATABASE}\`.*
    TO '${MYSQL_USER}'@'%';
  REVOKE ALL PRIVILEGES, GRANT OPTION FROM '${MYSQL_MIGRATION_USER}'@'%';
  GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
    ON \`${MYSQL_DATABASE}\`.*
    TO '${MYSQL_MIGRATION_USER}'@'%';
  FLUSH PRIVILEGES;

  -- Bootstrap the migrations table
  CREATE TABLE IF NOT EXISTS \`mako_migrations\` (
    \`batch\` int(10) unsigned NOT NULL,
    \`package\` varchar(255) DEFAULT NULL,
    \`version\` varchar(255) NOT NULL
  );
EOSQL

echo "✅ Database '${MYSQL_DATABASE}' initialized and privileges tightened."
