<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

/**
 * For Edit pages with sensitive fields that should stay disabled by default but can be
 * manually unlocked after a confirmation prompt, e.g. `disabled(fn ($livewire) =>
 * ! $livewire->isFieldUnlocked('vatsim_id'))` on the field, paired with a
 * `makeUnlockAction('vatsim_id', ...)` hint/header action to flip it.
 */
trait HasUnlockableFields
{
    /** @var array<string, bool> */
    public array $unlockedFields = [];

    public function unlockField(string $key): void
    {
        $this->unlockedFields[$key] = true;
    }

    public function isFieldUnlocked(string $key): bool
    {
        return $this->unlockedFields[$key] ?? false;
    }

    public function makeUnlockAction(
        string $key,
        string $modalHeading,
        string $modalDescription,
        string $label = 'Unlock to edit',
    ): Action {
        return Action::make("unlock_{$key}")
            ->label($label)
            ->icon('heroicon-o-lock-closed')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading($modalHeading)
            ->modalDescription($modalDescription)
            ->modalSubmitActionLabel('Yes, unlock')
            ->action(fn () => $this->unlockField($key))
            ->hidden(fn () => $this->isFieldUnlocked($key));
    }
}
