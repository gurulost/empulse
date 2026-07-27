<?php

namespace App\Services;

use App\Models\SurveyAssignment;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SurveyAssignmentAccessService
{
    public function resolve(string $plainTextToken): SurveyAssignment
    {
        $assignment = SurveyAssignment::findByAccessToken($plainTextToken);
        if (! $assignment) {
            abort(404);
        }

        $assignment->loadMissing(['user', 'surveyVersion', 'surveyWave']);
        $this->assertEligible($assignment);

        return $assignment;
    }

    public function assertEligible(SurveyAssignment $assignment): void
    {
        if ($assignment->token_revoked_at) {
            throw new HttpException(410, 'This survey link has been revoked.');
        }

        if (! $assignment->token_expires_at || $assignment->token_expires_at->isPast()) {
            throw new HttpException(410, 'This survey link has expired.');
        }

        if ($assignment->status === 'completed' || $assignment->response()->exists()) {
            throw new HttpException(409, 'This survey has already been completed.');
        }

        if ($assignment->status !== 'pending') {
            throw new HttpException(410, 'This survey assignment is not active.');
        }

        $user = $assignment->user()->first();
        if (! $user
            || $user->status !== 'active'
            || ! $assignment->survey_version_id
            || ! $assignment->surveyVersion()->exists()) {
            throw new HttpException(409, 'This survey assignment is incomplete.');
        }

        if ($assignment->due_at && $assignment->due_at->isPast()) {
            throw new HttpException(410, 'This survey assignment is closed.');
        }

        $wave = $assignment->surveyWave()->first();
        if (! $wave) {
            return;
        }

        if (! in_array($wave->status, ['scheduled', 'active', 'processing'], true)) {
            throw new HttpException(410, 'This survey wave is not open.');
        }

        if ($wave->opens_at && $wave->opens_at->isFuture()) {
            throw new HttpException(410, 'This survey wave is not open yet.');
        }

        if ($wave->due_at && $wave->due_at->isPast()) {
            throw new HttpException(410, 'This survey wave is closed.');
        }
    }
}
