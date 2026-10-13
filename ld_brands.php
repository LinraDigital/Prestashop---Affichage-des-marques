<?php
/**
 * ld_brands - Linra Digital
 * Affiche les logos des marques (fabricants) PrestaShop actives sur la home.
 * - Visibilité gérable indépendamment du statut "actif" natif PrestaShop
 *   (table ld_brands_visibility : opt-out, tout est visible par défaut).
 * - Nombre maximum affiché configurable ; si plus de marques visibles que
 *   cette limite, une sélection aléatoire différente à chaque chargement.
 * - Défilement horizontal optionnel (boucle infinie en CSS pur, ou simple
 *   bande défilable manuellement si la boucle est désactivée).
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ld_Brands extends Module implements WidgetInterface
{
    const DEFAULT_LIMIT = 6;

    /** @var string */
    private $templateFile;

    public function __construct()
    {
        $this->name = 'ld_brands';
        $this->tab = 'front_office_features';
        $this->version = '2.1.0';
        $this->author = 'Linra Digital';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('LD - Brands');
        $this->description = $this->l('Affiche les logos des marques actives sur la page d\'accueil, avec défilement horizontal optionnel.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:ld_brands/ld_brands.tpl';
    }

    public function install()
    {
        $this->_clearCache('ld_brands.tpl');

        return parent::install()
            && $this->installTables()
            && $this->registerHook('displayHome')
            && $this->registerHook('actionFrontControllerSetMedia')
            && Configuration::updateValue('LD_BRANDS_LIMIT', self::DEFAULT_LIMIT)
            && Configuration::updateValue('LD_BRANDS_LOOP', 1)
            && Configuration::updateValue('LD_BRANDS_SCROLL_ACTIVE', 0);
    }

    public function uninstall()
    {
        Configuration::deleteByName('LD_BRANDS_LIMIT');
        Configuration::deleteByName('LD_BRANDS_LOOP');
        Configuration::deleteByName('LD_BRANDS_SCROLL_ACTIVE');

        return parent::uninstall();
    }

    private function installTables()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ld_brands_visibility` (
            `id_manufacturer` INT UNSIGNED NOT NULL,
            `visible` TINYINT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_manufacturer`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        return (bool) Db::getInstance()->execute($sql);
    }

    /**
     * ------------------------------------------------------------------
     * Back office : Paramètres + tableau des marques
     * ------------------------------------------------------------------
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitLdBrandsSettings')) {
            $output .= $this->saveSettings();
        }

        if (Tools::isSubmit('submitLdBrandsVisibility')) {
            $output .= $this->saveVisibility();
        }

        return $output . $this->renderSettingsPanel() . $this->renderBrandsTable();
    }

    private function getAdminCurrentIndex()
    {
        return $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
    }

    private function saveSettings()
    {
        $limit = (int) Tools::getValue('LD_BRANDS_LIMIT');

        if ($limit < 1) {
            return $this->displayError($this->l('La limite doit être un nombre entier supérieur à 0.'));
        }

        Configuration::updateValue('LD_BRANDS_LIMIT', $limit);
        Configuration::updateValue('LD_BRANDS_LOOP', Tools::getValue('LD_BRANDS_LOOP') ? 1 : 0);
        Configuration::updateValue('LD_BRANDS_SCROLL_ACTIVE', Tools::getValue('LD_BRANDS_SCROLL_ACTIVE') ? 1 : 0);

        $this->_clearCache('ld_brands.tpl');

        return $this->displayConfirmation($this->l('Paramètres mis à jour.'));
    }

    private function saveVisibility()
    {
        $visible_ids = Tools::getValue('ld_brands_visible', []);
        $visible_ids = is_array($visible_ids) ? array_map('intval', $visible_ids) : [];

        $all_manufacturers = Db::getInstance()->executeS(
            'SELECT id_manufacturer FROM `' . _DB_PREFIX_ . 'manufacturer`'
        );

        foreach ($all_manufacturers ?: [] as $row) {
            $id_manufacturer = (int) $row['id_manufacturer'];
            $visible = in_array($id_manufacturer, $visible_ids, true) ? 1 : 0;

            Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'ld_brands_visibility` (id_manufacturer, visible)
                 VALUES (' . $id_manufacturer . ', ' . $visible . ')
                 ON DUPLICATE KEY UPDATE visible = ' . $visible
            );
        }

        $this->_clearCache('ld_brands.tpl');

        return $this->displayConfirmation($this->l('Visibilité des marques mise à jour.'));
    }

    private function renderSettingsPanel()
    {
        $this->context->smarty->assign([
            'ld_current_index' => $this->getAdminCurrentIndex(),
            'ld_token' => Tools::getAdminTokenLite('AdminModules'),
            'ld_limit' => (int) Tools::getValue('LD_BRANDS_LIMIT', Configuration::get('LD_BRANDS_LIMIT')),
            'ld_loop' => (bool) Configuration::get('LD_BRANDS_LOOP'),
            'ld_scroll_active' => (bool) Configuration::get('LD_BRANDS_SCROLL_ACTIVE'),
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . $this->name . '/views/templates/admin/settings.tpl'
        );
    }

    private function renderBrandsTable()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT m.id_manufacturer, m.name, m.active,
                    IFNULL(v.visible, 1) AS visible
             FROM `' . _DB_PREFIX_ . 'manufacturer` m
             LEFT JOIN `' . _DB_PREFIX_ . 'ld_brands_visibility` v
                 ON v.id_manufacturer = m.id_manufacturer
             ORDER BY m.name ASC'
        );

        $this->context->smarty->assign([
            'ld_current_index' => $this->getAdminCurrentIndex(),
            'ld_token' => Tools::getAdminTokenLite('AdminModules'),
            'ld_manufacturers' => $rows ?: [],
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . $this->name . '/views/templates/admin/brands_table.tpl'
        );
    }

    /**
     * ------------------------------------------------------------------
     * Front office
     * ------------------------------------------------------------------
     */
    public function hookDisplayHome($params)
    {
        return $this->renderWidget('displayHome', $params);
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        $this->context->controller->registerStylesheet(
            'ld-brands-css',
            'modules/' . $this->name . '/views/css/ld_brands.css',
            ['media' => 'all', 'priority' => 100]
        );

        if ((bool) Configuration::get('LD_BRANDS_SCROLL_ACTIVE') && (bool) Configuration::get('LD_BRANDS_LOOP')) {
            $this->context->controller->registerJavascript(
                'ld-brands-js',
                'modules/' . $this->name . '/views/js/ld_brands.js',
                ['position' => 'bottom', 'priority' => 100]
            );
        }
    }

    /**
     * ------------------------------------------------------------------
     * WidgetInterface
     * ------------------------------------------------------------------
     */
    public function renderWidget($hookName, array $configuration)
    {
        $variables = $this->getWidgetVariables($hookName, $configuration);
        if (empty($variables['ld_brands'])) {
            return false;
        }
        $this->smarty->assign($variables);

        return $this->fetch($this->templateFile);
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $id_shop = (int) $this->context->shop->id;

        $limit = (int) Configuration::get('LD_BRANDS_LIMIT');
        if ($limit <= 0) {
            $limit = self::DEFAULT_LIMIT;
        }

        // Uniquement les marques actives (natif PrestaShop) ET marquées visibles
        // pour ce module (par défaut visible=1 si jamais configuré : opt-out,
        // pas besoin de tout cocher manuellement pour retrouver le comportement
        // d'origine).
        $sql = 'SELECT m.id_manufacturer, m.name
                FROM `' . _DB_PREFIX_ . 'manufacturer` m
                INNER JOIN `' . _DB_PREFIX_ . 'manufacturer_shop` ms
                    ON (ms.id_manufacturer = m.id_manufacturer AND ms.id_shop = ' . $id_shop . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'ld_brands_visibility` v
                    ON v.id_manufacturer = m.id_manufacturer
                WHERE m.active = 1
                AND IFNULL(v.visible, 1) = 1
                ORDER BY RAND()';

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        $brands = [];

        if ($result) {
            foreach ($result as $row) {
                $id_manufacturer = (int) $row['id_manufacturer'];

                // On n'affiche que les marques qui ont réellement un logo uploadé
                if (!file_exists(_PS_MANU_IMG_DIR_ . $id_manufacturer . '.jpg')) {
                    continue;
                }

                $brands[] = [
                    'id_manufacturer' => $id_manufacturer,
                    'name' => $row['name'],
                    'image' => $this->context->link->getManufacturerImageLink($id_manufacturer, 'medium_default'),
                    'link' => $this->context->link->getManufacturerLink($id_manufacturer),
                ];

                if (count($brands) >= $limit) {
                    break;
                }
            }
        }

        if (empty($brands)) {
            return [];
        }

        return [
            'ld_brands' => $brands,
            'ld_brands_loop' => (bool) Configuration::get('LD_BRANDS_LOOP'),
            'ld_brands_scroll_active' => (bool) Configuration::get('LD_BRANDS_SCROLL_ACTIVE'),
        ];
    }
}
