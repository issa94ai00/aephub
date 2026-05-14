@extends('admin.spa-inner')

@section('title', 'Queue Workers Management')
@section('heading', 'Queue Workers Management')
@section('subheading', 'Manage and monitor Laravel queue workers via Supervisor')

@section('content')
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
                        <button type="button" onclick="manageWorker('start', ['{{ $worker }}'])" class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">Start</button>
                        <button type="button" onclick="manageWorker('stop', ['{{ $worker }}'])" class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">Stop</button>
                        <button type="button" onclick="manageWorker('restart', ['{{ $worker }}'])" class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">Restart</button>
                        <button type="button" onclick="manageWorker('status', ['{{ $worker }}'])" class="px-3 py-1 text-xs bg-gray-600 text-white rounded hover:bg-gray-700">Status</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="admin-card p-5">
        <h2 class="text-sm font-semibold text-white">Bulk Actions</h2>
        <p class="mt-1 text-xs text-white/50">Perform actions on all workers</p>

        <div class="mt-4 flex space-x-2">
            <button type="button" onclick="manageWorker('start')" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Start All</button>
            <button type="button" onclick="manageWorker('stop')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Stop All</button>
            <button type="button" onclick="manageWorker('restart')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Restart All</button>
            <button type="button" onclick="manageWorker('status')" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Status All</button>
        </div>
    </div>
</div>

<script>
function manageWorker(action, workers = null) {
    const data = {
        action: action,
        workers: workers || {{ json_encode($workers) }}
    };

    fetch('{{ route("admin.queue-workers.manage") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Action completed successfully');
            location.reload();
        } else {
            alert('Action failed: ' + data.output);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>
@endsection