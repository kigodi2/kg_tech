<?php

namespace App\Filament\Admin\Resources;

use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Academic';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('category')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Exam Configuration')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('exam_type_id')
                            ->relationship('examType', 'code')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('written_papers')
                            ->numeric()
                            ->default(1),
                        Forms\Components\TextInput::make('max_marks')
                            ->numeric()
                            ->default(100),
                    ]),

                Forms\Components\Section::make('Components')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Toggle::make('has_practical')
                            ->default(false),
                        Forms\Components\Toggle::make('has_project')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('examType.code')
                    ->label('Exam Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('written_papers')
                    ->label('Papers'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_type_id')
                    ->relationship('examType', 'code'),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\SubjectResource\Pages\ListSubjects::route('/'),
            'create' => \App\Filament\Admin\Resources\SubjectResource\Pages\CreateSubject::route('/create'),
            'view' => \App\Filament\Admin\Resources\SubjectResource\Pages\ViewSubject::route('/{record}'),
            'edit' => \App\Filament\Admin\Resources\SubjectResource\Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
