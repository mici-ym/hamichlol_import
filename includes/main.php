<?php

namespace MediaWiki\Extension\hamichlol_import;

use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\OutputPageParserOutputHook;
use Parser;
use MediaWiki\Output\OutputPage;
use ParserOutput;

class main implements GetPreferencesHook, BeforePageDisplayHook, ParserFirstCallInitHook, OutputPageParserOutputHook
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
        $parser->setFunctionHook('importdata', [$this, 'renderImportData']);
    }

    public function renderImportData(Parser $parser, ...$params)
    {
        if (!$params) {
            return '';
        }
        $title = $params[0];
        $output = $parser->getOutput();
        $output->setPageProperty('importName', $title);
        $updatervid = $params[1]?? null;
        if ($updatervid) {
            $output->setExtensionData('importUpdatervid', $updatervid);
        }
        return '';
    }

    /**
     * @param OutputPage $outputPage
     * @param ParserOutput $parserOutput
     */
    public function onOutputPageParserOutput( $outputPage, $parserOutput ): void {
        $importName = $parserOutput->getPageProperty('importName');
        $outputPage->setProperty('importData', $importName);
        $updatervid = $parserOutput->getExtensionData('importUpdatervid');
        if ($updatervid) {
            $outputPage->setProperty('updatervid', $updatervid);
        }
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

        if ($out->getTitle()->isSpecialPage() || !$out->getTitle()->isNewPage()) {
            return;
        }
        $importData = [];
        $importName = $out->getProperty('importData') ?? $skin->getWikiPage()->getParserOutput()->getPageProperty('importName');
        if ($importName) {
           $importData['name'] = $importName;
        }
        $updatervid = $out->getProperty('updatervid');
        if ($updatervid) {
            $importData['updatervid'] = $updatervid;
        }
        $out->addJsConfigVars('importData', $importData);
        
    }
}
