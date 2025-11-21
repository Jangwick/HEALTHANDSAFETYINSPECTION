<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Health & Safety Inspections System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-20 w-20 bg-blue-600 rounded-full flex items-center justify-center mb-4 shadow-lg">
                    <i class="fas fa-user-plus text-white text-3xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900 mb-2">
                    Create Your Account
                </h2>
                <p class="text-sm text-gray-600">
                    Register for Health & Safety Inspections System
                </p>
            </div>

            <!-- Registration Form -->
            <div class="bg-white rounded-xl shadow-2xl p-8">
                <form id="registerForm" class="space-y-6" onsubmit="handleRegister(event)">
                    <!-- Account Type Selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-user-tag mr-2 text-blue-600"></i>Account Type
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 hover:border-blue-500 transition">
                                <input type="radio" name="role" value="establishment_owner" checked class="sr-only peer">
                                <div class="flex items-center w-full">
                                    <div class="text-sm flex-1">
                                        <p class="font-semibold text-gray-900">Establishment Owner</p>
                                        <p class="text-gray-500 text-xs mt-1">Business/Facility Owner</p>
                                    </div>
                                    <i class="fas fa-store text-blue-600 text-2xl"></i>
                                </div>
                                <div class="absolute -inset-px rounded-lg border-2 border-blue-600 opacity-0 peer-checked:opacity-100"></div>
                            </label>
                            <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 hover:border-blue-500 transition">
                                <input type="radio" name="role" value="public" class="sr-only peer">
                                <div class="flex items-center w-full">
                                    <div class="text-sm flex-1">
                                        <p class="font-semibold text-gray-900">Public User</p>
                                        <p class="text-gray-500 text-xs mt-1">General Access</p>
                                    </div>
                                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                                </div>
                                <div class="absolute -inset-px rounded-lg border-2 border-blue-600 opacity-0 peer-checked:opacity-100"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    First Name *
                                </label>
                                <input 
                                    id="first_name" 
                                    name="first_name" 
                                    type="text" 
                                    required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Juan"
                                >
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Last Name *
                                </label>
                                <input 
                                    id="last_name" 
                                    name="last_name" 
                                    type="text" 
                                    required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Dela Cruz"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address *
                            </label>
                            <input 
                                id="email" 
                                name="email" 
                                type="email" 
                                required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="juan@example.com"
                            >
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number
                            </label>
                            <input 
                                id="phone" 
                                name="phone" 
                                type="tel" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="+63 912 345 6789"
                            >
                        </div>
                    </div>

                    <!-- Account Credentials -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Credentials</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                                    Username *
                                </label>
                                <input 
                                    id="username" 
                                    name="username" 
                                    type="text" 
                                    required 
                                    minlength="3"
                                    maxlength="50"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="juan.delacruz"
                                >
                                <p class="mt-1 text-xs text-gray-500">3-50 characters, alphanumeric and underscores only</p>
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    Password *
                                </label>
                                <div class="relative">
                                    <input 
                                        id="password" 
                                        name="password" 
                                        type="password" 
                                        required 
                                        minlength="8"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Enter password"
                                        oninput="checkPasswordStrength()"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="togglePassword('password')"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                    >
                                        <i id="toggleIcon1" class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordStrength" class="mt-2 hidden">
                                    <div class="flex gap-1">
                                        <div id="strength1" class="h-1 flex-1 bg-gray-300 rounded"></div>
                                        <div id="strength2" class="h-1 flex-1 bg-gray-300 rounded"></div>
                                        <div id="strength3" class="h-1 flex-1 bg-gray-300 rounded"></div>
                                        <div id="strength4" class="h-1 flex-1 bg-gray-300 rounded"></div>
                                    </div>
                                    <p id="strengthText" class="text-xs mt-1"></p>
                                </div>
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    Confirm Password *
                                </label>
                                <div class="relative">
                                    <input 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        type="password" 
                                        required 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Confirm password"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="togglePassword('confirm_password')"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                    >
                                        <i id="toggleIcon2" class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="flex items-start">
                        <input 
                            id="terms" 
                            name="terms" 
                            type="checkbox" 
                            required
                            class="h-4 w-4 mt-1 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="terms" class="ml-2 block text-sm text-gray-700">
                            I agree to the <a href="#" class="text-blue-600 hover:text-blue-500">Terms of Service</a> and 
                            <a href="#" class="text-blue-600 hover:text-blue-500">Privacy Policy</a>
                        </label>
                    </div>

                    <!-- Error/Success Alert -->
                    <div id="alertBox" class="hidden"></div>

                    <!-- Submit Button -->
                    <div>
                        <button 
                            type="submit" 
                            id="registerBtn"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 shadow-md hover:shadow-lg"
                        >
                            <i class="fas fa-user-plus mr-2"></i>
                            <span id="registerBtnText">Create Account</span>
                        </button>
                    </div>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Already have an account? 
                        <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                            Sign in here
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(fieldId === 'password' ? 'toggleIcon1' : 'toggleIcon2');
            
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

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthDiv.classList.add('hidden');
                return;
            }
            
            strengthDiv.classList.remove('hidden');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            const strengthBars = ['strength1', 'strength2', 'strength3', 'strength4'];
            strengthBars.forEach(bar => {
                document.getElementById(bar).className = 'h-1 flex-1 bg-gray-300 rounded';
            });
            
            if (strength <= 2) {
                strengthText.textContent = 'Weak password';
                strengthText.className = 'text-xs mt-1 text-red-600';
                document.getElementById('strength1').classList.add('bg-red-500');
            } else if (strength === 3) {
                strengthText.textContent = 'Fair password';
                strengthText.className = 'text-xs mt-1 text-yellow-600';
                document.getElementById('strength1').classList.add('bg-yellow-500');
                document.getElementById('strength2').classList.add('bg-yellow-500');
            } else if (strength === 4) {
                strengthText.textContent = 'Good password';
                strengthText.className = 'text-xs mt-1 text-green-600';
                document.getElementById('strength1').classList.add('bg-green-500');
                document.getElementById('strength2').classList.add('bg-green-500');
                document.getElementById('strength3').classList.add('bg-green-500');
            } else {
                strengthText.textContent = 'Strong password';
                strengthText.className = 'text-xs mt-1 text-green-600';
                strengthBars.forEach(bar => {
                    document.getElementById(bar).classList.add('bg-green-500');
                });
            }
        }

        function showAlert(message, type = 'error') {
            const alertBox = document.getElementById('alertBox');
            const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
            const bgColor = type === 'error' ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200';
            const textColor = type === 'error' ? 'text-red-800' : 'text-green-800';
            const iconColor = type === 'error' ? 'text-red-500' : 'text-green-500';
            
            alertBox.className = `rounded-lg ${bgColor} border p-4`;
            alertBox.innerHTML = `
                <div class="flex items-start">
                    <i class="fas ${icon} ${iconColor} mt-0.5 mr-3"></i>
                    <p class="text-sm font-medium ${textColor}">${message}</p>
                </div>
            `;
            alertBox.classList.remove('hidden');
        }

        async function handleRegister(event) {
            event.preventDefault();
            
            const registerBtn = document.getElementById('registerBtn');
            const registerBtnText = document.getElementById('registerBtnText');
            
            // Validate password match
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'error');
                return;
            }
            
            // Disable button
            registerBtn.disabled = true;
            registerBtnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating account...';
            
            // Get form data
            const formData = {
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: password,
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                phone: document.getElementById('phone').value,
                role: document.querySelector('input[name="role"]:checked').value
            };
            
            try {
                const response = await fetch('/api/v1/auth/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('Account created successfully! Please wait for activation.', 'success');
                    registerBtnText.innerHTML = '<i class="fas fa-check mr-2"></i>Success!';
                    
                    // Redirect to login after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showAlert(data.error.message, 'error');
                    registerBtn.disabled = false;
                    registerBtnText.innerHTML = 'Create Account';
                }
            } catch (error) {
                console.error('Registration error:', error);
                showAlert('Network error. Please try again.', 'error');
                registerBtn.disabled = false;
                registerBtnText.innerHTML = 'Create Account';
            }
        }
    </script>
</body>
</html>
