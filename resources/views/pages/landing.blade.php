@extends('layouts.app')

@section('content')
<!-- Hero Section -->
<section class="bg-light py-5 text-center text-md-start">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 mb-4 mb-md-0">
        <h1 class="display-5 fw-bold">Temukan Makeup Artist Terbaik untuk Setiap Momen</h1>
        <p class="lead">Booking MUA profesional dengan mudah, cepat, dan terpercaya. Cari berdasarkan lokasi, gaya makeup, dan harga.</p>
        <a href="{{ route('search.mua') }}" class="btn btn-primary btn-lg mt-3">Cari MUA Sekarang</a>
      </div>
      <div class="col-md-6 text-center">
        <img src="{{ asset('assets/images/hero-mua.png') }}" alt="Makeup Artist" class="img-fluid">
      </div>
    </div>
  </div>
</section>

<!-- Fitur Unggulan -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Fitur Unggulan</h2>
      <p class="text-muted">Semua yang Anda butuhkan dalam satu aplikasi.</p>
    </div>
    <div class="row text-center">
      <div class="col-md-4 mb-4">
        <img src="{{ asset('assets/icons/search.svg') }}" class="mb-3" width="60">
        <h5 class="fw-semibold">Pencarian Cerdas</h5>
        <p>Cari MUA terdekat berdasarkan lokasi, harga, dan gaya makeup favorit Anda.</p>
      </div>
      <div class="col-md-4 mb-4">
        <img src="{{ asset('assets/icons/profile.svg') }}" class="mb-3" width="60">
        <h5 class="fw-semibold">Profil & Portofolio Lengkap</h5>
        <p>Lihat hasil karya MUA, rating pelanggan, dan ketersediaan waktu secara real-time.</p>
      </div>
      <div class="col-md-4 mb-4">
        <img src="{{ asset('assets/icons/skin.svg') }}" class="mb-3" width="60">
        <h5 class="fw-semibold">Profil Kulit Digital</h5>
        <p>Dapatkan rekomendasi MUA sesuai warna dan jenis kulit Anda, termasuk riwayat skincare.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="bg-primary text-white py-5 text-center">
  <div class="container">
    <h2 class="mb-3">Mulai Temukan MUA Favoritmu Sekarang</h2>
    <a href="{{ route('register.customer') }}" class="btn btn-light btn-lg">Daftar Gratis</a>
  </div>
</section>
@endsection