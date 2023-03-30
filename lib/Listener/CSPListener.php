<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 T-Systems International
 *
 * @author B. Rederlechner <bernd.rederlechner@t-systems.com>
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
namespace OCA\NmcMarketing\Listener;

use OCP\IConfig;
use OCP\IRequest;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;

class CSPListener implements IEventListener {
	private IConfig $config;
	private ContentSecurityPolicyNonceManager $cspNonceManager;

	public function __construct(IRequest $request,
								IConfig $config,
								ContentSecurityPolicyNonceManager $nonceMgr) {
		$this->request = $request;
		$this->config = $config;
		$this->nonce = $nonceMgr->getNonce();
	}

	public function handle(Event $event): void {
		if (!$event instanceof AddContentSecurityPolicyEvent) {
			return;
		}

		if (!$this->isPageLoad()) {
			return;
		}

		$marketing_config = $this->config->getSystemValue("nmc_marketing");

		// see https://content-security-policy.com/ for possible value formats
		$policy = new EmptyContentSecurityPolicy();

		// include Taelium as valid scripting source, from config
		$policy->allowInlineScript(false);
		$policy->useJsNonce($this->nonce);
		$policy->addAllowedScriptDomain($this->domainOnly($marketing_config['script_url']));

		// include Telekom official font source for fonts
		$policy->addAllowedFontDomain($this->domainOnly($marketing_config['font_url']));
		$event->addPolicy($policy);
	}

	private function isPageLoad(): bool {
		$scriptNameParts = explode('/', $this->request->getScriptName());
		return end($scriptNameParts) === 'index.php';
	}

	/**
	 * Strips the path and query parameters from the URL.
	 */
	private function domainOnly(string $url): string {
		$parsedUrl = parse_url($url);
		$scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : 'https://';
		$host = $parsedUrl['host'] ?? '';
		$port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
		return "$scheme$host$port";
	}
}
