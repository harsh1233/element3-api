<!DOCTYPE html>
<html lang="en" data-textdirection="ltr" class="loading">
@php
// $watermark = url("/")."/images/watermark.png";
@endphp

<head>
	<title>PDF</title>
	<style type="text/css">
		@import url('https://fonts.googleapis.com/css?family=Roboto:400,500,700');

		.table {
			border: 0.5px solid #000000;
			/*font-family: 'calibre-regular-webfont';*/
			font-family: 'Roboto', sans-serif;
		}

		.table-bordered>thead>tr>th,
		.table-bordered>tbody>tr>th,
		.table-bordered>tfoot>tr>th,
		.table-bordered>thead>tr>td,
		.table-bordered>tbody>tr>td,
		.table-bordered>tfoot>tr>td {
			border: 0.5px solid #000000;
		}

		html body {
			height: 100%;
			background-color: #ffffff;
			direction: ltr;
			/* background-image: url(watermark); */
			background-repeat: no-repeat;
			background-size: 50%;
			background-attachment: fixed;
			background-position: center;
			font-family: 'Roboto', sans-serif;
			font-size: 18px;
		}

		.table thead th {
			font-size: 15px !important;
			font-weight: 700 !important;
		}

		.table tbody th {
			font-size: 14px !important;
			font-weight: 700 !important;
		}

		.table td {
			font-size: 14px !important;
		}

		.table th,
		.table td {
			padding: 0.35rem 0.5rem;
		}

		table {
			padding: 10px;
			border-collapse: collapse;
		}
	</style>
	<!-- END Custom CSS-->
</head>

<body>
	<div>
		<div style="width:49%;display:inline-block;vertical-align: top;">
			<img src="{{url('/')}}/images/logo-element3.png" width="200" style="text-align:leftfloat:right;" />
		</div>
		<div style="width:50%;display:inline-block;text-align:right;">
			<p>Klostergasse 8, 6370 Kitzbühel,</p>
			<p>Telefon: +43 5356 72301</p>
		</div>
	</div>
	<h4 style="text-align: center !important;font-size: 26px !important;font-weight: 700 !important;">Pay-Slip</h4>

	<!-- 1st table - Customer details -->
	<table class="table table-bordered no-footer" style="width: 100%;margin: 0 0 18px;padding: 0;">
		<thead>
			<tr>
				<th colspan="2" style="text-align: center;width: 50%; background-color:#0e70b7;color:#fff;">Employee Details
				</th>
				<th colspan="2" style="text-align: center;width: 50%; background-color:#0e70b7;color:#fff;">Payment Details</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Name</th>
				<td>{{$payslip->contact->first_name}} {{$payslip->contact->last_name}}</td>
				<th>Bank Name</th>
				<td>{{$payslip->contact->bank_detail->bank_name}}</td>
			</tr>
			<tr>
				<th>Designation</th>
				<td>{{$payslip->contact->designation}}</td>
				<th>A/C No.</th>
				<td>{{$payslip->contact->bank_detail->account_no}}</td>
			</tr>
			<tr>
				<th>IBAN NO.</th>
				<td>{{$payslip->contact->bank_detail->iban_no}}</td>
				<th>Month</th>
				<td>{{date('F, Y',strtotime($payslip->payroll->year.$payslip->payroll->month.'01'))}}</td>
			</tr>
			@if($payslip->leaves_paid==0)
			<tr>
				<th>Joining Date</th> 
				<td colspan="3">{{ date('jS F Y',strtotime($payslip->contact->joining_date))}}</td>
			</tr>
			@endif
		</tbody>
	</table>

	<!-- 2nd table - Leave details -->
	<table class="table table-bordered no-footer" style="width: 100%;margin: 0 0 18px;padding: 0;">
		<thead>
			<tr>
				<th colspan="2" style="text-align: center; width: 35%; background-color:#0e70b7;color:#fff;">Leave Details</th>
				<th colspan="2" style="text-align: center; width: 20%; background-color:#0e70b7;color:#fff;">Days Details</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Total</th>
				<td>{{$payslip->total_leaves}}</td>
				<th>Working Days</th>
				<td>{{$payslip->payroll->working_days}}</td>
			</tr>
			<tr>
				<td>Approved :- {{$payslip->leaves_approved}}</td>
				<td>Rejected :- {{$payslip->leaves_rejected}}</td>
				<th>Present Days</th>
				<td>{{$payslip->present_days}}</td>
			</tr>
			<tr>
				<td>Paid :- {{$payslip->leaves_paid}}</td>
				<td>Unpaid :- {{$payslip->leaves_unpaid}}</td>
				<th>Days Paid</th>
				<td>{{$payslip->days_paid}}</td>
			</tr>
		</tbody>
	</table>

	<!-- 3rd table - Salary details -->
	<table class="table table-bordered no-footer" style="width: 100%;margin: 0 0 18px;padding: 0;">
		<thead>
			<tr>
				<th colspan="4" style="text-align: center; width: 35%; background-color:#0e70b7;color:#fff;">Salary details
					({{$payslip->salary_name}})</th>
			</tr>
		</thead>
		{{-- Hourly --}}
		@if($payslip->salary_type === 'H')
		<tbody>
			<tr>
				<th>Total </th>
				<td>{{$payslip->total_hours ?? 0}}</td>
				<th>Rejected </th>
				<td>{{$payslip->hours_rejected ?? 0}}</td>
			</tr>
			<tr>
				<th>Approved </th>
				<td>{{$payslip->hours_approved ?? 0}}</td>
				<th>Approved Overtime</th>
				<td>{{$payslip->hours_approved_overtime ?? 0}}</td>
			</tr>
		</tbody>
		@endif

		@if($payslip->salary_type === 'FD')
		<tbody>
			<tr>
				<th colspan="2">Per day </th>
				<td colspan="2">{{$payslip->salary_amount}}</td>
			</tr>
		</tbody>
		@endif

		@if($payslip->salary_type === 'FM')
		<tbody>
			<tr>
				<th colspan="2">Per month </th>
				<td colspan="2">{{$payslip->salary_amount}}</td>
			</tr>
		</tbody>
		@endif
	</table>

	<!-- 4th table - Expenditure -->
	<table class="table table-bordered no-footer" style="width: 100%;margin: 0 0 18px;padding: 0;">
		<thead>
			<tr>
				<th colspan="2" style="text-align: center; width: 35%; background-color:#0e70b7;color:#fff;">Expenditure</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Debt Settled</th>
				<td>{{$payslip->expenditure_debt ?? 0}}</td>
			</tr>
		</tbody>
	</table>

	<!-- 5th table - Block details -->
	@if ($payslip->approved_paid_block_hours && $payslip->paid_block_amount)
	<table class="table table-bordered no-footer" style="width: 100%;margin: 0 0 18px;padding: 0;">
		<thead>
			<tr>
				<th colspan="4" style="text-align: center; width: 35%; background-color:#0e70b7;color:#fff;">Block details</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Paid Block Hours</th>
				<td>{{$payslip->approved_paid_block_hours ?? 0}}</td>
				<th>Paid Block Amount</th>
				<td>{{$payslip->paid_block_amount ?? 0}}</td>
			</tr>
		</tbody>
	</table>
	@endif

	<!-- 6th table - Payout details -->
	<table class="table table-bordered no-footer" style="width: 100%;margin: 0 0 18px;padding: 0;">
		<thead>
			<tr>
				<th colspan="4" style="text-align: center; width: 35%; background-color:#0e70b7;color:#fff;">Payout details</th>
			</tr>
		</thead>
		<tbody>
			@if ($payslip->salary_type === 'H')
			<tr>
				<th>Normal Payout </th>
				<td>{{$payslip->normal_payout ?? 0}}</td>
				<th>Overtime Payout </th>
				<td>{{$payslip->overtime_payout ?? 0}}</td>
			</tr>
			@endif
			@if ($payslip->settlement_amount)
			<tr>
				<th>Settlement Amount </th>
				<td>{{$payslip->settlement_amount ?? 0}}</td>
				<th>Settlement Description </th>
				<td>{{$payslip->settlement_description ?? "--"}}</td>
			</tr>
			@endif
			<tr>
				<th colspan="2">Total payout </th>
				<td colspan="2">{{$payslip->total_payout ?? 0}}</td>
			</tr>
		</tbody>
	</table>

	<!-- 7th table - Other details -->
	<table class="table table-bordered no-footer" style="width: 100%;margin: 0 0 18px;padding: 0;">
		<thead>
			<tr>
				<th colspan="4" style="text-align: center; width: 35%; background-color:#0e70b7;color:#fff;">Other details</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Cheque number</th>
				<td>{{$payslip->check_number ?? '-'}}</td>
				<th>Reference number</th>
				<td>{{$payslip->ref_number ?? '-'}}</td>
			</tr>
		</tbody>
	</table>


</body>

</html>

{{-- <th>Salary group</th>
					<td>{{$payslip->salary_name}}</td> --}}