// ==========================================================
//   SCRIPT.JS - Updated for Natural Edge-TTS
// ==========================================================

// Konfigurasi agar "Enter" biasa dianggap ganti baris
marked.use({
  breaks: true,
});

let isTyping = false; // Untuk mengunci input selama bot mengetik
let recognition = null;
let isListening = false;

// Inisialisasi setelah DOM siap
document.addEventListener("DOMContentLoaded", () => {
  const micBtnEl = document.getElementById("mic-btn");
  const userInputElLocal = document.getElementById("user-input");

  // ==========================================================
  //   SETUP SPEECH RECOGNITION (Microphone)
  //   Catatan: Ini untuk input suara user (Speech-to-Text),
  //   bukan untuk output suara bot.
  // ==========================================================
  if (window.SpeechRecognition || window.webkitSpeechRecognition) {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SR();
    recognition.lang = "id-ID";
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onstart = () => {
      isListening = true;
      if (micBtnEl) {
        micBtnEl.classList.add("mic-listening");
        micBtnEl.setAttribute("aria-pressed", "true");
      }
      if (userInputElLocal) {
        userInputElLocal.placeholder = "Sedang mendengarkan...";
      }
    };

    recognition.onresult = (e) => {
      const text = Array.from(e.results)
        .map((r) => r[0].transcript)
        .join("");
      if (userInputElLocal) {
        userInputElLocal.value = text;
      }
      // Otomatis kirim setelah 300ms (opsional)
      // if (text && text.trim().length > 0) setTimeout(() => sendMessage(), 300);
    };

    recognition.onend = () => {
      isListening = false;
      if (micBtnEl) {
        micBtnEl.classList.remove("mic-listening");
        micBtnEl.setAttribute("aria-pressed", "false");
      }
      if (userInputElLocal) {
        userInputElLocal.placeholder = "Ketik pesan...";
        userInputElLocal.focus();
      }
    };

    recognition.onerror = (ev) => {
      console.warn("Speech recognition error", ev);
      isListening = false;
      if (micBtnEl) {
        micBtnEl.classList.remove("mic-listening");
        micBtnEl.setAttribute("aria-pressed", "false");
      }
      if (userInputElLocal) {
        userInputElLocal.placeholder = "Ketik pesan...";
      }
    };
  }

  // Event Listener Tombol Mic
  if (micBtnEl) {
    micBtnEl.addEventListener("click", (ev) => {
      ev.preventDefault();
      if (!recognition) {
        alert(
          "Fitur pengenalan suara (microphone) tidak tersedia di browser ini. Gunakan Chrome/Edge terbaru."
        );
        return;
      }
      if (!isListening) {
        try {
          recognition.start();
        } catch (e) {
          console.error("Gagal memulai pengenalan suara", e);
        }
      } else {
        try {
          recognition.stop();
        } catch (e) {
          console.error(e);
        }
      }
    });
  }
});

// ==========================================================
//   FUNGSI MENU FAQ (Fetch dari /faq_tree)
// ==========================================================
fetch("/faq_tree")
  .then((res) => res.json())
  .then((rows) => buildFaqMenu(rows))
  .catch((err) => console.error("Gagal memuat data FAQ:", err));

function buildFaqMenu(rows) {
  const selectNode = document.getElementById("template-select");
  const wrapper = document.createElement("div");
  wrapper.classList.add("faq-menu", "up");

  const childrenMap = {};
  rows.forEach((r) => {
    const pid = r.parent_id === null ? "root" : String(r.parent_id);
    if (!childrenMap[pid]) childrenMap[pid] = [];
    childrenMap[pid].push(r);
  });

  const listArea = document.createElement("div");
  listArea.classList.add("faq-list-area");
  listArea.style.display = "none";
  wrapper.appendChild(listArea);

  const pickBtn = document.createElement("button");
  pickBtn.type = "button";
  pickBtn.classList.add("pick-btn");
  pickBtn.textContent = "--- Pilih Pertanyaan ---";
  wrapper.appendChild(pickBtn);

  function renderList(parentId) {
    listArea.innerHTML = "";

    // Judul Kategori
    if (parentId === "root") {
      const categoryTitle = document.createElement("div");
      categoryTitle.classList.add("faq-category-title");
      categoryTitle.textContent = "Pilih Kategori";
      listArea.appendChild(categoryTitle);
    }

    const items = childrenMap[parentId] || [];
    items.forEach((item) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.classList.add("faq-btn");
      btn.textContent = item.question_text;
      const childId = String(item.id);

      if (childrenMap[childId] && childrenMap[childId].length > 0) {
        // Sub-menu
        btn.addEventListener("click", (ev) => {
          ev.stopPropagation();
          renderList(childId);
        });
      } else {
        // Pertanyaan akhir
        btn.addEventListener("click", (ev) => {
          ev.stopPropagation();
          const input = document.getElementById("user-input");
          input.value = item.question_text;
          input.focus();
          try {
            const len = input.value.length;
            input.setSelectionRange(len, len);
          } catch (e) {}

          listArea.style.display = "none";
          pickBtn.classList.remove("open");
          const catTitle = listArea.querySelector(".faq-category-title");
          if (catTitle) catTitle.remove();
        });
      }
      listArea.appendChild(btn);
    });

    // Tombol CS WhatsApp di root
    if (parentId === "root") {
      const csNode = document.createElement("button");
      csNode.type = "button";
      csNode.classList.add("faq-btn", "cs-item");
      csNode.textContent = "Hubungi CS WhatsApp";
      csNode.addEventListener("click", (ev) => {
        ev.stopPropagation();
        window.open(
          "https://wa.me/6281290972022?text=Halo%20CS%20BKKBN%2C%20saya%20ingin%20bertanya",
          "_blank"
        );
        listArea.style.display = "none";
        pickBtn.classList.remove("open");
        const catTitle = listArea.querySelector(".faq-category-title");
        if (catTitle) catTitle.remove();
      });
      listArea.appendChild(csNode);
    }
  }

  function closeList() {
    listArea.style.display = "none";
    pickBtn.classList.remove("open");
    document.removeEventListener("click", outsideClick);
    const catTitle = listArea.querySelector(".faq-category-title");
    if (catTitle) catTitle.remove();
  }

  function outsideClick(e) {
    if (!wrapper.contains(e.target)) closeList();
  }

  pickBtn.addEventListener("click", (ev) => {
    ev.stopPropagation();
    if (listArea.style.display === "none") {
      renderList("root");
      listArea.style.display = "block";
      pickBtn.classList.add("open");
      setTimeout(() => document.addEventListener("click", outsideClick), 0);
    } else {
      closeList();
    }
  });

  if (selectNode && selectNode.parentNode)
    selectNode.parentNode.replaceChild(wrapper, selectNode);
}

// ==========================================================
//   EVENT LISTENER KIRIM PESAN
// ==========================================================
document.getElementById("send-btn").addEventListener("click", sendMessage);
const userInputEl = document.getElementById("user-input");

if (userInputEl) {
  userInputEl.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      if (!isTyping) sendMessage();
    }
  });
}

// ==========================================================
//   EFEK KETIKAN (Typewriter Effect)
// ==========================================================
function typeWriterEffect(container, htmlText, speed = 15, onComplete = null) {
  const tempDiv = document.createElement("div");
  tempDiv.innerHTML = htmlText;
  const textOnly = tempDiv.textContent || tempDiv.innerText || "";
  container.innerHTML = "";
  let i = 0;

  function typing() {
    if (i < textOnly.length) {
      container.textContent = textOnly.substring(0, i + 1);
      i++;
      setTimeout(typing, speed);
    } else {
      container.innerHTML = htmlText;
      if (onComplete) onComplete();
    }
  }
  typing();
}

// ==========================================================
//   FUNGSI TTS: SPEAKER -> POPUP MENU -> PLAY
// ==========================================================
function addMessageTtsButton(botMsg, botContent) {
  // 1. Buat Container
  const controls = document.createElement("div");
  controls.classList.add("msg-controls");

  // 2. Buat Tombol Speaker
  const playBtn = document.createElement("button");
  playBtn.type = "button";
  playBtn.classList.add("tts-btn");
  playBtn.title = "Klik untuk memilih suara & memutar";
  playBtn.textContent = "🔊";
  controls.appendChild(playBtn);

  // 3. Buat Menu Popup Kustom (Bukan Select Biasa)
  const popup = document.createElement("div");
  popup.classList.add("voice-popup");

  // Data Suara
  const voices = [
    { id: "id-ID-GadisNeural", label: "👩 Gadis (Cewek)" },
    { id: "id-ID-ArdiNeural", label: "👨 Ardi (Cowok)" },
  ];

  // Load suara terakhir dari memori
  let currentVoice =
    localStorage.getItem("preferred_voice") || "id-ID-GadisNeural";

  // Buat item menu
  voices.forEach((v) => {
    const item = document.createElement("div");
    item.classList.add("voice-option");
    item.textContent = v.label;

    // Tandai yang aktif
    if (v.id === currentVoice) item.classList.add("active");

    // LOGIKA KLIK ITEM MENU
    item.addEventListener("click", (e) => {
      e.stopPropagation(); // Cegah menu langsung nutup sebelum proses

      // Simpan pilihan
      currentVoice = v.id;
      localStorage.setItem("preferred_voice", currentVoice);

      // Update visual active
      popup
        .querySelectorAll(".voice-option")
        .forEach((el) => el.classList.remove("active"));
      item.classList.add("active");

      // Tutup menu
      popup.classList.remove("show");

      // Jalankan Play Audio Langsung
      playAudio();
    });

    popup.appendChild(item);
  });

  controls.appendChild(popup);

  // Sisipkan ke pesan bot
  botMsg.insertBefore(controls, botMsg.firstChild);

  // --- LOGIKA AUDIO PLAYER ---
  let audioObj = null;

  function playAudio() {
    // 1. Ambil teks
    const text = botContent.innerText || "";
    if (!text) return;

    // 2. Reset Audio Lain
    document.querySelectorAll("audio").forEach((a) => a.pause());
    document.querySelectorAll(".tts-btn").forEach((btn) => {
      btn.textContent = "🔊";
      btn.classList.remove("playing");
    });

    // 3. Loading State
    playBtn.textContent = "⏳";
    playBtn.disabled = true;

    // 4. Fetch ke Backend
    fetch("/tts", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ text: text, voice: currentVoice }),
    })
      .then((res) => {
        if (!res.ok) throw new Error("Gagal");
        return res.blob();
      })
      .then((blob) => {
        const url = URL.createObjectURL(blob);
        audioObj = new Audio(url);

        audioObj.onended = () => {
          playBtn.classList.remove("playing");
          playBtn.textContent = "🔊";
        };

        audioObj.play();
        playBtn.textContent = "⏹️"; // Tombol Stop
        playBtn.classList.add("playing");
        playBtn.disabled = false;
      })
      .catch((err) => {
        console.error(err);
        playBtn.textContent = "❌";
        setTimeout(() => {
          playBtn.textContent = "🔊";
          playBtn.disabled = false;
        }, 2000);
      });
  }

  // LOGIKA KLIK TOMBOL SPEAKER UTAMA
  playBtn.addEventListener("click", (e) => {
    e.stopPropagation();

    // A. Jika sedang main -> STOP
    if (playBtn.classList.contains("playing")) {
      if (audioObj) {
        audioObj.pause();
        audioObj.currentTime = 0;
      }
      playBtn.classList.remove("playing");
      playBtn.textContent = "🔊";
      return;
    }

    // B. Jika tidak main -> BUKA MENU
    // Toggle class 'show' pada popup
    // Tutup semua popup lain dulu biar rapi
    document.querySelectorAll(".voice-popup").forEach((p) => {
      if (p !== popup) p.classList.remove("show");
    });

    popup.classList.toggle("show");
  });

  // Klik di mana saja di luar untuk menutup menu
  document.addEventListener("click", (e) => {
    if (!controls.contains(e.target)) {
      popup.classList.remove("show");
    }
  });
}

// ==========================================================
//   FUNGSI UTAMA: KIRIM PESAN
// ==========================================================
function sendMessage() {
  if (isTyping) return;

  const userInput = document.getElementById("user-input");
  const message = userInput.value.trim();
  if (!message) return;

  isTyping = true;
  userInput.disabled = true;
  document.getElementById("send-btn").disabled = true;
  userInput.classList.add("is-locked");
  document.getElementById("send-btn").classList.add("is-locked");

  const chatBox = document.getElementById("chat-box");
  const pair = document.createElement("div");
  pair.classList.add("message-pair");

  const userDiv = document.createElement("div");
  userDiv.classList.add("message", "user");
  userDiv.textContent = message;
  pair.appendChild(userDiv);

  const botContainer = document.createElement("div");
  botContainer.classList.add("bot-container");
  pair.appendChild(botContainer);
  chatBox.appendChild(pair);
  chatBox.scrollTop = chatBox.scrollHeight;

  // UUID Conversation
  let convId = localStorage.getItem("conversation_id");
  if (!convId) {
    convId = crypto.randomUUID
      ? crypto.randomUUID()
      : String(Date.now()) + Math.random();
    localStorage.setItem("conversation_id", convId);
  }

  fetch("/get", {
    method: "POST",
    body: new URLSearchParams({ message, conversation_id: convId }),
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
  })
    .then((res) => res.json())
    .then((data) => {
      const jobId = data.job_id;

      // Typing indicator
      const typingWrap = document.createElement("div");
      typingWrap.classList.add("message", "bot");
      const typingBubble = document.createElement("div");
      typingBubble.classList.add("typing-bubble");
      typingBubble.innerHTML = `
        <div class="typing-dots">
          <span></span><span></span><span></span>
        </div>
      `;
      typingWrap.appendChild(typingBubble);
      botContainer.appendChild(typingWrap);
      chatBox.scrollTop = chatBox.scrollHeight;

      // Polling Job
      const poll = setInterval(() => {
        fetch(`/job/${jobId}`)
          .then((r) => r.json())
          .then((j) => {
            if (j.status === "done" || j.status === "error") {
              clearInterval(poll);
              botContainer.removeChild(typingWrap);

              const botMsg = document.createElement("div");
              botMsg.classList.add("message", "bot");

              const botContent = document.createElement("div");
              botContent.classList.add("bot-content");
              botMsg.appendChild(botContent);
              botContainer.appendChild(botMsg);

              const htmlContent = marked.parse(j.result);

              typeWriterEffect(botContent, htmlContent, 25, () => {
                isTyping = false;
                userInput.disabled = false;
                document.getElementById("send-btn").disabled = false;
                userInput.classList.remove("is-locked");
                document
                  .getElementById("send-btn")
                  .classList.remove("is-locked");
                userInput.focus();

                // Tambahkan tombol TTS setelah teks selesai diketik
                try {
                  addMessageTtsButton(botMsg, botContent);
                } catch (e) {
                  console.warn("Gagal menambahkan tombol TTS", e);
                }
              });

              chatBox.scrollTop = chatBox.scrollHeight;
            }
          })
          .catch((err) => {
            console.error("Gagal polling:", err);
            clearInterval(poll);
            botContainer.removeChild(typingWrap);

            const errorMsg = document.createElement("div");
            errorMsg.classList.add("message", "bot", "error");
            errorMsg.textContent = "Terjadi kesalahan, coba lagi.";
            botContainer.appendChild(errorMsg);

            isTyping = false;
            userInput.disabled = false;
            document.getElementById("send-btn").disabled = false;
            userInput.classList.remove("is-locked");
            document.getElementById("send-btn").classList.remove("is-locked");
          });
      }, 700);
    })
    .catch((err) => {
      const pair = document.createElement("div");
      pair.classList.add("message-pair");
      const botContainer = document.createElement("div");
      botContainer.classList.add("bot-container");
      const errorMsg = document.createElement("div");
      errorMsg.classList.add("message", "bot", "error");
      errorMsg.textContent = "Terjadi kesalahan sistem.";
      botContainer.appendChild(errorMsg);
      pair.appendChild(botContainer);
      chatBox.appendChild(pair);
      console.error("Error:", err);

      isTyping = false;
      userInput.disabled = false;
      document.getElementById("send-btn").disabled = false;
      userInput.classList.remove("is-locked");
      document.getElementById("send-btn").classList.remove("is-locked");
    });

  userInput.value = "";
}

// ==========================================================
//   LOGIKA DARK MODE
// ==========================================================
const toggleBtn = document.getElementById("theme-toggle");
const body = document.body;

// 1. Cek preferensi yang tersimpan
const currentTheme = localStorage.getItem("theme");
if (currentTheme === "dark") {
  body.classList.add("dark-mode");
  if (toggleBtn) toggleBtn.textContent = "☀️"; // Ubah ikon jadi matahari
}

// 2. Event Listener Tombol
if (toggleBtn) {
  toggleBtn.addEventListener("click", () => {
    body.classList.toggle("dark-mode");

    let theme = "light";
    if (body.classList.contains("dark-mode")) {
      theme = "dark";
      toggleBtn.textContent = "☀️"; // Ganti ikon
    } else {
      toggleBtn.textContent = "🌙"; // Ganti ikon
    }

    // Simpan ke local storage
    localStorage.setItem("theme", theme);
  });
}
