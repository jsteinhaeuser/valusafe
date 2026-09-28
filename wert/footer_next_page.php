        </div><!-- /.vs-page-content -->
    </div><!-- /.vs-workspace -->
</div><!-- /.vs-main -->

<!-- ── Export-Menü (Body-Portal) ─────────────────────────────── -->
<?php $vsmBase = defined('BASE_URL') ? BASE_URL : ''; ?>
<style>
.vsexp-item {
    display:flex; align-items:center; gap:10px; padding:11px 16px;
    color:var(--vs-text,#0c1f3d); text-decoration:none; font-size:13px;
    border-bottom:1px solid var(--vs-border,#e2e8f0); transition:background .12s;
}
.vsexp-item:hover { background:var(--vs-bg,#f1f5f9); }
.vsexp-item i { font-size:16px; color:var(--vs-accent,#185fa5); flex-shrink:0; }
#vsServiceModal {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
    z-index:99998; align-items:center; justify-content:center;
}
#vsServiceModal.active { display:flex; }
.vsm-box {
    background:var(--vs-surface,#fff); border-radius:12px;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
    width:min(820px,96vw); height:min(580px,90vh);
    display:flex; flex-direction:column; overflow:hidden;
}
.vsm-header {
    background:var(--vs-accent,#185fa5); color:#fff; padding:14px 20px;
    display:flex; align-items:center; justify-content:space-between; flex-shrink:0;
}
.vsm-header h2 { margin:0; font-size:15pt; font-weight:600; }
.vsm-close {
    background:rgba(255,255,255,.15); border:none; color:#fff; border-radius:50%;
    width:30px; height:30px; font-size:16px; cursor:pointer;
    display:flex; align-items:center; justify-content:center; transition:background .15s;
}
.vsm-close:hover { background:rgba(255,255,255,.3); }
.vsm-body { display:flex; flex:1; overflow:hidden; }
.vsm-sidebar {
    width:220px; flex-shrink:0; border-right:1px solid var(--vs-border,#e2e8f0);
    overflow-y:auto; padding:10px 0; background:var(--vs-bg,#f1f5f9);
}
.vsm-sidebar-title {
    font-size:10px; font-weight:700; color:var(--vs-muted,#64748b);
    letter-spacing:.08em; padding:6px 16px 8px; text-transform:uppercase;
}
.vsm-file-btn {
    display:flex; align-items:center; gap:9px; width:100%; padding:9px 16px;
    background:none; border:none; cursor:pointer; font-size:13px;
    color:var(--vs-text,#0c1f3d); text-align:left; transition:background .12s;
}
.vsm-file-btn:hover  { background:var(--vs-border,#e2e8f0); }
.vsm-file-btn.active { background:var(--vs-accent,#185fa5); color:#fff; font-weight:600; }
.vsm-content {
    flex:1; overflow-y:auto; padding:24px 28px;
    font-size:13.5px; line-height:1.7; color:var(--vs-text,#0c1f3d);
}
.vsm-content h1,.vsm-content h2,.vsm-content h3 { color:var(--vs-accent,#185fa5); margin:16px 0 8px; }
.vsm-content code { background:var(--vs-bg,#f1f5f9); padding:2px 6px; border-radius:4px; font-size:12px; }
.vsm-content pre  { background:var(--vs-bg,#f1f5f9); padding:12px; border-radius:6px; overflow-x:auto; }
.vsm-content a    { color:var(--vs-accent,#185fa5); }
</style>

<div id="vs-export-menu" style="display:none;position:fixed;background:var(--vs-surface,#fff);
     border:1px solid var(--vs-border,#e2e8f0);border-radius:8px;
     box-shadow:0 6px 20px rgba(0,0,0,.15);z-index:99999;min-width:220px;overflow:hidden;">
    <a href="<?php echo $vsmBase; ?>export_csv.php"          class="vsexp-item"><i class="ti ti-table-export"></i> CSV-Datei</a>
    <a href="<?php echo $vsmBase; ?>export_html.php"         class="vsexp-item"><i class="ti ti-file-type-html"></i> HTML-Seite</a>
    <a href="<?php echo $vsmBase; ?>export_pdf.php"          class="vsexp-item"><i class="ti ti-file-type-pdf"></i> PDF Detailliert</a>
    <a href="<?php echo $vsmBase; ?>export_pdf_compact.php"  class="vsexp-item"><i class="ti ti-file-description"></i> PDF Kompakt</a>
    <a href="<?php echo $vsmBase; ?>export_pdf_handover.php" class="vsexp-item"><i class="ti ti-home-check"></i> Übergabeprotokoll</a>
    <a href="<?php echo $vsmBase; ?>export_insurance.php"    class="vsexp-item" style="font-weight:600"><i class="ti ti-shield-check"></i> Versicherungs-Export</a>
    <a href="<?php echo $vsmBase; ?>qr_generator.php"        class="vsexp-item" style="border-bottom:none"><i class="ti ti-qrcode"></i> QR-Codes</a>
</div>

<div id="vsServiceModal" onclick="if(event.target===this)closeServiceModal()">
    <div class="vsm-box">
        <div class="vsm-header">
            <h2>ℹ️ Hilfe &amp; Informationen</h2>
            <button class="vsm-close" onclick="closeServiceModal()">✕</button>
        </div>
        <div class="vsm-body">
            <div class="vsm-sidebar">
                <div class="vsm-sidebar-title">Dokumente</div>
                <div id="vsmFileList"><div style="padding:16px;color:var(--vs-muted)">⏳ Lädt…</div></div>
            </div>
            <div class="vsm-content" id="vsmContent">
                <div style="text-align:center;padding:40px;color:var(--vs-muted)">👈 Bitte ein Dokument auswählen</div>
            </div>
        </div>
    </div>
</div>

<script>
/* ── Export ── */
function vsToggleExport(e) {
    e.stopPropagation();
    var m=document.getElementById('vs-export-menu'), b=document.getElementById('vs-export-btn');
    if(!m||!b) return;
    if(!m.style.display||m.style.display==='none') {
        var r=b.getBoundingClientRect();
        m.style.top=(r.bottom+6)+'px'; m.style.right=(window.innerWidth-r.right)+'px'; m.style.left='auto'; m.style.display='block';
    } else { m.style.display='none'; }
}
document.addEventListener('click',function(e){
    var m=document.getElementById('vs-export-menu'),b=document.getElementById('vs-export-btn');
    if(m&&m.style.display==='block'&&b&&!b.contains(e.target)&&!m.contains(e.target)) m.style.display='none';
});

/* ── Service Modal Funktionen (kein PHP) ── */
var _vsmFiles=[], _vsmLoaded=false, _vsmBase='';

// Solange die Hilfe offen ist, traegt das (i) in der Leiste die Markierung,
// nicht das Symbol der Seite darunter (aufgefallen beim Docker-Test 26.09.:
// Hilfe auf settings.php geoeffnet, das Zahnrad blieb markiert).
var _vsmRailVorher=[];
function _vsmRail(an){
    var hilfe=document.querySelector('.vs-rail-btn[onclick^="openServiceModal"]');
    if(!hilfe)return;
    if(an){
        if(hilfe.classList.contains('vs-rail-on'))return;
        _vsmRailVorher=[].slice.call(document.querySelectorAll('.vs-rail-btn.vs-rail-on'));
        _vsmRailVorher.forEach(function(b){b.classList.remove('vs-rail-on');});
        hilfe.classList.add('vs-rail-on');
        hilfe.setAttribute('aria-expanded','true');
    }else{
        hilfe.classList.remove('vs-rail-on');
        hilfe.setAttribute('aria-expanded','false');
        _vsmRailVorher.forEach(function(b){b.classList.add('vs-rail-on');});
        _vsmRailVorher=[];
    }
}

function openServiceModal(){
    document.getElementById('vsServiceModal').classList.add('active');
    _vsmRail(true);
    document.body.style.overflow='hidden';
    if(!_vsmLoaded)_renderVsm();
}
function closeServiceModal(){
    document.getElementById('vsServiceModal').classList.remove('active');
    _vsmRail(false);
    document.body.style.overflow='';
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeServiceModal();});

function _renderVsm(){
    var l=document.getElementById('vsmFileList');
    if(!_vsmFiles||!_vsmFiles.length){l.innerHTML='<div style="padding:16px">Keine Dokumente</div>';_vsmLoaded=true;return;}
    l.innerHTML=_vsmFiles.map(function(f,i){
        return '<button class="vsm-file-btn" onclick="_loadVsm('+i+',this)"><span style="font-size:16px">'+f.icon+'</span><span>'+f.label+'</span></button>';
    }).join('');
    _vsmLoaded=true;
    var fb=l.querySelector('.vsm-file-btn');
    if(fb) setTimeout(function(){_loadVsm(0,fb);},0);
}

var _md={parse:function(s){
    var h=s.replace(/```[\w]*\n?([\s\S]*?)```/g,'<pre><code>$1</code></pre>')
        .replace(/`([^`]+)`/g,'<code>$1</code>')
        .replace(/^#{4} (.+)$/gm,'<h4>$1</h4>').replace(/^#{3} (.+)$/gm,'<h3>$1</h3>')
        .replace(/^#{2} (.+)$/gm,'<h2>$1</h2>').replace(/^# (.+)$/gm,'<h1>$1</h1>')
        .replace(/^[-*]{3,}$/gm,'<hr>').replace(/\*\*\*(.+?)\*\*\*/g,'<strong><em>$1</em></strong>')
        .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\*(.+?)\*/g,'<em>$1</em>')
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g,'<a href="$2" target="_blank">$1</a>')
        .replace(/^\s*[-*+] (.+)$/gm,'<li>$1</li>').replace(/^\d+\. (.+)$/gm,'<li>$1</li>')
        .replace(/\n\n+/g,'</p><p>').replace(/\n/g,'<br>');
    return h.split('</p><p>').map(function(p){return /^<(h[1-6]|ul|ol|pre|hr|li)/.test(p.trim())?p:'<p>'+p+'</p>';}).join('');
}};

async function _loadVsm(idx,btn){
    document.querySelectorAll('.vsm-file-btn').forEach(function(b){b.classList.remove('active');});
    btn.classList.add('active');
    var f=_vsmFiles[idx], c=document.getElementById('vsmContent');
    var ac='var(--vs-accent,#185fa5)', tx='var(--vs-text,#0c1f3d)', mu='var(--vs-muted,#64748b)';
    if(f.type==='pdf'){
        c.innerHTML='<div style="text-align:center;padding:60px 20px;color:'+mu+'">'
            +'<div style="font-size:48px;margin-bottom:16px">📕</div>'
            +'<div style="font-size:14pt;font-weight:600;margin-bottom:6px;color:'+tx+'">'+f.label+'</div>'
            +'<div style="margin-bottom:24px">PDF-Dokument</div>'
            +'<a href="'+_vsmBase+'service_doc.php?file='+encodeURIComponent(f.file)+'" target="_blank"'
            +' style="background:'+ac+';color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none">📕 PDF öffnen</a>'
            +'<div style="margin-top:10px;font-size:9pt;color:'+mu+'">Öffnet in einem neuen Tab</div></div>';
        return;
    }
    if(f.type==='php'){
        c.innerHTML='<div style="text-align:center;padding:60px 20px;color:'+mu+'">'
            +'<div style="font-size:48px;margin-bottom:16px">📋</div>'
            +'<div style="font-size:14pt;font-weight:600;margin-bottom:24px;color:'+tx+'">'+f.label+'</div>'
            +'<a href="'+_vsmBase+f.file+'" target="_blank"'
            +' style="background:'+ac+';color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none">📋 '+f.label+' öffnen</a></div>';
        return;
    }
    c.innerHTML='<div style="padding:16px;color:'+mu+'">⏳ Lädt…</div>';
    try{
        var r=await fetch(_vsmBase+'service_doc.php?file='+encodeURIComponent(f.file));
        c.innerHTML=_md.parse(await r.text()); c.scrollTop=0;
    }catch(e){c.innerHTML='<div style="color:#c0392b">⚠️ Fehler beim Laden.</div>';}
}
</script>

<!-- PHP-Daten in separatem Block (Fehler hier bricht Funktionen nicht) -->
<?php
try {
    $vsmBase  = defined('BASE_URL') ? BASE_URL : '';
    $serviceDir = dirname(__FILE__) . '/service/';
    $files = [];
    if (is_dir($serviceDir)) {
        $ic = function($n, $e) {
            if ($e === 'pdf') return '📕';
            $n = strtolower($n);
            if (strpos($n,'erste')!==false||strpos($n,'start')!==false) return '🚀';
            if (strpos($n,'changelog')!==false) return '🦎';
            if ($e === 'php') return '🔄';
            if (strpos($n,'about')!==false) return 'ℹ️';
            if (strpos($n,'faq')!==false) return '❓';
            if (strpos($n,'fact')!==false||strpos($n,'feature')!==false) return '⭐';
            if (strpos($n,'manual')!==false||strpos($n,'handbuch')!==false) return '📖';
            return '📄';
        };
        $all = array_merge(
            glob($serviceDir.'*.php') ?: [],
            glob($serviceDir.'*.pdf') ?: [],
            glob($serviceDir.'*.md')  ?: []
        );
        usort($all, function($a,$b){
            $aA = strpos(strtolower(basename($a)),'about')!==false;
            $bA = strpos(strtolower(basename($b)),'about')!==false;
            if($aA&&!$bA) return 1; if(!$aA&&$bA) return -1;
            return strcmp(strtolower(basename($a)),strtolower(basename($b)));
        });
        foreach ($all as $fp) {
            $fn  = basename($fp);
            $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
            $files[] = [
                'file'  => $fn,
                'label' => ucwords(str_replace(['-','_'],' ', pathinfo($fn, PATHINFO_FILENAME))),
                'icon'  => $ic($fn, $ext),
                'type'  => $ext
            ];
        }
    }
    $json = json_encode($files);
    if ($json === false) $json = '[]';
    echo '<script>_vsmFiles=' . $json . ';_vsmBase="' . addslashes($vsmBase) . '";</script>';
} catch (Exception $e) {
    // Kein Output — _vsmFiles bleibt []
}
?>
<?php if (!isset($hide_swipe) && isset($_SESSION['user_id'])): ?>
<?php include __DIR__ . '/swipe_gestures.php'; ?>
<?php endif; ?>


<?php include __DIR__ . '/components/sw_registrierung.php'; ?>

<?php include __DIR__ . '/components/vs_dialog.php'; ?>
</body>
</html>
