import os
import sys
import json
import logging
import subprocess
import platform
import time
from pathlib import Path

import requests # pip install requests
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

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
def start_stream(bord, rtmp, stream_type="ip", rtsp_url=None):
    """
    Starts FFmpeg process based on camera type.
    - ip: RTSP input (copy)
    - usb: Local device input (encode x264)
    - builtin: Auto-detect local device per platform (encode x264)
    """
    logger.info(f"Starting stream for Bord {bord} (Type: {stream_type})")
    
    os_name = platform.system()
    cmd = ['ffmpeg']

    if stream_type == "ip":
        if not rtsp_url:
            logger.error("RTSP URL missing for type 'ip'")
            return None
        # ffmpeg -rtsp_flags prefer_tcp -i RTSP_URL -c copy -f flv RTMP_URL
        cmd += [
            '-rtsp_flags', 'prefer_tcp',
            '-i', rtsp_url,
            '-c', 'copy',
            '-f', 'flv',
            rtmp
        ]
    elif stream_type == "usb":
        # usb: ffmpeg -f v4l2 -i /dev/video0 (Linux) eller -f dshow -i video="..." (Windows) -c:v libx264 -preset ultrafast -f flv RTMP_URL
        if os_name == "Windows":
            cmd += ['-f', 'dshow', '-i', 'video="USB Camera"'] # Default name, usually overridden in real setup or generic
        elif os_name == "Linux":
            cmd += ['-f', 'v4l2', '-i', '/dev/video0']
        else:
            logger.error(f"USB capture not supported on {os_name}")
            return None
        
        cmd += ['-c:v', 'libx264', '-preset', 'ultrafast', '-f', 'flv', rtmp]

    elif stream_type == "builtin":
        # builtin: Linux: -f v4l2 -i /dev/video0. Windows: -f dshow -i video="Integrated Camera". macOS: -f avfoundation -i "0"
        if os_name == "Linux":
            cmd += ['-f', 'v4l2', '-i', '/dev/video0']
        elif os_name == "Windows":
            cmd += ['-f', 'dshow', '-i', 'video="Integrated Camera"']
        elif os_name == "Darwin": # macOS
            cmd += ['-f', 'avfoundation', '-i', '0']
        else:
            logger.error(f"Builtin camera not supported on {os_name}")
            return None
        
        cmd += ['-c:v', 'libx264', '-preset', 'ultrafast', '-f', 'flv', rtmp]
    else:
        logger.error(f"Unknown stream type: {stream_type}")
        return None

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
        response = requests.post(api_url, data=json.dumps(payload), headers={'Content-Type': 'application/json'}, timeout=10, verify=False)
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

    boards = config.get("boards", [1]) 
    
    for bord in boards:
        try:
            url = f"{api_url}?klub={klub}&bord={bord}"
            response = requests.get(url, timeout=10, verify=False)
            data = response.json()
            cmd = data.get("cmd")

            if cmd == "start":
                stream_type = data.get("type", "ip") # Default to 'ip' for backward compatibility
                rtmp = data.get("rtmp")
                rtsp = data.get("rtsp") # Only used if type == 'ip'

                if rtmp:
                    stop_stream(bord)
                    # Pass the necessary parameters based on type
                    proc = start_stream(
                        bord=bord, 
                        rtmp=rtmp, 
                        stream_type=stream_type, 
                        rtsp_url=rtsp
                    )
                    if proc:
                        active_streams[bord] = proc
                        send_status(klub, bord, "running")
                    else:
                        send_status(klub, bord, "error")
                else:
                    logger.warning(f"Start command received for Bord {bord} but RTMP URL is missing.")

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
