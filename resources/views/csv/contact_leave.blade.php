<table>
    <thead>
    <tr>
        <th align="center">Name</th>
        <th align="center">Leave Type</th>
        <th align="center">Start Date</th>
        <th align="center">End Date</th>
        <th align="center">Total Days</th>
        <th align="center">Reason</th>
    </tr>
    </thead>
    <tbody>
    @foreach($leave_data as $leave)
        <tr>
            <td>{{ $leave['contact_detail']['salutation'] }} {{ $leave['contact_detail']['first_name'] }} {{ $leave['contact_detail']['middle_name'] }} {{ $leave['contact_detail']['last_name'] }}</td>
            <td>{{ $leave['leave_detail']['leave_type'] }}</td>
            <td>{{ date('d/m/Y',strtotime($leave['start_date'])) }}</td>
            <td>{{ date('d/m/Y',strtotime($leave['end_date'])) }}</td>
            <td>{{ $leave['no_of_days'] }}</td>
            <td>{{ $leave['reason'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
