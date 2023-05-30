<table>
    <thead>
    <tr>
        <th align="center">Payment Number</th>
        <th align="center">Payee</th>
        <th align="center">Payment Type</th>
        <th align="center">Payment Cards</th>
        <th align="center">Total Amount (€)</th>
        <th align="center">Created At</th>
    </tr>
    </thead>
    <tbody>
    @foreach($payments_data as $payment)
        <tr>
            <td>{{ $payment['payment_number'] }} </td>
            <td>{{ $payment['payee_detail']['salutation'] }} {{ $payment['payee_detail']['first_name'] }} {{ $payment['payee_detail']['middle_name'] }} {{ $payment['payee_detail']['last_name'] }}</td>
            <td>
                @if($payment['payment_type_detail']['type'] == 'C')
                    Cash
                @elseif($payment['payment_type_detail']['type'] == 'P')
                    Paypal
                @elseif($payment['payment_type_detail']['type'] == 'S')
                    Stripe
                @elseif($payment['payment_type_detail']['type'] == 'BT')
                    Bank Transfer
                @elseif($payment['payment_type_detail']['type'] == 'CC')
                    Credit Card
                @elseif($payment['payment_type_detail']['type'] == 'O')
                    Other
                @elseif($payment['payment_type_detail']['type'] == 'CON')
                    Concardis
                @endif
            </td>
            <td>
                <span>
                    @if($payment['payment_card_brand'])
                        {{ $payment['payment_card_brand'] }} ({{ $payment['payment_card_type'] }})
                    @else
                        -----
                    @endif
                </span>
            </td>
            <td>{{ $payment['total_amount'] }}</td>
            <td>{{ $payment['created_at'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table>
    <thead>
    <tr>
        <th align="center">Payment method type</th>
        <th align="center">Amount (€)</th>
    </tr>
    </thead>
    <tbody>
    <?php $total_amount = 0; ?>
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
                @endif
            </td>
            <td>
                {{ $value }}
            </td>
        </tr>
        <?php $total_amount = $total_amount + $value ?>
    @endforeach
    <tr>
        <td>Total</td>
        <td>{{ $total_amount }}</td>
    </tr>
    </tbody>
</table>

<table>
    <thead>
    <tr>
        <th align="center">Credit card brand</th>
        <th align="center">Amount (€)</th>
    </tr>
    </thead>
    <tbody>
    <?php $total_amount = 0; ?>
    @foreach($payment_card_base_details as $key => $card_base_detail)
        <tr>
            <td> {{ $key }}
            </td>
            <td>
                {{ $card_base_detail['total_payment_amonut'] }}
            </td>
        </tr>
        <?php $total_amount = $total_amount + $card_base_detail['total_payment_amonut'] ?>
    @endforeach
    <tr>
        <td>Total</td>
        <td>{{ $total_amount }}</td>
    </tr>
    </tbody>
</table>
