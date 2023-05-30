<table>
    <thead>
    <tr>
        <th align="center">Cash Type</th>
        <th align="center">Offices / Branches</th>
        <th align="center">Contacts</th>
        <th align="center">Amount</th>
        <th align="center">Running Amount</th>
        <th align="center">Payment Date</th>
        <th align="center">Description</th>
    </tr>
    </thead>
    <tbody>
    @foreach($cash_entries_data as $cash_entrie)
        <tr>
            <td>
                @if($cash_entrie['type']==='CHKIN')
                Check In
                @elseif($cash_entrie['type']==='CHKOUT')
                Check Out
                @elseif($cash_entrie['type']==='CASHIN')
                Cash In
                @elseif($cash_entrie['type']==='CASHOUT')
                Cash Out
                @elseif($cash_entrie['type']==='BOOKPMT')
                Booking Payment
                @endif    
            {{-- $cash_entrie['type'] --}} </td>
            <td>{{ $cash_entrie['office']['name'] }}</td>
            <td>{{ $cash_entrie['contact']['first_name'] }} {{ $cash_entrie['contact']['last_name'] }} </td>
            <td>{{ $cash_entrie['amount'] }}</td>
            <td>{{ $cash_entrie['running_amount'] }}</td>
            <td>{{ date('d/m/Y',strtotime($cash_entrie['date_of_entry'])) }}</td>
            <td>{{ $cash_entrie['description'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
