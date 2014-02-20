<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * German language strings for search block
 * @package	   block_search
 * @copyright	 Anthony Kuske <www.anthonykuske.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Suche';
$string['pagetitle'] = 'Suche';
$string['search'] = 'Suche';
$string['search_input_text_block'] = 'Dieser Kurs suchen';
$string['search_input_text_page'] = 'Kurse, Aktivitäten oder Dokumente zu finden.';
$string['search_options'] = 'Suchoptionen:';
$string['search_all_of_site'] = 'Suche alle von {$a}';
$string['search_in_course'] = 'Suche in {$a}';
$string['include_hidden_results'] = 'Enthalten Sie die Ergebnisse habe ich Zugriff auf nicht';
$string['search_results_for'] = 'Suchergebnisse für \'{$a}\'';
$string['search_results'] = 'Suchergebnisse';
$string['items_found'] = '{$a} Einträge gefunden';
$string['showing'] = 'Ergebnis {$a->start}, {$a->end} von {$a->total}';
$string['no_results'] = 'Tut mir leid, wurden es keine Ergebnisse für Ihre Suche.';
$string['hidden_not_enrolled'] = 'Sie sind nicht in diesem Kurs eingeschrieben.';
$string['hidden_not_available'] = 'Diese Ressource ist nicht für Sie zur Verfügung.';
$string['folder_contents'] = 'Dateien in Ordnern';
$string['search_took'] = 'Suchdauer: <strong>{$a}</strong> Sekunden.';
$string['cached_results_generated'] = 'Zwischengespeicherte Ergebnisse <strong>{$a}</strong>.';
$string['filtering_took'] = 'Filtern von Ergebnissen hat <strong>{$a}</strong> Sekunden gedauert.';
$string['user_cached_results_generated'] = 'Personalisierte zwischengespeicherten Ergebnisse aus <strong>{$a}</strong>.';
$string['displaying_took'] = 'Resultate hat <strong>{$a}</strong> Sekunden gedauert.';
$string['settings_search_tables_name'] = 'Suchtabellen';
$string['settings_search_tables_desc'] = 'Welche Tabellen in der Datenbank werden durchsucht.';
$string['selectall'] = 'Wählen Sie alle';
$string['settings_cache_results_name'] = 'Cache-Ergebnisse für';
$string['settings_cache_results_desc'] = 'Cache-Suchergebnisse für wie lange (in Sekunden). 0 bedeutet keine Zwischenspeicherung. Standardwert ist 1 Tag. Dieser Cache speichert die Ergebnisse aus der Datenbank, bevor sie für einen bestimmten Benutzer personalisiert werden, (bevor Ergebnisse, die, denen der Benutzer Zugriff auf haben nicht, entfernt werden). Was bedeutet dieser Cache kann zwischen verschiedenen Benutzern gemeinsam genutzt werden und bietet Vorteile, wenn verschiedene Benutzer sind auf der Suche nach den gleichen Bedingungen. Wenn die Inhalte auf Ihrer Website, die sich nicht häufig ändern, können Sie diese höheren Wert festlegen.';
$string['settings_cache_results_per_user_name'] = 'Cache-benutzerspezifische Ergebnisse für';
$string['settings_cache_results_per_user_desc'] = 'Cache gefiltert-Ergebnisse für wie lange (in Sekunden). 0 bedeutet keine Zwischenspeicherung. Standardwert ist 15 Minuten. Dieser Cache speichert die Ergebnisse * nach * Ergebnisse der Benutzer hat keinen Zugriff auf die entfernt wurden. Jedes Element in diesem Cache ist spezifisch für einen einzelnen Benutzer, so dass es nur einen Vorteil bietet, wenn dieselbe Person für die gleiche Sache sucht, wieder (oder wenn sie zu einer anderen Seite in den Ergebnissen gehen). Es ist empfohlen, haben dies für mindestens ein paar Minuten aktiviert werden, damit Benutzer ohne die Ergebnisse generiert werden, auf jeder Seite mit allen Seiten des Ergebnisse anzeigen können. Wenn sie deaktiviert ist, muss die gesamte Suche erneut ausgeführt werden, wenn ein Benutzer auf eine andere Seite der Ergebnisse geht. Wenn Sie, dass Ihre Benutzer oft für die gleiche Sache sucht denken, überlegen Sie, diesen Wert zu erhöhen.';
$string['settings_log_searches_name'] = 'Protokoll-Suche';
$string['settings_log_searches_desc'] = 'Suchanfragen in den Moodle-Protokollen protokolliert werden sollen?';
$string['settings_allow_no_access_name'] = 'Versteckte Ergebnisse anzeigen';
$string['settings_allow_no_access_desc'] = 'Erlauben Sie Benutzern, kreuzen Sie "Enthalten Ergebnisse habe ich Zugriff auf nicht", siehe Ergebnisse, die nicht zur Verfügung. (Dies lässt nicht auf den eigentlichen Inhalt zuzugreifen, der gefunden wird. "Aber der Benutzer kann sehen, dass sie existiert.)';
$string['settings_search_files_in_folders_name'] = 'Suche nach Dateien in Ordner-Aktivitäten';
$string['settings_search_files_in_folders_desc'] = 'Sucht Dateien in "Ordner" Aktivitäten/Ressourcen in Kursen zu finden versuchen sollte?';
$string['settings_results_per_page_name'] = 'Ergebnisse pro Seite';
$string['settings_results_per_page_desc'] = 'Wie viele Suchergebnisse pro Seite zeigen';
$string['settings_text_substitutions_name'] = 'Textersetzungen';
$string['settings_text_substitutions_desc'] = 'Textersetzungen erlauben Benutzern, verkürzte Wörter/Phrasen suchen, aber immer noch Ergebnisse, die den vollständigen Ausdruck enthalten. Beispielsweise kann ein Benutzer "Docs" suchen und erhalten Ergebnisse, die das Wort "Dokumente" bzw. "Docs" enthalten.
Geben Sie jede Ersetzung auf eigener Zeile in diesem Format:<pre>Docs => Dokumente
App => Anwendung
einige Phrase => einige viel längere Phrase</pre>';
$string['advanced_search_title'] = 'Erweiterte Suchoptionen';
$string['advanced_search_desc'] = 'Fügen Sie diese Worte zu Ihrer Suche um die Ergebnisse zu verfeinern.';
$string['advanced_search_exclude_example'] = '-Wort';
$string['advanced_search_exclude_desc'] = 'Ergebnisse dieser <strong>nicht</strong> finden dieses Wort enthalten.';
$string['advanced_search_exact_example'] = 'Wörter in Anführungszeichen';
$string['advanced_search_exact_desc'] = 'Finden Sie Ergebnisse, die diese <strong>genaue Phrase</strong> enthalten';

//TODO: Example that works in german here
$string['advanced_search_wildcard_example'] = 'w*d';
$string['advanced_search_wildcard_desc'] = '* ist ein <strong>Platzhalter</strong>. Dies würde "Wort" und "komisch" übereinstimmen.';

$string['search:search'] = 'Führen Sie eine Suche';
