<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Chatbot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .logo-text {
            color: #333;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .logo-subtitle {
            color: #999;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: 500;
        }

        .forgot-password {
            font-size: 13px;
            margin-left: auto;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fff5f5;
            color: #c53030;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c53030;
            font-size: 14px;
        }

        .success-message {
            background: #f0fff4;
            color: #22543d;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #48bb78;
            font-size: 14px;
        }

        .info-box {
            background: #edf2f7;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: #4a5568;
            line-height: 1.6;
        }

        .info-box strong {
            display: block;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .demo-credentials {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 8px 0;
        }

        .demo-credentials div {
            margin: 4px 0;
        }

        .demo-label {
            color: #666;
            font-weight: bold;
        }

        .demo-value {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo">🤖</div>
            <div class="logo-text">Chatbot Admin</div>
            <div class="logo-subtitle">Management Panel</div>
        </div>

        <!-- Messages -->
        @if ($errors->any())
            <div class="error-message">
                <strong>Login Failed!</strong>
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('success'))
            <div class="success-message">
                {{ session('success') }}
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ route('admin.login') }}" method="POST">
            @csrf

            <!-- Email -->
            <div class="form-group">
                <label for="email">📧 Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus
                    placeholder="Enter your email"
                >
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">🔐 Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="Enter your password"
                >
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
                <div class="forgot-password">
                    <a href="#">Forgot password?</a>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-login">🔓 Login to Admin Panel</button>

           
        </form>
    </div>
</body>
</html>