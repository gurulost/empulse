<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Mail\CoworkersMsg;
use App\Services\SurveyService;
use Illuminate\Support\Facades\Mail;

class SendLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected SurveyService $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        parent::__construct();
        $this->surveyService = $surveyService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::all();
        foreach ($users as $user) {
            $link = $this->surveyService->assignmentLink($user);
            if ($link) {
                $department = \DB::table('company_worker')->where('email', $user->email)->value('department');
                $supervisor = \DB::table('company_worker')->where('email', $user->email)->value('supervisor');
                Mail::to($user->email)->send(new CoworkersMsg(
                    $user->name,
                    $department,
                    $supervisor,
                    $user->company_title,
                    $link
                ));
            }
        }

        return 0;
    }
}
