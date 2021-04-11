<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Http\Resources\UserItem;
use App\Models\Department;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return UserCollection
     */
    public function index(Request $request)
    {

        if($request->is('api/admin/publishers')){
            $query = User::publishers();
        }elseif ($request->is('api/admin/admins')){
            $query = User::admins();
        }elseif ($request->is('api/admin/publisher-requests')){
            $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Applications'])->id;

            $publisherRequestUserId = Message::where([
                    'department_id' => $publisherApplicationDepartmentId
                ]
            )->select('user_id')->get()->pluck('user_id')->unique()->filter(function ($value) { return !is_null($value); })->toArray();
            $query = User::whereIn('id', $publisherRequestUserId);
        }else{
            $query = User::query();
        }

        $filters = $request->get('filters', []);

        $usernameFilter = Arr::get($filters, 'username');

        $emailFilter = Arr::get($filters, 'email');

        if($usernameFilter){
            $query->SearchUsername($usernameFilter);
        }

        if($emailFilter){
            $query->SearchEmail($emailFilter);
        }

        $users = $query->paginate();

        return UserCollection::make($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        $user = Auth::user();

        return response()->json(new UserItem($user));

    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'username' => 'nullable|string|alpha_dash',
            //'email' => 'nullable|email',
            'avatar' => 'nullable|string',
            'current_password' => 'nullable||string|password|required_with:new_password',
            'new_password' => 'nullable|string|min:6|max:32|required_with:current_password',
        ]);

        $user = Auth::user();

        $user->username = $request->get('username', $user->username);

        $user->avatar = $request->get('avatar', $user->avatar);

        if($request->get('new_password')){
            $user->password = Hash::make($request->get('new_password'));
        }

        $user->eth_address = $request->get('eth_address', $request->eth_address);

        $user->save();

        return response()->json(new UserItem($user));

    }


}
