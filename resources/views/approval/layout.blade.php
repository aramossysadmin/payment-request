<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Autorización de Solicitud' }} - {{ config('app.name') }}</title>
    <link rel="icon" href="/images/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=open-sans:400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwindcss.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Open Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        navy: '#191731',
                        cream: '#EBDFC7',
                        gold: '#C5A059',
                        brand: {
                            bg: '#F8F6F1',
                            'bg-dark': '#0D0F1A',
                            card: '#FFFFFF',
                            'card-dark': '#191731',
                            muted: '#4A4670',
                            'muted-dark': '#A8A8C0',
                            secondary: '#EFE9DD',
                            'secondary-dark': '#1E1B3D',
                            border: '#D4C9A9',
                            'border-dark': '#2A2650',
                        },
                    },
                    borderRadius: {
                        DEFAULT: '0.625rem',
                    },
                },
            },
        }
    </script>
    <script>
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="min-h-screen bg-brand-bg dark:bg-brand-bg-dark font-sans antialiased flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <div class="flex justify-center mb-4 sm:mb-8">
            <img src="/images/logo_dark.svg" alt="{{ config('app.name') }}" class="h-10 dark:hidden">
            <img src="/images/logo_white.svg" alt="{{ config('app.name') }}" class="h-10 hidden dark:block">
        </div>

        @yield('content')

        <p class="text-center text-xs text-brand-muted dark:text-brand-muted-dark mt-6">
            {{ config('app.name') }} &copy; {{ date('Y') }}
        </p>
    </div>
</body>
</html>
