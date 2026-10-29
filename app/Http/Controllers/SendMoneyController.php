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

    public $amount;

    public function __construct()
    {
        $this->middleware('auth');
    }

    //
    public function index()
    {
        return view('salaries.sendMoney_index');
    }


    // public function sendMoney($name, $amount, $number, $channel, $month)
    // {
    //     $this->deductCharge($amount);

    //     // return $this->amount . " " . $amount;

    //     // dd($request->all());
    //         // $prepaidDepositId = '2024483'; // Replace with your actual Prepaid Deposit ID
    //         $url = "https://smp.hubtel.com/api/merchants/2024483/send/mobilemoney";
            

    //         $response = Http::withHeaders([
    //             'Content-Type' => 'application/json',
    //             'Authorization' => 'Basic ' . base64_encode('B81PkQQ:a239c6cf6e8d4dec8ae1d866ef0c633a'), 
    //         ])->post($url, [
    //             'RecipientName' => $name,
    //             'RecipientMsisdn' => $number,
    //             'Amount' => $this->amount,
    //             'Channel' => $channel, 
    //             'PrimaryCallbackURL' => 'https://issobs.com/sendMoneyCallback', 
    //             'Description' => 'FIRST WATCH SECURITY'." " .strtoupper($month) ." " . 'SALARY',
    //             'ClientReference' => 'FWSS'. Str::random(11)
    //         ]);

    //     // return $response->json();
    //     // return redirect()->route('sendMoneyCallback', ['request' => $response->json()]);
    //     // return url('sendMoneyCallback');
    //     // return  $this->sendMoneyCallback($response->json());
    //     $result =  $response->json();
    //     // $this->checkResponse($result);
    //     return $result;
    //     // $checkedData =  $this->statusCheck($result['Data']['ClientReference']);
    //     // dd($checkedData);
    // }

    // public function sendMoneyCallback(Request $request)
    // {
    //     dd($request->all());
    // }

    // public function checkResponse($result)
    // {
    //         // if($result->ResponseCode == "0001"){
    //         //     // INSERT PAYLOAD INTO HUBTEL PAYMENT DB

    //         //     // UPDATE HUBTEL PAYMENT ID ON SALARY MODEL
    //         // }

            
    // }


    // public function statusCheck($clientReference)
    // {
    //         $url = "https://smrsc.hubtel.com/api/merchants/2024483/transactions/status?clientReference=".$clientReference ;
            

    //         $response = Http::withHeaders([
    //             'Content-Type' => 'application/json',
    //             'Authorization' => 'Basic ' . base64_encode('B81PkQQ:a239c6cf6e8d4dec8ae1d866ef0c633a')
    //         ])->get($url);

    //         return $response->json();
    // }


    // public function payCashSalary(Request $request)
    // {
    //     // dd($request->all());
    //      $salaryIds = $request->input('salary', []);
    //     //  dd($salaryIds);
    //     if (empty($salaryIds)) {
    //         return back()->with('error', 'No employee selected for Salary Processing.');
    //     }

    //     $salaries =  Salary::findOrFail($request->salary);
    //     // dd($salaries);

    //     $action = $request->input('action_type') ?? $request->input('submit');

    //     if (! Auth::user()?->hasRole(['Finance Manager'])) {
    //         return back()->with('error', 'Only a Finance Manager can send or confirm salary payments.');
    //     }

    //     if($action == 'pay')
    //         {
    //             // return " You are about to Pay ";
    //             $skipped = [];

    //             foreach ($salaries as $salary )
    //                 {
    //                     // Never send money twice: approved (paid), or already sent / succeeded.
    //                     if ($salary->payment_status !== 'pending' || in_array(strtolower((string) $salary->status1), ['pending', 'success'], true)) {
    //                         $skipped[] = ($salary->employee?->name ?? 'Unknown') . ' (ID ' . $salary->id . ')';
    //                         continue;
    //                     }
    //                     // SEND MONEY
    //                 $result = $this->sendMoney($salary->employee->name, $salary->net_salary, $salary->employee->phone_number, $salary->employee->channel,  Carbon::parse($salary->salary_month)->format('F')); 
    //                 // echo  $result . "<br>" ;
    //                     //    print_r( $result) . "<br>";
    //                     // LOG RESPONSE
    //                 $id =  DB::table('hubtel')->insertGetId([
    //                             'responseCode' => $result['ResponseCode'],
    //                             'TransactionId' => $result['Data']['TransactionId'],
    //                             'ClientReference' => $result['Data']['ClientReference'],
    //                             'Description' => $result['Data']['Description'],
    //                             'Amount' => $result['Data']['Amount'],
    //                              'transactionStatus' => 'Pending',
    //                             'Salary_id' => $salary->id,
    //                             'staff_id' => Auth::id(),
    //                             'created_at' => now(),
    //                             'updated_at' => now(),
    //                         ]);

    //                     // UPDATE SALARY MODEL
    //                     $salary->hubtel_id = $id;
    //                     $salary->status1 = 'Pending';
    //                     $salary->save();
    //                 }

    //                 $skippedNote = $skipped ? ' Skipped (not pending, or already sent): ' . implode(', ', $skipped) . '.' : '';

    //                 return back()->with('success', ' Payments has been made, Wait for up to 5 mins to check confirmation.' . $skippedNote);

    //         }
    //     elseif($action == 'confirm')
    //         {
    //             // return " You are about to run status check";

    //             foreach ($salaries as $salary )
    //                 {
    //                     // Nothing sent for this salary (or already confirmed): nothing to check.
    //                 if (! $salary->hubtel_id || $salary->status1 === 'success') {
    //                     continue;
    //                 }
    //                     // RUN A STATUS CHECK WITH CLIENT REFERENCE
    //                 $hubtel = DB::table('hubtel')->where('id', $salary->hubtel_id)->get();
    //                 $data = json_decode($hubtel, true);

    //                 $result = $this->statusCheck( $data[0]['ClientReference'] ); 

    //                 // echo $data[0]['ClientReference'] . "<br>" . "<br>";
    //                 // UPDATE RESPONSE
    //                 DB::table('hubtel')->where('ClientReference', $data[0]['ClientReference'])
    //                             ->update([
    //                             'responseCode' => $result['ResponseCode'],
    //                             'Description' => $result['Data']['Description'],
    //                             'Amount' => $result['Data']['Amount'],
    //                             'fees' => $result['Data']['fees'],
    //                             'Channel' => $result['Data']['Channel'],
    //                             'customerNumber' => $result['Data']['customerNumber'],
    //                             'transactionStatus' => $result['Data']['transactionStatus'],
    //                             'CreatedAt' => $result['Data']['CreatedAt'],
    //                             'Salary_id' => $salary->id,
    //                             'staff_id' => Auth::id(),
    //                             'updated_at' => now(),
    //                         ]);

    //                     // UPDATE SALARY MODEL IF RESPONSE IS SUCCESSFUL

    //                     if( $result['Data']['transactionStatus'] == 'success')
    //                     {
    //                         $salary->status1 = $result['Data']['transactionStatus'];
    //                         $salary->payment_status = 'approved';
    //                         $salary->approval_date = $salary->approval_date ?? now();
    //                         $salary->user_id2 = $salary->user_id2 ?? Auth::id();
    //                         $salary->save(); 
    //                         // Paid by MoMo: record it in the open payment of the month.
    //                         \App\Support\PaymentRuns::recordSalary($salary, 'momo');
    //                     }else{
    //                         $salary->status1 = $result['Data']['transactionStatus'];
    //                         $salary->save(); 
    //                     }                       
    //                 }

    //                 return back()->with('success', 'Confirmation Process completed');

    //         }
  
  
  
    // }

    public function deductCharge($netSalary)
    {
       if($netSalary <= 500 )
        {
            $this->amount = $netSalary - 0.5;
        }
        else{

            $this->amount = $netSalary - 5.00;

        }
    
    }

}
