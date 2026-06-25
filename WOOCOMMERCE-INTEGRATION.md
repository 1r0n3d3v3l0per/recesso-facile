# Integrazione WooCommerce - Guida Completa

## Panoramica

Il plugin **Recesso Facile** si integra perfettamente con WooCommerce per offrire il pulsante di recesso in tutti i punti strategici del customer journey.

## 📍 Dove Appare il Pulsante di Recesso

### 1. Pagina Dettaglio Ordine (My Account)

**Posizione:** `My Account > Ordini > [Ordine specifico]`

Il cliente vede una sezione dedicata "Diritto di Recesso" con:
- Informazioni sui 14 giorni disponibili
- Pulsante "Richiedi Recesso" (se idoneo)
- Messaggi di errore chiari se non idoneo
- Avviso se già presente richiesta

**Hook utilizzato:**
```php
add_action('woocommerce_order_details_after_order_table', [callback], 10, 1);
```

**Condizioni di visualizzazione:**
- Plugin abilitato (`rf_enable_withdrawal = 'yes'`)
- Ordine idoneo (status + periodo + eccezioni)
- Nessuna richiesta duplicata in corso

---

### 2. Pagina Thank You (Conferma Ordine)

**Posizione:** Subito dopo il completamento dell'ordine

Box informativo che ricorda al cliente il diritto di recesso.

**Hook utilizzato:**
```php
add_action('woocommerce_thankyou', [callback], 20, 1);
```

**Contenuto:**
- Testo: "Ricorda: hai 14 giorni di tempo per recedere..."
- Appare solo per ordini idonei
- Design pulito e non invadente

---

### 3. Email di Conferma Ordine

**Email interessate:**
- `customer_completed_order`
- `customer_processing_order`

**Hook utilizzato:**
```php
add_action('woocommerce_email_after_order_table', [callback], 20, 4);
```

**Versione HTML:**
Box evidenziato con:
- Titolo "Diritto di Recesso"
- Testo informativo sui 14 giorni
- Pulsante CTA con link diretto al form
- Colori brand (#2271b1)

**Versione Testo Semplice:**
```
--------------------------------------------------

DIRITTO DI RECESSO

Hai 14 giorni di tempo per recedere da questo acquisto.

Per richiedere il recesso, visita:
https://tuosito.it/richiesta-recesso/
```

---

### 4. Metabox Admin Ordine

**Posizione:** WP Admin > WooCommerce > Ordini > [Ordine specifico]

**Sidebar metabox "Recesso Facile" mostra:**

1. **Richieste Esistenti:**
   - Lista di tutte le richieste associate
   - Status colorato per ogni richiesta
   - Link diretto alla gestione richiesta
   - Data creazione

2. **Verifica Idoneità:**
   - ✓ Idoneo al recesso (verde)
   - ✗ Non idoneo con motivo (rosso)
   - Giorni rimanenti: X di 14

**Hook utilizzato:**
```php
add_action('add_meta_boxes', [callback]);
```

**Compatibilità HPOS:**
Supporta sia il vecchio sistema ordini che HPOS (High-Performance Order Storage).

---

## 🎨 Personalizzazione CSS

### Frontend (Pagina Ordine)

```css
/* Sezione principale */
.rf-withdrawal-section {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

/* Pulsante CTA */
.rf-withdrawal-button {
    display: inline-block;
    padding: 12px 24px;
    background: #2271b1;
    color: #ffffff;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
}
```

### Admin (Metabox)

```css
/* Metabox ordine */
.rf-admin-order-metabox h4 {
    margin: 10px 0 5px;
    font-size: 13px;
    font-weight: 600;
}

/* Badge status */
.rf-status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
}
```

---

## ⚙️ Configurazione

### Impostazioni Generali

**WP Admin > Recesso Facile > Impostazioni > Generale**

- `rf_enable_withdrawal` - Abilita/disabilita tutto il sistema
- `rf_button_text` - Testo personalizzato del pulsante (default: "Richiedi Recesso")
- `rf_withdrawal_period` - Giorni disponibili (default: 14)

### Disabilitare Integrazione Specifica

Per disabilitare solo la visualizzazione in un punto specifico:

```php
// Nel tema functions.php

// Disabilita nella pagina ordine
remove_action('woocommerce_order_details_after_order_table',
    ['RF_WooCommerce_Integration', 'add_withdrawal_button_to_order_details'], 10);

// Disabilita nella Thank You page
remove_action('woocommerce_thankyou',
    ['RF_WooCommerce_Integration', 'add_withdrawal_info_to_thankyou'], 20);

// Disabilita nelle email
remove_action('woocommerce_email_after_order_table',
    ['RF_WooCommerce_Integration', 'add_withdrawal_link_to_email'], 20);

// Disabilita metabox admin
remove_action('add_meta_boxes',
    ['RF_WooCommerce_Integration', 'add_admin_order_metabox']);
```

---

## 🔧 Hook per Sviluppatori

### Personalizzare URL Form Recesso

```php
add_filter('recesso_facile_withdrawal_page_url', function($url) {
    // Usa URL personalizzato
    return 'https://tuosito.it/custom-recesso/';
});
```

### Modificare Testo Informativo Email

```php
add_filter('recesso_facile_email_withdrawal_text', function($text, $order) {
    $period = get_option('rf_withdrawal_period', 14);
    return sprintf(
        'Hai %d giorni dalla ricezione per recedere. Contattaci per assistenza.',
        $period
    );
}, 10, 2);
```

### Aggiungere Controlli Personalizzati Idoneità

```php
add_filter('recesso_facile_check_eligibility', function($eligible, $order_id, $email) {
    if (!$eligible) {
        return $eligible; // Mantieni errore esistente
    }

    // Aggiungi controllo personalizzato
    $order = wc_get_order($order_id);
    if ($order->get_payment_method() === 'custom_method') {
        return new WP_Error(
            'payment_method_excluded',
            'Questo metodo di pagamento non ammette recesso.'
        );
    }

    return $eligible;
}, 10, 3);
```

### Personalizzare Contenuto Metabox Admin

```php
add_action('recesso_facile_admin_metabox_after', function($order) {
    // Aggiungi contenuto extra al metabox
    echo '<hr>';
    echo '<p><strong>Note interne:</strong></p>';
    echo '<p>' . get_post_meta($order->get_id(), '_rf_internal_notes', true) . '</p>';
}, 10, 1);
```

---

## 🧪 Test Funzionalità

### Checklist Test Completi

1. **Test Ordine Idoneo:**
   - [ ] Crea ordine nuovo
   - [ ] Completa ordine
   - [ ] Verifica pulsante in My Account
   - [ ] Verifica sezione in Thank You page
   - [ ] Verifica link in email ricevuta
   - [ ] Verifica metabox in admin ordine

2. **Test Ordine Non Idoneo:**
   - [ ] Crea ordine > 14 giorni fa
   - [ ] Verifica messaggio "periodo scaduto"
   - [ ] Pulsante non deve apparire
   - [ ] Metabox admin mostra "Non idoneo"

3. **Test Richiesta Duplicata:**
   - [ ] Crea richiesta per ordine
   - [ ] Vai a dettaglio ordine
   - [ ] Verifica messaggio "già richiesta in corso"
   - [ ] Metabox admin mostra richiesta esistente

4. **Test Prodotto Escluso:**
   - [ ] Aggiungi eccezione prodotto (Art. 59)
   - [ ] Ordina quel prodotto
   - [ ] Verifica messaggio "prodotto non idoneo"
   - [ ] Check metabox admin

---

## 🎯 Best Practices

### 1. Mantenere Link Sempre Accessibile
Anche se l'ordine non è idoneo, mostra sempre informazioni chiare sul perché.

### 2. Email Chiare e Dirette
Il link nelle email deve portare direttamente al form, non a una pagina generica.

### 3. Admin Visibility
Il metabox admin deve dare tutte le info a colpo d'occhio senza dover aprire altre pagine.

### 4. Mobile First
Tutti i pulsanti e le sezioni devono essere perfettamente visibili e cliccabili su mobile.

### 5. Testi Legali Corretti
Usa sempre il linguaggio previsto dal Codice del Consumo:
- "diritto di recesso"
- "14 giorni"
- "Art. 52 e seguenti"

---

## 🐛 Troubleshooting

### Il pulsante non appare nella pagina ordine

**Possibili cause:**
1. Plugin disabilitato: verifica `rf_enable_withdrawal = 'yes'`
2. Ordine non idoneo: controlla status ordine e periodo
3. Tema custom: verifica che esegua `woocommerce_order_details_after_order_table`
4. Cache attiva: svuota cache tema e WooCommerce

**Debug:**
```php
// Aggiungi in functions.php per debug
add_action('woocommerce_order_details_after_order_table', function($order) {
    echo '<!-- RF Debug: Hook fired for order ' . $order->get_id() . ' -->';
    $eligibility = RF_Withdrawal_Service::check_eligibility(
        $order->get_id(),
        $order->get_billing_email()
    );
    var_dump($eligibility);
}, 5);
```

### Il link nelle email non funziona

**Soluzioni:**
1. Verifica pagina recesso: `WP Admin > Recesso Facile > Impostazioni`
2. Ricrea pagina se mancante
3. Controlla permalink: `Impostazioni > Permalink > Salva`

### Metabox non visibile in admin

**Soluzioni:**
1. Verifica permessi utente: `manage_woocommerce`
2. HPOS attivo: verifica compatibilità
3. Opzioni schermo: controlla che metabox sia selezionato

---

## 📊 Metriche e Analytics

### Tracciare Click Pulsante Recesso

```php
add_action('recesso_facile_button_clicked', function($order_id, $location) {
    // Invia a Google Analytics
    if (function_exists('gtag')) {
        gtag('event', 'recesso_button_click', [
            'event_category' => 'Recesso',
            'event_label' => $location, // 'order_page', 'email', etc.
            'value' => $order_id
        ]);
    }
}, 10, 2);
```

### Monitorare Conversione Recessi

```php
add_action('recesso_facile_withdrawal_created', function($withdrawal_id, $data) {
    // Log conversione
    update_option('rf_total_withdrawals', get_option('rf_total_withdrawals', 0) + 1);

    // Traccia sorgente
    $source = isset($_COOKIE['rf_source']) ? $_COOKIE['rf_source'] : 'direct';
    update_post_meta($withdrawal_id, '_rf_source', $source);
}, 10, 2);
```

---

## 🚀 Ottimizzazioni Performance

### Cache-Friendly

L'integrazione è progettata per funzionare con:
- WP Super Cache
- W3 Total Cache
- WP Rocket
- Autoptimize

### Fragment Caching

```php
// Nel tema, cachea la sezione recesso
$cache_key = 'rf_order_section_' . $order_id;
$output = get_transient($cache_key);

if (false === $output) {
    ob_start();
    do_action('woocommerce_order_details_after_order_table', $order);
    $output = ob_get_clean();
    set_transient($cache_key, $output, HOUR_IN_SECONDS);
}

echo $output;
```

---

## 📞 Supporto

Per domande sull'integrazione WooCommerce:
- **Email:** supporto@irn3.com
- **Documentazione:** https://irn3.com/docs/recesso-facile
- **GitHub Issues:** https://github.com/1r0n3d3v3l0per/recesso-facile/issues

---

**Ultima revisione:** 25 Giugno 2026
**Versione plugin:** 1.0.1
**Compatibilità WooCommerce:** 7.0 - 9.0
