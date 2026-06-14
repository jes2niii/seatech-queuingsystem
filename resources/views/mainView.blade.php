<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="{{ asset('css/mainView.css') }}?v={{ time() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <title>Main View</title>

</head>
<body>
    <div class="container-fluid">
        <div class="mainBody">

            <div class="top-header">
                <div class="companyName">
                    <img src="/img/seatechLogo.png" alt="Logo">
                    <p>SEATECH MARITIME TRAINING AND <br>ASSESSMENT CENTER INC,. LEGAZPI <br> Queueing System</p>
                </div>
                <div class="serve">NOW SERVING!</div>
            </div>

            <div class="tableInfo">
                <table>
                    <tr>
                        @php $rowCount = max(1, $users->where('usertype', 'Regular')->count()); @endphp
                        <td class="left" rowspan="{{ $rowCount }}">
                           @if($videos->isNotEmpty())
                               <video id="tvPlayer" width="580" playsinline style="width:100%; height:100%; object-fit:contain;" preload="auto">
                                    Your browser does not support the video tag.
                                </video>
                            @else
                                <div class="no-video-placeholder">
                                    <img src="/img/seatechLogo.png" alt="SEATECH">
                                    <p>No video content</p>
                                </div>
                            @endif
                        </td>
                        @php $regularUsers = $users->where('usertype', 'Regular'); @endphp
                           @forelse ($regularUsers as $user)
                                 @unless ($loop->first)
                                     </tr><tr>
                                 @endunless
                                 <td class="rightName">{{ $user->name }}</td>
                                 <td class="right" id="serving-{{ $user->id }}">
                                     {{ $user->servingTicket ? $user->servingTicket->ticket_no : 'NONE' }}
                                 </td>
                         @empty
                                 <td colspan="2" class="right">No staff available</td>
                         @endforelse

                    </tr>
                </table>
            </div>

            <audio id="tvSound" src="{{ asset('sounds/call.mp3') }}" preload="auto"></audio>

            {{-- <div class="clock-container"> 
                <div id="time" class="time">11:05:30</div>
                <div id="date" class="date">Monday, December 08, 2025</div>
            </div>--}}
            <script src="{{ asset('js/clock.js') }}"></script>
        </div>
    </div>

    <script>
        // Get the videos from the database
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

        // Start audio + video on first user interaction
        document.addEventListener('click', function unlockAll() {
            if (audio) {
                audio.play().then(() => {
                    audio.pause();
                    audio.currentTime = 0;
                }).catch(()=>{});
            }
            if (tvPlayer) {
                tvPlayer.volume = 0.25;
                tvPlayer.play().catch(() => {});
            }
            document.removeEventListener('click', unlockAll);
        });

        // Loop only 3 times
        audio.addEventListener('ended', () => {
            playCount++;
            if (playCount < maxPlays) {
                audio.currentTime = 0;
                audio.play();
            } else {
                playCount = 0; // reset for next ticket call
            }
        });

        // Call this when a new number is served
        function playSound3Times() {
            playCount = 0;
            audio.currentTime = 0;
            audio.play();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>