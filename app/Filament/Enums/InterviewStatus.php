<?php

namespace App\Filament\Enums;

use Filament\Support\Contracts\HasLabel;

enum InterviewStatus: string implements HasLabel
{
    case Scheduled = 'Scheduled';
    case Confirmed = 'Confirmed';
    case InProgress = 'In Progress';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
    case NoShow = 'No Show';
    case Rescheduled = 'Rescheduled';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Confirmed => 'primary',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::NoShow => 'gray',
            self::Rescheduled => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Scheduled => 'heroicon-o-calendar',
            self::Confirmed => 'heroicon-o-check-circle',
            self::InProgress => 'heroicon-o-video-camera',
            self::Completed => 'heroicon-o-check-badge',
            self::Cancelled => 'heroicon-o-x-circle',
            self::NoShow => 'heroicon-o-user-minus',
            self::Rescheduled => 'heroicon-o-arrow-path',
        };
    }
}
