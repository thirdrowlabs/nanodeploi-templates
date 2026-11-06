<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="5">
    <title>Demo 10 · Laravel queue</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 44rem; margin: 4rem auto; padding: 0 1rem">
    <h1>Laravel queue</h1>
    <p>
        Queue connection: <strong>{{ $queue }}</strong>.
        @if ($queue === 'sync')
            Jobs run inside the web request, so no worker is involved. Add Redis to the site to get a real queue.
        @endif
    </p>
    <p><a href="/dispatch">Dispatch a job</a>. It should show "worker" within a few seconds.</p>

    <table cellpadding="6">
        <tr><th align="left">#</th><th align="left">Dispatched</th><th align="left">Ran on</th></tr>
        @forelse ($pings as $ping)
            <tr>
                <td>{{ $ping->id }}</td>
                <td>{{ $ping->created_at->diffForHumans() }}</td>
                <td>
                    @if (! $ping->processed_at)
                        waiting…
                    @elseif ($ping->ranOnWorker())
                        worker ({{ $ping->processed_by }})
                    @else
                        same container as the web page
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="3">No jobs yet.</td></tr>
        @endforelse
    </table>
    <p style="color: #64748b">This web container: {{ $web }}</p>
</body>
</html>
