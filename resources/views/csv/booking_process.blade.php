<table>
    <thead>
    <tr>
        <th align="center">Booking No.</th>
        <th align="center">Course Name</th>
        <th align="center">Course Type</th>
        <th align="center">No. of Participant</th>
        <th align="center">No. of Instructor</th>
        <th align="center">Instructor</th>
        <th align="center">Start Date</th>
        <th align="center">End Date</th>
        <th align="center">Status</th>
    </tr>
    </thead>
    <tbody>
     
    @foreach($booking_data as $booking)
        <tr>
            <td>{{ $booking['booking_number'] }}</td>
            <td>{{ $booking['course_detail']['course_data']['name'] }}</td>
            <td>{{ $booking['course_detail']['course_type'] }}</td>
            <td>{{ count($booking['customer_detail']) }}</td>
            <td>{{ count($booking['instructor_detail']) }}</td>
            <td>
            @if(count($booking['instructor_detail']) > 0)
            
            {{ $booking['instructor_detail'][0]['contact']['salutation'] .''. $booking['instructor_detail'][0]['contact']['first_name'] .''. $booking['instructor_detail'][0]['contact']['middle_name'] .''. $booking['instructor_detail'][0]['contact']['last_name'] }}
            
            @endif 
            </td>
            <td>{{ date('d/m/Y h:i a',strtotime($booking['course_detail']['StartDate_Time'])) }}</td>
            <td>{{ date('d/m/Y h:i a',strtotime($booking['course_detail']['EndDate_Time'])) }}</td>
            <td>
                @if($booking['is_draft']===1)
                Draft
                @else
                Confirm
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
