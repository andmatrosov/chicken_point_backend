<?php

namespace App\Filament\Resources\UserPrizes\Schemas;

use App\Enums\UserPrizeStatus;
use App\Models\UserPrize;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserPrizeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assignment details')
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.email')
                            ->label('User'),
                        TextEntry::make('prize.title')
                            ->label('Prize')
                            ->formatStateUsing(fn (?string $state, UserPrize $record): ?string => $record->status === UserPrizeStatus::CANCELED ? null : $state)
                            ->placeholder('No active prize'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('rank_at_assignment')
                            ->placeholder('Not set'),
                        IconEntry::make('assigned_manually')
                            ->boolean(),
                        TextEntry::make('assignedBy.email')
                            ->label('Assigned by')
                            ->placeholder('System'),
                        TextEntry::make('assigned_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
