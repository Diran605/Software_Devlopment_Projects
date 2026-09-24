<?php
$files = [
    "app/Filament/App/Resources/Items/Tables/ItemsTable.php",
    "app/Filament/Admin/Resources/Items/Tables/ItemsTable.php"
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Revert the namespaces
    $content = str_replace("use Filament\Tables\Actions\ExportAction;", "use Filament\Actions\ExportAction;", $content);
    $content = str_replace("use Filament\Tables\Actions\ExportBulkAction;", "use Filament\Actions\ExportBulkAction;", $content);
    
    $content = str_replace("use Filament\Tables\Actions\EditAction;", "use Filament\Actions\EditAction;", $content);
    $content = str_replace("use Filament\Tables\Actions\ViewAction;", "use Filament\Actions\ViewAction;", $content);
    $content = str_replace("use Filament\Tables\Actions\DeleteAction;", "use Filament\Actions\DeleteAction;", $content);
    $content = str_replace("use Filament\Tables\Actions\BulkActionGroup;", "use Filament\Actions\BulkActionGroup;", $content);
    $content = str_replace("use Filament\Tables\Actions\DeleteBulkAction;", "use Filament\Actions\DeleteBulkAction;", $content);
    
    file_put_contents($file, $content);
}
echo "Done fixing exports\n";

