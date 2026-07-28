import os
import sys
import json
import logging
import subprocess
import platform
import time
from pathlib import Path

import requests # pip install requests

# --- Configuration & Paths ---
APP_NAME = "BillardStream"

def get_paths():
    # Config and log are placed in the same directory as the script for simplicity and portability
    base_dir = Path(sys.argv[0]).parent.absolute()
    return {
        "config": base_dir / "config.json", 
        "log": base_dir / "stream-client.log"
    }

paths = get_paths()

# --- Logging Setup ---
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler(paths["log"]),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

# --- Global State ---
active_streams = {} # bord_id -> subprocess.Popen

def load_config():
    try:
        if paths["config"].exists():
            with open(paths["config"], 'r') as f:
                return json.load(f)
    except Exception as e:
        logger.error(f"Error loading config: {e}")
    return {}

# --- FFmpeg Logic ---
def start_stream(bord, rtsp, rtmp):
    \"\"\"
    Starts FFmpeg process to push RTSP stream to RTMP destination.
    Using flags as requested: -rtsp_flags prefer_tcp, -c copy, -f flv.
    \"\"\"
    logger.info(f"Starting stream for Bord {bord}")
    
    cmd = [
        'ffmpeg',
        '-rtsp_flags', 'prefer_tcp',
        '-i', rtsp,
        '-c', 'copy',
        '-f', 'flv',
        rtmp
    ]
    
    try:
        process = subprocess.Popen(
            cmd, 
            stdout=subprocess.DEVNULL, 
            stderr=subprocess.PIPE, 
            text=True
        )
        return process
    except Exception as e:
        logger.error(f"Failed to launch FFmpeg for bord {bord}: {e}")
        return None

def stop_stream(bord):
    global active_streams
    if bord in active_streams:
        proc = active_streams[bord]
        logger.info(f"Stopping stream for Bord {bord}")
        proc.terminate()
        try:
            proc.wait(timeout=5)
        except subprocess.TimeoutExpired:
            proc.kill()
        del active_streams[bord]

# --- Polling Logic ---
def send_status(klub, bord, status):
    config = load_config()
    api_url = config.get("api_url", "https://www.wahl-it.dk/billard-stream/api/command.php")
    payload = {
        "klub": klub,
        "bord": bord,
        "status": status
    }
    try:
        # Using requests for cross-platform simplicity instead of raw curl subprocesses
        response = requests.post(api_url, data=json.dumps(payload), headers={'Content-Type': 'application/json'}, timeout=10)
        logger.info(f"Status update sent for Bord {bord}: {status} (HTTP {response.status_code})")
    except Exception as e:
        logger.error(f"Error sending status for Bord {bord}: {e}")

def poll_commands():
    global active_streams
    config = load_config()
    klub = config.get("klub")
    api_url = config.get("api_url", "https://www.wahl-it.dk/billard-stream/api/command.php")
    
    if not klub:
        logger.error("No 'klub' defined in config.json. Polling skipped.")
        return

    # In a real scenario, we might loop through all registered boards. 
    # For this task, we follow the requested pattern for bord=1 as example or iterate based on config.
    boards = config.get("boards", [1]) # Default to board 1 if none specified in config
    
    for bord in boards:
        try:
            url = f"{api_url}?klub={klub}&bord={bord}"
            response = requests.get(url, timeout=10)
            data = response.json()
            cmd = data.get("cmd")

            if cmd == "start":
                rtsp = data.get("rtsp")
                rtmp = data.get("rtmp")
                if rtsp and rtmp:
                    stop_stream(bord)
                    proc = start_stream(bord, rtsp, rtmp)
                    if proc:
                        active_streams[bord] = proc
                        send_status(klub, bord, "running")
                    else:
                        send_status(klub, bord, "error")
                else:
                    logger.warning(f"Start command received for Bord {bord} but RTSP/RTMP URLs are missing.")

            elif cmd == "stop":
                if bord in active_streams:
                    stop_stream(bord)
                    send_status(klub, bord, "stopped")
                else:
                    logger.info(f"Stop command received for Bord {bord}, but it was not running.")

        except Exception as e:
            logger.error(f"Error polling for Bord {bord}: {e}")

# --- Main Entry point ---
if __name__ == "__main__":
    logger.info(f"Starting {APP_NAME} client on {platform.system()} (Polling Mode)...")
    
    # Check if config exists, create a template if not
    if not paths["config"].exists():
        default_config = {
            "klub": "KLUB",
            "api_url": "https://www.wahl-it.dk/billard-stream/api/command.php",
            "boards": [1]
        }
        with open(paths["config"], 'w') as f:
            json.dump(default_config, f, indent=4)
        logger.info(f"Created default config at {paths['config']}")

    try:
        while True:
            poll_commands()
            time.sleep(30)
    except KeyboardInterrupt:
        logger.info("Shutting down...")
        for bord in list(active_streams.keys()):
            stop_stream(bord)
        sys.exit(0)
