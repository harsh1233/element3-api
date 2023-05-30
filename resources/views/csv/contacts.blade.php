<table>
    <thead>
    <tr>
        <th align="center">Name</th>
        <th align="center">Gender</th>
        <th align="center">Email</th>
        <th align="center">Mobile</th>
        <th align="center">Primary Tag</th>
    </tr>
    </thead>
    <tbody>
    @foreach($contacts as $contact)
        <tr>
            <td>{{ $contact->salutation.' '.$contact->first_name.' '.$contact->last_name }}</td>
            <td>{{ $contact->gender == 'M' ? 'Male' : ($contact->gender == 'F' ? 'Female' : $contact->gender == 'O' ? 'Other' :  '' )}}</td>
            <td>{{ $contact->email }}</td>
            <td>{{ $contact->mobile1 }}</td>
            <td>{{ $contact->category_detail ? $contact->category_detail->name : ''  }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
