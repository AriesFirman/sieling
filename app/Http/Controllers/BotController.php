<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Telegram;
use DB;

date_default_timezone_set('Asia/Makassar');

class BotController extends Controller
{
    const BOT_TOKEN = '5652350274:AAFM_MiVjuV0nH8rRRXZjsMYPXG0qcYZcYw';

    public static function update_webhook()
    {
        $url = 'https://a6a8-180-254-134-233.ngrok-free.app/api/sielling_bot';

        $response = Http::get('https://api.telegram.org/bot' . self::BOT_TOKEN . '/getWebhookInfo');

        $result = $response->json();

        if ($result['result']['pending_update_count'] !== 0) {
            $response = Http::post('https://api.telegram.org/bot' . self::BOT_TOKEN . '/setWebhook', [
                'url' => $url,
                'max_connections' => 100,
                'drop_pending_updates' => true
            ]);

            $result = $response->json();
            print_r($result);
        } else {
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

        if (substr($message, 0, 1) == '/') {
            if (strpos($message, "/start") === 0) {
                $hour = date('H', time());

                if ($hour > 6 && $hour <= 11) {
                    $saying = "Selamat Pagi";
                } else if ($hour > 11 && $hour <= 15) {
                    $saying = "Selamat Siang";
                } else if ($hour > 15 && $hour <= 17) {
                    $saying = "Selamat Sore";
                } else if ($hour > 17 && $hour <= 23) {
                    $saying = "Selamat Malam";
                } else {
                    $saying = "Why aren't you asleep?  Are you programming?";
                }

                $msg = "Hai $chat_title, $saying ...";

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            } elseif (strpos($message, "/chat_id") === 0) {
                $msg  = "Name   : <b>$chat_title</b>\n";
                $msg .= "Chat ID: <b>$chat_id</b>";

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            } elseif (strpos($message, "/whoami") === 0) {
                $data = DB::table('master_employee')->where('chat_id', $chat_id)->first();

                if ($data != null) {
                    $msg  = "NIK     : $data->nik\n";
                    $msg .= "Nama    : $data->name\n";
                    $msg .= "Chat ID : $data->chat_id\n";
                } else {
                    $msg = "who are u ?";
                }

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            } elseif (strpos($message, "/cek_absen_today") === 0) {
                $data = DB::table('master_employee')
                    ->where('chat_id', $chat_id)
                    ->first();

                if ($data != null) {
                    $absenData = DB::table('portal_rekap_absen')
                        ->where('nik', $data->nik)
                        ->orderBy('tanggal', 'asc') // Mengurutkan berdasarkan tanggal (ascending)
                        ->get();

                    if ($absenData->isNotEmpty()) {
                        $msg = "Data Absen untuk {$data->name} (NIK: {$data->nik}):\n\n";
                        foreach ($absenData as $absen) {
                            $msg .= "🔅 Tanggal: {$absen->tanggal}\n";
                            $msg .= "🔅 Jam Masuk: {$absen->jam_masuk}\n";
                            $msg .= "🔅 Jam Pulang: {$absen->jam_pulang}\n";
                            $msg .= "🔅 Keterangan: {$absen->keterangan}\n";
                            $msg .= "------------------------\n"; // Pemisah antar data absen
                        }
                    } else {
                        $msg = "Data absen hari ini tidak ditemukan untuk {$data->name} (NIK: {$data->nik}).";
                    }
                } else {
                    $msg = "Data karyawan tidak ditemukan.";
                }

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            } elseif (strpos($message, "/cek_payroll") === 0) {
                $data = DB::table('master_employee')
                    ->join('portal_payroll', 'master_employee.nik', '=', 'portal_payroll.nik') // Join tabel
                    ->where('master_employee.chat_id', $chat_id)
                    ->first();

                if ($data != null) {
                    // Hitung persentase kelengkapan absen
                    $jumlahMasuk = $data->m;
                    $jumlahHariKerja = $data->jhk;

                    if ($jumlahHariKerja > 0) { // Hindari pembagian dengan nol
                        $persentaseAbsen = $jumlahMasuk / $jumlahHariKerja * 100;
                        $persentaseAbsen = round($persentaseAbsen); // Bulatkan menjadi 2 angka di belakang koma
                    } else {
                        $persentaseAbsen = 0; // Jika jumlah hari kerja 0, persentase absen juga 0
                    }
                    $msg = "Data Payroll untuk {$data->name} (NIK: {$data->nik}):\n\n";
                    $msg .= "Sub Unit: {$data->unit}\n";
                    $msg .= "🎯 Jumlah Hari Kerja (tanpa weekend): {$data->jhk}\n";
                    $msg .= "🎯 Jumlah Masuk (termasuk libur) tanpa weekend: {$data->m}\n";
                    $msg .= "🎯 Jumlah Tidak Masuk: {$data->tm}\n";
                    $msg .= "🎯 Hari Kerja untuk payroll: {$data->hk}\n";
                    $msg .= "🎯 Kelengkapan Data Absen: {$persentaseAbsen}%\n"; // Menampilkan persentase

                } else {
                    $msg = "Data payroll tidak ditemukan untuk karyawan ini.";
                }

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            } elseif (strpos($message, "/cek_tacticalpro_absensi") === 0) {
                $data = DB::table('master_employee')
                    ->where('chat_id', $chat_id)
                    ->first();

                if ($data != null) {
                    $absenData = DB::table('tacticalpro_absensi')
                        ->where('nik', $data->nik)
                        ->get();

                    if ($absenData->isNotEmpty()) {
                        $msg = "Data Absensi TacticalPro untuk {$data->name} (NIK: {$data->nik}):\n\n";
                        foreach ($absenData as $absen) {
                            $msg .= "Tanggal: {$absen->absen_masuk}\n"; // Menggunakan kolom absen_masuk sebagai tanggal
                            $msg .= "Jam Masuk: " . date('H:i:s', strtotime($absen->absen_masuk)) . "\n"; // Mengambil jam dari absen_masuk
                            $msg .= "Jam Pulang: " . ($absen->absen_pulang != '0000-00-00 00:00:00' ? date('H:i:s', strtotime($absen->absen_pulang)) : '-') . "\n"; // Jika absen_pulang kosong, tampilkan '-'
                            $msg .= "Status: {$absen->status}\n";
                            $msg .= "------------------------\n";
                        }
                    } else {
                        $msg = "Data absensi TacticalPro hari ini tidak ditemukan untuk {$data->name} (NIK: {$data->nik}).";
                    }
                } else {
                    $msg = "Data karyawan tidak ditemukan.";
                }

                Telegram::sendMessageReply($chat_id, $msg, $messageID);
            } else {
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
