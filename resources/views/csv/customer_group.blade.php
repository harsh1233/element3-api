<table>
    <thead>
    <tr>
        <th align="center">Name</th>
        <th align="center">Description</th>
        <th align="center">No. of Participant</th>
    </tr>
    </thead>
    <tbody>
    @foreach($groups_data as $group)
        <tr>
            <td>{{ $group['name'] }}</td>
            <td>{{ $group['description'] }}</td>
            <td>{{ count($group['customers']) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
