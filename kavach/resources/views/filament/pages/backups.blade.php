<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Backup files</x-slot>
        <x-slot name="description">
            Database backups run without mysqldump, so they work on any shared hosting.
            Full backups also include the Ed25519 signing keys and uploaded release zips —
            everything needed to move Kavach to a new server.
        </x-slot>

        @if (count($this->backups) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No backups yet. Use the buttons above to create one.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 pr-4 font-semibold">File</th>
                            <th class="py-2 pr-4 font-semibold">Size</th>
                            <th class="py-2 pr-4 font-semibold">Created</th>
                            <th class="py-2 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->backups as $backup)
                            <tr class="border-b border-gray-100 dark:border-white/5" wire:key="{{ $backup['name'] }}">
                                <td class="py-2 pr-4 font-mono text-xs">{{ $backup['name'] }}</td>
                                <td class="py-2 pr-4 whitespace-nowrap">{{ number_format($backup['size'] / 1048576, 2) }} MB</td>
                                <td class="py-2 pr-4 whitespace-nowrap">{{ \Carbon\Carbon::createFromTimestamp($backup['created_at'])->format('d M Y H:i') }}</td>
                                <td class="py-2">
                                    <div class="flex flex-wrap gap-2">
                                        <x-filament::button size="xs" color="gray" wire:click="download('{{ $backup['name'] }}')">
                                            Download
                                        </x-filament::button>
                                        <x-filament::button size="xs" color="info" wire:click="emailBackup('{{ $backup['name'] }}')"
                                            wire:confirm="Email this backup to your account email?">
                                            Email me
                                        </x-filament::button>
                                        <x-filament::button size="xs" color="warning" wire:click="restoreBackup('{{ $backup['name'] }}')"
                                            wire:confirm="RESTORE from this backup? Current database data will be REPLACED with the backup contents. This cannot be undone.">
                                            Restore
                                        </x-filament::button>
                                        <x-filament::button size="xs" color="danger" wire:click="deleteBackup('{{ $backup['name'] }}')"
                                            wire:confirm="Delete this backup file permanently?">
                                            Delete
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Automatic daily backups</x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            A database backup runs daily at 02:30 (keeps 14 days) when the Laravel scheduler is active.
            On shared hosting, add this cron job in hPanel:
        </p>
        <pre class="mt-2 p-3 rounded bg-gray-950 text-emerald-300 text-xs overflow-x-auto">* * * * * php /path/to/kavach/artisan schedule:run >> /dev/null 2>&1</pre>
    </x-filament::section>
</x-filament-panels::page>
