=== Plugin Submenu Mover ===
Contributors: daveshine
Tags: admin, menu, navigation
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin-Menüs ordnen, Werkzeuge im Plugin-Hub bündeln und persönliche Favoriten schnell erreichen.

== Description ==
Plugin Submenu Mover entstand 2022 aus meinem eigenen WordPress-Alltag: Mit jedem zusätzlichen Plugin wurde das Adminmenü voller – und der Weg zur eigentlich gesuchten Einstellung länger. Ich wollte meine Werkzeuge schnell erreichen und wieder mehr Übersicht haben. Daraus wurde zunächst ein Code Snippet, 2025 schließlich ein eigenes Plugin.

Mit Version 2.0 wächst diese Idee zu einem flexiblen Arbeitsplatz für die tägliche Plugin-Verwaltung. Einzelne Plugin-Seiten lassen sich in passende Untermenüs einordnen. Umfangreichere Werkzeuge finden im optionalen Plugin-Hub zusammen: mit übersichtlichen Karten, schneller Suche und einem Kontext-Menü, das die ursprünglichen Plugin-Seiten erreichbar hält.

Persönliche Favoriten, kurze Bezeichnungen und dauerhaft eingehängte Plugin-Gruppen verkürzen wiederkehrende Wege. Arbeitsansichten helfen beim Wechsel zwischen verschiedenen Aufgaben. Und auf Kundenwebsites lässt sich festlegen, welche Benutzer den Hub verwenden – während andere ihren vertrauten Admin behalten.

Das Ziel ist seit 2022 dasselbe: weniger Zeit mit Suchen verbringen und leichter mit WordPress arbeiten.

Weniger Menüchaos. Mehr Überblick. Plugin-Menüs sinnvoll einordnen und Platz für die tägliche Arbeit schaffen.
Deine Plugins. Ein übersichtlicher Hub. Karten, Seitensuche und Kontext-Menü bringen umfangreiche Plugins zusammen.
Deine Werkzeuge bleiben griffbereit. Eine ausgewählte Plugin-Gruppe bleibt auch auf anderen Adminseiten erreichbar.
Deine wichtigsten Seiten. Dein direkter Weg. Persönliche Favoriten mit Kurzbezeichnungen im Hub, Adminmenü und in der Toolbar.
Dein Workflow. Der vertraute Admin für Kunden. Einzelne Benutzer ausschließen, während andere den gesamten Hub nutzen.

== Installation ==
1. Plugin-ZIP auf einer Test-Website installieren und aktivieren.
2. Werkzeuge → Plugin Submenu Mover öffnen oder Einstellungen in der Plugin-Liste wählen.
3. Automatische Verschiebungen prüfen. Ausnahmen oder Ziele anpassen, Änderungen vorab prüfen und anschließend übernehmen.
4. Für den Hub Plugin-Hub einschalten und bei den gewünschten Gruppen Im Hub verlinken oder Im Hub bündeln wählen. Änderungen prüfen und übernehmen.
5. Plugin-Hub öffnen. Karten, Seitensuche, Sterne und Plugin-Wechsler nutzen. Vorlagen für Arbeitsansichten auf der Einstellungsseite zusammenstellen.

== Frequently Asked Questions ==
= Ändert ein aufgeräumtes Menü die Zugriffsrechte? =
Nein. Jedes Ziel behält seine ursprüngliche Berechtigung. Arbeitsansichten und Menüsichtbarkeit dienen der Navigation und sind keine Zugriffskontrolle.

= Warum fehlt ein Plugin oder bleibt unverändert? =
Der Hub zeigt registrierte, erlaubte Hauptmenüs außerhalb des WordPress-Kerns; Core-Menüs und Inhaltstyp-Hauptmenüs sind ausgenommen. Eine Seite kann für das eigene Konto fehlen, extern verlinkt oder nicht registriert sein. Automatische Verschiebungen verwenden die kuratierte Registry. Regeln nicht verfügbarer Integrationen bleiben gespeichert.

= Kann ich zehn Plugins bündeln? =
Der Hub unterstützt mehrere ausgewählte Gruppen, auch zehn oder mehr. Karten und Kontext-Menü verwenden dieselben registrierten Ziele. Die Gruppenanzahl garantiert keine Kompatibilität mit beliebigen Drittplugin-Versionen.

= Was passiert nach einem Update des Zielplugins? =
Eine geänderte registrierte Menüstruktur verhindert das Ausblenden der ursprünglichen Gruppe bis zur erneuten Prüfung. Version und Identität erscheinen, wenn das installierte Plugin anhand seines registrierten Callbacks zugeordnet werden kann. Menüerkennung bestätigt keinen vollständigen Funktionstest.

= Funktioniert das mit einem weiteren Menüeditor? =
Pro Gruppe sollte ein Werkzeug zuständig sein. Weitere Editoren können Registrierung oder Auswahlmarkierung verändern. Bei der Fehlersuche Verlinkung oder Originalansicht verwenden; eine pauschale Kompatibilitätszusage gibt es nicht.

= Was gilt für Multisite? =
Regeln, Hub-Auswahl und Arbeitsansichten gehören zur einzelnen Website; Favoriten und Verlauf zum Benutzer und zur Website. Netzwerk-Admin-Menüs bleiben unverändert. Bei Netzwerkaktivierung gelten die jeweiligen Website-Einstellungen; neue Websites beginnen mit Standardwerten.

= Welche Daten bleiben nach Deaktivierung oder Deinstallation? =
Deaktivierung erhält die Konfiguration. Deinstallation entfernt temporäre Vorschau-, Rückgängig- und Originalansicht-Daten. Website-Einstellungen und persönliche Schnellzugriffe bleiben standardmäßig erhalten; die ausdrückliche Löschoption entfernt eigene Daten dieser Website. Bei Netzwerkdeinstallation gilt die Auswahl jeder Website. Die gemeinsame Library bereinigt Daten nach ihrer Letzt-Host-Regel. Andere Plugins und deren Inhalte bleiben erhalten.

== Changelog ==
= 2.0.0 · 2026-10-19 =
* Neu: Optionaler Plugin-Hub mit Karten, Suche und dauerhaft eingehängter Plugin-Gruppe.
* Neu: Persönliche Favoriten und Kurzbezeichnungen, Befehlspalette und Site Health.
* Neu: Positionswahl, Auswahlvorlagen und durchsuchbare Benutzer-Ausnahmen.
* Verbessert: Navigation, Einstellungen und Vektorgrafiken.
* Behoben: Erkennung von ACF und JetFormBuilder.
* Sonstiges: Erfordert PHP 8.3 oder neuer; WordPress weiterhin 6.7 oder neuer.











= 1.4.0 · 2025-03-28 =
* Neu: Erste öffentliche Version mit klassenbasierter Architektur.
* Sonstiges: Grundlage ist die seit 2022 privat verwendete Version.

= 1.0.0 · 2022-07 =
* Neu: Erste private Version.
