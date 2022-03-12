<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Csv extends Model
{
    use HasFactory;

    const PERCENTAGE = 30;

    /**
     * CSVファイルに動画情報を書き込みます
     * @param  string $key_word
     * @return void
     */
    public static function create_csv_file(string $key_word): void
    {
        // チャンネル登録者数のリスト
        $subscriber_count_list = Youtube::search_channels($key_word);
        $videos_info_list = Youtube::search_videos($key_word);
        // 動画投稿日のリスト
        $publishedAt_list = $videos_info_list['publishedAt'];
        // 動画タイトルのリスト
        $title_list = $videos_info_list['title'];
        // 動画再生回数のリスト
        $view_count_list = $videos_info_list['viewCount'];

        // CSVのヘッダー情報
        $csv_header = ['投稿日', 'タイトル', '再生回数', 'チャンネル登録者数', '再生回数/チャンネル登録者数（%）'];
        // csvファイルを書き込みモードで開く
        $file = fopen('/Users/terashimatakaya/myStudy/useApi/laravel/youtube-search-app/public/searchresults.csv', 'w');
        // 先にヘッダー情報を書き込む
        fputcsv($file, $csv_header);
        // その他の情報を１行ずつ書き込む
        $repeat_count = count($subscriber_count_list);
        for ($i = 0; $i < $repeat_count; $i++) {
            // チャンネル登録者数がゼロだとDivision by zeroのPHPエラーが発生するのでifで防止する
            if ($subscriber_count_list[$i] == 0) {
                continue;
            }
            // 再生回数/チャンネル登録者数が30%未満だったらその動画の情報は書き込みをスキップする
            $percentage = floor(($view_count_list[$i] / $subscriber_count_list[$i]) * 100);
            if ($percentage < self::PERCENTAGE) {
                continue;
            }
            // 条件を満たす動画についてはCSVに情報を書き込む
            $row = [];
            array_push($row, $publishedAt_list[$i], $title_list[$i], $view_count_list[$i], $subscriber_count_list[$i], $percentage);
            fputcsv($file, $row);
        }
        // ファイルを閉じる
        fclose($file);
    }
}
