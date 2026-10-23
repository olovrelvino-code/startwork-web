<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $job->title }} - StartWork</title>
</head>
<body>
    <main>
        <h1>{{ $job->title }}</h1>
        <p>{{ $job->company_name }} · {{ $job->location }}</p>
        <p>{{ $job->description }}</p>
        @if ($job->apply_url)
            <a href="{{ url('/api/job-posts/'.$job->id.'/apply-now') }}" rel="nofollow">Apply Sekarang</a>
        @endif
    </main>
</body>
</html>
