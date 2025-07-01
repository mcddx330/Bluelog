<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts</title>
</head>
<body>
<h1>Your Posts</h1>
<h5>
    <a href="{{ route('posts.show',[
        'identifier' => $identifier,
        'access_jwt' => $access_jwt,
    ]) }}">posts</a>
    |
    <a href="{{ route('likes.show',[
        'identifier' => $identifier,
        'access_jwt' => $access_jwt,
    ]) }}">likes</a>
</h5>
@if(session('error'))
    <p style="color: red;">{{ session('error') }}</p>
@endif
<ul>
    @foreach($posts as $post)
        @php
            $value = $post['value'];
        @endphp
        <li>{{ $value['text'] }}</li>
    @endforeach
</ul>
</body>
</html>
