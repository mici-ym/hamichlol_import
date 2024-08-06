<?php

namespace MediaWiki\Extension\hamichlol_import;

use Parser;
use WANObjectCache;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\ParserFirstCallInitHook;

class main implements GetPreferencesHook, BeforePageDisplayHook, ParserFirstCallInitHook
{
    private WANObjectCache $cache;
    private $pageCacheKey = null;

    public function __construct() {
        $service = MediaWikiServices::getInstance();
        $this->cache = $service->getMainWANObjectCache();
    }

    public function onGetPreferences($user, &$preferences)
    {
        $options = [
            'get-from-wikipedia' => 0,
            'get-from-proxi' => 1,
        ];
        if ($user->isAllowed('delete') && $user->isAllowed('aspaklarya_lockdown')) {
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
        $output = $parser->getOutput();
        if (!$title) {
            return true;
        }
        $output->setPageProperty('wikipediaName', $title);

        return true;
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
                if ($user->isAllowed('aspaklarya_lockdown')) {
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
    }

    private function createCacheKey($cacheKey) {
        if (!$cacheKey) {
            throw new InvalidArgumentException( 'Title or id is not set' );
        }/*
        if ( $cacheKey>isSpecialPage()) {
            throw new InvalidArgumentException('is a special page' );
        }*/
        $this->pageCacheKey = $this->cache->makeKey('wikipedia_data', $cacheKey);
    }
}
