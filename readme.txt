=== Plugin Name ===
Contributors: ndijkstra
Tags: mollie,doneren,donate,ideal,mistercash,bancontact,bitcoin,creditcard,paypal,sofort,belfius,overboeking,recurring,incasso,debit,herhaalbetalingen,sepa,subscriptions
Requires at least: 3.0.1
Tested up to: 4.5.3
Stable tag: 2.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Deze plugin is zowel geschikt voor eenmalige donaties als voor periodieke betalingen. Alle betaalmethodes van Mollie zijn in de plugin geïntegreerd.

== Description ==



== Installation ==

1. Zoek "Doneren met Mollie" in de plugins van Wordpress en klik op installeren
1. Activeer de plugin in Wordpress
1. Maak een account aan op Mollie.com en volg de stappen om een Live API-key te krijgen
1. Ga naar de instellingen pagina "Doneren met Mollie" en vul de Live API-key in
1. Plaats de shortcode [doneren_met_mollie] op een pagina waar het donatieformulier moet komen

== Changelog ==

= 2.1.3 =
* Problemen met database bij het updaten verholpen

= 2.1.2 =
* Problemen met velden opslaan verholpen wanneer recurring was ingeschakeld

= 2.1.1 =
* Problemen met webhook opgelost
* Keuzemenu interval niet meer zichtbaar indien recurring niet ingeschakeld
* Probleem met vertaling keuzemenu betaalmethodes opgelost
* Berichtveld heeft nu ook volledige breedte
* Indien herhaalbetaling, zijn nu alleen de beschikbare verificatiemethodes zichtbaar

= 2.1.0 =
* Herhaalbetalingen (Recurring Payments) nu ook beschikbaar!
* Mogelijk om minimaal te doneren bedrag in te stellen

= 2.0.1 =
* Plugin nu ook vertaald in het Duits en Frans

= 2.0.0 =
* Instellingen overzichtelijker gemaakt
* Vrije invoer en keuzelijst bedrag tegelijk mogelijk
* Variabelen meesturen in de omschrijving
* Zelf de weergave kiezen van de betaalmethodes
* Mogelijk om projecten toe te voegen
* Meer velden toegevoegd
* Velden actief en/of verplicht maken
* Meer classes toevoegen mogelijk
* Vertaald in Nederlands en Engels
* Code verbeterd
* Bugs opgelost

= 1.6.1 =
* Probleem waardoor bij Mollie fouten logboek wordt aangemaakt opgelost

= 1.6 =
* Probleem bij invoeren bedrag met komma verholpen
* Mogelijk om class in te stellen bij bedankt/mislukt melding
* Mogelijk om eigen pagina in te stellen na slagen/mislukken donatie

= 1.5.1 =
* Buxfixes

= 1.5 =
* Buxfix return/webhook url bij Wordpress in map

= 1.4 =
* Beveiliging verbeterd

= 1.3 =
* Mogelijk om donaties terug te storten
* Mogelijk om donatielijst te legen (leegt enkel de tabel, donaties worden niet teruggestort)

= 1.2 =
* Bufix positie plugin op pagina

= 1.1 =
* Standaard waarde bedrag zelf in te stellen

= 1.0 =
* Donaties te bekijken
* Webhook ingesteld, zodat status betaling ook nog wordt verwerkt als donateur niet terugkeert naar de website.

= 0.4 =
* Readme.txt aangepast
* Bugfix

= 0.3 =
* Readme.txt aangepast

= 0.2 =
* Bugfixes

== Upgrade Notice ==

= 2.0.0 =
Herhaalbetalingen (recurring payments) zijn nu beschikbaar!

== Screenshots ==

1. Donaties zichtbaar in admin
2. Meer informatie over de donatie en donateur
3. Algemene instellingen
4. Formulier instellingen
5. Classes instellen
6. Mollie instellingen
7. Abonnementen (doorlopende donaties)
8. Recurring instellingen