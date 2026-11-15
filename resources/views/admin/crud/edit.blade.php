@extends('admin.layouts.app')
@section('title', 'Edit '.strtolower($singular))
@section('content')
@include('admin.crud._form')
@endsection
