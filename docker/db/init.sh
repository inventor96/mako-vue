#!/bin/bash
set -e

echo "Initializing database '${MARIADB_DATABASE}'..."

mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" <<-EOSQL
  -- Ensure we are in the right database
  CREATE DATABASE IF NOT EXISTS \`${MARIADB_DATABASE}\`;
  USE \`${MARIADB_DATABASE}\`;

  -- Make sure the app and migration users exists
  CREATE USER IF NOT EXISTS '${MARIADB_USER}'@'%' IDENTIFIED BY '${MARIADB_PASSWORD}';
  CREATE USER IF NOT EXISTS '${MARIADB_MIGRATION_USER}'@'%' IDENTIFIED BY '${MARIADB_MIGRATION_PASSWORD}';

  -- Strip any lingering privileges and reapply minimal required ones
  REVOKE ALL PRIVILEGES, GRANT OPTION FROM '${MARIADB_USER}'@'%';
  GRANT SELECT, INSERT, UPDATE, DELETE
    ON \`${MARIADB_DATABASE}\`.*
    TO '${MARIADB_USER}'@'%';
  REVOKE ALL PRIVILEGES, GRANT OPTION FROM '${MARIADB_MIGRATION_USER}'@'%';
  GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
    ON \`${MARIADB_DATABASE}\`.*
    TO '${MARIADB_MIGRATION_USER}'@'%';
  FLUSH PRIVILEGES;

  -- Bootstrap the migrations table
  CREATE TABLE IF NOT EXISTS \`mako_migrations\` (
    \`batch\` int(10) unsigned NOT NULL,
    \`package\` varchar(255) DEFAULT NULL,
    \`version\` varchar(255) NOT NULL
  );
EOSQL

echo "✅ Database '${MARIADB_DATABASE}' initialized and privileges tightened."
