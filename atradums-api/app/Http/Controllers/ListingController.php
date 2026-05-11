<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Listing::query()
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:lost,found'],
            'category' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'image' => ['sometimes', 'nullable', 'string'],
            'image_file' => ['sometimes', 'nullable', 'file', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = uniqid('listing_') . '.' . $file->getClientOriginalExtension();
            $directory = public_path('uploads/listings');
            if (! file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $file->move($directory, $filename);
            $data['image'] = url('/uploads/listings/' . $filename);
        }

        $listing = Listing::create([
            ...$data,
            'user_id' => $request->user()->id,
            'views' => 0,
        ]);

        return response()->json($listing, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Listing::query()->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $listing = Listing::query()->findOrFail($id);

        if ($listing->user_id !== $request->user()->id && ! (bool) $request->user()->is_admin) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'type' => ['sometimes', 'in:lost,found'],
            'category' => ['sometimes', 'string', 'max:255'],
            'location' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'nullable', 'date'],
            'image' => ['sometimes', 'nullable', 'string'],
            'views' => ['sometimes', 'integer', 'min:0'],
        ]);

        $listing->fill($data);
        $listing->save();

        return $listing;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $listing = Listing::query()->findOrFail($id);

        if ($listing->user_id !== request()->user()->id && ! (bool) request()->user()->is_admin) {
            abort(403);
        }

        $listing->delete();

        return response()->json(['success' => true]);
    }
}
