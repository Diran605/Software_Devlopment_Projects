<?php
$f = "app/Filament/Concerns/HasInventoryCountView.php";
$c = file_get_contents($f);
$c = preg_replace(
    "/(qtyBefore: \\\$line->qty_system,\s*qtyAfter: \\\$line->qty_counted,)/",
    "\$1\n                                    movedAt: \$this->record->count_at,",
    $c
);
file_put_contents($f, $c);

