# REPLACE_GLOBALLY_WITH_NEXT_VERSION
- PPI-624 - Verbesserte Fehlerbehandlung im Zahlungsprozess nach einer Bestellung

# 5.0.2
- PPI-621 - Behebt ein Problem, bei dem die Zahlungsmethodenübersicht in Shopware 6.4.7.0 oder niedriger fehlte
- PPI-623 - Behebt ein Problem, bei dem eine PayPal-Bestellung mit Rabatt nicht erstellt werden kann

# 5.0.1
- PPI-615 - Behebt ein Problem mit fehlenden deutschen Übersetzungen in der Administration

# 5.0.0
- PPI-317 - Separate Kreditkarten-Zahlungsmethode hinzugefügt
- PPI-385 - Neuer Rechnungskauf hinzugefügt
- PPI-410 - Separate alternative Zahlungsmethoden hinzugefügt
- PPI-418 - Kompatiblität für PHP 8.1 hinzugefügt

# 4.1.1
- PPI-395 - Texte für veraltete PayPal-Produkte entfernt

# 4.1.0
- PPI-344 - Behebt ein Problem mit ungültigen Telefonnummern mit API v1 Zahlungen
- PPI-346 - Behebt Rundungsfehler im Zahlungseinzugsfenster in der Administration
- PPI-350 - Aktivieren des Zahlungsprozess nach einer Bestellung für unbestätigte Zahlungen ab Shopware 6.4.4.0
- PPI-356 - Verbesserte Plugin-Erweiterbarkeit
- PPI-366 - Verbesserte Fehlerbehandlung bei Zahlungen
- PPI-367 - Verbesserte Formulierung der Fußzeile von Sales Channels

# 4.0.0
- PPI-252 - Fehlerbehebung von Webhooks verbessert
- PPI-327 - Verbesserte Datentypenstruktur
- PPI-343 - Behebt ein Problem, wenn der Kundenname sich vom Versandanschriftname unterscheidet
- PPI-352 - Behebt ein Problem mit Zettle Webhooks bei POS-Verkäufen

# 3.5.0
- PPI-5 - Setzen von PayPal als Standardzahlungsart im Ersteinrichtungs-Assistent implementiert
- PPI-77 - Ersetzt Snippets in der Administration durch `global.defaults`
- PPI-126 - Fehlerdarstellung bei Authorisierungsfehlern in Zettle verbessert
- PPI-270 - Der Express Checkout erstellt keine doppelten Gastkonten mehr
- PPI-293 - Skript-Ladevorgang in der Storefront verbessert
- PPI-330 - Verbessert Nachkommastellen-Verhalten der Zettle-Synchronisation
- PPI-334 - Behebt ein Problem, bei dem eine Fehlermeldung beim verzögerten Zahlungseinzug erschien
- PPI-339 - Behebt ein Problem, bei dem doppelte Symbole für externe Links in der Administration dargestellt wurden

# 3.4.0
- PPI-228 - Weiß als Farbe für ECS- und SPB-Buttons hinzugefügt
- PPI-321 - Verbessert den Ablauf bei Zettle-Synchronisationen
- PPI-322 - Verbessert das Entfernen von PayPal von den verfügbaren Zahlungsarten, wenn die Zugangsdaten ungültig sind
- PPI-323 - Behebt Probleme mit dem Spinner bei Smart Payment Buttons
- PPI-329 - Behebt Rundungsprobleme bei Anfragen mit PayPal API v2

# 3.3.1
- PPI-316 - Behebt ein Problem beim Wechseln der Standardsprache nach der Plugininstallation

# 3.3.0
- PPI-219 - PayPal wird jetzt deaktiviert, wenn der Warenkorbwert 0 ist
- PPI-227 - Die an PayPal gesendete Bestellnummer kann nun mit einem Suffix versehen werden
- PPI-281 - Verbessertes Storefront-Verhalten bei Abbrüchen und Fehlern bei Express Checkout & Smart Payment Buttons
- PPI-287 - Behebt ein Problem, bei dem der Express-Button in CMS-Buyboxen nach Variantenwechsel nicht angezeigt wurde
- PPI-289 - Behebt ein Problem, bei dem der Express Checkout Button für eingeloggte Nutzer sichtbar war
- PPI-304 - Behebt ein Problem, bei dem die Smart Payment Buttons trotz Warenkorb-Fehlern sichtbar waren

# 3.2.1
- PPI-279, PPI-297 - Erweiterung der Partner-Referral-API
- PPI-290 - Erweiterbarkeit verbessert
- PPI-295 - Behebt ein Problem, bei dem die Bestelldetails in der Administration nicht komplett angezeigt wurden
- PPI-296 - Darstellung von APMs im Footer verbessert
- PPI-298 - Behebt Probleme mit dem Spinner bei Smart Payment Buttons
- PPI-300 - Es ist nicht mehr möglich, andere Zahlungsarten beim Express Checkout auszuwählen

# 3.2.0
- PPI-262 - Behebt ein Problem, bei dem der Express-Button in CMS-Buyboxen nicht angezeigt wurde
- PPI-271 - Behebt ein Problem, bei dem der Cache bei Einstellungsänderungen nicht korrekt invalidiert wurde
- PPI-273 - Bestellnummern-Prefix wird nun immer korrekt mitgesendet
- PPI-277 - Behebt ein Problem, bei dem die Express-Zahlung bei Änderungen auf der Bestätigungsseite fehlschlug
- PPI-282 - Behebt eine Inkompatibiltät mit dem Sendcloud-Plugin
- PPI-283 - Zusätzlicher Bestätigungsschritt bei Smart Payment Buttons entfernt, sodass Alternative Zahlungsmethoden korrekt dargestellt werden

# 3.1.0
- PPI-246 - Option zur erweiterten Protokollierung hinzugefügt
- PPI-251 - Behebt ein Problem, bei dem der Transaktionsstatus bei verzögertem Zahlungseinzug nicht korrekt gesetzt wurde
- PPI-276 - Entfernt mehrere unnötige Hintergrundrequests bei PayPal Plus

# 3.0.3
- PPI-20 - Behebt ein Problem, bei dem Webhooks fehlschlugen, wenn der Zahlungsstatus bereits identisch gesetzt wurde
- PPI-235 - Behebt Webhook-Registierungsfehler bei Verkaufskanal-eigenen Zugangsdaten
- PPI-238 - Behebt ein Problem, bei dem Zahlungen über PayPal Plus nicht in den Disputes verlinkt wurden
- PPI-243 - Weitere, PayPal-spezifische Transaktionsdaten zu Zusatzfeldern hinzugefügt
- PPI-265 - Behebt Kodierungsfehler bei gekürzten Zettle-Produktbeschreibungen

# 3.0.2
- NEXT-15014 - ACL-Handling verbessert

# 3.0.1
- PPI-65 - Kompatibilität mit Shopware 6.4 und Zettle verbessert
- PPI-255 - Problem mit Express Checkout und Datenschutzbestimmungen-Checkbox behoben
- PPI-263 - Das Plugin ist jetzt valide für den Konsolenbefehl `dal:validate`

# 3.0.0
- PPI-65 - Kompatibilität für Shopware 6.4 hinzugefügt
- PPI-239 - Rebranding von iZettle auf Zettle

# 2.2.3
- PPI-256 - Behebt das Abbrechen von abgeschlossenen Bestellungen über den Browserverlauf

# 2.2.2
- PPI-244 - Problem bei der API-Authentifizierung behoben
- PPI-221 - Problem mit überlangen Produktbeschreibungen bei Zettle korrigiert

# 2.2.1
- PPI-241 - Abbruch der Bestell-Transaktionen mit dem ScheduledTask verbessert

# 2.2.0
- PPI-191 - Übersicht der PayPal-Konflikte hinzugefügt

# 2.1.2
- PPI-211 - Name der Lieferaddresse wird nun korrekt zu PayPal übertragen
- PPI-222 - Express-Checkout-Button auf der Suchseite und der Wunschliste hinzugefügt
- PPI-229 - Bestell-Transaktionen mit einer veralteten PayPal-Zahlung werden mit einem ScheduledTask abgebrochen
- PPI-231 - Löschen der Rechnungskauf-Regel beim Deinstallieren korrigiert
- PPI-234 - Entity-Definition verbessert

# 2.1.1
- PPI-208 - Weiterleitung bei abgebrochenen Plus-Zahlung in Shopware 6.3.3.x korrigiert
- PPI-210 - Verarbeitung von Promotionen beim Express Checkout verbessert
- PPI-220 - Speichern der Kundentelefonnummer beim Express Checkout korrigiert
- PPI-223 - Behebt ein Problem mit dem Status des Express Checkout Buttons
- PPI-224 - Express Checkout für Shopware-Versionen vor 6.3.2.0 korrigiert

# 2.1.0
- PPI-174 - Warenkorb- und Bestellpositionen werden jetzt mit SKU gesendet
- PPI-174 - Es wurden Events hinzugefügt, um Positionen anzupassen, die zu PayPal gesendet werden
- PPI-202 - PayPal Checkout für Kunden mit Nettopreisen korrigiert

# 2.0.2
- PPI-199 - Webhook-Log-Einträge verbessert
- PPI-200 - Übertragen von Warenkörben mit Rabatten korrigiert

# 2.0.1
- PPI-171 - Message-Queue wird nun nur noch genutzt, wenn es iZettle Sales Channels gibt
- PPI-172 - Einzugs- und Rückerstattungsprozess verbessert
- PPI-177 - PayPal Express Checkout Buttons in Produkt-Listings korrigiert
- PPI-185 - Fehlerbehandlung des PayPal-Tabs im Bestellmodul verbessert
- PPI-194 - Deregistrierung von Webhooks beim Löschen von Sales Channel korrigiert
- PPI-196 - PayPal Plus Checkout-Prozess verbessert
- PPI-197 - "Warenkorb übertragen" Funktion korrigiert

# 2.0.0
- PPI-182 - Webhook-Registrierung verbessert
- PT-11875 - Umstellung auf PayPal API v2 für folgende Features: PayPal, Express Checkout und Smart Payment Buttons

# 1.10.0
- PPI-159 - ACL-Privilegien zu den PayPal-Modulen hinzugefügt
- PPI-161 - Korrigiert Fehler bei Eingabe von Zugangsdaten im First-Run-Wizard

# 1.9.3
- PPI-67 - Aktivierung der Webhooks überarbeitet
- PPI-110 - Einschränkungen für Alternative Zahlungsarten, welche durch PayPal festgelegt sind, hinzugefügt
- PPI-114 - Kleine Verbesserungen beim Onboarding-Prozess
- PPI-145 - Kleine Verbesserungen der Einstellungsseite
- PPI-151 - Korrigiert Fehler bei Zahlungen mit bereits vorhandenen Bestellnummern
- PPI-158 - Korrigiert Fehler beim Update auf Versionen ab 1.7.0, wenn keine Konfiguration vorhanden ist

# 1.9.2
- PPI-149 - Korrigiert auftretende Fehler bei der Kommunikation mit iZettle

# 1.9.1
- PPI-141 - Performance der API zu PayPal verbessert

# 1.9.0
- PPI-1 - Korrigiert das mobile Layout der Bestellabschlussseite bei "Kauf auf Rechnung"
- PPI-68, PPI-118, PPI-136 - API-Objekt-Nutzung für Dritt-Erweiterungen verbessert
- PPI-69 - Der Express-Button wird nun ausgeblendet, wenn die PayPal-Zahlungsmethode deaktiviert ist
- PPI-97 - Korrigiert Fehler beim Express Checkout, wenn erforderliche Felder nicht von PayPal gesendet werden
- PPI-124 - Korrigiert Weitergabe von Fehlern während der Kommunikation mit PayPal 
- PPI-128 - Korrigiert Problem beim Express Checkout bei Änderungen auf der Bestellbestätigungsseite
- PPI-130 - Neues Event hinzugefügt, welches geworfen wird, wenn der Plus-iFrame geladen ist
- PT-11048 - iZettle-Integration (Point of Sales) hinzugefügt

# 1.8.4
- PPI-125 - Shopware 6.3.2.0 Kompatibilität

# 1.8.3
- PPI-70 - Bestellnummer wird jetzt für Zahlungen mit Express Checkout, PLUS und Smart Payment Buttons korrekt übermittelt

# 1.8.2
- PPI-46 - Behebt Fehler beim Erstatten ohne Betrag
- PPI-47, PPI-48 - Erweiterung der PayPal API Elemente

# 1.8.1
- PPI-32, PPI-35 - Erweiterbarkeit für Dritt-Plugins verbessert
- PPI-36 - Weitere PayPal API Elemente hinzugefügt

# 1.8.0
- PT-11912 - Die Storefront Übersetzungen werden jetzt automatisch registriert
- PT-11920 - Shopware 6.3 Kompatibilität

# 1.7.3
- PT-11946 - Ein Update funktioniert nun auch wieder bei deaktiviertem Plugin
- PT-11949 - Das Setzen von PayPal als Standardzahlungsart für alle Sales Channel ist wieder möglich

# 1.7.2
- PT-10491 - Intern genutzte Zusatzfeld-Entität für Transaktions-IDs entfernt
- PT-11627 - Bestelltransaktionen haben nun den Status "In Bearbeitung" wenn der Zahlungsprozess gestartet wurde
- PT-11680 - Verkaufskanäle mit unbekanntem Typ in den Einstellungen entfernt
- PT-11681 - Titelleiste in Bestelldetails in Administration korrigiert, wenn Zahlungsdetails direkt aufgerufen wurden
- PT-11860 - Sprache der Bestellbestätigungs-E-Mail bei PayPal Plus korrigiert
- PT-11888 - Kleine Performanceverbesserung beim Erstellen einer Zahlung
- PT-11903 - Durch den Nutzer abgebrochene PayPal Plus-Zahlungen werden nun korrekt als fehlgeschlagen markiert
- PT-11928 - Eingabelänge von Textfeldern in der Administration passend zur PayPal-API limitiert

# 1.7.1
- PT-11884 - Wenn PayPal nicht verfügbar ist, werden Plus und Smart Payment Buttons nicht mehr geladen

# 1.7.0
- PT-11669 - Kompatibilität mit dem Zahlungsprozess nach einer Bestellung hinzugefügt
- PT-11707 - Individuelle Formular-Parameter der Bestellseite werden nicht mehr ignoriert
- PT-11748 - Weiterleitungs-URL für PayPal Plus und Express Checkout korrigiert. Die Webhook-URL wurde geändert, sodass sie unabhängig von einer Storefront ist
- PT-11773 - Kaufen von Custom Products mit PayPal korrigiert
- PT-11813 - Fehlerbehandlung für Express Checkout Buttons
- PT-11858 - Verarbeitung von mehreren Transaktionen pro Bestellung verbessert
- PT-11869 - Handhabung von Zahlungen verbessert, die von Kunden abgebrochen wurden

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
- NEXT-8322 - Shopware 6.2 Kompatibilität
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
