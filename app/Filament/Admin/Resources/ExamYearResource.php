<?php

namespace App\Filament\Admin\Resources;

use App\Models\ExamYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ExamYearResource extends Resource
{
    protected static ?string $model = ExamYear::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Exam Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Exam Year Details')
                    ->description('Configure exam year settings')
                    ->schema([
                        Forms\Components\TextInput::make('year_label')
                            ->label('Year Label')
                            ->required()
                            ->placeholder('e.g., 2024')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Only one active year allowed at a time'),

                        Forms\Components\Toggle::make('is_locked')
                            ->label('Locked')
                            ->helperText('Locked years are read-only across the system')
                            ->disabled(fn (?ExamYear $record) => $record?->is_locked === true),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->helperText('Publishing locks the year'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year_label')
                    ->label('Year')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn (ExamYear $record): string => 
                        $record->is_locked ? 'Locked' : ($record->is_active ? 'Active' : 'Inactive')
                    )
                    ->colors([
                        'danger' => 'Locked',
                        'success' => 'Active',
                        'secondary' => 'Inactive',
                    ])
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Locked'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (ExamYear $record) => !$record->is_locked),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ExamYear $record) => !$record->is_active && !$record->is_locked)
                    ->action(function (ExamYear $record) {
                        $record->activate();
                    }),
                Tables\Actions\Action::make('publish')
                    ->label('Publish & Lock')
                    ->icon('heroicon-m-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ExamYear $record) => !$record->is_locked && !$record->isPublished())
                    ->action(function (ExamYear $record) {
                        $record->publish();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (ExamYear $record) => !$record->is_locked && !$record->isPublished()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Overview')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('year_label')
                            ->label('Year'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->getStateUsing(fn (ExamYear $record): string => 
                                $record->is_locked ? 'Locked' : ($record->is_active ? 'Active' : 'Inactive')
                            )
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'Locked' => 'danger',
                                'Active' => 'success',
                                'Inactive' => 'secondary',
                            }),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ]),

                Section::make('Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('is_active')
                            ->label('Active')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        TextEntry::make('is_locked')
                            ->label('Locked')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        TextEntry::make('published_at')
                            ->label('Published At')
                            ->dateTime(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\ExamYearResource\Pages\ListExamYears::route('/'),
            'create' => \App\Filament\Admin\Resources\ExamYearResource\Pages\CreateExamYear::route('/create'),
            'view' => \App\Filament\Admin\Resources\ExamYearResource\Pages\ViewExamYear::route('/{record}'),
            'edit' => \App\Filament\Admin\Resources\ExamYearResource\Pages\EditExamYear::route('/{record}/edit'),
        ];
    }
}
