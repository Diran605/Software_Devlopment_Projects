<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class AdministerBackup extends Page
{

    public static function getNavigationIcon(): ?string { return "heroicon-o-circle-stack"; }
    public static function getNavigationGroup(): ?string { return "Settings"; }
    public static function getNavigationLabel(): string { return "Administer Backup"; }
    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable { return "Administer Backup"; }

    protected string $view = "filament.app.pages.administer-backup";

    protected function getHeaderActions(): array
    {
        return [
            Action::make("add")
                ->label("Add")
                ->icon("heroicon-o-plus")
                ->action("createBackup")
                ->requiresConfirmation()
                ->modalHeading("Create Database Backup")
                ->modalDescription("This will create a new backup of the database. It might take a few moments. Are you sure you want to proceed?"),
        ];
    }

        public function createBackup()
    {
        try {
            $exitCode = Artisan::call("backup:run", ["--only-db" => true]);
            
            if ($exitCode === 0) {
                Notification::make()
                    ->title("Backup completed successfully!")
                    ->success()
                    ->send();
            } else {
                $output = Artisan::output();
                Notification::make()
                    ->title("Backup failed!")
                    ->body("The backup process failed. Please check your system configuration (e.g. mysqldump path). Output: " . substr($output, 0, 200))
                    ->danger()
                    ->send();
            }
                
        } catch (\Exception $e) {
            Notification::make()
                ->title("Backup failed!")
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function downloadBackup($file)
    {
        $path = "backups/" . $file;
        if (Storage::disk("local")->exists($path)) {
            return response()->download(Storage::disk("local")->path($path));
        }
        
        Notification::make()->title("File not found")->danger()->send();
                $this->loadBackups();
    }

    public function deleteBackup($file)
    {
        $path = "backups/" . $file;
        if (Storage::disk("local")->exists($path)) {
            Storage::disk("local")->delete($path);
            Notification::make()->title("Backup deleted")->success()->send();
                $this->loadBackups();
            $this->loadBackups();
        }
    }

    public array $backups = [];

    public function mount()
    {
        $this->loadBackups();
    }

    public function loadBackups()
    {
        $files = Storage::disk("local")->files("backups");
        
        $this->backups = collect($files)->map(function ($file) {
            $size = Storage::disk("local")->size($file);
            $lastModified = Storage::disk("local")->lastModified($file);
            
            $units = ["B", "KB", "MB", "GB", "TB"];
            $bytes = max($size, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= (1 << (10 * $pow));
            $formattedSize = round($bytes, 2) . " " . $units[$pow];

            return [
                "file_name" => basename($file),
                "path" => $file,
                "size" => $formattedSize,
                "date" => Carbon::createFromTimestamp($lastModified)->format("Y-m-d H:i:s"),
                "age" => Carbon::createFromTimestamp($lastModified)->diffForHumans(),
                "timestamp" => $lastModified
            ];
        })->sortByDesc("timestamp")->values()->toArray();
    }

    protected function getViewData(): array
    {
        $files = Storage::disk("local")->files("backups");
        
        $backups = collect($files)->map(function ($file) {
            $size = Storage::disk("local")->size($file);
            $lastModified = Storage::disk("local")->lastModified($file);
            
            $units = ["B", "KB", "MB", "GB", "TB"];
            $bytes = max($size, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= (1 << (10 * $pow));
            $formattedSize = round($bytes, 2) . " " . $units[$pow];

            return [
                "file_name" => basename($file),
                "path" => $file,
                "size" => $formattedSize,
                "date" => Carbon::createFromTimestamp($lastModified)->format("Y-m-d H:i:s"),
                "age" => Carbon::createFromTimestamp($lastModified)->diffForHumans(),
                "timestamp" => $lastModified
            ];
        })->sortByDesc("timestamp")->values()->toArray();

        return [
            "backups" => $backups,
        ];
    }
}

