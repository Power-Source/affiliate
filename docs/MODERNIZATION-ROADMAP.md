# PS Affiliate Modernisierungsroadmap

## Ziele

- Sicherheitsrisiken reduzieren, ohne bestehende Integrationen zu brechen.
- Das aktuell nachgewiesene Verhalten durch automatisierte Tests absichern.
- Core, Administration und Plugin-Integrationen schrittweise entkoppeln.
- Eine nachvollziehbare Support-Matrix für ClassicPress, WordPress und PHP pflegen.

## Leitlinien

- Kein Big-Bang-Rewrite.
- Jede Phase muss einzeln auslieferbar und rückwärtskompatibel sein.
- Sicherheitsänderungen haben Vorrang vor strukturellen Refactorings.
- Vor größeren Umbauten werden die betroffenen Abläufe automatisiert getestet.

## Phase 1: Sicherheits-Härtung

**Status: Abgeschlossen**

### Arbeitspaket 1.1: Zentrale Angriffsflächen

- [x] Installations- und Aktivierungsstatus von Abhängigkeiten zuverlässig unterscheiden.
- [x] Core-Detaildatensätze für Guthaben und Belastungen reparieren.
- [x] Affiliate-Cookies mit `Secure`, `HttpOnly` und `SameSite=Lax` setzen.
- [x] Zentrale Record-Abfrage vollständig mit `$wpdb->prepare()` aufbauen.
- [x] Einstellungen vor dem Speichern validieren und bereinigen.
- [x] Zahlungs-Export mit Capability-Prüfung und vorbereiteter SQL-Abfrage härten.
- [x] Add-on-Aktionen auf erlaubte Dateinamen begrenzen und strikt autorisieren.

### Arbeitspaket 1.2: Weitere Admin-Aktionen

- [x] Benutzerbezogene Credit-, Debit- und Payment-Aktionen zentral validieren.
- [x] Profiländerungen auf Capability, Nonce und erwartete Werte prüfen.
- [x] Alle schreibenden GET-/POST-Einstiegspunkte inventarisieren und absichern.
- [x] Fehler nicht mehr unterdrücken, sondern über projektweite Logging-Hooks melden.

### Arbeitspaket 1.3: Ausgabe und Datenbank

- [x] Alle HTML-, Attribut-, URL- und JavaScript-Ausgaben kontextabhängig escapen.
- [x] Verbleibende dynamische SQL-Fragmente parameterisieren.
- [x] JSONP-Endpunkte entfernen oder auf sichere JSON-Antworten migrieren.
- [x] Tabellen- und Exportausgaben gegen Formelinjektion und ungültige Datensätze härten.

### Akzeptanzkriterien

- Jede schreibende Admin-Aktion besitzt Capability- und CSRF-Schutz.
- Direkte Request-Werte werden vor Verwendung mit `wp_unslash()` und passenden Sanitizern verarbeitet.
- Keine nutzerbeeinflussbaren Werte werden in SQL-Strings konkateniert.
- Dynamische Ausgaben werden passend zum Ausgabekontext escaped.
- Affiliate-Cookies verwenden moderne Sicherheitsattribute.

## Phase 2: Automatisierte Tests

**Status: Abgeschlossen**

- [x] PHPUnit-/Integrationstest-Infrastruktur einrichten.
- [x] Core-Hooks für Klick, Signup, Purchase, Credit, Debit und Referrer testen.
- [x] Verzeichnis, Kleinanzeigen, MarketPress, Mitgliedschaften und Bloghosting abdecken.
- [x] Single-Site, Multisite und Netzwerkaktivierung testen.
- [x] Doppelte Zahlungsbenachrichtigungen und Idempotenz prüfen.
- [x] Sicherheitsregressionen für Nonces, Capabilities, SQL und Escaping ergänzen.

### Akzeptanzkriterien

- Alle zentralen Provisionspfade sind reproduzierbar getestet.
- Tests laufen lokal und in CI mit dokumentierten PHP-Versionen.
- Fehlerhafte oder doppelte Events erzeugen keine falschen Provisionen.

## Phase 3: Core-Modernisierung

**Status: Abgeschlossen**

- [x] Datenbankzugriff in ein Repository extrahieren.
- [x] Transaktionsberechnung und -speicherung in einen Service verschieben.
- [x] Request-Verarbeitung, Geschäftslogik und HTML-Ausgabe trennen.
- [x] Globale Instanzen schrittweise durch klar definierte Abhängigkeiten ersetzen.
- [x] Einheitliche Value Objects für Geldbeträge, Perioden und Transaktionen einführen.

## Phase 4: Integrationsarchitektur

**Status: Geplant**

- Gemeinsame Integrationsschnittstelle für unterstützte Plugins definieren.
- Hook-Registrierung, Verfügbarkeit und Status zentral verwalten.
- Integrationen unabhängig testbar machen.
- Legacy-Hooks dokumentieren und mit Deprecation-Pfad versehen.

## Phase 5: Support und Qualität

**Status: Geplant**

- Unterstützte ClassicPress-, WordPress- und PHP-Versionen festlegen.
- Coding Standards, statische Analyse und CI aktivieren.
- Veraltete APIs und Kompatibilitätszweige kontrolliert entfernen.
- Administrator- und Entwicklerdokumentation aktualisieren.

## Empfohlene Reihenfolge

1. Phasen 1 bis 3 bilden den abgeschlossenen, gehärteten und testgestützten Core.
2. Integrationen aus Phase 4 einzeln migrieren.
3. Phase 5 parallel zu den strukturellen Arbeiten ausbauen.
