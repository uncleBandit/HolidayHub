<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'HolidayHub') }}</title>

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom CSS for advanced effects -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0d111c;
            /* A subtle, animated background with multiple celestial-like gradients */
            background-image: radial-gradient(at 20% 70%, hsla(208, 60%, 25%, 0.8) 0px, transparent 50%),
                              radial-gradient(at 80% 0%, hsla(271, 51%, 25%, 0.7) 0px, transparent 50%),
                              radial-gradient(at 50% 100%, hsla(170, 50%, 30%, 0.6) 0px, transparent 50%);
            background-size: 300% 300%;
            animation: backgroundPan 15s ease infinite alternate;
        }

        /* Keyframes for the subtle background animation */
        @keyframes backgroundPan {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }

        /* Glassmorphism effect for the container */
        .glass-container {
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            background-color: rgba(17, 25, 40, 0.5); /* A dark, semi-transparent background */
            border: 1px solid rgba(255, 255, 255, 0.125);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        /* Enhanced logo animation for more flair */
        .logo-text {
            animation: logo-glow 2s ease-in-out infinite alternate;
        }
        @keyframes logo-glow {
            from { text-shadow: 0 0 5px #48bb78, 0 0 10px #60a5fa; }
            to { text-shadow: 0 0 10px #34d399, 0 0 20px #60a5fa, 0 0 30px #a855f7; }
        }
    </style>
    @livewireStyles

</head>
<body class="text-white antialiased flex flex-col justify-center items-center min-h-screen p-4 sm:p-6 lg:p-8 space-y-8">
    <!-- Brand/Logo Section: Animated and elegant -->
    <div class="text-center w-full max-w-lg">
        <h1 class="text-5xl md:text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-teal-400 via-sky-400 to-purple-500 logo-text">
            HolidayHub
        </h1>
        <p class="mt-2 text-zinc-400 text-lg sm:text-xl font-medium tracking-wide">
            Your Next-Gen Holiday Booking System
        </p>
    </div>

    {{-- The content from the Livewire component will be inserted here, centered by the body --}}
    {{ $slot }}

    @livewireScripts

</body>
</html>
