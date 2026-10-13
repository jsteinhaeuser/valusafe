/**
 * multi_image_upload.js
 * Multi-Image Upload, Galerie, Lightbox, Drag & Drop Sort
 */

// Texte in der Sprache des Benutzers (components/js_texte.php, seit 4.3.34);
// der zweite Wert ist der deutsche Rueckfall.
const _miuT = (k, f, ...a) => (window.vsT ? window.vsT(k, f, ...a)
    : a.reduce((s, v) => s.replace('%s', v), f));
const _miuE = (s) => (window.vsEsc ? window.vsEsc(s) : String(s));

class MultiImageUpload {
    constructor(itemId, csrfToken, options = {}) {
        this.itemId = itemId;
        this.csrfToken = csrfToken;
        this.maxImages = options.maxImages || 10;
        this.images = [];
        this.lightboxIndex = 0;
        this.dragSrcIndex = null;
        
        this.container = document.getElementById('multi-image-container');
        if (!this.container) return;
        
        this.render();
        this.loadImages();
    }
    
    // =============================================
    // RENDER: Grundstruktur
    // =============================================
    render() {
        this.container.innerHTML = `
            <!-- Bild-Anzahl -->
            <div class="image-count-badge" id="imageCountBadge">
                📷 ${_miuT('js_img_count', '%s von %s Bildern', '<span id="imageCount">0</span>', this.maxImages)}
            </div>
            
            <!-- Galerie -->
            <div class="image-gallery" id="imageGallery">
                <div class="image-gallery-empty" id="imageGalleryEmpty">
                    ${_miuE(_miuT('js_img_none', 'Noch keine Bilder vorhanden'))}
                </div>
            </div>
            
            <!-- Upload Zone -->
            <div class="multi-upload-zone" id="multiUploadZone">
                <div class="multi-upload-zone-icon" aria-hidden="true">📸</div>
                <div class="multi-upload-zone-text">${_miuE(_miuT('js_img_upload', 'Bilder hochladen'))}</div>
                <div class="multi-upload-zone-hint">
                    ${_miuE(_miuT('js_img_hint', 'Mehrere Bilder auswählen oder hierher ziehen'))}<br>
                    ${_miuE(_miuT('js_img_limits', 'JPG, PNG, WebP, GIF'))}
                </div>
                <!-- Input ist außerhalb der Zone um Bubbling zu verhindern -->
            </div>
            
            <!-- Versteckter File-Input: außerhalb der Zone, kein Bubbling -->
            <input type="file" id="multiFileInput" aria-label="${_miuE(_miuT('js_img_select', 'Bilder auswählen'))}"
                   accept="image/*" multiple
                   style="display:none;">
            
            <!-- Kamera-Button (nur Mobile) -->
            <div class="camera-options" id="multiCameraOptions" style="display:none;">
                <button type="button" onclick="multiImageUpload.openCamera()">📷 ${_miuE(_miuT('js_img_camera', 'Foto aufnehmen'))}</button>
                <button type="button" onclick="document.getElementById('multiFileInput').click()">🖼️ ${_miuE(_miuT('js_img_gallery', 'Aus Galerie'))}</button>
            </div>
            
            <!-- Progress -->
            <div class="upload-progress-container" id="uploadProgress">
                <div class="upload-progress-bar">
                    <div class="upload-progress-fill" id="uploadProgressFill"></div>
                </div>
                <div class="upload-progress-text" id="uploadProgressText" role="status">${_miuE(_miuT('js_img_uploading', 'Lade hoch …'))}</div>
            </div>
            
            <!-- Errors -->
            <div class="upload-errors" id="uploadErrors"></div>
            
        `;
        
        // Lightbox AUSSERHALB aller Forms an body anhängen
        // Entferne zuerst eventuelle alte Instanz aus dem Container
        const existingInContainer = this.container.querySelector('#lightboxOverlay');
        if (existingInContainer) existingInContainer.remove();

        if (!document.getElementById('lightboxOverlay')) {
            const lb = document.createElement('div');
            lb.className = 'lightbox-overlay';
            lb.id = 'lightboxOverlay';
            lb.innerHTML = `
                <div class="lightbox-content">
                    <button type="button" class="lightbox-close" onclick="multiImageUpload.closeLightbox()" aria-label="${_miuE(_miuT('js_close', 'Schließen'))}">✕</button>
                    <button type="button" class="lightbox-nav lightbox-prev" onclick="event.preventDefault();event.stopPropagation();multiImageUpload.lightboxPrev();" aria-label="${_miuE(_miuT('js_prev', 'Vorheriges Bild'))}">‹</button>
                    <img id="lightboxImg" src="" alt="">
                    <button type="button" class="lightbox-nav lightbox-next" onclick="event.preventDefault();event.stopPropagation();multiImageUpload.lightboxNext();" aria-label="${_miuE(_miuT('js_next', 'Nächstes Bild'))}">›</button>
                    <div class="lightbox-counter" id="lightboxCounter"></div>
                </div>`;
            document.body.appendChild(lb);
        }
        
        this.bindEvents();
    }
    
    // =============================================
    // EVENTS
    // =============================================
    bindEvents() {
        // File Input
        const fileInput = document.getElementById('multiFileInput');
        fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
            e.target.value = ''; // Reset für erneuten Upload
        });
        
        // Drag & Drop auf Upload Zone
        const uploadZone = document.getElementById('multiUploadZone');
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('drag-over');
        });
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('drag-over');
        });
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');
            this.handleFiles(e.dataTransfer.files);
        });
        
        // Lightbox schließen bei Klick außerhalb
        document.getElementById('lightboxOverlay').addEventListener('click', (e) => {
            if (e.target === document.getElementById('lightboxOverlay')) {
                this.closeLightbox();
            }
        });
        
        // Keyboard Navigation
        document.addEventListener('keydown', (e) => {
            if (!document.getElementById('lightboxOverlay').classList.contains('active')) return;
            if (e.key === 'Escape') this.closeLightbox();
            if (e.key === 'ArrowLeft') this.lightboxPrev();
            if (e.key === 'ArrowRight') this.lightboxNext();
        });
        
        // Kamera-Button nur auf Mobile anzeigen
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        if (isMobile) {
            const cameraOptions = document.getElementById('multiCameraOptions');
            if (cameraOptions) cameraOptions.style.display = 'flex';
            // Upload Zone Click auf Mobile öffnet Kamera direkt
            uploadZone.addEventListener('click', (e) => {
                if (e.target.tagName === 'INPUT') return;
                this.openCamera();
            });
        } else {
            uploadZone.addEventListener('click', (e) => {
                if (e.target.tagName === 'INPUT') return;
                document.getElementById('multiFileInput').click();
            });
        }
    }
    
    // Kamera öffnen (Mobile)
    openCamera() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.capture = 'environment'; // Rückkamera
        input.onchange = (e) => {
            if (e.target.files.length > 0) {
                this.handleFiles(e.target.files);
            }
        };
        input.click();
    }
    
    // =============================================
    // LOAD: Bilder laden
    // =============================================
    async loadImages() {
        try {
            // Cache-Busting: Timestamp verhindert Firefox-Cache-Problem
            const ts = Date.now();
            const response = await fetch(
                `image_manager.php?action=get_images&item_id=${this.itemId}&_=${ts}`,
                { cache: 'no-store' }
            );
            const data = await response.json();
            
            if (data.success) {
                this.images = data.images;
                this.renderGallery();
            }
        } catch (err) {
            console.error('Fehler beim Laden der Bilder:', err);
        }
    }
    
    // =============================================
    // GALLERY: Rendern
    // =============================================
    renderGallery() {
        const gallery = document.getElementById('imageGallery');
        const empty = document.getElementById('imageGalleryEmpty');
        const count = document.getElementById('imageCount');
        
        // Zähler updaten
        count.textContent = this.images.length;
        
        if (this.images.length === 0) {
            gallery.innerHTML = '';
            gallery.appendChild(empty || this.createEmptyState());
            return;
        }
        
        // Galerie rendern
        gallery.innerHTML = this.images.map((img, index) => `
            <div class="image-gallery-item ${img.is_primary ? 'is-primary' : ''}" 
                 data-id="${img.id}" 
                 data-index="${index}"
                 draggable="true">
                ${img.is_primary ? '<div class="primary-badge">★ ' + _miuE(_miuT('js_img_primary', 'Hauptbild')) + '</div>' : ''}
                <img src="${img.url}" 
                     alt="${_miuE(_miuT('js_img_alt', 'Bild %s', index + 1))}"
                     onclick="multiImageUpload.openLightbox(${index})"
                     loading="lazy">
                <div class="image-actions">
                    ${!img.is_primary ? `
                    <button type="button" class="image-action-btn primary-btn" 
                            onclick="multiImageUpload.setPrimary(${img.id})"
                            title="${_miuE(_miuT('js_img_set_primary', 'Als Hauptbild setzen'))}" aria-label="${_miuE(_miuT('js_img_set_primary', 'Als Hauptbild setzen'))}">★</button>
                    ` : ''}
                    ${window.vsBildZuschnitt ? `
                    <button type="button" class="image-action-btn crop-btn"
                            onclick="multiImageUpload.cropImage(${img.id})"
                            title="${_miuE(_miuT('js_crop_button', 'Zuschneiden / drehen'))}" aria-label="${_miuE(_miuT('js_crop_button', 'Zuschneiden / drehen'))}"><i class="ti ti-crop" aria-hidden="true"></i></button>
                    ` : ''}
                    <button type="button" class="image-action-btn delete-btn" 
                            onclick="multiImageUpload.deleteImage(${img.id})"
                            title="${_miuE(_miuT('js_img_delete', 'Bild löschen'))}" aria-label="${_miuE(_miuT('js_img_delete', 'Bild löschen'))}">🗑️</button>
                </div>
            </div>
        `).join('');
        
        // Drag & Drop für Sortierung
        this.setupDragSort();
        
        // Upload Zone ausblenden wenn voll
        const uploadZone = document.getElementById('multiUploadZone');
        uploadZone.style.display = this.images.length >= this.maxImages ? 'none' : 'block';
    }
    
    createEmptyState() {
        const div = document.createElement('div');
        div.className = 'image-gallery-empty';
        div.id = 'imageGalleryEmpty';
        div.textContent = _miuT('js_img_none', 'Noch keine Bilder vorhanden');
        return div;
    }
    
    // =============================================
    // UPLOAD
    // =============================================
    async handleFiles(files) {
        if (!files || files.length === 0) return;

        // FileList sofort in Array kopieren — e.target.value='' löscht die
        // live FileList bevor das erste await zurückkommt (async/await-Timing)
        const fileArray = Array.from(files);

        // Prüfe Limit
        const remaining = this.maxImages - this.images.length;
        if (remaining <= 0) {
            this.showError(_miuE(_miuT('js_img_max', 'Maximum von %s Bildern erreicht', this.maxImages)));
            return;
        }
        
        // Progress anzeigen
        this.showProgress(0, _miuT('js_img_uploading', 'Lade hoch …'));
        this.hideError();
        
        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'upload');
        formData.append('item_id', this.itemId);
        
        // Füge Dateien hinzu (max. remaining) — mit client-seitiger Komprimierung
        let added = 0;
        for (const file of fileArray) {
            if (added >= remaining) break;
            const processed = await MultiImageUpload.compressIfNeeded(file, (msg) => {
                this.showProgress(10 + added * 5, msg);
            });
            formData.append('images[]', processed);
            added++;
        }

        try {
            // Progress
            this.showProgress(30, _miuT('js_img_uploading_n', 'Lade %s Bild(er) hoch …', added));
            
            const response = await fetch('image_manager.php', {
                method: 'POST',
                body: formData
            });
            
            this.showProgress(80, _miuT('js_img_processing', 'Verarbeite …'));
            const data = await response.json();
            
            this.showProgress(100, _miuT('js_img_done', 'Fertig!'));
            
            if (data.uploaded && data.uploaded.length > 0) {
                this.images = [...this.images, ...data.uploaded];
                this.renderGallery();
                
                // Haptic Feedback
                if (navigator.vibrate) navigator.vibrate(50);
            }
            
            if (data.errors && data.errors.length > 0) {
                // Fehlertexte koennen den Dateinamen enthalten (image_manager.php) - maskieren
                this.showError(data.errors.map(_miuE).join('<br>'));
            }
            
        } catch (err) {
            this.showError(_miuE(_miuT('js_img_upload_failed', 'Upload fehlgeschlagen: %s', err.message)));
        } finally {
            setTimeout(() => this.hideProgress(), 1500);
        }
    }
    
    // =============================================
    // DELETE
    // =============================================
    async deleteImage(imageId) {
        // imageId als Number sicherstellen (JSON liefert Strings, onclick Numbers)
        imageId = parseInt(imageId, 10);

        // deleteImage ist async, deshalb reicht hier await — der Ablauf
        // darunter bleibt unveraendert.
        const ja = await vsConfirm((window.VS_I18N && window.VS_I18N.confirm_delete_image) || 'Bild wirklich löschen?');
        if (!ja) return;

        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'delete');
        formData.append('item_id', this.itemId);
        formData.append('image_id', imageId);

        try {
            const response = await fetch('image_manager.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                // Server gibt aktualisierte Bildliste zurück (kein Cache-Problem)
                if (data.images) {
                    // IDs als Number normalisieren
                    this.images = data.images.map(img => ({
                        ...img,
                        id: parseInt(img.id, 10)
                    }));
                    this.renderGallery();
                } else {
                    // Fallback: lokal filtern mit korrektem Typ-Vergleich
                    this.images = this.images.filter(img => parseInt(img.id, 10) !== imageId);
                    if (data.new_primary) {
                        this.images = this.images.map(img => ({
                            ...img,
                            is_primary: parseInt(img.id, 10) === parseInt(data.new_primary, 10)
                        }));
                    }
                    this.renderGallery();
                }
                if (navigator.vibrate) navigator.vibrate(30);
            } else {
                this.showError(_miuE(data.error || _miuT('js_img_delete_failed', 'Löschen fehlgeschlagen')));
            }
        } catch (err) {
            this.showError(_miuE(_miuT('js_error_prefix', 'Fehler: %s', err.message)));
        }
    }


    // =============================================
    // ZUSCHNEIDEN (4.4.1): gespeichertes Bild zuschneiden/drehen und auf dem
    // Server ersetzen (image_manager.php action=replace). Reihenfolge und
    // Hauptbild bleiben, die Datei bekommt einen neuen Namen.
    // =============================================
    async cropImage(imageId) {
        imageId = parseInt(imageId, 10);
        const img = this.images.find(i => parseInt(i.id, 10) === imageId);
        if (!img || !img.url || !window.vsBildZuschnitt) return;

        let blob;
        try {
            blob = await window.vsBildZuschnitt.oeffnen(img.url);
        } catch (err) {
            this.showError(_miuE(_miuT('js_crop_failed', 'Zuschnitt fehlgeschlagen: %s', err.message)));
            return;
        }
        if (!blob) return;   // abgebrochen

        this.hideError();
        this.showProgress(40, _miuT('js_crop_saving', 'Speichere Zuschnitt …'));
        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'replace');
        formData.append('item_id', this.itemId);
        formData.append('image_id', imageId);
        formData.append('image', new File([blob], 'zuschnitt.jpg', { type: 'image/jpeg' }));

        try {
            const response = await fetch('image_manager.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success && data.images) {
                this.images = data.images.map(i => ({ ...i, id: parseInt(i.id, 10) }));
                this.renderGallery();
                this.showProgress(100, _miuT('js_img_done', 'Fertig!'));
                // Fokus zurueck auf den Knopf des ersetzten Bildes (die Galerie wurde neu gebaut)
                const knopf = document.querySelector('.image-gallery-item[data-id="' + imageId + '"] .crop-btn');
                if (knopf) knopf.focus();
            } else {
                this.showError(_miuE(data.error || _miuT('js_crop_failed', 'Zuschnitt fehlgeschlagen: %s', '')));
            }
        } catch (err) {
            this.showError(_miuE(_miuT('js_crop_failed', 'Zuschnitt fehlgeschlagen: %s', err.message)));
        } finally {
            setTimeout(() => this.hideProgress(), 1200);
        }
    }

    // =============================================
    // SET PRIMARY
    // =============================================
    async setPrimary(imageId) {
        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'set_primary');
        formData.append('item_id', this.itemId);
        formData.append('image_id', imageId);
        
        try {
            const response = await fetch('image_manager.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                this.images = this.images.map(img => ({
                    ...img,
                    is_primary: img.id === imageId
                }));
                this.renderGallery();
                if (navigator.vibrate) navigator.vibrate(30);
            } else {
                this.showError(_miuE(data.error || _miuT('js_img_primary_failed', 'Hauptbild konnte nicht gesetzt werden')));
            }
        } catch (err) {
            this.showError(_miuE(_miuT('js_error_prefix', 'Fehler: %s', err.message)));
        }
    }
    
    // =============================================
    // DRAG & DROP SORT
    // =============================================
    setupDragSort() {
        const items = document.querySelectorAll('.image-gallery-item');
        
        items.forEach((item, index) => {
            item.addEventListener('dragstart', (e) => {
                this.dragSrcIndex = index;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
                document.querySelectorAll('.image-gallery-item').forEach(i => {
                    i.classList.remove('drag-over');
                });
            });
            
            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                item.classList.add('drag-over');
            });
            
            item.addEventListener('dragleave', () => {
                item.classList.remove('drag-over');
            });
            
            item.addEventListener('drop', async (e) => {
                e.preventDefault();
                item.classList.remove('drag-over');
                
                const targetIndex = index;
                if (this.dragSrcIndex === targetIndex) return;
                
                // Tausche Bilder
                const newImages = [...this.images];
                const [moved] = newImages.splice(this.dragSrcIndex, 1);
                newImages.splice(targetIndex, 0, moved);
                this.images = newImages;
                
                this.renderGallery();
                
                // Speichere neue Reihenfolge
                await this.saveOrder();
            });
        });
    }
    
    async saveOrder() {
        const imageIds = this.images.map(img => img.id);
        
        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'reorder');
        formData.append('item_id', this.itemId);
        imageIds.forEach(id => formData.append('image_ids[]', id));
        
        try {
            await fetch('image_manager.php', { method: 'POST', body: formData });
        } catch (err) {
            console.error('Reihenfolge speichern fehlgeschlagen:', err);
        }
    }
    
    // =============================================
    // LIGHTBOX
    // =============================================
    openLightbox(index) {
        this.lightboxIndex = index;
        this.updateLightbox();
        document.getElementById('lightboxOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    closeLightbox() {
        document.getElementById('lightboxOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    lightboxPrev() {
        this.lightboxIndex = (this.lightboxIndex - 1 + this.images.length) % this.images.length;
        this.updateLightbox();
    }
    
    lightboxNext() {
        this.lightboxIndex = (this.lightboxIndex + 1) % this.images.length;
        this.updateLightbox();
    }
    
    updateLightbox() {
        const img = document.getElementById('lightboxImg');
        const counter = document.getElementById('lightboxCounter');
        const current = this.images[this.lightboxIndex];
        
        if (current) {
            img.src = current.url;
            counter.textContent = `${this.lightboxIndex + 1} / ${this.images.length}`;
        }
        
        // Nav Buttons ausblenden wenn nur 1 Bild
        document.querySelector('.lightbox-prev').style.display = 
            this.images.length > 1 ? 'block' : 'none';
        document.querySelector('.lightbox-next').style.display = 
            this.images.length > 1 ? 'block' : 'none';
    }
    
    // =============================================
    // PROGRESS & ERROR
    // =============================================
    showProgress(percent, text) {
        document.getElementById('uploadProgress').style.display = 'block';
        document.getElementById('uploadProgressFill').style.width = percent + '%';
        document.getElementById('uploadProgressText').textContent = text;
    }
    
    hideProgress() {
        document.getElementById('uploadProgress').style.display = 'none';
        document.getElementById('uploadProgressFill').style.width = '0%';
    }
    
    showError(message) {
        const el = document.getElementById('uploadErrors');
        el.innerHTML = message;
        el.style.display = 'block';
    }
    
    hideError() {
        document.getElementById('uploadErrors').style.display = 'none';
    }
    // =============================================
    // BILDKOMPRIMIERUNG (client-seitig)
    // Ziel: max. 1.5 MB, max. 1920 px (längste Seite)
    // =============================================
    static async compressIfNeeded(file, onStatus = null) {
        const MAX_BYTES  = 1.5 * 1024 * 1024;
        const MAX_DIM    = 1920;
        const QUAL_START = 0.85;
        const QUAL_MIN   = 0.50;

        const ext = file.name.split('.').pop().toLowerCase();

        // GIF und HEIC/HEIF nicht komprimieren
        if (['gif', 'heic', 'heif'].includes(ext) || file.type === 'image/gif') return file;

        // Unter Limit: Original zurückgeben
        if (file.size <= MAX_BYTES) return file;

        if (onStatus) onStatus('🗜️ ' + _miuT('js_img_compressing', 'Komprimiere %s …', file.name));

        return new Promise((resolve) => {
            // FileReader statt createObjectURL: data:-URL ist CSP-konform
            const reader = new FileReader();
            reader.onerror = () => resolve(file);
            reader.onload = (ev) => {
                const img = new Image();
                img.onerror = () => resolve(file);
                img.onload = () => {
                    let width  = img.naturalWidth;
                    let height = img.naturalHeight;
                    if (width === 0 || height === 0) { resolve(file); return; }
                    if (width > MAX_DIM || height > MAX_DIM) {
                        const ratio = Math.min(MAX_DIM / width, MAX_DIM / height);
                        width  = Math.round(width  * ratio);
                        height = Math.round(height * ratio);
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width  = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    if (!ctx) { resolve(file); return; }
                    ctx.drawImage(img, 0, 0, width, height);

                    // WebP-Support prüfen (Safari-Fallback auf JPEG)
                    const testData = canvas.toDataURL('image/webp');
                    const format   = testData.startsWith('data:image/webp') ? 'image/webp' : 'image/jpeg';
                    const fileExt  = format === 'image/webp' ? '.webp' : '.jpg';

                    let quality = QUAL_START;
                    const tryCompress = () => {
                        canvas.toBlob(blob => {
                            if (!blob) { resolve(file); return; }
                            if (blob.size <= MAX_BYTES || quality <= QUAL_MIN) {
                                const baseName = file.name.replace(/\.[^/.]+$/, '');
                                resolve(new File([blob], baseName + fileExt, { type: format }));
                            } else {
                                quality = Math.max(quality - 0.08, QUAL_MIN);
                                tryCompress();
                            }
                        }, format, quality);
                    };
                    tryCompress();
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

}

// Globale Instanz
let multiImageUpload = null;