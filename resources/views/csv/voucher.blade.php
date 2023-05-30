<table>
    <thead>
    <tr>
        <th align="center">Code</th>
        <th align="center">Status</th>
        <th align="center">Customer</th>
        <th align="center">Course</th>
        <th align="center">Amount</th>
        <th align="center">Date of purchase</th>
        <th align="center">Date Added</th>
    </tr>
    </thead>
    <tbody>
    @foreach($vouchers_data as $voucher)
        <tr>
            <td>{{ $voucher['code'] }} </td>
            <td>{{ $voucher['status'] }}</td>
            <td>{{ $voucher['contact']['first_name'] }} {{ $voucher['contact']['last_name'] }} </td>
            <td>{{ $voucher['course']['name'] }}</td>
            <td>
                @if($voucher['amount_type']==='V')
                    {{ $voucher['amount'] }}
                @elseif($voucher['amount_type']==='P')
                    {{ $voucher['amount'] }}%
                @endif
            </td>
            <td>{{ date('d/m/Y h:i a',strtotime($voucher['date_of_purchase'])) }}</td>
            <td>{{ date('d/m/Y h:i a',strtotime($voucher['created_at'])) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
