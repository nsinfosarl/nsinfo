<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022-2024 Nicolas SILOBRE <conFtact@ns-info.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    test/phpunit/NsinfoFunctionalTest.php
 * \ingroup nsinfo
 * \brief   Exemple de test Selenium.
 *
 * Insérer ici une description détaillée.
 */

namespace test\functional;

use PHPUnit_Extensions_Selenium2TestCase_WebDriverException;

/**
 * Classe NsinfoFunctionalTest
 *
 * Nécessite chromedriver pour Google Chrome
 * Nécessite geckodriver pour Mozilla Firefox
 *
 * @fixme Support de Firefox (Geckodriver/Marionette)
 * @todo Support d’Opera sous Linux
 * @todo Support de Windows (IE, Google Chrome, Mozilla Firefox, Safari)
 * @todo Support de OSX (Safari, Google Chrome, Mozilla Firefox)
 *
 * @package Testnsinfo
 */
class NsinfoFunctionalTest extends \PHPUnit_Extensions_Selenium2TestCase
{
	// TODO : déplacer vers un fichier de configuration global ?
	/** @var string URL de base du serveur web testé */
	protected static $base_url = 'http://dev.zenfusion.fr';
	/**
	 * @var string Nom d'utilisateur administrateur Dolibarr
	 * @see authenticate
	 */
	protected static $dol_admin_user = 'admin';
	/**
	 * @var string Mot de passe administrateur Dolibarr
	 * @see authenticate
	 */
	protected static $dol_admin_pass = 'admin';
	/** @var int Identifiant de module Dolibarr */
	private static $module_id = 500000; // TODO: détection automatique ?

	/** @var array Navigateurs à tester */
	public static $browsers = array(
		array(
			'browser' => 'Google Chrome on Linux',
			'browserName' => 'chrome',
			'sessionStrategy' => 'shared',
			'desiredCapabilities' => array()
		),
		// Geckodriver ne conserve pas la session pour le moment ?!
		// Les sélecteurs XPath ne semblent pas non plus fonctionner
		//array(
		//    'browser' => 'Mozilla Firefox on Linux',
		//    'browserName' => 'firefox',
		//    'sessionStrategy' => 'shared',
		//    'desiredCapabilities' => array(
		//        'marionette' => true,
		//    ),
		//)
	);

	/**
	 * Fonction utilitaire pour sélectionner les liens par href
	 *
	 * @param  string  $value      Href
	 * @return mixed               Chaîne utilitaire
	 */
	protected function byHref($value)
	{
		$anchor = null;
		$anchors = $this->elements($this->using('tag name')->value('a'));
		foreach ($anchors as $anchor) {
			if (strstr($anchor->attribute('href'), $value)) {
				break;
			}
		}
		return $anchor;
	}

	/**
	 * Initialisation globale des tests
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
	}

	/**
	 * Initialisation unitaire des tests
	 * @return void
	 */
	public function setUp()
	{
		$this->setSeleniumServerRequestsTimeout(3600);
		$this->setBrowserUrl(self::$base_url);
	}

	/**
	 * Vérification des pré-conditions
	 * @return void
	 */
	protected function assertPreConditions()
	{
	}

	/**
	 * Gestion de l’authentification Dolibarr
	 * @return void
	 */
	private function authenticate()
	{
		try {
			if ($this->byId('login')) {
				$login = $this->byId('username');
				$login->clear();
				$login->value('admin');
				$password = $this->byId('password');
				$password->clear();
				$password->value('admin');
				$this->byId('login')->submit();
			}
		} catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
			// Le login n'existe pas. On suppose que nous sommes déjà authentifiés
		}
	}

	/**
	 * Teste l’activation du mode développeur
	 * @return bool
	 */
	public function testEnableDeveloperMode()
	{
		$this->url('/admin/const.php');
		$this->authenticate();
		$main_features_level_path = '//input[@value="MAIN_FEATURES_LEVEL"]/following::input[@type="text"]';
		$main_features_level = $this->byXPath($main_features_level_path);
		$main_features_level->clear();
		$main_features_level->value('2');
		$this->byName('update')->click();
		// Page rechargée, il faut un nouveau XPath
		$main_features_level = $this->byXPath($main_features_level_path);
		return $this->assertEquals('2', $main_features_level->value(), "MAIN_FEATURES_LEVEL value is 2");
	}

	/**
	 * Teste l'activation du module
	 *
	 * @depends testEnableDeveloperMode
	 * @return bool
	 */
	public function testModuleEnabled()
	{
		$this->url('/admin/modules.php');
		$this->authenticate();
		$module_status_image_path = '//a[contains(@href, "'.self::$module_id.'")]/img';
		$module_status_image = $this->byXPath($module_status_image_path);
		if (strstr($module_status_image->attribute('src'), 'switch_off.png')) {
			// Active le module
			$this->byHref('modNsinfo')->click();
		} else {
			// Désactive le module
			$this->byHref('modNsinfo')->click();
			// Réactive le module
			$this->byHref('modNsinfo')->click();
		}
		// Page rechargée, il faut un nouveau XPath
		$module_status_image = $this->byXPath($module_status_image_path);
		return $this->assertContains('switch_on.png', $module_status_image->attribute('src'), "Module enabled");
	}

	/**
	 * Teste l'accès à la page de configuration
	 *
	 * @depends testModuleEnabled
	 * @return bool
	 */
	public function testConfigurationPage()
	{
		$this->url('/custom/nsinfo/admin/setup.php');
		$this->authenticate();
		return $this->assertContains('nsinfo/admin/setup.php', $this->url(), 'Configuration page');
	}

	/**
	 * Teste l'accès à la page À propos
	 *
	 * @depends testConfigurationPage
	 * @return bool
	 */
	public function testAboutPage()
	{
		$this->url('/custom/nsinfo/admin/about.php');
		$this->authenticate();
		return $this->assertContains('nsinfo/admin/about.php', $this->url(), 'About page');
	}

	/**
	 * Teste que la page À propos affiche le Markdown
	 *
	 * @depends testAboutPage
	 * @return bool
	 */
	public function testAboutPageRendersMarkdownReadme()
	{
		$this->url('/custom/nsinfo/admin/about.php');
		$this->authenticate();
		return $this->assertEquals(
			'Dolibarr Module Template (aka My Module)',
			$this->byTag('h1')->text(),
			"Readme title"
		);
	}

	/**
	 * Teste que la boîte est correctement déclarée
	 *
	 * @depends testModuleEnabled
	 * @return bool
	 */
	public function testBoxDeclared()
	{
		$this->url('/admin/boxes.php');
		$this->authenticate();
		return $this->assertContains('nsinfowidget1', $this->source(), "Box enabled");
	}

	/**
	 * Teste que le trigger est correctement activé
	 *
	 * @depends testModuleEnabled
	 * @return bool
	 */
	public function testTriggerDeclared()
	{
		$this->url('/admin/triggers.php');
		$this->authenticate();
		return $this->assertContains(
			'interface_99_modNsinfo_NsinfoTriggers.class.php',
			$this->byTag('body')->text(),
			"Trigger declared"
		);
	}

	/**
	 * Teste que le trigger est correctement déclaré
	 *
	 * @depends testTriggerDeclared
	 * @return bool
	 */
	public function testTriggerEnabled()
	{
		$this->url('/admin/triggers.php');
		$this->authenticate();
		return $this->assertContains(
			'tick.png',
			$this->byXPath('//td[text()="interface_99_modNsinfo_MyTrigger.class.php"]/following::img')->attribute('src'),
			"Trigger enabled"
		);
	}

	/**
	 * Vérification des post-conditions
	 * @return void
	 */
	protected function assertPostConditions()
	{
	}

	/**
	 * Nettoyage unitaire des tests
	 * @return void
	 */
	public function tearDown()
	{
	}

	/**
	 * Nettoyage global des tests
	 * @return void
	 */
	public static function tearDownAfterClass()
	{
	}
}
