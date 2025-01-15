@extends('device::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('device.name') !!}</p>
@endsection
