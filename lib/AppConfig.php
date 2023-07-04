<?php
/**
 * Nmcmarketing App
 *
 * @author sangramsinh desai
 * Email  sangramsinh.desai@t-systems.com
 *
 */

namespace OCA\NmcMarketing;

use \OCP\IConfig;

class AppConfig {

	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}
}
