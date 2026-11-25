<div>
    <div class="mb-4">
        <h1 class="text-gray-900 text-xl font-bold mb-1">My Profile &amp; Password Management</h1>
        <p class="text-sm text-default mb-0">Manage your personal account details and update your sign-in password.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-7 bg-white border border-border-color rounded-md p-5">
            <h2 class="text-base font-bold text-title mb-4">Profile Details</h2>
            <form wire:submit="saveProfile" class="space-y-4">
                <div class="flex items-center gap-4">
                    <div class="size-16 rounded-full border border-border-color bg-light flex items-center justify-center overflow-hidden">
                        @if ($avatarUpload)
                            <img src="{{ $avatarUpload->temporaryUrl() }}" class="size-full object-cover" alt="preview">
                        @elseif ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                            <img src="{{ asset('storage/'.$user->avatar) }}" class="size-full object-cover" alt="{{ $user->name }}">
                        @else
                            <span class="text-lg font-bold text-primary">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        @endif
                    </div>
                    <div>
                        <label class="btn-sm bg-dark text-white border border-dark inline-flex items-center gap-2 cursor-pointer hover:bg-primary-hover">
                            <i class="ph-duotone ph-upload-simple"></i> Change Photo
                            <input type="file" wire:model="avatarUpload" class="hidden" accept="image/*">
                        </label>
                        <p class="text-xs text-default mt-1 mb-0">Role: <strong>{{ $user->role?->name ?? 'N/A' }}</strong> · Type: <strong>{{ ucfirst($user->user_type) }}</strong></p>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-900 mb-1 block">Full Name</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    @error('name')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-semibold text-gray-900 mb-1 block">Email</label>
                        <input type="email" wire:model="email" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                        @error('email')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-900 mb-1 block">Phone</label>
                        <input type="text" wire:model="phone" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-sm bg-dark text-white border border-dark hover:bg-primary-hover">Save Profile</button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-5 bg-white border border-border-color rounded-md p-5">
            <h2 class="text-base font-bold text-title mb-4">Change Password</h2>
            <form wire:submit="updatePassword" class="space-y-4">
                <div>
                    <label class="text-sm font-semibold text-gray-900 mb-1 block">Current Password</label>
                    <input type="password" wire:model="current_password" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    @error('current_password')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-900 mb-1 block">New Password</label>
                    <input type="password" wire:model="new_password" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    @error('new_password')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-900 mb-1 block">Confirm New Password</label>
                    <input type="password" wire:model="new_password_confirmation" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn-sm bg-primary text-white border border-primary hover:bg-primary-hover">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
