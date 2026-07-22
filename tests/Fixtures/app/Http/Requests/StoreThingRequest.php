<?php

namespace App\Http\Requests;

use App\Models\Thing;
use Illuminate\Foundation\Http\FormRequest;

class StoreThingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Thing::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'email' => 'required|email',
            'amount' => ['nullable', 'numeric', 'min:0'],
            'user_id' => 'required|exists:users,id,deleted_at,'.null,
        ];
    }
}
