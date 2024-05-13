<?php

namespace App\Filament\Resources\Shield;

use App\Enums\DefaultValue;
use App\Filament\Resources\Shield\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Forms\ShieldSelectAllToggle;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class RoleResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Section::make()
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->columnSpan(4)
                            ->label(__('filament-shield::filament-shield.field.name'))
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('guard_name')
                            ->columnSpan(4)
                            ->default(Utils::getFilamentAuthGuard())
                            ->label(__('filament-shield::filament-shield.field.guard_name'))
                            ->nullable(),
                        ShieldSelectAllToggle::make('select_all')
                            ->columnSpan(4)
                            ->dehydrated(function (string $state): bool {
                                return $state;
                            })
                            ->helperText(function (): HtmlString {
                                return new HtmlString(__('filament-shield::filament-shield.field.select_all.message'));
                            })
                            ->label(__('filament-shield::filament-shield.field.select_all.name'))
                            ->offIcon('heroicon-s-shield-exclamation')
                            ->onIcon('heroicon-s-shield-check'),
                    ]),
                Forms\Components\Tabs::make('Permissions')
                    ->tabs([
                        static::getTabFormComponentForResources(),
                        static::getTabFormComponentForPage(),
                        static::getTabFormComponentForWidget(),
                        static::getTabFormComponentForCustomPermissions(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->default(DefaultValue::Text->value)
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->searchable()
                    ->sortable()
                    ->width('33%'),
                Tables\Columns\TextColumn::make('guard_name')
                    ->default(DefaultValue::Text->value)
                    ->label(__('filament-shield::filament-shield.column.guard_name'))
                    ->sortable()
                    ->width('33%'),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->default(DefaultValue::Text->value)
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->sortable()
                    ->width('33%'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->iconButton(),
            ])
            ->bulkActions([])
            ->defaultSort('name', 'asc');
    }

    public static function canGloballySearch(): bool
    {
        return Utils::isResourceGloballySearchable()
            && count(static::getGloballySearchableAttributes())
            && static::canViewAny();
    }

    public static function getCheckBoxListComponentForResource(array $entity): Component
    {
        $permissionsArray = static::getResourcePermissionOptions($entity);

        return static::getCheckboxListFormComponent($entity['resource'], $permissionsArray, false);
    }

    public static function getCheckboxListFormComponent(string $name, array $options, bool $searchable = true): Component
    {
        return Forms\Components\CheckboxList::make($name)
            ->afterStateHydrated(
                fn (Component $component, string $operation, ?Model $record) => static::setPermissionStateForRecordPermissions(
                    component: $component,
                    operation: $operation,
                    permissions: $options,
                    record: $record,
                )
            )
            ->bulkToggleable()
            ->columnSpan(static::shield()->getCheckboxListColumnSpan())
            ->columns(static::shield()->getCheckboxListColumns())
            ->dehydrated(fn ($state) => ! blank($state))
            ->gridDirection('row')
            ->label('')
            ->options(fn (): array => $options)
            ->searchable($searchable);
    }

    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster() ?? static::$cluster;
    }

    public static function getCustomPermissionOptions(): ?array
    {
        return FilamentShield::getCustomPermissions()
            ->mapWithKeys(fn ($customPermission) => [
                $customPermission => static::shield()->hasLocalizedPermissionLabels()
                    ? str($customPermission)->headline()->toString()
                    : $customPermission,
            ])
            ->toArray();
    }

    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function getModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.role');
    }

    public static function getNavigationBadge(): ?string
    {
        return Utils::isResourceNavigationBadgeEnabled()
            ? strval(static::getEloquentQuery()->count())
            : null;
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getNavigationIcon(): string
    {
        return __('filament-shield::filament-shield.nav.role.icon');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-shield::filament-shield.nav.role.label');
    }

    public static function getNavigationSort(): ?int
    {
        return Utils::getResourceNavigationSort();
    }

    public static function getPageOptions(): array
    {
        return collect(FilamentShield::getPages())
            ->flatMap(fn ($page) => [
                $page['permission'] => static::shield()->hasLocalizedPermissionLabels()
                    ? FilamentShield::getLocalizedPageLabel($page['class'])
                    : $page['permission'],
            ])
            ->toArray();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.roles');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getResourceEntitiesSchema(): ?array
    {
        return collect(FilamentShield::getResources())
            ->sortKeys()
            ->map(function ($entity) {
                $sectionLabel = strval(
                    static::shield()->hasLocalizedPermissionLabels()
                        ? FilamentShield::getLocalizedResourceLabel($entity['fqcn'])
                        : $entity['model']
                );

                return Forms\Components\Section::make($sectionLabel)
                    ->collapsible()
                    ->columnSpan(static::shield()->getSectionColumnSpan())
                    ->compact()
                    ->description(fn () => new HtmlString('<span style="word-break: break-word;">'.Utils::showModelPath($entity['fqcn']).'</span>'))
                    ->schema([
                        static::getCheckBoxListComponentForResource($entity),
                    ]);
            })
            ->toArray();
    }

    public static function getResourcePermissionOptions(array $entity): array
    {
        return collect(Utils::getResourcePermissionPrefixes($entity['fqcn']))
            ->flatMap(function ($permission) use ($entity) {
                $name = $permission.'_'.$entity['resource'];

                return [
                    $name => static::shield()->hasLocalizedPermissionLabels()
                        ? FilamentShield::getLocalizedResourcePermissionLabel($permission)
                        : $name,
                ];
            })
            ->toArray();
    }

    public static function getResourceTabBadgeCount(): ?int
    {
        return collect(FilamentShield::getResources())
            ->map(fn ($resource) => count(static::getResourcePermissionOptions($resource)))
            ->sum();
    }

    public static function getSlug(): string
    {
        return Utils::getResourceSlug();
    }

    public static function getTabFormComponentForCustomPermissions(): Component
    {
        $options = static::getCustomPermissionOptions();
        $count = count($options);

        return Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.custom'))
            ->badge($count)
            ->columnSpanFull()
            ->schema([
                static::getCheckboxListFormComponent('custom_permissions', $options),
            ])
            ->visible(fn (): bool => (bool) Utils::isCustomPermissionEntityEnabled() && $count > 0);
    }

    public static function getTabFormComponentForPage(): Component
    {
        $options = static::getPageOptions();
        $count = count($options);

        return Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.pages'))
            ->badge($count)
            ->columnSpanFull()
            ->schema([
                static::getCheckboxListFormComponent('pages_tab', $options),
            ])
            ->visible(fn (): bool => (bool) Utils::isPageEntityEnabled() && $count > 0);
    }

    public static function getTabFormComponentForResources(): Component
    {
        return static::shield()->hasSimpleResourcePermissionView()
            ? static::getTabFormComponentForSimpleResourcePermissionsView()
            : Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.resources'))
                ->badge(static::getResourceTabBadgeCount())
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Section::make()
                        ->columns(static::shield()->getGridColumns())
                        ->schema(static::getResourceEntitiesSchema()),
                ])
                ->visible(fn (): bool => (bool) Utils::isResourceEntityEnabled());
    }

    public static function getTabFormComponentForSimpleResourcePermissionsView(): Component
    {
        $options = FilamentShield::getAllResourcePermissions();
        $count = count($options);

        return Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.resources'))
            ->badge($count)
            ->columnSpanFull()
            ->schema([
                static::getCheckboxListFormComponent('resources_tab', $options),
            ])
            ->visible(fn (): bool => (bool) Utils::isResourceEntityEnabled() && $count > 0);
    }

    public static function getTabFormComponentForWidget(): Component
    {
        $options = static::getWidgetOptions();
        $count = count($options);

        return Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.widgets'))
            ->badge($count)
            ->columnSpanFull()
            ->schema([
                static::getCheckboxListFormComponent('widgets_tab', $options),
            ])
            ->visible(fn (): bool => (bool) Utils::isWidgetEntityEnabled() && $count > 0);
    }

    public static function getWidgetOptions(): array
    {
        return collect(FilamentShield::getWidgets())
            ->flatMap(fn ($widget) => [
                $widget['permission'] => static::shield()->hasLocalizedPermissionLabels()
                    ? FilamentShield::getLocalizedWidgetLabel($widget['class'])
                    : $widget['permission'],
            ])
            ->toArray();
    }

    public static function isScopedToTenant(): bool
    {
        return Utils::isScopedToTenant();
    }

    public static function setPermissionStateForRecordPermissions(Component $component, string $operation, array $permissions, ?Model $record): void
    {
        if (in_array($operation, ['edit', 'view'])) {
            if (blank($record)) {
                return;
            }
            if ($component->isVisible() && count($permissions) > 0) {
                $component->state(
                    collect($permissions)
                        /** @phpstan-ignore-next-line */
                        ->filter(fn ($value, $key) => $record->checkPermissionTo($key))
                        ->keys()
                        ->toArray()
                );
            }
        }
    }

    public static function shield(): FilamentShieldPlugin
    {
        return FilamentShieldPlugin::get();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Utils::isResourceNavigationRegistered();
    }
}
