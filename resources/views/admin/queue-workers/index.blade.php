@extends('admin.spa-inner')

@section('title', 'Queue Workers Management')
@section('heading', 'Queue Workers Management')
@section('subheading', 'Manage and monitor Laravel queue workers via Supervisor')

@section('content')
@if(session('success'))
    <div class="mb-4 p-4 bg-green-600 text-white rounded">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-600 text-white rounded">
        {{ session('error') }}
    </div>
@endif
<div class="space-y-6">
    <div class="admin-card p-5">
        <h2 class="text-sm font-semibold text-white">Worker Status</h2>
        <p class="mt-1 text-xs text-white/50">Current status of all queue workers</p>

        <div class="mt-4 space-y-3">
            @foreach($workers as $worker)
                <div class="flex items-center justify-between p-3 rounded-xl border border-white/10 bg-[#0a0f0d]">
                    <div>
                        <span class="text-sm font-medium text-white">{{ $worker }}</span>
                        <span class="ml-2 text-xs text-white/50">{{ $statuses[$worker] ?? 'Unknown' }}</span>
                    </div>
                    <div class="flex space-x-2">
                        <form method="post" action="{{ route('admin.queue-workers.manage') }}" class="inline">
                            @csrf
                            <input type="hidden" name="action" value="start">
                            <input type="hidden" name="workers[]" value="{{ $worker }}">
                            <button type="submit" class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">Start</button>
                        </form>
                        <form method="post" action="{{ route('admin.queue-workers.manage') }}" class="inline">
                            @csrf
                            <input type="hidden" name="action" value="stop">
                            <input type="hidden" name="workers[]" value="{{ $worker }}">
                            <button type="submit" class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">Stop</button>
                        </form>
                        <form method="post" action="{{ route('admin.queue-workers.manage') }}" class="inline">
                            @csrf
                            <input type="hidden" name="action" value="restart">
                            <input type="hidden" name="workers[]" value="{{ $worker }}">
                            <button type="submit" class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">Restart</button>
                        </form>
                        <form method="post" action="{{ route('admin.queue-workers.manage') }}" class="inline">
                            @csrf
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="workers[]" value="{{ $worker }}">
                            <button type="submit" class="px-3 py-1 text-xs bg-gray-600 text-white rounded hover:bg-gray-700">Status</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="admin-card p-5">
        <h2 class="text-sm font-semibold text-white">Bulk Actions</h2>
        <p class="mt-1 text-xs text-white/50">Perform actions on all workers</p>

        <div class="mt-4 flex space-x-2">
            <form method="post" action="{{ route('admin.queue-workers.manage') }}" class="inline">
                @csrf
                <input type="hidden" name="action" value="start">
                @foreach($workers as $worker)
                    <input type="hidden" name="workers[]" value="{{ $worker }}">
                @endforeach
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Start All</button>
            </form>
            <form method="post" action="{{ route('admin.queue-workers.manage') }}" class="inline">
                @csrf
                <input type="hidden" name="action" value="stop">
                @foreach($workers as $worker)
                    <input type="hidden" name="workers[]" value="{{ $worker }}">
                @endforeach
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Stop All</button>
            </form>
            <form method="post" action="{{ route('admin.queue-workers.manage') }}" class="inline">
                @csrf
                <input type="hidden" name="action" value="restart">
                @foreach($workers as $worker)
                    <input type="hidden" name="workers[]" value="{{ $worker }}">
                @endforeach
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Restart All</button>
            </form>
            <form method="post" action="{{ route('admin.queue-workers.manage') }}" class="inline">
                @csrf
                <input type="hidden" name="action" value="status">
                @foreach($workers as $worker)
                    <input type="hidden" name="workers[]" value="{{ $worker }}">
                @endforeach
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Status All</button>
            </form>
        </div>
    </div>
</div>
@endsection