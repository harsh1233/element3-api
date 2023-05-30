<table><?php //dd($payroll_data);?>
    {{-- <thead> --}}
    <tr>
        <th align="center">Month</th>
        <th align="center">Year</th>
        <th align="center">Working Days</th>
        <th align="center">Total Contacts</th>
        <th align="center">Total Contacts Processed</th>
        <th align="center">Amount</th>
        <th align="center">Created At</th>
        <th align="center">Updated At</th>
    </tr>
    {{-- </thead> --}}
    <tbody>
    @foreach($payroll_data as $payroll)
        <tr>
            <td>{{ date('F', mktime(0, 0, 0, $payroll['month'], 10)) }}</td>
            <td>{{ $payroll['year'] }}</td>
            <td>{{ $payroll['total_days'] }} </td>
            <td>{{ $payroll['total_contacts'] }} </td>
            <td>{{ $payroll['total_contacts_processed'] }} </td>
            <td>@currency_format_without_euro($payroll['amount']) </td>
            <td>{{ date('d-m-Y',strtotime($payroll['created_at'])) }} </td>
            <td>{{ date('d-m-Y',strtotime($payroll['updated_at'])) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
