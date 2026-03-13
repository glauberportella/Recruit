<div class="space-y-4">
    @if($match)
        {{-- Overall Score --}}
        <div class="text-center">
            <div class="text-3xl font-bold @if($match->overall_score >= 80) text-green-600 @elseif($match->overall_score >= 60) text-blue-600 @elseif($match->overall_score >= 40) text-yellow-600 @else text-red-600 @endif">
                {{ number_format($match->overall_score, 1) }}%
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Compatibility Score</div>
        </div>

        {{-- Score Breakdown --}}
        <div class="grid grid-cols-2 gap-3 mt-4">
            @foreach([
                'Skills Match' => $match->skills_score,
                'Experience Fit' => $match->experience_score,
                'Education Match' => $match->education_score,
                'Salary Alignment' => $match->salary_score,
            ] as $label => $score)
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-lg font-semibold">{{ number_format($score, 1) }}%</div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                        <div class="h-2 rounded-full @if($score >= 80) bg-green-500 @elseif($score >= 60) bg-blue-500 @elseif($score >= 40) bg-yellow-500 @else bg-red-500 @endif" style="width: {{ min(100, $score) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Skill Gap --}}
        @if(!empty($match->skill_gap_analysis))
            <div class="mt-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Your Skills vs Requirements</h4>
                <div class="space-y-1">
                    @foreach($match->skill_gap_analysis as $gap)
                        <div class="flex items-center gap-2 text-sm">
                            @if(($gap['status'] ?? '') === 'match')
                                <span class="text-green-500">&#10003;</span>
                            @elseif(($gap['status'] ?? '') === 'partial')
                                <span class="text-yellow-500">&#9679;</span>
                            @else
                                <span class="text-red-500">&#10007;</span>
                            @endif
                            <span class="font-medium">{{ $gap['skill'] ?? 'N/A' }}</span>
                            <span class="text-gray-400">—</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $gap['notes'] ?? ucfirst($gap['status'] ?? 'unknown') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(!empty($match->matching_details['summary']))
            <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $match->matching_details['summary'] }}</p>
            </div>
        @endif

        <div class="text-xs text-gray-400 mt-3 text-center">
            Analysis from: {{ $match->matched_at->diffForHumans() }}
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-gray-400">No match details available.</p>
        </div>
    @endif
</div>
