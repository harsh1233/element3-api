<table><?php //dd($payroll_data);?>
    {{-- <thead> --}}
    <tr>
        <th align="center">Name</th>
        <th align="center">Address</th>
    </tr>
    {{-- </thead> --}}
    <tbody>
    @foreach($meeting_points as $meeting_point)
        <tr>
            <td>{{ $meeting_point['name'] }}</td>
            <td>{{ $meeting_point['address'] }} </td>
        </tr>
    @endforeach
    </tbody>
</table>
