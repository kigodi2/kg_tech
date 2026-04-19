<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DashboardAnnouncementResource\Pages;
use App\Models\DashboardAnnouncement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DashboardAnnouncementResource extends Resource
{
    protected static ?string $model = DashboardAnnouncement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Events & News';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        DashboardAnnouncement::TYPE_EVENT => 'Event',
                        DashboardAnnouncement::TYPE_NEWS => 'News',
                    ]),

                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\DatePicker::make('publish_date')
                    ->required(),

                Forms\Components\TextInput::make('link_url')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://...')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->colors([
                        'info' => DashboardAnnouncement::TYPE_EVENT,
                        'success' => DashboardAnnouncement::TYPE_NEWS,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('publish_date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        DashboardAnnouncement::TYPE_EVENT => 'Event',
                        DashboardAnnouncement::TYPE_NEWS => 'News',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            ->defaultSort('publish_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDashboardAnnouncements::route('/'),
            'create' => Pages\CreateDashboardAnnouncement::route('/create'),
            'edit' => Pages\EditDashboardAnnouncement::route('/{record}/edit'),
        ];
    }
}
