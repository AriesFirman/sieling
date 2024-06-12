<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Telegram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

date_default_timezone_set('Asia/Makassar');

class BotController extends Controller
{
    const BOT_TOKEN = '5652350274:AAFM_MiVjuV0nH8rRRXZjsMYPXG0qcYZcYw';

    public static function update_webhook()
    {
        $url = 'https://example.ngrok-free.app/api/sielling_bot';

        $response = Http::get('https://api.telegram.org/bot' . self::BOT_TOKEN . '/getWebhookInfo');

        $result = $response->json();

        if ($result['result']['pending_update_count'] !== 0)
        {
            $response = Http::post('https://api.telegram.org/bot' . self::BOT_TOKEN . '/setWebhook', [
                'url' => $url,
                'max_connections' => 100,
                'drop_pending_updates' => true
            ]);

            $result = $response->json();
            print_r($result);
        }
        else
        {
            print_r("Pending Update Count is Zero \n");
        }
    }

    public static function sielling_bot(Request $request)
    {
        $apiBot = "https://api.telegram.org/bot" . self::BOT_TOKEN;
        $update = $request->all();

        $message = $update['message']['text'] ?? '';
        $messageID = $update['message']['message_id'] ?? '';
        $chat_id = $update['message']['chat']['id'] ?? '';

        $chat_title = $update['message']['chat']['title'] ?? $update['message']['chat']['first_name'] . ' ' . ($update['message']['chat']['last_name'] ?? '');

        $message = trim($message);

        if (substr($message, 0, 1) == '/')
        {
            if (strpos($message, "/start") === 0)
            {
                $hour = date('H', time());

                if ($hour > 6 && $hour <= 11)
                {
                    $saying = "Selamat Pagi";
                }
                else if ($hour > 11 && $hour <= 15)
                {
                    $saying = "Selamat Siang";
                }
                else if ($hour > 15 && $hour <= 17)
                {
                    $saying = "Selamat Sore";
                }
                else if ($hour > 17 && $hour <= 23)
                {
                    $saying = "Selamat Malam";
                }
                else
                {
                    $saying = "Why aren't you asleep?  Are you programming?";
                }

                $msg = "Hai $chat_title, $saying ...";

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            }
            elseif (strpos($message, "/chat_id") === 0)
            {
                $msg  = "Name   : <b>$chat_title</b>\n";
                $msg .= "Chat ID: <b>$chat_id</b>";

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            }
            elseif (strpos($message, "/example") === 0)
            {
                $parameter = str_replace(array("/example","/example ", " "), "", $message);

                $msg = "ini isi parameter setelah command\n\n<code>$parameter</code>";

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            }
            else
            {
                $msg = "Maaf perintah tidak tersedia ...";

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            }

            self::logCommandBot($chat_title, $chat_id, $message);
        }
    }

    public static function logCommandBot($chat_title, $chat_id, $message)
    {
        DB::table('log_command_bot')
        ->insert([
            'chat_title' => $chat_title,
            'chat_id'    => $chat_id,
            'message'    => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
