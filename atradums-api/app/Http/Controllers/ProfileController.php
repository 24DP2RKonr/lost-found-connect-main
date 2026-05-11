<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $profile = Profile::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['name' => $request->user()->name]
        );

        return $profile;
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avatar_url' => ['sometimes', 'nullable', 'string'],
        ]);

        $profile = Profile::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['name' => $request->user()->name]
        );

        $profile->fill($data);
        $profile->save();

        return $profile;
    }
}
