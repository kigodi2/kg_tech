<?php

namespace App\Filament\Admin\Resources;

use App\Models\District;
use App\Models\Region;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Models\UserScope;
use App\Services\PasswordGenerationService;
use App\Filament\Admin\Resources\UserResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identity')
                    ->description('User name and contact information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignorable: fn($record) => $record),

                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->nullable(),
                    ])->columns(2),

                Section::make('Authorization')
                    ->description('Role and scope assignment')
                    ->schema([
                        Select::make('role_id')
                            ->label('Role')
                            ->relationship('role', 'name')
                            ->options(Role::pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('scope_type', null)),

                        Select::make('scope_type')
                            ->label('Scope Type')
                            ->options([
                                UserScope::SCOPE_REGION => 'Region',
                                UserScope::SCOPE_DISTRICT => 'District',
                                UserScope::SCOPE_SCHOOL => 'School',
                            ])
                            ->visible(fn(Forms\Get $get) => static::isScopeRequiredForRole($get('role_id')))
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('scope_id', null)),

                        Select::make('scope_id')
                            ->label('Scope')
                            ->options(function (Forms\Get $get) {
                                $scopeType = $get('scope_type');
                                return match ($scopeType) {
                                    UserScope::SCOPE_REGION => Region::pluck('name', 'id'),
                                    UserScope::SCOPE_DISTRICT => District::pluck('name', 'id'),
                                    UserScope::SCOPE_SCHOOL => School::pluck('name', 'id'),
                                    default => [],
                                };
                            })
                            ->visible(fn(Forms\Get $get) => static::isScopeRequiredForRole($get('role_id'))),
                    ])->columns(3),

                Section::make('Account Status')
                    ->description('Control user access')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                User::STATUS_ACTIVE => 'Active',
                                User::STATUS_SUSPENDED => 'Suspended',
                            ])
                            ->default(User::STATUS_ACTIVE)
                            ->required(),

                        Textarea::make('suspension_reason')
                            ->label('Suspension Reason')
                            ->visible(fn(Forms\Get $get) => $get('status') === User::STATUS_SUSPENDED)
                            ->nullable(),
                    ]),

                Section::make('Password')
                    ->description('Auto-generated on creation, can be reset anytime')
                    ->schema([
                        Hidden::make('generated_password')
                            ->default(''),

                        Textarea::make('password_display')
                            ->label('Generated Password')
                            ->disabled()
                            ->visible(fn($record) => $record === null || data_get($record, 'generated_password')),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText('Leave blank to keep the current password'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        User::STATUS_ACTIVE => 'success',
                        User::STATUS_SUSPENDED => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Role')
                    ->relationship('role', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        User::STATUS_ACTIVE => 'Active',
                        User::STATUS_SUSPENDED => 'Suspended',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->action(function (User $record) {
                        $generated = PasswordGenerationService::generateAndHash();
                        $record->update([
                            'password' => $generated['hash'],
                            'password_reset_required' => true,
                        ]);

                        // Log the action
                        \App\Models\GovernanceAuditLog::log(
                            \App\Models\GovernanceAuditLog::ACTION_PASSWORD_RESET,
                            userId: $record->id,
                            adminId: auth()->id(),
                            data: [
                                'reset_at' => now()->toIso8601String(),
                            ]
                        );

                        Notification::make()
                            ->title('Password Reset')
                            ->body("Generated password: {$generated['plaintext']}")
                            ->success()
                            ->duration(10)
                            ->send();
                    }),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(User $record) => $record->isActive())
                    ->action(function (User $record) {
                        $record->update(['status' => User::STATUS_SUSPENDED]);

                        // Invalidate sessions
                        \Illuminate\Support\Facades\DB::table('sessions')
                            ->where('user_id', $record->id)
                            ->delete();

                        // Log the action
                        \App\Models\GovernanceAuditLog::log(
                            \App\Models\GovernanceAuditLog::ACTION_USER_SUSPENDED,
                            userId: $record->id,
                            adminId: auth()->id(),
                            data: [
                                'suspended_at' => now()->toIso8601String(),
                            ]
                        );

                        Notification::make()
                            ->title('User Suspended')
                            ->body("User {$record->name} has been suspended. All active sessions have been invalidated.")
                            ->success()
                            ->send();
                    }),

                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(User $record) => $record->isSuspended())
                    ->action(function (User $record) {
                        $record->update(['status' => User::STATUS_ACTIVE]);

                        // Log the action
                        \App\Models\GovernanceAuditLog::log(
                            \App\Models\GovernanceAuditLog::ACTION_USER_ACTIVATED,
                            userId: $record->id,
                            adminId: auth()->id(),
                            data: [
                                'activated_at' => now()->toIso8601String(),
                            ]
                        );

                        Notification::make()
                            ->title('User Activated')
                            ->body("User {$record->name} has been activated.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // No bulk delete - users are never deleted
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Helper: Determine if a role requires a scope
     */
    protected static function isScopeRequiredForRole(?int $roleId): bool
    {
        if (!$roleId) {
            return false;
        }

        $role = Role::find($roleId);
        if (!$role) {
            return false;
        }

        // Admin role does not require a scope
        return $role->code !== Role::CODE_ADMIN;
    }
}
