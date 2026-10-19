# Fragen nach Themen

## Ändert ein aufgeräumtes Menü die Zugriffsrechte?

Nein. Jedes Ziel behält seine ursprüngliche Berechtigung. Arbeitsansichten und Menüsichtbarkeit dienen der Navigation und sind keine Zugriffskontrolle.

## Warum fehlt ein Plugin oder bleibt unverändert?

Der Hub zeigt registrierte, erlaubte Hauptmenüs außerhalb des WordPress-Kerns; Core-Menüs und Inhaltstyp-Hauptmenüs sind ausgenommen. Eine Seite kann für das eigene Konto fehlen, extern verlinkt oder nicht registriert sein. Automatische Verschiebungen verwenden die kuratierte Registry. Regeln nicht verfügbarer Integrationen bleiben gespeichert.

## Kann ich zehn Plugins bündeln?

Der Hub unterstützt mehrere ausgewählte Gruppen, auch zehn oder mehr. Karten und Kontext-Menü verwenden dieselben registrierten Ziele. Die Gruppenanzahl garantiert keine Kompatibilität mit beliebigen Drittplugin-Versionen.

## Was passiert nach einem Update des Zielplugins?

Eine geänderte registrierte Menüstruktur verhindert das Ausblenden der ursprünglichen Gruppe bis zur erneuten Prüfung. Version und Identität erscheinen, wenn das installierte Plugin anhand seines registrierten Callbacks zugeordnet werden kann. Menüerkennung bestätigt keinen vollständigen Funktionstest.

## Funktioniert das mit einem weiteren Menüeditor?

Pro Gruppe sollte ein Werkzeug zuständig sein. Weitere Editoren können Registrierung oder Auswahlmarkierung verändern. Bei der Fehlersuche Verlinkung oder Originalansicht verwenden; eine pauschale Kompatibilitätszusage gibt es nicht.

## Was gilt für Multisite?

Regeln, Hub-Auswahl und Arbeitsansichten gehören zur einzelnen Website; Favoriten und Verlauf zum Benutzer und zur Website. Netzwerk-Admin-Menüs bleiben unverändert. Bei Netzwerkaktivierung gelten die jeweiligen Website-Einstellungen; neue Websites beginnen mit Standardwerten.

## Welche Daten bleiben nach Deaktivierung oder Deinstallation?

Deaktivierung erhält die Konfiguration. Deinstallation entfernt temporäre Vorschau-, Rückgängig- und Originalansicht-Daten. Website-Einstellungen und persönliche Schnellzugriffe bleiben standardmäßig erhalten; die ausdrückliche Löschoption entfernt eigene Daten dieser Website. Bei Netzwerkdeinstallation gilt die Auswahl jeder Website. Die gemeinsame Library bereinigt Daten nach ihrer Letzt-Host-Regel. Andere Plugins und deren Inhalte bleiben erhalten.

## Kann die Gruppenleiste ohne Lösen der Gruppe ausgeblendet werden?

Ja. Die persönliche Einstellung Gruppenleiste auf Admin-Seiten anzeigen ausschalten. Adminmenü und Toolbar behalten die eingehängte Gruppe. Andere Konten bleiben unverändert.
