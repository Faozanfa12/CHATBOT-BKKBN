import sys
import asyncio
import edge_tts
import platform

# Argumen yang diterima dari Flask:
# sys.argv[1] = PATH FILE TEKS
# sys.argv[2] = VOICE ID
# sys.argv[3] = PATH OUTPUT AUDIO

async def main():
    try:
        text_file_path = sys.argv[1]
        voice = sys.argv[2]
        output_file = sys.argv[3]
        
        # --- BACA TEKS DARI FILE ---
        with open(text_file_path, "r", encoding="utf-8") as f:
            text = f.read()

        if not text.strip():
            print("Error: Isi file teks kosong", file=sys.stderr)
            sys.exit(1)

        # --- GENERATE AUDIO (DIPERLAMBAT) ---
        # Perhatikan bagian rate="-15%"
        # Semakin besar angkanya (misal -50%), semakin lambat.
        # -15% adalah kecepatan yang pas untuk presentasi formal/BKKBN.
        communicate = edge_tts.Communicate(text, voice, rate="-10%")
        
        await communicate.save(output_file)
                    
    except Exception as e:
        print(f"Error Worker: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    # Fix Wajib Windows
    if platform.system() == 'Windows':
        asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())

    if len(sys.argv) > 3:
        asyncio.run(main())
    else:
        print("Argumen kurang", file=sys.stderr)
        sys.exit(1)