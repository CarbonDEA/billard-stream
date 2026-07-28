#!/bin/bash
# Billard Stream — Linux installation
# curl -sSL https://wahl-it.dk/billard-stream/install.sh | bash

set -e

echo "🎱 Billard Stream — Installation"
echo "================================="

# Tjek OS
if [[ "$OSTYPE" != "linux-gnu"* ]]; then
    echo "❌ Dette script er kun til Linux."
    exit 1
fi

# Tjek om Python 3 findes
if ! command -v python3 &> /dev/null; then
    echo "📦 Installerer Python 3..."
    if command -v apt &> /dev/null; then
        sudo apt update && sudo apt install -y python3 python3-pip
    elif command -v dnf &> /dev/null; then
        sudo dnf install -y python3 python3-pip
    else
        echo "❌ Kunne ikke installere Python 3. Installér manuelt."
        exit 1
    fi
fi

# Tjek om FFmpeg findes
if ! command -v ffmpeg &> /dev/null; then
    echo "🎬 Installerer FFmpeg..."
    if command -v apt &> /dev/null; then
        sudo apt install -y ffmpeg
    elif command -v dnf &> /dev/null; then
        sudo dnf install -y ffmpeg
    else
        echo "❌ Kunne ikke installere FFmpeg. Installér manuelt."
        exit 1
    fi
fi

# Opret mapper
echo "📁 Opretter mapper..."
sudo mkdir -p /etc/billard-stream
sudo mkdir -p /var/log/billard-stream

# Download klient
echo "📥 Henter stream-client.py..."
sudo curl -sSL "https://www.wahl-it.dk/billard-stream/client/stream-client.py" -o /usr/local/bin/stream-client
sudo chmod +x /usr/local/bin/stream-client

# Installer Python pakker
echo "📦 Installerer Python afhængigheder..."
pip3 install websocket-client pystray Pillow --quiet

# Opret config
if [ ! -f /etc/billard-stream/config.json ]; then
    echo "🔧 Opretter standard config..."
    echo '{"server":"wss://wahl-it.dk/billard-stream/ws","klub":"","token":""}' | sudo tee /etc/billard-stream/config.json > /dev/null
fi

echo ""
echo "✅ Installation gennemført!"
echo ""
echo "📝 Næste skridt:"
echo "   1. Log ind på https://wahl-it.dk/billard-stream/"
echo "   2. Indstil dine kameraer og YouTube stream keys"
echo "   3. Redigér /etc/billard-stream/config.json med dine klub-oplysninger"
echo "   4. Kør: stream-client"
echo ""
