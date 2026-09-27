<!-- ============================================
     COLUMN MANAGER - PHASE 1
     Sortieren + Umbenennen
     ============================================ -->

<?php
// Spalten-Daten laden
$userSpalten = getUserSpaltenAuswahl();
$availableSpalten = getAvailableSpalten();

// Reihenfolge aus User-Settings oder Standard
$spaltenOrder = $_SESSION['spalten_order'] ?? array_keys($availableSpalten);
$spaltenLabels = $_SESSION['spalten_labels'] ?? [];

// Neue Spalten die noch nicht in der gespeicherten Reihenfolge sind, anhängen
foreach (array_keys($availableSpalten) as $newSpalteId) {
    if (!in_array($newSpalteId, $spaltenOrder)) {
        $spaltenOrder[] = $newSpalteId;
    }
}
?>

<div class="column-manager-modal" id="columnManagerModal">
    <div class="column-manager-overlay"></div>
    <div class="column-manager-content">
        <div class="column-manager-header">
            <h3>📊 Spalten konfigurieren</h3>
            <button type="button" class="column-manager-close" id="closeColumnManager">×</button>
        </div>
        
        <div class="column-manager-body">
            <!-- Sortierbare Spalten-Liste -->
            <ul class="column-list" id="columnList">
                <?php foreach ($spaltenOrder as $spalteId): ?>
                    <?php if (isset($availableSpalten[$spalteId])): ?>
                        <?php 
                        $spalte = $availableSpalten[$spalteId];
                        $isVisible = $userSpalten[$spalteId] ?? false;
                        $customLabel = $spaltenLabels[$spalteId] ?? $spalte['label'];
                        $isStandard = in_array($spalteId, ['name', 'aktionen']); // Nicht umbenennen
                        ?>
                        <li class="column-item" data-column-id="<?php echo $spalteId; ?>">
                            <!-- Drag Handle -->
                            <span class="column-drag-handle">≡</span>
                            
                            <!-- Icon -->
                            <span class="column-icon"><?php echo $spalte['icon']; ?></span>
                            
                            <!-- Label (Editable) -->
                            <span class="column-label" 
                                  data-column-id="<?php echo $spalteId; ?>"
                                  data-original="<?php echo htmlspecialchars($spalte['label']); ?>"
                                  <?php echo !$isStandard ? 'data-editable="true"' : ''; ?>>
                                <?php echo htmlspecialchars($customLabel); ?>
                            </span>
                            
                            <?php if ($isStandard): ?>
                                <span class="column-badge">Standard</span>
                            <?php endif; ?>
                            
                            <!-- Visibility Toggle -->
                            <label class="column-toggle">
                                <input type="checkbox" 
                                       class="column-visibility"
                                       aria-label="<?php echo htmlspecialchars($customLabel); ?> ein-/ausblenden"
                                       data-column-id="<?php echo $spalteId; ?>"
                                       <?php echo $isVisible ? 'checked' : ''; ?>
                                       <?php echo $spalte['required'] ? 'disabled' : ''; ?>>
                                <span class="toggle-icon"><?php echo $isVisible ? '👁' : '👁‍🗨'; ?></span>
                            </label>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            
            <div class="column-manager-info">
                💡 <strong>Tipp:</strong> Doppelklick auf Namen zum Umbenennen · Ziehen zum Sortieren
            </div>
        </div>
        
        <div class="column-manager-footer">
            <button type="button" class="btn btn-secondary" id="resetColumns">
                🔄 Zurücksetzen
            </button>
            <button type="button" class="btn btn-primary" id="saveColumns">
                💾 Speichern
            </button>
        </div>
    </div>
</div>

<style>
/* Column Manager Modal */
.column-manager-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
}

.column-manager-modal.active {
    display: block;
}

.column-manager-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    animation: fadeIn 0.3s ease;
}

.column-manager-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    animation: cmSlideUp 0.3s ease;
}

@keyframes cmSlideUp {
    from {
        opacity: 0;
        transform: translate(-50%, -45%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

/* Header */
.column-manager-header {
    padding: 20px 24px;
    border-bottom: 2px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.column-manager-header h3 {
    margin: 0;
    font-size: 20px;
    color: #333;
}

.column-manager-close {
    background: #f0f0f0;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.column-manager-close:hover {
    background: #e0e0e0;
    transform: rotate(90deg);
}

/* Body */
.column-manager-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
}

/* Column List */
.column-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.column-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 8px;
    transition: all 0.2s;
    cursor: move;
}

.column-item:hover {
    background: #e9ecef;
}

.column-item.sortable-ghost {
    opacity: 0.4;
    background: #dee2e6;
}

.column-item.sortable-drag {
    opacity: 1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.column-item.drag-over {
    border-top: 3px solid #667eea;
    margin-top: 8px;
}

/* Drag Handle */
.column-drag-handle {
    font-size: 18px;
    color: #999;
    cursor: grab;
    user-select: none;
}

.column-drag-handle:active {
    cursor: grabbing;
}

/* Icon */
.column-icon {
    font-size: 20px;
}

/* Label */
.column-label {
    flex: 1;
    font-size: 15px;
    font-weight: 500;
    color: #333;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.column-label[data-editable="true"] {
    cursor: pointer;
}

.column-label[data-editable="true"]:hover {
    background: white;
}

.column-label.editing {
    background: white;
    border: 2px solid #667eea;
    outline: none;
}

/* Badge */
.column-badge {
    font-size: 11px;
    padding: 2px 8px;
    background: #e9ecef;
    border-radius: 12px;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
}

/* Toggle */
.column-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.column-toggle input {
    display: none;
}

.toggle-icon {
    font-size: 20px;
    transition: all 0.2s;
}

.column-toggle input:checked + .toggle-icon {
    opacity: 1;
}

.column-toggle input:not(:checked) + .toggle-icon {
    opacity: 0.3;
}

.column-toggle input:disabled + .toggle-icon {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Info */
.column-manager-info {
    margin-top: 16px;
    padding: 12px;
    background: #e7f3ff;
    border-radius: 8px;
    font-size: 13px;
    color: #0066cc;
}

/* Footer */
.column-manager-footer {
    padding: 16px 24px;
    border-top: 2px solid #eee;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.column-manager-footer .btn {
    padding: 10px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5568d3;
}

.btn-secondary {
    background: #e9ecef;
    color: #495057;
}

.btn-secondary:hover {
    background: #dee2e6;
}

/* Mobile */
@media (max-width: 768px) {
    .column-manager-content {
        width: 95%;
        max-height: 90vh;
    }
    
    .column-manager-header,
    .column-manager-body,
    .column-manager-footer {
        padding: 16px;
    }
    
    .column-item {
        gap: 8px;
    }
}
</style>

<script>
// Column Manager - Phase 1 (OHNE externe Libraries)
class ColumnManager {
    constructor() {
        this.modal = document.getElementById('columnManagerModal');
        this.columnList = document.getElementById('columnList');
        this.draggedElement = null;
        this.init();
    }
    
    init() {
        // Native Drag & Drop initialisieren
        this.initDragAndDrop();
        
        // Event Listeners
        document.getElementById('closeColumnManager')?.addEventListener('click', () => this.close());
        this.modal.querySelector('.column-manager-overlay')?.addEventListener('click', () => this.close());
        document.getElementById('saveColumns')?.addEventListener('click', () => this.save());
        document.getElementById('resetColumns')?.addEventListener('click', () => this.reset());
        
        // Inline-Edit für Labels
        document.querySelectorAll('.column-label[data-editable="true"]').forEach(label => {
            label.addEventListener('dblclick', (e) => this.startEdit(e.target));
        });
        
        // Visibility Toggles
        document.querySelectorAll('.column-visibility').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updatePreview());
        });
    }
    
    initDragAndDrop() {
        const items = this.columnList.querySelectorAll('.column-item');
        
        items.forEach(item => {
            item.draggable = true;
            
            item.addEventListener('dragstart', (e) => {
                this.draggedElement = item;
                item.classList.add('sortable-drag');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            item.addEventListener('dragend', (e) => {
                item.classList.remove('sortable-drag');
                this.columnList.querySelectorAll('.column-item').forEach(i => {
                    i.classList.remove('drag-over');
                });
            });
            
            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (item !== this.draggedElement) {
                    item.classList.add('drag-over');
                }
            });
            
            item.addEventListener('dragleave', (e) => {
                item.classList.remove('drag-over');
            });
            
            item.addEventListener('drop', (e) => {
                e.preventDefault();
                item.classList.remove('drag-over');
                
                if (item !== this.draggedElement) {
                    const allItems = [...this.columnList.querySelectorAll('.column-item')];
                    const draggedIndex = allItems.indexOf(this.draggedElement);
                    const targetIndex = allItems.indexOf(item);
                    
                    if (draggedIndex < targetIndex) {
                        item.parentNode.insertBefore(this.draggedElement, item.nextSibling);
                    } else {
                        item.parentNode.insertBefore(this.draggedElement, item);
                    }
                }
            });
        });
    }
    
    open() {
        this.modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    close() {
        this.modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    startEdit(labelElement) {
        if (labelElement.classList.contains('editing')) return;
        
        const originalText = labelElement.textContent;
        labelElement.classList.add('editing');
        labelElement.contentEditable = true;
        labelElement.focus();
        
        // Selektiere Text
        const range = document.createRange();
        range.selectNodeContents(labelElement);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
        
        const finishEdit = (save) => {
            labelElement.classList.remove('editing');
            labelElement.contentEditable = false;
            
            if (!save || !labelElement.textContent.trim()) {
                labelElement.textContent = originalText;
            }
        };
        
        labelElement.addEventListener('blur', () => finishEdit(true), { once: true });
        labelElement.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                finishEdit(true);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                finishEdit(false);
            }
        });
    }
    
    updatePreview() {
        // Könnte hier Live-Preview implementieren
    }
    
    save() {
        // Reihenfolge sammeln
        const order = [];
        this.columnList.querySelectorAll('.column-item').forEach(item => {
            order.push(item.dataset.columnId);
        });
        
        // Sichtbarkeit sammeln
        const visibility = {};
        document.querySelectorAll('.column-visibility').forEach(checkbox => {
            visibility[checkbox.dataset.columnId] = checkbox.checked;
        });
        
        // Labels sammeln
        const labels = {};
        document.querySelectorAll('.column-label[data-editable="true"]').forEach(label => {
            const columnId = label.dataset.columnId;
            const customLabel = label.textContent.trim();
            const originalLabel = label.dataset.original;
            
            if (customLabel !== originalLabel) {
                labels[columnId] = customLabel;
            }
        });
        
        // An Server senden
        fetch('ajax_save_columns.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order: order,
                visibility: visibility,
                labels: labels
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.close();
                location.reload(); // Seite neu laden um Änderungen zu sehen
            } else {
                vsAlert(<?php echo json_encode(t('error_save_prefix')); ?>.replace('%s', function () { return data.error || <?php echo json_encode(t('error_unknown')); ?>; }));
            }
        })
        .catch(err => {
            vsAlert(<?php echo json_encode(t('error_save_prefix')); ?>.replace('%s', function () { return err.message; }));
        });
    }
    
    reset() {
        vsConfirm(<?php echo json_encode(t('index_confirm_reset_columns')); ?>).then(function (ja) {
            if (!ja) return;
            fetch('ajax_reset_columns.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
        });
    }
}

// Initialisieren
window.columnManager = new ColumnManager();

// Öffnen via Button (sowohl der versteckte als auch der in der Suche)
document.getElementById('columnsSelectorBtn')?.addEventListener('click', (e) => {
    e.preventDefault();
    window.columnManager.open();
});

// Auch den Button in der Suche verbinden
document.getElementById('columnSettings')?.addEventListener('click', (e) => {
    e.preventDefault();
    window.columnManager.open();
});
</script>
