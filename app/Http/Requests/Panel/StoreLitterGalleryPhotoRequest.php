<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class StoreLitterGalleryPhotoRequest extends FormRequest
{
    protected $errorBag = 'litterGallery';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ];
    }
}

