
<table>
    <thead>
    <tr>
        <th align="center">Estimate Number</th>
        <th align="center">Customer</th>
        <th align="center">Courses</th>
        <th align="center">Course Type</th>
        <th align="center">Start Date</th>
        <th align="center">End Date</th>
        <th align="center">Total Net Price</th>
        <th align="center">Discount</th>
    </tr>
    </thead>
    <tbody>
    @foreach($estiamte_data as $estimate)
        <tr>
            <td>{{ $estimate['estimate_number'] }}</td>
            <td>{{ $estimate['customer_data']['first_name'] }} {{ $estimate['customer_data']['last_name'] }}</td>
            <td>{{ $estimate['course_data']['name'] }}</td>
            <td>{{ $estimate['course_data']['type'] }}</td>
            <td>{{ date('d/m/Y',strtotime($estimate['start_date'])) }}</td>
            <td>{{ date('d/m/Y',strtotime($estimate['end_date'])) }}</td>
            <td>{{ $estimate['net_price'] }}</td>
            <td>{{ $estimate['discount'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
