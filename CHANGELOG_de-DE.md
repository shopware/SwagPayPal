# 9.6.4
- PPI-930 - Behebt ein Problem, bei dem bei einem ausgewähltem Verkaufskanal die vererbte Konfiguration nicht korrekt funktionierte
- PPI-1013 - Verbessert die Zuverlässigkeit der Produktsynchronisation mit Zettle
- PPI-1016 - Behebt ein Problem, bei dem Sendungsverfolgungsdaten aufgrund zu langer Produktnamen nicht an PayPal übermittelt wurden
- PPI-1014 - Behebt ein Problem, bei dem Bilder mit Sonderzeichen nicht zu Zettle synchronisiert werden konnten
- PPI-1024 - Behebt ein Problem, bei dem Kartenzahlungen aufgrund von inaktivem 3DS einer Karte fehlschlagen konnten

# 9.6.3
- PPI-1015 - Behebt ein Problem, bei dem PayPal-Einstellungen in der Administration nicht gespeichert werden konnten

# 9.6.2
- PPI-971 - Behebt ein Problem, bei dem Umlaute im Markennamen dazu führten, dass Apple Pay-Zahlungen abgebrochen wurden
- PPI-999 - Behebt ein Problem, bei dem Überschreibungen von Administratorkomponenten das Speichern von Einstellungen blockieren konnten
- PPI-1002 - Behebt ein Problem, bei dem der Apple Pay Domain Hinweis nicht korrekt angezeigt wurde
- PPI-1008 - Behebt ein Problem, bei dem die Zahlungsstatusabfrage mit nicht existierenden Transaktionen weiterhin wiederholt wurde
- PPI-1009 - Behebt ein Problem, bei dem PayPal Plus im Checkout nicht angezeigt werden konnte
- PPI-1010 - Behebt ein Problem, bei dem das kompilierte CSS in Kombination mit anderen Erweiterungen Syntaxfehler enthalten konnte

# 9.6.1
- PPI-942 - Behebt ein Problem, bei dem Cookies trotz deaktiviertem Google Pay im Cookie-Banner angezeigt wurden
- PPI-957 - Fügt Schnell-Links zum Kontext-Menü der Erweiterung und der PayPal Einstellungen hinzu
- PPI-991 - Behebt ein Problem, bei dem Trustly fälschlicherweise zur Auswahl stand
- PPI-995 - Behebt ein Problem, bei dem der Zettle-Einrichtungsassistent nicht korrekt beendet werden konnte
- PPI-996 - Behebt ein Problem, bei dem PayPal-Buttons mit bestimmten Themes während des Checkouts verschwinden konnten

# 9.6.0
- PPI-922 - Verbessert die Sichtbarkeit von Apple Pay für nicht unterstützte Browser und Geräte
- PPI-978 - Trustly für weitere kompatible Länder & Währungen freigeschaltet
- PPI-979 - Behebt ein Problem, bei dem PUI für Kunden ohne Geburtsdatum im Zahlartwechsel nach Bestellabschluss nicht funktioniert hat
- PPI-981 - Behebt ein Problem, bei dem der Zahlungsstatus manchmal nicht korrekt auf Fehlgeschlagen gesetzt wurde, wenn die Kommunikation mit PayPal fehlschlug
- PPI-987 - Behebt ein Problem, bei dem Zettle Verkaufskanäle nicht erstellt oder deren Zugangsdaten geändert werden konnten
- PPI-988 - Behebt ein Problem, bei dem SEPA, Karten und Venmo im Checkout nicht erschienen

# 9.5.0
- PPI-958 - Behebt ein Problem, bei dem die API-Zugangsdaten-Prüfung bei ungültiger Merchant-Payer-ID erfolgreich war
- PPI-960 - Express-Checkout-Buttons sind nun auch bei aktiviertem Double-Opt-In verfügbar, eine Warnung wird in den Einstellungen angezeigt
- PPI-961 - Behebt ein Problem, bei dem die Einstellung des 'Später Bezahlen'-Banner auf Loginseite nicht gespeichert wurde
- PPI-965 - Behebt ein Problem, bei dem ein Fehler im Checkout zu einem dauerhaften Neuladen der Seite führen konnte, wenn SwagCommercial installiert ist

# 9.4.0
- PPI-807 - Verbessert die Ladezeit der Scripte in der Storefront
- PPI-924 - Fügt genauere Fehlermeldungen für Fehler während des Express Checkouts hinzu
- PPI-929 - Fügt Onboarding-Informationen für die Zahlungsarten Apple und Google Pay hinzu
- PPI-950 - Ändert das Versanddienstleister-Feld in der Administration zu einem Dropdown
- PPI-951 - Behebt ein Problem, bei dem ohne Smart Payment Buttons nur generische Fehlermeldungen während des Bestellprozesses angezeigt wurden
- PPI-953 - Behebt ein Problem, bei dem Sendungsverfolgungsdaten bei invaliden Versanddienstleister nicht an PayPal übermittelt wurden
- PPI-956 - Verbessert das Logging und die Fehlerbehandlung für Händlereinstellungen

# 9.3.1
- PPI-944 - Behebt ein Problem, bei dem die Abfrage des Zahlungsstatus bei invaliden Transaktionen weiter versucht wurde
- PPI-945 - Behebt ein Problem, bei dem ausgeschlossene Produkte nicht korrekt in der Administration angezeigt wurden
- PPI-947 - Behebt ein Problem, bei dem Smart Payment Buttons möglicherweise nicht konfigurierbar waren
- PPI-948 - Entfernt die nicht mehr unterstützte Giropay-Zahlungsart

# 9.3.0
- PPI-934 - Übermittlung von Sendungsverfolgungsdaten auf neue PayPal-API-Endpunkte umgezogen

# 9.2.0
- PPI-666 - Zusätzliche Informationen auf Rechnung für Rechnungskäufe hinzugefügt
- PPI-924 - Explizitere Fehlermeldungen für Fehler beim Checkout hinzugefügt (z.B. Postleitzahl fehlt)
- PPI-932 - Entfernt die nicht mehr unterstützte Sofort-Zahlungsart
- PPI-936 - Behebt ein Problem, bei dem Zettle evtl. inkorrekte Steuersätze erhält

# 9.1.1
- PPI-933 - Behebt ein Problem, bei dem Apple Pay-Domains möglicherweise nicht korrekt registriert werden können

# 9.1.0
- PPI-850 - Automatische Abfrage des Zahlungsstatus für autorisierte und in Bearbeitung befindliche Transaktionen erweitert
- PPI-862 - Vaulting (Kunden speichern) für Zahlungsart Venmo hinzugefügt
- PPI-863 - Zahlungsart Apple Pay hinzugefügt
- PPI-863 - Zahlungsart Google Pay hinzugefügt
- PPI-918 - Behebt ein Problem, bei dem der rückerstattbare Betrag in der Administration nicht korrekt angezeigt wurde

# 9.0.3
- PPI-906 - Änderung der Express Buttons, sodass diese freien Platz besser nutzen und zum restlichen Layout passen
- PPI-914 - Behebt ein Problem, bei dem Bestellungen mit ausschließlich digitalen Produkten fehlschlugen und inkorrekt kategorisiert wurden
- PPI-916 - Behebt ein Problem, bei dem Bestellungen mit der Landingpage-Einstellung "Gast-Checkout" fehlschlagen können
- PPI-917 - Rechnungskauf-Disclaimer über den Bestätigungsbutton verschoben

# 9.0.2
- PPI-908 - Behebt ein Problem, welches eine falsche Landingpage Einstellung an PayPal übermittelt.

# 9.0.1
- PPI-896 - Änderung der Checkout Smart Payment Buttons von 'Direkt zu PayPal' zu 'Mit PayPal zahlen'.
- PPI-900 - Behebt ein Problem, bei dem Händler Integrationen für Zahlungsmethoden nicht korrekt geladen werden
- PPI-904 - Behebt ein Timestamp-Problem, welches den Shop mit bestimmten Cache-Konfigurationen unbenutzbar macht

# 9.0.0
- PPI-830 - Kompatibilität mit Shopware 6.6 und Symfony 7 hinzugefügt
- PPI-857 - Technische Namen für PayPal-Zahlungsarten hinzugefügt 

# 8.0.0
- PPI-763 - "Später bezahlen" Banner wird unterhalb des Preises auf der Produktdetailseite angezeigt
- PPI-779 - Neues Beta-Feature Vaulting (Kunden speichern) für PayPal und Kredit-/Debitkartenzahlungen hinzugefügt
- PPI-800 - API-Struktur & PayPal-Order-Erstellung verbessert
- PPI-827 - Automatische Abfrage des Zahlungsstatus für unbestätigte Transaktionen hinzugefügt
- PPI-831 - Verbesserte Verwaltung des Zahlungsstatus-Webhooks
- PPI-860 - Kredit-/Debitkartenzahlungen auf neue Card Fields statt Hosted Fields migriert

# 7.4.0
- PPI-853 - Fügt eine Option zum Einstellen des Käuferlandes für "Später bezahlen" Banner hinzu

# 7.3.2
- PPI-845 - Behebt ein Problem, bei dem eine Bestellung aufgrund zu langer Produktnamen fehlschlug
- PPI-849 - Fügt Kampagnen- und Affiliatecodes zum Express Checkout
- PPI-855 - Abschaltungshinweis für Sofort-Zahlungsart hinzugefügt

# 7.3.1
- PPI-844 - Behebt ein Problem, bei dem der Ratenzahlungs-Banner nicht auf CMS-Produktdetailseiten deaktivierbar war

# 7.3.0
- PPI-765 - "Später bezahlen" Banner lassen sich nun feiner ein- und ausschalten
- PPI-828 - Behebt ein Problem, bei dem aufgrund des Caches eine inkorrekte Liste an Zahlungsarten im Checkout angezeigt wurde

# 7.2.4
- PPI-818 - Warnung für potentielle Nicht-Verfügbarkeit der MyBank-Zahlungsmethode hinzugefügt
- PPI-820 - Behebt ein Problem, bei dem ein Übergang in den Zahlungsstatus 'Bezahlt' nicht über Webhooks möglich war
- PPI-826 - Die Express Buttons werden nicht mehr angezeigt, wenn die Double-Opt-In-Funktion des Gastkunden aktiviert ist.

# 7.2.3
- PPI-808 - Behebt ein Problem, bei dem manche Währungen (HUF, JPY, TWD) nicht korrekt an PayPal übermittelt wurden
- PPI-809 - Behebt ein Problem, bei dem die Buttons nicht die richtige Farbe hatten
- PPI-810 - Intuitiveres Verhalten der Einstellungen in der Administration
- PPI-811 - Behebt ein Problem, bei dem Kreditkarten mit nicht verfügbarem 3D Secure nicht akzeptiert wurden
- PPI-812 - Behebt ein Problem, bei dem der Zettle-Sync sich nicht zurücksetzbar ist

# 7.2.2
- PPI-802 - Verbesserte Formulierung und Standardwerte in der Administration

# 7.2.1
- PPI-797 - Behebt ein Problem, bei dem der Zettle-Produktsync nicht funktionierte, wenn der Bildersync fehlschlug

# 7.2.0
- PPI-769 - "Später bezahlen"-Button zum Express Checkout Shortcut hinzugefügt
- PPI-773 - Die Einstellungsseite ist nun in Tabs strukturiert
- PPI-788 - Protokollierung auf Symfony-Standard umgestellt, um größere Umgebungen besser zu unterstützen

# 7.1.0
- PPI-679 - Zahlungsdetails für Rechnungskauf werden jetzt in den Bestelldetails in der Administration angezeigt
- PPI-762 - Einige Zahlungsarten sind nun auch für Nicht-PPCP-Märkte verfügbar
- PPI-767 - Zusätzliche Plugin-Informationen zu Transaktionsdetails hinzugefügt

# 7.0.1
- PPI-757 - Behebt ein Problem, bei dem Zahlungen von alternative Zahlungsarten doppelt angelegt werden konnten

# 7.0.0
- PPI-755 - Shopware 6.5-Kompatibilität wiederhergestellt

# 6.0.2
- PPI-753 - Behebt ein Problem, bei dem Template-Erweiterungen im Meta-Block nicht mehr funktionierten
- PPI-754 - Behebt ein Problem, dass die kompilierten Assets nicht mit 6.4 kompatibel waren (Kompatiblität mit Shopware 6.5 vorübergehend entfernt)

# 6.0.1
- PPI-751 - Behebt ein Problem, bei dem das Plugin mit anderen Plugins wie B2B-Suite und Customized Products nicht kompatibel war

# 6.0.0
- PPI-430 - Synchronisationsablauf von Zettle verbessert
- PPI-659 - Storefront-Routen hinzufügt, um den in Shopware 6.5 fehlenden Store-API-Client zu ersetzen
- PPI-685, PPI-701, PPI-725 - Automatisches Verstecken der Smart Payment Buttons Konfiguration in der Administration entfernt
- PPI-731 - Kompatibilität mit Shopware 6.5

# 5.4.6
- PPI-748 - Zahlart Trustly deaktiviert, da PayPal die API deaktiviert hat
- PPI-749 - Behebt ein Problem, bei dem der Ersatz-Button für Kreditkartenzahlungen nicht korrekt funktionierte

# 5.4.5
- PPI-734 - Behebt ein Problem, bei dem Zahlungsarten beim Zahlartwechsel nach Bestellabschluss nicht angezeigt wurden
- PPI-720, PPI-741, PPI-743 - Behebt ein Problem, bei dem die Steuern für Nettokudnen falsch berechnet wurde

# 5.4.4
- PPI-734 - Behebt ein Problem, bei dem Zahlungsarten beim Zahlartwechsel nach Bestellabschluss nicht angezeigt wurden
- PPI-735 - Behebt ein Problem, bei dem Zahlungsdetails nicht angezeigt wurden, wenn PayPal nicht die erstgewählte Zahlungsart war
- PPI-737 - Behebt ein Problem, bei dem die Bestell-/Zahlungsdetails nicht korrekt an PayPal übertragen wurden

# 5.4.3
- PPI-654 - Behebt ein Problem, bei dem Zettle Synchronisationsfehler nicht angezeigt wurden
- PPI-661 - Kleine Performance-Verbesserungen
- PPI-718 - Behebt ein Problem, bei dem das Onboarding für Sales-Channel-spezifischen Konfigurationen nicht abgeschlossen werden konnte
- PPI-733 - Behebt ein Problem, bei dem die Sandbox-Konfiguration nicht immer bei Sales-Channel-spezifischen Konfigurationen verwendet wurde

# 5.4.2
- PPI-723 - Behebt ein Problem, bei dem aufgrund von unangekündigten API-Änderungen bei PayPal einige APM-Zahlungsmethoden manchmal nicht mehr funktionierten
- PPI-724 - Behebt ein Problem, bei dem eine Zahlung fehlschlagen konnte, falls PayPal nicht die vollen Zahlungsdetails zurückgab

# 5.4.1
- PPI-716 - Behebt einen Fehler beim Update, falls Verfügbarkeits-Regel noch in Benutzung sind

# 5.4.0                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      
- PPI-707 - Behebt ein Problem, bei dem während des Bestellvorgangs mit "Später bezahlen", "SEPA" and "Venmo" ein Fehler auftrat
- PPI-712 - Handhabung von Zahlungsmethodenverfügbarkeit verbessert, existierende Verfügbarkeitsregeln entfernt
- PPI-713 - Verwendung von 3D Secure für Kreditkartenzahlungen verbessert

# 5.3.2
- PPI-709 - Behebt ein Problem, bei dem PayPal nicht installiert werden konnte, wenn die Standardsprache nicht Deutsch oder Englisch war

# 5.3.1
- PPI-672 - Behebt ein Problem, bei dem Einzügen der Zahlstatus Bezahlt nicht immer gesetzt werden konnte
- PPI-681 - Behebt ein Problem, bei dem Später Bezahlen nicht für britische und australische Kunden verfügbar ist
- PPI-681 - Behebt ein Problem, bei dem Oxxo nicht für mexikanische Kunden verfügbar ist
- PPI-682 - Behebt ein Problem mit fehlenden deutschen Übersetzungen in der Administration
- PPI-684 - Rechtschreibfehler in der Administration korrigiert
- PPI-688 - Behebt ein Problem, bei dem das Eingabefeld für den Versandanbieter nicht immer angezeigt wurde
- PPI-694 - Behebt ein Problem, bei dem der Versandanbieter auch für Nicht-PayPal-Bestellungen angezeigt wurde
- PPI-695 - Behebt ein Problem, bei dem ausgeschlossene Produkte pro Verkaufskannal ignoriert werden
- PPI-700 - API-URL von PayPal von `api.paypal.com` auf `api-m.paypal.com` für Performanceverbesserungen geändert
- PPI-702 - Behebt ein Problem, bei dem bei Nicht-PayPal-Zahlungen die Zahlungsdetails nicht immer angezeigt wurden

# 5.3.0
- PPI-627 - Neue Zahlungsarten "Später bezahlen" und "Venmo" hinzugefügt
- PPI-673 - Automatische Übermittlung von Sendungsverfolgungsdaten an PayPal hinzugefügt
- PPI-677 - Verfügbarkeit von Zahlungsmethoden in der Administration verbessert
- PPI-678 - Behebt ein Problem, bei dem das Zettle-Medien-URL-Feld in der Administration einen Fehler anzeigte

# 5.2.0
- PPI-625 - Kompatibilität für neue Zahlungsmethodenübersicht von Shopware 6.4.14.0 hinzugefügt
- PPI-663 - Behebt ein Problem, bei dem Steuern nicht korrekt für Netto-Bestellungen berechnet wurden

# 5.1.2
- PPI-664 - Verwendung von 3D Secure für Kreditkartenzahlungen verbessert
- PPI-670 - Darstellung des Onboarding-Status in der Administration verbessert

# 5.1.1
- PPI-657 - Template `buy-widget-form` wurde bereinigt

# 5.1.0
- PPI-611 - Möglichkeit hinzugefügt, Produkte & dynamischen Produktgruppen von PayPal- und Express Checkout-Käufen auszuschließen
- PPI-617 - Behebt ein Problem, bei dem die Freigaben für Zahlungsarten nicht korrekt bei Sales-Channel-spezifischen Einstellungen dargestellt wurden
- PPI-620 - Behebt ein Problem, bei dem fälschlicherweise eine Webhookfehlermeldung bei fehlenden Zugangsdaten angezeigt wurde
- PPI-634 - Behebt ein Problem mit der Versandkostensteuerberechnung für Netto-Kundengruppen
- PPI-635 - Behebt ein Problem, bei dem leere Zahlungsdetails zu Rechnungskäufen in Rechnungen angezeigt wurden
- PPI-639 - Die Sales Channel Auswahl in den PayPal-Einstellungen kann nun mehr als 25 Sales Channel anzeigen
- PPI-648 - Verbessert den Zahlvorgang mit Smart Payment Buttons, wenn das JS nicht schnell genug lädt
- PPI-649 - Behebt ein Problem, bei dem teilrückerstattete PayPal Plus-Zahlungen in Shopware wg. Webhooks als zurückerstattet angezeigt wurden
- PPI-650 - Kompatibilität auf Shopware 6.4.3.0 erweitert

# 5.0.4
- PPI-642 - Behebt ein Problem, bei dem der Zahlungsstatus von Kreditkartenzahlungen manchmal nicht korrekt gesetzt wurde

# 5.0.3
- PPI-624 - Verbesserte Fehlerbehandlung im Zahlungsprozess nach einer Bestellung
- PPI-628 - Verbessert die Zahlungsmethodenauswahl, wenn PayPal einen Käufer als nicht berechtigt erachtet
- PPI-629 - Behebt ein Problem, bei dem die Zahlungsdetails zu API v1 Zahlungen nicht angezeigt wurden

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
