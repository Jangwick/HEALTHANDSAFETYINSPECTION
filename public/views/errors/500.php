<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }
        
        .error-title {
            font-size: 2rem;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-details {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .error-details h3 {
            color: #dc2626;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .error-details p {
            color: #374151;
            font-size: 0.9rem;
            word-break: break-all;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            margin-left: 1rem;
        }
        
        .btn-secondary:hover {
            background: #d1d5db;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 768px) {
            .error-code {
                font-size: 5rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-container {
                padding: 2rem;
            }
            
            .btn {
                display: block;
                margin: 0.5rem 0;
            }
            
            .btn-secondary {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <h1 class="error-title">Internal Server Error</h1>
        <p class="error-message">
            Oops! Something went wrong on our server. We're working to fix the issue.
        </p>
        
        <?php if (isset($error) && $_ENV['APP_ENV'] !== 'production'): ?>
        <div class="error-details">
            <h3>Error Details (Development Mode)</h3>
            <p><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>
        
        <div>
            <a href="/" class="btn">Go to Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>
    </div>
</body>
</html>
