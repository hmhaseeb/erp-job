<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('My Profile & Password')]
class ProfileManager extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public $avatarUpload;

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->phone = (string) ($user->phone ?? '');
    }

    public function saveProfile(): void
    {
        $user = Auth::user();
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:40'],
            'avatarUpload' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ];

        if ($this->avatarUpload) {
            $data['avatar'] = $this->avatarUpload->store('avatars', 'public');
        }

        $user->update($data);
        $this->avatarUpload = null;
        log_activity('users', 'profile_updated', 'User updated their profile information.', $user);
        $this->dispatch('toast', message: 'Profile updated successfully.', type: 'success');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();
        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->new_password)]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        log_activity('users', 'password_changed', 'User changed their account password.', $user);
        $this->dispatch('toast', message: 'Password changed successfully.', type: 'success');
    }

    public function render()
    {
        return view('livewire.profile.profile-manager', [
            'user' => Auth::user()->load('role', 'department'),
        ]);
    }
}
