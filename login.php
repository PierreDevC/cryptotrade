<?php
// ADDED: Require Session for flash messages
// Assumes Session.php is within the include path or use absolute path if needed
require_once __DIR__ . '/app/Core/Session.php';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>CryptoTrade - Login</title>
    <!-- CDN Imports -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/f09d5e5c92.js" crossorigin="anonymous"></script>
    <!-- Other CDN links -->
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS - Use root-relative path -->
    <link rel="stylesheet" href="/cryptotrade/css/style.css">
</head>
<body>
    <div class="container-fluid vh-100">
        <div class="row h-100">
            <!-- Left column -->
            <div class="col-md-6 col-lg-6 d-none d-md-block p-0 position-relative h-100 overflow-hidden">
                {/* Link to root route */}
                <a href="/cryptotrade/" class="text-decoration-none">
                    <h1 class="position-absolute text-white p-4 brand-logo" style="z-index: 1;">CryptoTrade</h1>
                </a>
                <img src="/cryptotrade/img/login.jpg" class="w-100 h-100" style="object-fit: cover;" alt="Login background image">
            </div>
            <!-- Right column -->
            <div class="col-12 col-md-6 col-lg-6 d-flex align-items-center justify-content-center">
                <div class="w-75" style="max-width: 400px;">
                    <div class="text-center mb-4 d-md-none">
                         <a href="/cryptotrade/" class="navbar-brand brand-logo fs-2 text-dark text-decoration-none">CryptoTrade</a>
                    </div>
                    <h2 class="text-center mb-4">Se connecter</h2>

                    {/* ADDED: PHP Snippet for Flash Messages */}
                    <?php
                        $error = App\Core\Session::flash('error');
                        $success = App\Core\Session::flash('success');
                        if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif;
                        if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                             </div>
                         <?php endif; ?>
                    {/* End Flash Messages */}

                    {/* Form points to route */}
                    <form method="POST" action="/cryptotrade/login">
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse courriel</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="action-button" style="width: 100%;">Se connecter</button>
                        </div>
                        <p class="text-center mt-3">
                            Vous n'avez pas encore de compte?
                            {/* Link to route */}
                            <a href="/cryptotrade/signup" class="text-decoration-none">Créer un compte</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>