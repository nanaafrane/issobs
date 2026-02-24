<?php

namespace App\Http\Controllers;

use App\Models\ProformaClient;
use App\Http\Requests\StoreProformaClientRequest;
use App\Http\Requests\UpdateProformaClientRequest;
use App\Models\Field;

class ProformaClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $clients = ProformaClient::all();
        $clientsCount = count($clients);
        $fields = Field::all();

        return view('sales.proformaClient_index', compact('clients', 'clientsCount', 'fields'));

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
    public function store(StoreProformaClientRequest $request)
    {
        //
        // dd($request->all());
        $client = new ProformaClient();
        $client->create($request->all());
        return redirect()->back()->with('success', 'Proforma Client Added Sucessfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProformaClient $proformaClient)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProformaClient $proformaClient)
    {
        //
        // dd($proformaClient);
        $fields = Field::all();
        return view('sales.proformaClient_edit', compact('proformaClient', 'fields'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProformaClientRequest $request, ProformaClient $proformaClient)
    {
        //
         $proformaClient->update($request->all());
        return redirect('proformaClient')->with('info', $proformaClient->id. ' '.'Client Updated Sucessfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProformaClient $proformaClient)
    {
        //
        // dd($proformaClient);
            $proformaClient->delete();
            return back()->with('error', 'Proforma Client Deleted Successfully');
    }
}
