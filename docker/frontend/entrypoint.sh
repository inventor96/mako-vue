#!/bin/bash
set -e

# If we're running npm run dev, double check that npm dependencies are installed
if [ "$1" = "npm" ] && [ "$2" = "run" ] && [ "$3" = "dev" ]; then
  if [ ! -d "node_modules" ]; then
    if [ -f "package-lock.json" ]; then
      echo "Node modules not found. Installing with npm ci..."
      npm ci
    else
      echo "Node modules not found. Installing with npm install..."
      npm install
    fi
  fi
fi

# Hand off to whatever command was passed
exec "$@"