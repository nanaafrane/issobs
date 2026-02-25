<?php

namespace App\Exports;

use App\Models\Salary;
// use Maatwebsite\Excel\Concerns\FromCollection;

// use Illuminate\Contracts\View\View;
// use Maatwebsite\Excel\Concerns\FromView;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalaryExport implements FromQuery
{  
      use Exportable;  
    /**
    * @param Salary $salary
    */

    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }


    public function query()
    {
        return Salary::query()->where('salary_month', $this->month);
    }

}

