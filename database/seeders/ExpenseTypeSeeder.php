<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use App\Models\Field;
use Illuminate\Database\Seeder;

class ExpenseTypeSeeder extends Seeder
{
    /**
     * NOTE: adjust the field name strings below ('Accra', 'Tema', 'Botwe',
     * 'Koforidua', 'Takoradi', 'Kumasi') to exactly match the `name` values already
     * in your `fields` table before running. Uses firstOrCreate so this is safe
     * to re-run.
     */
    public function run(): void
    {
        $fields = collect(['Accra', 'Tema', 'Botwe', 'Koforidua', 'Takoradi', 'Kumasi'])
            ->mapWithKeys(fn ($name) => [$name => Field::firstOrCreate(['name' => $name])]);

        // ---- Head Office / Corporate types (scope: corporate) ----
        $corporate = [
            'Office Expense', 'Rent', 'Loan', 'Salary', 'Repairs of Office Building',
            'Loan for Phone', 'Enterprise & Business Promotion', 'Print & Stationery',
            'Light & Heat', 'Communication', 'Bank Charges', 'Utilities',
            'Internet & Postpaid', 'Medicals', 'Office Equipment', 'Uniform & Logistics',
            'Cleaning & Sanitation', 'CEO Miscellaneous', 'Transport', 'Equipment Repairs',
            'Fixtures & Fittings', 'Office MoMo', 'Water', 'Salary Advance',
            'Gun Documentation', 'Couriers & Deliveries Charges',
            'Capital Expense on Office Rent', 'Background Checks', 'Orientation', 'Theft',
            'Education Support, Training & Capacity Building', 'Donation', 'Scouting',
            'T&T Allowance', 'Meal', 'IOU', 'Welfare', 'Website', 'Metropolitan Office',
            'Recruitment', 'SSNIT', 'Software', 'Fees', 'Funeral Expenses',
            'General Expenses', 'PAYE - Tax', 'Directors Loan', 'MoMo Charges',
            'License & Registration', 'Insurance',
        ];

        foreach ($corporate as $name) {
            $type = ExpenseType::firstOrCreate(['name' => $name, 'scope' => 'corporate']);
            $type->fields()->syncWithoutDetaching([$fields['Accra']->id]);
        }

        // ---- Field-unit types (scope: field), canonical name => offices that use it ----
        // Office-name prefixes from the source doc (e.g. "Tema Vehicle Repairs &
        // Maintenance", "Kuamsi Vehicle Repairs & Maintenance") are stripped here;
        // the office is tracked via the field_expense_type pivot instead. This also
        // silently fixes the "Kuamsi" -> "Kumasi" typo in the source document.
        $fieldTypes = [
            'Vehicle Repairs & Maintenance' => ['Tema', 'Kumasi', 'Botwe', 'Koforidua', 'Takoradi'],
            'Overtime'                      => ['Tema', 'Kumasi', 'Botwe', 'Takoradi'],
            'Imprest'                       => ['Tema', 'Kumasi', 'Botwe', 'Koforidua', 'Takoradi'],
            'Accident Car'                  => ['Tema', 'Kumasi'],
            'Vehicle'                       => ['Tema', 'Kumasi'],
            'Vehicle Expense'               => ['Tema', 'Kumasi'],
            'Motor Repairs'                 => ['Tema', 'Kumasi'],
            'Fuel'                          => ['Tema', 'Kumasi', 'Botwe', 'Koforidua', 'Takoradi'],
            'Lubricant'                     => ['Tema', 'Kumasi', 'Botwe', 'Koforidua', 'Takoradi'],
            'Water'                         => ['Tema', 'Kumasi', 'Botwe', 'Koforidua', 'Takoradi'],
            'Salary'                        => ['Tema', 'Kumasi'],
            'Salary Advance'                => ['Botwe', 'Koforidua', 'Takoradi'],
            'Donation'                      => ['Tema', 'Kumasi'],
            'T&T Allowance'                 => ['Tema', 'Kumasi', 'Botwe', 'Koforidua', 'Takoradi'],
            'Print & Stationery'            => ['Tema', 'Kumasi', 'Botwe', 'Koforidua', 'Takoradi'],
            'Internet & Postpaid'           => ['Botwe', 'Koforidua', 'Takoradi'],
        ];

        foreach ($fieldTypes as $name => $officeNames) {
            $type = ExpenseType::firstOrCreate(['name' => $name, 'scope' => 'field']);
            $type->fields()->syncWithoutDetaching(
                collect($officeNames)->map(fn ($n) => $fields[$n]->id)
            );
        }

        // ---- Accommodation / hostel types (cross-cutting, scope: field, flagged) ----
        $hostels = [
            'Mataheko Hostel'                       => [],                 // Accra-area, not tied to a field unit in the source doc
            'Asylum Down Hostel'                    => [],
            'Adabraka Hostel'                       => [],
            'Hostel Expense'                        => [],
            'Kumasi Hostel'                          => ['Kumasi'],
            'Koforidua Hostel'                       => ['Koforidua'],
            'Botwe Hostel (Communication)'   => ['Botwe'],
        ];

        foreach ($hostels as $name => $officeNames) {
            $type = ExpenseType::firstOrCreate(
                ['name' => $name, 'scope' => 'field'],
                ['is_accommodation' => true]
            );
            $type->update(['is_accommodation' => true]);

            if (! empty($officeNames)) {
                $type->fields()->syncWithoutDetaching(
                    collect($officeNames)->map(fn ($n) => $fields[$n]->id)
                );
            }
        }
    }
}
