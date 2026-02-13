<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CandidateResultResource\Pages;
use App\Models\CandidateResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CandidateResultResource extends Resource
{
    protected static ?string $model = CandidateResult::class;

    protected static ?string $slug = 'candidate-results';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Results';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Result Information')
                    ->schema([
                        Forms\Components\Select::make('candidate_id')
                            ->label('Candidate')
                            ->relationship('candidate', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('exam_type_id')
                            ->label('Exam Type')
                            ->relationship('examType', 'code')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('year')
                            ->label('Year')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('overall_grade')
                            ->label('Overall Grade')
                            ->maxLength(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Grades & Points')
                    ->schema([
                        Forms\Components\TextInput::make('total_marks')
                            ->label('Total Marks')
                            ->numeric(),

                        Forms\Components\TextInput::make('grade_points')
                            ->label('Grade Points')
                            ->numeric(),

                        Forms\Components\TextInput::make('division')
                            ->label('Division')
                            ->maxLength(10),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Verified')
                            ->default(false),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->default(false),

                        Forms\Components\Toggle::make('is_locked')
                            ->label('Locked')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Candidate')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('examType.code')
                    ->label('Exam Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('overall_grade')
                    ->label('Grade')
                    ->alignment('center')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_marks')
                    ->label('Total Marks')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade_points')
                    ->label('Grade Points')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('division')
                    ->label('Division')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_type_id')
                    ->label('Exam Type')
                    ->relationship('examType', 'code'),

                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified'),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),

                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Locked'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCandidateResults::route('/'),
            'create' => Pages\CreateCandidateResult::route('/create'),
            'view' => Pages\ViewCandidateResult::route('/{record}'),
            'edit' => Pages\EditCandidateResult::route('/{record}/edit'),
        ];
    }
}
