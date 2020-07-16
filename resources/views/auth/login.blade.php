@extends('layouts.auth')

@section('content')

    <div class="login-form">
        <div class="logo">
            <img src="{{asset('img/logo.png')}}" alt="" height="70px" />
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input type="hidden" id="SAMLRequest" name="SAMLRequest" value="{{ request('SAMLRequest') }}" />
            <input type="hidden" id="RelayState" name="RelayState" value="{{ request('RelayState') }}" />

            <div class="form-group">
                <label for="exampleInputEmail">Utilizador</label>
                <input id="exampleInputEmail" type="text" class="form-control @error('email') is-invalid @enderror" placeholder="seu.email@una.com" name="email"   autocomplete="email" autofocus/>

                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="exampleInputPassword">Palavra-passe</label>
                <input id="exampleInputPassword" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-login">Aceder</button>
        </form>
    </div>
@endsection
