<table>
    <thead>
    <tr>
        <th align="center">Id</th>
        <th align="center">Instructor Name</th>
        <th align="center">Title</th>
        <th align="center">Start Date</th>
        <th align="center">End Date</th>
        <th align="center">Start Time</th>
        <th align="center">End Time</th>
        <th align="center">Description</th>
        <th align="center">Block Color</th>
        <th align="center">Amount</th>
        <th align="center">Paid Status</th>
        <th align="center">Meeting Point</th>
    </tr>
    </thead>
    <tbody>
     
    @foreach($instructor_blocks_data as $instructor_block)
        <tr>
            <td>{{ $instructor_block['id'] }}</td>
            <td>{{ $instructor_block['instructor_details']['salutation'].' '.$instructor_block['instructor_details']['first_name'].' '.$instructor_block['instructor_details']['last_name'] }}</td>
            <td>{{ $instructor_block['title'] }}</td>
            <td>{{ date('d/m/Y',strtotime($instructor_block['start_date'])) }}</td>
            <td>{{ date('d/m/Y',strtotime($instructor_block['end_date'])) }}</td>
            <td>{{ $instructor_block['start_time'] }}</td>
            <td>{{ $instructor_block['end_time'] }}</td>

            <td>{{ $instructor_block['description'] }}</td>
            <td>{{ $instructor_block['block_color'] }}</td>
            <td>{{ $instructor_block['amount'] }}</td>
            <td>
                    @if($instructor_block['is_paid'])
                        Paid
                    @else
                        Un Paid
                    @endif 
                
            </td>
            <td>{{ $instructor_block['meeting_point_details']['name'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
