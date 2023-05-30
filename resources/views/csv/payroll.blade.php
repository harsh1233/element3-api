<table><?php //dd($payroll_data);?>
    {{-- <thead> --}}
    <tr>
        <th align="center"><b>Year</b></th>
        <th align="center">Month</th>
        <th align="center">Total Days</th>
        <th align="center">Working Days</th>
        <th align="center">Total Contacts</th>
        <th align="center">Total Contacts Processed</th>
        <th align="center">Amount</th>
    </tr>   
    <tr>
        <td>{{ $payroll_data['year'] }}</td>
        <td>{{ date("F", mktime(0, 0, 0, $payroll_data['month'], 10)) }}</td>
        <td>{{ $payroll_data['total_days'] }}</td>
        <td>{{ $payroll_data['working_days'] }}</td>
        <td>{{ $payroll_data['total_contacts'] }}</td>
        <td>{{ $payroll_data['total_contacts_processed'] }}</td>
        <td>{{ $payroll_data['amount'] }}</td>
    </tr>   
    <tr>
    </tr>
    <tr>
        <th align="center">Employee</th>
        <th align="center">Type</th>
        <th align="center">Cheque Number</th>
        <th align="center">Reference Number</th>
        <th align="center">Salary group name</th>
        <th align="center">Salary Type</th>
        <th align="center">Hours</th>
        <th align="center">Days paid</th>
        <th align="center">Expenditure Debt(Euro)</th>
        <th align="center">Payout(Euro)</th>
        <th align="center">Status</th>
    </tr>
    {{-- </thead> --}}
    <tbody>
    @foreach($payroll_data['payslips'] as $payslips)
        <tr>
            <td>{{ $payslips['contact']['first_name'] }} {{ $payslips['contact']['last_name'] }}</td>
            <td>{{ $payslips['contact']['category_detail']['name'] }}</td>
            <td>{{ $payslips['check_number'] }} </td>
            <td>{{ $payslips['ref_number'] }} </td>
            <td>{{ $payslips['salary_name'] }} </td>
            <td> @if($payslips['salary_type']==='FD') Fixed Day @elseif($payslips['salary_type']==='H') Hourly @elseif($payslips['salary_type']==='FM') Fixed Month @endif </td>
            <td>{{ $payslips['total_hours'] }} </td>
            <td>{{ $payslips['days_paid'] }} </td>
            <td>@currency_format_without_euro($payslips['expenditure_debt']) </td>
            <td>@currency_format_without_euro($payslips['total_payout']) </td>
            <td>@if($payslips['status']==='NP') Not Paid @elseif($payslips['status']==='P') Paid @elseif($payslips['status']==='IP') In Progress @endif </td>
        </tr>
    @endforeach
    </tbody>
</table>
