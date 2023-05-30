<table>
    <thead>
    <tr>
        <!-- <th align="center">Id</th> -->
        <th align="center">Season Ticket Number</th>
        <th align="center">Customer</th>
        <!-- <th align="center">Customer Mobile</th> -->
        <!-- <th align="center">Customer Email</th> -->
        <th align="center">Course</th>
        <th align="center">Course Type</th>

        <!-- <th align="center">Payment Method</th> -->
        <!-- <th align="center">Payment Status</th> -->
        <th align="center">Issue Date</th>
        <th align="center">Expiration Date</th>
        <!-- <th align="center">Start Time</th> -->
        <!-- <th align="center">End Time</th> -->
        <!-- <th align="center">Total Price</th> -->

        <!-- <th align="center">Net Price</th> -->
        <!-- <th align="center">Vat Percentage</th> -->
        <!-- <th align="center">Vat Amount</th> -->
        <!-- <th align="center">Vat Excluded Amount</th> -->
        <th align="center">Total Net Price</th>
        <th align="center">Scan Count</th>
        <th align="center">Last Scan Date</th>
    </tr>
    </thead>
    <tbody>
    @foreach($season_tickets_data as $season_tickets)
        <tr>
            <!-- <td>{{ $season_tickets['id'] }}</td> -->
            <td>{{ $season_tickets['ticket_number'] }}</td>
            <td>{{ $season_tickets['customer_name'] }}</td>
            <!-- <td>{{ $season_tickets['customer_mobile'] }}</td> -->
            <!-- <td>{{ $season_tickets['customer_email'] }}</td> -->
            <td>{{ $season_tickets['course']['name'] }}</td>
            <td>{{ $season_tickets['course']['type'] }}</td>
            <!-- <td>
                @if($season_tickets['payment_method_detail']['type'] == 'C')
                    Cash
                @elseif($season_tickets['payment_method_detail']['type'] == 'P')
                    Paypal
                @elseif($season_tickets['payment_method_detail']['type'] == 'S')
                    Stripe
                @elseif($season_tickets['payment_method_detail']['type'] == 'BT')
                    Bank Transfer
                @elseif($season_tickets['payment_method_detail']['type'] == 'CC')
                    Credit Card
                @elseif($season_tickets['payment_method_detail']['type'] == 'O')
                    Other
                @elseif($season_tickets['payment_method_detail']['type'] == 'CON')
                    Concardis
                @endif
            </td> -->
            <!-- <td>{{ $season_tickets['payment_status'] }}</td> -->

            <td>{{ date('d/m/Y',strtotime($season_tickets['start_date'])) }}</td>
            <td>{{ date('d/m/Y',strtotime($season_tickets['end_date'])) }}</td>
            <!-- <td>{{ $season_tickets['start_time'] }}</td> -->
            <!-- <td>{{ $season_tickets['end_time'] }}</td> -->

            <!-- <td>{{ $season_tickets['total_price'] }}</td> -->
            <!-- <td>{{ $season_tickets['net_price'] }}</td> -->
            <!-- <td>{{ $season_tickets['vat_percentage'] }}</td> -->
            <!-- <td>{{ $season_tickets['vat_amount'] }}</td> -->
            <!-- <td>{{ $season_tickets['vat_excluded_amount'] }}</td> -->
            <td>{{ $season_tickets['bookings_total_amount'] }}</td>
            <td>{{ $season_tickets['scaned_count'] }}</td>
            @if($season_tickets['scaned_at'])
            <td>{{ date('d/m/Y',strtotime($season_tickets['scaned_at'])) }}</td>
            @else
            <td></td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>
