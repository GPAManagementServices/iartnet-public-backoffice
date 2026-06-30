<?php

namespace App\Http\Requests\Api;

use App\Rules\MediaCuratorPathRule;
use Illuminate\Foundation\Http\FormRequest;

class MediaSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'path' => ['required', 'string', 'max:4096', new MediaCuratorPathRule],
            'w' => 'nullable|integer|min:10|max:2500',
            'h' => 'nullable|integer|min:10|max:2500',
            'fit' => 'nullable|string|in:contain,max,fill,stretch,crop',
            'fm' => 'nullable|string|in:webp,png,jpg',
        ];
    }
}
