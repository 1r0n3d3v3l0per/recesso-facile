=== Recesso Facile ===
Contributors: 1r0n3d3v3l0per
Tags: woocommerce, recesso, diritto di recesso, consumer rights, ecommerce, italy
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Soluzione completa per la gestione del diritto di recesso conforme all'Art. 54-bis del Codice del Consumo italiano.

== Description ==

**Recesso Facile** è il plugin WordPress definitivo per gestire il diritto di recesso dei consumatori nel tuo e-commerce WooCommerce, in piena conformità con la normativa italiana (Art. 54-bis del Codice del Consumo).

= Caratteristiche Principali =

* **Conformità Legale Art. 54-bis** - "Easy In, Easy Out": form semplificato a 3 step per la richiesta di recesso
* **Gestione Completa** - Dashboard amministrativa per gestire tutte le richieste
* **Eccezioni Art. 59** - Configura prodotti e categorie esclusi dal diritto di recesso
* **Doppia Conferma** - Sistema di doppia conferma per garantire la volontarietà della richiesta
* **Ricevuta Legale** - Generazione automatica di ricevuta PDF con hash SHA256 per la tracciabilità
* **Email Automatiche** - Notifiche via email a clienti e amministratori
* **Guest Support** - Permetti anche agli ospiti (non registrati) di richiedere il recesso
* **HPOS Compatible** - Compatibile con WooCommerce High-Performance Order Storage
* **Activity Log** - Audit trail completo per tracciare ogni operazione
* **Sticky Button** - Bottone fisso sulla pagina per accesso rapido al form
* **Responsive Design** - Form e admin completamente responsive

= Funzionalità per l'Amministratore =

* Dashboard con statistiche in tempo reale
* Gestione richieste di recesso con cambio status
* Configurazione eccezioni per prodotti/categorie (Art. 59)
* Registro attività per audit completo
* Impostazioni complete con 5 sezioni
* Email personalizzabili
* Generazione PDF ricevute

= Funzionalità per i Clienti =

* Form a 3 step semplice e intuitivo
* Verifica ordine con email
* Selezione modalità rimborso (originale, bonifico, credito)
* Ricevuta PDF con hash di sicurezza
* Email di conferma automatica

= Conformità Normativa =

Il plugin è progettato per supportare gli obblighi introdotti dalla nuova "funzione di recesso" online:
* Art. 54-bis del Codice del Consumo (D.Lgs. 206/2005), introdotto dal D.Lgs. 209/2025
* Recepimento della Direttiva (UE) 2023/2673 (che modifica la Direttiva 2011/83/UE)
* Obbligo applicabile dal 19 giugno 2026 per i contratti a distanza conclusi online
* Art. 52 e seguenti del D.Lgs. 206/2005 (disciplina del diritto di recesso)
* Art. 59 - Eccezioni al diritto di recesso
* GDPR per la gestione dei dati personali

Nota: il plugin è uno strumento tecnico a supporto della conformità. La responsabilità della conformità legale resta in capo al titolare dell'e-commerce, che dovrebbe verificare la propria configurazione con un consulente legale.

= REST API =

Include REST API completa per integrazioni esterne:
* GET /wp-json/recesso-facile/v1/withdrawals
* POST /wp-json/recesso-facile/v1/withdrawals
* PUT /wp-json/recesso-facile/v1/withdrawals/{id}/status
* GET /wp-json/recesso-facile/v1/statistics

== Installation ==

1. Carica la cartella `recesso-facile` nella directory `/wp-content/plugins/`
2. Attiva il plugin dal menu 'Plugins' di WordPress
3. Vai su WooCommerce > Recesso Facile per configurare le impostazioni
4. Crea una pagina e inserisci lo shortcode `[recesso_facile_form]`
5. Configura le impostazioni secondo le tue necessità

= Requisiti =

* WordPress 6.0 o superiore
* WooCommerce 7.0 o superiore
* PHP 7.4 o superiore
* MySQL 5.6 o superiore

= Configurazione Rapida =

1. **Generale**: Imposta il periodo di recesso (default 14 giorni)
2. **Email**: Configura gli indirizzi email per le notifiche
3. **Modulo**: Abilita le opzioni del form (motivo obbligatorio, note, ecc.)
4. **PDF**: Inserisci i dati aziendali per le ricevute
5. **Eccezioni**: Aggiungi prodotti esclusi dal recesso (se applicabile)

== Frequently Asked Questions ==

= Il plugin è conforme alla normativa italiana? =

Sì, il plugin è stato sviluppato seguendo le linee guida dell'Art. 54-bis del Codice del Consumo italiano, che prevede un sistema "Easy In, Easy Out" per il diritto di recesso.

= Posso personalizzare il periodo di recesso? =

Sì, puoi impostare il periodo desiderato nelle impostazioni. Il periodo legale minimo è 14 giorni.

= Come funziona il sistema di eccezioni? =

Puoi configurare prodotti o categorie esclusi dal recesso secondo l'Art. 59 del Codice del Consumo (es. prodotti personalizzati, beni deteriorabili, software sigillati aperti, ecc.).

= Il plugin supporta gli ordini guest? =

Sì, anche gli utenti non registrati possono richiedere il recesso verificando l'ordine con email.

= Le ricevute PDF sono valide legalmente? =

Sì, le ricevute includono un hash SHA256 che garantisce l'autenticità e la tracciabilità del documento.

= Posso personalizzare le email? =

Le email utilizzano template che possono essere sovrascritti copiando i file nella tua cartella theme.

= Il plugin è compatibile con HPOS? =

Sì, il plugin dichiara piena compatibilità con WooCommerce High-Performance Order Storage.

== Screenshots ==

1. Dashboard amministrativa con statistiche
2. Form di richiesta recesso (Step 1 - Verifica Ordine)
3. Form di richiesta recesso (Step 2 - Motivazione)
4. Form di richiesta recesso (Step 3 - Conferma)
5. Gestione richieste di recesso
6. Configurazione eccezioni prodotti (Art. 59)
7. Registro attività (Audit Trail)
8. Pagina impostazioni
9. Ricevuta PDF generata

== Changelog ==

= 1.1.0 - 2026-06-27 =
* Nuovo: diciture conformi di default ("Recedere dal contratto qui" e
  "Conferma recesso"), personalizzabili dalle impostazioni.
* Nuovo: avviso di ricevimento via email su supporto durevole, con data e
  ora di ricezione e ricevuta PDF allegata.
* Nuovo: template email reali sovrascrivibili dal tema.
* Nuovo: export CSV delle richieste dal pannello, nel rispetto del filtro
  per stato.
* Migliorie di escaping e coerenza nelle email di fallback.

= 1.0.4 - 2026-06-27 =
* Correzione: le impostazioni a schede (tab) non azzerano più le caselle
  degli altri tab quando se ne salva uno. Il salvataggio è ora circoscritto
  alla scheda effettivamente inviata.
* Correzione: l'endpoint REST di creazione richieste non inviava il campo
  nome cliente (obbligatorio), facendo fallire sempre la creazione via API.
* Correzione: il cleanup automatico giornaliero (opzione "Elimina vecchie
  richieste") non veniva mai eseguito perché privo di gestore. Ora funziona.
* Correzione: aggiornamento stato richiesta più robusto nella scrittura della
  data di completamento e delle note.
* Correzione: gestione di ordini senza data (no errori fatali nel form e PDF).
* Correzione: metabox ordine registrato una sola volta e compatibile HPOS in
  modo sicuro.
* Correzione: l'eliminazione di una richiesta inesistente non segnala più
  "eliminata con successo".

= 1.0.3 - 2026-06-25 =
* Correzione: allineata la versione del database all'attivazione, evitando
  una migrazione ridondante al primo caricamento su installazioni nuove
* Testato end-to-end su WordPress + WooCommerce (creazione ordine, richiesta
  di recesso, generazione ricevuta PDF, verifica protezione accessi)

= 1.0.2 - 2026-06-25 =
* Sicurezza: le ricevute PDF non sono più accessibili pubblicamente via URL
  (nome file basato su hash non indovinabile, cartella protetta con .htaccess,
  download solo previa verifica di identità o token ricevuta)
* Sicurezza: rate limiting sulla creazione richieste (AJAX e REST) contro lo spam
* Rimossi riferimenti e date non applicabili dalla documentazione

= 1.0.1 - 2026-06-25 =
* Campo nome cliente nel form di recesso (conformità Art. 54-bis)
* Integrazione WooCommerce (pulsante pagina ordine, email, metabox admin)
* Sistema di migrazione automatica del database
* Riferimenti normativi aggiornati (D.Lgs. 209/2025, Direttiva UE 2023/2673)
* Correzioni di sicurezza e miglioramenti vari

= 1.0.0 - 2025-06-25 =
* Release iniziale
* Form a 3 step conforme Art. 54-bis
* Dashboard amministrativa completa
* Sistema di eccezioni (Art. 59)
* Generazione PDF con hash SHA256
* Email automatiche
* REST API
* HPOS compatible
* Activity logging
* Guest support
* Responsive design

== Upgrade Notice ==

= 1.1.0 =
Diciture conformi, avviso di ricevimento su supporto durevole, template email sovrascrivibili ed export CSV.

= 1.0.4 =
Diverse correzioni importanti, tra cui il salvataggio delle impostazioni a schede. Aggiornamento consigliato a tutti.

= 1.0.3 =
Correzione minore alla gestione della versione del database. Aggiornamento consigliato.

= 1.0.2 =
Aggiornamento di sicurezza importante: protegge le ricevute PDF da accessi non autorizzati. Aggiornamento consigliato a tutti.

= 1.0.1 =
Aggiunto campo nome cliente, integrazione WooCommerce e riferimenti normativi aggiornati (D.Lgs. 209/2025).

= 1.0.0 =
Release iniziale del plugin Recesso Facile.

== Support ==

Per supporto, documentazione e aggiornamenti:
* Website: https://irn3.com
* Email: support@irn3.com

== Privacy Policy ==

Recesso Facile raccoglie e memorizza le seguenti informazioni:
* Dati ordine (numero ordine, email cliente)
* Motivazione del recesso
* Indirizzo IP della richiesta
* User agent del browser

Questi dati sono necessari per:
* Gestire le richieste di recesso
* Conformità legale e audit trail
* Comunicazioni con il cliente

I dati sono conservati secondo le impostazioni configurate dall'amministratore e possono essere eliminati completamente durante la disinstallazione del plugin.

== Credits ==

Sviluppato da Andrea Ferro - https://irn3.com
Copyright © 2025-2026 Andrea Ferro
Distribuito sotto licenza GPL v2 o successiva.
