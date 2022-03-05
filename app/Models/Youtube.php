<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Youtube extends Model
{
    use HasFactory;

    public static function search_by_keyword($keyword)
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/search', [
            'part' => 'snippet',
            'order' => 'date',
            'type' => 'video',
            'q' => $keyword,
            'key' => env('YOUTUBE_API_KEY'),
        ]);

        $posts = $response->getBody();
        $results = json_decode($posts, true);
        var_dump($results);
    }
}
