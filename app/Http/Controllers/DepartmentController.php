<?php

namespace App\Http\Controllers;

use App\Http\Resources\Department\DepartmentCollection;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return DepartmentCollection::make(Department::all());
    }
}
