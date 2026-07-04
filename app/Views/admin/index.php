<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chatbot BKKBN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary">⚡ Admin Chatbot BKKBN</h2>
        <div>
            <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary me-2">Ke Chatbot</a>
            <a href="<?= base_url('logout') ?>" class="btn btn-danger me-2">Logout</a> <a href="<?= base_url('admin/create') ?>" class="btn btn-primary">+ Tambah FAQ</a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Parent</th>
                            <th>Key</th>
                            <th width="30%">Pertanyaan</th>
                            <th width="30%">Jawaban</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faqs as $row) : ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['parent_id'] ?? '-' ?></td>
                            <td><span class="badge bg-info text-dark"><?= $row['key_name'] ?></span></td>
                            <td><?= esc($row['question_text']) ?></td>
                            <td><?= substr(esc($row['answer_text']), 0, 50) ?>...</td>
                            <td>
                                <a href="<?= base_url('admin/edit/' . $row['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="<?= base_url('admin/delete/' . $row['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin mau hapus?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>