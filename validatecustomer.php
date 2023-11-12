<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ValidationCustomer extends Module
{
    public function __construct()
    {
        $this->name = 'validationcustomer';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Doryan Fourrichon';
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];
        
        //récupération du fonctionnement du constructeur de la méthode __construct de Module
        parent::__construct();
        $this->bootstrap = true;

        $this->displayName = $this->l('Validation Customer');
        $this->description = $this->l('Module qui valide la création d\'un compte client');

        $this->confirmUninstall = $this->l('Do you want to delete this module');

    }

    public function install()
    {
        if (!parent::install() ||
        !Configuration::updateValue('ACTIVATE_VALIDATE_CUSTOMER', 0) ||
        !Configuration::updateValue('GROUP_CUSTOMER','') ||
        !$this->registerHook('actionCustomerAccountAdd')
        ) {
            return false;
        }
            return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
        !Configuration::deleteByName('ACTIVATE_VALIDATE_CUSTOMER') ||
        !Configuration::deleteByName('GROUP_CUSTOMER') ||
        !$this->unregisterHook('actionCustomerAccountAdd')
        ) {
            return false;
        }
            return true;
    }

    public function getContent()
    {
        return $this->postProcess().$this->renderForm();
    }

    public function postProcess()
    {
        if(Tools::isSubmit('saving'))
        {
            Configuration::updateValue('ACTIVATE_VALIDATE_CUSTOMER',Tools::getValue('ACTIVATE_VALIDATE_CUSTOMER'));
            Configuration::updateValue('GROUP_CUSTOMER',Tools::getValue('GROUP_CUSTOMER'));

            return $this->displayConfirmation('Les champs sont enregistrer !');
        }
    }

    public function renderForm()
    {
        $field_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings Hover Carousel'),
            ],
            'input' => [
                [
                    'type' => 'switch',
                        'label' => $this->l('Active customer validation'),
                        'name' => 'ACTIVATE_VALIDATE_CUSTOMER',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'label2_on',
                                'value' => 1,
                                'label' => $this->l('Oui')
                            ),
                            array(
                                'id' => 'label2_off',
                                'value' => 0,
                                'label' => $this->l('Non')
                            )
                        )
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Groupe customer'),
                    'name' => 'GROUP_CUSTOMER',
                    
                ]
            ],
            'submit' => [
                'title' => $this->l('save'),
                'class' => 'btn btn-primary',
                'name' => 'saving'
            ]
        ];

        $helper = new HelperForm();
        $helper->module  = $this;
        $helper->name_controller = $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value['ACTIVATE_VALIDATE_CUSTOMER'] = Configuration::get('ACTIVATE_VALIDATE_CUSTOMER');
        $helper->fields_value['GROUP_CUSTOMER'] = Configuration::get('GROUP_CUSTOMER');

        return $helper->generateForm($field_form);
    }

    public function hookActionCustomerAccountAdd($params)
    {
        if(Configuration::get('ACTIVATE_VALIDATE_CUSTOMER') == 1)
        {
            $customer = $params['newCustomer'];

            echo '<pre>';
            echo print_r($customer);
            echo '</pre>';
            die();

            $context = Context::getContext();
            $id_lang = (int) $context->language->id;
            $id_shop = (int) $context->shop->id;
            $configuration = Configuration::getMultiple(
                [
                'PS_SHOP_EMAIL',
                'PS_SHOP_NAME'
                ],$id_lang, null, $id_shop
            );
            $date = date('Y-m-d à H:i:s');
            
            $tempalte_vars = [
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{email}' => $customer->email,
                '{date}' => $date,
                '{shop_name}' => $configuration['PS_SHOP_EMAIL'],
                '{shop_url}' => 'https://'.$configuration['PS_SHOP_DOMAIN'].'/'.$configuration['PS_SHOP_NAME']
            ];


            $mailsAdmin = explode(', ',Configuration::get('EMAIL_NEW_CUSTOMER'));
            foreach ($mailsAdmin as $mail) {
                $mail_id_lang = $id_lang;

                Mail::send(
                    $mail_id_lang,
                    'new_customer',
                    $this->l('New Customer'),
                    $tempalte_vars,
                    $mail,
                    null,
                    null,
                    null,
                    null,
                    null,
                    _PS_MODULE_DIR_.'notificationplus/mails/fr/new_customer.html'
                );
            }
        }
        
    }
}