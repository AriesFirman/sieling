<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

date_default_timezone_set('Asia/Makassar');

class Telegram extends Model
{
    use HasFactory;

    const BOT_TOKEN = '5652350274:AAFM_MiVjuV0nH8rRRXZjsMYPXG0qcYZcYw';

    public static function sendMessage($chat_id, $message)
    {
        $text = urlencode($message);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://api.telegram.org/bot" . self::BOT_TOKEN . "/sendmessage?chat_id=$chat_id&text=$text&parse_mode=HTML",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendMessageReply($chat_id, $message, $message_id)
    {
        $text = urlencode($message);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://api.telegram.org/bot" . self::BOT_TOKEN . "/sendmessage?chat_id=$chat_id&text=$text&parse_mode=HTML&reply_to_message_id=$message_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendPhoto($chat_id, $caption, $photo)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://api.telegram.org/bot" . self::BOT_TOKEN . "/sendPhoto",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => [
                "chat_id"    => $chat_id,
                "parse_mode" => "HTML",
                "caption"    => $caption,
                "photo"      => new \CURLFILE($photo)
            ],
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}
