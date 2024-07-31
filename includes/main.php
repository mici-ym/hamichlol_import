<?php

namespace MediaWiki\Extension\hamichlol_import;

use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Output\Hook\BeforePageDisplayHook;



class main implements GetPreferencesHook, BeforePageDisplayHook
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
    }
}
