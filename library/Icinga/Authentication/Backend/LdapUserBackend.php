<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use \stdClass;
use \Zend_Config;
use \Icinga\User;
use \Icinga\Authentication\UserBackend;
use \Icinga\Authentication\Credential;
use \Icinga\Protocol\Ldap;
use Icinga\Protocol\Ldap\Connection;
use \Icinga\Application\Config as IcingaConfig;

/**
 * User authentication backend
 */
class LdapUserBackend implements UserBackend
{
    /**
     * Ldap resource
     *
     * @var Connection
     **/
    protected $connection;

    /**
     * The ldap connection information
     *
     * @var Zend_Config
     */
    private $config;

    /**
     * Name of the backend
     *
     * @var string
     */
    private $name;

    /**
     * Create new Ldap User backend
     *
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->connection = new Ldap\Connection($config);
        $this->config = $config;
        $this->name = $config->name;
    }

    /**
     * Name of the backend
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Test if the username exists
     *
     * @param   Credential $credential
     *
     * @return  bool
     */
    public function hasUsername(Credential $credential)
    {
        return $this->connection->fetchOne(
            $this->selectUsername($credential->getUsername())
        ) === $credential->getUsername();
    }

    /**
     * Removes the '*' character from $string
     *
     * @param string $string
     *
     * @return string
     **/
    protected function stripAsterisks($string)
    {
        return str_replace('*', '', $string);
    }

    /**
     * Tries to fetch the username
     *
     * @param  string   $username The username to select
     *
     * @return stdClass $result
     **/
    protected function selectUsername($username)
    {
        return $this->connection->select()
            ->from(
                $this->config->user_class,
                array(
                    $this->config->user_name_attribute
                )
            )
            ->where(
                $this->config->user_name_attribute,
                $this->stripAsterisks($username)
            );
    }

    /**
     * Authenticate
     *
     * @param   Credential $credentials
     *
     * @return  User
     */
    public function authenticate(Credential $credentials)
    {
        if (!$this->connection->testCredentials(
            $this->connection->fetchDN($this->selectUsername($credentials->getUsername())),
            $credentials->getPassword()
        )) {
            return false;
        }
        $user = new User($credentials->getUsername());

        return $user;
    }

    public function getUserCount()
    {
        return $this->connection->count(
            $this->connection->select()->from(
                $this->config->user_class,
                array(
                    $this->config->user_name_attribute
                )
            )
        );

    }
}
