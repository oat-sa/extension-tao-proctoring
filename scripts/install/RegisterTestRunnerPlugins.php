<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\scripts\install;

use oat\oatbox\extension\InstallAction;
use common_report_Report as Report;
use oat\taoTests\models\runner\plugins\PluginRegistry;
use oat\taoTests\models\runner\plugins\TestPlugin;

/**
 * @author Aleksej Tikhanovich <aleksej@taotesting.com>
 */
class RegisterTestRunnerPlugins extends InstallAction
{
    public static $plugins = [
        'security' => [
            [
                'id' => 'blurPause',
                'name' => 'Blur Pause',
                'module' => 'taoTestRunnerPlugins/runner/plugins/security/blurPause',
                'bundle' => 'taoTestRunnerPlugins/loader/testPlugins.min',
                'description' => 'Pause the test when leaving the test window',
                'category' => 'security',
                'active' => true,
                'tags' => []
            ],
            [
                'id' => 'blurWarning',
                'name' => 'Blur Warning',
                'module' => 'taoTestRunnerPlugins/runner/plugins/security/blurWarning',
                'bundle' => 'taoTestRunnerPlugins/loader/testPlugins.min',
                'description' => 'Warning message when leaving the test window',
                'category' => 'security',
                'active' => true,
                'tags' => ['only_warning']
            ],
            [
                'id' => 'preventScreenshot',
                'name' => 'Prevent Screenshot',
                'module' => 'taoTestRunnerPlugins/runner/plugins/security/preventScreenshot',
                'bundle' => 'taoTestRunnerPlugins/loader/testPlugins.min',
                'description' => 'Prevent screenshot from Cmd+Shift (mac) and PrtScn (win) shortcuts',
                'category' => 'security',
                'active' => true,
                'tags' => []
            ], [
                'id' => 'preventScreenshotWarning',
                'name' => 'Prevent Screenshot',
                'module' => 'taoTestRunnerPlugins/runner/plugins/security/preventScreenshotWarning',
                'bundle' => 'taoTestRunnerPlugins/loader/testPlugins.min',
                'description' => 'Prevent screenshot from Cmd+Shift (mac) and PrtScn (win) shortcuts',
                'category' => 'security',
                'active' => true,
                'tags' => ['only_warning']
            ]
        ]
    ];

    /**
     * Run the install action
     */
    public function __invoke($params)
    {

        $registry = PluginRegistry::getRegistry();
        $count = 0;

        foreach(self::$plugins as $categoryPlugins) {
            foreach($categoryPlugins as $pluginData){
                if( $registry->register(TestPlugin::fromArray($pluginData)) ) {
                    $count++;
                }
            }
        }

        return new Report(Report::TYPE_SUCCESS, $count .  ' plugins registered.');
    }
}
