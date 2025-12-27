<?php

namespace App\Filament\Resources\ApiKeys\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class ApiKeyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Key Name')
                    ->helperText('A descriptive name for this API key'),

                Forms\Components\CheckboxList::make('permissions')
                    ->options([
                        'gdpr.delete' => 'GDPR Delete Users',
                        'users.read' => 'Read User Data',
                        'users.update' => 'Update Users',
                        'courses.read' => 'Read Courses',
                        'training-logs.read' => 'Read Training Logs',
                        'endorsements.read' => 'Read Endorsements',
                    ])
                    ->columns(2)
                    ->required()
                    ->label('Permissions')
                    ->helperText('Select which permissions this API key should have'),

                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('Active'),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->helperText('Leave empty for no expiration'),
            ]);
    }
}