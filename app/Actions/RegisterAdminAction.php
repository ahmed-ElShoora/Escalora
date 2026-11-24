<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class RegisterAdminAction
{
    /**
     * @param  array{name: string, email: string, password: string, company: string}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $company = Company::query()->create([
                'name' => $data['company'],
            ]);

            $admin = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'company_id' => $company->id,
            ]);

            event(new Registered($admin));

            return $admin;
        });
    }
}
