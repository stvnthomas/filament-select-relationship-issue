<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\Component;

class Login extends \Filament\Pages\Auth\Login
{
    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => 'user1@example.com',
            'password' => 'password',
            'remember' => true,
        ]);
    }
}
