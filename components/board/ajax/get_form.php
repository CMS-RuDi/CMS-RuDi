<?php

/*
 *                           InstantCMS v1.10.6
 *                        http://www.instantcms.ru/
 *
 *                   written by InstantCMS Team, 2007-2015
 *                produced by InstantSoft, (www.instantsoft.ru)
 *
 *                        LICENSED BY GNU/GPL v2
 */

define('PATH', __DIR__ . '/../../..');

include(PATH . '/core/ajax/ajax_core.php');

$model = new cms_model_board();

$cat_id = cmsCore::request('value', 'int', 0);

$cat = $model->getCategory($cat_id);

if ( !$cat ) {
    echo 1;
    exit;
}

if ( !$model->checkLoadedByUser24h($cat) ) {
    echo 1;
    exit;
}

if ( !$model->checkAdd($cat) ) {
    echo 1;
    exit;
}

$forms = cmsForm::getFieldsHtml($cat['form_id']);

if ( !$forms ) {
    echo 1;
    exit;
}

$html = '';

foreach ( $forms as $form ) {
    $html .= '<tr class="cat_form">
            <td valign="top">
                    <span>' . $form['title'] . ':</span>';
    if ( $form['description'] ) {
        $html .= '<div style="color:gray">' . $form['description'] . '</div>';
    }

    $html .= '</td>
            <td valign="top">
                    ' . $form['field'] . '
            </td>
    </tr>';
}

echo $html;
