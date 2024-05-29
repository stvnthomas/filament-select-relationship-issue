In this repo, I'm using the Shield plugin for the user roles/permissions in my Filament panel. The user resource has the following select field with relationship-sourced options for the role assignement. The issue isn't specific to the Shield plugin, that's just where I initially noticed the issue and the code I have on-hand.

```
Forms\Components\Select::make('roles')
    ->label('Role')
    ->preload()
    ->relationship(titleAttribute: 'name')
    ->required()
    ->searchable(),
```

Since Filament 3.2.79, setting the role causes the following error when saving:

```
array_diff(): Argument #2 must be of type array, string given
```

Downgrading to 3.2.78 fixes the issue. The issue isn't present when using `options()` with the select. The error appears to originate on [Select.php#L994-L1000](https://github.com/filamentphp/filament/blob/0699ac595508a94cf62fd12ece1279d67f463151/packages/forms/src/Components/Select.php#L994-L1000). It seems to be expecting an array but the select is returning a single value.

## Steps to reproduce the issue

**1. Clone the repo**
`git clone <url>`

**2. CD into the project**
`cd filament-select-relationship-issue`

**3. Composer**
`composer install`

**4. Create the .env config**
`cp .env.example .env`

**5. Generate the application key**
`php artisan key:generate`

**6. Serve the project**
`php artisan serve`

**7. Login**
Vist `http://127.0.0.1:8000` and log in - the user/pass is preconfigured.

**8. Create a user**
Navigate to the user resource and create a new user. The user will be created but the selected role will not be assigned. The application will error with the following:

```
array_diff(): Argument #2 must be of type array, string given
```

**9. Edit a user**
The same will happen if you edit a user and assign a new role.
