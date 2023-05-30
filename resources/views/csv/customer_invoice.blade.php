<table>
    <thead>
    <tr>
        <th align="center">Invoice Date</th>
        <th align="center">Invoice No.</th>
        <th align="center">Customer</th>
        <th align="center">Payee</th>
        <th align="center">Course Type</th>
        <th align="center">Course Name</th>
        <th align="center">Total Days</th>
        <th align="center">Hours</th>
        <th align="center">Total Net Price</th>
        <th align="center">Payment Type</th>
        <th align="center">Payment Card Type</th>
        <th align="center">Payment Card Brand</th>
        <th align="center">Lead Sources</th>
        <th align="center">Invoice Status</th>
        <th align="center">Sent Invoice No</th>
        <th align="center">Tax Consultation Status</th>
    </tr>
    </thead>
    <tbody>
     
    @foreach($invoice_data as $invoice)
        <tr>
            <td>{{ date('d/m/Y h:i a',strtotime($invoice['created_at'])) }}</td>
            <td>
            <?php 
                if($invoice['status'] != 'Success'){
                    $exploded_invoice_number = explode('INV', $invoice['invoice_number']);
                    $reference_number = (isset($exploded_invoice_number[1]) ? $exploded_invoice_number[1] : '');
                    echo $reference_number.',';
                }
                else{
                    echo $invoice['invoice_number'].',';
                }
            ?>
            </td>
            <td>{{ $invoice['customer']['first_name'] }} {{ $invoice['customer']['middle_name'] }} {{ $invoice['customer']['last_name'] }}</td>
            <td>{{ $invoice['payi_detail']['first_name'] }} {{ $invoice['payi_detail']['middle_name'] }} {{ $invoice['payi_detail']['last_name'] }}</td>
            <td>{{ $invoice['lead_datails']['course_type'] }}</td>
            <td>{{ $invoice['lead_datails']['course_data']['name'] }}</td>
            <td>{{ $invoice['booking_customer_datails']['days_total']['days'] }}</td>
            <td>{{ $invoice['booking_customer_datails']['days_total']['hours'] }}</td>
            <td>{{ $invoice['net_price'] }}</td>
            <td>{{ $invoice['status'] }}</td>
            @if($invoice['payment_detail'])
                <td>{{ $invoice['payment_detail']['payment_card_type'] }}</td>
                <td>{{ $invoice['payment_detail']['payment_card_brand'] }}</td>
            @endif
            <td>{{ $invoice['lead_datails']['lead_data']['name'] }}</td>
            <td>
                @if($invoice['is_new_invoice']===0 && empty($invoice['refund_payment']))
                Regular
                @elseif($invoice['is_new_invoice']===1 && empty($invoice['refund_payment']))
                Extended
                @elseif($invoice['is_new_invoice']===1 && $invoice['refund_payment'])
                Reduction
                @endif
            </td>
            <td>{{ $invoice['no_invoice_sent'] }}</td>
            <td>{{ $invoice['tax_consultant'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
