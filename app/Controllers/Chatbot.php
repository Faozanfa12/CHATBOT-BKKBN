<?php

namespace App\Controllers;

use App\Models\FaqModel;

class Chatbot extends BaseController
{
    protected $faqModel;

    public function __construct()
    {
        $this->faqModel = new FaqModel();
        // API Key diambil nanti di fungsi callGemini agar bisa rotasi
    }

    public function index()
    {
        return view('chat_view');
    }

    public function faq_tree()
    {
        $data = $this->faqModel->findAll();
        $formatted = [];
        foreach($data as $row) {
            $formatted[] = [
                'id' => (int)$row['id'],
                'parent_id' => (!empty($row['parent_id']) && $row['parent_id'] != 0) ? (int)$row['parent_id'] : null,
                'question_text' => $row['question_text']
            ];
        }
        return $this->response->setHeader('Content-Type', 'application/json')->setJSON($formatted);
    }

    // =========================================================================
    // LOGIKA UTAMA
    // =========================================================================
    public function getResponse()
    {
        // 1. SETTING TIMEOUT PHP (Agar tidak fatal error jika loading lama)
        ini_set('max_execution_time', 60);

        $message = $this->request->getPost('message');
        $convId  = $this->request->getPost('conversation_id') ?? 'default_session';
        $jobId   = uniqid();

        // 2. SESSION HISTORY
        $session = session();
        if (!$session->has($convId)) $session->set($convId, []);
        $history = $session->get($convId);

        // 3. SEARCH DATA (PANGGIL PYTHON AI SERVICE)
        $url = "http://127.0.0.1:5000/search";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $message]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        // Timeout Python: Maksimal nunggu 10 detik. 
        // Kalau Python mati, dia langsung skip (biar web gak loading selamanya)
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
        
        $pythonResponse = curl_exec($ch);
        curl_close($ch);
        
        $searchResults = json_decode($pythonResponse, true) ?? [];

        // 3. PEMILIHAN PROMPT (LOGIKA IF-ELSE SAMA PERSIS PYTHON)
        $finalPrompt = "";

        // JIKA ADA DATA (Mirip blok `if a_src:` atau `if related:` di Python)
        if (!empty($searchResults)) {
            
            // a. Susun Context Text
            $contextParts = [];
            foreach ($searchResults as $item) {
                // Format sama persis: FAQ: ... Jawaban sumber: ... (skore kemiripan: ...)
                $score = isset($item['score']) ? $item['score'] : '-';
                $contextParts[] = "FAQ: {$item['question']}\nJawaban sumber: {$item['answer']}\n(skore kemiripan: {$score})";
            }
            $contextText = implode("\n\n---\n\n", $contextParts);

            // b. Susun History Text
            $historyText = "";
            $lastHistory = array_slice($history, -4);
            foreach($lastHistory as $turn) {
                $role = ucfirst($turn['role']); 
                $historyText .= "$role: {$turn['text']}\n";
            }

            // c. PROMPT 1: BKKBN (COPY PASTE DARI PYTHON)
            // c. PROMPT 1: BKKBN (COPY PASTE DARI PYTHON)
            $finalPrompt = "
Anda adalah asisten resmi BKKBN assistant yang membantu pengguna menjawab pertanyaan tentang BKKBN.
" . ($historyText ? "Riwayat obrolan (untuk konteks):\n$historyText\n" : "") . "
Gunakan HANYA informasi yang disediakan di bawah ini (jika ada) untuk menjawab **pertanyaan pengguna terakhir**.
Tolong susun jawaban menggunakan **bahasa sendiri** (parafrasa) — jangan menyalin teks sumber kata-per-kata.
Jika informasi tidak memadai untuk menjawab, jawab dengan pelajari data yang ada sesuaikan dengan pengetahuanmu yang terpenting masih berkaitan dengan data FAQ ataupun BKKBN.

--- Informasi FAQ relevan ---
$contextText

--- Pertanyaan pengguna terakhir ---
$message

--- Petunjuk gaya jawaban ---
- Singkat, jelas, dan langsung ke inti.
- Bila relevan, sertakan langkah/aksi yang harus dilakukan (misal menu, path).
- Jika ada istilah teknis, jelaskan singkat artinya.
- Jangan tambahkan informasi di luar FAQ kecuali masih berhubungan dengan FAQ ataupun terkait BKKBN.
- Jika pengguna bertanya 'maksudnya?', 'gimana?', 'terus?', 'kok bisa?', atau pertanyaan klarifikasi lain,
  maka jelaskan ulang jawaban Anda sebelumnya dengan lebih jelas.
- Untuk pertanyaan klarifikasi, jangan memulai topik baru dan jangan meminta konteks tambahan.
  Cukup jelaskan jawaban terakhir Anda dengan kata-kata yang lebih mudah.
";

        } else {
            // JIKA TIDAK ADA DATA (Mirip blok `else:` prompt SIRIKA di Python)
            // d. PROMPT 2: SIRIKA (COPY PASTE DARI PYTHON)
            
            $finalPrompt = "
Anda adalah asisten resmi SIRIKA.
Jika tidak ditemukan informasi relevan pada data FAQ internal.
Pertanyaan pengguna: $message

Silakan jawab singkat: jika Anda tahu jawaban umum yang aman dan umum (tanpa menambahkan kebijakan internal) yang terpenting masih berkaitan dengan data FAQ namun apabila tidak ada kaitanya dengan data FAQ ataupun pengetahuan terkait BKKBN maka jawablah anda hanya bisa menjawab pertanyaan seputar BKKBN dan tambahkan Silakan hubungi CS BKKBN untuk bantuan lebih lanjut.";
        }

        // 5. PANGGIL GEMINI (Kirim ke Cloud)
        $aiResponse = $this->callGemini($finalPrompt);

        // // =================================================================
        // // LOGIC ANTI-CRASH / FALLBACK (CERDAS & RAMAH)
        // // =================================================================
        
        $isError = false;
        if (strpos($aiResponse, 'Google Error') !== false) $isError = true;
        if (strpos($aiResponse, 'cURL Error') !== false) $isError = true;

        // JIKA GEMINI MATI / ERROR
        if ($isError) {
            
            // A. DEFAULT: Tolak dengan sopan (Jika tidak ada yg cocok)
            $aiResponse = "Mohon maaf, sebagai asisten resmi BKKBN saya hanya dapat menjawab pertanyaan seputar program BKKBN. Silakan hubungi CS via WhatsApp untuk bantuan lebih lanjut.";

            $msgLow = strtolower($message);

            // B. CEK SAPAAN / TERIMAKASIH (General)
            if (preg_match('/\b(terimakasih|makasih|thanks|thank|oke|ok|siap|baik|mantap|keren|halo|hai|pagi|siang|sore|malam)\b/', $msgLow)) {
                $aiResponse = "Halo! Senang bisa membantu. 😊 Jangan ragu bertanya lagi jika butuh informasi seputar BKKBN.";
            }

            // C. CEK PERTANYAAN IDENTITAS (Kamu Siapa?)
            if (preg_match('/\b(siapa\s+(kamu|anda|ini)|nama\s+(kamu|anda)|namamu|bot|asisten|robot)\b/', $msgLow)) {
                $aiResponse = "Saya adalah Asisten Digital BKKBN. Tugas saya membantu Anda menemukan informasi seputar program Bangga Kencana dan kesehatan keluarga. Ada yang bisa saya bantu?";
            }

            // D. CEK DATABASE (PRIORITAS TERTINGGI)
            // Kalau ini pertanyaan serius (ada datanya), TINDIH jawaban basa-basi di atas
            if (!empty($searchResults)) {
                $topResult = $searchResults[0];
                $score = isset($topResult['score']) ? (float)$topResult['score'] : 0;
                
                // Jika skor valid (Relevan >= 0.65)
                if ($score >= 0.65) {
                    $dbAnswer = $topResult['answer'];
                    
                    // Bersihkan jawaban
                    $cleanAnswer = trim($dbAnswer);

                    // TRIK ILUSI NATURAL
                    $intros = [
                        "Baik, berdasarkan informasi resmi, ",
                        "Mengenai pertanyaan Anda, ",
                        "Bisa saya jelaskan bahwa ",
                        "Oke, poin pentingnya adalah: ",
                        "Menurut panduan BKKBN, ",
                        "Berikut penjelasannya: "
                    ];
                    $outros = [
                        " Semoga informasi ini membantu ya.",
                        " Silakan tanya lagi jika belum jelas.",
                        "", 
                        " Pastikan Anda mencatat poin tersebut.",
                        " Yuk, wujudkan keluarga berkualitas!"
                    ];

                    $randomIntro = $intros[array_rand($intros)];
                    $randomOutro = $outros[array_rand($outros)];
                    
                    $aiResponse = $randomIntro . lcfirst($cleanAnswer) . $randomOutro;
                }
            }
        }
        // =================================================================

        // 6. SIMPAN HISTORY
        $history[] = ['role' => 'user', 'text' => $message];
        $history[] = ['role' => 'assistant', 'text' => $aiResponse];
        if (count($history) > 10) $history = array_slice($history, -10);
        $session->set($convId, $history);

        // 7. OUTPUT FILE JSON (Untuk Frontend Polling)
        $tempFile = WRITEPATH . 'uploads/job_' . $jobId . '.json';
        file_put_contents($tempFile, json_encode(['result' => $aiResponse]));

        return $this->response->setJSON(['job_id' => $jobId]);
    }

    public function jobStatus($jobId)
    {
        $tempFile = WRITEPATH . 'uploads/job_' . $jobId . '.json';
        if (file_exists($tempFile)) {
            $data = json_decode(file_get_contents($tempFile), true);
            unlink($tempFile);
            return $this->response->setJSON(['status' => 'done', 'result' => $data['result']]);
        }
        return $this->response->setJSON(['status' => 'pending']);
    }

    // TTS (TEXT TO SPEECH)
    // TTS (TEXT TO SPEECH) - FIXED VERSION
    public function tts()
    {
        $json = $this->request->getJSON();
        $text = $json->text ?? '';
        $voice = $json->voice ?? 'id-ID-GadisNeural';

        // --- PERBAIKAN 1: HAPUS PEMBATASAN 250 HURUF ---
        // Kode lama kamu memotong teks disini:
        // if (strlen($text) > 250) $text = substr($text, 0, 250);  <-- INI PENYEBAB UTAMA TERPOTONG

        // Kita ganti dengan pembersihan Markdown saja
        $text = str_replace(["*", "#", "`"], "", $text);
        
        // Hapus tag HTML (misal <br> atau <b>) agar tidak dibaca oleh robot
        $text = strip_tags($text);

        // Panggil Flask langsung (Service Python)
        $url = "http://127.0.0.1:5000/tts";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['text' => $text, 'voice' => $voice]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        // --- PERBAIKAN 2: NAIKKAN TIMEOUT ---
        // Ubah dari 15 detik menjadi 60 detik (1 menit)
        // Agar PHP sabar menunggu Python selesai generate suara panjang
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            return $this->response
                ->setHeader('Content-Type', 'audio/mpeg')
                ->setBody($response);
        } else {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Gagal TTS via Flask', 'detail' => $response]);
        }
    }

    // GANTI FUNGSI callGemini DENGAN INI:
    private function callGemini($text)
    {
        // 1. Ambil Key berurutan dari .env
        $keys = [
            getenv('GEMINI_API_KEY_1'),
            getenv('GEMINI_API_KEY_2'),
            getenv('GEMINI_API_KEY_3')
        ];
        
        // Buang yang kosong
        $keys = array_filter($keys);

        if (empty($keys)) return "Error: API Key belum disetting.";

        $lastError = "Tidak ada respon.";

        // 2. LOOPING (FAILOVER SYSTEM)
        // Mencoba key satu per satu. Jika key pertama sukses, loop berhenti.
        // Jika gagal, lanjut ke key berikutnya.
        foreach ($keys as $apiKey) {


            // $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

            // Menggunakan model Flash-Lite (Hemat & Cepat)
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . $apiKey;

            // $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-001:generateContent?key=" . $apiKey;

            $data = [
                "contents" => [
                    ["parts" => [["text" => $text]]]
                ],
                // Config agar hemat token
                "generationConfig" => [
                    "maxOutputTokens" => 250, 
                    "temperature" => 0.7
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Jika cURL Error (koneksi), simpan error dan LANJUT ke key berikutnya
            if ($curlError) {
                $lastError = "Koneksi Error: " . $curlError;
                continue; 
            }

            $json = json_decode($response, true);

            // JIKA SUKSES (Ada jawaban text) -> Langsung return, hentikan loop
            if ($httpCode == 200 && isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                return $json['candidates'][0]['content']['parts'][0]['text'];
            }

            // JIKA GOOGLE ERROR (Limit Habis / Invalid Key) -> Simpan error dan LANJUT ke key berikutnya
            if (isset($json['error']['message'])) {
                $lastError = "Google Error: " . $json['error']['message'];
                continue;
            }
        }

        // 3. Jika sudah mencoba SEMUA key tapi tetap gagal
        return "Maaf, sistem sedang sibuk (Semua API Key limit/error). Detail: " . $lastError;
    }


    #CEK MODEL GEMINI YANG TERSEDIA UNTUK API KEY 
    public function cekModel()
    {
        $apiKey = getenv('GEMINI_API_KEY_1'); // Pastikan ini mengambil key yang benar
        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        echo "<pre>";
        if (isset($data['models'])) {
            echo "<h3>Daftar Model yang Tersedia untuk Key Anda:</h3>";
                foreach ($data['models'] as $model) {
            // Kita cari yang support 'generateContent'
            if (isset($model['supportedGenerationMethods']) && in_array("generateContent", $model['supportedGenerationMethods'])) {
                
                echo "<b>Nama: " . $model['name'] . "</b><br>";
                echo "Versi: " . ($model['version'] ?? '-') . "<br>";
                
                // PERBAIKAN UTAMA DI SINI (Pakai tanda ??)
                echo "Deskripsi: " . ($model['description'] ?? 'Tidak ada deskripsi') . "<br>";
                
                echo "------------------------------------------------<br>";
            }
        }
        } else {
            echo "Gagal mengambil daftar model. Pesan: " . $response;
        }
        echo "</pre>";
    }
}