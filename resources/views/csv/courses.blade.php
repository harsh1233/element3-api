<table>
    <thead>
    <tr>
        <th align="center">Name</th>
        <th align="center">Course Type</th>
        <th align="center">Category</th>
        <!-- <th align="center">Difficulty Level</th> -->
        <th align="center">No. of Participant</th>
        <th align="center">Start time</th>
        <th align="center">End time</th>
        <th align="center">Meeting point name</th>
        <th align="center">Restricted start time</th>
        <th align="center">Restricted end time</th>
        <th align="center">Display on website</th>
        <th align="center">Archived</th>
    </tr>
    </thead>
    <tbody>
    
    @foreach($courses_data as $courses)
        <tr>
            <td>{{ $courses['name'] }} </td>
            <td>{{ $courses['type'] }}</td>
            <td>{{ $courses['category_detail']['name'] }}</td>
            <!-- <td>{{ $courses['difficulty_level_detail']['name'] }}</td> -->
            <td>{{ $courses['maximum_participant'] }}</td>
            <td>{{ $courses['start_time'] }}</td>
            <td>{{ $courses['end_time'] }}</td>
            <td>{{ $courses['meeting_point_detail']['name'] }}</td>
            <td>{{ $courses['restricted_start_time'] }}</td>
            <td>{{ $courses['restricted_end_time'] }}</td>
            @if($courses['is_display_on_website'])<td>Yes</td>
            @else<td>No</td>
            @endif
            @if($courses['is_archived'])<td>Yes</td>
            @else<td>No</td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>
