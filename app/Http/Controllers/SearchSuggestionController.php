<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    public function genres(Request $request): JsonResponse
    {
        return response()->json([
            'items' => $this->suggestions(
                modelClass: Genre::class,
                keyword: $request->string('q')->value(),
            ),
        ]);
    }

    public function tags(Request $request): JsonResponse
    {
        return response()->json([
            'items' => $this->suggestions(
                modelClass: Tag::class,
                keyword: $request->string('q')->value(),
            ),
        ]);
    }

    /**
     * @return list<string>
     */
    private function suggestions(string $modelClass, string $keyword): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [];
        }

        try {
            /** @var \Illuminate\Database\Eloquent\Model $modelClass */
            return $modelClass::query()
                ->where('name', 'like', "%{$keyword}%")
                ->orderBy('name')
                ->limit(8)
                ->pluck('name')
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
