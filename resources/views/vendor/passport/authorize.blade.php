<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Authorize</title>
</head>
<body>
    <h1>Authorize Application</h1>
    <p>Cette vue est utilisée par Passport pour autoriser les clients OAuth.</p>
    <form method="post" action="{{ url('/oauth/authorize') }}">
        @csrf
        <button type="submit" name="approve">Autoriser</button>
        <button type="submit" name="deny">Refuser</button>
    </form>
</body>
</html>
