<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;

class ApiController extends Controller
{
    private $ApiToken;
    public function __construct(){
        $this->ApiToken = env("QUALTRICS_API_TOKEN");
    } 
    public function users(Request $request) 
    {
        echo $request->name;
        $usermodel = new User();
        $UserList = $usermodel->getUsersList(); 
        return view('test.index', compact("UserList"));
    }

    public function getInterviewResult() {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://yul1.qualtrics.com/API/v3/surveys/SV_9FtECtejcxTGgL4/responses/R_82PTLMIL1ctcBdn",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => " ",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-API-TOKEN: " . env('QUALTRICS_API_TOKEN')
            ],
        ]);

        $response = curl_exec($curl);
        var_dump($response);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    public function qualtrics() {
        $userName = Auth::user()->name;
        $userEmail = Auth::user()->email;
        $userRole = Auth::user()->role;
        $userPassword = Auth::user()->password;
        $companyTitle = Auth::user()->company_title;

        $table = 'qualtrics';
        $token = env('QUALTRICS_API_TOKEN');
        $survey_id = env('SURVEY_ID');

        $headers = [
            'X-API-TOKEN' => $token,
            'Content-Type' => 'application/json'
        ];

        $raw = [
            "format" => "json",
            "compress" => false
        ];

        try {
            $first_request = \Http::withHeaders($headers)
                ->post('https://sjc1.qualtrics.com/API/v3/surveys/'.$survey_id.'/export-responses', $raw);

            $first_data = $first_request->json();
            $progress_id = $first_data["result"]["progressId"];

            sleep(4);

            $second_request = \Http::withHeaders($headers)
                ->get('https://sjc1.qualtrics.com/API/v3/surveys/'.$survey_id.'/export-responses/'.$progress_id);

            $second_data = $second_request->json();

            $file_id = $second_data["result"]["fileId"];

            sleep(4);

            $third_request = \Http::withHeaders($headers)
                ->get('https://sjc1.qualtrics.com/API/v3/surveys/'.$survey_id.'/export-responses/'.$file_id.'/file');

            $third_data = $third_request->json();
            $response = json_encode($third_data["responses"]);

            $record = \DB::table($table)->where('id', 1)->first();
            $time = microtime(true) * 1000;
            if($record) {
                \DB::table($table)->update(['data' => $response, 'date' => $time]);
            } else {
                \DB::table($table)->insert(['data' => $response, 'date' => $time]);
            }

            sleep(4);

            if($userRole !== 0 && $userPassword !== 'user') {
                $model = new User();
                $qualtrics = $model->qualtricsFunc($userName, $userEmail, $userRole, $userPassword, $companyTitle);
                return response()->json(['status' => 200, 'message' => $qualtrics]);
            }

            return response()->json(['status' => 200, 'message' => ['data' => null]]);
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
