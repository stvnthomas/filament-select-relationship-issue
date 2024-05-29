<?php

namespace App\Filament\Resources;

use App\Enums\DefaultValue;
use App\Filament\Resources\Shield\RoleResource;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static bool $isGloballySearchable = false;

    protected static ?string $label = 'User';

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 91;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $slug = 'users';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Section::make()
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->columnSpan(12)
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->columnSpan(12)
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->columnSpan(12)
                            ->password()
                            ->required(function (string $operation): bool {
                                return $operation === 'create';
                            })
                            ->revealable(),
                        Forms\Components\Select::make('roles')
                            ->columnSpan(12)
                            ->label('Role')
                            ->preload()
                            ->relationship('roles', 'name')
                            ->required()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->default(DefaultValue::Text->value)
                    ->searchable()
                    ->sortable()
                    ->width('33%'),
                Tables\Columns\TextColumn::make('email')
                    ->default(DefaultValue::Text->value)
                    ->sortable()
                    ->width('33%'),
                Tables\Columns\TextColumn::make('roles.0')
                    ->color(function (User $record, string $state): ?string {
                        if ($state === DefaultValue::Text->value) {
                            return null;
                        }

                        return 'primary';
                    })
                    ->default(DefaultValue::Text->value)
                    ->formatStateUsing(function (string $state): string {
                        if ($state === DefaultValue::Text->value) {
                            return $state;
                        }

                        return json_decode($state, true)['name'];
                    })
                    ->label('Role')
                    ->sortable()
                    ->url(function (User $record, string $state): ?string {
                        if ($state === DefaultValue::Text->value) {
                            return null;
                        }

                        return RoleResource::getUrl('view', [
                            'record' => json_decode($state, true)['id'],
                        ]);
                    })
                    ->weight(function (User $record, string $state): ?FontWeight {
                        if ($state === DefaultValue::Text->value) {
                            return null;
                        }

                        return FontWeight::Bold;
                    })
                    ->width('33%'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
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
                Tables\Actions\RestoreAction::make()
                    ->icon('heroicon-o-arrow-path')
                    ->iconButton(),
            ])
            ->bulkActions([])
            ->defaultSort('name', 'asc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
