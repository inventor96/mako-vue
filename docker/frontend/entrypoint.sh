#!/bin/bash
set -e

# If we're running npm run dev, double check that npm dependencies are installed
if [ "$1" = "npm" ] && [ "$2" = "run" ] && [ "$3" = "dev" ]; then
  if [ ! -d "node_modules" ]; then
    echo "Node modules not found. Installing..."
    npm install
  fi
fi

# Hand off to whatever command was passed
exec "$@"