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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoProctoring\model;

use oat\oatbox\service\ConfigurableService;

/**
 * Settings for the user interface of the proctoring
 *
 * Class GuiSettings
 * @package oat\taoProctoring\model
 */
class GuiSettingsService extends ConfigurableService
{
    public const SERVICE_ID = 'taoProctoring/GuiSettings';

    /**
     * Refresh button can be configured as available or unavailable
     */
    public const PROCTORING_REFRESH_BUTTON = 'refreshBtn';

    /**
     * Time between auto refresh in milliseconds
     * 0 - don't refresh
     * Note: it is recommended to avoid having the auto-refresh rate less then 30 seconds.
     */
    public const PROCTORING_AUTO_REFRESH = 'autoRefresh';

    /**
     * Allow or not proctor to  pause a delivery
     */
    public const PROCTORING_ALLOW_PAUSE = 'canPause';

    /**
     * Settings: message and icon for different actions will be displayed in dialog (bulkActionPopup)
     * !!!only for one language
     * TODO: add translation for message
     * example:
     * [
     *   'terminate' => [
     *      'message' => 'Some text...',
     *      'icon' => 'danger'
     *   ],
     *   'authorize' => [
     *      'message' => 'Some text...',
     *      'icon' => 'warning'
     *   ],
     * ]
     */
    public const OPTION_DIALOG_SETTINGS = 'dialogSettings';

    public const OPTION_SHOW_COLUMN_FIRST_NAME = 'showColumnFirstName';
    public const OPTION_SHOW_COLUMN_LAST_NAME = 'showColumnLastName';
    public const OPTION_SHOW_COLUMN_AUTHORIZE = 'showColumnAuthorize';
    public const OPTION_SHOW_COLUMN_REMAINING_TIME = 'showColumnRemainingTime';
    public const OPTION_SHOW_COLUMN_EXTENDED_TIME = 'showColumnExtendedTime';
    public const OPTION_SHOW_COLUMN_CONNECTIVITY = 'onlineStatus';

    public const OPTION_SHOW_ACTION_SHOW_HISTORY = 'showActionShowHistory';

    public const OPTION_SET_START_DATA_ONE_DAY = 'setStartDataOneDay';

    /**
     * @return array
     */
    public function asArray()
    {
        return [
            self::PROCTORING_REFRESH_BUTTON => $this->hasOption(self::PROCTORING_REFRESH_BUTTON)
                ? $this->getOption(self::PROCTORING_REFRESH_BUTTON)
                : true,
            self::PROCTORING_AUTO_REFRESH => $this->hasOption(self::PROCTORING_AUTO_REFRESH)
                ? $this->getOption(self::PROCTORING_AUTO_REFRESH)
                : 0,
            self::PROCTORING_ALLOW_PAUSE => $this->hasOption(self::PROCTORING_ALLOW_PAUSE)
                ? $this->getOption(self::PROCTORING_ALLOW_PAUSE)
                : true,
            self::OPTION_DIALOG_SETTINGS => $this->hasOption(self::OPTION_DIALOG_SETTINGS)
                ? $this->getOption(self::OPTION_DIALOG_SETTINGS)
                : [],
            self::OPTION_SHOW_COLUMN_FIRST_NAME => $this->hasOption(self::OPTION_SHOW_COLUMN_FIRST_NAME)
                ? $this->getOption(self::OPTION_SHOW_COLUMN_FIRST_NAME)
                : true,
            self::OPTION_SHOW_COLUMN_LAST_NAME => $this->hasOption(self::OPTION_SHOW_COLUMN_LAST_NAME)
                ? $this->getOption(self::OPTION_SHOW_COLUMN_LAST_NAME)
                : true,
            self::OPTION_SHOW_COLUMN_AUTHORIZE => $this->hasOption(self::OPTION_SHOW_COLUMN_AUTHORIZE)
                ? $this->getOption(self::OPTION_SHOW_COLUMN_AUTHORIZE)
                : true,
            self::OPTION_SHOW_COLUMN_REMAINING_TIME => $this->hasOption(self::OPTION_SHOW_COLUMN_REMAINING_TIME)
                ? $this->getOption(self::OPTION_SHOW_COLUMN_REMAINING_TIME)
                : true,
            self::OPTION_SHOW_COLUMN_EXTENDED_TIME => $this->hasOption(self::OPTION_SHOW_COLUMN_EXTENDED_TIME)
                ? $this->getOption(self::OPTION_SHOW_COLUMN_EXTENDED_TIME)
                : true,
            self::OPTION_SHOW_COLUMN_CONNECTIVITY => $this->hasOption(self::OPTION_SHOW_COLUMN_CONNECTIVITY)
                ? $this->getOption(self::OPTION_SHOW_COLUMN_CONNECTIVITY)
                : false,
            self::OPTION_SHOW_ACTION_SHOW_HISTORY => $this->hasOption(self::OPTION_SHOW_ACTION_SHOW_HISTORY)
                ? $this->getOption(self::OPTION_SHOW_ACTION_SHOW_HISTORY)
                : true,
            self::OPTION_SET_START_DATA_ONE_DAY => $this->hasOption(self::OPTION_SET_START_DATA_ONE_DAY)
                ? $this->getOption(self::OPTION_SET_START_DATA_ONE_DAY)
                : true,
        ];
    }
}
