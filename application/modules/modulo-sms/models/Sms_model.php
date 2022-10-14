<?php

class Sms_model extends CI_Model
{
    const SKEBBY_CLASSIC = 'classic';
    const SKEBBY_PLUS = 'plus';
    const SKEBBY_BASIC = 'basic';
    const INTL_PREFIX_DEFAULT = 39;

    /**
     * Sender data
     *
     * @var array
     */
    private $sender = [];

    /**
     * Array contenente i parametri di connessione ai vari servizi
     *
     * @var array
     */
    private $services = [];

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Setup with empty sender
        $this->setSender();
    }

    /**
     * Imposta dati sender
     *
     * @param string|null $name
     * @param string|null $number
     */
    public function setSender($name = null, $number = null)
    {
        $this->sender = compact('name', 'number');
    }

    /**
     * Aggiungi i parametri di connessione per skebby
     *
     * @param string $username
     * @param string $password
     * @param bool $test
     * @return \Sms_model
     */
    public function skebbyConnect($username, $password, $test = false)
    {
        //require_once './skebby_sms.php';

        $this->setServiceParams('skebby', [
            'username' => $username,
            'password' => $password,
            'testMode' => (bool) $test
        ]);

        return $this;
    }

    /**
     * Invia sms usando skebby. Modalità strict: bloccante, altrimenti non
     * bloccante.
     *   - modalità non strict desiderabile per invii multipli o quando si vuole
     *     lasciare che il sistema configuri automaticamente l'invio in caso di
     *     errori non bloccanti
     *   - modalità strict lancia un'eccezione al minimo problema (un numero non
     *     corretto, ecc...). In genere si usa in operazioni mission critical,
     *     quando è indispensabile che il messaggio venga recapitato
     *
     * @param array|string $numbers
     * @param string $text
     * @param array $data
     * @param bool $strict
     * @param string $type
     * @param string|null $senderName
     * @param string|null $senderNum
     *
     * @return int
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */

    
    public function send_sms($mobile_number, $text)
    {
        // Configurations
        $config = $this->apilib->searchFirst('sms_configuration');

        // Check balance
        if ($config['sms_configuration_saldo'] <= 0) {
            throw new ApiException('Oh no, your SMS balance is 0');
            exit;
        }

        // Controllo che il numero di telefono sia effettivamente un numero
        if (!ctype_digit($mobile_number)) {
            return false;
        }

        // Assegno i dati passati in post a delle variabili
        $login = $config['sms_configuration_login'];
        $passwd = $config['sms_configuration_password'];
        $mittente = $config['sms_configuration_mittente'];
        $saldo = $config['sms_configuration_saldo'];
        $testo = $text;
        $_saldo = $saldo - 1;

        // Invio l'sms e scalo i crediti

        // @TODO -> Se il testo contiene più di 160 caratteri, scalare 1 credito ogni 160 chars
        try {
            $this->apilib->edit('sms_configuration', $config['sms_configuration_id'], ['sms_configuration_saldo' => $_saldo]);
        } catch (Exception $e) {
            throw new ApiException('Balance problem.');
            exit;
        }

        try {
            $this->skebbyConnect($login, $passwd)->skebbySend($mobile_number, $text, [], true, Sms_model::SKEBBY_CLASSIC, $mittente);
        } catch (Exception $e) {
            throw new ApiException('Skebby error: '.$e->getMessage());
            exit;
        }
        return true;
    }

    


    // Do not use this method to send SMS, use send_sms.
    public function skebbySend($numbers, $text, array $data = [], $strict = false, $type = self::SKEBBY_CLASSIC, $senderName = null, $senderNum = null)
    {

        // Recupera parametri skebby dal collettore servizi
        $params = $this->getServiceParams('skebby');

        // Normalizzazione numeri:
        // ---
        // I numeri verranno ciclati e a ciascuno verrà anteposto il prefisso
        // internazionale senza +.
        // Nel caso in cui il prefisso non sia passato assieme al numero, verrà
        // impostato il prefisso di default
        if (!is_array($numbers)) {
            $numbers = [$numbers];
        }

        foreach ($numbers as $k => &$number) {
            if ($strict) {
                $number = $this->normalizePhoneNumber($number);
            } else {
                try {
                    $number = $this->normalizePhoneNumber($number);
                } catch (Exception $ex) {
                    unset($numbers[$k]);
                }
            }
        }

        if (!$numbers) {
            // Nessun numero? 0 sms inviati
            return 0;
        }


        // Determina il tipo di sms da inviare [strettamente legato al servizio
        // skebby]
        switch ($type) {
            case self::SKEBBY_BASIC:
                $type = $params['testMode'] ? SMS_TYPE_TEST_BASIC : SMS_TYPE_BASIC;
                break;

            case self::SKEBBY_PLUS:
                $type = $params['testMode'] ? SMS_TYPE_TEST_CLASSIC_PLUS : SMS_TYPE_CLASSIC_PLUS;
                break;

            default:
                if (!$strict or $type === self::SKEBBY_CLASSIC) {
                    // Di default imposto il tipo classic se siamo in modalità
                    // NON STRICT oppure se effetticamente abbiamo richiesto
                    // l'invio di un sms classic
                    $type = $params['testMode'] ? SMS_TYPE_TEST_CLASSIC : SMS_TYPE_CLASSIC;
                } else {
                    // In tutti gli altri casi lancio un'eccezione per bloccare
                    // l'invio
                    throw new InvalidArgumentException(sprintf(
                        "Tipo sms %s non valido. Tipi accettati: %s, %s, %s",
                        $type,
                        'Sms_model::SKEBBY_CLASSIC (:' . self::SKEBBY_CLASSIC . ')',
                        'Sms_model::SKEBBY_BASIC (:' . self::SKEBBY_BASIC . ')',
                        'Sms_model::SKEBBY_PLUS (:' . self::SKEBBY_PLUS . ')'
                    ));
                }
        }

        // A questo punto prova l'invio dell'sms: se lo stato non è `success`
        // allora lancio una runtime exception, altrimenti ritorno il numero di
        // sms inviati
        $result = skebbyGatewaySendSMS(
            $params['username'],
            $params['password'],
            $numbers,
            $this->replacePlaceholders($text, $data),
            $type,
            $senderNum ?: $this->sender['number'],
            $senderName ?: $this->sender['name']
        );

        if ($result['status'] != 'success') {
            throw new RuntimeException($result['message'], $result['code']);
        }

        return count($numbers);
    }

    // ==========================
    // Internals
    // ==========================

    /**
     * Aggiungi i parametri di connessione ad un servizio
     *
     * @param string $name
     * @param array $params
     */
    private function setServiceParams($name, array $params)
    {
        $this->services[$name] = $params;
    }

    /**
     * Ottieni i parametri di connessione ad un servizio
     *
     * @param string $name
     * @return array
     * @throws RuntimeException
     */
    private function getServiceParams($name)
    {
        if (empty($this->services[$name])) {
            throw new RuntimeException(sprintf("Il servizio %s non è stato impostato", $name));
        }

        return $this->services[$name];
    }

    /**
     * Normalizza il numero di telefono
     *  - rimuove ogni carattere estraneo
     *  - rimuove il +{prefix}
     *
     * @param string $cell
     * @return string
     */
    private function normalizePhoneNumber($cell)
    {
        $_orig = $cell;
        $number = str_replace([' ', '-', '/'], "", $cell);

        if ($number[0] == '+') {
            // Il numero ha il prefisso
            $_number = ltrim($number, '+');
            $prefix = substr($_number, 0, 2);
            $number = substr($_number, 2);
        } else {
            $prefix = self::INTL_PREFIX_DEFAULT;
        }

        if (strlen($number) < 6 or !is_numeric($number) or !is_numeric($prefix)) {
            throw new InvalidArgumentException(sprintf("Il numero %s non è corretto", $_orig));
        }

        return $prefix . $number;
    }

    /**
     * Sostituisci i placeholder nel testo nella forma {key}
     *
     * @param string $string
     * @param array $data
     */
    private function replacePlaceholders($string, array $data)
    {
        if (!$data) {
            return $string;
        }

        return str_replace(
            array_map(function ($key) {
                return '{' . $key . '}';
            }, array_keys($data)),
            array_values($data),
            $string
        );
    }
}

define("NET_ERROR", "Errore+di+rete+impossibile+spedire+il+messaggio");
define("SENDER_ERROR", "Puoi+specificare+solo+un+tipo+di+mittente%2C+numerico+o+alfanumerico");

define("SMS_TYPE_CLASSIC", "classic");
define("SMS_TYPE_CLASSIC_PLUS", "classic_plus");
define("SMS_TYPE_BASIC", "basic");
define("SMS_TYPE_TEST_CLASSIC", "test_classic");
define("SMS_TYPE_TEST_CLASSIC_PLUS", "test_classic_plus");
define("SMS_TYPE_TEST_BASIC", "test_basic");


function do_post_request($url, $data, $optional_headers = null)
{
    if (!function_exists('curl_init')) {
        $params = array(
            'http' => array(
                'method' => 'POST',
                'content' => $data
            )
        );
        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            return 'status=failed&message='.NET_ERROR;
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            return 'status=failed&message='.NET_ERROR;
        }
        return $response;
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Generic Client');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($optional_headers !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $optional_headers);
        }

        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) {
            return 'status=failed&message='.NET_ERROR;
        }
        return $response;
    }
}

function skebbyGatewaySendSMS($username, $password, $recipients, $text, $sms_type=SMS_TYPE_CLASSIC, $sender_number='', $sender_string='', $user_reference='', $charset='', $optional_headers=null)
{
    $url = 'https://gateway.skebby.it/api/send/smseasy/advanced/http.php';

    switch ($sms_type) {
        case SMS_TYPE_CLASSIC:
        default:
            $method='send_sms_classic';
            break;
        case SMS_TYPE_CLASSIC_PLUS:
            $method='send_sms_classic_report';
            break;
        case SMS_TYPE_BASIC:
            $method='send_sms_basic';
            break;
        case SMS_TYPE_TEST_CLASSIC:
            $method='test_send_sms_classic';
            break;
        case SMS_TYPE_TEST_CLASSIC_PLUS:
            $method='test_send_sms_classic_report';
            break;
        case SMS_TYPE_TEST_BASIC:
            $method='test_send_sms_basic';
            break;
   }

    $parameters = 'method='
                  .urlencode($method).'&'
                  .'username='
                  .urlencode($username).'&'
                  .'password='
                  .urlencode($password).'&'
                  .'text='
                  .urlencode($text).'&'
                  .'recipients[]='.implode('&recipients[]=', $recipients)
                  ;
                  
    if ($sender_number != '' && $sender_string != '') {
        parse_str('status=failed&message='.SENDER_ERROR, $result);
        return $result;
    }
    $parameters .= $sender_number != '' ? '&sender_number='.urlencode($sender_number) : '';
    $parameters .= $sender_string != '' ? '&sender_string='.urlencode($sender_string) : '';

    $parameters .= $user_reference != '' ? '&user_reference='.urlencode($user_reference) : '';

    
    switch ($charset) {
        case 'UTF-8':
            $parameters .= '&charset='.urlencode('UTF-8');
            break;
        case '':
        case 'ISO-8859-1':
        default:
    }
    
    parse_str(do_post_request($url, $parameters, $optional_headers), $result);

    return $result;
}

function skebbyGatewayGetCredit($username, $password, $charset='')
{
    $url = "https://gateway.skebby.it/api/send/smseasy/advanced/http.php";
    $method = "get_credit";
    
    $parameters = 'method='
                .urlencode($method).'&'
                .'username='
                .urlencode($username).'&'
                .'password='
                .urlencode($password);
                
    switch ($charset) {
        case 'UTF-8':
            $parameters .= '&charset='.urlencode('UTF-8');
            break;
        default:
    }
    
    parse_str(do_post_request($url, $parameters), $result);
    return $result;
}

// Invio singolo
//$recipients = array('393494400076');

// Per invio multiplo
// $recipients = array('393471234567','393497654321');


// ------------ Invio SMS Classic --------------

// Invio SMS CLASSIC con mittente personalizzato di tipo alfanumerico
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you?', SMS_TYPE_CLASSIC,'','John');

// Invio SMS CLASSIC con mittente personalizzato di tipo numerico
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you?', SMS_TYPE_CLASSIC,'393471234567');


// ------------- Invio SMS Basic ----------------
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you? By John', SMS_TYPE_BASIC);


// ------------ Invio SMS Classic Plus -----------

// Invio SMS CLASSIC PLUS(con notifica) con mittente personalizzato di tipo alfanumerico
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you?', SMS_TYPE_CLASSIC_PLUS,'','John');

// Invio SMS CLASSIC PLUS(con notifica) con mittente personalizzato di tipo numerico
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you?', SMS_TYPE_CLASSIC_PLUS,'393471234567');

// Invio SMS CLASSIC PLUS(con notifica) con mittente personalizzato di tipo numerico e stringa di riferimento personalizzabile
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you?', SMS_TYPE_CLASSIC_PLUS,'393471234567','','riferimento');




// ------------------------------------------------------------------
// ATTENZIONE I TIPI DI SMS SMS_TYPE_TEST* NON FANNO PARTIRE ALCUN SMS
// SERVONO SOLO PER VERIFICARE LA POSSIBILITA' DI RAGGIUNGERE IL SERVER DI SKEBBY
// ------------------------------------------------------------------

// ------------- Testing invio SMS Classic---------
// TEST di invio SMS CLASSIC con mittente personalizzato di tipo alfanumerico
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you?', SMS_TYPE_TEST_CLASSIC,'','John');

// TEST di invio SMS CLASSIC con mittente personalizzato di tipo numerico
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you?', SMS_TYPE_TEST_CLASSIC,'393471234567');

// ------------- Testing invio SMS Classic Plus---------

// TEST di invio SMS CLASSIC PLUS(con notifica) con mittente personalizzato di tipo alfanumerico
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you?', SMS_TYPE_TEST_CLASSIC_PLUS,'','John');

// TEST di invio SMS CLASSIC PLUS(con notifica) con mittente personalizzato di tipo numerico
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you?', SMS_TYPE_TEST_CLASSIC_PLUS,'393471234567');

// ------------- Testing invio SMS Basic---------------
// $result = skebbyGatewaySendSMS('username','password',$recipients,'Hi Mike, how are you? By John', SMS_TYPE_TEST_BASIC);

// ------------------------------------------------------------------
// ATTENZIONE I TIPI DI SMS SMS_TYPE_TEST* NON FANNO PARTIRE ALCUN SMS
// SERVONO SOLO PER VERIFICARE LA POSSIBILITA' DI RAGGIUNGERE IL SERVER DI SKEBBY
// ------------------------------------------------------------------
/*



if($result['status']=='success') {
    echo '<b style="color:#8dc63f;">SMS inviato con successo</b><br/>';
    if (isset($result['remaining_sms'])){
        echo '<b>SMS rimanenti:</b>'.$result['remaining_sms'];
    }
    if (isset($result['id'])){
        echo '<b>ID:</b>'.$result['id'];
    }
}

if($result['status']=='failed')	{
    echo '<b style="color:#ed1c24;">Invio fallito</b><br/>';
    if(isset($result['code'])) {
        echo '<b>Codice:</b>'.$result['code'].'<br/>';
    }
        echo '<b>Motivo:</b>'.urldecode($result['message']);
}
*/

// ------------ Controllo del CREDITO RESIDUO -------------
// $credit_result = skebbyGatewayGetCredit('username', 'password');


// if($credit_result['status']=='success') {
  // echo 'Credito residuo: ' .$credit_result['credit_left']."\n";
  // echo 'SMS Classic rimanenti: ' .$credit_result['classic_sms']."\n";
  // echo 'SMS Basic rimanenti: ' .$credit_result['basic_sms']."\n";
// }

// if($credit_result['status']=='failed') {
  // echo 'Invio richiesta fallito';
// }
