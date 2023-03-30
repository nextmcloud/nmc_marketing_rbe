<?php
/*
 * @copyright Copyright (c) 2022 T-Systems International
 *
 * @author Bernd Rederlechner <bernd.rederlechner@t-systems.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace OCA\NmcMarketing\UnitTest;

use OCP\ILogger;
use OCP\IConfig;
use OCP\IRequest;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use OCP\AppFramework\App;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Security\IContentSecurityPolicyManager;

use OC\Security\CSP\ContentSecurityPolicyManager;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;

use OCA\NmcMarketing\AppInfo\Application;
use OCA\NmcMarketing\Listener\CSPListener;

class CspPolicyTest extends TestCase {


	public function setUp(): void {
		parent::setUp();
		$this->app = new App(Application::APPNAME);
		$this->config = $this->app->getContainer()->get(IConfig::class);
		$this->config->setSystemValue("nmc_marketing", array(
			'script_url' => 'https://my.marketing.source.site/environment/utag.js',
			'font_url' => 'https://telekom.fonts.source.site/xxx'
		));
		$this->logger = $this->app->getContainer()->get(ILogger::class);
		$this->nonceMgr = $this->app->getContainer()->get(ContentSecurityPolicyNonceManager::class);
	}

	public function testNoRequestNoPolicy() {
		$request = $this->app->getContainer()->get(IRequest::class);		
		$this->listener = new CSPListener($request,
										  $this->config,
										  $this->nonceMgr);
		$cspManager = $this->createMock(ContentSecurityPolicyManager::class);
		$cspManager->expects($this->never())
					->method('addDefaultPolicy');
		$event = new AddContentSecurityPolicyEvent($cspManager);
		$this->listener->handle($event);

	}


	public function testMarketingEvent() {
		$request = $this->getMockForAbstractClass(IRequest::class);
		$request->expects($this->once())
					->method('getScriptName')
					->willReturn('/index.php');
		$this->listener = new CSPListener($request,
										  $this->config,
										  $this->nonceMgr);
		$cspManager = $this->createMock(ContentSecurityPolicyManager::class);
		$cspManager->expects($this->once())
					->method('addDefaultPolicy')
					->with($this->callback(function($policy) {
						$representation = $policy->buildPolicy();
						//print($representation);
						$this->assertRegExp("/script-src [^;]*nonce-[^;]*https:\/\/my.marketing.source.site[^;]*;/", $representation);						
						$this->assertRegExp("/font-src [^;]*https:\/\/telekom.fonts.source.site[^;]*;/", $representation);						
						return true;
					}));

		$event = new AddContentSecurityPolicyEvent($cspManager);
		$this->listener->handle($event);
	}

}