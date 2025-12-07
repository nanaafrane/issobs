<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Http\Requests\StoreFieldRequest;
use App\Http\Requests\UpdateFieldRequest;
use Illuminate\Support\Facades\Auth;

class FieldController extends Controller
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
        $Locations = Field::all();
        $LocationsCount = count($Locations);
        return view('locations.index', compact('Locations', 'LocationsCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        Field::create([
            // 'name' => 'Accra',
            // 'user_id' => Auth::user()->id,

            // 'name' => 'Botwe',
            // 'user_id' => Auth::user()->id,

            // 'name' => 'Tema',
            // 'user_id' => Auth::user()->id,

            // 'name' => 'Takoradi',
            // 'user_id' => Auth::user()->id,

            // 'name' => 'Koforidua',
            // 'user_id' => Auth::user()->id,

            // 'name' => 'Kumasi',
            // 'user_id' => Auth::user()->id,
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFieldRequest $request)
    {
        //
        $field = new Field();
        // $field->create($request->all());
        $field->name = $request->input('name');
        $field->user_id = Auth::user()->id;
        $field->save();
        return back()->with('success', 'Field Office Added Sucessfully');
      }

    /**
     * Display the specified resource.
     */
    public function show(Field $field)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Field $field)
    {
        //
        // dd($field);
        return view('locations.edit', compact('field'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFieldRequest $request, Field $field)
    {
        //
        // dd($request->all(), $field);
         $field->name = $request->input('name');

        if($field->isDirty()) {
            $field->name = $request->input('name');
            $field->user_id = Auth::user()->id;
            $field->save();
            return redirect()->route('field.index')->with('success', 'Field Office has been successfully updated!');
        }
        else{
            return back()->with('error', 'No changes detected. Please edit Field Office Name to Update.');
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Field $field)
    {
        //
        // dd($field);
        $field->delete();
        return back()->with('error', 'Field Office has been successfully deleted!');
    }
}
