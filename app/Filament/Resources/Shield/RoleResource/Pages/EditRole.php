<?php

namespace App\Filament\Resources\Shield\RoleResource\Pages;

use App\Filament\Resources\Shield\RoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public Collection $permissions;

    protected function afterSave(): void
    {
        $permissionModels = collect();

        $this->permissions->each(function ($permission) use ($permissionModels) {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                /** @phpstan-ignore-next-line */
                'name' => $permission,
                'guard_name' => $this->data['guard_name'],
            ]));
        });

        $this->record->syncPermissions($permissionModels);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View'),
            Actions\DeleteAction::make()
                ->label('Delete'),
            Actions\RestoreAction::make()
                ->label('Restore'),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Update');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(function ($permission, $key) {
                return ! in_array($key, ['name', 'guard_name', 'select_all']);
            })
            ->values()
            ->flatten()
            ->unique();

        return Arr::only($data, ['name', 'guard_name']);
    }
}
