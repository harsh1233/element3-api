<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Temp concardis checkout page</title>
</head>
<body>
  <h1>Welcome to test checkout page.</h1>
  <h2>Data : </h2>
  <p><b>PSPID : </b>{{ $PSPID }}<br />
  <p><b>ORDERID : </b>{{ $ORDERID }}<br />
  <p><b>AMOUNT : </b>{{ $AMOUNT }}<br />
  <p><b>CURRENCY : </b>{{ $CURRENCY }}<br />
  <p><b>LANGUAGE : </b>{{ $LANGUAGE }}<br />
  <p><b>CN : </b>{{ $CN }}<br />
  <p><b>EMAIL : </b>{{ $EMAIL }}
  <p><b>SHASIGN : </b>{{ $SHASIGN }}</p>
  {{-- <form method="post" action="https://secure.payengine.de/ncol/test/orderstandard_utf8.asp" id=form1 name=form1> --}}
  <form method="post" action="{{ $ACTION }}" id=form1 name=form1>
    <!-- general parameters: see Form parameters -->

    <input type="hidden" name="PSPID" value="{{ $PSPID }}">

    <input type="hidden" name="ORDERID" value="{{ $ORDERID }}">

    <input type="hidden" name="AMOUNT" value="{{ $AMOUNT }}">

    <input type="hidden" name="CURRENCY" value="{{ $CURRENCY }}">

    <input type="hidden" name="LANGUAGE" value="{{ $LANGUAGE }}">

    <input type="hidden" name="CN" value="{{ $CN }}">

    <input type="hidden" name="EMAIL" value="{{ $EMAIL }}">

    <!-- post payment redirection: see Transaction feedback to the customer -->

    <input type="hidden" name="ACCEPTURL" value="{{ $ACCEPTURL }}">

    <input type="hidden" name="DECLINEURL" value="{{ $DECLINEURL }}">

    <input type="hidden" name="EXCEPTIONURL" value="{{ $EXCEPTIONURL }}">

    <input type="hidden" name="CANCELURL" value="{{ $CANCELURL }}">

    <!-- check before the payment: see Security: Check before the payment -->

    <input type="hidden" name="SHASIGN" value="{{ $SHASIGN }}">

    <input type="submit" value="Pay on concardis" style="padding:10px; background:#288feb;border:1px solid black;border-radius: 10px;">

    </form>
</body>
</html>