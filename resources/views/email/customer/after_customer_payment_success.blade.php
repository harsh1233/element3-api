<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldn't be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
    <meta name="x-apple-disable-message-reformatting"> <!-- Disable auto-scale in iOS 10 Mail entirely -->
    <title></title> <!-- The title tag shows in email notifications, like Android 4.4. -->

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,400i,600,600i,700&display=swap" rel="stylesheet">
   
    <!-- CSS Reset : BEGIN -->
    <style>
        /* What it does: Remove spaces around the email design added by some email clients. */
        /* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
        html,
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
            background: #f1f1f1;
        }
        ul
        {
            list-style-type:none;
            padding-left: 0;
        }

        ul li
        {
            display: inline-block;
        }

        /* What it does: Stops email clients resizing small text. */
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        /* What it does: Centers email on Android 4.4 */
        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        /* What it does: Stops Outlook from adding extra spacing to tables. */
        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        /* What it does: Fixes webkit padding issue. */
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode: bicubic;
        }

        /* What it does: Prevents Windows 10 Mail from underlining links despite inline CSS. Styles for underlined links should be inline. */
        a {
            text-decoration: none;
        }

        /* What it does: A work-around for email clients meddling in triggered links. */
        *[x-apple-data-detectors],
        /* iOS */
        .unstyle-auto-detected-links *,
        .aBn {
            border-bottom: 0 !important;
            cursor: default !important;
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* What it does: Prevents Gmail from displaying a download button on large, non-linked images. */
        .a6S {
            display: none !important;
            opacity: 0.01 !important;
        }

        /* What it does: Prevents Gmail from changing the text color in conversation threads. */
        .im {
            color: inherit !important;
        }

        /* If the above doesn't work, add a .g-img class to any image in question. */
        img.g-img+div {
            display: none !important;
        }

        .mb0 {
            margin-bottom: 0;
        }

        /* What it does: Removes right gutter in Gmail iOS app: https://github.com/TedGoas/Cerberus/issues/89  */
        /* Create one of these media queries for each additional viewport size you'd like to fix */
        /* iPhone 4, 4S, 5, 5S, 5C, and 5SE */
        @media only screen and (min-device-width: 320px) and (max-device-width: 374px) {
            u~div .email-container {
                min-width: 320px !important;
            }
        }

        /* iPhone 6, 6S, 7, 8, and X */
        @media only screen and (min-device-width: 375px) and (max-device-width: 413px) {
            u~div .email-container {
                min-width: 375px !important;
            }
        }

        /* iPhone 6+, 7+, and 8+ */
        @media only screen and (min-device-width: 414px) {
            u~div .email-container {
                min-width: 414px !important;
            }
        }
    </style>

    <!-- CSS Reset : END -->

    <!-- Progressive Enhancements : BEGIN -->
    <style>
        .primary {
            background: #f5564e;
        }

        .bg_white {
            background: #ffffff;
        }

        .bg_light {
            background: #fafafa;
        }

        .bg_black {
            background: #000000;
        }

        .bg_dark {
            background: rgba(0, 0, 0, .8);
        }

        .bg_blue {
            background: #0e6db1;
        }

        .email-section {
            padding: 2.5em;
        }

        /*BUTTON*/
        .btn {
            padding: 5px 15px;
            display: inline-block;
        }

        .btn.btn-primary {
            border-radius: 5px;
            background: #f5564e;
            color: #ffffff;
        }

        .btn.btn-white {
            border-radius: 5px;
            background: #ffffff;
            color: #000000;
        }

        .btn.btn-white-outline {
            border-radius: 5px;
            background: transparent;
            border: 1px solid #fff;
            color: #fff;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Open Sans', sans-serif;
            color: #000000;
            margin-top: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            font-weight: 400;
            font-size: 15px;
            line-height: 1.8;
            color: rgba(0, 0, 0, .8);
        }

        a {
            color: #f5564e;
        }

        table {}

        /*LOGO*/
        .logo h1 {
            margin: 0;
            margin-top: 10px;
        }

        .logo h1 a {
            color: #000;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            font-family: 'Open Sans', sans-serif;
        }

        .navigation {
            padding: 0;
        }

        .navigation li {
            list-style: none;
            display: inline-block;
            ;
            margin-left: 5px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .navigation li a {
            color: rgba(0, 0, 0, .6);
        }

        /*HEADING SECTION*/
        .heading-section {}

        .heading-section h2 {
            color: #000000;
            font-size: 24px;
            margin-top: 0;
            line-height: 1.4;
            font-weight: 700;
        }

        .heading-section .subheading {
            margin-bottom: 20px !important;
            display: inline-block;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(0, 0, 0, .4);
            position: relative;
        }

        .heading-section .subheading::after {
            position: absolute;
            left: 0;
            right: 0;
            bottom: -10px;
            content: '';
            width: 100%;
            height: 2px;
            background: #f5564e;
            margin: 0 auto;
        }

        .heading-section-white {
            color: rgba(255, 255, 255, .8);
        }

        .heading-section-white h2 {
            font-family:
                line-height: 1;
            padding-bottom: 0;
        }

        .heading-section-white h2 {
            color: #ffffff;
        }

        .heading-section-white .subheading {
            margin-bottom: 0;
            display: inline-block;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(255, 255, 255, .4);
        }

        .icon {
            text-align: center;
        }

        .icon img {}

        /*DESTINATION*/
        .text-tour {
            padding-top: 10px;
        }

        .text-tour h3 {
            margin-bottom: 0;
        }

        .text-tour h3 a {
            color: #000;
        }

        /*BLOG*/
        .text-services .meta {
            text-transform: uppercase;
            font-size: 14px;
        }

        /*TESTIMONY*/
        .text-testimony .name {
            margin: 0;
        }

        .text-testimony .position {
            color: rgba(0, 0, 0, .3);
        }

        /*COUNTER*/
        .counter {
            width: 100%;
            position: relative;
            z-index: 0;
        }

        .counter .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            content: '';
            width: 100%;
            background: #000000;
            z-index: -1;
            opacity: .3;
        }

        .counter-text {
            text-align: center;
        }

        .counter-text .num {
            display: block;
            color: #ffffff;
            font-size: 34px;
            font-weight: 700;
        }

        .counter-text .name {
            display: block;
            color: rgba(255, 255, 255, .9);
            font-size: 13px;
        }

        ul.social {
            padding: 0;
        }

        ul.social li {
            display: inline-block;
        }

        /*FOOTER*/
        .footer {
            color: rgba(255, 255, 255, .5);
        }

        .footer .heading {
            color: #ffffff;
            font-size: 20px;
        }

        .footer ul {
            margin: 0;
            padding: 0;
        }

        .footer ul li {
            list-style: none;
            margin-bottom: 10px;
            margin-right: 15px;
        }

        .footer ul li a {
            color: rgba(255, 255, 255, 1);
        }

        @media screen and (max-width: 500px) {
            .icon {
                text-align: left;
            }

            .text-services {
                padding-left: 0;
                padding-right: 20px;
                text-align: left;
            }
        }

        /*GENERAL CSS*/
        .light-text {
            font-weight: 400;
        }

        .card {
            padding: 2em 2.5em;
            border-radius: 5px;
            box-shadow: 0px 0px 20px #e2e2e2;
        }

        .gray-divider {
            border-bottom: 1px solid #e2e2e2;
        }

        p {
            font-size: 15px;
            font-weight: normal;
            color: #333333;
        }

        .text-white {
            color: #ffffff;
        }
    </style>


</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #222222;">
    <h3 style="width: 100%; background-color: #f1f1f1;">
        
        <div style="max-width: 650px; margin: 0 auto; margin-bottom:50px" class="email-container">
            <!-- BEGIN BODY -->
             <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: auto;font-family: 'Open Sans', sans-serif;font-weight: 400;font-size: 15px;line-height: 1.8;color: rgba(0, 0, 0, .8);">
                <tr>
                    <td valign="top" class="" style="padding: 1em 1em;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td width="60%" class="logo" style="text-align: left;">
                                    <h1 style="font-size:12px"><a href="#" style="font-size:12px"><img src="{{url('/')}}/images/logo-element3.png" width="180" alt="Element3 Logo" style="display:block" class="logo-img" /></a></h1>
                                </td>
                                <td width="50%" class="logo" style="text-align: right;">
                                    <ul class="navigation">
                                        <li><a href="#"><i class="fa fa-chevron-right"></i> {{ __('email_template.visit_website') }}</a></li>
                                    </ul>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr><!-- end tr -->
                <tr>
                   <td valign="top" class="bg_white card" bgcolor="#ffffff" style="padding: 2em 2.5em; border-radius: 5px;box-shadow: 0px 0px 20px #e2e2e2;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td width="60%" class="" style="text-align: left;">
                                  <h1 class="mb0 light-text" style="line-height:normal;font-weight: 400;">{{ __('email_template.hello') }}</h1>
                                    <h2 style="margin-top:0">{{ $name }}</h2>
                                </td>
                                <td width="50%" class="logo" style="text-align: right;">
                                    {{-- <ul class="" style="list-style-type:none;    font-size: 14px;">
                                        <li>
                                            {{ __('email_template.booking_number') }}
                                        @foreach ($booking_numbers as $key => $booking_number)
                                            <b>{{ $booking_number }},</b>
                                        </li>
                                        @endforeach
                                        <li>
                                            {{ __('email_template.invoice_number') }}
                                        @foreach ($invoice_numbers as $key => $invoice_number)
                                            <b>{{ $invoice_number }},</b>
                                        </li>
                                        @endforeach
                                    </ul> --}}
                                </td>
                            </tr>
                            <tr>
                                <td class="" style="text-align: left;" colspan="2">
                                    {{-- <h2 class="light-text" style="font-weight: 400;">{{ __('email_template.success') }}</h2> --}}
                                    <h3>{{ __('email_template.customer_payment_success.description1') }}</h3>
                                    <p>
                                        {{ __('email_template.booking_number') }}
                                        @foreach ($booking_numbers as $key => $booking_number)
                                            <b>{{ $booking_number }},</b>
                                        </li>
                                        @endforeach
                                    </p>
                                    <p>
                                        {{ __('email_template.invoice_number') }}
                                        @foreach ($invoice_numbers as $key => $invoice_number)
                                            <b>{{ $invoice_number }},</b>
                                        </li>
                                        @endforeach
                                    </p>
                                    <p>{{ __('email_template.customer_payment_success.payment_method') }}
                                        <b>
                                            @if($payment_method=='P')
                                            Paypal
                                            @elseif($payment_method=='S')
                                            Stripe
                                            @elseif($payment_method=='C')
                                            Cash
                                            @elseif($payment_method=='BT')
                                            Bank Transfer
                                            @elseif($payment_method=='CC')
                                            Credit Card
                                            @elseif($payment_method=='CON')
                                            Concardis
                                            @elseif($payment_method=='O')
                                            Other
                                            @endif
                                        </b>
                                    </p>
                                    <p class="gray-divider" style="border-bottom: 1px solid #e2e2e2;margin-bottom: 20px;"></p>
                                    
                                    <p>{{ __('email_template.customer_payment_success.description2') }}</p>
                                    <p>{{ __('email_template.customer_payment_success.description3') }}</p>
                                    <p>{{ __('email_template.customer_payment_success.description4') }}</p>
                                    <p class="gray-divider" style="border-bottom: 1px solid #e2e2e2;margin-top: 20px;
                                    "></p>
                                    

                                    <!-- <div class="heading-section" style="text-align: center; padding: 0 30px;">
                                        <h4 class="light-text" style="font-weight: 400;">{{__('email_template.download_app')}}</h4>
                                        <p></p>
                                       <p><a href="#"><img src="{{ url('/')}}/images/google-play-download-android-app.png" style="display: inline-block;" alt="Google Play Store" /></a> <a href="#"><img src="{{url('/')}}/images/download-on-the-app-store-apple.png" style="display: inline-block;" alt="Apple Store" /></a></p>
                                    </div> -->


                                </td>
                            </tr>
                        </table>
                    </td>
                </tr><!-- end tr -->
            </table>
            </td>
            </tr><!-- end:tr -->
            <!-- 1 Column Text + Button : END -->
            </table>
            <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: auto;">
                <tr>
                     <td valign="middle" class="bg_blue footer email-section" bgcolor="#0e6db1" style="padding: 2.5em;">
                        <table>
                            <tr>
                                <td valign="top" width="60%" style="padding-top: 20px;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="text-align: center; padding-right: 10px;" colspan="2">

                                                <p class="text-white" style="color:#ffffff;font-weight:400">{{ __('email_template.business_thankfull') }}</p>
                                                <h3 class="heading text-center" style="color: #ffffff;font-size: 20px;">{{ __('email_template.description4') }}</h3>
                                                <br />
                                                <hr />
                                                <p class="text-white" style="color:#ffffff">&copy; {{ date("Y") }} {{ __('email_template.element3_rights_reserved') }}</p>
                                                <ul class="social" style="color:#ffffff">
                                                    <li><a href="#"><img src="{{url('/')}}/images/FB.png" alt="Facebook"/></a></li>
                                                    <li><a href="#"><img src="{{url('/')}}/images/Instagram.png" alt="Instagram"/></a></li>
                                                    <li><a href="#"><img src="{{url('/')}}/images/Twitter.png" alt="Twitter"/></a></li>
                                                </ul>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr><!-- end: tr -->
            </table>

        </div>
        </center>
</body>

</html>