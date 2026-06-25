# Changelog - Versione 1.0.1

**Data rilascio:** 25 Giugno 2026

## 🎯 Nuove Funzionalità

### 1. Campo Nome Cliente nel Form (Art. 54-bis Compliance)
- **Aggiunto campo obbligatorio "Nome e Cognome"** nel form di recesso (Step 1)
- Il campo richiede almeno 3 caratteri per garantire validità
- Il nome viene ora **esplicitamente richiesto all'utente** invece di essere recuperato automaticamente dall'ordine
- Questo migliora la conformità all'Art. 54-bis che richiede la raccolta di: nome, numero ordine, email

**File modificati:**
- `templates/withdrawal-form.php` - Aggiunto input field customer_name
- `assets/js/frontend.js` - Validazione e invio customer_name
- `includes/class-rf-ajax-handler.php` - Gestione customer_name nel backend
- `includes/validators/class-rf-validator.php` - Validazione customer_name
- `includes/services/class-rf-withdrawal-service.php` - Salvataggio customer_name
- `includes/class-rf-install.php` - Schema database aggiornato
- `includes/admin/class-rf-admin-requests.php` - Visualizzazione customer_name
- `includes/services/class-rf-pdf-service.php` - Customer_name nel PDF

### 2. Integrazione WooCommerce - Pulsante Recesso negli Ordini

Nuova classe `RF_WooCommerce_Integration` che aggiunge il pulsante di recesso in punti strategici:

#### A. Pagina Dettaglio Ordine (My Account)
- **Pulsante "Richiedi Recesso"** nella sezione ordine del cliente
- Verifica automatica dell'idoneità dell'ordine
- Messaggi informativi sui 14 giorni disponibili
- Gestione richieste duplicate (mostra avviso se già presente)

#### B. Pagina Thank You (Conferma Ordine)
- Sezione informativa sul diritto di recesso
- Appare dopo il completamento dell'ordine
- Ricorda al cliente i 14 giorni disponibili

#### C. Email di Conferma Ordine
- **Link diretto al form di recesso** nelle email ai clienti
- Box evidenziato con informazioni legali
- Versioni HTML e testo semplice
- Si attiva per email: `customer_completed_order` e `customer_processing_order`

#### D. Metabox Admin Ordine
- **Nuovo metabox "Recesso Facile"** nella pagina admin ordine
- Mostra tutte le richieste di recesso associate all'ordine
- Verifica idoneità in tempo reale
- Calcolo giorni rimanenti per il recesso
- Link diretti alle richieste

**File creati:**
- `includes/class-rf-woocommerce-integration.php` - Classe completa integrazione

**File modificati:**
- `recesso-facile.php` - Inizializzazione RF_WooCommerce_Integration
- `assets/css/frontend.css` - Stili pulsante ordini
- `assets/css/admin.css` - Stili metabox admin

### 3. Sistema di Aggiornamento Database

- **Nuova classe `RF_Update`** per gestire migrazioni database automatiche
- Aggiunge automaticamente la colonna `customer_name` per installazioni esistenti
- Popola i dati esistenti con nome e cognome dall'ordine
- Versioning database: da 1.0.0 a 1.0.1

**File creati:**
- `includes/class-rf-update.php` - Gestore aggiornamenti

**File modificati:**
- `recesso-facile.php` - Check aggiornamenti all'inizializzazione

## 🔧 Miglioramenti Tecnici

### Database
- Aggiunta colonna `customer_name VARCHAR(100) NOT NULL` alla tabella `wp_rf_withdrawals`
- Migrazione automatica per installazioni esistenti
- Popolamento automatico dati storici

### Validazione
- Validazione nome: minimo 3 caratteri, massimo 100
- Sanitizzazione `sanitize_text_field()` per customer_name
- Messaggi errore specifici per il campo nome

### JavaScript
- Aggiornato `collectFormData()` per includere customer_name
- Aggiornato `updateSummary()` per mostrare customer_name
- Validazione real-time campo nome in Step 1

### Admin Interface
- Lista richieste: mostra nome cliente in grassetto sopra email
- Dettaglio richiesta: riga dedicata per "Nome Cliente"
- Ordinamento e filtri mantengono funzionalità

### PDF Ricevuta
- Nome cliente ora proviene dal campo compilato dall'utente
- Ordine campi: Nome prima, Email dopo
- Miglior conformità legale con dato esplicito

## 📍 Posizionamento Strategico

Il pulsante/link di recesso è ora presente in:

1. ✅ **Area My Account** → Dettaglio Ordine (soluzione principale)
2. ✅ **Pagina Thank You** → Dopo completamento ordine
3. ✅ **Email Conferma** → Link diretto al form
4. ✅ **Admin Ordine** → Metabox con informazioni complete
5. ✅ **Pulsante Sticky** → Presente su tutte le pagine (già esistente)
6. ✅ **Pagina Dedicata** → [recesso_facile_form] shortcode

## 🔒 Compatibilità

- ✅ WooCommerce 7.0+
- ✅ HPOS (High-Performance Order Storage)
- ✅ WordPress 6.0+
- ✅ PHP 7.4+
- ✅ Backward compatible con v1.0.0

## 📋 Checklist Conformità Art. 54-bis

- ✅ **Pulsante visibile** - Multipli punti di accesso
- ✅ **Form guidato** - 3 step con raccolta: nome, numero ordine, email
- ✅ **Doppia conferma** - Step 3 con checkbox obbligatori
- ✅ **Ricevuta automatica** - Email + PDF con timestamp
- ✅ **Mobile responsive** - Design ottimizzato
- ✅ **Periodo 14 giorni** - Verifica automatica con calcolo giorni

## 🚀 Istruzioni Aggiornamento

### Per Nuove Installazioni
Installare normalmente - tutte le funzionalità sono già attive.

### Per Aggiornamento da v1.0.0
1. Aggiornare il plugin tramite WordPress
2. La migrazione database si esegue automaticamente
3. I dati esistenti vengono popolati automaticamente
4. Nessuna azione richiesta dall'utente

### Verifica Post-Aggiornamento
```bash
# Controllare che la colonna sia stata aggiunta
SELECT customer_name FROM wp_rf_withdrawals LIMIT 1;

# Verificare il log attività
# WP Admin > Recesso Facile > Attività
# Cercare: "Database aggiornato alla versione 1.0.1"
```

## 🐛 Bug Fix (da v1.0.0)

Nessun bug critico in questa release - solo miglioramenti e nuove funzionalità.

## 📚 Documentazione

### Shortcodes Disponibili
```php
// Form completo di recesso
[recesso_facile_form]

// Pulsante semplice
[recesso_facile_button]

// Status richieste utente
[recesso_facile_status]
```

### Hook per Sviluppatori
```php
// Personalizzare URL pagina recesso
add_filter('recesso_facile_withdrawal_page_url', 'custom_withdrawal_url');

// Modificare idoneità ordine
add_filter('recesso_facile_check_eligibility', 'custom_eligibility_check', 10, 3);

// Azione dopo creazione integrazione WooCommerce
add_action('recesso_facile_woocommerce_integration_init', 'custom_integration_hook');
```

## 👥 Crediti

- **Sviluppo:** Andrea Ferro
- **Conformità Legale:** Art. 54-bis Codice del Consumo D.Lgs. 206/2005

## 📞 Supporto

Per assistenza: https://irn3.com/supporto
Per segnalazioni bug: https://github.com/1r0n3d3v3l0per/recesso-facile/issues

---

**Nota Importante:** Questa versione migliora significativamente la conformità all'Art. 54-bis richiedendo esplicitamente il nome del cliente nel form, come previsto dalla normativa "Easy In, Easy Out".
