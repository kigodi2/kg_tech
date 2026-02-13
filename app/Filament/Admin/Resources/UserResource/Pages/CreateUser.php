<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\GovernanceAuditLog;
use App\Models\User;
use App\Models\UserScope;
use App\Services\PasswordGenerationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate password
        $generated = PasswordGenerationService::generateAndHash();
        $data['password'] = $generated['hash'];
        $data['password_reset_required'] = true;
        $data['status'] = User::STATUS_ACTIVE;

        // Store plaintext temporarily to display to admin
        $data['generated_password'] = $generated['plaintext'];

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $data = $this->form->getState();

        // Create user scope if provided
        if (isset($data['scope_type']) && isset($data['scope_id'])) {
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

        // Log user creation
        GovernanceAuditLog::log(
            GovernanceAuditLog::ACTION_USER_CREATED,
            userId: $user->id,
            adminId: auth()->id(),
            data: [
                'name' => $user->name,
                'email' => $user->email,
                'role_code' => $user->role->code ?? null,
                'password_reset_required' => true,
            ]
        );

        // Log role assignment
        if ($user->role_id) {
            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_USER_ROLE_ASSIGNED,
                userId: $user->id,
                adminId: auth()->id(),
                data: [
                    'role_code' => $user->role->code,
                    'role_name' => $user->role->name,
                ]
            );
        }

        // Show password to admin
        Notification::make()
            ->title('User Created')
            ->body("Generated password: {$data['generated_password']}\n\nUser must change this password on first login.")
            ->warning()
            ->duration(15)
            ->send();
    }
}
