@extends('components.layouts.sec_level_layout', [ 
    'data' => $data,
])

@section('content')
    @livewire('profile.update-profile', [ 
        $data = $data
    ])
@endsection