#!/bin/bash

echo "Starting Aviator Socket.IO Server..."
echo ""

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "ERROR: Node.js is not installed"
    echo "Please install Node.js from https://nodejs.org/"
    exit 1
fi

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to install dependencies"
        exit 1
    fi
fi

echo ""
echo "Socket.IO Server Configuration:"
echo "- Port: 3000 (default)"
echo "- CORS: Enabled for all origins"
echo ""
echo "Starting server..."
echo ""

node socket-server.js
