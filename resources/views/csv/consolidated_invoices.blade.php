<table>
    <thead>
    <tr>
        <th align="center">Name</th>
        <th align="center">Address</th>
        <th align="center">Emails</th>
        <th align="center">Invoices</th>
        <th align="center">Total amount</th>
        <th align="center">Vat percentage</th>
        <th align="center">Vat amount</th>
        <th align="center">Vat excluded amount</th>
        <th align="center">Created Date</th>
    </tr>
    </thead>
    <tbody>
    @foreach($consolidated_invoices_data as $invoice)
        <tr>
            <td>{{ $invoice['name'] }}</td>
            <td>{{ $invoice['address'] }}</td>
            <td>{{ implode(', ',$invoice['emails']) }}</td>
            <td>
            <?php 
                foreach($invoice['invoice_details'] as $invoice_data){
                    if($invoice_data['status'] != 'Success'){
                        $exploded_invoice_number = explode('INV', $invoice_data['invoice_number']);
                        $reference_number = (isset($exploded_invoice_number[1]) ? $exploded_invoice_number[1] : '');
                        echo $reference_number.',';
                    }
                    else{
                        echo $invoice_data['invoice_number'].',';
                    }
                }
            ?>
            </td>
            <td>{{ $invoice['total_amount'] }}</td>
            <td>{{ $invoice['vat_percentage'] }}</td>
            <td>{{ $invoice['vat_amount'] }}</td>
            <td>{{ $invoice['vat_excluded_amount'] }}</td>
            <td>{{ date('d/m/Y',strtotime($invoice['created_at'])) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
