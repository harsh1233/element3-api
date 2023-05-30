@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">

        <div class="col-md-6 col-md-offset-3">
            <script src="{{ asset('js/Duo-Web-v1.bundled.min.js') }}"></script>

            <script>
                Duo.init({
                    'host':'{{$duoinfo['HOST']}}',
                    'post_action':'{{$duoinfo['POST']}}',
                    'sig_request':'{{$duoinfo['SIG']}}'
                });
                
            </script>

            
            <iframe id="duo_iframe" width="590" height="500" frameborder="0" allowtransparency="true" style="background:transparent;"></iframe>
            <form id="duo_form" action="{{$duoinfo['POST']}}" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="user_email" value="{{ $duoinfo['USER'] }}">
            </form>
            <!-- <iframe id="duo_iframe"
                data-host="<?php echo $duoinfo['HOST']; ?>"
                data-sig-request="<?php echo $duoinfo['SIG']; ?>"
                data-post-action="<?php echo $duoinfo['POST']; ?>"
            ></iframe> -->

        </div>

    </div>
</div>
@endsection
