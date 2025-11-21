<?php
session_start();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../../config/database.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Get database connection
        $db = Database::getConnection();
        
        // Find user by email or username
        $stmt = $db->prepare("
            SELECT u.*, r.role_name, r.permissions 
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE u.email = ? OR u.username = ?
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['logged_in'] = true;
            
            // Redirect to dashboard
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    } catch (PDOException $e) {
        $error = 'Database error. Please try again.';
        error_log('Login error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Health & Safety Inspections System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo and Header -->
            <div class="text-center">
                <div class="mx-auto h-24 w-24 bg-blue-600 rounded-full flex items-center justify-center mb-6 shadow-lg">
                    <i class="fas fa-shield-alt text-white text-4xl"></i>
                </div>
                <h2 class="text-4xl font-extrabold text-gray-900 mb-2">
                    Health & Safety Inspections
                </h2>
                <p class="text-sm text-gray-600 mb-1">
                    Local Government Unit - Module 8
                </p>
                <p class="text-lg font-semibold text-blue-600">
                    Sign in to your account
                </p>
            </div>

            <!-- Login Form -->
            <div class="bg-white rounded-xl shadow-2xl p-8">
                <?php if (isset($error)): ?>
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-blue-600"></i>Username or Email
                        </label>
                        <input 
                            id="username" 
                            name="username" 
                            type="text" 
                            required 
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            class="appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
                            placeholder="Enter your username or email"
                        >
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-blue-600"></i>Password
                        </label>
                        <div class="relative">
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required 
                                autocomplete="current-password"
                                class="appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
                                placeholder="Enter your password"
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                            >
                                <i id="toggleIcon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                id="remember-me" 
                                name="remember-me" 
                                type="checkbox" 
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer"
                            >
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700 cursor-pointer">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="forgot-password.php" class="font-medium text-blue-600 hover:text-blue-500 transition duration-150">
                                Forgot password?
                            </a>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button 
                            type="submit" 
                            class="group relative w-full flex justify-center items-center py-3 px-4 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 shadow-md hover:shadow-lg"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            <span>Sign in</span>
                        </button>
                    </div>
                </form>

                <!-- Divider -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">New to the system?</span>
                        </div>
                    </div>
                </div>

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <a href="register.php" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500 transition duration-150">
                        <i class="fas fa-user-plus mr-2"></i>
                        Request an account
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-600">
                <p>&copy; 2025 Local Government Unit. All rights reserved.</p>
                <p class="mt-1">Health & Safety Inspections System v1.0</p>
            </div>
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

        // Auto-focus username field on page load
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>
