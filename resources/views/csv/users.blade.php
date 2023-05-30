<table>
    <thead>
    <tr>
        <th align="center">Name</th>
        <th align="center">Gender</th>
        <th align="center">Email</th>
        <th align="center">Mobile</th>
        <th align="center">Date of Birth</th>
        <th align="center">Nationality</th>
        <th align="center">Role</th>
    </tr>
    </thead>
    <tbody>
    @foreach($user_data as $user)
        <tr>
            <td>{{ $user['name'] }}</td>
            <td>{{ $user['contact_detail']['gender'] == 'M' ? 'Male' : ($user['contact_detail']['gender'] == 'F' ? 'Female' : 'Other') }}</td>
            <td>{{ $user['email'] }}</td>
            <td>{{ $user['contact_detail']['mobile1'] }}</td>
            <td>{{ date('d/m/Y',strtotime($user['contact_detail']['dob'])) }}</td>
            <td>{{ $user['contact_detail']['nationality'] }}</td>
            <td>{{ $user['role_detail']['name'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
