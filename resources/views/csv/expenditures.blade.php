<table>
    <thead>
    <tr>
        <th align="center">Name</th>
        <th align="center">Product</th>
        <th align="center">Service</th>
        <th align="center">Description</th>
        <th align="center">Date of Expense</th>
        <th align="center">Amount</th>
        <th align="center">Payment Status</th>
        <th align="center">Tax Consultation Status</th>
        <th align="center">Date Added</th>
        <th align="center" colspan="5">Images</th>
    </tr>
    </thead>
    <tbody>
    @foreach($expenditure_data as $expenditure)
        <tr>
            <td>{{ $expenditure['user']['name'] }} </td>
            <td>
                 @if($expenditure['is_product']===1)
                Yes
                @else
                No
                @endif
            </td>
            <td>
                @if($expenditure['is_service']===1)
                Yes
                @else
                No
                @endif
            </td>
            <td>{{ $expenditure['description'] }}</td>
            <td>{{ date('d/m/Y',strtotime($expenditure['date_of_expense'])) }}</td>
            <td>{{ $expenditure['amount'] }} </td>
            <td>
                @if($expenditure['payment_status']==='P')
                Paid
                @elseif($expenditure['payment_status']==='NP')
                Not Paid
                @elseif($expenditure['payment_status']==='PP')
                Paid Privately
                @endif
            </td>
            <td>
                @if($expenditure['tax_consultation_status']==='ND')
                Not Done
                @elseif($expenditure['tax_consultation_status']==='IP')
                In Progress
                @elseif($expenditure['tax_consultation_status']==='A')
                Approved
                @elseif($expenditure['tax_consultation_status']==='R')
                Rejected
                @endif
            </td>
            <td>{{ date('d/m/Y h:i a',strtotime($expenditure['created_at'])) }}</td>
            <!-- For expenditures images S3 Urls -->
                @foreach($expenditure['receipt_images'] as $image)
                <td>
                    {{ $image }}
                </td> 
                @endforeach
            <!--End -->
        </tr>
    @endforeach
    </tbody>
</table>
