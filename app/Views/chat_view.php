<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Asisten Digital BKKBN</title>
    
    <link rel="stylesheet" href="<?= base_url('static/css/style.css') ?>" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  </head>
  <body>
    
    <div class="chat-container" role="application" aria-label="Chat BKKBN Assistant">
      <div class="chat-header">
        
        <img src="<?= base_url('static/images/bkkbn-logo.png') ?>" alt="Logo BKKBN" class="bkkbn-logo" />

        <div class="header-info">
          <span class="chat-title">Chat BKKBN</span>
          <div class="chat-subtitle">
            Asisten digital — bantuan cepat & jawaban terverifikasi
          </div>
        </div>

        <div class="settings-menu-container">
            <button id="settingsBtn" class="settings-trigger" title="Pengaturan">
                ⚙️
            </button>

            <div class="settings-options" id="settingsOptions">
                <button class="option-btn" id="theme-toggle" title="Ganti Mode">
                    🌙
                </button>
                
                <a href="<?= base_url('login') ?>" class="option-btn" title="Login Admin">
                    🔐
                </a>
            </div>
        </div>
        </div>
      
      <div class="chat-box" id="chat-box" aria-live="polite" aria-atomic="false"></div>

      <select id="template-select">
        <option value="">-- Pilih pertanyaan --</option>
      </select>

      <div class="input-box">
        <button id="mic-btn" class="voice-btn" aria-pressed="false" title="Dengarkan (microphone)">🎙️</button>
        <input type="text" id="user-input" placeholder="Ketik pesan..." aria-label="Pesan" />
        <button id="send-btn">Kirim</button>
      </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="<?= base_url('static/js/script.js') ?>"></script>

    <script>
        const settingsBtn = document.getElementById('settingsBtn');
        const settingsOptions = document.getElementById('settingsOptions');
        
        if(settingsBtn && settingsOptions) {
            settingsBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Mencegah event bubbling
                settingsBtn.classList.toggle('active');
                settingsOptions.classList.toggle('show');
            });

            // Tutup menu jika klik di mana saja di luar menu
            document.addEventListener('click', (e) => {
                if (!settingsBtn.contains(e.target) && !settingsOptions.contains(e.target)) {
                    settingsBtn.classList.remove('active');
                    settingsOptions.classList.remove('show');
                }
            });
        }
    </script>
  </body>
</html>