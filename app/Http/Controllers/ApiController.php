<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

date_default_timezone_set('Asia/Makassar');

class ApiController extends Controller
{
    private function tacticalpro_token($user_id, $secret)
    {
        $token = null;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tacticalpro.co.id/api/token/get',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(['user_id' => $user_id, 'secret' => $secret]),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $result = json_decode($response);

        if (in_array($result->message, ['Success get token', 'Token was created']))
        {
            $token = $result->data->token;
        }

        return $token;
    }

    public static function tacticalpro_absensi($start_date, $end_date)
    {
        $that = new \App\Http\Controllers\ApiController();

        $token = $that->tacticalpro_token('915999', 'SWxiamlVYjFYRFVCY0xXeldybHVIQT09');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tacticalpro.co.id/api/presensi/lensa',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query(['start_date' => $start_date, 'end_date' => $end_date]),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $result = json_decode($response);

        if ($result->message == 'Data presensi found!')
        {
            $insert = [];

            foreach ($result->data as $v)
            {
                $date = date('Y-m-d', strtotime($v->absen_masuk));

                DB::table('tacticalpro_absensi')->where('nik', $v->nik)->whereDate('absen_masuk', $date)->delete();

                $insert[] = [
                    'reg'          => $v->reg,
                    'witel'        => $v->witel,
                    'wilayah'      => $v->wilayah,
                    'nik'          => $v->nik,
                    'name'         => $v->name,
                    'posisi'       => $v->posisi,
                    'status'       => $v->status,
                    'absen_masuk'  => $v->absen_masuk,
                    'absen_pulang' => $v->absen_pulang
                ];
            }

            $chunks = array_chunk($insert, 500);

            foreach ($chunks as $numb => $value)
            {
                DB::table('tacticalpro_absensi')->insert($value);

                print_r("saved page $numb and sleep (1)\n");

                sleep(1);
            }
        }
    }
}
