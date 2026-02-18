<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SendMoneyController extends Controller
{
    //
    public function index()
    {
        return view('salaries.sendMoney_index');
    }


    public function sendMoney(Request $request)
    {
        // dd($request->all());
            // $prepaidDepositId = '2024483'; // Replace with your actual Prepaid Deposit ID
            $url = "https://smp.hubtel.com/api/merchants/2024483/send/mobilemoney";
            

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('B81PkQQ:a239c6cf6e8d4dec8ae1d866ef0c633a'), // Replace with your actual credentials
            ])->post($url, [
                'RecipientName' => $request->input('recipient_name'),
                'RecipientMsisdn' => 233247759944,
                'Amount' => 1.00,
                'Channel' => 'mtn-gh', // e.g., 'mtn-gh'
                'PrimaryCallbackURL' => 'https://issobs.com/api/sendEmployeeMoney', // Replace with your actual callback URL
                'Description' => 'FIRST WATCH TEST SALARIES FOR THE MONTH',
                'ClientReference' => 'FWSS'. Str::random(11)
            ]);

        // return $response->json();
        // return redirect()->route('sendMoneyCallback', ['request' => $response->json()]);
        // return url('sendMoneyCallback');
        // return  $this->sendMoneyCallback($response->json());
        $result =  $response->json();
        // $this->checkResponse($result);

        $checkedData =  $this->statusCheck($result->data->clientReference);
        dd($checkedData);
    }

    public function checkResponse($result)
    {
            if($result->ResponseCode == "0001"){
                // INSERT PAYLOAD INTO HUBTEL PAYMENT DB

                // UPDATE HUBTEL PAYMENT ID ON SALARY MODEL
            }

            
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

}
