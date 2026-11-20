<?php

namespace components\content\backend\actions;

class add extends \cms\com_action {

    public function actContent($category_id = 0) {
        $this->runAction('edit', ['content', 0, $category_id]);
    }

    public function actCategory() {
        $this->runAction('edit', ['category', 0]);
    }
}