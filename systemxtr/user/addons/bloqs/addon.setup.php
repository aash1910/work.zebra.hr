<?php
// Build: 138f5200
/**
 * @package     ExpressionEngine
 * @category    Bloqs
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2012, 2024 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/bloqs
 * @license
 *
 * Copyright (c) 2019. BoldMinded, LLC
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

use BoldMinded\Bloqs\Controller\HookExecutor;
use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Library\Basee\Cache;
use BoldMinded\Bloqs\Library\Basee\Setting;
use BoldMinded\Bloqs\Library\Basee\Trial;
use ExpressionEngine\Core\Provider;

if (!class_exists('Bloqs_base')) {
    require_once(PATH_THIRD . 'bloqs/base.bloqs.php');
}

if (!defined('BLOQS_VERSION')) {
    define('BLOQS_VERSION', '5.2.2');
    define('BLOQS_BUILD_VERSION', '138f5200');
    define('BLOQS_TRIAL', file_exists(PATH_THIRD . 'bloqs/Config/trial'));
    define('BLOQS_NAME', 'Bloqs' . (BLOQS_TRIAL ? ' (Free Trial)' : ''));
    define('BLOQS_NAME_SHORT', 'Bloqs');
}

// < 7.3 Support
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return null;
    }
}

return [
    'author'      => 'BoldMinded',
    'author_url'  => 'https://boldminded.com',
    'docs_url'    => 'https://boldminded.com/add-ons/bloqs',
    'name'        => BLOQS_NAME,
    'description' => 'A modular content add-on for ExpressionEngine',
    'version'     => BLOQS_VERSION,
    'namespace'   => 'BoldMinded\Bloqs',
    'settings_exist' => true,

    'requires' => [
        'php'   => '8.2',
        'ee'    => '6.4.5'
    ],

    'coilpack' => [
        'fieldtypes' => [
            'bloqs' => 'BoldMinded\Bloqs\Tags\Replace',
        ]
    ],

    'fieldtypes' => [
        'bloqs' => [
            'name' => 'Bloqs',
            'templateGenerator' => 'Bloqs'
        ]
    ],

    'services.singletons' => [
        'Cache' => function () {
            $cache = new Cache('bloqs', 0);

            if (ee()->config->item('bloqs_cache_scope') === 'url') {
                $cache->setScope(implode('/', ee()->uri->rsegments));
            }

            return $cache;
        },
        'FormSecret' => function ($provider) {
            /** @var Provider $provider */
            $hookExecutor = new HookExecutor(ee());
            return new BoldMinded\Bloqs\Service\FormSecret($hookExecutor);
        },
        'Setting' => function () {
            return new Setting('blocks_settings');
        },
        'Trial' => function ($provider) {
            /** @var Provider $provider */
            $setting = $provider->make('Setting');
            /** @var Trial $trialService */
            $trialService = new Trial();
            $trialService
                ->setTrialEnabled(BLOQS_TRIAL)
                ->setInstalledDate($setting->get('installed_date'))
                ->setMessageTitle('Your free trial of Bloqs has expired and is disabled.')
                ->setMessageBody('Please go to the <a href="https://boldminded.com">https://boldminded.com</a> to purchase the full version.');

            return $trialService;
        },
    ],
    'services' => [
        /**
         * @usage ee('bloqs:Adapter')->getBlocks($entryId, $fieldId);
         */
        'Adapter' => function () {
            return new Adapter(ee());
        },
        /**
         * @usage ee('bloqs:getContent', $entryId, $fieldId);
         */
        'getContent' => function ($provider, $entryId, $fieldId) {
            return (new Adapter(ee()))->getContent($entryId, $fieldId);
        }
    ],
];
