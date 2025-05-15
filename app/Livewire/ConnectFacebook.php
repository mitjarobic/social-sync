<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Notifications\Notification;

class ConnectFacebook extends Component
{
    public bool $isFacebookConnected = false;

    public function redirectToFacebook()
    {
        return redirect()->away(route('facebook.redirect'));
    }

    public function render()
    {
        return view('livewire.connect-facebook');
    }

    public function isFacebookConnected(): bool
    {
        return auth()->user()?->facebook_token && (new \App\Services\FacebookService)->isFacebookTokenValid();
    }

    public function mount(): void
    {
        $this->isFacebookConnected = $this->isFacebookConnected();

        if (session()->has('success')) {
            Notification::make()
                ->title(session('success'))
                ->success()
                ->send();
        }

        if (session()->has('error')) {
            Notification::make()
                ->title(session('error'))
                ->danger()
                ->send();
        }
    }
}
