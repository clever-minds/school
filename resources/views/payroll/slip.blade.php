<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
    }

    body {
        margin: 0;
        padding: 0;
    }

    @page {
        margin: 25px;
    }

    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 6px;
        border: 1px solid #000;
    }

    .no-border td, .no-border th {
        border: none;
    }

    .header-line {
        border-bottom: 2px solid #000;
        margin-top: 5px;
        margin-bottom: 10px;
    }

    .salary-table th {
        background-color: #f2f2f2;
    }
</style>
<title>Salary Slip || {{ $schoolSetting['school_name'] }}</title>
</head>

<body>

    <div class="text-center">
        @if ($schoolSetting['horizontal_logo'] ?? '')
            <img class="school-logo" height="60" style="max-width:100%;" src="{{ public_path('storage/') . $schoolSetting['horizontal_logo'] }}" alt="">                    
        @else
            <img height="60" style="max-width:100%;" src="{{ public_path('assets/horizontal-logo2.svg') }}" alt="">
        @endif
        <br><br>
        <div>
            {{ $schoolSetting['school_address'] ?? '' }}
        </div>
        <div class="header-line"></div>
        <h3>SALARY SLIP - {{ strtoupper($salary->title) }}</h3>
    </div>

    @php
        $lwp = 0;
        $lwp_amount = 0;
        $allowance = 0;
        $deduction = 0;

        if ($salary->paid_leaves < $total_leaves && $allow_leaves) {
            $lwp = number_format($total_leaves - $salary->paid_leaves, 2);
        }
    @endphp

    <table class="no-border" style="margin-top:15px;">
        <tr>
            <td width="50%" style="vertical-align:top; text-align:left; padding:0;">
                <strong>Employee Summary</strong><br><br>
                <strong>Name:</strong> {{ $salary->staff->user->full_name }}<br>
                <strong>Employee ID:</strong> {{ $salary->staff->id }}<br>
                <strong>Date:</strong> {{ format_date($salary->date) }}<br>
            </td>

            <td width="50%" style="vertical-align:top; text-align:left; padding:0;">
                <strong>Salary Details</strong><br><br>
                <strong>Net Salary:</strong> {{ number_format($salary->amount, 2) }}<br>
                <strong>Paid Days:</strong> {{ $days - $lwp }}<br>
                <strong>LWP Days:</strong> {{ $lwp }}<br>
            </td>
        </tr>
    </table>

    <table class="salary-table" style="margin-top:20px;">
        <thead>
            <tr>
                <th class="text-left" width="25%">Earnings</th>
                <th class="text-right" width="25%">Amount</th>
                <th class="text-left" width="25%">Deductions</th>
                <th class="text-right" width="25%">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">Basic</td>
                <td class="text-right">{{ number_format($salary->basic_salary, 2) }}</td>
                <td class="text-left">Leave Without Pay<br><small>Paid Leaves : {{ $salary->paid_leaves }}</small></td>
                <td class="text-right">
                    @if ($lwp)
                        @php
                            $lwp_amount = ($salary->basic_salary / 30) * $lwp;
                        @endphp
                        {{ number_format($lwp_amount, 2) }}
                    @else
                        0.00
                    @endif
                </td>
            </tr>

            @php
                $allowancesList = [];
                $deductionsList = [];
                foreach ($salary->staff_payroll as $payroll) {
                    if ($payroll->payroll_setting->type == 'allowance') {
                        $amt = $payroll->amount ? $payroll->amount : ($salary->basic_salary * $payroll->percentage) / 100;
                        $allowance += $amt;
                        $allowancesList[] = [
                            'name' => $payroll->payroll_setting->name . ($payroll->percentage ? ' ('.$payroll->percentage.'%)' : ''),
                            'amount' => $amt
                        ];
                    } else if ($payroll->payroll_setting->type == 'deduction') {
                        $amt = $payroll->amount ? $payroll->amount : ($salary->basic_salary * $payroll->percentage) / 100;
                        $deduction += $amt;
                        $deductionsList[] = [
                            'name' => $payroll->payroll_setting->name . ($payroll->percentage ? ' ('.$payroll->percentage.'%)' : ''),
                            'amount' => $amt
                        ];
                    }
                }

                $other_allowance = 0;
                $other_deduction = 0;

                if ($salary->amount > ($salary->basic_salary + $allowance - $lwp_amount - $deduction)) {
                    $other_allowance = $salary->amount - ($salary->basic_salary + $allowance - $deduction - $lwp_amount);
                    $allowance += $other_allowance;
                } else if ($salary->amount < ($salary->basic_salary + $allowance - $lwp_amount - $deduction)) {
                    $other_deduction = ($salary->basic_salary + $allowance - $deduction - $lwp_amount) - $salary->amount;
                    $deduction += $other_deduction;
                }

                if ($other_allowance > 0) {
                    $allowancesList[] = [
                        'name' => 'Other Allowances',
                        'amount' => $other_allowance
                    ];
                }

                if ($other_deduction > 0) {
                    $deductionsList[] = [
                        'name' => 'Other Deductions',
                        'amount' => $other_deduction
                    ];
                }

                $maxRows = max(count($allowancesList), count($deductionsList));
            @endphp

            @for ($i = 0; $i < $maxRows; $i++)
                <tr>
                    <td class="text-left">{{ isset($allowancesList[$i]) ? $allowancesList[$i]['name'] : '' }}</td>
                    <td class="text-right">{{ isset($allowancesList[$i]) ? number_format($allowancesList[$i]['amount'], 2) : '' }}</td>
                    
                    <td class="text-left">{{ isset($deductionsList[$i]) ? $deductionsList[$i]['name'] : '' }}</td>
                    <td class="text-right">{{ isset($deductionsList[$i]) ? number_format($deductionsList[$i]['amount'], 2) : '' }}</td>
                </tr>
            @endfor
            
        </tbody>
        <tfoot>
            <tr>
                <th class="text-left">Gross Earnings</th>
                <th class="text-right">{{ number_format($salary->basic_salary + $allowance, 2) }}</th>
                <th class="text-left">Total Deductions</th>
                <th class="text-right">{{ number_format($lwp_amount + $deduction, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <table style="margin-top:20px;">
        <tr>
            <th class="text-left">Total Net Payable<br><small style="font-weight: normal;">Gross Earnings - Total Deductions</small></th>
            <th class="text-right" style="font-size: 16px;">{{ number_format($salary->amount, 2) }}</th>
        </tr>
    </table>
    
    <table class="no-border" style="margin-top:50px;">
        <tr>
            <td width="50%" class="text-left">
                <strong>Employer Signature</strong><br><br><br>
                ______________________
            </td>

            <td width="50%" class="text-right">
                <strong>Employee Signature</strong><br><br><br>
                ______________________
            </td>
        </tr>
    </table>

</body>
</html>
