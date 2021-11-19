<?php

namespace App\Http\Controllers;


use Amir\Permission\Models\Role;
use App\Http\Requests\Tag\TagStore;
use App\Http\Requests\Tag\TagUpdate;
use App\Http\Resources\Role\RoleItem;
use App\Http\Resources\Tag\TagItem;
use App\Models\Tag;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::paginate();

        return RoleItem::collection($roles);
    }
}
