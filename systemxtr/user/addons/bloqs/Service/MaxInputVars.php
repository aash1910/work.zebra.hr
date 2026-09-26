<?php

namespace BoldMinded\Bloqs\Service;

class MaxInputVars
{
    /**
     * It's mentioned in the docs, but show a warning in the CP, so it's more clear.
     * https://boldminded.com/support/ticket/2257
     *
     * Minimum max_input_vars value recommended for Bloqs
     */
    const MIN_INPUT_VARS = 2000;

    public static function checkInline(string $name = 'shared-form')
    {
        $maxInputVars = intval(ini_get('max_input_vars'));

        if ($maxInputVars < self::MIN_INPUT_VARS) {
            ee()->lang->loadfile('bloqs');

            ee('CP/Alert')
                ->makeInline($name)
                ->asWarning()
                ->withTitle(lang('bloqs_max_input_vars'))
                ->addToBody(sprintf(lang('bloqs_max_input_vars_description'), self::MIN_INPUT_VARS))
                ->cannotClose()
                ->now();
        }
    }

    public static function checkBanner()
    {
        $maxInputVars = intval(ini_get('max_input_vars'));

        if ($maxInputVars < self::MIN_INPUT_VARS) {
            ee()->lang->loadfile('bloqs');

            ee('CP/Alert')
                ->makeBanner('bloqs-input-vars')
                ->asWarning()
                ->withTitle(lang('bloqs_max_input_vars'))
                ->addToBody(sprintf(lang('bloqs_max_input_vars_description'), self::MIN_INPUT_VARS))
                ->now();
        }
    }
}
