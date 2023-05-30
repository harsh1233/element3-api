<table>
    <thead>
        <tr>
            <th align="center">Course Name (Booking Count)</th>
            <th align="center">Total Course Price</th>
            <th align="center">Total Extended Price</th>
            <th align="center">Total Course Cancelled Price</th>
            <th align="center">Total Discounted Amount</th>
            <th align="center">Total Vat Amount</th>
            <th align="center">Total Vat Excluded Amount</th>
            <th align="center">Total Net Price</th>
            <th align="center">Total</th>
        </tr>
    </thead>
    <tbody>
    @foreach($data['course_data'] as $course_data)
        <tr>
            <td>{{ $course_data['course_name'] }} ({{ $course_data['total_bookings'] }})</td>
            <td>{{ $course_data['total_price'] }}</td>
            <td>{{ $course_data['axtended_net_price'] }}</td>
            <td>{{ $course_data['cancelled_total'] }}</td>
            <td>{{ $course_data['discounted_amount'] }}</td>
            <td>{{ $course_data['vat_amount'] }}</td>
            <td>{{ $course_data['vat_excluded_amount'] }}</td>
            <td>{{ $course_data['net_price'] }}</td>
            <td>{{ $course_data['row_base_total_amount'] }}</td>
        </tr>
    @endforeach
    <tr>
        <td>Total</td>
        <td>{{ $data['total_price_sum'] }}</td>
        <td>{{ $data['axtended_net_price_sum'] }}</td>
        <td>{{ $data['cancelled_total_sum'] }}</td>
        <td>{{ $data['discounted_amount_sum'] }}</td>
        <td>{{ $data['vat_amount_sum'] }}</td>
        <td>{{ $data['vat_excluded_amount_sum'] }}</td>
        <td>{{ $data['net_price_sum'] }}</td>
        <td>{{ $data['column_base_total_amount'] }}</td>
    </tr>
    </tbody>
</table>
<table>
    <thead>
    <tr>
        <th align="center">Payment type</th>
        <th align="center">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($payment_method_base_details as $key => $value)
        <tr>
            <td> 
                @if($key == 'C')
                    Cash
                @elseif($key == 'P')
                    Paypal
                @elseif($key == 'S')
                    Stripe
                @elseif($key == 'BT')
                    Bank Transfer
                @elseif($key == 'CC')
                    Credit Card
                @elseif($key == 'O')
                    Other
                @elseif($key == 'CON')
                    Concardis
                @elseif($key == 'OC')
                    Rechnung
                @endif
            </td>
            <td>
                {{ $value }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<table>
    <thead>
        <tr>
            <th align="center">Credit card brand</th>
            <th align="center">20 %</th>
            <th align="center">0 %</th>
            <th align="center">Total</th>
        </tr>
    </thead>
    <tbody>
    <?php 
        $total_amount = 0;
        $payment_card_reverce_amount = 0; 
        $total_sum = 0;
    ?>
    @foreach($payment_card_base_details as $key => $card_base_detail)
        <tr>
            <td> 
                {{ $key }}
            </td>
            <td>
                {{ $card_base_detail['total_payment_amonut'] }}
            </td>
            <td>
                {{ $card_base_detail['payment_card_reverce_amount'] }}
            </td>
            <td>
                {{ $card_base_detail['total_payment_amonut'] + $card_base_detail['payment_card_reverce_amount'] }}
            </td>
        </tr>
        <?php 
            $total_amount = $total_amount + $card_base_detail['total_payment_amonut'];
            $payment_card_reverce_amount = $payment_card_reverce_amount + $card_base_detail['payment_card_reverce_amount'];
            $total_sum = $total_sum + ($card_base_detail['total_payment_amonut'] + $card_base_detail['payment_card_reverce_amount']);
        ?>
    @endforeach
    <tr>
        <td>Total</td>
        <td>{{ $total_amount }}</td>
        <td>{{ $payment_card_reverce_amount }}</td>
        <td>{{ $total_sum }}</td>
    </tr>
    </tbody>
</table>
<table>
    <thead>
    <tr>
        <th align="center">Credit card type</th>
        <th align="center">20 %</th>
        <th align="center">0 %</th>
        <th align="center">Total</th>
    </tr>
    </thead>
    <tbody>
    <?php 
        $total_amount = 0; 
        $card_type_reverce_amount = 0; 
        $total_sum = 0;
    ?>
    @foreach($credit_card_type_details as $key => $credit_card)
        <tr>
            <td> {{ $key }}
            </td>
            <td>
                {{ $credit_card['total_payment_amonut'] }}
            </td>
            <td>
                {{ $credit_card['card_type_reverce_amount'] }}
            </td>
            <td>
                {{ $credit_card['total_payment_amonut'] + $credit_card['card_type_reverce_amount'] }}
            </td>
        </tr>
        <?php 
            $total_amount = $total_amount + $credit_card['total_payment_amonut'];
            $card_type_reverce_amount = $card_type_reverce_amount + $credit_card['card_type_reverce_amount'];
            $total_sum = $total_sum  + ($credit_card['total_payment_amonut'] + $credit_card['card_type_reverce_amount']);
        ?>
    @endforeach
    <tr>
        <td>Total</td>
        <td>{{ $total_amount }}</td>
        <td>{{ $card_type_reverce_amount }}</td>
        <td>{{ $total_sum }}</td>
    </tr>
    </tbody>
</table>
