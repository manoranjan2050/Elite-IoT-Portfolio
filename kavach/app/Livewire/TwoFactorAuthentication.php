<?php

namespace App\Livewire;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Jeffgreco13\FilamentBreezy\Actions\PasswordButtonAction;
use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication as BaseTwoFactorAuthentication;

/**
 * Filament Breezy's own enableAction() never calls ->requiresConfirmation()
 * explicitly (unlike disableAction()/regenerateCodesAction()) — it relies
 * entirely on PasswordButtonAction::setUp() to add it conditionally. In this
 * app that path 419s on first click (mountAction fails before any modal
 * renders), while every action that calls requiresConfirmation() explicitly
 * works correctly. Overriding just this one method with the explicit call
 * fixes it without touching vendor code.
 */
class TwoFactorAuthentication extends BaseTwoFactorAuthentication
{
    public function enableAction(): Action
    {
        return PasswordButtonAction::make('enable')
            ->label(__('filament-breezy::default.profile.2fa.actions.enable'))
            ->requiresConfirmation()
            ->action(function () {
                $this->user->enableTwoFactorAuthentication();
                Notification::make()
                    ->success()
                    ->title(__('filament-breezy::default.profile.2fa.enabled.notify'))
                    ->send();
            });
    }
}
