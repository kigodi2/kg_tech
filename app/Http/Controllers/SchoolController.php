<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Region;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::with('region')->paginate(15);
        return view('schools.index', compact('schools'));
    }

    public function show(School $school)
    {
        if (request()->expectsJson()) {
            return response()->json($school);
        }
        $school->load(['region', 'candidates']);
        return view('schools.show', compact('school'));
    }

    public function create()
    {
        $regions = Region::all();
        return view('schools.create', compact('regions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:schools',
            'name' => 'required',
            'region_id' => 'required|exists:regions,id',
            'school_type' => 'required|in:PRIMARY,SECONDARY,BOTH',
        ]);

        School::create($validated);
        return redirect('/schools')->with('success', 'School created');
    }

    public function edit(School $school)
    {
        $regions = Region::all();
        return view('schools.edit', compact('school', 'regions'));
    }

    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'code' => 'required|unique:schools,code,' . $school->id,
            'name' => 'required',
            'region_id' => 'required|exists:regions,id',
            'school_type' => 'required|in:PRIMARY,SECONDARY,BOTH',
            'address' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'principal_name' => 'nullable',
            'is_active' => 'boolean',
        ]);

        $school->update($validated);
        return redirect('/schools')->with('success', 'School updated');
    }

    public function destroy(School $school)
    {
        $school->delete();
        return redirect('/schools')->with('success', 'School deleted');
    }
}
