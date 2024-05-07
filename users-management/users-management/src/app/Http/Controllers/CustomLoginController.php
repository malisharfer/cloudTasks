<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Services\GetUsers;
use App\Models\User;
use App\Enums\Users\Role;


class CustomLoginController extends Controller
{
    public function UserFromLogin(){
        $user_login = Socialite::driver('azure')->stateless()->user();
        $services = new GetUsers();
        $users = $services->getUsersFromGroups();

        foreach ($users as $user) {
            if($user['name'] == $user_login->name && $user['email'] == $user_login->email){
                if(!($user_login->role == Role::Admin && $user['role'] == 'User')){
                    $user_login = User::updateOrCreate([
                        'name' => $user_login->name,
                        'email' => $user_login->email,
                        'role' => $user['role']
                        ],
                    );
                    Auth::login($user_login);
                }
            }
        }
        if(Auth::user()==null){
            return redirect('/login');
        }
        $this->updateDB($users);
        return redirect('/');
    }

    public function updateDB($allMembers){
        $oldUsers = User::all()->toArray();

        foreach ($oldUsers as $oldUser) {
            $usersWithSameEmail = collect($allMembers)->where('email', $oldUser['email'])->where('role', $oldUser['role'])->count();
            if ($usersWithSameEmail == 0 && !(auth()->user()->email==$oldUser['email'] && auth()->user()->role==$oldUser['role'])) {
                User::where('id', $oldUser['id'])->delete();
            }
        }

        foreach ($allMembers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email'], 'role' => $userData['role']],
                $userData
            );
        }
    }
}
