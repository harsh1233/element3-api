<table>
    <tbody>
     
    @foreach($elda as $data)
        @if($data['status'] == 'register')
            <tr>
                <td>{{ $data['elda_insurance_number'] }}</td>
                <td>{{ date('d/m/Y',strtotime($data['contact_detail']['dob'])) }}</td>
                <td>{{ $data['contact_detail']['last_name'] }}</td>
                <td>{{ $data['contact_detail']['first_name'] }}</td>
                <td>{{ date('d/m/Y',strtotime($data['date'])) }}</td>
                <td>
                    @if($data['employement_area'] == 'employee')
                        02
                    @else
                        01
                    @endif
                </td>
                <td>
                    @if($data['minority'])
                        Yes
                    @else
                        No
                    @endif
                </td>
                <td>
                    @if($data['is_free_service_contract'])
                        Yes
                    @else
                        No
                    @endif
                </td>
                <td>{{ date('d/m/Y',strtotime($data['pension_contribution_from'])) }}</td>
            </tr>
        @endif
    @endforeach
    </tbody>
</table>
