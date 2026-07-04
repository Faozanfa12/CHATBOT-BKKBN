<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Script Google reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <style>
        body { background: #e0f7fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { width: 100%; max-width: 400px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="card p-4">
        <h3 class="text-center mb-4 text-primary fw-bold">Login Admin</h3>
        
        <?php if(session()->getFlashdata('error')):?>
            <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif;?>

        <form action="<?= base_url('/login/auth') ?>" method="post">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?= old('username') ?>" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <!-- PASTE SITE KEY BARU DI SINI -->
            <div class="mb-3 d-flex justify-content-center">
                <div class="g-recaptcha" data-sitekey="6LdXpB8sAAAAAL3wgiNV5nVV97A0iKqNRNunt9Af"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Masuk</button>
        </form>

        <!-- INI TOMBOL KEMBALINYA -->
        <div class="text-center mt-3">
            <a href="<?= base_url('/') ?>" class="text-decoration-none text-muted">← Kembali ke Chatbot</a>
        </div>
    </div>
</body>
</html>