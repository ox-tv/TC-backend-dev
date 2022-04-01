<?php

namespace App\Http\Controllers;

use App\Http\Resources\Department\DepartmentCollection;
use App\Http\Resources\Department\DepartmentItem;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::query();

        if($request->is('api/departments')){
            $query->scopeUserOrGlobal();
        }elseif ($request->is('api/publisher/departments')){
            $query->scopePublisherOrGlobal();
        }

        $departments = $query->get();

        if($request->is('api/admin/*')){
            $departments->append(['scope_text']);
        }

        return DepartmentItem::collection($departments);
    }
}
