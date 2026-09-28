@extends('layouts.app')

@section('title', 'Things')

@include('things.partials.row')

@component('components.alert')
    Saved!
@endcomponent

<div>{{ $thing->title }}</div>
