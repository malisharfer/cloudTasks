<?php

namespace App\Models;

use App\Enums\Users\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => Role::class,
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
    ];

    public function updateUsersInDB($all_members)
    {
        $this->deleteUnnecessaryUsers($all_members, $this->all()->toArray());
        $this->updateUsersDetiles($all_members);
    }

    public function deleteUnnecessaryUsers($all_members, $old_users)
    {
        foreach ($old_users as $old_user) {
            $users_with_same_email = collect($all_members)->where('email', $old_user['email'])->where('role', $old_user['role'])->count();
            if ($users_with_same_email == 0 && ! (auth()->user()->email == $old_user['email'] && auth()->user()->role == $old_user['role'])) {
                $this->where('id', $old_user['id'])->delete();
            }
        }
    }

    public function updateUsersDetiles($all_members)
    {
        foreach ($all_members as $user) {
            $this->updateOrCreate(
                ['email' => $user['email'], 'role' => $user['role']],
                $user
            );
        }
    }

    public function defineUserLoginRole($user_login, $role)
    {
        $user = $this->updateOrCreate(
            ['email' => $user_login->email],
            [
                'name' => $user_login->name,
                'email' => $user_login->email,
                'role' => $role,
            ]
        );

        return $user;
    }
}
