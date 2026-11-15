@extends('admin.layouts.app')
@section('title', 'Create '.strtolower($singular))
@section('content')
@include('admin.crud._form')
@endsection
