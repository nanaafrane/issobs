<?php

namespace App\Imports;

use App\Models\Salary;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
// use Maatwebsite\Excel\Concerns\ToModel;

class SalariesImport implements ToCollection, WithHeadingRow, WithUpserts
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            Salary::updateOrCreate(
                [
                    'employee_id' => $row['employee_id'], // Unique identifier in your Excel file and database
                ],
                [
                    'gross_salary' => $row['gross_salary'],
                    'total_deductions' => $row['total_deductions'],
                    'net_salary' => $row['net_salary'],
                    'cost_to_company' => $row['cost_to_company'],
                    // Add other fields you want to update
                ]
            );
        }
    }

    /**
     * @return string|array
     */
    public function uniqueBy()
    {
        return 'employee_id'; // The column to check for existing records
    }
}



// class SalariesImport implements ToModel 
// {
//     /**
//     * @param array $row
//     *
//     * @return \Illuminate\Database\Eloquent\Model|null
//     */
//     public function model(array $row)
//     {
//         return new Salary([
//             //
//         ]);
//     }
// }
