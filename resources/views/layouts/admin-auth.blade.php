<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Login') - Fati Market</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- React Hot Toast -->
    <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/react-hot-toast/dist/index.css" />
    <script src="https://unpkg.com/react-hot-toast/dist/index.umd.js"></script>
    <style>
        :root {
            --dark-green: #1A5C38;
            --dark-green-light: #2E7D52;
            --gold: #D4A017;
            --gold-light: #FFD700;
            --off-white: #F5F5F0;
            --light-gray-bg: #EEEEE8;
            --white: #FFFFFF;
            --dark-text: #1C1B1F;
            --muted-text: #6B6B6B;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>
<body class="bg-[#F5F5F0] min-h-screen">
    <div id="toast-container" class="toast-container"></div>
    @yield('content')
    
    @stack('scripts')
</body>
</html>
