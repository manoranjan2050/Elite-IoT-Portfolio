<?php

namespace App\Filament\Pages;

use App\Services\BackupService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class Backups extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'System';

    protected static string $view = 'filament.pages.backups';

    /** Attachments larger than this are not emailed (most SMTP limits ~25 MB). */
    private const MAX_EMAIL_BYTES = 20 * 1024 * 1024;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createDb')
                ->label('Backup database')
                ->icon('heroicon-o-circle-stack')
                ->action(fn () => $this->runCreate(false)),
            Action::make('createFull')
                ->label('Full backup (DB + keys + releases)')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('info')
                ->action(fn () => $this->runCreate(true)),
            Action::make('upload')
                ->label('Upload backup')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    FileUpload::make('file')
                        ->label('Kavach backup zip')
                        ->disk('local')
                        ->directory('backups')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->preserveFilenames()
                        ->required(),
                ])
                ->action(function (): void {
                    Notification::make()->title('Backup uploaded')
                        ->body('It now appears in the list below — use Restore when ready.')
                        ->success()->send();
                }),
        ];
    }

    /** @return array<int, array{name: string, size: int, created_at: int}> */
    public function getBackupsProperty(): array
    {
        return app(BackupService::class)->list();
    }

    private function runCreate(bool $full): void
    {
        try {
            $name = app(BackupService::class)->create($full);
            Notification::make()->title('Backup created')->body($name)->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Backup failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function download(string $name): ?BinaryFileResponse
    {
        try {
            return response()->download(app(BackupService::class)->path($name));
        } catch (Throwable $e) {
            Notification::make()->title('Download failed')->body($e->getMessage())->danger()->send();

            return null;
        }
    }

    public function emailBackup(string $name): void
    {
        try {
            $path = app(BackupService::class)->path($name);

            if (filesize($path) > self::MAX_EMAIL_BYTES) {
                Notification::make()->title('Too large to email')
                    ->body('This backup is over 20 MB — download it instead.')
                    ->warning()->send();

                return;
            }

            $email = auth()->user()->email;

            Mail::raw(
                "Kavach backup attached.\n\nFile: {$name}\nServer: ".config('app.url')."\nCreated: ".now()->toDayDateTimeString(),
                fn ($message) => $message->to($email)
                    ->subject("Kavach backup — {$name}")
                    ->attach($path),
            );

            Notification::make()->title('Backup emailed')
                ->body("Sent to {$email}. (Requires MAIL_* settings in .env — see README.)")
                ->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Email failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function deleteBackup(string $name): void
    {
        try {
            @unlink(app(BackupService::class)->path($name));
            Notification::make()->title('Backup deleted')->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Delete failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function restoreBackup(string $name): void
    {
        try {
            app(BackupService::class)->restore($name);
            Notification::make()->title('Backup restored')
                ->body('Database (and any keys/releases in the backup) have been restored.')
                ->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Restore failed')->body($e->getMessage())->danger()->send();
        }
    }
}
