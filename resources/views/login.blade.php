@extends('layouts.app')

@section('title', 'Login')

@section('content')
<form action="{{ route('login') }}" method="POST">
    @csrf
    <label for="identifier">Identifier:</label>
    <input type="text" name="identifier" id="identifier" required><br>
    <label for="password">Password:</label>
    <input type="password" name="password" id="password" required><br>
    <button type="submit">Login</button>
</form>
@endsection
