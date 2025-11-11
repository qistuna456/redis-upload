<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>YoPrint CSV Upload</title>
	<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
	<meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100">
	<div class="max-w-4xl mx-auto p-6">
		<h1 class="text-2xl font-semibold mb-4">CSV Uploads</h1>
		
		@if (session('status'))
			<div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
				{{ session('status') }}
			</div>
		@endif
		
		<div class="bg-white p-4 rounded shadow mb-6">
			<form action="{{ route('uploads.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
				@csrf
				<div>
					<label class="block text-sm font-medium mb-1">Upload CSV</label>
					<input type="file" name="file" accept=".csv,text/csv" class="border rounded p-2 w-full" required>
					@error('file')
						<div class="text-red-600 text-sm mt-1">{{ $message }}</div>
					@enderror
				</div>
				<button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Upload</button>
			</form>
		</div>
		
		<div class="bg-white p-4 rounded shadow">
			<div class="flex items-center justify-between mb-3">
				<h2 class="text-lg font-medium">Recent Uploads</h2>
				<span id="last-updated" class="text-sm text-gray-500"></span>
			</div>
			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200">
					<thead class="bg-gray-50">
						<tr>
							<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
							<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
							<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
							<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
							<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
							<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
						</tr>
					</thead>
					<tbody id="uploads-body" class="bg-white divide-y divide-gray-200">
						@foreach($uploads as $u)
							<tr>
								<td class="px-3 py-2 text-sm text-gray-700">{{ $u->id }}</td>
								<td class="px-3 py-2 text-sm text-gray-700 truncate max-w-xs" title="{{ $u->original_name }}">{{ $u->original_name }}</td>
								<td class="px-3 py-2 text-sm">
									<span class="px-2 py-1 rounded text-white {{ $u->status === 'completed' ? 'bg-green-600' : ($u->status === 'failed' ? 'bg-red-600' : ($u->status === 'processing' ? 'bg-yellow-600' : 'bg-gray-500')) }}">
										{{ ucfirst($u->status) }}
									</span>
								</td>
								<td class="px-3 py-2 text-sm text-gray-700">{{ $u->processed_rows }}/{{ $u->total_rows }} ({{ $u->failed_rows }} failed)</td>
								<td class="px-3 py-2 text-sm text-gray-500">{{ optional($u->created_at)->toDateTimeString() }}</td>
								<td class="px-3 py-2 text-sm text-gray-500">{{ optional($u->completed_at)->toDateTimeString() }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
	
	<script>
		const uploadsBody = document.getElementById('uploads-body');
		const lastUpdated = document.getElementById('last-updated');
		
		function escapeHtml(str) {
			return str.replace(/[&<>"']/g, function(m) {
				return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]);
			});
		}
		
		function badge(status) {
			let color = 'bg-gray-500';
			if (status === 'completed') color = 'bg-green-600';
			else if (status === 'failed') color = 'bg-red-600';
			else if (status === 'processing') color = 'bg-yellow-600';
			return `<span class=\"px-2 py-1 rounded text-white ${color}\">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
		}

		const listUrl = "{{ route('uploads.list') }}";

		async function refreshUploads() {
			try {
				const res = await fetch(listUrl, { headers: { 'Accept': 'application/json' }});
				if (!res.ok) return;
				const data = await res.json();
				const rows = (data.data || []).map(u => {
					return `<tr>
						<td class=\"px-3 py-2 text-sm text-gray-700\">${u.id}</td>
						<td class=\"px-3 py-2 text-sm text-gray-700 truncate max-w-xs\" title=\"${escapeHtml(u.original_name)}\">${escapeHtml(u.original_name)}</td>
						<td class=\"px-3 py-2 text-sm\">${badge(u.status)}</td>
						<td class=\"px-3 py-2 text-sm text-gray-700\">${u.processed_rows}/${u.total_rows} (${u.failed_rows} failed)</td>
						<td class=\"px-3 py-2 text-sm text-gray-500\">${u.created_at || ''}</td>
						<td class=\"px-3 py-2 text-sm text-gray-500\">${u.completed_at || ''}</td>
					</tr>`;
				}).join('');
				uploadsBody.innerHTML = rows;
				lastUpdated.textContent = 'Updated at ' + new Date().toLocaleTimeString();
			} catch (e) {
				// ignore
			}
		}
		
		setInterval(refreshUploads, 2000);
	</script>
</body>
</html>


