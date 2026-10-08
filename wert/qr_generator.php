<?php
// qr_generator.php - QR-Code Generator (serverseitig PHP, kein JS, CSP-konform)
require_once 'db.php';
require_once 'helpers_permissions.php';
require_once 'helpers.php';
requireLogin();
// Export-Recht aus der Rechteverwaltung (bis 4.3.32 nicht geprueft).
requirePermission('export_qr');

define('PAGE_TITLE', t('nav_export_qr'));

// ─── Minimaler QR-Code Generator ────────────────────────────────────────────
// Erzeugt QR-Codes als SVG. Unterstützt URLs bis ~120 Zeichen (Version 1-5, ECL L).
// Basiert auf dem QR-Standard ISO/IEC 18004.

function qr_svg(string $text, int $px = 200): string {
    $bytes = array_values(unpack('C*', $text));
    $len   = count($bytes);

    // Version bestimmen (ECL L, Byte-Mode)
    $caps = [17,32,53,78,106,134,154,192,230,271];
    $version = 0;
    foreach ($caps as $i => $cap) { if ($len <= $cap) { $version = $i+1; break; } }
    if (!$version) return qr_error($px, 'Text zu lang');

    $size = $version * 4 + 17;
    $mat  = array_fill(0, $size, array_fill(0, $size, 0));
    $res  = array_fill(0, $size, array_fill(0, $size, false));

    qr_finder($mat, $res, 0,        0,        $size);
    qr_finder($mat, $res, $size-7,  0,        $size);
    qr_finder($mat, $res, 0,        $size-7,  $size);
    qr_timing($mat, $res, $size);
    qr_dark($mat, $res, $version);
    qr_alignment($mat, $res, $version);
    qr_reserve_format($res, $size);

    $bits = qr_data_bits($bytes, $len, $version);
    qr_place($mat, $res, $bits, $size);

    $best = 0; $bestScore = PHP_INT_MAX;
    for ($m = 0; $m < 8; $m++) {
        $tmp = $mat;
        qr_mask($tmp, $res, $size, $m);
        $s = qr_score($tmp, $size);
        if ($s < $bestScore) { $bestScore = $s; $best = $m; }
    }
    qr_mask($mat, $res, $size, $best);
    qr_format($mat, $size, $best); // ECL L = 01

    // SVG ausgeben
    $q  = 4; // quiet zone modules
    $total = ($size + $q*2) * $px / ($size + $q*2); // keep ratio
    $cell = $px / ($size + $q*2);
    $w    = round($cell * ($size + $q*2));
    $svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'" height="'.$w.'" viewBox="0 0 '.$w.' '.$w.'">';
    $svg .= '<rect width="'.$w.'" height="'.$w.'" fill="#fff"/>';
    $rects = '';
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            if ($mat[$r][$c]) {
                $x = round(($q + $c) * $cell, 2);
                $y = round(($q + $r) * $cell, 2);
                $s = round($cell + 0.1, 2); // +0.1 anti-aliasing gap
                $rects .= '<rect x="'.$x.'" y="'.$y.'" width="'.$s.'" height="'.$s.'"/>';
            }
        }
    }
    $svg .= '<g fill="#000">'.$rects.'</g></svg>';
    return $svg;
}

function qr_error(int $px, string $msg): string {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$px.'" height="'.$px.'"><rect width="'.$px.'" height="'.$px.'" fill="#fff"/><text x="50%" y="50%" text-anchor="middle" fill="red" font-size="12">'.htmlspecialchars($msg).'</text></svg>';
}

function qr_finder(array &$m, array &$r, int $row, int $col, int $size): void {
    $p = [[1,1,1,1,1,1,1],[1,0,0,0,0,0,1],[1,0,1,1,1,0,1],[1,0,1,1,1,0,1],[1,0,1,1,1,0,1],[1,0,0,0,0,0,1],[1,1,1,1,1,1,1]];
    for ($dr = -1; $dr <= 7; $dr++) for ($dc = -1; $dc <= 7; $dc++) {
        $rr = $row+$dr; $cc = $col+$dc;
        if ($rr<0||$rr>=$size||$cc<0||$cc>=$size) continue;
        $r[$rr][$cc] = true;
        $m[$rr][$cc] = ($dr>=0&&$dr<=6&&$dc>=0&&$dc<=6) ? $p[$dr][$dc] : 0;
    }
}

function qr_timing(array &$m, array &$r, int $size): void {
    for ($i = 8; $i < $size-8; $i++) {
        $v = $i%2==0 ? 1 : 0;
        if (!$r[$i][6]) { $m[$i][6]=$v; $r[$i][6]=true; }
        if (!$r[6][$i]) { $m[6][$i]=$v; $r[6][$i]=true; }
    }
}

function qr_dark(array &$m, array &$r, int $v): void {
    $row = 4*$v+9; $col = 8;
    $m[$row][$col] = 1; $r[$row][$col] = true;
}

function qr_alignment(array &$m, array &$r, int $v): void {
    $tbl = [[],[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38],[6,24,42]];
    $pos = $tbl[$v] ?? [];
    $p = [[1,1,1,1,1],[1,0,0,0,1],[1,0,1,0,1],[1,0,0,0,1],[1,1,1,1,1]];
    foreach ($pos as $row) foreach ($pos as $col) {
        if ($r[$row][$col]) continue;
        for ($dr=-2;$dr<=2;$dr++) for ($dc=-2;$dc<=2;$dc++) {
            $m[$row+$dr][$col+$dc]=$p[$dr+2][$dc+2]; $r[$row+$dr][$col+$dc]=true;
        }
    }
}

function qr_reserve_format(array &$r, int $size): void {
    for ($i=0;$i<=8;$i++) { $r[$i][8]=true; $r[8][$i]=true; }
    for ($i=$size-8;$i<$size;$i++) { $r[$i][8]=true; $r[8][$i]=true; }
}

function qr_data_bits(array $bytes, int $len, int $v): array {
    // RS block info [blocks, total codewords, data codewords] for ECL L
    $rsInfo = [1=>[1,26,19],2=>[1,44,34],3=>[1,70,55],4=>[1,100,80],
               5=>[1,134,108],6=>[2,86,68],7=>[4,98,78],8=>[2,121,97]];
    [$blocks,$totalCW,$dataCW] = $rsInfo[$v] ?? [1,26,19];
    $ecCW = $totalCW - $dataCW;

    // Build byte stream
    $stream = [];
    $stream[] = 0x40 | ($len >> 4); // mode(4) + len high(4)
    $stream[] = (($len & 0xF) << 4) | ($bytes[0] >> 4); // len low(4) + data[0] high(4)
    for ($i = 0; $i < $len-1; $i++) {
        $stream[] = (($bytes[$i] & 0xF) << 4) | ($bytes[$i+1] >> 4);
    }
    $stream[] = ($bytes[$len-1] & 0xF) << 4; // last nibble + terminator
    // Pad
    $padBytes = [0xEC,0x11]; $pi=0;
    while (count($stream) < $dataCW * $blocks) { $stream[] = $padBytes[$pi%2]; $pi++; }
    $stream = array_slice($stream, 0, $dataCW * $blocks);

    // Split into blocks, add EC
    $result = []; $ecResult = [];
    for ($b=0; $b<$blocks; $b++) {
        $blockData = array_slice($stream, $b*$dataCW, $dataCW);
        $result[]   = $blockData;
        $ecResult[] = qr_rs_encode($blockData, $ecCW);
    }

    // Interleave
    $bits = [];
    for ($i=0; $i<$dataCW; $i++) for ($b=0; $b<$blocks; $b++) {
        $byte = $result[$b][$i] ?? 0;
        for ($j=7;$j>=0;$j--) $bits[] = ($byte>>$j)&1;
    }
    for ($i=0; $i<$ecCW; $i++) for ($b=0; $b<$blocks; $b++) {
        $byte = $ecResult[$b][$i] ?? 0;
        for ($j=7;$j>=0;$j--) $bits[] = ($byte>>$j)&1;
    }
    // Remainder bits
    $rem = [0,7,7,7,7,7,0,0];
    for ($i=0; $i<($rem[$v]??0); $i++) $bits[] = 0;
    return $bits;
}

// GF(256) tables
$QR_EXP = []; $QR_LOG = [];
function qr_gf_init(): void {
    global $QR_EXP, $QR_LOG;
    if ($QR_EXP) return;
    $QR_EXP = array_fill(0,512,0); $QR_LOG = array_fill(0,256,0);
    $x = 1;
    for ($i=0; $i<255; $i++) {
        $QR_EXP[$i] = $x; $QR_LOG[$x] = $i;
        $x = ($x<<1) ^ ($x&128 ? 0x11D : 0);
    }
    for ($i=255; $i<512; $i++) $QR_EXP[$i] = $QR_EXP[$i-255];
}

function qr_rs_encode(array $data, int $ecCount): array {
    global $QR_EXP, $QR_LOG;
    qr_gf_init();
    // Generator polynomial
    $gen = [1];
    for ($i=0; $i<$ecCount; $i++) {
        $newGen = array_fill(0, count($gen)+1, 0);
        for ($j=0; $j<count($gen); $j++) {
            $newGen[$j]   ^= $gen[$j];
            $newGen[$j+1] ^= $gen[$j] ? $QR_EXP[($QR_LOG[$gen[$j]] + $i) % 255] : 0;
        }
        $gen = $newGen;
    }
    // Divide
    $msg = array_merge($data, array_fill(0, $ecCount, 0));
    for ($i=0; $i<count($data); $i++) {
        $coef = $msg[$i];
        if (!$coef) continue;
        $logCoef = $QR_LOG[$coef];
        for ($j=0; $j<count($gen); $j++) {
            if ($gen[$j]) $msg[$i+$j] ^= $QR_EXP[($logCoef + $QR_LOG[$gen[$j]]) % 255];
        }
    }
    return array_slice($msg, count($data));
}

function qr_place(array &$m, array $r, array $bits, int $size): void {
    $idx=0; $up=true;
    for ($col=$size-1; $col>=1; $col-=2) {
        if ($col==6) $col=5;
        for ($i=0; $i<$size; $i++) {
            $row = $up ? ($size-1-$i) : $i;
            for ($c=0; $c<2; $c++) {
                $cc=$col-$c;
                if (!$r[$row][$cc]) { $m[$row][$cc]=$bits[$idx]??0; $idx++; }
            }
        }
        $up=!$up;
    }
}

function qr_mask(array &$m, array $r, int $size, int $mask): void {
    for ($row=0;$row<$size;$row++) for ($col=0;$col<$size;$col++) {
        if ($r[$row][$col]) continue;
        $flip = match($mask) {
            0 => ($row+$col)%2==0,
            1 => $row%2==0,
            2 => $col%3==0,
            3 => ($row+$col)%3==0,
            4 => (intdiv($row,2)+intdiv($col,3))%2==0,
            5 => ($row*$col)%2+($row*$col)%3==0,
            6 => (($row*$col)%2+($row*$col)%3)%2==0,
            7 => (($row*$col)%3+($row+$col)%2)%2==0,
            default => false
        };
        if ($flip) $m[$row][$col] ^= 1;
    }
}

function qr_score(array $m, int $size): int {
    $score = 0;
    // Rule 1: 5+ in a row same color
    for ($r=0;$r<$size;$r++) {
        $cnt=1;
        for ($c=1;$c<$size;$c++) {
            if ($m[$r][$c]==$m[$r][$c-1]) $cnt++; else $cnt=1;
            if ($cnt==5) $score+=3; elseif ($cnt>5) $score++;
        }
        $cnt=1;
        for ($c=1;$c<$size;$c++) {
            if ($m[$c][$r]==$m[$c-1][$r]) $cnt++; else $cnt=1;
            if ($cnt==5) $score+=3; elseif ($cnt>5) $score++;
        }
    }
    // Rule 2: 2x2 blocks
    for ($r=0;$r<$size-1;$r++) for ($c=0;$c<$size-1;$c++) {
        $v=$m[$r][$c];
        if ($m[$r][$c+1]==$v&&$m[$r+1][$c]==$v&&$m[$r+1][$c+1]==$v) $score+=3;
    }
    return $score;
}

function qr_format(array &$m, int $size, int $mask): void {
    // ECL L = 01
    $data = (0b01 << 3) | $mask;
    $d = $data << 10;
    for ($i=14;$i>=10;$i--) if ($d&(1<<$i)) $d ^= (0b10100110111 << ($i-10));
    $fmt = (($data<<10)|($d&0x3FF)) ^ 0b101010000010010;

    $bits=[];
    for ($i=14;$i>=0;$i--) $bits[]=($fmt>>$i)&1;

    // Top-left horizontal (row 8)
    $cols1=[0,1,2,3,4,5,7,8];
    foreach ($cols1 as $i=>$c) $m[8][$c]=$bits[$i];
    // Top-left vertical (col 8)
    $rows1=[7,5,4,3,2,1,0];
    foreach ($rows1 as $i=>$r) $m[$r][8]=$bits[$i+8]; // bits 8..14

    // Bottom-left vertical
    for ($i=0;$i<7;$i++) $m[$size-1-$i][8]=$bits[$i];
    // Top-right horizontal
    for ($i=0;$i<8;$i++) $m[8][$size-8+$i]=$bits[7+$i];
}
// ─── Ende QR-Generator ──────────────────────────────────────────────────────

// Items laden
$filterCategory = isset($_GET['kategorie']) ? (int)$_GET['kategorie'] : 0;
$selectedIds = [];
if (isset($_GET['ids'])) {
    $selectedIds = array_filter(array_map('intval', explode(',', $_GET['ids'])));
}

// Bei "nur eigene" nur eigene Gegenstaende (auch bei ?ids=...).
[$ownSql, $ownParams] = nurEigeneSql('w');

if (!empty($selectedIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
    $stmt = $pdo->prepare("SELECT w.id, w.name, k.name as kategorie_name
        FROM wertsachen w LEFT JOIN kategorien k ON w.kategorie_id = k.id
        WHERE w.id IN ($placeholders)" . $ownSql . " ORDER BY w.name");
    $stmt->execute(array_merge($selectedIds, $ownParams));
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($filterCategory > 0) {
    $stmt = $pdo->prepare("SELECT w.id, w.name, k.name as kategorie_name
        FROM wertsachen w LEFT JOIN kategorien k ON w.kategorie_id = k.id
        WHERE w.kategorie_id = ?" . $ownSql . " ORDER BY w.name");
    $stmt->execute(array_merge([$filterCategory], $ownParams));
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT w.id, w.name, k.name as kategorie_name
        FROM wertsachen w LEFT JOIN kategorien k ON w.kategorie_id = k.id
        WHERE (w.hidden = 0 OR w.hidden IS NULL)" . $ownSql . "
        ORDER BY w.name");
    $stmt->execute($ownParams);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$categories = $pdo->query("SELECT id, name FROM kategorien ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

// QR-Codes vorberechnen (serverseitig)
$qrData = [];
foreach ($items as $item) {
    $url = $baseUrl . '/edit.php?id=' . $item['id'];
    $svg = qr_svg($url, 160);
    $qrData[$item['id']] = 'data:image/svg+xml;base64,' . base64_encode($svg);
}

include 'header_next_page.php';
?>
<style>
.qr-controls {
    background: rgba(255,255,255,0.6);
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 16px;
    padding: 16px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.qr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 18px;
    margin: 0 0 30px;
}
.qr-card {
    background: rgba(255,255,255,0.7);
    border: 1px solid rgba(0,0,0,0.09);
    border-radius: 16px;
    padding: 18px 14px 14px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    transition: box-shadow 0.18s;
}
.qr-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.11); }
.qr-card img {
    width: 160px;
    height: 160px;
    display: block;
    margin: 0 auto 12px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}
.qr-name {
    font-weight: 600;
    font-size: 14px;
    color: #1e293b;
    margin-bottom: 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.qr-category { font-size: 12px; color: #64748b; margin-bottom: 11px; min-height: 16px; }
.qr-actions { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
.qr-btn {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid rgba(0,0,0,0.12);
    background: rgba(255,255,255,0.8);
    color: #374151;
    text-decoration: none;
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.qr-btn:hover { background: white; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
.qr-btn-primary { background: var(--primary-color, #3498db); color: white; border-color: transparent; }
.qr-btn-primary:hover { opacity: 0.88; box-shadow: none; }
@media print {
    .qr-controls, .qr-actions, .no-print { display: none !important; }
    .qr-grid { grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .qr-card { border: 1px solid #ccc; border-radius: 8px; box-shadow: none; break-inside: avoid; }
}
@media (max-width: 600px) {
    .qr-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
}
</style>

<div class="no-print">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
        <a href="index.php" class="btn">← <?php echo t('btn_back'); ?></a>
        <h2 style="margin:0; border:none; padding:0;">📱 <?php echo t('nav_export_qr'); ?></h2>
        <span style="font-size:13px; color:#64748b;"><?php echo sprintf(t('qr_items_count'), count($items)); ?></span>
        <button onclick="window.print()" class="btn btn-secondary" style="margin-left:auto;">🖨️ <?php echo t('qr_print'); ?></button>
    </div>

    <div class="qr-controls">
        <label style="font-size:14px; font-weight:500; margin:0; white-space:nowrap;"><?php echo t('col_kategorie'); ?>:</label>
        <select class="form-control" style="max-width:220px; margin:0;"
                onchange="window.location.href='qr_generator.php'+(this.value>0?'?kategorie='+this.value:'')">
            <option value="0"><?php echo t('placeholder_all_categories'); ?></option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategory == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterCategory > 0): ?>
            <a href="qr_generator.php" class="btn btn-secondary">✕ <?php echo t('qr_clear_filter'); ?></a>
        <?php endif; ?>
        <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
            <label style="font-size:13px; color:#64748b; margin:0;"><?php echo t('qr_size'); ?>:</label>
            <select class="form-control" style="max-width:110px; margin:0;" onchange="changeSize(this.value)">
                <option value="120"><?php echo t('qr_size_small'); ?></option>
                <option value="160" selected><?php echo t('qr_size_medium'); ?></option>
                <option value="200"><?php echo t('qr_size_large'); ?></option>
            </select>
        </div>
    </div>
</div>

<?php if (empty($items)): ?>
    <div class="alert alert-info no-print"><?php echo t('qr_no_items'); ?></div>
<?php else: ?>

<div class="qr-grid">
    <?php foreach ($items as $item):
        $itemUrl   = $baseUrl . '/edit.php?id=' . $item['id'];
        $safeUrl   = htmlspecialchars($itemUrl, ENT_QUOTES);
        $safeName  = htmlspecialchars($item['name']);
        $safeCat   = htmlspecialchars($item['kategorie_name'] ?? '');
        $qrSrc     = $qrData[$item['id']] ?? '';
    ?>
    <div class="qr-card">
        <?php if ($qrSrc): ?>
            <img src="<?php echo $qrSrc; ?>" alt="<?php echo sprintf(htmlspecialchars(t('qr_alt')), $safeName); ?>"
                 id="qrimg-<?php echo $item['id']; ?>">
        <?php else: ?>
            <div style="width:160px;height:160px;margin:0 auto 12px;background:#f3f4f6;border-radius:8px;
                        display:flex;align-items:center;justify-content:center;color:#e74c3c;font-size:12px;">
                <?php echo t('qr_error'); ?>
            </div>
        <?php endif; ?>
        <div class="qr-name" title="<?php echo $safeName; ?>"><?php echo $safeName; ?></div>
        <div class="qr-category"><?php echo $safeCat ? '📁 '.$safeCat : ''; ?></div>
        <div class="qr-actions no-print">
            <a href="<?php echo $qrSrc; ?>" download="qr-<?php echo $item['id']; ?>.svg"
               class="qr-btn qr-btn-primary">⬇ SVG</a>
            <button class="qr-btn" onclick="copyUrl('<?php echo $safeUrl; ?>', this)">📋 URL</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<script>
function changeSize(val) {
    var s = parseInt(val) + 'px';
    document.querySelectorAll('.qr-card img').forEach(function(img) {
        img.style.width = s; img.style.height = s;
    });
}
function copyUrl(url, btn) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '✅ ' + <?php echo json_encode(t('qr_copied')); ?>;
            setTimeout(function() { btn.innerHTML = orig; }, 2000);
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = url; ta.style.cssText = 'position:fixed;left:-9999px';
        document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
    }
}
</script>

<?php include 'footer_next.php'; ?>
