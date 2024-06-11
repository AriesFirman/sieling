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
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
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

            DB::table('cookie_apps')
            ->where('apps', 'tacticalpro')
            ->update([
                'token' => $token
            ]);
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
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
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

    public static function portal_report_hr($user, $pass, $unit, $start_date, $end_date)
    {
        $unit = $unit ? 109 : 0;
        $start_date = $start_date ? date('Y-m-d', strtotime('-30 days')) : null;
        $end_date = $end_date ? date('Y-m-d') : null;

        $curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://apps.telkomakses.co.id/portal/login.php',
			CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER => true,
			CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CUSTOMREQUEST => 'GET',
		));
		$response = curl_exec($curl);
		$header            = curl_getinfo($curl);
		$header_content    = substr($response, 0, $header['header_size']);
		trim(str_replace($header_content, '', $response));
		$pattern           = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
		preg_match_all($pattern, $header_content, $matches);
		$cookiesOut        = [];
		$header['headers'] = $header_content;
		$header['cookies'] = $cookiesOut;
		$cookiesOut = implode("; ", $matches['cookie']);

        DB::table('cookie_apps')
        ->where('apps', 'portal')
        ->update([
            'cookies' => $cookiesOut
        ]);

		libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $answer = $dom->getElementsByTagName("img")->item(1)->getAttribute('src');
		$id_captcha = $dom->getElementById("id_captcha")->getAttribute("value");
		$get_captcha_answer = DB::table('portal_captcha')->where('id_captcha', $id_captcha)->first();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://apps.telkomakses.co.id/portal/proses_login.php",
			CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "nik=$user&password=$pass&id_captcha=$id_captcha&answer=$get_captcha_answer->answer&submit=",
			CURLOPT_HTTPHEADER => array(
			  "Content-Type: application/x-www-form-urlencoded",
			  "Cookie: ".$cookiesOut
			),
		));
		curl_exec($curl);

		$otp = 0;
        print_r("\ninput otp:\n");
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);

        if(trim($line) == 'cancel')
        {
            print_r("ABORTING!\n");
            exit;
        }

        $otp = trim($line);
        fclose($handle);
        print_r("response $otp\n\n");

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://apps.telkomakses.co.id/portal/proses_otp.php",
			CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "otp=$otp&submit=",
			CURLOPT_HTTPHEADER => array(
			  "Content-Type: application/x-www-form-urlencoded",
			  "Cookie: ".$cookiesOut
			),
		));
		curl_exec($curl);

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://apps.telkomakses.co.id/portal/home.php",
			CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "nik=$user&password=$pass&submit=",
			CURLOPT_HTTPHEADER => array(
			  "Content-Type: application/x-www-form-urlencoded",
			  "Cookie: ".$cookiesOut
			),
		));
		$response = curl_exec($curl);
		libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));

        $linkElements = $dom->getElementsByTagName('a');
		$targetKeyword = 'Appraisal';
		$url_present = null;

		foreach ($linkElements as $linkElement)
		{
			if (stripos($linkElement->textContent, $targetKeyword) !== false)
			{
				$url_present = str_replace('https://apps.telkomakses.co.id/appraisal?code=', '', $linkElement->getAttribute('href'));
                break;
			}
		}

		if ($url_present !== null)
		{
			print_r("Isi value dari href: $url_present\n");
		}
		else
		{
			print_r("Tidak ditemukan elemen dengan kata kunci '$targetKeyword'\n");
		}

        curl_setopt_array($curl, array(
			CURLOPT_URL => "https://apps.telkomakses.co.id/portal/save_user_log.php",
			CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "aplikasi=ABSEN&nik=$user",
			CURLOPT_HTTPHEADER => array(
			  "Content-Type: application/x-www-form-urlencoded",
			  "Cookie: ".$cookiesOut
			),
		));
		curl_exec($curl);

        curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://apps.telkomakses.co.id/absen/?code='.$url_present,
			CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Cookie: $cookiesOut"
			),
		));
		$response = curl_exec($curl);

        curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://apps.telkomakses.co.id/absen/report_payroll_excel.php?unit='.$unit.'&tgl_awal='.$start_date.'&tgl_akhir='.$end_date,
			CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Cookie: $cookiesOut"
			),
		));
		$response = curl_exec($curl);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(trim($response));
        $table = $dom->getElementById('dataUserTables');
        $rows = $table->getElementsByTagName('tr');

        DB::table('portal_payroll')->truncate();

        foreach ($rows as $row)
        {
            $cells = $row->getElementsByTagName('td');

            if ($cells->length > 0)
            {
                $insert[] = [
                    'nik'        => $cells->item(1)->nodeValue,
                    'nama'       => $cells->item(2)->nodeValue,
                    'unit'       => $cells->item(3)->nodeValue,
                    'sub_unit'   => $cells->item(4)->nodeValue,
                    'jhk'        => $cells->item(5)->nodeValue,
                    'm'          => $cells->item(6)->nodeValue,
                    'tm'         => $cells->item(7)->nodeValue,
                    'hk'         => $cells->item(8)->nodeValue,
                    'start_date' => $start_date,
                    'end_date'   => $end_date
                ];
            }
        }

        foreach (array_chunk($insert, 500) as $numb => $item)
        {
            DB::table('portal_payroll')->insert($item);

            print_r("saved page $numb and sleep (1)\n");

            sleep(1);
        }
    }
}
