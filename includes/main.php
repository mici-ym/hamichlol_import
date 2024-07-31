<?php

namespace MediaWiki\Extension\hamichlol_import;

use MediaWiki;
use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use Wikimedia\ParamValidator\TypeDef\BooleanDef;
use MediaWiki\MediaWikiServices;



class main implements BeforePageDisplayHook, GetPreferencesHook, OutputPageParserOutputHook, ParserFirstCallInitHook
{
    public function onBeforePageDisplay($out, $skin): void
    {
    }
    public function onGetPreferences($user, &$preferences)
    {
        $preferences['proxi'] = [
            'type' => 'check',
            'label-message' => 'path-of-wikipedia',
            'help-message' => 'path-of-wikipedia-help',
            'section' => 'importOfUpdate/settings'
        ];
        if ($user->isAllowed('delete')) {
            $preferences['filter-bypass'] = [
                'type' => 'check',
                'label-message' => 'path-of-Filter-bypass',
                'help-message' => 'path-of-Filter-bypass-help',
                'section' => 'importOfUpdate/settings',
                'disable-if' => ['===', 'proxi', '']
            ];
        };
    }

    public function onOutputPageParserOutput($outputPage, $parserOutput): void
    {
        $services = MediaWikiServices::getInstance();
        $user = $outputPage->getUser();
        $proxi = $services->getUserOptionsLookup()->getBoolOption($user, 'proxi');
        $oonfigImport = [
            'proxi' => $proxi
        ];
        if (!$proxi) {
            $configImport['pathOfWikipedia'] = 'https://he.wikipedia.org/w/php.api';
        } else {
            $fiterBypass = $services->getUserOptionsLookup()->getBoolOption($user, 'filter-bypass');
            if ($user->isAllowed('delete') && $fiterBypass) {
                $parserOutput->addJsConfigVars([
                    'configImport' => [
                        'pathOfWikipedia' => "/import.getWiki1/",
                        'proxi' => 1
                    ]
                ]);
            } else {
                $parserOutput->addJsConfigVars([
                    'configImport' => [
                        'pathOfWikipedia' => "https://import.hamichlol.org.il/",
                        'proxi' => 1
                    ]
                ]);
            }
        }
        $wikipediaName = $parserOutput->getExtensionData("nameOfWikipedia");
        $revidOfUpdate = $parserOutput->getExtensionData("revidOfUpdate");
        if ($wikipediaName) {
            $parserOutput->setJsConfigVar();
        }
    }

    public function onParserFirstCallInit(&$parser)
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

    private function renderName($parser)
    {
        $params = $this->getParserFunctionArgs(func_get_args());
        $nameOfWikipedia = $params['name'];
        $revidOfUpdate = $params['revid'] ?? null;
        $parser->getOutput()->setPageProperty("nameOfWikipedia", $nameOfWikipedia);
        $parser->getOutput()->appendExtensionData("nameOfWikipedia", $nameOfWikipedia);
        if ($revidOfUpdate) {
            $parser->getOutput()->appendExtensionData("revidOfUpdate", $revidOfUpdate);
        }
        return true;
    }
}
