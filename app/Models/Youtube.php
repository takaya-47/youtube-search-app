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
     * @param  string|null $next_page_token
     * @return array
     */
    public static function search_by_keyword(string $key_word, string $next_page_token = null): array
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/search', [
            'key'             => env('YOUTUBE_API_KEY'),
            'part'            => 'snippet', // 必須パラメータ
            'maxResults'      => 2, // 結果セットとして返されるアイテムの最大数
            'order'           => 'date', // 並び順（作成日の新しい順）
            'pageToken'       => $next_page_token,
            'publishedAfter'  => '2022-02-01T00:00:00Z', // とりあえず２月中に投稿された動画を取得
            'publishedBefore' => '2022-02-28T00:00:00Z', // とりあえず２月中に投稿された動画を取得
            'q'               => $key_word,
            'type'            => 'video', // ビデオのみ検索する
        ]);
        $response_body = $response->getBody();
        $search_results = json_decode($response_body, true);
        return $search_results;
    }

    /**
     * 検索メソッドを再帰的に実行し、動画のチャンネルIDを返却します
     * @param  string $key_word
     * @return array
     */
    public static function recursive_search(string $key_word): array
    {
        // 検索結果を格納する配列
        $channel_ids = [];
        // 検索を実行
        $search_results = self::search_by_keyword($key_word);
        // 検索結果を格納
        for ($i = 0; $i < count($search_results['items']); $i++) {
            $channel_ids[] = $search_results['items'][$i]['snippet']['channelId'];
        }

        // 検索結果に次ページがあれば次ページの検索結果も取得する
        if ($search_results['nextPageToken']) {
            $next_page_token = $search_results['nextPageToken'];
            while ($next_page_token) {
                $search_results = self::search_by_keyword($key_word, $next_page_token);
                for ($i = 0; $i < count($search_results['items']); $i++) {
                    $channel_ids[] = $search_results['items'][$i]['snippet']['channelId'];
                }
                $next_page_token = $search_results['nextPageToken'];
            }
        }
        return $channel_ids;
    }

    /**
     * チャンネルごとにチャンネル登録者数を取得します
     * @param  string $keyword
     * @return array
     */
    public static function search_channels(string $key_word): array
    {
        // キーワード検索で取得した各動画のチャンネルIDを取得する
        $channel_ids = self::recursive_search($key_word);
        // チャンネルIDを使って、そのチャンネルのチャンネル登録者数を取得する
        $subscriber_count_list = [];
        for ($i = 0; $i < count($channel_ids); $i++) {
            $response = Http::get('https://www.googleapis.com/youtube/v3/channels', [
                'key'  => env('YOUTUBE_API_KEY'),
                'part' => 'statistics', // 必須パラメータ
                'id'   => $channel_ids[$i],
            ]);
            $response_body = $response->getBody();
            $search_results = json_decode($response_body, true);
            $subscriber_count_list[] = $search_results['items']['subscriberCount'];
        }
        // チャンネルごとのチャンネル登録者数を取得しているため、count()すると$channel_idsと同数になる想定
        return $subscriber_count_list;
    }
}
