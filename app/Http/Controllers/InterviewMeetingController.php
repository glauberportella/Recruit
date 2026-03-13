<?php

namespace App\Http\Controllers;

use App\Filament\Enums\InterviewStatus;
use App\Models\Interview;
use App\Services\Jitsi\JitsiService;
use Illuminate\Http\Request;

class InterviewMeetingController extends Controller
{
    public function show(Interview $interview, Request $request, JitsiService $jitsi)
    {
        if ($interview->status === InterviewStatus::Cancelled) {
            abort(410, 'This interview has been cancelled.');
        }

        $user = auth('web')->user();
        $candidateUser = auth('candidate_web')->user();

        if (! $user && ! $candidateUser) {
            abort(403, 'You must be logged in to join this meeting.');
        }

        $isModerator = false;
        $meetingUser = [];

        if ($user) {
            $isModerator = true;
            $meetingUser = [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        } elseif ($candidateUser) {
            $meetingUser = [
                'id' => 'candidate-' . $candidateUser->id,
                'name' => $candidateUser->name,
                'email' => $candidateUser->email,
            ];
        }

        $token = $jitsi->generateToken($interview, $meetingUser, $isModerator);
        $config = $jitsi->getEmbedConfig($interview);

        return view('interview.meeting', [
            'interview' => $interview,
            'token' => $token,
            'config' => $config,
            'isModerator' => $isModerator,
        ]);
    }

    public function start(Interview $interview)
    {
        if (! auth('web')->check()) {
            abort(403);
        }

        $interview->update([
            'status' => InterviewStatus::InProgress->value,
            'started_at' => now(),
        ]);

        return response()->json(['status' => 'started']);
    }

    public function end(Interview $interview, Request $request)
    {
        if (! auth('web')->check()) {
            abort(403);
        }

        $interview->update([
            'status' => InterviewStatus::Completed->value,
            'ended_at' => now(),
        ]);

        return response()->json(['status' => 'ended']);
    }
}
