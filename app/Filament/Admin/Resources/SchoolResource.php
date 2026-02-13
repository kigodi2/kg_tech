<?php

namespace App\Filament\Admin\Resources;

use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Geographic';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('school_type')
                            ->options([
                                School::TYPE_PRIMARY => 'Primary',
                                School::TYPE_SECONDARY => 'Secondary',
                                School::TYPE_BOTH => 'Both',
                            ])
                            ->default(School::TYPE_SECONDARY)
                            ->helperText('PSLE schools must be PRIMARY or BOTH. CSEE/ACSEE schools must be SECONDARY or BOTH.'),
                    ]),

                Forms\Components\Section::make('Location')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('region_id')
                            ->relationship('region', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('district_id')
                            ->relationship('district', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('principal_name')
                            ->label('Principal Name')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
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
                Tables\Columns\TextColumn::make('district.name')
                    ->label('District')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Region')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('school_type')
                    ->badge()
                    ->colors([
                        'info' => School::TYPE_SECONDARY,
                        'warning' => School::TYPE_PRIMARY,
                        'success' => School::TYPE_BOTH,
                    ]),
                Tables\Columns\TextColumn::make('candidates_count')
                    ->label('Candidates')
                    ->counts('candidates')
                    ->alignment('center'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('district_id')
                    ->relationship('district', 'name'),
                Tables\Filters\SelectFilter::make('region_id')
                    ->relationship('region', 'name'),
                Tables\Filters\SelectFilter::make('school_type')
                    ->options([
                        School::TYPE_PRIMARY => 'Primary',
                        School::TYPE_SECONDARY => 'Secondary',
                        School::TYPE_BOTH => 'Both',
                    ]),
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
            'index' => \App\Filament\Admin\Resources\SchoolResource\Pages\ListSchools::route('/'),
            'create' => \App\Filament\Admin\Resources\SchoolResource\Pages\CreateSchool::route('/create'),
            'view' => \App\Filament\Admin\Resources\SchoolResource\Pages\ViewSchool::route('/{record}'),
            'edit' => \App\Filament\Admin\Resources\SchoolResource\Pages\EditSchool::route('/{record}/edit'),
        ];
    }
}
