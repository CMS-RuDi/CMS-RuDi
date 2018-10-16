jQuery(function () {

// ========================================================================== //
//перетаскиваемые элементы

    jQuery(".uidrag").draggable({});
//умолчания см. http://api.jqueryui.com/draggable/

// ========================================================================== //
//элементы контейнеры для перетаскиваемых элементов

//jQuery( ".uidrop" ).droppable({
//умолчания см. http://api.jqueryui.com/droppable/

// ========================================================================== //
//авторесайз элементы

//jQuery( ".autores" ).resizable({});
//умолчания см. http://api.jqueryui.com/resizable/

// ========================================================================== //
//выборки

//jQuery( ".uiselect" ).selectable({
//умолчания см. http://api.jqueryui.com/selectable/

// ========================================================================== //
//сортируемые элементы

//jQuery(".uisort").sortable();
//умолчания см. http://api.jqueryui.com/sortable/

//jQuery( ".uisort" ).disableSelection();

//ВИДЖЕТЫ:
// ========================================================================== //
//Аккордеон

    jQuery(".uiacc").accordion({});
//умолчания см. http://api.jqueryui.com/accordion/

// ========================================================================== //
//Автоподстановка значений

    jQuery('.uiautocomplete').each(function (i, e) {
        jQuery(e).autocomplete({source: jQuery(e).data('source'), select: function (event, ui) {
                if (jQuery(event.target).data('select-fn')) {
                    return window[jQuery(event.target).data('select-fn')](jQuery(event.target), ui.item.value);
                }
            }});
    });
//умолчания см. http://api.jqueryui.com/autocomplete/

// ========================================================================== //
//Кнопки, тулбары и т.п.

    jQuery(".uibtn").button({});
//умолчания см. http://api.jqueryui.com/button/

//доступны те же опции
    jQuery(".uibtnset").buttonset({});

// ========================================================================== //
//Установка дат

    jQuery("#pubdate, #enddate, #answerdate").datepicker({
//умолчания см. http://api.jqueryui.com/datepicker/

//altField: "",
//altFormat: "",
//appendText: "",
        autoSize: true,
        buttonImage: "/images/icons/date.gif",
        buttonImageOnly: true,
//buttonText: "...",
        calculateWeek: jQuery.datepicker.iso8601Week,
        changeMonth: true,
        changeYear: true,
//closeText: 'Закрыть',
//prevText: '&#x3C;Пред',
//nextText: 'След&#x3E;',
//currentText: 'Сегодня',
//monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
//monthNamesShort: ['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'],
//dayNames: ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'],
//dayNamesShort: ['вск','пнд','втр','срд','чтв','птн','сбт'],
//dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
//weekHeader: 'Нед',
        dateFormat: 'dd.mm.yy',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: '',
        constrainInput: false,
//defaultDate: null,
//duration: "normal",
        gotoCurrent: false,
//hideIfNoPrevNext: false,
        maxDate: "+5y",
        minDate: "-5y",
//navigationAsDateFormat: false,
//numberOfMonths: 1,
        selectOtherMonths: false,
//shortYearCutoff: "+10",
//showAnim: "show",
        showButtonPanel: true,
//showCurrentAtPos: 0,
//showMonthAfterYear: false,
        showOn: "both",
//showOptions: {},
        showOtherMonths: true,
//showWeek: false,
//stepMonths: 1,
//yearRange: "c-10:c+10",
//beforeShow: null,
//beforeShowDay: null,
//onChangeMonthYear: null,
//onClose: null,
//onSelect: null

//no events

    });
//jQuery( "#enddate" ).datepicker("option", "dateFormat", "yy-mm-dd");
// ========================================================================== //
//Диалоги, модалки

    jQuery(".uidialog").dialog({});
//умолчания см. http://api.jqueryui.com/dialog/

// ========================================================================== //
//Меню

    jQuery(".uimenu").menu({});
//умолчания см. http://api.jqueryui.com/menu/

// ========================================================================== //
//Прогрессбар

    jQuery(".uipbar").progressbar({});
//умолчания см. http://api.jqueryui.com/progressbar/

// ========================================================================== //
//Слайдеры

    jQuery(".uisl").slider({});
//умолчания см. http://api.jqueryui.com/slider/

// ========================================================================== //
//Спиннеры

    jQuery('.uispin').spinner();
//умолчания см. http://api.jqueryui.com/spinner/

// ========================================================================== //
//Табы

    jQuery(".uitabs").tabs({});
//умолчания см. http://api.jqueryui.com/tabs/

// ========================================================================== //
//Тултипы

    jQuery('.uittip').tooltip({});
//умолчания см. http://api.jqueryui.com/tooltip/


//подхватываем от lightbox
//jQuery( '.lightbox-enabled' ).colorbox({ transition: "none", width: "90%", height: "90%"});

});