<?php


namespace jojoe77777\SlapperPlus;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;

/**
 * This class exists for backwards compatibility
 * for jojo77777\FormAPI legacy methods
 *
 * Class FormAPI
 * @package jojoe77777\SlapperPlus
 */
class FormAPI {

    public function createCustomForm(?callable $function = null) : CustomForm {
        return new CustomForm($function);
    }

    public function createSimpleForm(?callable $function = null) : SimpleForm {
        return new SimpleForm($function);
    }
}
