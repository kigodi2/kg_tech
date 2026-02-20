<?php

namespace App\Filament\Admin\Resources\GradingProfileResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class GradingRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'gradingRules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('grade')
                    ->required()
                    ->maxLength(5),
                Forms\Components\TextInput::make('min_marks')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                Forms\Components\TextInput::make('max_marks')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('grade')
            ->columns([
                Tables\Columns\TextColumn::make('grade')
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_marks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_marks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('min_marks');
    }
}
