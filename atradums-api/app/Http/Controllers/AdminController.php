<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        $me = $request->user();

        if (! $me || ! (bool) ($me->is_admin ?? false)) {
            abort(403);
        }

        return User::query()
            ->with(['profile:user_id,name,phone,location,avatar_url'])
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'is_admin', 'email_verified_at', 'created_at']);
    }

    public function destroy(Request $request, User $user)
    {
        $me = $request->user();

        if (! $me || ! (bool) ($me->is_admin ?? false)) {
            abort(403);
        }

        if ($me->id === $user->id) {
            return response()->json(['message' => 'Nevar izdzēst pašam sevi.'], 422);
        }

        $user->delete();

        return response()->json(['success' => true]);
    }
}

