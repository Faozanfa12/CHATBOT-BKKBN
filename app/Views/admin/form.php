<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <div class="alert alert-info shadow-sm border-0 mb-4">
                <h5 class="alert-heading fw-bold">💡 Panduan Pengisian Data</h5>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6>1. Membuat Kategori Utama (Menu Awal)</h6>
                        <ul class="small mb-0">
                            <li><b>Parent ID:</b> KOSONGKAN (Jangan diisi).</li>
                            <li><b>Key Name:</b> Gunakan kode pendek, misal: <span class="badge bg-secondary">P1</span>, <span class="badge bg-secondary">P2</span>.</li>
                            <li><b>Jawaban:</b> Boleh dikosongkan jika hanya sebagai judul menu.</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>2. Membuat Sub-Pertanyaan (Isi Chat)</h6>
                        <ul class="small mb-0">
                            <li><b>Parent ID:</b> WAJIB DIISI dengan <b>ID Angka</b> milik Kategori Utama. <br><i>(Lihat ID di halaman daftar admin).</i></li>
                            <li><b>Key Name:</b> Gunakan turunan, misal: <span class="badge bg-secondary">P1.1</span>, <span class="badge bg-secondary">P1.2</span>.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?= $title ?></h4>
                </div>
                <div class="card-body p-4">
                    
                    <form action="<?= isset($data) ? base_url('admin/update/' . $data['id']) : base_url('admin/store') ?>" method="post">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Parent ID (Induk)</label>
                                <input type="number" name="parent_id" class="form-control" placeholder="Masukan ID Angka (Lihat di Tabel Utama)" value="<?= $data['parent_id'] ?? '' ?>">
                                <div class="form-text text-danger">
                                    *Kosongkan jika ingin membuat Kategori Baru (Menu Utama).<br>
                                    *Isi angka ID jika ini adalah anak/sub pertanyaan.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Key Name (Kode Unik)</label>
                                <input type="text" name="key_name" class="form-control" placeholder="Contoh: P1.1" value="<?= $data['key_name'] ?? '' ?>">
                                <div class="form-text">Kode unik untuk urutan (Opsional tapi disarankan).</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Pertanyaan / Judul Menu</label>
                            <textarea name="question_text" class="form-control" rows="3" required placeholder="Tulis pertanyaan atau judul menu di sini..."><?= $data['question_text'] ?? '' ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Jawaban Bot</label>
                            <textarea name="answer_text" class="form-control" rows="5" placeholder="Tulis jawaban bot di sini..."><?= $data['answer_text'] ?? '' ?></textarea>
                            <div class="form-text">Boleh dikosongkan jika ini hanyalah Judul Kategori.</div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= base_url('admin') ?>" class="btn btn-secondary">← Kembali</a>
                            <button type="submit" class="btn btn-success btn-lg">💾 Simpan Data</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>