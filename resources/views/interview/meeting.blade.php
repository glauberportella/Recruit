<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $interview->title }} - Interview Meeting</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #1a1a2e; color: #fff; }
        .meeting-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 24px; background: #16213e; border-bottom: 1px solid #0f3460;
        }
        .meeting-header h1 { font-size: 16px; font-weight: 600; }
        .meeting-info { display: flex; gap: 16px; align-items: center; font-size: 13px; color: #a0aec0; }
        .meeting-info .badge {
            padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase;
        }
        .badge-scheduled { background: #2b6cb0; color: #fff; }
        .badge-in-progress { background: #d69e2e; color: #1a202c; }
        .badge-completed { background: #38a169; color: #fff; }
        #jitsi-container { width: 100%; height: calc(100vh - 52px); }
        .meeting-ended {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: calc(100vh - 52px); text-align: center; gap: 16px;
        }
        .meeting-ended h2 { font-size: 24px; }
        .meeting-ended p { color: #a0aec0; max-width: 400px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500;
            text-decoration: none; cursor: pointer; border: none; transition: background 0.2s;
        }
        .btn-primary { background: #4299e1; color: #fff; }
        .btn-primary:hover { background: #3182ce; }
    </style>
</head>
<body>
    <div class="meeting-header">
        <h1>{{ $interview->title }}</h1>
        <div class="meeting-info">
            <span>{{ $interview->scheduled_at->format('M d, Y H:i') }}</span>
            <span>{{ $interview->duration_minutes }} min</span>
            <span class="badge badge-{{ str_replace(' ', '-', strtolower($interview->status->value)) }}">
                {{ $interview->status->value }}
            </span>
        </div>
    </div>

    <div id="jitsi-container"></div>

    <div id="meeting-ended" class="meeting-ended" style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="color: #38a169;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h2>Meeting Ended</h2>
        <p>The interview meeting has been concluded. You can close this window.</p>
        <a href="javascript:window.close()" class="btn btn-primary">Close Window</a>
    </div>

    <script src="https://meet.jit.si/external_api.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const config = @json($config);
            const token = @json($token);
            const isModerator = @json($isModerator);
            const interviewId = @json($interview->id);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            const api = new JitsiMeetExternalAPI(config.domain, {
                roomName: config.roomName,
                jwt: token,
                parentNode: document.getElementById('jitsi-container'),
                width: '100%',
                height: '100%',
                configOverwrite: config.configOverwrite,
                interfaceConfigOverwrite: config.interfaceConfigOverwrite,
            });

            // Mark interview as started when moderator joins
            if (isModerator) {
                api.addEventListener('videoConferenceJoined', function () {
                    fetch('/interview/' + interviewId + '/start', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    });
                });
            }

            // Handle meeting end
            api.addEventListener('videoConferenceLeft', function () {
                document.getElementById('jitsi-container').style.display = 'none';
                document.getElementById('meeting-ended').style.display = 'flex';

                if (isModerator) {
                    fetch('/interview/' + interviewId + '/end', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    });
                }
            });

            api.addEventListener('readyToClose', function () {
                document.getElementById('jitsi-container').style.display = 'none';
                document.getElementById('meeting-ended').style.display = 'flex';
            });
        });
    </script>
</body>
</html>
