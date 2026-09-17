<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMitigationRequest;
use App\Http\Requests\UpdateMitigationRequest;
use App\Models\Mitigation;

class MitigationController extends Controller
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
    public function store(StoreMitigationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Mitigation $mitigation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mitigation $mitigation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMitigationRequest $request, Mitigation $mitigation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mitigation $mitigation)
    {
        //
    }
}
