<div class="vx-col w-full mb-base m-auto">
   <div id="invoice-POS" class="col-md-4"> 
    <div id="mid" class="mainPrint">
    <div class="info">
        <h1 class="mb-2 mt-1 pt-0 pb-1" style="color:#cccccc;"><b>Season Ticket</b></h1>
        <h2>{{ $start_date }} - {{ $end_date }}</h2>
        <h5 class="semibold">{{ $course ? $course : '' }}</h5>

        <!-- <p class="mb-2"> 
            <b>Price :</b> @currency_format($total_price)
        </p> -->
        <p class="mb-2"> 
          @if($sub_customer_name)
              <b>Payee :</b> {{ $customer_name ? $customer_name : '' }}
          @else
              <b>Customer  :</b> {{ $customer_name ? $customer_name : '' }}
          @endif
        </p>
        @if($sub_customer_name)
        <p class="mb-2"> 
            <b>Sub Child :</b> {{ $sub_customer_name }}
        </p>
        @endif
        <p class="mb-2"> 
            <b>Reference :</b> #{{ $ticket_number }}</br>
        </p>

        <p class="mb-2"> 
            <b>Time :</b> {{ $start_time }} - {{ $end_time }}
        </p>
        <p class="mb-2">
            <b>Payment Status :</b> {{ $payment_status }}
        </p>
        <p class="mb-2 semibold width75"> 
        Viel Spab Belm Unterricht! <br>
        Have a nice day!</p>
        
        <p class="mb-2 semibold ver-center width25">
          @if($season_ticket_qr)
          <img src="{{ $season_ticket_qr }}" width="55" />
          @endif
        </p>
      </div><!--End Invoice Mid-->
    <div id="mid" class="mainPrint">
      <span class="text-center mb-2 mt-2 duplikat">Duplikat</span>
      <div class="info">
        <h1 class="mb-2 mt-1 pt-0 pb-1" style="color:#cccccc;"><b>Season Ticket</b></h1>
        <h2>{{ $start_date }} - {{ $end_date }}</h2>
        <h5 class="semibold">{{ $course ? $course : '' }}</h5>

        <!-- <p class="mb-2"> 
            <b>Price :</b> @currency_format($total_price)
        </p> -->
        <p class="mb-2"> 
          @if($sub_customer_name)
              <b>Payee :</b> {{ $customer_name ? $customer_name : '' }}
          @else
              <b>Customer  :</b> {{ $customer_name ? $customer_name : '' }}
          @endif
        </p>
        @if($sub_customer_name)
        <p class="mb-2"> 
            <b>Sub Child :</b> {{ $sub_customer_name }}
        </p>
        @endif
        <p class="mb-2"> 
            <b>Reference :</b> #{{ $ticket_number }}</br>
        </p>

        <p class="mb-2"> 
            <b>Time :</b> {{ $start_time }} - {{ $end_time }}
        </p>
        <p class="mb-2">
            <b>Payment Status :</b> {{ $payment_status }}
        </p>
        <p class="mb-2 semibold width75"> 
        Viel Spab Belm Unterricht!<br>
        Have a nice day!</p>
        
        <p class="mb-2 semibold ver-center width25">
          @if($season_ticket_qr)
          <img src="{{ $season_ticket_qr }}" width="55" />
          @endif
        </p>
      </div>
    </div>
    </div><!--End Invoice Mid-->
  </div><!--End Invoice-->
  </div>
<style>
@media print
{
.noprint {display:none;}
}
p b {font-weight: 600;}
.duplikat { display: inline-block;width: 100%;  }
.width75 { width: 65%; display:inline-block;}    
.width25 { width: 35%; display:inline-block;}  
.ver-center {vertical-align: top;}
.mb-2 { margin-bottom: 5px; }
#invoice-POS {
  box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
  padding: 7mm 5mm 7mm 30mm;
  margin: 0 auto 20px;
  width: 110mm;
  background: #FFF;
 page-break-after: always;
}
#invoice-POS ::selection {
  background: #f31544;
  color: #FFF;
}
#invoice-POS ::moz-selection {
  background: #f31544;
  color: #FFF;
}
#invoice-POS h1 {
  font-size: 1.5em;
  color: #222;
}
#invoice-POS h2 {
  font-size: 1em;
  margin-bottom: 2px;
}
#invoice-POS h5 {
  font-size: .9em;
   font-weight: 600;
   margin-bottom: 10px;
}
#invoice-POS h3 {
  font-size: 1.2em;
  font-weight: 300;
  line-height: 2em;
}
#invoice-POS p {
  font-size: .8em;
  color: #333;
  line-height: 1.5em;
}
.text-center{text-align: center;}
#invoice-POS #top, #invoice-POS #mid, #invoice-POS #bot {
  /* Targets all id with 'col-' */
  border-bottom: 0px solid #EEE;
}
#invoice-POS #top {
  min-height: 100px;
}
#invoice-POS #mid {
  min-height: 80px;
}
#invoice-POS .mainPrint { margin-bottom: 15px; }
#invoice-POS .mainPrint:last-child { margin-bottom: 0px; }
#invoice-POS #bot {
  min-height: 50px;
}
#invoice-POS #top .logo {
  height: 60px;
  width: 60px;
  background: url(http://michaeltruong.ca/images/logo1.png) no-repeat;
  background-size: 60px 60px;
}
#invoice-POS .clientlogo {
  float: left;
  height: 60px;
  width: 60px;
  background: url(http://michaeltruong.ca/images/client.jpg) no-repeat;
  background-size: 60px 60px;
  border-radius: 50px;
}
.semibold {font-weight:600;}
#invoice-POS .info {
  display: block;
  margin-left: 0;
}
#invoice-POS .title {
  float: right;
}
#invoice-POS .title p {
  text-align: right;
}
#invoice-POS table {
  width: 100%;
  border-collapse: collapse;
}
#invoice-POS .tabletitle {
  font-size: .5em;
  background: #EEE;
}
#invoice-POS .service {
  border-bottom: 1px solid #EEE;
}
#invoice-POS .viewdata {
  width: 24mm;
}
#invoice-POS .viewdatatext {
  font-size: .5em;
}
#invoice-POS #legalcopy {
  margin-top: 5mm;
}
</style>