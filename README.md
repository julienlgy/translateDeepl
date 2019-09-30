### TranslateDeepL ###  
This was my first PHP project

## Constantes ##
All langs = TranslateDeepl::langs

## Exemple d'utilisation ##
Utilisation advice :

> $translate = new TranslateDeepl($from (default:FR), $to (default:self::langs), $texte (optional))

# 1 #

> $alltranslation = $translate->translate(Texte (optional))->getResponses(lang (optional))

> $alltranslation > ARRAY (de,fr,en,es...).

## 2 ##

> $langs = $translate->translate()->getTo();


> foreach ($langs => $lang) {


>  $res = $translate->getResponses($lang);

>  your code here..

>}

**->setFrom : optionnel**

**->setTo : 1 ou plusieurs langues parmis la liste des langues disponibles, voir TranslateDeepl::langs**

**->setText : Une array contenant titre, chapo, texte, renvoie la même array en multidimensionnelle (+langue)**

## Une fois ->translate appelé, les réponses ne sont pas renvoyés immédiatement, il faut retourner ->getResponses; ##

