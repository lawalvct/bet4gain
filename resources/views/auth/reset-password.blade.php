@extends('layouts.auth')
@section('title', 'Reset Password')
@section('content')
    <reset-password-page
        token="{{ $request->route('token') ?? ($token ?? '') }}"
        email="{{ $request->email ?? old('email', '') }}"
    ></reset-password-page>
@endsection
