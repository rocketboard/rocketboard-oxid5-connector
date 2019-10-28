<?php
/**
 *
 * @package   ##@@PACKAGE@@##
 * @version   ##@@VERSION@@##
 * @license   ##@@LICENSE@@##
 * @link      https://www.proudcommerce.com
 * @author    Stefan Moises <support@proudcommerce.com>
 * @copyright ProudCommerce | ##@@DATE@@##
 *
 * This Software is the property of Proud Sourcing GmbH
 * and is protected by copyright law, it is not freeware.
 *
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be
 * prosecuted by civil and criminal law.
 *
 * ##@@HASH@@##
 *
 **/

$sMetadataVersion = '1.1';

$aModule = array(
    'id'            => 'pcRocketBoard',
    'title'       => [
        'de' => 'RocketBoard',
        'en' => 'RocketBoard',
    ],
    'description' => [
        'de' => 'Modul um Shop-Infos an Rocketboard.io zu übermitteln.',
        'en' => 'Module to send shop infos to Rocketboard.io',
    ],
    'thumbnail'     => '',
    'version'       => '1.3.1',
    'author'        => 'ProudCommerce',
    'url'           => 'https://rocketboard.io',
    'email'         => 'support@rocketboard.io',

    'extend'                  => [
    ],
    'files'             => [
        'rocketboard' => 'pc/rocketboard/application/controllers/Rocketboard.php',
    ],
    'templates'               => [],
    'smartyPluginDirectories' => [],
    'blocks'                  => [
    ],
    'events'                  => [
    ],
    'settings'                => [
        [
            'group' => 'pcRocketboardMain',
            'name'  => 'rocketToken',
            'type'  => 'str',
            'value' => ''
        ],
    ],
);
