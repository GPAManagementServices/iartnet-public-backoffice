<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $query = Faq::query()
            ->with(['opengraphPicture'])
            ->orderByDesc('updated_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function show(int $id)
    {
        return Faq::query()
            ->with(['opengraphPicture'])
            ->findOrFail($id);
    }

    public function showBySlug(string $slug)
    {
        // Qui decidiamo la lingua: se passi ?lang=it|en usa quella, altrimenti prova en poi it
        $lang = request()->query('lang');

        $query = Faq::query()->with(['opengraphPicture']);

        if ($lang === 'it') {
            return $query->where('slug_it', $slug)->firstOrFail();
        }

        if ($lang === 'en') {
            return $query->where('slug_en', $slug)->firstOrFail();
        }

        return $query->where('slug_en', $slug)
            ->orWhere('slug_it', $slug)
            ->firstOrFail();
    }
}
