<?php

namespace App\Filament\Admin\Resources\BackupResource\Pages;

use App\Filament\Admin\Resources\BackupResource;
use Filament\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewBackup extends ViewRecord
{
    protected static string $resource = BackupResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Backup Details')
                    ->schema([
                        TextInput::make('filename')
                            ->label('Filename')
                            ->disabled(),

                        TextInput::make('type')
                            ->label('Backup Type')
                            ->disabled()
                            ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),

                        TextInput::make('examYear.year_label')
                            ->label('Exam Year')
                            ->disabled(),

                        TextInput::make('size_bytes')
                            ->label('Size')
                            ->disabled()
                            ->formatStateUsing(fn($record) => $record->getSizeFormatted()),
                    ])->columns(2),

                Section::make('Integrity & Security')
                    ->schema([
                        TextInput::make('checksum_algo')
                            ->label('Checksum Algorithm')
                            ->disabled(),

                        TextInput::make('checksum')
                            ->label('Checksum (SHA256)')
                            ->disabled()
                            ->columnSpanFull(),

                        TextInput::make('verified')
                            ->label('Verification Status')
                            ->disabled()
                            ->formatStateUsing(fn($record) => $record->getStatusLabel()),

                        TextInput::make('verified_at')
                            ->label('Verified At')
                            ->disabled()
                            ->formatStateUsing(fn($record) => $record->verified_at?->format('Y-m-d H:i:s')),
                    ])->columns(2),

                Section::make('Metadata')
                    ->schema([
                        TextInput::make('admin.name')
                            ->label('Created By')
                            ->disabled(),

                        TextInput::make('created_at')
                            ->label('Created At')
                            ->disabled()
                            ->formatStateUsing(fn($record) => $record->created_at?->format('Y-m-d H:i:s')),

                        TextInput::make('notes')
                            ->label('Notes')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Manifest')
                    ->collapsed()
                    ->schema([
                        Textarea::make('manifest')
                            ->label('Manifest JSON')
                            ->disabled()
                            ->columnSpanFull()
                            ->formatStateUsing(fn($record) => json_encode($record->manifest, JSON_PRETTY_PRINT))
                            ->rows(15),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->icon('heroicon-o-arrow-down')
                ->action(fn() => BackupResource::downloadBackup($this->record))
                ->requiresConfirmation(),
            Actions\Action::make('restore')
                ->icon('heroicon-o-arrow-up')
                ->color('danger')
                ->url(fn() => route('backup.restore-form', ['id' => $this->record->id]))
                ->requiresConfirmation(),
        ];
    }
}
