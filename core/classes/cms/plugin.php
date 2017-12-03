<?php

namespace cms;

class plugin
{

    /**
     * Массив со списком активных плагинов
     *
     * @var array
     */
    private static $plugins = [];

    /**
     * Массив опций плагинов
     *
     * @var array
     */
    private static $configs = [];

    /**
     * @var \cms\db
     */
    protected $db;

    /**
     * @var \cmsCore
     */
    protected $inCore;

    /**
     * @var \cmsPage
     */
    protected $inPage;

    /**
     * @var \cms\lang
     */
    protected $lang;

    /**
     * @var string
     */
    public $lang_prefix;

    /**
     * Название класса плагина
     * @var string
     */
    protected $name;

    /**
     * Версия плагина
     * @var string
     */
    protected $version;

    /**
     * Автор плагина
     * @var string
     */
    protected $author;

    /**
     * Email автора плагина
     * @var string
     */
    protected $author_email;

    /**
     * События на которые будет подписан плагин
     * @var array
     */
    protected $events = [];

    /**
     * Настройки плагина
     * @var array
     */
    protected $config = [];

    /**
     * Настройки плагина по умолчанию
     * @var array
     */
    protected $default_config = [];

    public function __construct()
    {
        $this->inCore = \cmsCore::getInstance();
        $this->db     = \cms\db::getInstance();
        $this->inPage = \cmsPage::getInstance();

        $this->name   = get_called_class();
        $this->config = array_merge($this->default_config, self::loadConfig($this->name));

        $this->setLangPrefix();

        $this->lang = \cms\lang::loadPluginLang(get_called_class());
    }

    private function setLangPrefix()
    {
        $parts = explode('_', $this->name);

        foreach ( $parts as $part ) {
            $this->lang_prefix .= $part{0};
        }

        if ( mb_strlen($this->lang_prefix) == 1 ) {
            $this->lang_prefix .= $part{1} . $part{2};
        }
    }

    /**
     * Возвращает название класса плагина
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Возвращает версию плагина
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Возвращает имя автора плагина
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Возвращает email адресс авора
     *
     * @return string
     */
    public function getAuthorEmail()
    {
        return $this->author_email;
    }

    /**
     * Возвращает название плагина
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->lang->get($this->lang_prefix . '_title');
    }

    /**
     * Возвращае описание плагина
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->lang->get($this->lang_prefix . '_description');
    }

    /**
     * Возвращает информацию о плагине
     *
     * @return array
     */
    public function getInfo()
    {
        return [
            'plugin'       => $this->getName(),
            'title'        => $this->getTitle(),
            'description'  => $this->getDescription(),
            'version'      => $this->getVersion(),
            'author'       => $this->getAuthor(),
            'author_email' => $this->getAuthorEmail(),
        ];
    }

    /**
     * Усановка плагина
     *
     * @return boolean
     */
    public function install()
    {
        $info = $this->getInfo();

        $info['config'] = \cms\model::arrayToYaml($this->config);

        if ( !$info['config'] ) {
            $info['config'] = '';
        }

        // добавляем плагин в базу
        $plugin_id = $this->db->insert('plugins', $info);

        // возвращаем ложь, если плагин не установился
        if ( !$plugin_id ) {
            return false;
        }

        // добавляем хуки событий для плагина
        foreach ( $this->events as $event ) {
            $this->db->insert('event_hooks', array( 'event' => $event, 'plugin_id' => $plugin_id ));
        }

        // возращаем ID установленного плагина
        return $plugin_id;
    }

    /**
     * Обновление плагина
     *
     * @return boolean
     */
    public function upgrade()
    {
        // находим ID установленной версии
        $plugin_id = $this->db->getField('plugins', "plugin='" . $this->name . "'");

        // если плагин еще не был установлен, выходим
        if ( !$plugin_id ) {
            return false;
        }

        // загружаем текущие настройки плагина
        $old_config = self::getConfig($this->name);

        // удаляем настройки, которые больше не нужны
        foreach ( $old_config as $param => $value ) {
            if ( !isset($this->default_config[$param]) ) {
                unset($old_config[$param]);
            }
        }

        $config = array_merge($this->default_config, $old_config);

        unset($old_config);

        $info = $this->getInfo();

        // конвертируем массив настроек в YAML
        $info['config'] = \cms\model::arrayToYaml($config);

        // обновляем плагин в базе
        $this->db->update('plugins', 'id=' . $plugin_id, $info);

        // Удаляем все события плагина
        $this->db->delete('event_hooks', 'plugin_id=' . $plugin_id);

        // добавляем хуки событий для плагина
        foreach ( $this->events as $event ) {
            $this->db->insert('event_hooks', array( 'event' => $event, 'plugin_id' => $plugin_id ));
        }

        // плагин успешно обновлен
        return true;
    }

    /**
     * Возвращает настройки плагина
     *
     * @param type $name
     *
     * @return type
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Выставляет настройки плагина
     *
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Сохроняет настройки плагина
     *
     * @return boolean
     */
    public function saveConfig()
    {
        // конвертируем массив настроек в YAML
        $config_yaml = $this->db->escape(\cms\model::arrayToYaml($this->config));

        // обновляем плагин в базе
        $this->db->query("UPDATE {#}plugins SET config='" . $config_yaml . "' WHERE plugin = '" . $this->name . "'");

        return true;
    }

    //========================================================================//
    // Теперь об именовании методов для выполнения их при определенных событиях
    // Например событие contet.add_item - добавление материала в каталог статей
    // название метода для обработки этого события должен иметь название
    // contentAddItem тоесть первая буква стоящая после разделителя (точка или знак
    // подчеркивания) переводится в верхний регистр а все разделители убираются
    // из названия события. Принимать метод будет один единственный параметр $data
    // содержание которого будет зависить от события
    //
    // public function contentAddItem($item)
    // {
    //    return $item;
    // }
    //
    // в конце метод должен вернуть обработанные данные
    //========================================================================//

    public function execute($event, $data = [])
    {
        $method_name = \cms\helper\str::toCamel('_', str_replace('.', '_', $event));

        if ( method_exists($this, $method_name) ) {
            return $this->{$method_name}($data);
        }

        return $data;
    }

    //========================================================================//

    /**
     * Возвращает конфигурацию плагина
     *
     * @param string $name Название плагина
     *
     * @return array
     */
    final public static function loadConfig($name)
    {
        if ( !isset(self::$configs[$name]) ) {
            self::loadEnabledPlugins();

            if ( isset(self::$plugins[$name]) ) {
                self::$configs[$name] = self::$plugins[$name]['config'];
            }
            else {
                $config = db::getInstance()->getField('plugins', "plugin='" . $name . "'", 'config');

                if ( !empty($config) ) {
                    self::$configs[$name] = model::yamlToArray($config);
                }
            }

            if ( empty(self::$configs[$name]) ) {
                self::$configs[$name] = [];
            }
        }

        return self::$configs[$name];
    }

    /**
     * Загружает плагин и возвращает его объект
     *
     * @param string $plugin Название плагина
     *
     * @return self
     */
    final public static function load($plugin)
    {
        if ( !class_exists($plugin) ) {
            return false;
        }

        return new $plugin();
    }

    /**
     * Возвращает массив активных плагинов
     *
     * @return array
     */
    final public static function getEnabledPlugins()
    {
        self::loadEnabledPlugins();

        return self::$plugins;
    }

    /**
     * Загружает список активных плагинов
     */
    final private static function loadEnabledPlugins()
    {
        if ( empty(self::$plugins) ) {
            self::$plugins = (new model())->filterEqual('published', 1)->useCache('plugins')->get('plugins', function ($item, $model) {
                $item['config'] = model::yamlToArray($item['config']);
                return $item;
            }, 'plugin');
        }
    }

}
