<?php
/**
 * components/vs_dialog.php — Ersatz fuer alert(), confirm() und prompt().
 *
 * WOZU
 *   Die eingebauten Browser-Dialoge stellen dem Text eine Zeile voran, die
 *   der Browser selbst schreibt: "Auf example.org wird Folgendes
 *   angezeigt". Diese Zeile kommt in der Sprache des Browsers, nicht in der
 *   im Programm eingestellten. Wer die Anwendung auf Tuerkisch bedient und
 *   einen deutschen Browser hat, liest also erst Deutsch und dann Tuerkisch.
 *   An diese Zeile kommt kein Programm heran; der einzige Ausweg ist, die
 *   eingebauten Dialoge nicht mehr zu benutzen.
 *
 * AUFRUF
 *   Alle drei geben ein Promise zurueck — der Aufruf laeuft weiter, waehrend
 *   der Dialog noch offen ist. Das ist der Unterschied zu alert() und
 *   confirm(), die den Ablauf anhalten.
 *
 *     vsAlert('Datei nicht gefunden');                 // -> immer true
 *     vsConfirm('Wirklich loeschen?').then(ja => { … }) // -> true / false
 *     vsPrompt('Strichcode eingeben', '').then(t => …)  // -> Text oder null
 *
 *   Ein zweiter Aufruf bei offenem Dialog wartet, bis der erste beantwortet
 *   ist. Escape und ein Klick neben den Dialog gelten als Abbruch.
 *
 * EINBINDUNG
 *   Am Ende der drei Fusszeilen: footer_next.php, footer_next_page.php und
 *   backend/layout/footer_next_page.php. Mehrfaches Einbinden auf derselben
 *   Seite ist harmlos, es zaehlt das erste.
 *
 * Die Beschriftungen kommen ueber t() und damit in allen neun Sprachen. Das
 * Aussehen steht als .vs-dialog in css/next.css, nicht hier.
 */
if (defined('VS_DIALOG_EINGEBUNDEN')) { return; }
define('VS_DIALOG_EINGEBUNDEN', true);
?>
<div class="vs-dialog-backdrop" id="vsDialogBackdrop" hidden>
    <div class="vs-dialog" role="dialog" aria-modal="true" aria-labelledby="vsDialogText">
        <h3 class="vs-dialog-title" id="vsDialogTitle" hidden></h3>
        <p class="vs-dialog-text" id="vsDialogText"></p>
        <input type="text" class="vs-dialog-input" id="vsDialogInput" hidden>
        <div class="vs-dialog-actions">
            <button type="button" class="vs-dialog-btn vs-dialog-btn-secondary" id="vsDialogCancel" hidden></button>
            <button type="button" class="vs-dialog-btn vs-dialog-btn-primary" id="vsDialogOk"></button>
        </div>
    </div>
</div>
<script>
(function () {
    'use strict';
    if (window.vsDialog) { return; }

    var BESCHRIFTUNG_SCHLIESSEN = <?php echo json_encode(t('btn_close')   ?: 'Schliessen'); ?>;
    var BESCHRIFTUNG_BESTAETIGEN = <?php echo json_encode(t('btn_confirm') ?: 'Bestaetigen'); ?>;
    var BESCHRIFTUNG_ABBRECHEN   = <?php echo json_encode(t('btn_cancel')  ?: 'Abbrechen'); ?>;

    var hintergrund = document.getElementById('vsDialogBackdrop');
    var kasten      = hintergrund.querySelector('.vs-dialog');
    var elTitel     = document.getElementById('vsDialogTitle');
    var elText      = document.getElementById('vsDialogText');
    var elEingabe   = document.getElementById('vsDialogInput');
    var knopfOk     = document.getElementById('vsDialogOk');
    var knopfAb     = document.getElementById('vsDialogCancel');

    var laufend        = null;   // { einstellungen, loesen }
    var warteschlange  = [];
    var fokusVorher    = null;

    function zeigen(auftrag) {
        laufend = auftrag;
        var e = auftrag.einstellungen;

        if (e.titel) { elTitel.textContent = e.titel; elTitel.hidden = false; }
        else         { elTitel.textContent = '';      elTitel.hidden = true;  }

        elText.textContent = e.text == null ? '' : String(e.text);

        elEingabe.hidden = !e.eingabe;
        elEingabe.value  = e.eingabe ? (e.vorgabe || '') : '';

        knopfAb.hidden      = !e.abbrechen;
        knopfAb.textContent = BESCHRIFTUNG_ABBRECHEN;
        knopfOk.textContent = e.okBeschriftung || BESCHRIFTUNG_SCHLIESSEN;

        fokusVorher = document.activeElement;
        hintergrund.hidden = false;

        // Erst nach dem Sichtbarwerden fokussieren, sonst nimmt Safari den
        // Fokus nicht an.
        window.requestAnimationFrame(function () {
            if (e.eingabe) { elEingabe.focus(); elEingabe.select(); }
            else           { knopfOk.focus(); }
        });
    }

    function schliessen(ergebnis) {
        if (!laufend) { return; }
        var auftrag = laufend;
        laufend = null;
        hintergrund.hidden = true;

        if (fokusVorher && typeof fokusVorher.focus === 'function') {
            try { fokusVorher.focus(); } catch (ignoriert) {}
        }
        fokusVorher = null;

        auftrag.loesen(ergebnis);

        if (warteschlange.length) {
            zeigen(warteschlange.shift());
        }
    }

    function bestaetigt() {
        if (!laufend) { return; }
        var e = laufend.einstellungen;
        schliessen(e.eingabe ? elEingabe.value : true);
    }

    function abgebrochen() {
        if (!laufend) { return; }
        var e = laufend.einstellungen;
        // Ein reiner Hinweis kennt kein Nein: Escape schliesst ihn, das
        // Ergebnis bleibt true.
        if (!e.abbrechen) { schliessen(true); return; }
        schliessen(e.eingabe ? null : false);
    }

    knopfOk.addEventListener('click', bestaetigt);
    knopfAb.addEventListener('click', abgebrochen);

    hintergrund.addEventListener('click', function (ereignis) {
        if (ereignis.target === hintergrund) { abgebrochen(); }
    });

    document.addEventListener('keydown', function (ereignis) {
        if (!laufend) { return; }
        if (ereignis.key === 'Escape') {
            ereignis.preventDefault();
            abgebrochen();
        } else if (ereignis.key === 'Enter' && ereignis.target !== knopfAb) {
            ereignis.preventDefault();
            bestaetigt();
        } else if (ereignis.key === 'Tab') {
            // Fokus im Dialog halten.
            var ziele = [];
            if (!elEingabe.hidden) { ziele.push(elEingabe); }
            if (!knopfAb.hidden)   { ziele.push(knopfAb); }
            ziele.push(knopfOk);
            if (!kasten.contains(document.activeElement)) {
                ereignis.preventDefault();
                ziele[0].focus();
                return;
            }
            var i = ziele.indexOf(document.activeElement);
            if (i === -1) { return; }
            ereignis.preventDefault();
            var weiter = ereignis.shiftKey ? i - 1 : i + 1;
            if (weiter < 0)             { weiter = ziele.length - 1; }
            if (weiter >= ziele.length) { weiter = 0; }
            ziele[weiter].focus();
        }
    });

    function vsDialog(einstellungen) {
        return new Promise(function (loesen) {
            var auftrag = { einstellungen: einstellungen || {}, loesen: loesen };
            if (laufend) { warteschlange.push(auftrag); }
            else         { zeigen(auftrag); }
        });
    }

    window.vsDialog = vsDialog;

    window.vsAlert = function (text, titel) {
        return vsDialog({ text: text, titel: titel });
    };

    window.vsConfirm = function (text, titel) {
        return vsDialog({ text: text, titel: titel, abbrechen: true,
                          okBeschriftung: BESCHRIFTUNG_BESTAETIGEN });
    };

    window.vsPrompt = function (text, vorgabe, titel) {
        return vsDialog({ text: text, titel: titel, abbrechen: true, eingabe: true,
                          vorgabe: vorgabe || '',
                          okBeschriftung: BESCHRIFTUNG_BESTAETIGEN });
    };

    // ── Drei Helfer fuer onclick und onsubmit im Markup ───────────────────
    //
    // Dort stand bisher `return confirm(...)`: der Browser hielt an, wartete
    // auf die Antwort und liess Klick oder Absenden danach weiterlaufen oder
    // eben nicht. vsConfirm haelt nicht an — die Antwort kommt spaeter. Also
    // muss die Stelle den Vorgang zuerst abbrechen und ihn nach einem Ja
    // selbst neu ausloesen. Genau da entsteht der Fehler, den man nicht
    // sieht: loest man ihn falsch neu aus, laeuft er zweimal.
    //
    // Deshalb stehen die drei Wege hier einmal und nicht dreizehnmal im
    // Markup.

    // Link: <a href="..." onclick="return vsConfirmLink(event, 'Text?')">
    window.vsConfirmLink = function (ereignis, text) {
        ereignis.preventDefault();
        // Das Ziel jetzt merken — nach dem Zustellen ist currentTarget leer.
        var ziel = ereignis.currentTarget.href;
        vsConfirm(text).then(function (ja) {
            if (ja) { window.location.href = ziel; }
        });
        return false;
    };

    // Formular: <form onsubmit="return vsConfirmForm(event, 'Text?')">
    window.vsConfirmForm = function (ereignis, text) {
        var formular = ereignis.currentTarget;
        if (formular.dataset.vsBestaetigt === 'ja') {
            formular.dataset.vsBestaetigt = '';
            return true;
        }
        ereignis.preventDefault();
        vsConfirm(text).then(function (ja) {
            if (!ja) { return; }
            // submit() loest onsubmit NICHT erneut aus — kein zweiter Durchlauf,
            // aber auch keine Pruefung der Pflichtfelder. requestSubmit() macht
            // beides richtig, deshalb hat es den Vorrang; die Marke schuetzt
            // den zweiten Durchlauf.
            if (typeof formular.requestSubmit === 'function') {
                formular.dataset.vsBestaetigt = 'ja';
                formular.requestSubmit();
            } else {
                formular.submit();
            }
        });
        return false;
    };

    // Absende-Knopf im Formular:
    //   <button type="submit" name="..." value="..."
    //           onclick="return vsConfirmSubmit(event, 'Text?')">
    // Wichtig ist hier, dass Name und Wert des Knopfes mitgeschickt werden —
    // die Seite unterscheidet daran, welche Aktion gemeint war. Genau das
    // leistet requestSubmit(knopf).
    window.vsConfirmSubmit = function (ereignis, text) {
        var knopf = ereignis.currentTarget;
        if (knopf.dataset.vsBestaetigt === 'ja') {
            knopf.dataset.vsBestaetigt = '';
            return true;
        }
        ereignis.preventDefault();
        var formular = knopf.form;
        if (!formular) { return false; }
        vsConfirm(text).then(function (ja) {
            if (!ja) { return; }
            if (typeof formular.requestSubmit === 'function') {
                formular.requestSubmit(knopf);
            } else {
                knopf.dataset.vsBestaetigt = 'ja';
                knopf.click();
            }
        });
        return false;
    };
})();
</script>
