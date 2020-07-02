# REPLACE-GLOBAL-WITH-NEXT-VERSION
- PT-11669 - Kompatibilität mit dem Zahlungsprozess nach einer Bestellung hinzugefügt
- PT-11707 - Individuelle Formular-Parameter der Bestellseite werden nicht mehr ignoriert
- PT-11748 - Weiterleitungs-URL für PayPal Plus und Express Checkout korrigiert. Die Webhook-URL wurde geändert, sodass sie unabhängig von einer Storefront ist
- PT-11773 - Kaufen von Custom Products mit PayPal korrigiert

# 1.6.0
- PT-11519 - Registriert Webhooks mit HTTPS
- PT-11593 - Hinweis an der "Zahlungsabschluss"-Option hinzugefügt, um Verwendung mit PayPal PLUS zu verdeutlichen
- PT-11704 - Anzeige des Express-Checkout-Buttons auf paginierten Produktlistenseiten korrigiert
- PT-11706 - Beim Express Checkout wird nun auch das Bundesland gespeichert
- PT-11717 - Fügt PayPal-Cookies zum Cookie-Manager hinzu

# 1.5.2
- PT-10502 - Abbrüche der Zahlung auf der PayPal-Seite führen nicht mehr zu Fehlern
- PT-11710 - Korrigiert die Installation des Plugins in Umgebungen, in denen die Standardsprache nicht de-DE oder en-GB ist

# 1.5.1
- PT-10640 - Behebt ein Problem mit den SalesChannel-API Routen
- PT-10897 - Ländercodevalidierung für Smart Payment Buttons und Express Checkout
- PT-11294 - Fehlerbehandlung für Smart Payment Buttons
- PT-11582 - Wehook-Registrierung korrigiert
- PT-11637 - Einzugs- und Rückerstattungs-Workflow verbessert

# 1.5.0
- NEXT-8322 - Shopware 6.2 compatibility
- PT-10654 - Aktivieren und setzen Sie PayPal als Standard für den ausgewählten Saleschannel im Einstellungsmodul
- PT-11599 - Behebt ein Problem, bei dem PayPal Plus nicht per Saleschannel konfiguriert werden konnte

# 1.4.0
- PT-11540 - Korrigiert übrigen Betrag für mehrfache teilweise Erstattungen
- PT-11541 - Verhalten von mehrfachen partiellen Erstattungen & Einzügen einer Transaktion verbessert
- PT-11606 - Shopware 6.2 Kompatibilität

# 1.3.0
- PT-10448 - API-Zugangsdaten können nun auch im Einstellungsmodul über einen PayPal Login geholt werden
- PT-11292 - Zeigt nun auch Möglichkeit zum Eingeben separater Zugangsdaten für den Sandbox Modus im First-Run-Wizard
- PT-11498 - Der PayPal Express Button wird nun in der QuickView des CMS Extensions Plugin angezeigt
- PT-11550 - Korrigiert die Nutzung der Sandbox-Zugangsdaten nach einem Update

# 1.2.0
- PT-11233 - Der PayPal Express Button wird auf der Produkt-Detail-Seite nicht mehr angezeigt, wenn der Artikel im Abverkauf ist
- PT-11292 - Möglichkeit zum Eingeben separater Zugangsdaten für den Sandbox Modus

# 1.1.1
- PT-11443 - Behebt ein Problem mit der Fehlerbehandlung bei den Paypal-Zugangsdaten
- PT-11475 - Verarbeitung von Gutscheinen während des Checkouts verbessert

# 1.1.0
- PT-11276 - Banner für das Bewerben von Ratenzahlungen hinzugefügt

# 1.0.0
- PT-11181, PT-11275 - PayPal PLUS Integration hinzugefügt
- PT-11277 - Die Übermittlung des Warenkorbs und der Bestellnummer ist jetzt standardmäßig aktiv

# 0.13.0
- Shopware 6.1 Kompatibilität

# 0.12.0
- PT-10287 - Fügt die Möglichkeit hinzu, die Rechnungsnummer, Beschreibung oder den Grund während der Rückerstattung einer Bestellung anzugeben
- PT-10705 - Die PayPal-Einstellungen befinden sich nun in einem eigenen Administrationsmodul
- PT-10771 - Verbessert die Anzeige von Smart Payment Buttons
- PT-10775 - Verbessert das Status-Handling der Bestelltransaktion
- PT-10809 - Smart Payment Buttons können nun getrennt vom Express Checkout Button gestylt werden
- PT-10821 - Behebt Fehler bei der Sale-Complete-Webhook-Ausführung
- NEXT-4282 - Neuinstallation des Plugins dupliziert keine Konfigurationseinträge mehr

# 0.11.2
- PT-10733 - Problem beim automatischen Holen der API Credentials im First-Run-Wizard behoben

# 0.11.1
- PT-10755 - Fehler bei der Deinstallation und Konfigurationsfehler behoben

# 0.11.0
- PT-10391 - Kauf auf Rechnung implementiert
- PT-10695 - Error-Logging für API-Calls hinzugefügt
- PT-10702 - URL für Smart Payment Buttons Javascript angepasst
- PT-10715 - Paypal wird wieder korrekt als Zahlungsart bei Express Checkout ausgewählt
- PT-10723 - Smart Payment Buttons schließen nun nicht mehr direkt die Bestellung ab
- PT-10729 - Die PayPal-Zahlungsbeschreibung zeigt nun verfügbare Zahlungen mit Symbolen an

# 0.10.1
- Generierung der Links für Javascript-API-Calls verbessert

# 0.10.0
- Onboarding für den First-Run-Wizard hinzugefügt

# 0.9.0
- Erste Version der PayPal-Integrationen für Shopware 6
