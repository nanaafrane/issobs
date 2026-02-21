<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\Clock\now;

class SendMoneyController extends Controller
{
    //
    public function index()
    {
        return view('salaries.sendMoney_index');
    }


    public function sendMoney($name, $number, $channel, $month)
    {
        // dd($request->all());
            // $prepaidDepositId = '2024483'; // Replace with your actual Prepaid Deposit ID
            $url = "https://smp.hubtel.com/api/merchants/2024483/send/mobilemoney";
            

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('B81PkQQ:a239c6cf6e8d4dec8ae1d866ef0c633a'), 
            ])->post($url, [
                'RecipientName' => $name,
                'RecipientMsisdn' => $number,
                'Amount' => 1,
                'Channel' => $channel, 
                'PrimaryCallbackURL' => 'https://issobs.com/sendMoneyCallback', 
                'Description' => 'FIRST WATCH SECURITY'." " .strtoupper($month) ." " . 'SALARY',
                'ClientReference' => 'FWSS'. Str::random(11)
            ]);

        // return $response->json();
        // return redirect()->route('sendMoneyCallback', ['request' => $response->json()]);
        // return url('sendMoneyCallback');
        // return  $this->sendMoneyCallback($response->json());
        $result =  $response->json();
        // $this->checkResponse($result);
        return $result;
        // $checkedData =  $this->statusCheck($result['Data']['ClientReference']);
        // dd($checkedData);
    }

    public function sendMoneyCallback(Request $request)
    {
        dd($request->all());
    }

    public function checkResponse($result)
    {
            // if($result->ResponseCode == "0001"){
            //     // INSERT PAYLOAD INTO HUBTEL PAYMENT DB

            //     // UPDATE HUBTEL PAYMENT ID ON SALARY MODEL
            // }

            
    }


    public function statusCheck($clientReference)
    {
            $url = "https://smrsc.hubtel.com/api/merchants/2024483/transactions/status?clientReference=".$clientReference ;
            

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('B81PkQQ:a239c6cf6e8d4dec8ae1d866ef0c633a'), // Replace with your actual credentials
            ])->get($url);

            return $response->json();
    }


    public function payCashSalary(Request $request)
    {
        // dd($request->all());
         $salaryIds = $request->input('salary', []);
        //  dd($salaryIds);
        if (empty($salaryIds)) {
            return back()->with('error', 'No employee selected for Salary Processing.');
        }

        $salaries =  Salary::findOrFail($request->salary);
        // dd($salaries);

        if(isset($request->submit) && $request->submit == 'pay')
            {
                // return " You are about to Pay ";

                foreach ($salaries as $salary )
                    {
                        // SEND MONEY
                    $result = $this->sendMoney($salary->employee->name, $salary->employee->phone_number, $salary->employee->channel,  Carbon::parse($salary->salary_month)->format('F')); 

                        //    print_r( $result) . "<br>";
                        // LOG RESPONSE
                    $id =  DB::table('hubtel')->insertGetId([
                                'responseCode' => $result['ResponseCode'],
                                'TransactionId' => $result['Data']['TransactionId'],
                                'ClientReference' => $result['Data']['ClientReference'],
                                'Description' => $result['Data']['Description'],
                                'Amount' => $result['Data']['Amount'],
                                'Salary_id' => $salary->id,
                                'staff_id' => Auth::id(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                        // UPDATE SALARY MODEL
                        $salary->hubtel_id = $id;
                        $salary->save();

                        return back()->with('success', ' Payments has been made, Wait for up to 5 mins to check confirmation');
                        
                    }
            }
        elseif(isset($request->submit) && $request->submit == 'confirm')
            {
                // return " You are about to run status check";

                foreach ($salaries as $salary )
                    {
                        // RUN A STATUS CHECK WITH CLIENT REFERENCE
                    $ClientReference = DB::table('hubtel')->where('id', $salary->hubtel_id)->pluck('ClientReference');
                    // $result = $this->statusCheck( $ClientReference ); 

                    print_r( $ClientReference) . "<br>";
                        // UPDATE RESPONSE
                    // DB::table('hubtel')->where('ClientReference', $ClientReference)
                    //             ->update([
                    //             'responseCode' => $result['ResponseCode'],
                    //             'Description' => $result['Data']['Description'],
                    //             'Amount' => $result['Data']['Amount'],
                    //             'fees' => $result['Data']['fees'],
                    //             'Channel' => $result['Data']['Channel'],
                    //             'customerNumber' => $result['Data']['customerNumber'],
                    //             'transactionStatus' => $result['Data']['transactionStatus'],
                    //             'CreatedAt' => $result['Data']['CreatedAt'],
                    //             'Salary_id' => $salary->id,
                    //             'staff_id' => Auth::id(),
                    //             'updated_at' => now(),
                    //         ]);

                    //     // UPDATE SALARY MODEL IF RESPONSE IS SUCCESSFUL
                    //     $salary->status1 = $result['Data']['transactionStatus'];
                    //     $salary->save();

 

                    //     return back()->with('success', 'Confirmation Process completed');
                        
                    }

            }
  
  
  
    }

}
