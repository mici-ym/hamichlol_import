<?php

namespace MediaWiki\Extension\hamichlol_import;

use MediaWiki\MediaWikiServices;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use Wikimedia\ParamValidator\TypeDef\BooleanDef;



class main implements GetPreferencesHook, BeforePageDisplayHook, ParserFirstCallInitHook
{

    public function onGetPreferences($user, &$preferences)
    {
        $options = [
            'wikipedia' => 0,
            'proxi' => 1,
        ];
        if ($user->isAllowed('delete') && $user->isAllowed('aspaklarya')) {
            $options = array_merge($options, ['filter-bypass' => 2]);
        }

        $preferences['path-import'] = [
            'type' => 'radio',
            'label-message' => 'path-of-Filter-bypass',
            'help-message' => 'path-of-Filter-bypass-help',
            'options-messages' => $options,
            'section' => 'importAndUpdate/settings',
        ];
    }


    public function onParserFirstCallInit($parser)
    {
        $parser->setHook('sortwikipedia', [$this, 'renderName']);
        return true;
    }

    private function getParserFunctionArgs(array $args)
    {
        $params = [];
        $args = explode('|', $args[0]);
        foreach ($args as $arg) {
            $pair = explode('=', $arg, 2);
            if (count($pair) == 2) {
                $name = trim($pair[0]);
                $value = trim($pair[1]);
                if (in_array($value, BooleanDef::$TRUEVALS, true)) {
                    $value = true;
                }
                if (in_array($value, BooleanDef::$FALSEVALS, true)) {
                    $value = false;
                }
                if ($value !== '') {
                    $params[$name] = $value;
                }
            } else {
                $params[] = $arg;
            }
        }
        return $params;
    }

    public function renderName($parser)
    {
        $params = $this->getParserFunctionArgs(func_get_args());
        $nameOfWikipedia = $params['name'];
        //$revidOfUpdate = $params['revid'] ?? null;
        $parser->getOutput()->setPageProperty("nameOfWikipedia", $nameOfWikipedia);
        /*
        $parser->getOutput()->appendExtensionData("nameOfWikipedia", $nameOfWikipedia);
        if ($revidOfUpdate) {
            $parser->getOutput()->appendExtensionData("revidOfUpdate", $revidOfUpdate);
        }
            */
        return true;
    }
    public function onBeforePageDisplay($out, $skin): void
    {
        $services = MediaWikiServices::getInstance();
        $user = $out->getUser();
        $pathImport = $services->getUserOptionsLookup()->getOption($user, 'path-import');
        $configImport = [
            'proxi' => 1,
        ];
        switch ($pathImport) {
            case 0:
                $configImport['path'] = 'https://he.wikipedia.org/w/api.php';
                break;
            case 1:
                $configImport['path'] = 'https://import.hamichlol.org.il/';
                break;
            case 2:
                if ($user->isAllowed('aspaklarya')) {
                    $configImport['path'] = '/import.getWiki1/';
                } else {
                    $configImport['path'] = 'https://import.hamichlol.org.il/';
                }
                break;

            default:
                $configImport['path'] = 'https://import.hamichlol.org.il/';
                break;
        }

        $nameOfWikipedia = $out->getProperty('nameOfWikipedia');
        if ($nameOfWikipedia) {
            array_merge($configImport, [$nameOfWikipedia]);
        }
        $out->addJsConfigVars('miconfig', $configImport);
    }
}
