<?php 
include 'includes/config.php';

// Ensure no output before headers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);
    
    $sql = "SELECT * FROM users WHERE username='$username' AND password=MD5('$password')";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['jurusan'] = $user['jurusan'];
       
        header("Location: admin/dashboard.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kantin Digital Sekolah</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #5a72ee;
            --primary-dark: #2f4ac9;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --accent-light: #ff7bab;
            --light: #f8f9fa;
            --light-gray: #f1f3f5;
            --dark: #212529;
            --dark-gray: #495057;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #ef233c;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: 1px solid rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: var(--dark);
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/img/kantin.jpg') ;
            /* opacity: 0.03; */
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            
        }

        .login-container {
            max-width: 1200px;
            margin: 2rem auto;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            background: white;
            position: relative;
            z-index: 1;
            min-height: 700px;
        }

        .login-hero {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            animation: rotate 20s linear infinite;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
            text-align: center;
            padding: 2rem;
            max-width: 80%;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .features-list {
            text-align: left;
            margin-top: 3rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: var(--glass-border);
            border-radius: var(--radius-md);
            padding: 1rem;
            transition: var(--transition);
        }

        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--accent-light);
            min-width: 40px;
            text-align: center;
        }

        .feature-text h5 {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .feature-text p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .login-form-wrapper {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
            background: white;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-wrapper {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 90px;
            height: 90px;
            /* background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); */
            border-radius: 50%;
            margin-bottom: 1.5rem;
            /* box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); */
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .logo-wrapper::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            /* background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 70%); */
            animation: rotate 15s linear infinite;
        }

        .logo-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            position: relative;
            z-index: 1;
        }

        .form-title {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 2rem;
            position: relative;
            display: inline-block;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 3px;
        }

        .form-subtitle {
            color: var(--dark-gray);
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .form-control {
            border-radius: var(--radius-sm);
            padding: 0.85rem 1.25rem;
            border: 1px solid #e0e0e0;
            transition: var(--transition);
            font-size: 0.95rem;
            background-color: var(--light-gray);
            border: none;
        }

        .form-control:focus {
            background-color: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            border-color: var(--primary-light);
        }

        .input-group-text {
            background-color: var(--light-gray);
            border: none;
            color: var(--dark-gray);
            transition: var(--transition);
        }

        .form-floating label {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: var(--radius-sm);
            padding: 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
            width: 100%;
            color: white;
            font-size: 1rem;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--dark-gray);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e9ecef;
        }

        .divider::before {
            margin-right: 1rem;
        }

        .divider::after {
            margin-left: 1rem;
        }

        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .social-btn {
            flex: 1;
            border-radius: var(--radius-sm);
            padding: 0.75rem;
            text-align: center;
            transition: var(--transition);
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid #e9ecef;
            color: var(--dark);
        }

        .social-btn i {
            margin-right: 8px;
            font-size: 1.1rem;
        }

        .social-btn.google:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: #db4437;
            border-color: #db4437;
        }

        .social-btn.facebook {
            background-color: #1877f2;
            color: white;
            border-color: #1877f2;
        }

        .social-btn.facebook:hover {
            background-color: #166fe5;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }

        .form-footer a:hover {
            color: var(--accent);
        }

        .form-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--accent);
            transition: var(--transition);
        }

        .form-footer a:hover::after {
            width: 100%;
        }

        /* Error message styling */
        .alert-error {
            background-color: rgba(239, 35, 60, 0.08);
            border-left: 3px solid var(--danger);
            color: var(--danger);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .alert-error i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        /* Password toggle */
        .password-toggle {
            cursor: pointer;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            border: none;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Floating labels */
        .form-floating>.form-control:focus~label,
        .form-floating>.form-control:not(:placeholder-shown)~label,
        .form-floating>.form-select~label {
            color: var(--primary);
            opacity: 1;
            transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem);
        }

        /* Animation */
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                min-height: auto;
                margin: 1rem;
            }
            
            .login-hero {
                display: none;
            }
            
            .login-form-wrapper {
                padding: 2rem;
            }
        }

        @media (max-width: 576px) {
            .login-form-wrapper {
                padding: 1.5rem;
            }
            
            .form-header {
                margin-bottom: 2rem;
            }
            
            .logo-wrapper {
                width: 80px;
                height: 80px;
            }
            
            .logo-img {
                width: 45px;
                height: 45px;
            }
            
            .form-title {
                font-size: 1.8rem;
            }
            
            .social-login {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid login-container d-flex animate__animated animate__fadeIn">
        <!-- Hero Section -->
        <div class="col-md-6 login-hero d-none d-lg-flex">
            <div class="hero-content">
                <h1 class="hero-title animate__animated animate__fadeInDown">Selamat Datang</h1>
                <p class="hero-subtitle animate__animated animate__fadeIn">Sistem Manajemen Kantin Digital Modern dengan Pengalaman Pengguna Terbaik</p>
                
                <div class="features-list">
                    <div class="feature-item animate__animated animate__fadeInLeft">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="feature-text">
                            <h5>Transaksi Kilat</h5>
                            <p>Proses pembayaran hanya dalam hitungan detik</p>
                        </div>
                    </div>
                    
                    <div class="feature-item animate__animated animate__fadeInLeft animate__delay-1s">
                        <div class="feature-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="feature-text">
                            <h5>Analisis Real-time</h5>
                            <p>Pantau penjualan dan inventaris secara langsung</p>
                        </div>
                    </div>
                    
                    <div class="feature-item animate__animated animate__fadeInLeft animate__delay-2s">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-text">
                            <h5>Keamanan Terjamin</h5>
                            <p>Proteksi data dengan enkripsi tingkat tinggi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Login Form Section -->
        <div class="col-md-6 login-form-wrapper">
            <div class="form-header">
                <div class="logo-wrapper animate__animated animate__bounceIn">
                    <img src="assets/img/Smk6-removebg-preview.png" alt="Logo Kantin Digital" class="logo-img">
                </div>
                <h1 class="form-title">Kantin Digital</h1>
                <p class="form-subtitle">Masukkan kredensial Anda untuk mengakses sistem</p>
            </div>
            
            <?php if(!empty($error)): ?>
                <div class="alert-error animate__animated animate__fadeIn">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="animate__animated animate__fadeIn animate__delay-1s">
                <div class="form-floating mb-4">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label small" for="rememberMe">Ingat saya</label>
                    </div>
                    <a href="#" class="small text-decoration-none" style="color: var(--primary);">Lupa password?</a>
                </div>
                
                <button type="submit" class="btn btn-login mb-4">
                    <i class="fas fa-sign-in-alt me-2"></i> Masuk Sekarang
                </button>
                
                <div class="divider">Atau lanjutkan dengan</div>
                
                <div class="social-login">
                    <a href="#" class="social-btn google">
                        <i class="fab fa-google"></i> Google
                    </a>
                    <a href="#" class="social-btn facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                </div>
                
                <div class="form-footer">
                    <p>Belum memiliki akun? <a href="#">Daftar disini</a></p>
                    <p class="mt-2"><small>&copy; <?php echo date('Y'); ?> Kantin Digital. All rights reserved.</small></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Add floating label effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-group-text').style.color = 'var(--primary)';
                this.parentElement.querySelector('.input-group-text').style.backgroundColor = 'rgba(67, 97, 238, 0.05)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-group-text').style.color = 'var(--dark-gray)';
                this.parentElement.querySelector('.input-group-text').style.backgroundColor = 'var(--light-gray)';
            });
        });

        // Add ripple effect to buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - e.target.getBoundingClientRect().left;
                const y = e.clientY - e.target.getBoundingClientRect().top;
                
                const ripple = document.createElement('span');
                ripple.classList.add('ripple-effect');
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 1000);
            });
        });
    </script>
</body>
</html>