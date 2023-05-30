<style>
table,tr,td{
    
}
</style>
<table style="border: 1px solid black;" width="100%">
    <tr>
        <th>Customer ID<th>
        <td>{{ $id }}</td>
        <th>Name<th>
        <td>{{ $salutation }} {{ $middle_name }} {{ $last_name }}</td>
    </tr>
    <tr>
        <th>Email Address<th>
        <td>{{ $email }}</td>
        <th>Contact Number's<th>
        <td>{{ $mobile1 }} | {{ $mobile2 }}</td>
    </tr>
    <tr>
        <th>Nationality<th>
        <td>{{ $nationality }}</td>
        <th>designation<th>
        <td>{{ $designation }} </td>
    </tr>
    <tr>
        <th>Date of Birth<th>
        <td>{{ $dob }}</td>
        <th>Gender<th>
        <td> @if($gender =='M') Male @else Female @endif</td>
    </tr>
    <tr>
        <th>Profile Pic<th>
        <td><img src='{{ $profile_pic }}' height="50px" width="50px"></td>
        <th>Display_in_app<th>
        <td>@if($display_in_app =='0') Not App User @else App User @endif</td>
    </tr>
    
</table>