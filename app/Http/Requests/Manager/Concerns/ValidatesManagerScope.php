<?php

namespace App\Http\Requests\Manager\Concerns;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait ValidatesManagerScope
{
    /** The selected user must be a tenant. */
    protected function tenantExists(): Exists
    {
        return Rule::exists('users', 'id')->where('role', User::ROLE_TENANT);
    }

    /** The selected stall must be managed by the current manager. */
    protected function ownStallExists(): Exists
    {
        return Rule::exists('stalls', 'id')->where('manager_id', $this->user()->id);
    }
}
