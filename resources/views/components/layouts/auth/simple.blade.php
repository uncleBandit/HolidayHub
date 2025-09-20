<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Holiday Hub - Next-Gen Booking</title>
    <!-- Use Tailwind CSS for rapid styling -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ocean-blue-dark: #000F1F;
            --ocean-blue-light: #002D59;
            --wave-color: #002040;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--ocean-blue-dark) 0%, var(--ocean-blue-light) 100%);
            overflow: hidden; /* Hide scrollbars for the waves animation */
        }

        /* Frosted Glass effect for the card */
        .frosted-glass-card {
            background-color: rgba(255, 255, 255, 0.08); /* Semi-transparent white */
            backdrop-filter: blur(10px) saturate(180%);
            -webkit-backdrop-filter: blur(10px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Keyframes for a gentle, rhythmic wave animation */
        @keyframes subtle-waves {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        /* Create the wavy background element */
        .wave-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                var(--wave-color) 0%,
                transparent 15%,
                var(--wave-color) 30%,
                transparent 45%,
                var(--wave-color) 60%,
                transparent 75%,
                var(--wave-color) 90%
            );
            background-size: 400% 400%;
            animation: subtle-waves 60s ease infinite;
            opacity: 0.3;
        }

        /* Custom icon colors and glow */
        .app-icon {
            color: #4dc0b5; /* A bright, teal color */
            text-shadow: 0 0 10px #4dc0b5;
        }
    </style>
</head>

<body class="min-h-screen antialiased text-white">
    <!-- Wave background animation -->
    <div class="wave-background"></div>

    <div class="relative z-10 flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
        <!-- Main card container -->
        <div class="frosted-glass-card w-full max-w-lg overflow-hidden rounded-2xl shadow-2xl p-8 flex flex-col gap-6">

            <!-- App Logo and Name -->
            <a href="#" class="flex flex-col items-center gap-4 text-white hover:text-teal-400 transition-colors duration-300">
                <span class="flex h-16 w-16 mb-2 items-center justify-center rounded-full bg-transparent border-2 border-teal-400 p-2">
                    <!-- Placeholder for your app logo icon -->
                    <svg class="h-12 w-12 app-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16zm-1.5-6.5h3a.5.5 0 0 0 0-1h-3a.5.5 0 0 0 0 1zM12 9a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1z"/>
                    </svg>
                </span>
                <span class="text-3xl font-bold tracking-tight">Holiday Hub</span>
            </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
