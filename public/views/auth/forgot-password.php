<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Health & Safety Inspections</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-20 w-20 bg-blue-600 rounded-full flex items-center justify-center mb-4 shadow-lg">
                    <i class="fas fa-key text-white text-3xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900 mb-2">
                    Forgot Your Password?
                </h2>
                <p class="text-sm text-gray-600">
                    Enter your email and we'll send you a reset link
                </p>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-2xl p-8">
                <form id="forgotPasswordForm" class="space-y-6" onsubmit="handleForgotPassword(event)">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-blue-600"></i>Email Address
                        </label>
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            required 
                            autocomplete="email"
                            class="appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
                            placeholder="Enter your email address"
                        >
                    </div>

                    <!-- Alert -->
                    <div id="alertBox" class="hidden"></div>

                    <!-- Submit Button -->
                    <div>
                        <button 
                            type="submit" 
                            id="submitBtn"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 shadow-md hover:shadow-lg"
                        >
                            <i class="fas fa-paper-plane mr-2"></i>
                            <span id="submitBtnText">Send Reset Link</span>
                        </button>
                    </div>
                </form>

                <!-- Back to Login -->
                <div class="mt-6 text-center">
                    <a href="login.php" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500 transition duration-150">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        async function handleForgotPassword(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            const alertBox = document.getElementById('alertBox');
            
            submitBtn.disabled = true;
            submitBtnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
            alertBox.classList.add('hidden');
            
            const formData = {
                email: document.getElementById('email').value
            };
            
            try {
                const response = await fetch('/api/v1/auth/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('Password reset link has been sent to your email.', 'success');
                    submitBtnText.innerHTML = '<i class="fas fa-check mr-2"></i>Email Sent';
                    
                    // For development: show reset token (remove in production)
                    if (data.data.reset_token) {
                        console.log('Reset Token (DEV ONLY):', data.data.reset_token);
                        setTimeout(() => {
                            window.location.href = `reset-password.php?token=${data.data.reset_token}`;
                        }, 2000);
                    }
                } else {
                    showAlert(data.error.message, 'error');
                    submitBtn.disabled = false;
                    submitBtnText.innerHTML = 'Send Reset Link';
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Network error. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtnText.innerHTML = 'Send Reset Link';
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>
