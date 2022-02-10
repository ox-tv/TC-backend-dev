<?php

namespace App\Http\Controllers;


use Amir\Permission\Models\Role;
use App\Http\Resources\Role\RoleItem;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::paginate();

        return RoleItem::collection($roles);
    }
}
