<?php

namespace MediaWiki\Extension\hamichlol_import;

use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use Parser;

class main implements GetPreferencesHook, BeforePageDisplayHook, ParserFirstCallInitHook
{

    public function onGetPreferences($user, &$preferences)
    {
        $options = [
            'get-from-wikipedia' => 0,
            'get-from-proxi' => 1,
        ];
        if ($user->isAllowed('delete') && $user->isAllowed('aspaklarya')) {
            $options['get-from-filter-bypass'] = 2;
        }

        $preferences['path-import'] = [
            'type' => 'radio',
            'label-message' => 'path-of-requests',
            'help-message' => 'path-of-requests-help',
            'options-messages' => $options,
            'section' => 'importAndUpdate/config',
        ];
    }

    public function onParserFirstCallInit($parser)
    {
        $parser->setFunctionHook('sortwikipedia', [$this, 'renderWikipediaData']);
    }

    public function renderWikipediaData(Parser $parser, $title)
    {
        wfDebugLog("hamichlol_import", "Rendering wikipedia data for $title");
        $output = $parser->getOutput();
        $output->setPageProperty('wikipediaName', $title);
        return '';
    }
    public function onBeforePageDisplay($out, $skin): void
    {
        $services = MediaWikiServices::getInstance();
        $user = $out->getUser();
        $pathImport = $services->getUserOptionsLookup()->getOption($user, 'path-import');
        $configImport = [
            'proxi' => $pathImport,
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
                    $configImport['path'] = '/import/get_Wik1i.php';
                } else {
                    $configImport['path'] = 'https://import.hamichlol.org.il/';
                }
                break;

            default:
                $configImport['path'] = 'https://import.hamichlol.org.il/';
                break;
        }

        $out->addJsConfigVars('importConfig', $configImport);
        if (!$out->getTitle()->isSpecialPage()) {
            return;
        }
        wfDebugLog('hamichlol_import', "isSpecialPage: " . $out->getTitle()->isSpecialPage());
        $parserOutput = $skin->getWikiPage()->getParserOutput();
        if ($parserOutput) {
            $wikipediaName = $parserOutput->getPageProperty('wikipediaName');
            if ($wikipediaName) {
                $out->addJsConfigVars('wikipediaName', $wikipediaName);
            }
        }
    }
}
