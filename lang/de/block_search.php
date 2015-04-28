<?php

/**
 * German language strings for search block
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Suche';
$string['pagetitle'] = 'Suche';
$string['search'] = 'Suche';
$string['search_input_text_block'] = 'Diesen Kurs suchen';
$string['search_input_text_page'] = 'Finde Kurse, Aktivitäten oder Dokumente';
$string['search_options'] = 'Suchoptionen:';
$string['search_all_of_site'] = 'Globale Suche in {$a}';
$string['search_in_course'] = 'Suche nur in {$a}';
$string['include_hidden_results'] = 'Zeigen von Ergebnissen, auf die ich keinen Zugriff habe.';
$string['search_results_for'] = 'Suchergebnisse für \'{$a}\'';
$string['search_results'] = 'Suchergebnisse';
$string['items_found'] = '{$a} Einträge gefunden';
$string['showing'] = 'Ergebnis {$a->start}, {$a->end} von {$a->total}';
$string['no_results'] = 'Sorry, es gab keine Ergebnisse zu Ihrer Suche.';
$string['hidden_not_enrolled'] = 'Sie sind nicht in diesem Kurs eingeschrieben.';
$string['hidden_not_available'] = 'Diese Ressource ist nicht für Sie verfügbar.';
$string['folder_contents'] = 'Dateien in Ordnern';
$string['search_took'] = 'Suchdauer: <strong>{$a}</strong> Sekunden.';
$string['cached_results_generated'] = 'Zwischengespeicherte Ergebnisse <strong>{$a}</strong>.';
$string['filtering_took'] = 'Filtern von Ergebnissen hat <strong>{$a}</strong> Sekunden gedauert.';
$string['user_cached_results_generated'] = 'Personalisierte zwischengespeicherten Ergebnisse aus <strong>{$a}</strong>.';
$string['displaying_took'] = 'Resultate zeigen hat <strong>{$a}</strong> Sekunden gedauert.';
$string['settings_search_tables_name'] = 'Suchtabellen';
$string['settings_search_tables_desc'] = 'Welche Tabellen in der Datenbank werden durchsucht.';
$string['selectall'] = 'Wählen Sie alle';
$string['settings_cache_results_name'] = 'Cache-Ergebnisse für';
$string['settings_cache_results_desc'] = 'Cache-Suchergebnisse für wie lange (in Sekunden). 0 bedeutet keine Zwischenspeicherung. Standardwert ist 1 Tag. Dieser Cache speichert die Ergebnisse aus der Datenbank, bevor sie für einen bestimmten Benutzer personalisiert werden, (bevor Ergebnisse, die, denen der Benutzer Zugriff auf haben nicht, entfernt werden). Was bedeutet dieser Cache kann zwischen verschiedenen Benutzern gemeinsam genutzt werden und bietet Vorteile, wenn verschiedene Benutzer sind auf der Suche nach den gleichen Bedingungen. Wenn die Inhalte auf Ihrer Website, die sich nicht häufig ändern, können Sie diese höheren Wert festlegen.';
$string['settings_cache_results_per_user_name'] = 'Cache-benutzerspezifische Ergebnisse für';
$string['settings_cache_results_per_user_desc'] = 'Cache gefiltert-Ergebnisse für wie lange (in Sekunden). 0 bedeutet keine Zwischenspeicherung. Standardwert ist 15 Minuten. Dieser Cache speichert die Ergebnisse * nach * Ergebnisse der Benutzer hat keinen Zugriff auf die entfernt wurden. Jedes Element in diesem Cache ist spezifisch für einen einzelnen Benutzer, so dass es nur einen Vorteil bietet, wenn dieselbe Person für die gleiche Sache sucht, wieder (oder wenn sie zu einer anderen Seite in den Ergebnissen gehen). Es ist empfohlen, haben dies für mindestens ein paar Minuten aktiviert werden, damit Benutzer ohne die Ergebnisse generiert werden, auf jeder Seite mit allen Seiten des Ergebnisse anzeigen können. Wenn sie deaktiviert ist, muss die gesamte Suche erneut ausgeführt werden, wenn ein Benutzer auf eine andere Seite der Ergebnisse geht. Wenn Sie, dass Ihre Benutzer oft für die gleiche Sache sucht denken, überlegen Sie, diesen Wert zu erhöhen.';
$string['settings_log_searches_name'] = 'Log Suche';
$string['settings_log_searches_desc'] = 'Sollen Suchanfragen in den Moodle-Protokollen gelogged werden?';
$string['settings_allow_no_access_name'] = 'Versteckte Ergebnisse anzeigen';

$string['settings_allow_no_access_desc'] = 'Benutzer erlauben, Suchergebnisse zu sehen, auf die sie keinen Zugriff haben: "Zeigen von Ergebnissen, auf die ich kein Zugriff habe."  (Dies lässt nicht auf den eigentlichen Inhalt zuzugreifen, der gefunden wird. Aber der Benutzer kann sehen, dass sie existiert.)';

$string['settings_search_files_in_folders_name'] = 'Suche nach Dateien in Ordner-Aktivitäten';
$string['settings_search_files_in_folders_desc'] = 'Soll versucht werden, Dateien innerhalb von "Ordner" Aktivitäten/Ressourcen zu finden?';
$string['settings_results_per_page_name'] = 'Ergebnisse pro Seite';
$string['settings_results_per_page_desc'] = 'Wie viele Suchergebnisse pro Seite anzeigen';
$string['settings_text_substitutions_name'] = 'Textersetzungen';
$string['settings_text_substitutions_desc'] = 'Textersetzungen erlauben Benutzern, verkürzte Wörter/Phrasen suchen, aber immer noch Ergebnisse, die den vollständigen Ausdruck enthalten. Beispielsweise kann ein Benutzer "Docs" suchen und erhalten Ergebnisse, die das Wort "Dokumente" bzw. "Docs" enthalten.
Geben Sie jede Ersetzung auf eigener Zeile in diesem Format:<pre>Docs => Dokumente
App => Anwendung
einige Phrase => einige viel längere Phrase</pre>';
$string['advanced_search_title'] = 'Erweiterte Suchoptionen';
$string['advanced_search_desc'] = 'Fügen Sie diese Worte zu Ihrer Suche hinzu, um die Ergebnisse zu verfeinern.';
$string['advanced_search_exclude_example'] = '-Wort';
$string['advanced_search_exclude_desc'] = 'Finden Sie Ergebnisse, die dieses Wort <strong>nicht</strong> enthalten.';
$string['advanced_search_exact_example'] = 'Wörter in Anführungszeichen';
$string['advanced_search_exact_desc'] = 'Finden Sie Ergebnisse mit dieser <strong>exakten Phrase</strong>';

$string['advanced_search_wildcard_example'] = 'w*d';
$string['advanced_search_wildcard_desc'] = '* ist ein <strong>Platzhalter</strong>. Das würde z.B. "word" und "weird" finden.';

$string['search:search'] = 'Führen Sie eine Suche durch';

$string['error_query_too_short'] = 'Bitte geben Sie einen Suchbegriff mit mindestens {$a} Zeichen ein.';
$string['try_full_search'] = 'Wollten Sie die gesamte Seite durchsuchen anstatt nur diesen Kurs?';

// MUC
$string['cachedef_main'] = 'Suchen';
$string['cachedef_user_searches'] = 'Benutzer Suchen';
