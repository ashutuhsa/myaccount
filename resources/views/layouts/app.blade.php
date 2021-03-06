<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" sizes="32x32">
    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
    @yield('header_tags')
</head>
<body style="font-family: WoodfordBourne;">
    <div id="app">
        <nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">

                    <!-- Collapsed Hamburger -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <!-- Branding Image -->
                    <a class="navbar-brand" href="{{ url('/') }}">
                        <a class="navbar-brand" href="{{ url('/') }}"><img src="{{ asset('images/logo.png') }}" style="height: 100%;" /></a>
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="app-navbar-collapse">
                    <!-- Left Side Of Navbar -->
                    <ul class="nav navbar-nav">
                        &nbsp;
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->
                        @if (Auth::guest())
                            <li><a href="{{ route('login') }}">Login</a></li>
                            <li><a href="{{ route('register') }}">Register</a></li>
                        @else
                            <li class="dropdown">                               
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu" role="menu">
                                    <li>
                                        <a href="{{ url('/home') }}">
                                            Dashboard
                                        </a>                                        
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
    <?php

    if( Session::get('msg_error') !== null){
        $msg_error = Session::get('msg_error');
    }
    elseif(Session::get('msg') !== null){
        $msg = Session::get('msg');
    } 

    if(!empty($msg) ) {
        $msg_modal = $msg;    

        echo '<div class="col-xs-1">&nbsp;</div>';
        echo '<div class="alert alert-success alert-dismissable"  role="alert">';
        echo '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span>';
        echo '<span class="sr-only">Close</span></button>';
        echo $msg_modal;
        echo '</div><div class="col-xs-1">&nbsp;</div>';

    }
    elseif(!empty($msg_error)) {
        $msg_modal = $msg_error;
        echo '<div class="col-xs-1">&nbsp;</div>';
        echo '<div class="alert alert-danger alert-dismissable"  role="alert">';
        echo '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span>';
        echo '<span class="sr-only">Close</span></button>';
        echo $msg_modal;
        echo '</div><div class="col-xs-1">&nbsp;</div>';
    }    
    ?>  
        
        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
