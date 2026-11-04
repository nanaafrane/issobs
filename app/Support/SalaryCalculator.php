<?php

namespace App\Support;

use App\Models\employee;
use App\Models\Salary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The one place a salary's totals are worked out: the salary edit form
 * (SalaryController::update) and the salary upload (SalaryUpload) both use it.
 *
 *   SSNIT   when the employee's "Deduct SSNIT" is on and they have an SSNIT number:
 *           tier 2 = 5% of basic, tier 1 = 0.5%, company 13% and 13.5%.
 *   Tax     when "Deduct Tax" is on and basic is above 490: PAYE bands on the basic,
 *           less 5.5% when SSNIT is deducted.
 *   Gross   = earnings + SSNIT tier 2 (as before).
 *   Deductions = tax + SSNIT tier 2 + every deduction column.
 *   Net     = gross - deductions.   Cost to company = net + 13.5%.
 *
 * Blank amounts count as 0 and are saved as blank.
 */
class SalaryCalculator
{
    /** Earnings columns => label, in the order the salary pages show them. */
    public const EARNINGS = [
        'basic_salary' => 'Basic salary',
        'allowances' => 'Allowances',
        'airtime_allowance' => 'Airtime allowance',
        'overtime' => 'Overtime',
        'reimbursements' => 'Reimbursements',
        'transport_allowance' => 'Transport allowance',
    ];

    /** Deduction columns => label. */
    public const DEDUCTIONS = [
        'welfare' => 'Welfare',
        'maintenance' => 'Maintenance',
        'absent' => 'Absent',
        'boot' => 'Boot',
        'iou' => 'IOU',
        'hostel' => 'Hostel',
        'insurance' => 'Insurance',
        'reprimand' => 'Reprimand',
        'scouter' => 'Scouter',
        'raincoat' => 'Raincoat',
        'meal' => 'Meal',
        'loan' => 'Loan',
        'walkin' => 'Walk-in',
        'amnt_ded_cof_start_date' => 'Start date deduction',
        'other_deductions' => 'Other deductions',
    ];

    /** Worked out here, never typed in. */
    public const TOTALS = [
        'ssnit_tier2_5d', 'ssnit_tier2_5', 'ssnit_tier1_0_5', 'tax', 'gross_salary', 'total_deductions',
        'net_salary', 'ssnit_comp_cont_13', 'ssnit_tobe_paid13_5', 'cost_to_company',
    ];

    /** Every column people type in => label. */
    public static function editable(): array
    {
        return self::EARNINGS + self::DEDUCTIONS;
    }

    public static function isDeduction(string $key): bool
    {
        return isset(self::DEDUCTIONS[$key]);
    }

    /** A Yes/No switch on the employee ("on" from the employee form and the bulk upload). */
    public static function switchOn($value): bool
    {
        return $value !== null && $value !== '' && ! in_array(strtolower(trim((string) $value)), ['off', 'no', '0', 'false'], true);
    }

    /**
     * The employee's SSNIT number: on the employee where that column exists, otherwise
     * (or when blank there) in their payment details, where the employee form keeps it.
     */
    public static function ssnitNumber(?employee $e): ?string
    {
        if (! $e) {
            return null;
        }
        $own = trim((string) ($e->getAttributes()['ssnit_number'] ?? ''));
        if ($own !== '') {
            return $own;
        }
        static $infoHasColumn = null;
        $infoHasColumn ??= Schema::hasColumn('payment_infos', 'ssnit_number');
        if (! $infoHasColumn) {
            return null;
        }
        $fromInfo = $e->relationLoaded('paymentInfo')
            ? $e->paymentInfo?->ssnit_number
            : DB::table('payment_infos')->where('employee_id', $e->id)->orderByDesc('id')->value('ssnit_number');
        $fromInfo = trim((string) $fromInfo);

        return $fromInfo !== '' ? $fromInfo : null;
    }

    /**
     * Totals for these amounts (column => number|null) and this employee.
     *
     * @return array<string, float> TOTALS keys
     */
    public static function totals(array $values, ?employee $e): array
    {
        $v = fn (string $k) => (float) ($values[$k] ?? 0);
        $basic = $v('basic_salary');

        $tier2 = $tier1 = $c13 = $c135 = 0.0;
        $taxable = $basic;
        $ssnit = $e && self::switchOn($e->ssnit_button) && self::ssnitNumber($e) !== null;
        if ($ssnit) {
            $tier2 = $basic * 0.05;
            $tier1 = $basic * 0.005;
            $c13 = $basic * 0.13;
            $c135 = $basic * 0.135;
            $taxable = $basic - $basic * 0.055;
        }

        $tax = 0.0;
        if ($e && self::switchOn($e->tax_button) && $basic > 490.00) {
            $tax = self::tax(max(0, $taxable));
        }

        $gross = $tier2;
        foreach (array_keys(self::EARNINGS) as $k) {
            $gross += $v($k);
        }
        $deductions = $tax + $tier2;
        foreach (array_keys(self::DEDUCTIONS) as $k) {
            $deductions += $v($k);
        }
        $net = $gross - $deductions;

        return [
            'ssnit_tier2_5d' => $tier2,
            'ssnit_tier2_5' => $tier2,
            'ssnit_tier1_0_5' => $tier1,
            'tax' => $tax,
            'gross_salary' => $gross,
            'total_deductions' => $deductions,
            'net_salary' => $net,
            'ssnit_comp_cont_13' => $c13,
            'ssnit_tobe_paid13_5' => $c135,
            'cost_to_company' => $net + $c135,
        ];
    }

    /** Set the amounts on the salary and work out its totals (not saved). */
    public static function fill(Salary $salary, array $values): Salary
    {
        foreach (array_keys(self::editable()) as $k) {
            if (array_key_exists($k, $values)) {
                $salary->$k = $values[$k] === '' ? null : $values[$k];
            }
        }
        $amounts = [];
        foreach (array_keys(self::editable()) as $k) {
            $amounts[$k] = $salary->$k;
        }
        foreach (self::totals($amounts, $salary->employee) as $k => $value) {
            $salary->$k = round($value, 2);   // as the decimal(15,2) columns keep it
        }

        return $salary;
    }

    /** The salary's amounts as numbers (blank stays null). */
    public static function amounts(Salary $salary): array
    {
        $out = [];
        foreach (array_keys(self::editable()) as $k) {
            $out[$k] = $salary->$k === null || $salary->$k === '' ? null : round((float) $salary->$k, 2);
        }

        return $out;
    }

    /**
     * PAYE on a monthly taxable amount. Same bands and steps as
     * SalaryController::taxEmployee() (kept there for the older pages).
     */
    public static function tax(float $amount): float
    {
        // Band 1: the first 490 is free, the next 110 at 5%.
        $over = $amount - 490;
        if ($over < 110) {
            $t1 = $over * 0.05;
            $balance = 0;
        } else {
            $t1 = 110 * 0.05;
            $balance = $over - 110;
        }
        if ($t1 <= 0) {
            return 0.0;
        }
        if ($t1 < 5.5) {
            return $t1;
        }
        // Band 2: the next 130 at 10%.
        $t2 = $balance < 130 ? $balance * 0.10 : 130 * 0.10;
        $rest2 = $balance - 130;
        if ($rest2 < 0) {
            return $t1 + $t2;
        }
        // Band 3: the next 3,166.67 at 17.5%, the rest at 25%.
        $t3 = $rest2 < 3166.67 ? $rest2 * 0.175 : 3166.67 * 0.175;
        $rest3 = $rest2 - 3166.67;
        if ($rest3 < 0) {
            return $t1 + $t2 + $t3;
        }

        return $t1 + $t2 + $t3 + $rest3 * 0.25;
    }
}
