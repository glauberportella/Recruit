<div class="space-y-4">
    @if($match)
        {{-- Overall Score --}}
        <div class="text-center">
            <div class="text-3xl font-bold @if($match->overall_score >= 80) text-green-600 @elseif($match->overall_score >= 60) text-blue-600 @elseif($match->overall_score >= 40) text-yellow-600 @else text-red-600 @endif">
                {{ number_format($match->overall_score, 1) }}%
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Overall Match Score</div>
        </div>

        {{-- Score Breakdown --}}
        <div class="grid grid-cols-2 gap-3 mt-4">
            @foreach([
                'Skills' => $match->skills_score,
                'Experience' => $match->experience_score,
                'Education' => $match->education_score,
                'Salary' => $match->salary_score,
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

        {{-- Skill Gap Analysis --}}
        @if(!empty($match->skill_gap_analysis))
            <div class="mt-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Skill Gap Analysis</h4>
                <div class="space-y-1">
                    @foreach($match->skill_gap_analysis as $gap)
                        <div class="flex items-center gap-2 text-sm">
                            @if(($gap['status'] ?? '') === 'match')
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            @elseif(($gap['status'] ?? '') === 'partial')
                                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                            @else
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            @endif
                            <span class="font-medium">{{ $gap['skill'] ?? 'N/A' }}</span>
                            <span class="text-gray-400">—</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $gap['notes'] ?? ucfirst($gap['status'] ?? 'unknown') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Strengths & Weaknesses --}}
        @if(!empty($match->matching_details))
            @if(!empty($match->matching_details['strengths']))
                <div class="mt-4">
                    <h4 class="text-sm font-semibold text-green-700 dark:text-green-400 mb-1">Strengths</h4>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-0.5">
                        @foreach($match->matching_details['strengths'] as $strength)
                            <li>{{ $strength }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($match->matching_details['weaknesses']))
                <div class="mt-3">
                    <h4 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-1">Areas of Concern</h4>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-0.5">
                        @foreach($match->matching_details['weaknesses'] as $weakness)
                            <li>{{ $weakness }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($match->matching_details['summary']))
                <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-1">AI Summary</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $match->matching_details['summary'] }}</p>
                </div>
            @endif
        @endif

        <div class="text-xs text-gray-400 mt-3 text-center">
            Last analyzed: {{ $match->matched_at->diffForHumans() }}
        </div>
    @else
        <div class="text-center py-8">
            <x-heroicon-o-cpu-chip class="w-12 h-12 mx-auto text-gray-400"/>
            <p class="mt-2 text-gray-500 dark:text-gray-400">No AI match analysis available for this candidate.</p>
            <p class="text-sm text-gray-400">Click the "AI Match" button to run the analysis.</p>
        </div>
    @endif
</div>
