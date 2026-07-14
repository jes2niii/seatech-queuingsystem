<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="{{ asset('css/mainView.css') }}?v={{ time() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Now Serving | SEATECH</title>
</head>
<body>
    <div class="tv-body">

        <header class="tv-header">
            <div class="tv-brand">
                <img src="/img/seatechLogo.png" alt="SEATECH">
                <div class="tv-brand-text">
                    <h1>SEATECH Maritime Training &amp; Assessment Center Inc.</h1>
                    <p>Queueing System &middot; Legazpi</p>
                </div>
            </div>
            <div class="tv-now-serving-badge">NOW SERVING</div>
        </header>

        <main class="tv-main">

            <section class="tv-panel">
                <div class="tv-panel-header">
                    <i class="bi bi-camera-reels-fill"></i> Promotional Content
                </div>
                <div class="tv-panel-body">
                    @if($videos->isNotEmpty())
                        <video id="tvPlayer" class="tv-video" playsinline preload="auto" muted>
                            Your browser does not support the video tag.
                        </video>
                        <video id="tvPlayer2" class="tv-video tv-video-hidden" playsinline preload="auto" muted>
                            Your browser does not support the video tag.
                        </video>
                    @else
                        <div class="no-video-placeholder">
                            <img src="/img/seatechLogo.png" alt="SEATECH">
                            <p>No video content available</p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="tv-panel">
                <div class="tv-panel-header">
                    <i class="bi bi-megaphone-fill"></i> Currently Being Served
                </div>
                <div class="tv-panel-body">
                    @php
                        $regularUsers = $users->filter(function ($user) {
                            return in_array(strtolower((string) $user->usertype), ['regular', 'cashier', 'releasing'], true);
                        })->values();
                    @endphp
                    <div class="tv-serving-list">
                        @forelse ($regularUsers as $user)
                            <div class="tv-serving-item" id="serving-row-{{ $user->id }}">
                                <div class="tv-serving-name">{{ $user->name }}</div>
                                <div class="tv-serving-ticket" id="serving-{{ $user->id }}">
                                    {{ $user->servingTicket ? $user->servingTicket->ticket_no : '—' }}
                                </div>
                            </div>
                        @empty
                            <div class="tv-serving-empty">No staff available at the moment.</div>
                        @endforelse
                    </div>
                </div>
            </section>

        </main>

        <div class="tv-clock">
            <div class="tv-clock-time" id="tvTime">--:--:--</div>
            <div class="tv-clock-date" id="tvDate">—</div>
        </div>

        <audio id="tvSound" src="{{ asset('sounds/call.mp3') }}" preload="auto"></audio>
    </div>

    <script>
        let videos = [
            @foreach($videos as $video)
                "{{ asset('vid/' . $video) }}",
            @endforeach
        ];
    </script>
    <script src="{{ asset('js/mainView.js') }}"></script>

    <script>
        let playCount = 0;
        const maxPlays = 3;
        const audio = document.getElementById('tvSound');
        const tvPlayer = document.getElementById("tvPlayer");
        const tvPlayer2 = document.getElementById("tvPlayer2");

        document.addEventListener('click', function unlockAll() {
            if (audio) {
                audio.play().then(() => {
                    audio.pause();
                    audio.currentTime = 0;
                }).catch(()=>{});
            }
            if (tvPlayer) {
                tvPlayer.volume = 0.25;
                tvPlayer.muted = false;
            }
            if (tvPlayer2) {
                tvPlayer2.volume = 0.25;
                tvPlayer2.muted = false;
            }
            document.removeEventListener('click', unlockAll);
        });

        audio.addEventListener('ended', () => {
            playCount++;
            if (playCount < maxPlays) {
                audio.currentTime = 0;
                audio.play();
            } else {
                playCount = 0;
            }
        });

        function playSound3Times() {
            playCount = 0;
            audio.currentTime = 0;
            audio.play();
        }

        // Clock
        function updateTvClock() {
            const now = new Date();
            let h = now.getHours();
            let m = now.getMinutes();
            let s = now.getSeconds();
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            const pad = (n) => String(n).padStart(2, '0');
            document.getElementById('tvTime').textContent =
                pad(h) + ':' + pad(m) + ':' + pad(s) + ' ' + ampm;
            const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            document.getElementById('tvDate').textContent =
                days[now.getDay()] + ', ' + months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear();
        }
        setInterval(updateTvClock, 1000);
        updateTvClock();
    </script>
</body>
</html>
