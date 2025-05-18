<?php

namespace App\Livewire;

use App\Models\User;
use App\Support\TimezoneHelper;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UpdateTimezoneForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'timezone' => Auth::user()->timezone ?? 'UTC',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('timezone')
                    ->label('Timezone')
                    ->options(TimezoneHelper::getTimezones())
                    ->required()
                    ->searchable(),
            ])
            ->statePath('data');
    }

    public function updateTimezone(): void
    {
        $data = $this->form->getState();

        $userId = Auth::id();

        // Update the user's timezone using the User model
        User::where('id', $userId)->update([
            'timezone' => $data['timezone'],
        ]);

        // Update the application timezone
        TimezoneHelper::setApplicationTimezone();

        $this->dispatch('saved');

        // Show a success notification
        Notification::make()
            ->title(__('Your timezone has been updated.'))
            ->success()
            ->send();
    }

    public function render()
    {
        return view('profile.update-timezone-form');
    }
}
