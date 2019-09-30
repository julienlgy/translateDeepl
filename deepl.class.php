<?php
/******
@Author : julienLGY
******/
/******
All langs = TranslateDeepl::langs
Utilisation advice :

$translate = new TranslateDeepl($from (default:FR), $to (default:self::langs), $text (optional))
-----USE
$alltranslation = $translate->translate(Texte (optional))->getResponses(lang (optional))
$alltrsnaltion = ARRAY (de,fr,en,es...).
-----OR
$langs = $translate->translate()->getTo();
foreach ($langs => $lang) {
  $res = $translate->getResponses($lang);
  your code here..
}
******/

class TranslateDeepl {
  /********************************************************
    Variables to be used by the class
  ********************************************************/
  /// List of all langs can be used, go to https://www.deepl.com/api.html to see full list
  const langs = array('FR','DE','EN','ES','IT','NL','PL');
  /// Default language of the given text, can be null, the API auto search for a language
  protected $from;
  /// Targeted language, can be multiple, default self::langs.
  protected $to;
  /// Given text, should be an array. Initialize it on the construtor or in translate method.
  protected $text;
  /// All given options are stocked here
  protected $options;
  protected $method;
  const options = array(
    'split_sentences' => '1',
    'preserve_formatting' => '1'
    //'tag_handling' => 'xml',
    //'non_splitting_tags' => '...',
    //'ignore_tags' => '....'
  );

  /// Variable where is stored the last result.
  protected $translatedTexte;
  /// API KEY
  private $api = "{{YOUR API KEY}}";
  private $baserequest = "https://api.deepl.com/v1/translate?";
  /********************************************************/

  public function __construct($from = "auto",$to = self::langs, $text = '') {
    $this->setFrom($from);
    $this->setTo($to);
    $this->setText($text);
    $this->setOptions(self::options);
    $this->setMethod('post');
  }


  /********************************************************
    accessors and mutators [GET/SET]
  ********************************************************/
  public function getText() {
    return $this->text;
  }
  public function getTo() {
    return $this->to;
  }
  public function getResponses($lang = self::langs) {
    $onelang = false;
    if (gettype($lang) == "string") {
      $onelang = true;
      if (in_array($lang,self::langs)) {
          $lang = array($lang);
      } else {
        Throw new Exception($lang." not found in responses");
      }
    }
    foreach($this->translatedTexte As $langtr => $arraytranslated) {
      if (in_array($langtr,$lang)) {
        $temptranslated[$langtr] = $arraytranslated;
      }
    }
    if ($onelang) {
      return $temptranslated[$lang[0]];
    } else {
      return $temptranslated;
    }
  }
  public function getOptions() {
    return $this->options;
  }
  public function getMethod() {
    return $this->method;
  }
  /// MUTATORS
  public function setFrom($from) {
    $from = strtoupper($from);
    if (isset($from) && in_array($from,self::langs)) {
      $this->from = $from;
    } else {
      if (strtolower($from) == 'auto') {
        $this->from = 'auto';
      }
    }
    return $this;
  }
  public function setTo($to) {
    if (isset($to) && (gettype($to) == 'array')) {
      foreach($to As $key => $element) {
        $to[$key] = strtoupper($element);
        $element = strtoupper($element);
        if (!in_array($element,self::langs)) {
          Throw new Exception($element." not found..");
          return $this;
        }
        if ($element == $this->from) {
          unset($to[$key]);
        }
      }
      $this->to = $to;
    } else {
      if ($this->to == $this->from) {
        Throw new Exception("Cannot translate same language");
      } else {
        $this->to = array($to);
      }
    }
    return $this;
  }
  public function setTexte($text) {
    $this->text = array();
    if (gettype($text) == 'array' || gettype($text) == 'string'){
      if (gettype($text) == 'string') {
        $this->text = array(urlencode($text));
      } else {
        foreach($text As $k=>$txt) {
          array_push($this->text,(urlencode($txt)));
        }
      }
    } else {
      Throw new Exception("Type du texte non reconnu. Valide : String ou array");
    }
    return $this;
  }
  public function setOptions($name,$value = null) {
    if (!isset($name)) {
      return false;
    } else {
      if (gettype($name) == 'array') {
        if ($name == self::options) {
          $this->options = $name;
          return $this;
        } else {
          return false;
        }
      } else {
        if (isset($value)) {
          if (in_array($name,array_keys(self::options))) {
            $this->options[$name] = $value;
            return $this;
          } else {
            return false;
          }
        } else {
          return false;
        }
      }
    }
    return $this;
  }
  public function setMethod($method) {
    if (in_array(strtolower($method),array('get','post'))) {
      $this->method = $method;
    } else {
      return false;
    }
  }
  /********************************************************/
  public function checkValidateLang($lang) {
    if (in_array($lang,self::langs)){
      return true;
    } else {
      return false;
    }
  }
  /********************************************************
    PROCESS > Translation
  ********************************************************/
  public function translate($text = null) {
    $this->initResponses();

    // If $text is set, remplace it.
    if (isset($text)) {
      $this->setText($text);
    }
    foreach($this->to As $to) {
      $this->translate_process($this->from,$to);
    }
    return $this;
  }
  public function quickTranslate($text,$to,$from = "auto") {
    if (isset($text)) {
      $temptxt = $this->getText();
      $this->setText($text);
      if (isset($to)) {
        if (gettype($to) == 'string') {
          if (in_array($to,self::langs)) {
            $this->setMethod("get");
            $this->translate_process($from,$to);
            $this->setText($temptxt);
            return $this->getResponses()[$to];
          } else {
            $this->setText($temptxt);
            return false;
          }
        } else {
          $this->setText($temptxt);
          return false;
        }
      } else {
        $this->setText($temptxt);
        return false;
      }
    } else {
      return false;
    }
  }
  private function translate_process($from,$to) {
    ///** Load the request **//
    $request = $this->baserequest;
    /// ** Define options **//
    $options = $this->options;
    foreach($this->text As $k=>$text) {
      $options = array_merge($options,array('text#'.$k => $text));
    }
    $options = array_merge($options,array('auth_key' => $this->api));
    if ($from != 'auto') {$options = array_merge($options,array('source_lang' => $from));}
    $options = array_merge($options,array('target_lang' => $to));
    /// ** POST / GET -> define parameters **//
    if (strtolower($this->method) == 'post') {
      $optpost = "";
      foreach($options As $k => $e) {
        if (count(explode('#',$k)) > 1) {
          $k = explode('#',$k)[0];
        }
        $optpost = $optpost.$k."=".$e."&";
      }
      $optpost = substr($optpost,0,strlen($optpost)-1);
      $res = $this->curlHandler($request,$optpost);
    } else if (strtolower($this->method == 'get')) {
      foreach($options As $k => $e) {
        if (count(explode('#',$k)) > 1) {
          $k = explode('#',$k)[0];
        }
        $request = $request.$k.'='.$e.'&';
      }
      $request = substr($request,0,strlen($request)-1);
      $res = $this->curlHandler($request);
    } else {
      Throw new Exception("Method not supported, select POST or GET");
    }
    $this->addResponse($to,$res);
  }
  private function curlHandler($req,$postoptions = null) {
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$req);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    if (strtolower($this->method) == 'post') {
      curl_setopt($ch,CURLOPT_POST,1);
      curl_setopt($ch,CURLOPT_POSTFIELDS,
        $postoptions
      );
    }
    $res = curl_exec($ch);
    if (!$res) {
      var_dump($req);
      var_dump($postoptions);
      Throw new Exception("cURL error : ".curl_error($ch));
    }
    curl_close($ch);
    return $this->JSONparser($res);
  }
  private function JSONparser($res) {
    try {
      $resdecoded = json_decode($res);
    } catch(Exception $e){
      Throw new Exception($e->getMessage());
    }
    if (isset($resdecoded)){
      return $resdecoded;
    } else {
      var_dump($res);
      Throw new Exception("No JSON");
    }
  }

  private function initResponses() {
    if (gettype($this->to) == "array") {
      foreach($this->to As $lang) {
        foreach ($this->text As $keyText => $text) {
          $this->translatedTexte[$lang][$keyText] = 0;
        }
      }
    } else {
      foreach ($this->text As $keyText => $text) {
        $this->translatedTexte[$this->to][$keyText] = 0;
      }
    }
  }
  private function addResponse($to,$res) {
    if (gettype($res) != 'object') {
      Throw new Exception("Invalid responses, check for the length of the request.");
    }
    if (isset($res->message)) {
      $this->Throwdeepl($res);
      return;
    }
    foreach ($res->translations As $pos => $obj) {
      $this->translatedTexte[$to][$pos] = $obj->text;
    }
  }
  private function Throwdeepl($res) {
    Throw new Exception("DEEPL : " .$res->message);
  }
  public function debug() {
    $return =  '<br>From : '.$this->from.' <br>To : ';
    if (gettype($this->to) == 'array') {
      $return = $return.join(', ',$this->to);
    } else {
      $return = $return.$this->to;
    }
    $return = $return.'<br>Texte : ';
    if (gettype($this->text) == 'array') {
      $return = $return. '' .join(',',$this->text);
    } else {
      $return = $return.$this->text;
    }
    $return = $return. '<br>';
    echo $return;
    return 'debug';
  }
}
?>
