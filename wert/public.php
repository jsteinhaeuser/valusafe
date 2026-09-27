<?php
/**
 * public.php - Öffentliche Sammlungsansicht
 * Kein Login erforderlich, Token-gesichert
 * v3.15
 */

require_once 'db.php';
require_once 'RateLimiter.php';
// KEIN requireLogin() - bewusst öffentlich

$anfrage_result = null;

// Token aus URL holen und validieren
$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    http_response_code(404);
    die('Seite nicht gefunden.');
}

// Token in DB prüfen
try {
    $settings = [];
    $rows = $db->select("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'public_%'");
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $stored_token = $settings['public_token'] ?? null;

    if (empty($stored_token) || !hash_equals($stored_token, $token)) {
        http_response_code(404);
        die('Seite nicht gefunden.');
    }

    // ── Kaeufer-Anfrage verarbeiten ─────────────────────────────────────
    // Dieser Zweig stand bis zum 25.09.2026 VOR der Token-Pruefung, also am
    // Anfang der Datei. Wer die Adresse von public.php kannte, konnte damit
    // ohne gueltigen Token beliebig viele E-Mails an den Betreiber ausloesen
    // - der Token wurde erst 45 Zeilen spaeter geprueft. Gefunden beim
    // Durchsehen von ARCHITECTURE.md vor der Veroeffentlichung: die Zeile
    // stand dort seit dem 26.08.2026 als bekannte Baustelle und war noch
    // wahr. Jetzt laeuft er hinter der Pruefung und mit Begrenzung.
    $rateLimiter     = new RateLimiter($db);
    $anfrage_ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $anfrage_versuch = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['anfrage_item_id']);

    // Ohne Begrenzung ist dieses Formular ein Versandweg fuer beliebig viele
    // Mails an den Betreiber. RateLimiter ist dieselbe Klasse, die die Anmeldung
    // schuetzt; 'public_anfrage' ist ein eigener Zaehler, damit eine Flut von
    // Anfragen niemanden vom Anmelden aussperrt.
    if ($anfrage_versuch && $rateLimiter->isLocked($anfrage_ip, 'public_anfrage')) {
        $anfrage_result = ['status' => 'error'];
    } elseif ($anfrage_versuch) {
        $rateLimiter->recordAttempt($anfrage_ip, 'public_anfrage');
        $item_id    = (int)($_POST['anfrage_item_id'] ?? 0);
        $name       = trim($_POST['anfrage_name'] ?? '');
        $email      = trim($_POST['anfrage_email'] ?? '');
        $nachricht  = mb_substr(trim($_POST['anfrage_nachricht'] ?? ''), 0, 200);

        // Admin-Email laden
        $adminEmailFile = defined('BACKUP_DIR') ? BACKUP_DIR . 'admin_email.txt' : __DIR__ . '/backups/admin_email.txt';
        $adminEmail = file_exists($adminEmailFile) ? trim(file_get_contents($adminEmailFile)) : '';

        if (empty($adminEmail)) {
            $anfrage_result = ['status' => 'no_email'];
        } elseif (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $anfrage_result = ['status' => 'invalid'];
        } else {
            $item = $db->selectOne("SELECT name FROM wertsachen WHERE id = ? AND oeffentlich = 1", [$item_id]);
            $item_name = $item ? $item['name'] : 'Unbekannter Gegenstand';

            $subject = "Kaufinteresse: " . $item_name;
            $body  = "Jemand hat Interesse an einem Gegenstand aus deiner öffentlichen Sammlung bekundet.

    ";
            $body .= "Gegenstand: " . $item_name . "
    ";
            $body .= "Name: " . $name . "
    ";
            $body .= "E-Mail: " . $email . "
    ";
            if (!empty($nachricht)) {
                $body .= "Nachricht: " . $nachricht . "
    ";
            }
            $body .= "
    Diese Anfrage wurde über deinen öffentlichen Sammlungslink gesendet.";

            $headers  = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "
    ";
            $headers .= "Reply-To: " . $email . "
    ";
            $headers .= "Content-Type: text/plain; charset=UTF-8
    ";

            $sent = mail($adminEmail, $subject, $body, $headers);
            $anfrage_result = ['status' => $sent ? 'ok' : 'error', 'item' => $item_name];
        }
    }

    // Konfiguration
    $show_price    = ($settings['public_show_price'] ?? '0') === '1';
    $show_location = ($settings['public_show_location'] ?? '1') === '1';
    $page_title    = !empty($settings['public_title']) ? $settings['public_title'] : 'Meine Sammlung';
    $page_desc     = $settings['public_description'] ?? '';

    // Öffentliche Items laden
    $wertsachen = $db->select("
        SELECT w.*, 
               o.name AS ort_name,
               k.name AS kategorie_name
        FROM wertsachen w
        LEFT JOIN raeume o ON w.raum_id = o.id
        LEFT JOIN kategorien k ON w.kategorie_id = k.id
        WHERE w.oeffentlich = 1
          AND (w.hidden = 0 OR w.hidden IS NULL)
        ORDER BY k.name, w.name
    ");

    // Statistiken
    $total_items = count($wertsachen);
    $total_value = 0;
    if ($show_price) {
        foreach ($wertsachen as $item) {
            $total_value += floatval($item['preis'] ?? 0);
        }
    }

    // Items nach Kategorie gruppieren
    $grouped = [];
    foreach ($wertsachen as $item) {
        $cat = $item['kategorie_name'] ?? 'Sonstiges';
        $grouped[$cat][] = $item;
    }
    ksort($grouped);

} catch (PDOException $e) {
    http_response_code(500);
    die('Fehler beim Laden der Daten.');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:    #3b82f6;
            --primary-dk: #1d4ed8;
            --bg:         #f8fafc;
            --surface:    #ffffff;
            --border:     #e2e8f0;
            --text:       #1e293b;
            --text-muted: #64748b;
            --radius:     12px;
            --shadow:     0 1px 4px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* HEADER */
        .pub-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dk) 100%);
            color: white;
            padding: 48px 24px 36px;
            text-align: center;
        }
        .pub-header h1 {
            font-size: clamp(1.6rem, 4vw, 2.6rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 10px;
        }
        .pub-header p {
            font-size: 1.05rem;
            opacity: .85;
            max-width: 540px;
            margin: 0 auto 24px;
        }
        .pub-stats {
            display: inline-flex;
            gap: 32px;
            background: rgba(255,255,255,.15);
            border-radius: 999px;
            padding: 10px 28px;
            font-size: .9rem;
        }
        .pub-stats span strong {
            display: block;
            font-size: 1.3rem;
            font-weight: 700;
        }

        /* MAIN CONTENT */
        .pub-main {
            max-width: 1280px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        /* KATEGORIE GRUPPE */
        .category-group {
            margin-bottom: 48px;
        }
        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
        }
        .category-header h2 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text);
        }
        .category-badge {
            background: var(--primary);
            color: white;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: .78rem;
            font-weight: 600;
        }

        /* ITEMS GRID */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        /* ITEM CARD */
        .item-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
            border: 1px solid var(--border);
        }
        .item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,.1), 0 12px 32px rgba(0,0,0,.1);
        }
        .item-image {
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            background: #f1f5f9;
            display: block;
        }
        .item-image-placeholder {
            width: 100%;
            aspect-ratio: 4/3;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #94a3b8;
        }
        .item-body {
            padding: 16px;
        }
        .item-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 6px;
            line-height: 1.3;
        }
        .item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }
        .item-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: .75rem;
            color: var(--text-muted);
            background: #f1f5f9;
            border-radius: 6px;
            padding: 3px 8px;
        }
        .item-notes {
            font-size: .82rem;
            color: var(--text-muted);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .item-price {
            display: inline-block;
            margin-top: 10px;
            font-size: .9rem;
            font-weight: 700;
            color: var(--primary-dk);
            background: #eff6ff;
            border-radius: 6px;
            padding: 4px 10px;
        }

        /* LEER-ZUSTAND */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-state .icon { font-size: 3rem; margin-bottom: 16px; }
        .empty-state h2 { font-size: 1.3rem; font-weight: 600; margin-bottom: 8px; }

        /* FOOTER */
        .pub-footer {
            text-align: center;
            padding: 24px;
            border-top: 1px solid var(--border);
            font-size: .8rem;
            color: var(--text-muted);
            background: var(--surface);
        }
        .pub-footer a {
            color: var(--primary);
            text-decoration: none;
        }

        /* LIGHTBOX */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .lightbox.active { display: flex; }
        .lightbox img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,.5);
        }
        .lightbox-close {
            position: absolute;
            top: 16px;
            right: 20px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            line-height: 1;
            background: none;
            border: none;
        }

        @media (max-width: 480px) {
            .pub-header { padding: 32px 16px 24px; }
            .pub-stats { gap: 20px; padding: 8px 20px; }
            .items-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="pub-header">
    <h1>📦 <?php echo htmlspecialchars($page_title); ?></h1>
    <?php if ($page_desc): ?>
        <p><?php echo nl2br(htmlspecialchars($page_desc)); ?></p>
    <?php endif; ?>
    <div class="pub-stats">
        <span><strong><?php echo $total_items; ?></strong>Gegenstände</span>
        <span><strong><?php echo count($grouped); ?></strong>Kategorien</span>
        <?php if ($show_price && $total_value > 0): ?>
            <span><strong><?php echo number_format($total_value, 2, ',', '.'); ?> €</strong>Gesamtwert</span>
        <?php endif; ?>
    </div>
</header>

<!-- MAIN -->
<main class="pub-main">
    <?php if (empty($wertsachen)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <h2><?php echo t('public_no_items'); ?></h2>
            <p><?php echo t('public_no_items_desc'); ?></p>
        </div>
    <?php else: ?>

        <?php foreach ($grouped as $kategorie => $items): ?>
        <div class="category-group">
            <div class="category-header">
                <h2><?php echo htmlspecialchars($kategorie); ?></h2>
                <span class="category-badge"><?php echo count($items); ?></span>
            </div>
            <div class="items-grid">
                <?php foreach ($items as $item): ?>
                <div class="item-card">
                    <?php
                    $imgPath = !empty($item['bild']) ? 'upload/' . $item['bild'] : null;
                    if ($imgPath && file_exists(__DIR__ . '/' . $imgPath)):
                    ?>
                        <img class="item-image"
                             src="<?php echo htmlspecialchars($imgPath); ?>"
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             loading="lazy"
                             onclick="openLightbox(this.src)"
                             style="cursor:zoom-in;">
                    <?php else: ?>
                        <div class="item-image-placeholder">📦</div>
                    <?php endif; ?>

                    <div class="item-body">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>

                        <div class="item-meta">
                            <?php if ($show_location && !empty($item['ort_name'])): ?>
                                <span class="item-tag">📍 <?php echo htmlspecialchars($item['ort_name']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['kaufdatum'])): ?>
                                <span class="item-tag">📅 <?php echo date('Y', strtotime($item['kaufdatum'])); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($item['notizen'])): ?>
                            <div class="item-notes"><?php echo nl2br(htmlspecialchars($item['notizen'])); ?></div>
                        <?php endif; ?>

                        <?php if ($show_price && floatval($item['preis']) > 0): ?>
                            <span class="item-price">
                                <?php echo number_format(floatval($item['preis']), 2, ',', '.'); ?> €
                            </span>
                        <?php endif; ?>

                        <button class="anfrage-btn"
                                onclick="openAnfrage(<?php echo (int)$item['id']; ?>, <?php echo htmlspecialchars(json_encode($item['name'])); ?>)">
                            ✉️ <?php echo t('public_btn_interest'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>
</main>

<!-- FOOTER -->
<footer class="pub-footer">
    Erstellt mit <a href="https://palindrom.de/valusafe/" target="_blank" rel="noopener">Wertsachen-Inventarverwaltung</a>
</footer>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
    <img id="lightboxImg" src="" alt="">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
</script>

<!-- KÄUFER-ANFRAGE MODAL -->
<div id="anfrageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:16px; padding:28px; max-width:440px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="margin:0 0 6px; font-size:18px;">✉️ <?php echo t('public_btn_interest'); ?></h3>
        <p id="anfrageItemName" style="color:#6b7280; font-size:14px; margin:0 0 20px;"></p>

        <?php if (isset($anfrage_result)): ?>
            <?php if ($anfrage_result['status'] === 'ok'): ?>
                <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:14px; color:#166534; margin-bottom:16px;">
                    ✅ <?php echo t('public_anfrage_ok'); ?>
                </div>
            <?php elseif ($anfrage_result['status'] === 'no_email'): ?>
                <div style="background:#fef9c3; border:1px solid #fde047; border-radius:8px; padding:14px; color:#854d0e; margin-bottom:16px;">
                    ⚠️ <?php echo t('public_anfrage_no_email'); ?>
                </div>
            <?php else: ?>
                <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:14px; color:#991b1b; margin-bottom:16px;">
                    ❌ <?php echo t('public_anfrage_error'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="?token=<?php echo htmlspecialchars($token); ?>" id="anfrageForm">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="anfrage_item_id" id="anfrageItemId" value="">

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px; color:#374151;"><?php echo t('public_name_label'); ?></label>
                <input type="text" name="anfrage_name" required maxlength="100"
                       style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box;"
                       placeholder="<?php echo t('public_ph_name'); ?>">
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px; color:#374151;"><?php echo t('public_email_label'); ?></label>
                <input type="email" name="anfrage_email" required maxlength="150"
                       style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box;"
                       placeholder="<?php echo t('public_ph_email'); ?>">
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px; color:#374151;">
                    <?php echo t('public_message_label'); ?> <span style="font-weight:400; color:#9ca3af;"><?php echo t('public_message_hint'); ?></span>
                </label>
                <textarea name="anfrage_nachricht" maxlength="200" rows="3"
                          style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box; resize:vertical;"
                          placeholder="<?php echo t('public_ph_message'); ?>"
                          oninput="document.getElementById('charCount').textContent = this.value.length"></textarea>
                <div style="text-align:right; font-size:12px; color:#9ca3af; margin-top:2px;">
                    <span id="charCount">0</span>/200
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit"
                        style="flex:1; padding:11px; background:#2563eb; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;">
                    <?php echo t('public_btn_send'); ?>
                </button>
                <button type="button" onclick="closeAnfrage()"
                        style="padding:11px 18px; background:#f3f4f6; color:#374151; border:1px solid #d1d5db; border-radius:8px; font-size:14px; cursor:pointer;">
                    <?php echo t('settings_cancel'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.anfrage-btn {
    display: block;
    width: 100%;
    margin-top: 12px;
    padding: 9px;
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
    text-align: center;
}
.anfrage-btn:hover {
    background: #dbeafe;
    border-color: #2563eb;
}
</style>

<script>
function openAnfrage(itemId, itemName) {
    document.getElementById('anfrageItemId').value = itemId;
    document.getElementById('anfrageItemName').textContent = itemName;
    const modal = document.getElementById('anfrageModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeAnfrage() {
    document.getElementById('anfrageModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('anfrageModal').addEventListener('click', function(e) {
    if (e.target === this) closeAnfrage();
});
<?php if (isset($anfrage_result) && $anfrage_result['status'] === 'ok'): ?>
// Modal nach Erfolg automatisch offen lassen damit User Bestätigung sieht
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('anfrageModal').style.display = 'flex';
});
<?php endif; ?>
</script>

</body>
</html>
