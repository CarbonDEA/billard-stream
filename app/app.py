#!/usr/bin/env python3
"""
Billard Stream — Lokal GUI App
Streaming til YouTube fra USB/indbygget kamera.
"""
import tkinter as tk
from tkinter import ttk, messagebox
import subprocess, threading, json, os, time, platform, urllib.request, urllib.error

VERSION = "1.0.0"
API_URL = "https://www.wahl-it.dk/billard-stream/api/license.php"
CONFIG_DIR = os.path.expanduser("~/.billard-stream")
CONFIG_FILE = os.path.join(CONFIG_DIR, "config.json")

class BillardApp:
    def __init__(self, root):
        self.root = root
        self.root.title(f"Billard Stream v{VERSION}")
        self.root.geometry("750x550")
        self.root.configure(bg="#0a0a0a")
        self.root.resizable(False, False)
        
        self.ffmpeg_proc = None
        self.config = self.load_config()
        self.license_valid = False
        
        self.build_ui()
        self.check_license_on_startup()
    
    def build_ui(self):
        # --- Mørkt tema ---
        bg, fg, accent = "#0a0a0a", "#e0e0e0", "#00ff41"
        entry_bg, btn_bg = "#1a1a1a", "#00ff41"
        
        style = ttk.Style()
        style.theme_use("clam")
        style.configure("TFrame", background=bg)
        style.configure("TLabelframe", background=bg, foreground=fg)
        style.configure("TLabel", background=bg, foreground=fg)
        style.configure("TButton", background=accent, foreground="#0a0a0a", borderwidth=0)
        style.map("TButton", background=[("active", "#00cc33")])
        
        # --- Top: Licens + Version ---
        top = tk.Frame(self.root, bg=bg)
        top.pack(fill="x", padx=15, pady=(15,5))
        tk.Label(top, text="🎱 Billard Stream", font=("Arial", 16, "bold"), 
                 fg=accent, bg=bg).pack(side="left")
        tk.Label(top, text=f"v{VERSION}", font=("Arial", 9), 
                 fg="#666", bg=bg).pack(side="left", padx=10)
        
        # --- Licens frame ---
        lf = tk.LabelFrame(self.root, text="🔑 Licens", fg=accent, bg=bg, 
                          font=("Arial", 10), padx=10, pady=10)
        lf.pack(fill="x", padx=15, pady=5)
        row = tk.Frame(lf, bg=bg)
        row.pack(fill="x")
        tk.Label(row, text="Licensnøgle:", fg=fg, bg=bg, font=("Arial", 9)).pack(side="left")
        self.license_entry = tk.Entry(row, width=30, bg=entry_bg, fg=fg, 
                                      insertbackground=fg, relief="flat", font=("Arial", 9))
        self.license_entry.pack(side="left", padx=10, ipady=3)
        if self.config.get("license_key"):
            self.license_entry.insert(0, self.config["license_key"])
        self.license_btn = tk.Button(row, text="✅ Valider", command=self.validate_license,
                                     bg=btn_bg, fg="#0a0a0a", font=("Arial", 8, "bold"),
                                     relief="flat", padx=10, cursor="hand2")
        self.license_btn.pack(side="left")
        self.license_status = tk.Label(row, text="⚠️ Ikke valideret", fg="#ff4444", bg=bg, font=("Arial", 8))
        self.license_status.pack(side="left", padx=10)
        
        # --- Stream kontrol frame ---
        sf = tk.LabelFrame(self.root, text="📡 Stream", fg=accent, bg=bg,
                          font=("Arial", 10), padx=10, pady=5)
        sf.pack(fill="x", padx=15, pady=5)
        
        # Kamera type
        r1 = tk.Frame(sf, bg=bg)
        r1.pack(fill="x", pady=2)
        tk.Label(r1, text="Kameratype:", fg=fg, bg=bg, font=("Arial", 9), width=14, anchor="w").pack(side="left")
        self.cam_type = ttk.Combobox(r1, values=["Indbygget", "USB Kamera", "IP Kamera"], 
                                      state="readonly", width=20, font=("Arial", 9))
        self.cam_type.set("Indbygget")
        self.cam_type.pack(side="left", padx=5)
        self.cam_type.bind("<<ComboboxSelected>>", self.on_cam_type_change)
        
        # Stream key
        r2 = tk.Frame(sf, bg=bg)
        r2.pack(fill="x", pady=2)
        tk.Label(r2, text="Stream key:", fg=fg, bg=bg, font=("Arial", 9), width=14, anchor="w").pack(side="left")
        self.key_entry = tk.Entry(r2, width=40, bg=entry_bg, fg=fg, 
                                  insertbackground=fg, relief="flat", font=("Arial", 9))
        self.key_entry.pack(side="left", padx=5, ipady=3)
        if self.config.get("stream_key"):
            self.key_entry.insert(0, self.config["stream_key"])
        
        # RTMP URL (auto-genereret)
        r3 = tk.Frame(sf, bg=bg)
        r3.pack(fill="x", pady=2)
        tk.Label(r3, text="RTMP URL:", fg=fg, bg=bg, font=("Arial", 9), width=14, anchor="w").pack(side="left")
        self.rtmp_label = tk.Label(r3, text="rtmp://a.rtmp.youtube.com/live2/XXXX", 
                                    fg="#888", bg=bg, font=("Arial", 8))
        self.rtmp_label.pack(side="left", padx=5)
        
        # IP/Device (vises ved IP eller USB)
        r4 = tk.Frame(sf, bg=bg)
        r4.pack(fill="x", pady=2)
        self.ip_label = tk.Label(r4, text="RTSP URL:", fg=fg, bg=bg, font=("Arial", 9), width=14, anchor="w")
        self.ip_entry = tk.Entry(r4, width=40, bg=entry_bg, fg=fg,
                                  insertbackground=fg, relief="flat", font=("Arial", 9))
        
        # --- Knapper ---
        btn_frame = tk.Frame(self.root, bg=bg)
        btn_frame.pack(fill="x", padx=15, pady=10)
        self.start_btn = tk.Button(btn_frame, text="▶ START STREAM", 
                                    command=self.start_stream,
                                    bg=btn_bg, fg="#0a0a0a", font=("Arial", 11, "bold"),
                                    relief="flat", padx=20, pady=8, cursor="hand2")
        self.start_btn.pack(side="left", padx=5)
        self.stop_btn = tk.Button(btn_frame, text="⏹ STOP", 
                                   command=self.stop_stream,
                                   bg="#441111", fg="#ff4444", font=("Arial", 11, "bold"),
                                   relief="flat", padx=20, pady=8, cursor="hand2", state="disabled")
        self.stop_btn.pack(side="left", padx=5)
        self.status_label = tk.Label(btn_frame, text="● Slukket", fg="#888", bg=bg, font=("Arial", 10, "bold"))
        self.status_label.pack(side="right", padx=10)
        
        # --- Log panel ---
        log_frame = tk.LabelFrame(self.root, text="📋 Log", fg=accent, bg=bg,
                                  font=("Arial", 10), padx=5, pady=5)
        log_frame.pack(fill="both", expand=True, padx=15, pady=(5,15))
        self.log_text = tk.Text(log_frame, height=10, bg="#111", fg="#ccc",
                                 font=("Consolas", 9), relief="flat", wrap="word")
        self.log_text.pack(fill="both", expand=True)
        scroll = tk.Scrollbar(self.log_text, command=self.log_text.yview)
        scroll.pack(side="right", fill="y")
        self.log_text.config(yscrollcommand=scroll.set)
        
        # Deaktiver start hvis ingen licens
        self.update_buttons()
    
    def log(self, msg):
        self.log_text.insert("end", f"{time.strftime('%H:%M:%S')} {msg}\n")
        self.log_text.see("end")
        self.root.update()
    
    def on_cam_type_change(self, event=None):
        t = self.cam_type.get()
        self.ip_label.pack_forget()
        self.ip_entry.pack_forget()
        if t == "IP Kamera":
            self.ip_label.pack(side="left")
            self.ip_entry.pack(side="left", padx=5, ipady=3)
            self.ip_label.config(text="RTSP URL:")
        elif t == "USB Kamera":
            self.ip_label.pack(side="left")
            self.ip_entry.pack(side="left", padx=5, ipady=3)
            self.ip_label.config(text="Enhedssti:")
            self.ip_entry.delete(0, "end")
            self.ip_entry.insert(0, "/dev/video0" if platform.system() != "Windows" else "video=USB Camera")
    
    def load_config(self):
        try:
            os.makedirs(CONFIG_DIR, exist_ok=True)
            if os.path.exists(CONFIG_FILE):
                with open(CONFIG_FILE) as f:
                    return json.load(f)
        except: pass
        return {}
    
    def save_config(self):
        try:
            os.makedirs(CONFIG_DIR, exist_ok=True)
            with open(CONFIG_FILE, "w") as f:
                json.dump(self.config, f)
        except: pass
    
    def check_license_on_startup(self):
        key = self.config.get("license_key", "")
        if key:
            self.validate_license(silent=True)
    
    def validate_license(self, silent=False):
        key = self.license_entry.get().strip()
        if not key:
            if not silent:
                messagebox.showerror("Fejl", "Indtast en licensnøgle")
            return
        
        self.log(f"Validerer licens: {key[:8]}...")
        self.license_status.config(text="⏳ Tjekker...", fg="#ffcc00")
        
        def check():
            try:
                url = f"{API_URL}?key={urllib.request.quote(key)}"
                req = urllib.request.Request(url, headers={"User-Agent": "BillardStream/1.0"})
                resp = urllib.request.urlopen(req, timeout=10)
                data = json.loads(resp.read())
                if data.get("valid"):
                    self.license_valid = True
                    self.config["license_key"] = key
                    self.save_config()
                    self.root.after(0, lambda: self.license_status.config(text="✅ Gyldig", fg="#00ff41"))
                    self.root.after(0, self.log, f"✅ Licens gyldig for {data.get('klub', 'ukendt')}")
                    self.root.after(0, self.update_buttons)
                else:
                    self.license_valid = False
                    self.root.after(0, lambda: self.license_status.config(
                        text=f"❌ {data.get('error', 'Ugyldig')}", fg="#ff4444"))
            except Exception as e:
                self.license_valid = False
                self.root.after(0, lambda: self.license_status.config(text="⚠️ Ingen forbindelse", fg="#ff4444"))
                self.root.after(0, self.log, f"❌ Licensfejl: {e}")
                self.root.after(0, self.update_buttons)
        
        threading.Thread(target=check, daemon=True).start()
    
    def update_buttons(self):
        state = "normal" if self.license_valid else "disabled"
        self.start_btn.config(state=state)
    
    def start_stream(self):
        key = self.key_entry.get().strip()
        if not key:
            messagebox.showerror("Fejl", "Indtast din YouTube stream key")
            return
        
        rtmp = f"rtmp://a.rtmp.youtube.com/live2/{key}"
        self.rtmp_label.config(text=rtmp)
        
        cam_type = self.cam_type.get()
        os_name = platform.system()
        
        cmd = ["ffmpeg"]
        if cam_type == "IP Kamera":
            rtsp = self.ip_entry.get().strip()
            if not rtsp:
                messagebox.showerror("Fejl", "Indtast RTSP URL")
                return
            cmd += ["-rtsp_flags", "prefer_tcp", "-i", rtsp, "-c", "copy"]
        elif cam_type == "USB Kamera":
            device = self.ip_entry.get().strip() or "/dev/video0"
            if os_name == "Windows":
                cmd += ["-f", "dshow", "-i", device]
            else:
                cmd += ["-f", "v4l2", "-i", device]
            cmd += ["-c:v", "libx264", "-preset", "ultrafast", "-b:v", "2000k"]
        else:  # Indbygget
            if os_name == "Windows":
                cmd += ["-f", "dshow", "-i", "video=Integrated Camera"]
            elif os_name == "Darwin":
                cmd += ["-f", "avfoundation", "-i", "0"]
            else:
                cmd += ["-f", "v4l2", "-i", "/dev/video0"]
            cmd += ["-c:v", "libx264", "-preset", "ultrafast", "-b:v", "2000k"]
        
        cmd += ["-f", "flv", rtmp]
        
        self.log(f"▶ Starter stream: {' '.join(cmd)}")
        self.status_label.config(text="🟢 Starter...", fg="#ffcc00")
        self.start_btn.config(state="disabled")
        
        def run():
            try:
                self.ffmpeg_proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)
                self.root.after(0, lambda: self.status_label.config(text="🟢 LIVE", fg="#00ff41"))
                self.root.after(0, lambda: self.stop_btn.config(state="normal"))
                
                for line in self.ffmpeg_proc.stdout:
                    self.root.after(0, self.log, f"  {line.strip()[:120]}")
                
                self.ffmpeg_proc.wait()
                self.root.after(0, self.stream_stopped)
            except Exception as e:
                self.root.after(0, self.log, f"❌ Fejl: {e}")
                self.root.after(0, self.stream_stopped)
        
        threading.Thread(target=run, daemon=True).start()
    
    def stop_stream(self):
        if self.ffmpeg_proc:
            self.log("⏹ Stopper stream...")
            self.ffmpeg_proc.terminate()
            try:
                self.ffmpeg_proc.wait(timeout=5)
            except:
                self.ffmpeg_proc.kill()
            self.ffmpeg_proc = None
            self.stream_stopped()
    
    def stream_stopped(self):
        self.status_label.config(text="● Slukket", fg="#888")
        self.stop_btn.config(state="disabled")
        if self.license_valid:
            self.start_btn.config(state="normal")

if __name__ == "__main__":
    root = tk.Tk()
    app = BillardApp(root)
    root.mainloop()
