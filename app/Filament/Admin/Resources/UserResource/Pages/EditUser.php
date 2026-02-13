<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\GovernanceAuditLog;
use App\Models\UserScope;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->disabled() // Users are never deleted
                ->label('Cannot Delete')
                ->tooltip('Users are never deleted per governance policy'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove generated_password from data before saving to DB
        unset($data['generated_password']);

        // Hash password if provided (and not empty)
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            // Remove empty password field so it doesn't overwrite the existing one
            unset($data['password']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        $data = $this->form->getState();

        // Handle scope changes
        if (isset($data['scope_type']) && isset($data['scope_id'])) {
            $existingScope = UserScope::where('user_id', $user->id)->first();

            if ($existingScope) {
                $scopeChanged = $existingScope->scope_type !== $data['scope_type']
                    || $existingScope->scope_id !== $data['scope_id'];

                if ($scopeChanged) {
                    $existingScope->update([
                        'scope_type' => $data['scope_type'],
                        'scope_id' => $data['scope_id'],
                    ]);

                    // Log scope change
                    GovernanceAuditLog::log(
                        GovernanceAuditLog::ACTION_USER_SCOPE_ASSIGNED,
                        userId: $user->id,
                        adminId: auth()->id(),
                        data: [
                            'scope_type' => $data['scope_type'],
                            'scope_id' => $data['scope_id'],
                        ]
                    );
                }
            } else {
                // Create new scope
                UserScope::create([
                    'user_id' => $user->id,
                    'scope_type' => $data['scope_type'],
                    'scope_id' => $data['scope_id'],
                ]);

                // Log scope assignment
                GovernanceAuditLog::log(
                    GovernanceAuditLog::ACTION_USER_SCOPE_ASSIGNED,
                    userId: $user->id,
                    adminId: auth()->id(),
                    data: [
                        'scope_type' => $data['scope_type'],
                        'scope_id' => $data['scope_id'],
                    ]
                );
            }
        }
    }
}
