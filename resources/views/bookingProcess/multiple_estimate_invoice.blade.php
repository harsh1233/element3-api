<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
  <meta charset="utf-8"> <!-- utf-8 works for most cases -->
  <meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldn't be necessary -->
  <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
  <meta name="x-apple-disable-message-reformatting"> <!-- Disable auto-scale in iOS 10 Mail entirely -->
  <title></title> <!-- The title tag shows in email notifications, like Android 4.4. -->

  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,400i,600,600i,700&display=swap" rel="stylesheet">
  <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

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
      background: #ffffff;
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
      line-height: 1.3;
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
    .semibold-text{
      font-weight: 600;
    }
    .light-text {
      font-weight: 400;
    }

    .card {
      padding: 0.5em 1.5em;
    /* border-radius: 5px; */
    /* box-shadow: 0px 0px 20px #e2e2e2; */
    margin-bottom: 30px !important;
    border: 0px solid #e2e2e2;
    margin: 5px !important;
    }

    .card.bg_white {
      background: #ffffff;
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

    .top-right-panel p {
      margin: 5px 0;
    }

    .feather-icon {
      display: -webkit-inline-box;
      display: -ms-inline-flexbox;
      display: inline-flex;
      -webkit-box-align: center;
      -ms-flex-align: center;
      align-items: center;
    }

    .feather {
      font-family: feather !important;
      speak: none;
      font-style: normal;
      font-weight: 400;
      font-variant: normal;
      text-transform: none;
      line-height: 1;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    .my-4 {
      margin-top: 0.7rem !important;
      margin-bottom: 0.7rem !important;
    }

    .ml-2 {
      margin-left: .5rem !important;
    }

    .w-4 {
      width: 1rem !important;
    }

    .h-4 {
      height: 1rem !important;
    }

    .flex {
      display: -webkit-box !important;
      display: -ms-flexbox !important;
      display: flex !important;
    }

    .items-center {
      -webkit-box-align: center !important;
      -ms-flex-align: center !important;
      align-items: center !important;
    }

    .justify-end {
      -webkit-box-pack: end !important;
      -ms-flex-pack: end !important;
      justify-content: flex-end !important;
    }

    .gray-invoice-text {
      font-size: 40px;
      color: #e2e2e2;
    }
    .payment-table
    {
      width: 100%;
      text-align: center;
      font-size: 17px;
      font-weight: normal;
      color: #333333;
      border: 1px solid #e2e2e2;
    }
    .payment-table p {margin:5px 0;}
    .payment-table tr , .payment-table td , .payment-table th { border-bottom:1px solid #e2e2e2; }
    .text-right
    {
      text-align:right;
    }
    .text-left
    {
      text-align:left;
    }
    /* .email-container{page-break-after: always;} */

  </style>


</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #ffffff;">
<div style="max-width: 100%; margin: 0 auto;" class="email-container card bg_white">
<table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: auto;page-break-after: always;">
<tr>
          <td valign="top" style="padding:1em 0">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td width="60%" class="logo" style="text-align: left;">
                  <h1><a href="#"><img src="{{url('/')}}/images/logo-element3.png" width="200" class="logo-img" /></a></h1>
                </td>
                <td width="50%" class="top-right-panel" style="text-align: right;">
                  <ul class="navigation">
                    <li>
                      <h1 class="mb0 gray-invoice-text">Quote Estimate</h1>
                      <p class="semibold-text">Date : {{ date('d-m-Y') }}</p>
                    </li>
                  </ul>
                </td>
              </tr>
            </table>
          </td>
        </tr><!-- end tr -->
<tr>
          <td valign="top" class="bg_white" style="">
            <table class="payment-table">
              <tr><td colspan="4"><br/><h3>Total Quote Estimate Detail</h3></td></tr>
              <tr>
                <th><p class="semibold-text">Quote Offer Number :</p></th>
                <th><p class="semibold-text">Course Name :</p></th>
                <th><p class="semibold-text">Customer Name :</p></th>
                <th><p class="semibold-text">Total Price :</p></th>
              </tr>
              @foreach($estimate as $estimate_data)
              <tr>
                <td> {{ $estimate_data['estimate_number'] }} </td>
                <td> {{ $estimate_data['course_name'] }} </td>
                <td> {{ $estimate_data['customer_name'] }} </td>
                <td> @currency_format($estimate_data['net_price']) </td>
              </tr>
              @endforeach
              <tr>
                <td colspan="3" style="text-align:right"><h3 style="margin-bottom:10px;margin-top:10px"><h3><b>Total Amount : </b></h3></td>
                <td><h3 style="margin-bottom:10px;margin-top:10px"><h3><b>@currency_format($total_estimate_amount)</b></h3></td>
              </tr>
            </table>
          </td>
        </tr>
  </table>
  </div>
@foreach($estimate as $data)
    <div style="max-width: 100%; margin: 0 auto;" class="email-container card bg_white">
      <!-- BEGIN BODY -->
      <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: auto;">
        <tr>
          <td valign="top" style="padding:1em 0">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td width="60%" class="logo" style="text-align: left;">
                  <h1><a href="#"><img src="{{url('/')}}/images/logo-element3.png" width="200" class="logo-img" /></a></h1>
                </td>
                <td width="50%" class="top-right-panel" style="text-align: right;">
                  <ul class="navigation">
                    <li>
                      <h1 class="mb0 gray-invoice-text">Quote Estimate</h1>
                    </li>
                  </ul>
                  <p><b>Quote Offer Number:</b> # <?php echo $data['estimate_number'];?></p>
                  <p><b>Quote Offer Date:</b> <?php echo date('d.m.Y',strtotime($data['estimate_date'])); ?></p>

                </td>
              </tr>
            </table>
          </td>
        </tr><!-- end tr -->
        <tr>
          <td valign="top" style="padding:1em 0">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td width="60%" class="logo top-right-panel" style="text-align: left;">
                  <p>
                    <h4 class="mb0"><b>Recipient</b></h4>
                  </p>
                  <div class="invoice__company-info my-4">
                  <p><b>Customer Name:</b> <?php echo $data['customer_name'];?></p>
                  <p><b>Start Date</b>:</b> <?php echo date('d.m.Y',strtotime($data['start_date']));?></p>
                  <p><b>Start Time</b>:</b> <?php echo date('h:i a',strtotime($data['start_time']));?></p>
                  <p><b>End Date</b>:</b> <?php echo date('d.m.Y',strtotime($data['end_date']));?></p>
                  <p><b>End Time</b>:</b> <?php echo date('h:i a',strtotime($data['end_time']));?></p>
                  </div>
                  <div class="invoice__company-contact">
                    <p><b>Email:</b><span class="ml-2"><?php echo $data['customer_email'];?></span></p>
                    <p><b>Mobile:</b></n><span class="ml-2"><?php echo $data['customer_mobile'];?></span></p>
                </td>
                <td width="50%" class="top-right-panel" style="text-align: right;">
                    <h4 class="mb0"><b>Element3</b></h4>
                  <div class="invoice__company-info my-4">
                    <p>Klostergasse 8,</p>
                    <p>6370 Kitzbühel</p>
                  </div>
                  <div class="invoice__company-contact">
                    <p class="flex items-center justify-end"><span class="feather-icon select-none relative"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-mail h-4 w-4">
                          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                          <polyline points="22,6 12,13 2,6"></polyline>
                        </svg></span><span class="ml-2">info@element3.at</span></p>
                    <p class="flex items-center justify-end"><span class="feather-icon select-none relative"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-phone h-4 w-4">
                          <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg></span><span class="ml-2">+43 5356 72301</span></p>
                  </div>
                </td>
              </tr>
            </table>
          </td>
        </tr><!-- end tr -->
        <tr>
          <td valign="top" class="bg_white" style="">
            <table class="payment-table">
              <tr><td colspan="2"><br/><h3>Payment Detail </h3></td></tr>
              <tr>
                <th><p class="semibold-text">Course Name:</p></th>
                <td><?php echo $data['course_name']; ?></td>
              </tr>
              <tr>
                <th><p class="semibold-text">Total Course Price:</p></th>
                <td> <span >&euro; <?php echo $data['total_price']; ?></span></td>
              </tr>
             <tr>
                <th><p class="semibold-text">Discount(%)</p></th>
                    <td><?php if($data['discount']) { ?><span> <?php echo $data['discount']; ?> </span> <?php } else { echo "0"; } ?></td>
              </tr>
              <tr>
                <th><p class="semibold-text">Excluding VAT Amount</p></th>
                <td><span>&euro; <?php echo $data['vat_excluded_amount']; ?></span></td>
              </tr>
              <tr>
                <th><p class="semibold-text">Vat Amount (<?php echo $data['vat_percentage']; ?>%)</p></th>
                <td><span>&euro; <?php echo $data['vat_amount']; ?></span></td>
              </tr>

              @if($data['is_include_lunch'])
              <tr>
                <th><p class="semibold-text">Lunch Price (<?php echo $data['lunch_vat_percentage']; ?>%)</p></th>
                <td><span>&euro; <?php echo $data['include_lunch_price']; ?></span></td>
              </tr>
              <tr>
                <th><p class="semibold-text">Excluding Lunch VAT Amount</p></th>
                <td><span>&euro; <?php echo $data['lunch_vat_excluded_amount']; ?></span></td>
              </tr>
              <tr>
                <th><p class="semibold-text">Lunch Vat Amount (<?php echo $data['lunch_vat_percentage']; ?>%)</p></th>
                <td><span>&euro; <?php echo $data['lunch_vat_amount']; ?></span></td>
              </tr>
              @endif

              @if($data['settlement_amount'])
              <tr>
                <th><p class="semibold-text">Settlement Amount : </p></th>
                <td><span>&euro; <?= $data['settlement_amount'] ?> </span></td>
              </tr>
              @endif

              <tr>
                <th><h3 style="margin-bottom:10px;margin-top:10px"><b>Total Price: </b></h3></th>
                <td><h3 style="margin-bottom:10px;margin-top:10px"><b>&euro; <?php echo $data['net_price']; ?></b></h3></td>
              </tr>
        
            </table>
          
          </td>
        </tr><!-- end tr -->
        <tr>
          <td style="text-align:center"><br/>
          <p class="gray-color"> If You have any question about this estimate, please contact us.</p>
          <p><b>Thanks You For Your Inquiry!</b></p>
        </td>
        </tr>
      </table>
      </td>
      </tr><!-- end:tr -->
      <!-- 1 Column Text + Button : END -->
      </table>
    </div>
    </center>
@endforeach()
</body>

</html>
