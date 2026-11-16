<?php

/*
 *                      CMS RuDi v1.10.9
 *                    https://cmsrudi.ru/
 *
 *              Written by CMS RuDi Team, 2026
 *           produced by DagIT, (https://dagit.su)
 *
 *                  LICENSED BY GNU/GPL v2
 */

namespace components\users\backend\actions;

class add extends \cms\com_action {

    public function actUser() {
        return $this->runAction('edit', ['user', '0']);
    }

    public function actGroup() {
        return $this->runAction('edit', ['group', '0']);
    }
}