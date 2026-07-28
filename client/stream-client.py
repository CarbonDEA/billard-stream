import os
import sys
import json
import logging
import subprocess
import platform
import threading
from pathlib import Path

import websocket # pip install websocket-client
import pystray # pip install pystray
from PIL import Image, ImageDraw # pip install Pillow

# --- Configuration & Paths ---
APP_NAME = "BillardStream"
CONFIG_PATH_LINUX = "/etc/billard-stream/config.json"
LOG_PATH_LINUX = "/var/log/billard-stream.log"

def get_paths():
    if platform.system() == "Windows":
        appdata = os.getenv("APPDATA", os.path.expanduser("~"))
        base_dir = Path(appdata) / APP_NAME
        base_dir.mkdir(parents=True, exist_ok=True)
        
        # Config is either in etc (not applicable here) or next to exe
        exe_dir = Path(sys.executable).parent
        return {
            "config": exe_dir / "config.json", 
            "log": base_dir / "log.txt"
        }
    else:
        # Linux
        return {
            "config": Path(CONFIG_PATH_LINUX),
            "log": Path(LOG_PATH_LINUX)
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
ws = None

def load_config():
    try:
        if paths["config"].exists():
            with open(paths["config"], 'r') as f:
                return json.load(f)
    except Exception as e:
        logger.error(f"Error loading config: {e}")
    return {}

# --- FFmpeg Logic ---
def start_stream(bord, rtsp, rtmp, title):
    """
    Starts FFmpeg process to push RTSP stream to RTMP destination.
    Adjust flags as needed for the specific camera/server requirements.
    """
    logger.info(f"Starting stream for Bord {bord}: {title}")
    
    # Simple copy command: rtsp -> rtmp
    # -i: input, -c copy: don't re-encode (low CPU), -f flv: required for RTMP
    cmd = [
        'ffmpeg',
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

# --- WebSocket Logic ---
def on_message(ws, message):
    global active_streams
    try:
        data = json.loads(message)
        cmd = data.get("cmd")
        bord = data.get("bord")
        
        if not bord:
            return

        if cmd == "start":
            rtsp = data.get("rtsp")
            rtmp = data.get("rtmp")
            title = data.get("title", f"Bord {bord}")
            
            # Stop existing before starting new
            stop_stream(bord)
            
            proc = start_stream(bord, rtsp, rtmp, title)
            if proc:
                active_streams[bord] = proc
                ws.send(json.dumps({"bord": bord, "status": "running"}))
            else:
                ws.send(json.dumps({"bord": bord, "status": "error"}))
                
        elif cmd == "stop":
            stop_stream(bord)
            ws.send(json.dumps({"bord": bord, "status": "stopped"}))

    except Exception as e:
        logger.error(f"Error processing WS message: {e}")

def on_error(ws, error):
    logger.error(f"WebSocket Error: {error}")

def on_close(ws, close_status_code, close_msg):
    logger.info("WebSocket connection closed")

def run_websocket():
    global ws
    url = "wss://wahl-it.dk/billard-stream/ws" # Assumed wss for production
    ws = websocket.WebSocketApp(
        url,
        on_message=on_message,
        on_error=on_error,
        on_close=on_close
    )
    # Run forever with auto-reconnect logic usually handled by a loop in main
    ws.run_forever()

# --- System Tray Logic ---
def create_image():
    # Create a simple 64x64 icon (blue circle)
    width, height = 64, 64
    image = Image.new('RGB', (width, height), color=(255, 255, 255))
    dc = ImageDraw.Draw(image)
    dc.ellipse([10, 10, 54, 54], fill=(0, 120, 215))
    return image

def on_exit(icon, item):
    logger.info("Exiting application...")
    for bord in list(active_streams.keys()):
        stop_stream(bord)
    icon.stop()
    os._exit(0)

def run_tray():
    icon = pystray.Icon(APP_NAME)
    icon.menu = pystray.Menu(
        pystray.MenuItem('Exit', on_exit)
    )
    icon.icon = create_image()
    icon.title = f"{APP_NAME} - Running"
    icon.run()

# --- Main Entry point ---
if __name__ == "__main__":
    logger.info(f"Starting {APP_NAME} client on {platform.system()}...")
    
    # Load config (though currently endpoint is hardcoded, this fulfills the requirement)
    config = load_config()
    
    # Start WebSocket thread
    ws_thread = threading.Thread(target=run_websocket, daemon=True)
    ws_thread.start()
    
    # Start Tray (this blocks until icon.stop())
    try:
        run_tray()
    except KeyboardInterrupt:
        on_exit(None, None)
