<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GroWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="../MAIN/main.php">GroWise</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../MAIN/main.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">About</a>
                    </li>
                    <li class="nav-item">
                    <a class="nav-link" href="<?php echo isset($_SESSION['username']) ? 'logout.php' : 'login.php'; ?>">
                    <?php echo isset($_SESSION['username']) ? 'Logout' : 'Login'; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Empowering Small Enterprises with Digital Solutions</h1>
            <p class="lead mb-4">Powering small businesses with big solutions for seamless growth.</p>
            <a href="#" class="empowerment-btn">
                Get Started 
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ms-2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </section>

    <section class="container my-5">
        <h2 class="keyFeatures mb-5">Key Features</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-card">
                    <h1>isip kayo!!</h1>
                    <p>mag isip kayo magandang ilagay sa key features hehe lab u all</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                <h1>isip kayo!!</h1>
                <p>mag isip kayo magandang ilagay sa key features hehe lab u all</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                <h1>isip kayo!!</h1>
                <p>mag isip kayo magandang ilagay sa key features hehe lab u all</p>
                </div>
            </div>
        </div>
    </section>

    <section class="success-section">
        <div class="container py-5">
            <h2 class="text-center display-6 fw-bold mb-5">Success Story</h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="success-card">
                        <p>content for success story</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="success-card">
                        <p>content for success story</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="containers">
            <div class="footer-left">
                <h4>Digital Business Hub</h4>
                <p>Empowering small enterprises worldwide</p>
            </div>
            <div class="footer-right">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Us</a>
            </div>
        </div>
        <div class="copyright">
            © 2025 Digital Business Hub. All rights reserved.
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>