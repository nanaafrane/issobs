<?php

namespace App\Http\Controllers;

use App\Models\category;
use App\Http\Requests\StorecategoryRequest;
use App\Http\Requests\UpdatecategoryRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{

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
        // $categories = category::all();
        return view('categories.index');
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
    public function store(StorecategoryRequest $request)
    {
        //
        // dd($request->all());
        $category = new category();
        $category->name = $request->name;
        $category->user_id = Auth::id(); 
        $category->save();
        return back()->with('success', 'Category added successfully');
    }

        /**
     * Category Add Clients For A Month.
     */
    public function categorySearch(Request $request)
    {
        // dd($request->all());
        $date = Carbon::parse($request->input('month')); 
        $categories = category::whereMonth('category_month', $date->month)->get();
        return view('categories.index', compact('categories'));


    }


    /**
     * Category Add Clients For A Month.
     */
    public function categoryAssign(Request $request)
    {
        // dd($request->all());

        // CHECK IF INCOMING REQUEST IS NOT EMPTY
        $client = $request->input('client', []);
        // dd($employees);
        if (empty($client)) {
            return back()->with('error', 'No Client selected to assign.');
        }


        // get current month and year
        $date = Carbon::parse($request->input('month')); 
        // dd($date->format('Y-m-d'));
        // FIND CATEGORY
        $Clients = Client::findOrFail($request->client);

        foreach($Clients as $key => $categoryClient){

            //  CHECK IF CLIENT ALREADY EXIST FOR THE SAME MONTH
             $exists = category::where('client_id', $request->client[$key])
                                ->whereMonth('category_month', $date->month)
                                ->exists();

                if ($exists) {
                    $alreadyProcessed[] = $request->client[$key];
                    continue;
                }

                // echo  $request->client[$key] .    $request->name[$key] ."<br>"; 
            $category = new category();
            $category->client_id = $request->client[$key];
            $category->name = $request->name[$key];
            $category->category_month = $date->format('Y-m-d');
            $category->user_id = Auth::id(); 
            $category->save();

            // UPDATE CLIENT AND MONTH
            $categoryClient->category_id = $category->id;
            $categoryClient->category_name = $request->name[$key];
            $categoryClient->category_month = $date->format('Y-m-d');
            $categoryClient->save();
        }

        if (!empty($alreadyProcessed)) {
            return back()->with('error', 'The Clients with the IDs have already been Assign to a Category for this month: '. implode(', ', $alreadyProcessed)) ;
        }
        return back()->with('success', 'Clients added to this month salary'); 

    }


    /**
     * Change Category  Clients For A Month.
     */
    public function categoryReAssign(Request $request)
    {
        // dd($request->all());

        // CHECK IF INCOMING REQUEST IS NOT EMPTY
        $client = $request->input('client', []);
        // dd($employees);
        if (empty($client)) {
            return back()->with('error', 'No Client selected to ReAssign.');
        }


        // get current month and year
        // dd($date->format('Y-m-d'));
        // FIND CATEGORY

        $clientData =  collect($request->client)
                        ->map(function ($clientId, $index) use ($request) {

                            // update category for the client and month
                            $date = Carbon::parse($request->input('month')); 
                            $category = category::where('client_id', $clientId)
                                                ->whereMonth('category_month', $date->month)
                                                ->first();
                            if ($category) {
                                $category->name = $request->name[$index];
                                $category->save();
                            }
                            // update client category name
                            $client = Client::findorFail($clientId);
                            if ($client) {
                                // $client->category_id = $category->id;
                                $client->category_name = $request->name[$index];
                                // $client->category_month = $date->format('Y-m-d');
                                $client->save();
                            }

                        });

        // dd($clientData);
         return back()->with('success', 'Clients ReAssigned to this month salary');
    }



    /**
     * Display the specified resource.
     */
    public function show(category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatecategoryRequest $request, category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(category $category)
    {
        //
    }
}
