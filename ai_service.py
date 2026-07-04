from pydoc import text
from flask import Flask, request, jsonify, send_file
from sentence_transformers import SentenceTransformer
import numpy as np
import pandas as pd
import mysql.connector
from sklearn.metrics.pairwise import cosine_similarity
import os
import uuid
import subprocess 
import sys
import time

# --- FIX KHUSUS WINDOWS ---
import platform
import asyncio
if platform.system() == 'Windows':
    asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())

app = Flask(__name__)

# =========================================================
# 1. KONFIGURASI & STARTUP CHECK
# =========================================================
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'chatbot'
}

# Folder Audio
AUDIO_DIR = "static/audio"
os.makedirs(AUDIO_DIR, exist_ok=True)

# --- TAMPILAN DASHBOARD STARTUP ---
print("\n" + "="*60)
print("      🚀  SYSTEM BOOT: HYBRID AI CHATBOT SERVICE  🚀")
print("="*60)

# Cek 1: Local LLM (MiniLM)
print(" [1/3] 🧠 Memuat Local LLM (MiniLM) ... ", end="", flush=True)
try:
    model = SentenceTransformer("all-MiniLM-L6-v2")
    print("✅ [AKTIF]")
except Exception as e:
    print(f"❌ [GAGAL]\nError: {e}")
    model = None

# Cek 2: TTS Engine
print(" [2/3] 🔊 Memuat Engine Suara (TTS) ... ", end="", flush=True)
try:
    import edge_tts
    print("✅ [AKTIF]")
except ImportError:
    print("❌ [GAGAL] (Library edge-tts belum terinstall)")

# Cek 3: Status Gemini (Simulasi)
# Python tidak load Gemini, tapi dia siap melayani PHP yang pegang Gemini
print(" [3/3] ☁️  Bridge ke Google Gemini    ... ", end="", flush=True)
print("✅ [STANDBY]") 

print("-" * 60)
print(" ✅ SYSTEM READY! Menunggu request dari CodeIgniter...")
print("-" * 60 + "\n")

# Variabel Global
df_faq = None
faq_embeddings = None

# =========================================================
# 2. FUNGSI UPDATE MEMORY
# =========================================================
def update_memory():
    global df_faq, faq_embeddings
    print("🔄 Sedang membaca ulang Database...")
    try:
        conn = mysql.connector.connect(**db_config)
        df_faq = pd.read_sql("SELECT id, question_text, answer_text FROM faq", conn)
        conn.close()
        
        questions = df_faq['question_text'].tolist()
        if questions and model:
            faq_embeddings = model.encode(questions)
            np.save("faq_embeddings_sql.npy", faq_embeddings)
            print(f"✅ Sukses! {len(df_faq)} data dipelajari.")
        else:
            print("⚠️ Database kosong atau Model belum siap.")
            faq_embeddings = None
    except Exception as e:
        print(f"❌ Error update memory: {e}")

# =========================================================
# 3. STARTUP
# =========================================================
if os.path.exists("faq_embeddings_sql.npy"):
    try:
        print("📂 Memuat ingatan lama...")
        faq_embeddings = np.load("faq_embeddings_sql.npy")
        conn = mysql.connector.connect(**db_config)
        df_faq = pd.read_sql("SELECT id, question_text, answer_text FROM faq", conn)
        conn.close()
        if len(df_faq) != faq_embeddings.shape[0]:
            update_memory()
        else:
            print("✅ Data sinkron.")
    except:
        update_memory()
else:
    update_memory()

# =========================================================
# 4. ROUTE SEARCH
# =========================================================
@app.route('/search', methods=['POST'])
def search():
    global faq_embeddings, df_faq
    user_query = request.json.get('query', '')
    if not user_query or faq_embeddings is None: return jsonify([])

    query_vec = model.encode([user_query])
    similarities = cosine_similarity(query_vec, faq_embeddings)
    top_indices = similarities[0].argsort()[-3:][::-1]
    
    results = []
    for idx in top_indices:
        score = float(similarities[0][idx])
        if score > 0.35: 
            row = df_faq.iloc[idx]
            results.append({
                'question': row['question_text'],
                'answer': row['answer_text'],
                'score': score
            })
    return jsonify(results)

@app.route('/refresh', methods=['GET'])
def refresh():
    update_memory()
    return jsonify({"status": "success"})

# =========================================================
# 5. ROUTE TTS (MODIFIKASI ANTI-CRASH)
# =========================================================

# Di dalam ai_service.py

@app.route('/tts', methods=['POST'])
def tts_handler():
    try:
        data = request.json
        raw_text = data.get('text', '')
        voice = data.get('voice', 'id-ID-GadisNeural')

        if not raw_text:
            return jsonify({"error": "Text kosong"}), 400

        # --- PERBAIKAN: BERSIHKAN TEKS ---
        # 1. Hapus tanda bintang (*) atau pagar (#) bekas format Markdown
        clean_text = raw_text.replace("*", "").replace("#", "").replace("`", "")
        
        # 2. Ganti Enter (\n) dengan Titik (.) agar jeda, tapi tidak berhenti
        # Ini kuncinya: Edge-TTS kadang stop kalau ketemu \n
        clean_text = clean_text.replace("\n", ". ")
        
        # 3. Hapus spasi ganda
        clean_text = " ".join(clean_text.split())

        # --- LANJUT PROSES SEPERTI BIASA ---
        abs_audio_dir = os.path.abspath(AUDIO_DIR)
        unique_id = uuid.uuid4()
        
        # Nama File
        audio_filename = f"tts_{unique_id}.mp3"
        audio_filepath = os.path.join(abs_audio_dir, audio_filename)
        text_filename = f"tts_{unique_id}.txt"
        text_filepath = os.path.join(abs_audio_dir, text_filename)
        
        # Simpan teks BERSIH ke file
        with open(text_filepath, "w", encoding="utf-8") as f:
            f.write(clean_text)

        # Panggil Worker
        cmd = [sys.executable, "python_tts.py", text_filepath, voice, audio_filepath]
        result = subprocess.run(cmd, capture_output=True, text=True)

        # Hapus file text
        if os.path.exists(text_filepath):
            os.remove(text_filepath)

        if result.returncode != 0:
            return jsonify({"error": "Gagal generate suara", "detail": result.stderr}), 500
        
        if os.path.exists(audio_filepath):
            return send_file(audio_filepath, mimetype="audio/mpeg")
        else:
            return jsonify({"error": "File audio tidak ditemukan"}), 500

    except Exception as e:
        print(f"Error TTS Fatal: {e}")
        return jsonify({"error": "Python Error", "message": str(e)}), 500

if __name__ == '__main__':
    app.run(port=5000, debug=True, threaded=True)