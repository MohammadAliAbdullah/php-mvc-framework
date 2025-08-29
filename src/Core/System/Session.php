<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace App\Core\System;

use App\Core\System\Session\PhpSession;
use function App\Core\System\Utils\config;

class Session {
	private $driver;

	public static function getInstance() {
		static $inst = null;

		if ($inst === null) {
			$driver = config(APP . '.session.driver', 'php');

			if ($driver) {
                try {
                    $inst = new Session($driver);
                } catch (\Exception $e) {
                    return $inst;
                }
            }
		}

		return $inst;
	}

	public function __construct($expire = 3600) {
        $options      = config(APP . '.session', []);
        $this->driver = new PhpSession($options);
	}

	public function get($key) {
		return $this->driver->get($key) ?? null;
	}

	public function set($key, $value) {
		$this->driver->set($key, $value);
        return $this->get($key);
	}

	public function delete($key): bool
    {
		$this->driver->delete($key);
        return true;
	}

	public function close(): bool
    {
		return $this->driver->close();
	}
	public function sessionId($id = null) {
		return $this->driver->sessionId($id);
	}
}
