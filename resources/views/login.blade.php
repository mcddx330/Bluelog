<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
<form action="{{ route('login') }}" method="POST">
    @csrf
    <label for="identifier">Identifier:</label>
    <input type="text" name="identifier" id="identifier" required><br>
    <label for="password">Password:</label>
    <input type="password" name="password" id="password" required><br>
    <button type="submit">Login</button>
</form>
</body>
</html>
