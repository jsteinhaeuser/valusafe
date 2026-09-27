/*
 * Kontextsensitive Hilfe — ValuSafe
 * Zeigt hilfreiche Tipps auf jeder Seite
 * Sprache folgt der App-Sprache (data-lang am <html>-Tag)
 */

function getHelpLang() {
    return (document.documentElement.getAttribute('data-lang') || 'de').substring(0, 2);
}

const helpTexts = {

    'index.php': {
        de: { title: '📋 Gegenstandsliste', content: `
            <h4>Übersicht aller Gegenstände</h4>
            <h5>🔍 Suchen & Filtern</h5>
            <ul>
                <li><strong>Suchfeld:</strong> Name oder Notizen durchsuchen</li>
                <li><strong>Kategorie / Raum:</strong> Filter-Chips oben verwenden</li>
                <li><strong>Spalten:</strong> Über den Spalten-Button auswählen</li>
            </ul>
            <h5>↕ Sortierung</h5>
            <ul>
                <li>Klick auf Spaltenüberschrift zum Sortieren</li>
                <li>Manuell: per Drag & Drop (Griffpunkte links)</li>
            </ul>
            <h5>📊 Export</h5>
            <ul>
                <li><strong>CSV:</strong> Für Excel/LibreOffice</li>
                <li><strong>PDF:</strong> Mit Bildern, ideal für Versicherungen</li>
            </ul>` },
        en: { title: '📋 Items list', content: `
            <h4>Overview of all items</h4>
            <h5>🔍 Search & Filter</h5>
            <ul>
                <li><strong>Search:</strong> Search by name or notes</li>
                <li><strong>Category / Room:</strong> Use filter chips at the top</li>
                <li><strong>Columns:</strong> Select via the Columns button</li>
            </ul>
            <h5>↕ Sorting</h5>
            <ul>
                <li>Click a column header to sort</li>
                <li>Manual: drag & drop using the handle on the left</li>
            </ul>
            <h5>📊 Export</h5>
            <ul>
                <li><strong>CSV:</strong> For Excel/LibreOffice</li>
                <li><strong>PDF:</strong> With images, ideal for insurance</li>
            </ul>` }
    },

    'add.php': {
        de: { title: '➕ Gegenstand hinzufügen', content: `
            <h4>Neuen Gegenstand anlegen</h4>
            <h5>📝 Pflichtfelder</h5>
            <ul><li><strong>Name:</strong> Eindeutiger Name (max. 255 Zeichen)</li></ul>
            <h5>📸 Bild</h5>
            <ul>
                <li>Formate: JPG, PNG, WebP — max. 5 MB</li>
                <li>Tipp: Kamera-Funktion auf dem Smartphone nutzen</li>
            </ul>
            <h5>🏷️ Barcode</h5>
            <ul><li>EAN/ISBN scannen oder eingeben für automatische Datenübernahme</li></ul>
            <h5>⌨️ Tastenkürzel</h5>
            <ul>
                <li><kbd>Strg+S</kbd> — Speichern</li>
                <li><kbd>ESC</kbd> — Abbrechen</li>
            </ul>` },
        en: { title: '➕ Add item', content: `
            <h4>Add a new item</h4>
            <h5>📝 Required fields</h5>
            <ul><li><strong>Name:</strong> Unique name (max. 255 characters)</li></ul>
            <h5>📸 Image</h5>
            <ul>
                <li>Formats: JPG, PNG, WebP — max. 5 MB</li>
                <li>Tip: use the camera on your smartphone</li>
            </ul>
            <h5>🏷️ Barcode</h5>
            <ul><li>Scan or enter EAN/ISBN for automatic data lookup</li></ul>
            <h5>⌨️ Shortcuts</h5>
            <ul>
                <li><kbd>Ctrl+S</kbd> — Save</li>
                <li><kbd>ESC</kbd> — Cancel</li>
            </ul>` }
    },

    'edit.php': {
        de: { title: '✏️ Gegenstand bearbeiten', content: `
            <h4>Gegenstand bearbeiten</h4>
            <h5>🔄 Änderungen</h5>
            <ul>
                <li>Alle Felder können geändert werden</li>
                <li><strong>Achtung:</strong> Alte Werte werden überschrieben</li>
            </ul>
            <h5>📸 Bild ersetzen</h5>
            <ul>
                <li>Altes Bild bleibt, wenn kein neues hochgeladen wird</li>
                <li>Neues Bild ersetzt und löscht das alte automatisch</li>
            </ul>
            <h5>💾 Speichern</h5>
            <ul><li><kbd>Strg+S</kbd> — Schnell speichern</li></ul>` },
        en: { title: '✏️ Edit item', content: `
            <h4>Edit item</h4>
            <h5>🔄 Changes</h5>
            <ul>
                <li>All fields can be edited</li>
                <li><strong>Note:</strong> Old values will be overwritten</li>
            </ul>
            <h5>📸 Replace image</h5>
            <ul>
                <li>Old image is kept if no new one is uploaded</li>
                <li>New image automatically replaces and deletes the old one</li>
            </ul>
            <h5>💾 Save</h5>
            <ul><li><kbd>Ctrl+S</kbd> — Quick save</li></ul>` }
    },

    'locations.php': {
        de: { title: '📍 Räume & Standorte', content: `
            <h4>Räume-Verwaltung</h4>
            <p>Drei Hierarchie-Ebenen: <strong>Räume → Standorte → Positionen</strong></p>
            <h5>🚪 Räume</h5>
            <ul>
                <li>Oberste Ebene (z.B. Wohnzimmer, Keller, Büro)</li>
                <li>Gesamtwert aller Gegenstände im Raum wird angezeigt</li>
            </ul>
            <h5>📦 Standorte</h5>
            <ul><li>Innerhalb eines Raums (z.B. Regal, Schrank, Vitrine)</li></ul>
            <h5>📌 Positionen</h5>
            <ul><li>Genaueste Ebene (z.B. Fach 3, Schublade links)</li></ul>
            <h5>🗑️ Löschen</h5>
            <ul><li>Nur möglich wenn keine Gegenstände zugewiesen sind</li></ul>` },
        en: { title: '📍 Rooms & Locations', content: `
            <h4>Location Management</h4>
            <p>Three hierarchy levels: <strong>Rooms → Locations → Positions</strong></p>
            <h5>🚪 Rooms</h5>
            <ul>
                <li>Top level (e.g. Living room, Basement, Office)</li>
                <li>Total value of all items in the room is shown</li>
            </ul>
            <h5>📦 Locations</h5>
            <ul><li>Within a room (e.g. Shelf, Cabinet, Display case)</li></ul>
            <h5>📌 Positions</h5>
            <ul><li>Most precise level (e.g. Shelf 3, Left drawer)</li></ul>
            <h5>🗑️ Delete</h5>
            <ul><li>Only possible when no items are assigned</li></ul>` }
    },

    'categories.php': {
        de: { title: '🏷️ Kategorien', content: `
            <h4>Kategorien verwalten</h4>
            <ul>
                <li>Namen eingeben und Erstellen klicken</li>
                <li>Löschen nur möglich wenn keine Gegenstände zugewiesen</li>
            </ul>
            <h5>💡 Empfehlungen</h5>
            <p>Elektronik · Schmuck · Möbel · Kunstwerk · Fahrzeug · Musikinstrument · Sportgerät</p>` },
        en: { title: '🏷️ Categories', content: `
            <h4>Manage categories</h4>
            <ul>
                <li>Enter a name and click Create</li>
                <li>Delete only possible when no items are assigned</li>
            </ul>
            <h5>💡 Suggestions</h5>
            <p>Electronics · Jewellery · Furniture · Artwork · Vehicle · Musical instrument · Sports equipment</p>` }
    },

    'settings.php': {
        de: { title: '⚙️ Einstellungen', content: `
            <h4>Persönliche Einstellungen</h4>
            <h5>🎨 Theme</h5>
            <ul><li>Wird in der Datenbank gespeichert und gilt geräteübergreifend</li></ul>
            <h5>🌐 Sprache</h5>
            <ul><li>9 Sprachen verfügbar — Änderung gilt sofort</li></ul>
            <h5>🔐 Passwort ändern</h5>
            <ul>
                <li>Aktuelles Passwort erforderlich</li>
                <li>Argon2id-Verschlüsselung</li>
            </ul>` },
        en: { title: '⚙️ Settings', content: `
            <h4>Personal settings</h4>
            <h5>🎨 Theme</h5>
            <ul><li>Stored in the database, applies across all devices</li></ul>
            <h5>🌐 Language</h5>
            <ul><li>9 languages available — change takes effect immediately</li></ul>
            <h5>🔐 Change password</h5>
            <ul>
                <li>Current password required</li>
                <li>Argon2id encryption</li>
            </ul>` }
    },

    'export_csv.php': {
        de: { title: '📊 CSV Export', content: `
            <h4>CSV Export</h4>
            <ul>
                <li>Tab-getrennte Werte — öffnet direkt in Excel/LibreOffice</li>
                <li>Enthält alle Felder außer Bilder</li>
                <li>Ideal als Datensicherung oder für Weiterverarbeitung</li>
            </ul>` },
        en: { title: '📊 CSV Export', content: `
            <h4>CSV Export</h4>
            <ul>
                <li>Tab-separated values — opens directly in Excel/LibreOffice</li>
                <li>Contains all fields except images</li>
                <li>Ideal as a data backup or for further processing</li>
            </ul>` }
    },

    'export_pdf.php': {
        de: { title: '📄 PDF Export', content: `
            <h4>PDF Export</h4>
            <ul>
                <li>Jeder Gegenstand mit Bild und allen Details</li>
                <li>Ideal für Versicherungsdokumentation</li>
            </ul>
            <h5>🖨️ So geht's</h5>
            <ol>
                <li>Seite im Browser öffnen</li>
                <li>Strg+P → "Als PDF speichern"</li>
            </ol>` },
        en: { title: '📄 PDF Export', content: `
            <h4>PDF Export</h4>
            <ul>
                <li>Each item with image and full details</li>
                <li>Ideal for insurance documentation</li>
            </ul>
            <h5>🖨️ How to</h5>
            <ol>
                <li>Open the page in your browser</li>
                <li>Ctrl+P → "Save as PDF"</li>
            </ol>` }
    },

    'gallery.php': {
        de: { title: '🖼️ Bild-Galerie', content: `
            <h4>Alle Bilder auf einen Blick</h4>
            <h5>📊 Statistiken</h5>
            <ul>
                <li><strong>Bilder gesamt:</strong> Anzahl aller hochgeladenen Bilder</li>
                <li><strong>Gesamtgröße:</strong> Gesamter Speicherverbrauch</li>
                <li><strong>Ø Dateigröße:</strong> Durchschnittliche Bildgröße</li>
            </ul>
            <h5>🔍 Ansichten</h5>
            <ul>
                <li><strong>Grid:</strong> Visuelle Kachelansicht</li>
                <li><strong>Liste:</strong> Detailtabelle mit Dateinamen und Größen</li>
            </ul>` },
        en: { title: '🖼️ Image Gallery', content: `
            <h4>All images at a glance</h4>
            <h5>📊 Statistics</h5>
            <ul>
                <li><strong>Total images:</strong> Number of all uploaded images</li>
                <li><strong>Total size:</strong> Combined storage used</li>
                <li><strong>Avg. file size:</strong> Average image size</li>
            </ul>
            <h5>🔍 Views</h5>
            <ul>
                <li><strong>Grid:</strong> Visual tile overview</li>
                <li><strong>List:</strong> Detail table with filenames and sizes</li>
            </ul>` }
    },

    'stats.php': {
        de: { title: '📈 Statistiken', content: `
            <h4>Auswertungen & Analysen</h4>
            <h5>📊 Top 10 Kategorien</h5>
            <ul><li>Rangfolge nach Gesamtwert · Anzahl · Ø Wert pro Gegenstand</li></ul>
            <h5>📍 Top 10 Orte</h5>
            <ul><li>Rangfolge nach gespeichertem Gesamtwert</li></ul>
            <h5>💎 Wertvollste Gegenstände</h5>
            <ul><li>Top 10 nach aktuellem Wert (Kaufpreis als Fallback)</li></ul>
            <h5>📈 Wertentwicklung</h5>
            <ul><li>Historischer Verlauf der eingetragenen Werte</li></ul>` },
        en: { title: '📈 Statistics', content: `
            <h4>Reports & Analysis</h4>
            <h5>📊 Top 10 Categories</h5>
            <ul><li>Ranked by total value · count · avg. value per item</li></ul>
            <h5>📍 Top 10 Locations</h5>
            <ul><li>Ranked by total stored value</li></ul>
            <h5>💎 Most Valuable Items</h5>
            <ul><li>Top 10 by current value (purchase price as fallback)</li></ul>
            <h5>📈 Value Trend</h5>
            <ul><li>Historical progression of recorded values</li></ul>` }
    },

    'insurance.php': {
        de: { title: '🛡️ Versicherungen', content: `
            <h4>Versicherungsübersicht</h4>
            <ul>
                <li>Alle Policen mit zugewiesenen Gegenständen</li>
                <li>Gesamtwert der versicherten Objekte auf einen Blick</li>
                <li>PDF-Export für Versicherungsgespräche</li>
            </ul>` },
        en: { title: '🛡️ Insurance', content: `
            <h4>Insurance overview</h4>
            <ul>
                <li>All policies with assigned items</li>
                <li>Total value of insured objects at a glance</li>
                <li>PDF export for insurance discussions</li>
            </ul>` }
    },

};

// Hilfe-System initialisieren
function initContextHelp() {
    const currentPage = getCurrentPage();
    if (!helpTexts[currentPage]) return;

    const lang = getHelpLang();
    const entry = helpTexts[currentPage][lang] || helpTexts[currentPage]['de'];
    if (!entry) return;

    const helpButton = document.createElement('button');
    helpButton.className = 'context-help-button';
    helpButton.innerHTML = 'ℹ';
    helpButton.title = lang === 'de' ? 'Hilfe anzeigen' : 'Show help';
    helpButton.onclick = () => showHelp(currentPage);
    document.body.appendChild(helpButton);
}

function getCurrentPage() {
    const path = window.location.pathname;
    return path.substring(path.lastIndexOf('/') + 1).replace('#','') || 'index.php';
}

function showHelp(page) {
    const lang = getHelpLang();
    const pageHelp = helpTexts[page];
    if (!pageHelp) return;
    const help = pageHelp[lang] || pageHelp['de'];
    if (!help) return;

    const modal = document.createElement('div');
    modal.className = 'help-modal';
    modal.innerHTML = `
        <div class="help-modal-content">
            <div class="help-modal-header">
                <h3>${help.title}</h3>
                <button class="help-modal-close" onclick="this.closest('.help-modal').remove()">✕</button>
            </div>
            <div class="help-modal-body">${help.content}</div>
            <div class="help-modal-footer">
                <button onclick="this.closest('.help-modal').remove()" style="padding:6px 16px; cursor:pointer;">
                    ${lang === 'de' ? 'Schließen' : 'Close'}
                </button>
            </div>
        </div>
        <div class="help-modal-backdrop" onclick="this.parentElement.remove()"></div>
    `;
    document.body.appendChild(modal);
}

// Styles
const helpStyles = `
    .context-help-button {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--vs-accent, #185fa5);
        color: #fff;
        border: none;
        font-size: 18px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.15s, background 0.15s;
    }
    .context-help-button:hover { transform: scale(1.1); }

    .help-modal {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .help-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.4);
    }
    .help-modal-content {
        position: relative;
        z-index: 1;
        background: #fff;
        border-radius: 12px;
        width: 520px;
        max-width: 92vw;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    }
    .help-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #eee;
    }
    .help-modal-header h3 { margin: 0; font-size: 16px; }
    .help-modal-close {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #666;
        padding: 0 4px;
    }
    .help-modal-body {
        padding: 16px 20px;
        overflow-y: auto;
        flex: 1;
        font-size: 14px;
        line-height: 1.6;
    }
    .help-modal-body h4 { margin: 0 0 10px; font-size: 15px; }
    .help-modal-body h5 { margin: 12px 0 4px; font-size: 13px; color: #444; }
    .help-modal-body ul, .help-modal-body ol { margin: 0; padding-left: 18px; }
    .help-modal-body li { margin-bottom: 3px; }
    .help-modal-body kbd {
        background: #f0f0f0;
        border: 1px solid #ccc;
        border-radius: 3px;
        padding: 1px 5px;
        font-family: monospace;
        font-size: 12px;
    }
    .help-modal-footer {
        padding: 12px 20px;
        border-top: 1px solid #eee;
        text-align: right;
    }
    @media (max-width: 600px) {
        .context-help-button { width: 44px; height: 44px; bottom: 20px; right: 20px; }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = helpStyles;
document.head.appendChild(styleSheet);

// Initialisierung — sofort wenn DOM bereit, sonst auf Event warten
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initContextHelp);
} else {
    initContextHelp();
}
