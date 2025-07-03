@extends('layouts.app')

@section('title', 'Posts')

@section('content')
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
@endsection
