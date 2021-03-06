<?php

/**
 * moziloCMS Plugin: TabMenu
 *
 * The TabMenu Plugin creates a tab navigation from subcategories and their pages of
 * the current category. Switch between tabs doesn't require page reload.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   DEVMOUNT <mail@devmount.de>
 * @license  GPL v3+
 * @version  GIT: v0.0.2014-04-11
 * @link     https://github.com/devmount-mozilo/TabMenu/wiki/Dokumentation
 * @see      But the fruit of the Spirit is love, joy, peace, patience, kindness,
 *           goodness, faithfulness...
 *           – The Bible
 *
 * Plugin created by DEVMOUNT
 * www.devmount.de
 *
 */

// only allow moziloCMS environment
if (!defined('IS_CMS')) {
    die();
}

/**
 * TabMenu Class
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   DEVMOUNT <mail@devmount.de>
 * @license  GPL v3+
 * @link     https://github.com/devmount-mozilo/TabMenu
 */
class TabMenu extends Plugin
{
    // language
    private $_admin_lang;
    private $_cms_lang;

    // plugin information
    const PLUGIN_AUTHOR  = 'DEVMOUNT';
    const PLUGIN_TITLE   = 'TabMenu';
    const PLUGIN_VERSION = 'v0.0.2014-04-11';
    const MOZILO_VERSION = '2.0';
    const PLUGIN_DOCU
        = 'https://github.com/devmount-mozilo/TabMenu/wiki/Dokumentation';

    private $_plugin_tags = array(
        'tag1' => '{TabMenu}',
    );

    const LOGO_URL = 'http://media.devmount.de/logo_pluginconf.png';

    /**
     * set configuration elements, their default values and their configuration
     * parameters
     *
     * @var array $_confdefault
     *      text     => default, type, maxlength, size, regex
     *      textarea => default, type, cols, rows, regex
     *      password => default, type, maxlength, size, regex, saveasmd5
     *      check    => default, type
     *      radio    => default, type, descriptions
     *      select   => default, type, descriptions, multiselect
     */
    // private $_confdefault = array(
    //     'text' => array(
    //         'string',
    //         'text',
    //         '100',
    //         '5',
    //         "/^[0-9]{1,3}$/",
    //     ),
    //     'textarea' => array(
    //         'string',
    //         'textarea',
    //         '10',
    //         '10',
    //         "/^[a-zA-Z0-9]{1,10}$/",
    //     ),
    //     'password' => array(
    //         'string',
    //         'password',
    //         '100',
    //         '5',
    //         "/^[a-zA-Z0-9]{8,20}$/",
    //         true,
    //     ),
    //     'check' => array(
    //         true,
    //         'check',
    //     ),
    //     'radio' => array(
    //         'red',
    //         'radio',
    //         array('red', 'green', 'blue'),
    //     ),
    //     'select' => array(
    //         'bike',
    //         'select',
    //         array('car','bike','plane'),
    //         false,
    //     ),
    // );

    /**
     * creates plugin content
     *
     * @param string $value Parameter divided by '|'
     *
     * @return string HTML output
     */
    function getContent($value)
    {
        global $CMS_CONF;
        global $syntax;
        global $CatPage;

        $this->_cms_lang = new Language(
            $this->PLUGIN_SELF_DIR
            . 'lang/cms_language_'
            . $CMS_CONF->get('cmslanguage')
            . '.txt'
        );

        // get language labels
        // $label = $this->_cms_lang->getLanguageValue('label');

        // get params
        // list($param_, $param_, $param_)
            // = $this->makeUserParaArray($value, false, '|');

        // get conf and set default
        // $conf = array();
        // foreach ($this->_confdefault as $elem => $default) {
        //     $conf[$elem] = ($this->settings->get($elem) == '')
        //         ? $default[0]
        //         : $this->settings->get($elem);
        // }

        // get pages AND subcategories of current cat
        $pagearray = $CatPage->get_PageArray(
            CAT_REQUEST,
            array(EXT_PAGE, EXT_HIDDEN)
        );
        // real cat name without '/'
        $cat = substr(CAT_REQUEST, strpos(CAT_REQUEST, '%2F')+3);

        // remove page name = current category name
        for ($i=0; $i < count($pagearray); $i++) {
            if ($cat == $pagearray[$i]) {
                unset($pagearray[$i]);
            }
        }
        $pagearray = array_values($pagearray);

        // get all categories
        $catarray = $CatPage->get_CatArray(true, false, false);

        // get all subcategories
        $currentcatarray = array_intersect($pagearray, $catarray);

        // handle no subcategories
        if (count($currentcatarray) == 0) {
            return $this->throwMessage(
                $this->_cms_lang->getLanguageValue(
                    'notice_no_subcategories',
                    urldecode(CAT_REQUEST)
                ),
                'NOTICE'
            );
        }

        // include jquery and TabMenu javascript
        $syntax->insert_jquery_in_head('jquery');
        $syntax->insert_in_head(
            '<script
                type="text/javascript"
                src="//code.jquery.com/ui/1.10.4/jquery-ui.js"
            ></script>'
        );
        $syntax->insert_in_head(
            '<script>
                $(function() {
                    $( "#tabs" ).tabs();
                });
            </script>'
        );

        // initialize return content, begin plugin content
        $content = '<!-- BEGIN ' . self::PLUGIN_TITLE . ' plugin content --> ';

        // build content
        $content .= '<div class="tabmenu-tab">';
        $content .= '<div id="tabs"><ul>';

        // build tabs
        foreach ($currentcatarray as $key => $cat) {
            $catname = substr($cat, strripos($cat, '%2F')+3);
            $content .=
                '<li>
                    <a href="#tabs-' . $key . '">' . urldecode($catname) . '</a>
                </li>';
        }
        $content .= '</ul>';

        // build tab content for each subcategory
        foreach ($currentcatarray as $key => $cat) {
            $catname = substr($cat, strripos($cat, '%2F')+3);
            $content .= '<div id="tabs-' . $key . '">';
            // get pages from current subcategory
            $currentpagearray = $CatPage->get_PageArray(
                $cat,
                array(EXT_PAGE)
            );
            // remove page name = current subcategory name
            $currentpagearray = array_diff($currentpagearray, array($catname));

            // handle no pages in category
            if (count($currentpagearray) == 0) {
                $content .=
                    '<span>'
                    . $this->_cms_lang->getLanguageValue(
                        'notice_no_subcategory_pages',
                        urldecode($catname)
                    )
                    . '</span>';
            } else {
                // build link to each page of current subcategory
                foreach ($currentpagearray as $currentpage) {
                    $content .= $CatPage->create_AutoLinkTag(
                        $cat,
                        $currentpage,
                        'tabmenu-pagelink'
                    );
                }
            }
            // add link to go directly to subcategory
            $content .=
                '<a
                    class="tabmenu-catlink"
                    href="' . $CatPage->get_Href($cat, false) . '"
                >'
                . $this->_cms_lang->getLanguageValue('goto_overview')
                . '</a>';
            $content .= '</div>';
        }
        $content .= '<br style="clear:both;" />
                </div>
            </div>';

        // end plugin content
        $content .= '<!-- END ' . self::PLUGIN_TITLE . ' plugin content --> ';

        return $content;
    }

    /**
     * sets backend configuration elements and template
     *
     * @return Array configuration
     */
    function getConfig()
    {
        $config = array();

        // read configuration values
        // foreach ($this->_confdefault as $key => $value) {
        //     // handle each form type
        //     switch ($value[1]) {
        //     case 'text':
        //         $config[$key] = $this->confText(
        //             $this->_admin_lang->getLanguageValue('config_' . $key),
        //             $value[2],
        //             $value[3],
        //             $value[4],
        //             $this->_admin_lang->getLanguageValue(
        //                 'config_' . $key . '_error'
        //             )
        //         );
        //         break;

        //     case 'textarea':
        //         $config[$key] = $this->confTextarea(
        //             $this->_admin_lang->getLanguageValue('config_' . $key),
        //             $value[2],
        //             $value[3],
        //             $value[4],
        //             $this->_admin_lang->getLanguageValue(
        //                 'config_' . $key . '_error'
        //             )
        //         );
        //         break;

        //     case 'password':
        //         $config[$key] = $this->confPassword(
        //             $this->_admin_lang->getLanguageValue('config_' . $key),
        //             $value[2],
        //             $value[3],
        //             $value[4],
        //             $this->_admin_lang->getLanguageValue(
        //                 'config_' . $key . '_error'
        //             ),
        //             $value[5]
        //         );
        //         break;

        //     case 'check':
        //         $config[$key] = $this->confCheck(
        //             $this->_admin_lang->getLanguageValue('config_' . $key)
        //         );
        //         break;

        //     case 'radio':
        //         $descriptions = array();
        //         foreach ($value[2] as $label) {
        //             $descriptions[$label] = $this->_admin_lang->getLanguageValue(
        //                 'config_' . $key . '_' . $label
        //             );
        //         }
        //         $config[$key] = $this->confRadio(
        //             $this->_admin_lang->getLanguageValue('config_' . $key),
        //             $descriptions
        //         );
        //         break;

        //     case 'select':
        //         $descriptions = array();
        //         foreach ($value[2] as $label) {
        //             $descriptions[$label] = $this->_admin_lang->getLanguageValue(
        //                 'config_' . $key . '_' . $label
        //             );
        //         }
        //         $config[$key] = $this->confSelect(
        //             $this->_admin_lang->getLanguageValue('config_' . $key),
        //             $descriptions,
        //             $value[3]
        //         );
        //         break;

        //     default:
        //         break;
        //     }
        // }

        // read admin.css
        // $admin_css = '';
        // $lines = file('../plugins/' . self::PLUGIN_TITLE. '/admin.css');
        // foreach ($lines as $line_num => $line) {
        //     $admin_css .= trim($line);
        // }

        // // add template CSS
        // $template = '<style>' . $admin_css . '</style>';

        // // build Template
        // $template .= '
        //     <div class="tabmenu-admin-header">
        //     <span>'
        //         . $this->_admin_lang->getLanguageValue(
        //             'admin_header',
        //             self::PLUGIN_TITLE
        //         )
        //     . '</span>
        //     <a href="' . self::PLUGIN_DOCU . '" target="_blank">
        //     <img style="float:right;" src="' . self::LOGO_URL . '" />
        //     </a>
        //     </div>
        // </li>
        // <li class="mo-in-ul-li ui-widget-content tabmenu-admin-li">
        //     <div class="tabmenu-admin-subheader">'
        //     . $this->_admin_lang->getLanguageValue('admin_test')
        //     . '</div>
        //     <div style="margin-bottom:5px;">
        //         <div class="tabmenu-single-conf">
        //             {test1_text}
        //         </div>
        //         {test1_description}
        //         <span class="tabmenu-admin-default">
        //             [' . /*$this->_confdefault['test1'][0] .*/']
        //         </span>
        //     </div>
        //     <div style="margin-bottom:5px;">
        //         <div class="tabmenu-single-conf">
        //             {test2_text}
        //         </div>
        //         {test2_description}
        //         <span class="tabmenu-admin-default">
        //             [' . /*$this->_confdefault['test2'][0] .*/']
        //         </span>
        // ';

        // $config['--template~~'] = $template;

        return $config;
    }

    /**
     * sets default backend configuration elements, if no plugin.conf.php is
     * created yet
     *
     * @return Array configuration
     */
    function getDefaultSettings()
    {
        $config = array('active' => 'true');
        foreach ($this->_confdefault as $elem => $default) {
            $config[$elem] = $default[0];
        }
        return $config;
    }

    /**
     * sets backend plugin information
     *
     * @return Array information
     */
    function getInfo()
    {
        global $ADMIN_CONF;

        $this->_admin_lang = new Language(
            $this->PLUGIN_SELF_DIR
            . 'lang/admin_language_'
            . $ADMIN_CONF->get('language')
            . '.txt'
        );

        // build plugin tags
        $tags = array();
        foreach ($this->_plugin_tags as $key => $tag) {
            $tags[$tag] = $this->_admin_lang->getLanguageValue('tag_' . $key);
        }

        $info = array(
            '<b>' . self::PLUGIN_TITLE . '</b> ' . self::PLUGIN_VERSION,
            self::MOZILO_VERSION,
            $this->_admin_lang->getLanguageValue(
                'description',
                htmlspecialchars($this->_plugin_tags['tag1'], ENT_COMPAT, 'UTF-8')
            ),
            self::PLUGIN_AUTHOR,
            array(
                self::PLUGIN_DOCU,
                self::PLUGIN_TITLE . ' '
                . $this->_admin_lang->getLanguageValue('on_devmount')
            ),
            $tags
        );

        return $info;
    }

    /**
     * creates configuration for text fields
     *
     * @param string $description Label
     * @param string $maxlength   Maximum number of characters
     * @param string $size        Size
     * @param string $regex       Regular expression for allowed input
     * @param string $regex_error Wrong input error message
     *
     * @return Array  Configuration
     */
    protected function confText(
        $description,
        $maxlength = '',
        $size = '',
        $regex = '',
        $regex_error = ''
    ) {
        // required properties
        $conftext = array(
            'type' => 'text',
            'description' => $description,
        );
        // optional properties
        if ($maxlength != '') {
            $conftext['maxlength'] = $maxlength;
        }
        if ($size != '') {
            $conftext['size'] = $size;
        }
        if ($regex != '') {
            $conftext['regex'] = $regex;
        }
        if ($regex_error != '') {
            $conftext['regex_error'] = $regex_error;
        }
        return $conftext;
    }

    /**
     * creates configuration for textareas
     *
     * @param string $description Label
     * @param string $cols        Number of columns
     * @param string $rows        Number of rows
     * @param string $regex       Regular expression for allowed input
     * @param string $regex_error Wrong input error message
     *
     * @return Array  Configuration
     */
    protected function confTextarea(
        $description,
        $cols = '',
        $rows = '',
        $regex = '',
        $regex_error = ''
    ) {
        // required properties
        $conftext = array(
            'type' => 'textarea',
            'description' => $description,
        );
        // optional properties
        if ($cols != '') {
            $conftext['cols'] = $cols;
        }
        if ($rows != '') {
            $conftext['rows'] = $rows;
        }
        if ($regex != '') {
            $conftext['regex'] = $regex;
        }
        if ($regex_error != '') {
            $conftext['regex_error'] = $regex_error;
        }
        return $conftext;
    }

    /**
     * creates configuration for password fields
     *
     * @param string  $description Label
     * @param string  $maxlength   Maximum number of characters
     * @param string  $size        Size
     * @param string  $regex       Regular expression for allowed input
     * @param string  $regex_error Wrong input error message
     * @param boolean $saveasmd5   Safe password as md5 (recommended!)
     *
     * @return Array   Configuration
     */
    protected function confPassword(
        $description,
        $maxlength = '',
        $size = '',
        $regex = '',
        $regex_error = '',
        $saveasmd5 = true
    ) {
        // required properties
        $conftext = array(
            'type' => 'text',
            'description' => $description,
        );
        // optional properties
        if ($maxlength != '') {
            $conftext['maxlength'] = $maxlength;
        }
        if ($size != '') {
            $conftext['size'] = $size;
        }
        if ($regex != '') {
            $conftext['regex'] = $regex;
        }
        $conftext['saveasmd5'] = $saveasmd5;
        return $conftext;
    }

    /**
     * creates configuration for checkboxes
     *
     * @param string $description Label
     *
     * @return Array  Configuration
     */
    protected function confCheck($description)
    {
        // required properties
        return array(
            'type' => 'checkbox',
            'description' => $description,
        );
    }

    /**
     * creates configuration for radio buttons
     *
     * @param string $description  Label
     * @param string $descriptions Array Single item labels
     *
     * @return Array Configuration
     */
    protected function confRadio($description, $descriptions)
    {
        // required properties
        return array(
            'type' => 'select',
            'description' => $description,
            'descriptions' => $descriptions,
        );
    }

    /**
     * creates configuration for select fields
     *
     * @param string  $description  Label
     * @param string  $descriptions Array Single item labels
     * @param boolean $multiple     Enable multiple item selection
     *
     * @return Array   Configuration
     */
    protected function confSelect($description, $descriptions, $multiple = false)
    {
        // required properties
        return array(
            'type' => 'select',
            'description' => $description,
            'descriptions' => $descriptions,
            'multiple' => $multiple,
        );
    }

    /**
     * throws styled message
     *
     * @param string $text Content of message
     * @param string $type Type of message ('ERROR', 'SUCCESS')
     *
     * @return string HTML content
     */
    protected function throwMessage($text, $type)
    {
        return '<div class="'
                . strtolower(self::PLUGIN_TITLE . '-' . $type)
            . '">'
            . '<div>'
                . $this->_cms_lang->getLanguageValue(strtolower($type))
            . '</div>'
            . '<span>' . $text. '</span>'
            . '</div>';
    }
}

?>
