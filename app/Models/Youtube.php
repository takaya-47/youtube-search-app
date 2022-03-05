<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Youtube extends Model
{
    use HasFactory;

    /**
     * 検索ワードをもとに動画を検索します
     * @param  string $key_word
     * @param  string $next_page_token
     * @return array
     */
    public static function search_by_keyword(string $key_word, string $next_page_token = null): array
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/search', [
            'key' => env('YOUTUBE_API_KEY'),
            'part' => 'snippet', // 必須パラメータ
            'maxResults' => 2, // 結果セットとして返されるアイテムの最大数
            'order' => 'date', // 並び順（作成日の新しい順）
            'pageToken' => $next_page_token,
            'publishedAfter' => '2022-02-01T00:00:00Z', // とりあえず２月中に投稿された動画を取得
            'publishedBefore' => '2022-02-28T00:00:00Z', // とりあえず２月中に投稿された動画を取得
            'q' => $key_word,
            'type' => 'video', // ビデオのみ検索する
        ]);
        $response_body = $response->getBody();
        $search_results = json_decode($response_body, true);
        return $search_results;
    }

    /**
     * 検索メソッドを再帰的に実行します
     * @param  string $key_word
     * @return array
     */
    public static function recursive_search(string $key_word): array
    {
        // 検索結果を格納する配列
        $result_items = [];
        // 検索を実行
        $search_results = self::search_by_keyword($key_word);
        // 検索結果を格納
        for ($i = 0; $i < count($search_results['items']); $i++) {
            $result_items[] = $search_results['items'][$i];
        }

        $next_page_token = $search_results['nextPageToken'];
        while ($next_page_token) {
            $search_results = self::search_by_keyword($key_word, $next_page_token);
            for ($i = 0; $i < count($search_results['items']); $i++) {
                $result_items[] = $search_results['items'][$i];
            }
            $next_page_token = $search_results['nextPageToken'];
        }
        return $result_items;
    }
}
