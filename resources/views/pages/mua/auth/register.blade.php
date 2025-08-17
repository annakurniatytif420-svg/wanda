@extends('layouts.app')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <h2 class="mb-4 text-center">Daftar Akun</h2>
      <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
            <input type="password" class="form-control" name="password_confirmation" required>
          </div>
          <div class="col-md-12 mb-3">
            <label class="form-label">Daftar Sebagai</label><br>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="role" value="customer" checked>
              <label class="form-check-label">Customer</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="role" value="mua">
              <label class="form-check-label">MUA</label>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-success w-100">Daftar</button>
      </form>
    </div>
  </div>
</div>
@endsection
