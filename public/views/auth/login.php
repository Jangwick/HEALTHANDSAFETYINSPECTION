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
                <form id="loginForm" class="space-y-6" onsubmit="handleLogin(event)">
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

                    <!-- Error Alert -->
                    <div id="errorAlert" class="hidden rounded-lg bg-red-50 border border-red-200 p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                            <div class="flex-1">
                                <p id="errorMessage" class="text-sm font-medium text-red-800"></p>
                            </div>
                            <button onclick="closeError()" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button 
                            type="submit" 
                            id="loginBtn"
                            class="group relative w-full flex justify-center items-center py-3 px-4 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 shadow-md hover:shadow-lg"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            <span id="loginBtnText">Sign in</span>
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

        function closeError() {
            document.getElementById('errorAlert').classList.add('hidden');
        }

        async function handleLogin(event) {
            event.preventDefault();
            
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const errorAlert = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');
            
            // Disable button and show loading state
            loginBtn.disabled = true;
            loginBtnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';
            
            // Hide previous errors
            errorAlert.classList.add('hidden');
            
            // Get form data
            const formData = {
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
                remember_me: document.getElementById('remember-me').checked
            };
            
            try {
                const response = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Store token in localStorage
                    localStorage.setItem('access_token', data.data.token.access_token);
                    localStorage.setItem('user', JSON.stringify(data.data.user));
                    
                    // Show success message
                    loginBtnText.innerHTML = '<i class="fas fa-check mr-2"></i>Success! Redirecting...';
                    
                    // Redirect to dashboard
                    setTimeout(() => {
                        window.location.href = '/dashboard.php';
                    }, 500);
                } else {
                    // Show error
                    errorMessage.textContent = data.error.message;
                    errorAlert.classList.remove('hidden');
                    
                    // Re-enable button
                    loginBtn.disabled = false;
                    loginBtnText.innerHTML = 'Sign in';
                }
            } catch (error) {
                console.error('Login error:', error);
                errorMessage.textContent = 'Network error. Please check your connection and try again.';
                errorAlert.classList.remove('hidden');
                loginBtn.disabled = false;
                loginBtnText.innerHTML = 'Sign in';
            }
        }

        // Auto-focus username field on page load
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>
