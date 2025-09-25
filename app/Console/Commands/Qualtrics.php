<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Qualtrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qualtrics:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to get actuall data from qualtrics survey.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
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


            print('Success');
        } catch(\Exception $e) {
            print('Error: '.$e->getMessage());
        }
    }
}
