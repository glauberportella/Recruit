<x-filament-panels::page>
    @php
        $interview = $this->record;
        $jobCandidate = $interview->jobCandidate;
        $candidate = $jobCandidate?->candidateProfile;
        $job = $jobCandidate?->job;
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Interview Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Interview Details Card --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-video-camera class="w-5 h-5 text-primary-600" />
                        {{ $interview->title }}
                    </div>
                </x-slot>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Candidate</div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ $candidate?->FirstName }} {{ $candidate?->LastName }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Job Opening</div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ $job?->JobTitle }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled By</div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ $interview->scheduler?->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</div>
                        <div class="mt-1">
                            <x-filament::badge :color="$interview->status->getColor()">
                                {{ $interview->status->value }}
                            </x-filament::badge>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled</div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ $interview->scheduled_at->format('M d, Y \a\t H:i') }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ $interview->duration_minutes }} minutes</div>
                    </div>
                </div>

                @if($interview->description)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</div>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ $interview->description }}</div>
                    </div>
                @endif
            </x-filament::section>

            {{-- Feedback & Notes --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-primary-600" />
                        Feedback & Notes
                    </div>
                </x-slot>

                <div class="space-y-4">
                    @if($interview->rating)
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Rating</div>
                            <div class="mt-1 text-2xl">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $interview->rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}">★</span>
                                @endfor
                                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">({{ $interview->rating }}/5)</span>
                            </div>
                        </div>
                    @endif

                    @if($interview->interviewer_notes)
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Interviewer Notes</div>
                            <div class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap rounded-lg bg-gray-50 dark:bg-gray-800 p-3">{{ $interview->interviewer_notes }}</div>
                        </div>
                    @endif

                    @if(!$interview->rating && !$interview->interviewer_notes)
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-document-text class="w-8 h-8 mx-auto mb-2" />
                            <p class="text-sm">No feedback submitted yet. Edit this interview to add notes and rating.</p>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>

        {{-- Right Column - Timeline & Actions --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            @if($interview->isJoinable())
                <x-filament::section>
                    <x-slot name="heading">Quick Actions</x-slot>
                    <div class="space-y-3">
                        <a href="{{ route('interview.meeting', $interview) }}" target="_blank"
                           class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 transition">
                            <x-heroicon-o-video-camera class="w-4 h-4" />
                            Join Meeting
                        </a>
                    </div>
                </x-filament::section>
            @endif

            {{-- Meeting Room --}}
            <x-filament::section>
                <x-slot name="heading">Meeting Room</x-slot>
                <div class="space-y-2">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Room ID</div>
                    <code class="block text-xs bg-gray-100 dark:bg-gray-800 rounded p-2 break-all">{{ $interview->meeting_room }}</code>
                </div>
            </x-filament::section>

            {{-- Interview Timeline --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-4 h-4 text-primary-600" />
                        Timeline
                    </div>
                </x-slot>
                <div class="space-y-4">
                    {{-- Created --}}
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                            <div class="w-0.5 flex-1 bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        <div class="pb-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Created</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $interview->created_at->format('M d, Y H:i') }}</div>
                        </div>
                    </div>

                    {{-- Scheduled --}}
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-2.5 h-2.5 rounded-full {{ $interview->scheduled_at->isPast() ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
                            <div class="w-0.5 flex-1 bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        <div class="pb-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Scheduled</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $interview->scheduled_at->format('M d, Y H:i') }}</div>
                        </div>
                    </div>

                    @if($interview->started_at)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                                <div class="w-0.5 flex-1 bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                            <div class="pb-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Started</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $interview->started_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                    @endif

                    @if($interview->ended_at)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Completed</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $interview->ended_at->format('M d, Y H:i') }}</div>
                                @if($interview->started_at)
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        Duration: {{ $interview->started_at->diffForHumans($interview->ended_at, true) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @elseif(!$interview->started_at)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-400 dark:text-gray-500">Awaiting start...</div>
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
