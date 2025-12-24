@extends('layouts.chat')

@section('content')
<div class="flex-1 overflow-y-auto bg-gray-50">
    <div class="max-w-2xl mx-auto p-6">
        <h1 class="text-2xl font-medium text-gray-900 mb-6">Settings</h1>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <!-- Profile Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Profile</h2>
            
            <form action="{{ route('settings.profile') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Avatar</label>
                    <div class="flex items-center space-x-4">
                        @if(auth()->user()->avatar)
                            <img src="{{ Storage::url(auth()->user()->avatar) }}" 
                                alt="{{ auth()->user()->name }}" 
                                class="w-20 h-20 rounded-full object-cover">
                        @else
                            <div class="w-20 h-20 rounded-full bg-gray-600 flex items-center justify-center text-white font-medium text-2xl">
                                {{ auth()->user()->initials }}
                            </div>
                        @endif
                        <div>
                            <input type="file" name="avatar" id="avatar" accept="image/*" 
                                class="block text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                            <p class="mt-1 text-xs text-gray-500">JPG, PNG or GIF. Max size 2MB.</p>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username', auth()->user()->username) }}" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent @error('username') border-red-500 @enderror">
                    @error('username')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" 
                    class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Update Profile
                </button>
            </form>
        </div>

        <!-- Password Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Change Password</h2>
            
            <form action="{{ route('settings.password') }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input type="password" name="current_password" id="current_password" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent @error('current_password') border-red-500 @enderror">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="password" id="password" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent">
                </div>

                <button type="submit" 
                    class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</div>
@endsection


