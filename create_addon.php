<?php
#!/usr/bin/env php

namespace Tygh\CreateAddon;

use Tygh\Registry;

//  create_addon.php?addon=new_test_addon&lang=ru,en
//  php ./create_addon.php --addon=super_test_addon

define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');

require(dirname(__FILE__) . '/init.php');

class FileTemplate
{
    static $templates = [
      'addon.xml' => [
          'main' => <<<EOT
<?xml version="1.0"?>
<addon scheme="3.0">
    <id>%addon_id%</id>
    <version>1.0.0</version>
    <auto_install>MULTIVENDOR,ULTIMATE</auto_install>
    <priority>100</priority>
    <status>active</status>
    <position>0</position>
    <default_language>%default_language%</default_language>
</addon>
EOT
        ],
        'lang' => [
            'en' => <<<EOT
msgid ""
msgstr "Project-Id-Version: tygh\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Language-Team: English\n"
"Language: en_US"

msgctxt "Addons::name::%addon_id%"
msgid "%addon_id%"
msgstr "%addon_name%"

msgctxt "Addons::description::%addon_id%"
msgid "%addon_id%"
msgstr "%addon_description%"
EOT
            , 'ru' => <<<EOT
msgid ""
msgstr ""
"Project-Id-Version: cs-cart-latest\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Language-Team: Russian\n"
"Language: ru_RU\n"
"Plural-Forms: nplurals=4; plural=((n%10==1 && n%100!=11) ? 0 : ((n%10 >= 2 && n%10 <=4 && (n%100 < 12 || n%100 > 14)) ? 1 : ((n%10 == 0 || (n%10 >= 5 && n%10 <=9)) || (n%100 >= 11 && n%100 <= 14)) ? 2 : 3));\n"
"X-Generator: crowdin.com\n"
"X-Crowdin-Project: cs-cart-latest\n"
"X-Crowdin-Language: ru\n"
"X-Crowdin-File: /release-4.11.1/addons/%addon_id%.po\n"
"Last-Translator: cscart\n"
"PO-Revision-Date: 2019-11-06 09:42\n"

msgctxt "Addons::name::%addon_id%"
msgid "%addon_id%"
msgstr "%addon_name%"

msgctxt "Addons::description::%addon_id%"
msgid "%addon_id%"
msgstr "%addon_description%"
EOT
        ],
    ];

    static public function getTemplate($name)
    {
        return self::$templates[$name] ?? '';
    }
}

/**
*
*/
class GetAddonException extends \Exception
{

    protected function getMessageBody()
    {

        switch ( $this->getCode() ) {
            // case 3 : $message = "Data issue: " . $this->getMessage(); break;

            default: $message = "Something broke" . ' ' . $this->getMessage();
        }

        return $message;
    }

    public function __toString()
    {
        return 'Error!!! ' . $this->getMessageBody();
    }
}

/**
* Print info
*/
class DisplayMessage
{
    protected $line = 1;
    protected $break = '<br />';

    public function push($value, $showLine = true)
    {
        fn_echo( ($showLine ? (int) $this->line++ . '.  ' : '') . $value . $this->break);
    }

    public function hr()
    {
        $this->push('<hr />', false);
    }

    public function end()
    {
      $this->push('FIN', false);
      exit;
    }
}

/**
* Join cli & http
*/
class ScriptParam
{
    protected $param = [];

    public function __construct()
    {
      //  cli
      if (self::isConsole()) {
            //  param wih short name
            $consoleParamsWithShort = array(
                'a:'    => 'addon:',
                'p:'   => 'path:',
            );

            //  param only for long name
            $consoleParams = array_merge($consoleParamsWithShort, [
                'help',
                'wibug',
                'lang'
            ]);

            $this->param = getopt( implode('', array_keys($consoleParams)), $consoleParams );

            //  duplicate param with short name
            foreach ($consoleParamsWithShort as $short => $long) {
                if (isset($this->param[trim(str_replace(':', '', $short))])) {
                    $this->param[trim(str_replace(':', '', $long))] = $this->param[trim(str_replace(':', '', $short))];
                }
            }

        //  browser
        } else {
            $this->param = $_REQUEST;
        }
    }

    public function getall()
    {
        return $this->param;
    }

    public function get($name)
    {
        return isset($this->param[$name]) ? $this->param[$name] : '';
    }

    public function set($name, $value)
    {
        return $this->param[$name] = $value;
    }

    public function isset($name)
    {
        return isset($this->param[$name]);
    }

    static public function isConsole()
    {
        global $argc;
        return ($argc > 0);
    }

    static public function getHelp()
    {
      $jsonFormat = <<<EOT
id: (required)(String) addon name,
lang: (2 letters lang id) separated by comma,
js:
  (Bool) true | false
    OR
  (String)  separated by comma field names
    OR
  (Obj) {
    status: (Bool) true | false,
    fields: (String) separated by comma field names
  }
EOT;
        $return = self::isConsole()
        ? "
usage: php create_addon.php [--help] [--wibug] [-a|--addon='first_addon'] [-p|--path='']

Options:
            --help          Show this message
            --wibug         Display PHP notice
        -a  --addon         Add-on ID
        -p  --path          In progress
            --lang          Languages separated by comma
"
        : "
Request params:
    help          Show this message
    wibug         Display PHP notice
    addon         Add-on ID
    path          In progress
    lang          Languages separated by comma
";
    return $return . $jsonFormat;
    }
}

/**
*
*/
class Addon
{
    static public $scriptParam = [];
    private $data = [];
    private $createdFields = [];
    private $defaultData = [
      'js' => 'func.js',
      'lang' => 'en'
    ];
    public $m; // messages

    public function __construct()
    {
      $script_params = self::$scriptParam->getall();
      $this->parseParams($script_params);
      $this->m = new DisplayMessage();
    }

    private function parseParams($script_params)
    {
      $data = [];

      //  get from json
      if (isset($script_params['path'])
        && $script_params['path']
      ) {
        // code...
      } else {
        $data['id'] = $script_params['addon'];

        // [lang]
        $data['lang'] = isset($script_params['lang'])
          ? explode(',', $script_params['lang'])
          : [$this->defaultData['lang']];
        // [/lang]

        // [js]
        // FIXME: add typeof for from json
        $data['js']['status'] = isset($script_params['js'])
          ? true
          : false;

        if ($data['js']['status']) {
          $data['js']['fields'] = $script_params['js']
            ? explode(',', $script_params['js'])
            : [$this->defaultData['js']];
          }
        // /[js]
      }

      $this->data = $data;
    }

    public function createFields()
    {
      $data = $this->data;

      $this->createAddonXml();
      $this->createLangs();



      if ($data['js']['status']) {
        //  REMEMBER! js + themes
        // $this->createJs();
      }

      return $this->getCreatedFields();
    }

    private function addCreatedFields($value)
    {
      $this->createdFields[] = $value;
    }

    private function getCreatedFields()
    {
      return $this->createdFields;
    }

    private function createFile($name, $template, $dir)
    {
      if (!is_dir($dir)) {
        fn_mkdir($dir);
      }

      @touch($dir . $name);

      $result = fn_put_contents($name, $template, $dir);

      if (!$result) {
        $this->m->push('Can not create file: ' . $dir . $name);
      }
    }

    private function createAddonXml()
    {
      $id = $this->data['id'];
      $dir = Registry::get('config.dir.addons') . $id . '/';
      $name = 'addon.xml';
      $template = FileTemplate::getTemplate($name);
      $template = str_replace(
        [
          '%addon_id%',
          '%default_language%'
        ],
        [
          $id,
          $this->defaultData['lang']
        ],
        $template['main']
      );

      $this->createFile($name, $template, $dir);

      $this->addCreatedFields($dir . $name);
    }

    private function createLangs()
    {
      $id = $this->data['id'];
      $name = $id . '.po';
      $templates = FileTemplate::getTemplate('lang');
      $addon_name = ucfirst(str_replace('_', ' ', $id));
      $addon_description = '';

      foreach ($this->data['lang'] as $lang) {
        // WIFIXME: if string (ex. ru) then name = id with replace _ -> ' '
        //          if array => replace with data 
        $dir = Registry::get('config.dir.lang_packs') . $lang . '/' . 'addons' . '/';
        $template = str_replace(
          [
            '%addon_id%',
            '%addon_name%',
            '%addon_description%'
          ], 
          [
            $id,
            $addon_name,
            $addon_description
          ], 
          $templates[$lang]
        );


        $this->createFile($name, $template, $dir);
        $this->addCreatedFields($dir . $name);
      }

    }
}

$m = new DisplayMessage();
Addon::$scriptParam = new ScriptParam();

//  dispaly help
if (Addon::$scriptParam->isset('help')) {
    fn_print_r(ScriptParam::getHelp());
    exit();
}

//  enable error reporting
if (Addon::$scriptParam->isset('wibug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
    ini_set('display_startup_errors', true);

    if (!defined('DEVELOPMENT')) {
        define('DEVELOPMENT', true);
    }
}

//  check required fields
if (!Addon::$scriptParam->get('addon')
  && !Addon::$scriptParam->get('path')
) {
  $m->push('addon or path params required');
  $m->end();
}

try {
  $addon = new Addon();

  $m->push('Start', false);
  $m->hr();

  $createdFileds = $addon->createFields();
  $m->push('Created fields:', false);
  foreach ($createdFileds as $field) {
    $m->push($field);
  }

} catch( GetAddonException $e ) {
    $m->push($e);
}

$m->hr();
$m->end();
