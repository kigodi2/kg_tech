<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FinalGradeResource\Pages;
use App\Models\FinalGrade;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FinalGradeResource extends Resource
{
    protected static ?string $model = FinalGrade::class;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    protected static ?string $slug = 'final-grades';

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Evaluations';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grade Information')
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

                        Forms\Components\Select::make('grading_profile_id')
                            ->label('Grading Profile')
                            ->relationship('gradingProfile', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Final Results')
                    ->schema([
                        Forms\Components\TextInput::make('overall_grade')
                            ->label('Overall Grade')
                            ->maxLength(2),

                        Forms\Components\TextInput::make('total_marks')
                            ->label('Total Marks')
                            ->numeric(),

                        Forms\Components\TextInput::make('grade_points')
                            ->label('Grade Points')
                            ->numeric(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
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

                Tables\Columns\TextColumn::make('gradingProfile.name')
                    ->label('Grading Profile')
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

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),

                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Locked'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
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
            'index' => Pages\ListFinalGrades::route('/'),
            'view' => Pages\ViewFinalGrade::route('/{record}'),
        ];
    }
}
