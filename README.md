# Recesso Facile

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588a.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](LICENSE)

Plugin WordPress/WooCommerce per la gestione del diritto di recesso dei consumatori, a supporto della "funzione di recesso" online introdotta dall'art. 54-bis del Codice del Consumo.

## Contesto normativo

Dal **19 giugno 2026** i professionisti che concludono contratti a distanza tramite interfaccia online devono mettere a disposizione del consumatore una funzione di recesso dedicata, facilmente accessibile e disponibile per tutto il periodo di esercizio del diritto.

L'obbligo deriva da:

- **Art. 54-bis del Codice del Consumo** (D.Lgs. 206/2005), introdotto dal **D.Lgs. 209/2025**;
- **Direttiva (UE) 2023/2673**, che modifica la Direttiva 2011/83/UE.

La norma richiede, tra l'altro: una funzione identificata dalla dicitura "recedere dal contratto qui" (o formulazione equivalente inequivocabile), un pulsante di conferma "conferma recesso", la raccolta dei dati identificativi del contratto e l'invio di un avviso di ricevimento su supporto durevole con data e ora.

> Questo plugin è uno strumento tecnico a supporto della conformità. Non costituisce consulenza legale; la responsabilità della conformità resta in capo al titolare dell'e-commerce, che dovrebbe verificare la propria configurazione con un consulente.

## Funzionalità

- Modulo di recesso guidato in tre passaggi (verifica ordine, motivazione, conferma).
- Diciture conformi di default ("Recedere dal contratto qui", "Conferma recesso"), personalizzabili dalle impostazioni.
- Verifica dell'ordine tramite numero ordine ed email, con supporto agli ordini come ospite.
- Avviso di ricevimento via email su supporto durevole, con data e ora di ricezione e ricevuta PDF allegata.
- Ricevuta PDF con hash SHA-256, archiviata in una directory protetta e servita solo previa verifica di accesso.
- Gestione delle eccezioni per prodotto o categoria (art. 59).
- Pannello di amministrazione con elenco richieste, filtri per stato, dettaglio, registro attività ed export CSV.
- Email personalizzabili tramite template sovrascrivibili dal tema.
- REST API per la creazione e la gestione delle richieste.
- Compatibilità con WooCommerce High-Performance Order Storage (HPOS).

## Requisiti

- WordPress 6.0 o superiore
- WooCommerce 7.0 o superiore
- PHP 7.4 o superiore
- MySQL 5.6 o superiore

## Installazione

1. Scaricare l'archivio dalla pagina [Releases](https://github.com/1r0n3d3v3l0per/recesso-facile/releases).
2. Caricarlo da `Plugin → Aggiungi nuovo → Carica plugin`, oppure estrarre la cartella `recesso-facile` in `wp-content/plugins/`.
3. Attivare il plugin dal pannello di amministrazione.
4. Configurare le opzioni in `Recesso Facile → Impostazioni`.

All'attivazione viene creata automaticamente una pagina contenente il modulo di recesso. In alternativa, inserire lo shortcode in qualsiasi pagina.

## Shortcode

| Shortcode | Descrizione |
|-----------|-------------|
| `[recesso_facile_form]` | Modulo di recesso completo a tre passaggi. |
| `[recesso_facile_button]` | Pulsante che rimanda alla pagina del modulo. |
| `[recesso_facile_status]` | Elenco delle richieste dell'utente collegato. |

## Configurazione

Le impostazioni sono suddivise in cinque schede:

- **Generale** – periodo di recesso, diciture del modulo e dei pulsanti, pulsante flottante, pagina del modulo.
- **Email** – attivazione delle notifiche a cliente e amministratore, indirizzo e nome del mittente.
- **Modulo** – campi e opzioni del form, metodi di rimborso, doppia conferma.
- **PDF** – dati aziendali riportati nella ricevuta.
- **Avanzate** – registro attività ed eliminazione automatica delle richieste obsolete.

## REST API

Namespace: `recesso-facile/v1`. Gli endpoint di lettura e gestione richiedono la capability `manage_woocommerce`.

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| `GET` | `/withdrawals` | Elenco delle richieste. |
| `GET` | `/withdrawals/{id}` | Dettaglio di una richiesta. |
| `POST` | `/withdrawals` | Creazione di una richiesta. |
| `PUT` | `/withdrawals/{id}/status` | Aggiornamento dello stato. |
| `GET` | `/statistics` | Statistiche aggregate. |

## Hook per sviluppatori

```php
// Personalizza il controllo di idoneità al recesso
add_filter('recesso_facile_check_eligibility', function ($eligible, $order_id, $email) {
    return $eligible;
}, 10, 3);

// Azione dopo la creazione di una richiesta
add_action('recesso_facile_withdrawal_created', function ($withdrawal_id, $data) {
    // ...
}, 10, 2);
```

I template delle email possono essere sovrascritti copiando i file da `templates/emails/` nella cartella `recesso-facile/emails/` del tema attivo.

## Sicurezza

- Verifica nonce su tutte le richieste AJAX e sui form.
- Controllo delle capability sulle operazioni di amministrazione e sugli endpoint REST.
- Query parametrizzate.
- Sanitizzazione e validazione degli input, escaping degli output.
- Ricevute PDF non accessibili pubblicamente: nome file basato su hash non indovinabile, directory protetta, download previa verifica.
- Limitazione di frequenza sulla creazione delle richieste.

Per segnalazioni di sicurezza vedere [SECURITY.md](SECURITY.md).

## Licenza

Distribuito sotto licenza [GNU General Public License v2.0](LICENSE) o successiva.

## Autore

Andrea Ferro — [irn3.com](https://irn3.com)
