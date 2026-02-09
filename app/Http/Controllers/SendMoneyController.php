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
                'RecipientMsisdn' => $request->input('recipient_number'),
                'Amount' => $request->input('amount'),
                'Channel' => $request->input('channel'), // e.g., 'mtn-gh'
                'PrimaryCallbackURL' => route('sendMoneyCallback'), // Replace with your actual callback URL
                'Description' => $request->input('description'),
                'ClientReference' => 'FWSS'. Str::random(11)
            ]);

        // return $response->json();
        dd($response);
    }

    
    public function sendMoneyCallback(Request $request)
    {
        dd($request->all());
    }

}
