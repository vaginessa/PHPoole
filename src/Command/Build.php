<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Command;

class Build extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $_serve;

    public function processCommand()
    {
        $this->_serve = $this->route->getMatchedParam('serve', false);

        $this->wlAnnonce('Building website...');
        try {
            $this->getPHPoole()->build();
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
        if ($this->_serve) {
            $callable = new Serve();
            call_user_func($callable, $this->getRoute(), $this->getConsole());
        }
    }
}
