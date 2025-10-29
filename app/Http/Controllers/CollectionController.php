<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;

class CollectionController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
       $collections = Collection::all();
       $clientsCount = count($collections);


       $accra = Collection::where('field_id', 1)->get();
       $accraTotal = $accra->sum('total_amount');
       $accraCount = count($accra);

        $botwe = Collection::where('field_id', 2)->get();
        $botweTotal = $botwe->sum('total_amount');
        $botweCount = count($botwe);

        $tema = Collection::where('field_id', 3)->get();
        $temaTotal = $tema->sum('total_amount');
        $temaCount = count($tema);

        $takoradi = Collection::where('field_id', 4)->get();
        $takoradiTotal = $takoradi->sum('total_amount');
        $takoradiCount = count($takoradi);

        $koforidua = Collection::where('field_id', 5)->get();
        $koforiduaTotal = $koforidua->sum('total_amount');
        $koforiduaCount = count($koforidua);

        $kumasi = Collection::where('field_id', 6)->get();
        $kumasiTotal = $kumasi->sum('total_amount');
        $kumasiCount = count($kumasi);


       return view('collections.index', compact('collections', 'accra', 'botwe', 'tema', 'takoradi', 'koforidua', 'kumasi', 'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));

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
    public function store(StoreCollectionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Collection $collection)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Collection $collection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCollectionRequest $request, Collection $collection)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Collection $collection)
    {
        //
    }
}
