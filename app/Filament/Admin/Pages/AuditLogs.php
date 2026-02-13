<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\AuthenticationAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.admin.pages.audit-logs';

    protected static ?string $title = 'Audit Logs';

    private function getQuery()
    {
        // Check if table exists before querying
        try {
            if (!$this->tableExists()) {
                return AuthenticationAuditLog::query()->whereRaw('1=0'); // Empty query
            }
            return AuthenticationAuditLog::query();
        } catch (\Exception $e) {
            return AuthenticationAuditLog::query()->whereRaw('1=0'); // Empty query on error
        }
    }

    private function tableExists(): bool
    {
        try {
            \DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $tables = \Schema::getTables();
            return collect($tables)->contains('name', 'authentication_audit_logs');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                TextColumn::make('id')
                    ->label('Log ID')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('event_type')
                    ->label('Event')
                    ->colors([
                        'success' => 'LOGIN',
                        'danger' => 'LOGOUT',
                        'warning' => 'FAILED_LOGIN',
                        'info' => 'PASSWORD_CHANGE',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state)),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->options([
                        'LOGIN' => 'Login',
                        'LOGOUT' => 'Logout',
                        'FAILED_LOGIN' => 'Failed Login',
                        'PASSWORD_CHANGE' => 'Password Change',
                    ]),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->striped();
    }
}
