<?php

function smarty_modifier_lang($name)
{
    return \cms\lang::getInstance()->get($name);
}
