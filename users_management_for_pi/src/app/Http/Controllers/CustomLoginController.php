<?php

namespace App\Http\Controllers;

use App\Enums\Users\Role;
use App\Models\User;
use App\Services\GetUsers;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class CustomLoginController extends Controller
{
    public function UserFromLogin()
    {
        $user_login = Socialite::driver('azure')->stateless()->user();
        if ($user_login->name == config('services.azure.super_admin')) {
            $user_with_role = $this->defineUserSuperAdmin($user_login);
        } else {
            $user_with_role = $this->defineUser($user_login);
        }
        Auth::login($user_with_role);

        if (Auth::user() == null) {
            return redirect('/login');
        }

        return redirect('/');
    }

    public function defineUser($user_login)
    {
        $services = app(GetUsers::class);
        $group_ids = [config('services.azure.group_id_clients'), config('services.azure.group_id_admins')];
        $role = $services->checkGroupMemberships($user_login->id, $group_ids);
        $user = new User();
        $user_with_role = $user->defineUserLoginRole($user_login, $role);

        return $user_with_role;
    }

    public function defineUserSuperAdmin($user_login)
    {
        $user = new User();
        $user_with_role = $user->defineUserLoginRole($user_login, Role::Admin);

        return $user_with_role;
    }
}
