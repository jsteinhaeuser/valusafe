<?php

namespace Tests;

/**
 * Reihenfolge der Bilder speichern (image_manager.php action=reorder,
 * reorderItemImages() in helpers_images.php).
 *
 * Bis 4.9.5 fehlte die Funktion ganz: Das Ziehen in der Bildergalerie beim
 * Bearbeiten endete auf dem Server in einem Fehler, nach dem Neuladen stand
 * die alte Reihenfolge da. Gefunden von PHPStan (function.notFound).
 *
 * Testdaten aus GeschaeftskernTestCase: Ring (1) hat die Bilder 1 und 2,
 * Uhr (2) hat Bild 3.
 */
class BilderReihenfolgeTest extends GeschaeftskernTestCase
{
    private function reihenfolge(int $itemId): array
    {
        return array_map('intval', array_column(getItemImages($itemId), 'id'));
    }

    public function testNeueReihenfolgeWirdGespeichert(): void
    {
        $this->assertSame([1, 2], $this->reihenfolge(1));
        $this->assertTrue(reorderItemImages(1, [2, 1]));
        $this->assertSame([2, 1], $this->reihenfolge(1));
    }

    public function testFremdeBilderBleibenUnberuehrt(): void
    {
        // Bild 3 gehoert zur Uhr - es darf ueber den Ring nicht verschoben werden
        $this->assertTrue(reorderItemImages(1, [3, 2, 1]));
        $this->assertSame(0, $this->zahl("SELECT sort_order FROM item_images WHERE id = 3"));
        $this->assertSame([2, 1], $this->reihenfolge(1));
        $this->assertSame([3], $this->reihenfolge(2));
    }

    public function testHauptbildBleibtHauptbild(): void
    {
        reorderItemImages(1, [2, 1]);
        $this->assertSame(1, $this->zahl("SELECT is_primary FROM item_images WHERE id = 1"));
        $this->assertSame(0, $this->zahl("SELECT is_primary FROM item_images WHERE id = 2"));
    }
}
