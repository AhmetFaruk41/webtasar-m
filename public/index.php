<?php
require_once '../config/app.php';

// Eğer kullanıcı zaten giriş yapmışsa dashboard'a yönlendir
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
    } elseif (empty($email) || empty($password)) {
        $error = 'E-posta ve şifre alanları zorunludur.';
    } else {
        $user = new User();
        
        try {
            if ($user->authenticate($email, $password)) {
                redirect('dashboard.php');
            } else {
                $error = 'E-posta veya şifre hatalı.';
            }
        } catch (Exception $e) {
            $error = 'Giriş yapılırken hata oluştu: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Giriş</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-20 w-20 bg-white rounded-full flex items-center justify-center shadow-lg">
                <i class="fas fa-building text-3xl text-indigo-600"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                <?php echo APP_NAME; ?>
            </h2>
            <p class="mt-2 text-center text-sm text-indigo-100">
                Hesabınızla giriş yapın
            </p>
        </div>

        <form class="mt-8 space-y-6" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="bg-white rounded-lg shadow-xl p-8">
                <?php if ($error): ?>
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            E-posta Adresi
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="email" 
                                name="email" 
                                type="email" 
                                autocomplete="email" 
                                required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                placeholder="E-posta adresinizi girin"
                                value="<?php echo htmlspecialchars($email ?? ''); ?>"
                            >
                            <i class="fas fa-envelope absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Şifre
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                autocomplete="current-password" 
                                required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                placeholder="Şifrenizi girin"
                            >
                            <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                            <button 
                                type="button" 
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600"
                                onclick="togglePassword()"
                            >
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                id="remember-me" 
                                name="remember-me" 
                                type="checkbox" 
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            >
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700">
                                Beni hatırla
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                                Şifremi unuttum
                            </a>
                        </div>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out"
                        >
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-sign-in-alt text-indigo-500 group-hover:text-indigo-400"></i>
                            </span>
                            Giriş Yap
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="text-center">
            <p class="text-sm text-indigo-100">
                Test hesabı: admin@company.com / admin123
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
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

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Lütfen tüm alanları doldurun.');
                return;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('Lütfen geçerli bir e-posta adresi girin.');
                return;
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
</body>
</html>