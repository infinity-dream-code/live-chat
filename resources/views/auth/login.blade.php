<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LiveChat - Sign In</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .fade-in { animation: fadeIn 0.6s ease-out; }
    .input-field:focus { transform: translateY(-1px); }
    @keyframes pulse-subtle {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.8; }
    }
    .pulse-subtle { animation: pulse-subtle 2s ease-in-out infinite; }
    .icon-wrapper {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      display: flex;
      align-items: center;
      padding-left: 1rem;
      pointer-events: none;
      z-index: 10;
    }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 flex items-center justify-center p-4">
  <div class="w-full max-w-md fade-in">
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
      <div class="bg-slate-800 px-8 py-12 text-center relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-slate-700/30 rounded-full -mr-16 -mt-16"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-slate-700/30 rounded-full -ml-12 -mb-12"></div>
        <div class="relative">
          <div class="inline-flex items-center justify-center w-20 h-20 bg-slate-700 rounded-2xl mb-5 shadow-lg pulse-subtle">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
          </div>
          <h1 class="text-3xl font-bold text-white mb-2 tracking-tight">LiveChat</h1>
          <p class="text-slate-300">Sign in to continue chatting</p>
        </div>
      </div>

      <div class="p-8">
        <form action="{{ route('login') }}" method="POST" class="space-y-5" novalidate>
          @csrf

          <div>
            <label for="username" class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
            <div class="relative transition-transform duration-200">
              <div class="icon-wrapper">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <input
                id="username"
                name="username"
                type="text"
                autocomplete="username"
                placeholder="Enter your username"
                class="input-field w-full pl-12 pr-4 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-slate-800 focus:ring-4 focus:ring-slate-800/10 transition-all duration-200 outline-none text-slate-800 placeholder-slate-400 relative z-0"
                required
              />
            </div>
          </div>

          <div>
            <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
            <div class="relative transition-transform duration-200">
              <div class="icon-wrapper">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
              </div>
              <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                placeholder="Enter your password"
                class="input-field w-full pl-12 pr-4 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-slate-800 focus:ring-4 focus:ring-slate-800/10 transition-all duration-200 outline-none text-slate-800 placeholder-slate-400 relative z-0"
                required
              />
            </div>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              class="w-full bg-slate-800 hover:bg-slate-700 active:bg-slate-900 text-white font-semibold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200"
            >
              <span class="flex items-center justify-center">
                Sign In
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
              </span>
            </button>
          </div>
        </form>

        <div class="mt-8 pt-8 border-t-2 border-slate-100">
          <p class="text-center text-sm text-slate-600">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-bold text-slate-800 hover:text-slate-600 hover:underline transition ml-1">
              Create account
            </a>
          </p>
        </div>
      </div>
    </div>

    <p class="text-center text-xs text-slate-500 mt-8">
      © 2024 LiveChat. All rights reserved.
    </p>
  </div>

  <script>
    // Handle validation errors with SweetAlert
    @if ($errors->any())
      Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        html: '<ul class="text-left list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
        confirmButtonColor: '#1e293b',
      });
    @endif

    const form = document.querySelector('form');
    if (form) {
      form.addEventListener('submit', function () {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
          btn.disabled = true;
          btn.classList.add('opacity-80', 'cursor-not-allowed');
        }
      });
    }

    const inputs = document.querySelectorAll('.input-field');
    inputs.forEach((input) => {
      input.addEventListener('focus', function () {
        const wrap = this.closest('.relative');
        if (wrap) wrap.classList.add('scale-[1.01]');
      });
      input.addEventListener('blur', function () {
        const wrap = this.closest('.relative');
        if (wrap) wrap.classList.remove('scale-[1.01]');
      });
    });
  </script>
</body>
</html>
