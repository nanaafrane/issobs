<?php

namespace App\Http\Controllers;

use App\Models\PaymentInfo;
use App\Http\Requests\StorePaymentInfoRequest;
use App\Http\Requests\UpdatePaymentInfoRequest;

class PaymentInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentInfoRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentInfo $paymentInfo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentInfo $paymentInfo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentInfoRequest $request, PaymentInfo $paymentInfo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentInfo $paymentInfo)
    {
        //
    }
}
