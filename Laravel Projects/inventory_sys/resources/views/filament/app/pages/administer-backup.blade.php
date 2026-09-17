<x-filament-panels::page>
    <style>
        .backup-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        .backup-table th, 
        .backup-table td {
            padding: 0.75rem 1rem !important;
        }
        .backup-table th {
            font-weight: 600;
            background-color: rgba(39, 39, 42, 0.8) !important;
            color: #e4e4e7 !important;
            border-bottom: 1px solid #3f3f46;
        }
        .backup-table td {
            border-bottom: 1px solid #27272a;
            color: #d4d4d8;
        }
        .backup-table tbody tr:hover {
            background-color: rgba(39, 39, 42, 0.4);
        }
    </style>

    <x-filament::card>
        <div class="overflow-x-auto">
            <table class="backup-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Size</th>
                        <th>Date</th>
                        <th>Age</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                        <tr>
                            <td class="font-medium">{{ $backup["file_name"] }}</td>
                            <td>{{ $backup["size"] }}</td>
                            <td>{{ $backup["date"] }}</td>
                            <td>{{ $backup["age"] }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-filament::button size="sm" color="info" wire:click="downloadBackup('{{ $backup['file_name'] }}')" outlined>
                                        Download
                                    </x-filament::button>
                                    
                                    <x-filament::button size="sm" color="danger" wire:click="deleteBackup('{{ $backup['file_name'] }}')" wire:confirm="Are you sure you want to delete this backup?" outlined>
                                        Delete
                                    </x-filament::button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500">No backups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::card>
</x-filament-panels::page>
