<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petrichor</title>
    <link rel="icon" type="image/png" href="<?= base_url('logo.png') ?>">
    <link rel="stylesheet" href="<?= base_url('css/output.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top right, #1e293b, #0f172a, #020617);
        }

        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .spotify-gradient {
            background: linear-gradient(135deg, #1DB954 0%, #191414 100%);
        }
    </style>
</head>
<body class="text-slate-100 min-h-screen flex flex-col items-center justify-center p-6 overflow-hidden relative">

    <!-- Decorative Elements -->
    <div class="absolute top-0 -left-20 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 -right-20 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl"></div>

    <div class="max-w-md w-full glass rounded-[2.5rem] shadow-2xl p-10 relative z-10 overflow-hidden">
        
        <header class="text-center mb-12 relative">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-500/20 rounded-2xl mb-6 animate-float">
                <i data-lucide="cloud-rain" class="w-8 h-8 text-blue-400"></i>
            </div>
            <h1 class="text-5xl font-black tracking-tighter mb-2 bg-clip-text text-transparent bg-gradient-to-r from-blue-400 via-indigo-400 to-emerald-400">
                PETRICHOR
            </h1>
            <p class="text-slate-400 text-sm font-medium tracking-widest uppercase">Atmospheric Playlist Generator</p>
        </header>

        <!-- Weather Section -->
        <div id="weather-card" class="hidden transition-all duration-700 transform">
            <div class="bg-slate-900/40 p-6 rounded-3xl mb-8 border border-white/5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <i data-lucide="map-pin" class="w-3 h-3 text-slate-500"></i>
                            <p id="city-name" class="text-sm font-semibold text-slate-300">Detecting...</p>
                        </div>
                        <p id="weather-desc" class="text-xl font-bold capitalize">Clear Sky</p>
                    </div>
                    <div class="text-right">
                        <p id="temp" class="text-5xl font-black bg-clip-text text-transparent bg-gradient-to-br from-white to-slate-500">0°</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <button id="btn-spotify" 
                        onclick="<?= session()->get('is_logged_in') ? 'createPlaylist()' : "window.location.href='/login'" ?>"
                        class="group w-full bg-[#1DB954] hover:bg-[#1ed760] text-black font-bold py-4 rounded-2xl transition-all flex items-center justify-center gap-3 shadow-xl hover:shadow-[#1DB954]/20 hover:-translate-y-1 active:translate-y-0">
                    <i data-lucide="music-4" class="w-5 h-5 group-hover:rotate-12 transition-transform"></i>
                    <span><?= session()->get('is_logged_in') ? 'Create Your Playlist' : 'Connect Spotify' ?></span>
                </button>

                <?php if (session()->get('is_logged_in')): ?>
                    <button onclick="window.location.href='/logout'" class="w-full text-slate-500 text-xs hover:text-red-400 transition-colors flex items-center justify-center gap-1">
                        <i data-lucide="log-out" class="w-3 h-3"></i> Disconnect Account
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- States -->
        <div id="loading" class="text-center py-12">
            <div class="relative w-16 h-16 mx-auto mb-6">
                <div class="absolute inset-0 border-4 border-blue-500/20 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-blue-400 rounded-full border-t-transparent animate-spin"></div>
            </div>
            <p class="text-slate-400 font-medium animate-pulse">Syncing with nature...</p>
        </div>

        <div id="error-card" class="hidden animate-in slide-in-from-top-4 duration-300">
            <div class="bg-red-500/10 border border-red-500/20 p-5 rounded-2xl">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0"></i>
                    <div id="error-msg" class="text-sm text-red-400 leading-relaxed"></div>
                </div>
            </div>
            <button onclick="location.reload()" class="mt-4 w-full text-slate-500 text-xs underline underline-offset-4">Try again</button>
        </div>

        <div id="success-card" class="hidden animate-in zoom-in duration-500">
            <div class="text-center">
                <div class="w-20 h-20 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="check-circle-2" class="w-10 h-10 text-emerald-400"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Vibe Ready!</h3>
                <p class="text-slate-400 text-sm mb-8">We've tailored a new playlist based on the sky in <span id="success-city" class="text-emerald-400"></span>.</p>
                
                <a id="playlist-link" href="#" target="_blank" class="block w-full bg-white text-black py-4 rounded-2xl font-bold hover:scale-[1.02] transition-transform flex items-center justify-center gap-2">
                    Open in Spotify <i data-lucide="external-link" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>

    <footer class="mt-12 text-slate-600 text-[0.65rem] uppercase tracking-[0.2em] font-bold">
        Petrichor &copy; <?= date('Y') ?>
    </footer>

    <script>
        let currentWeatherData = null;
        const weatherCard = document.getElementById('weather-card');
        const loading = document.getElementById('loading');
        const errorCard = document.getElementById('error-card');
        const successCard = document.getElementById('success-card');
        const btnSpotify = document.getElementById('btn-spotify');

        // Initialize Lucide Icons
        lucide.createIcons();

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(fetchWeather, showError);
            } else {
                showError({ message: "Geolocation is not supported by this browser." });
            }
        }

        async function fetchWeather(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            try {
                const response = await fetch(`<?= base_url('get-weather') ?>?lat=${lat}&lon=${lon}`);
                const data = await response.json();

                if (data.error) throw data;

                currentWeatherData = data;
                document.getElementById('city-name').innerText = data.name;
                document.getElementById('weather-desc').innerText = data.weather[0].description;
                document.getElementById('temp').innerText = `${Math.round(data.main.temp)}°`;

                loading.classList.add('hidden');
                weatherCard.classList.remove('hidden');
                weatherCard.classList.add('opacity-100');
            } catch (err) {
                showError(err);
            }
        }

        async function createPlaylist() {
            if (!currentWeatherData) return;

            btnSpotify.disabled = true;
            btnSpotify.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-black"></div>';

            const formData = new FormData();
            formData.append('weather', currentWeatherData.weather[0].main);
            formData.append('city', currentWeatherData.name);

            try {
                const response = await fetch('<?= base_url('create-playlist') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.error) {
                    showError(data);
                    btnSpotify.disabled = false;
                    btnSpotify.innerHTML = '<i data-lucide="music-4" class="w-5 h-5"></i> Create Your Playlist';
                    lucide.createIcons();
                    return;
                }

                weatherCard.classList.add('hidden');
                successCard.classList.remove('hidden');
                document.getElementById('success-city').innerText = currentWeatherData.name;
                document.getElementById('playlist-link').href = data.playlist_url;

            } catch (err) {
                showError(err);
                btnSpotify.disabled = false;
                btnSpotify.innerHTML = 'Create Your Playlist';
            }
        }

        function showError(error) {
            console.error('Full Error:', error);
            loading.classList.add('hidden');
            weatherCard.classList.add('hidden');
            errorCard.classList.remove('hidden');
            
            let displayMessage = '';

            if (typeof error === 'object') {
                if (error.error && error.message) {
                    displayMessage = `<div class="font-bold text-red-500 mb-1">${error.error}</div><div class="text-xs opacity-70">${error.message}</div>`;
                } else if (error.debug_info) {
                    displayMessage = `
                        <div class="font-bold text-red-500 mb-1">${error.error}</div>
                        <div class="opacity-70 text-[10px]">
                            Playlist ID: ${error.debug_info.playlist_id || 'N/A'}<br>
                            Tracks: ${error.debug_info.track_uris_sent ? error.debug_info.track_uris_sent.length : 0} items<br>
                            Detail: ${error.body || 'Forbidden Access'}
                        </div>
                    `;
                } else {
                    displayMessage = error.message || error.error || "Failed to fetch data from nature.";
                }
            } else {
                displayMessage = error;
            }

            document.getElementById('error-msg').innerHTML = displayMessage;
        }

        window.onload = getLocation;
    </script>
</body>
</html>
