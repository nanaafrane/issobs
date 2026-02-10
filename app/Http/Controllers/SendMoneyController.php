<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SendMoneyController extends Controller
{

    public $amount;
    public $number;
    public $name;
    public $post_data;
    //
    public function index()
    {
        return view('salaries.sendMoney_index');
    }


    public function sendMoney(Request $request)
    {
        // dd($request->all());

        
        //     // $prepaidDepositId = '2024483'; // Replace with your actual Prepaid Deposit ID
        //     $url = "https://smp.hubtel.com/api/merchants/2024483/send/mobilemoney";
            

        //     $response = Http::withHeaders([
        //         'Content-Type' => 'application/json',
        //         'Authorization' => 'Basic ' . base64_encode('B81PkQQ:a239c6cf6e8d4dec8ae1d866ef0c633a'), // Replace with your actual credentials
        //     ])->post($url, [
        //         'RecipientName' => $request->input('recipient_name'),
        //         'RecipientMsisdn' => $request->input('recipient_number'),
        //         'Amount' => $request->input('amount'),
        //         'Channel' => $request->input('channel'), // e.g., 'mtn-gh'
        //         'PrimaryCallbackURL' => route('sendMoneyCallback'), // Replace with your actual callback URL
        //         'Description' => $request->input('description'),
        //         'ClientReference' => 'FWSS'. Str::random(11)
        //     ]);

        // // return $response->json();
        // dd($response->json());

            $this->post_data = array(
                'RecipientName' => $request->input('recipient_name'),
                'RecipientMsisdn' => $request->input('recipient_number'),
                'Amount' => $request->input('amount'),
                'Channel' => $request->input('channel'), // e.g., 'mtn-gh'
                'PrimaryCallbackURL' => 'https://issobs.com/sendMoneyCallback', // Replace with your actual callback URL
                'Description' => $request->input('description'),
                'ClientReference' => 'FWSS1234567890',
            );

            $myJason = json_encode($this->post_data);
            $curl = curl_init();
            curl_setopt_array($curl, [

                CURLOPT_URL => "https://webhook.site/b4ff4762-0d57-473c-98b3-d620eebd0012",

                CURLOPT_CUSTOMREQUEST => "POST",

                CURLOPT_POSTFIELDS => $myJason,

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Basic " .  base64_encode("B81PkQQ:a239c6cf6e8d4dec8ae1d866ef0c633a")
                ]

            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            $result = json_decode($response);

            if ($error) {
                return "cURL Error #:" . $error;
            }else{
             return $result;
            // var_dump($result);
            
            }

    }

    
    public function sendMoneyCallback(Request $request)
    {
        dd($request->all());
    }

}
