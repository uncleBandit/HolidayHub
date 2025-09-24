@extends('layouts.app')

@section('title', 'HolidayHub - Your Next Holiday Awaits')

@section('content')
    <section class="relative h-[600px] md:h-[700px] overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/welcome-screen.jpg') }}');">
            <div class="absolute inset-0 bg-deep-ocean opacity-50"></div>
        </div>

        <div class="relative container mx-auto px-6 h-full flex items-center justify-center text-white text-center">
            <div>
                <h1 class="text-6xl md:text-7xl font-extrabold mb-4 drop-shadow-lg animate-fade-in-down">Find Your Slice of Paradise</h1>
                <p class="text-xl md:text-2xl font-light mb-6 drop-shadow-md animate-fade-in-up">Discover stunning destinations and book your perfect tropical getaway.</p>
            </div>
        </div>
    </section>


    <section class="relative -mt-24 z-10">
        <div class="container mx-auto px-6">
            <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12">
                <h2 class="text-4xl font-bold text-center mb-12 text-tropical-blue">
                    Top-Rated Destinations
                </h2>

                {{-- Replace foreach with Livewire component --}}
                <livewire:featured.featured-destinations />
            </div>
        </div>
    </section>

    <section class="py-16 bg-sea-foam">
        <div class="container mx-auto px-6">
            <livewire:testimonials />
        </div>
    </section>

    <section class="py-20 bg-sunset-orange text-white text-center rounded-t-3xl mt-12">
        <div class="container mx-auto px-6">
            <h2 class="text-4xl font-bold mb-4">Ready to Book Your Next Adventure?</h2>
            <p class="mb-8 font-light">The perfect holiday is just a few clicks away. Find the best deals and create unforgettable memories.</p>
            <a href="{{ route('bookings.index') }}"
               class="bg-white text-sunset-orange font-bold text-xl px-10 py-4 rounded-full shadow-2xl hover:bg-gray-100 transition-all duration-300 transform hover:scale-105">
                Start Booking
            </a>
        </div>
    </section>
@endsection
