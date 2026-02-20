<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DistrictCouncilResource\Pages;
use App\Models\DistrictCouncil;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DistrictCouncilResource extends Resource
{
    protected static ?string $model = DistrictCouncil::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Geographic';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('District Council Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('region_id')
                            ->relationship('region', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Region')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('schools_count')
                    ->label('Schools')
                    ->counts('schools')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('region_id')
                    ->relationship('region', 'name'),
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
            ->defaultSort('name')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistrictCouncils::route('/'),
            'create' => Pages\CreateDistrictCouncil::route('/create'),
            'view' => Pages\ViewDistrictCouncil::route('/{record}'),
            'edit' => Pages\EditDistrictCouncil::route('/{record}/edit'),
        ];
    }
}
