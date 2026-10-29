# PS Affiliate Core-Architektur

## Transaktionsfluss

Die öffentlichen WordPress-Hooks bleiben die stabile Kompatibilitätsschicht:

- `affiliate_click`
- `affiliate_signup`
- `affiliate_purchase`
- `affiliate_credit`
- `affiliate_debit`
- `affiliate_referrer`

Die Klasse `affiliate` nimmt diese Hooks weiterhin entgegen, delegiert die Geschäftslogik jedoch an `Affiliate_Transaction_Service`. Dadurch müssen bestehende Integrationen nicht geändert werden.

## Domain-Objekte

- `Affiliate_Money` normalisiert Geldbeträge auf ganzzahlige Untereinheiten und gibt Datenbankwerte mit zwei Dezimalstellen aus.
- `Affiliate_Period` validiert Perioden im Format `YYYYMM`.
- `Affiliate_Transaction` enthält eine validierte, unveränderliche Core-Transaktion.
- `Affiliate_Request_Context` kapselt bereinigte Request-, Cookie- und Serverdaten.

## Datenzugriff

`Affiliate_Repository` ist die zentrale Datenzugriffsschicht für:

- periodische Affiliate-Summen,
- Transaktionsdetails,
- Referrer-Zähler,
- Affiliate-Benutzersuche,
- Idempotenzprüfungen,
- gefilterte Detailabfragen.

Datenbankfehler werden über den Hook `affiliate_repository_error` gemeldet. Abgelehnte Transaktionen werden über `affiliate_transaction_rejected` signalisiert.

## Abhängigkeiten

`affiliate` erstellt standardmäßig Repository und Service selbst. Tests oder künftige Integrationen können beide Abhängigkeiten über den Konstruktor injizieren:

```php
$affiliate = new affiliate( $repository, $transaction_service );
```

Die bisherigen öffentlichen Eigenschaften und Methoden bleiben während der schrittweisen Migration verfügbar. Neue Core-Logik sollte nicht direkt auf `$wpdb` oder die Tabellennamen der Fassade zugreifen, sondern das Repository beziehungsweise den Transaktionsservice verwenden.
