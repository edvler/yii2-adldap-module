<?php
namespace Edvlerblog;

/**
 * YII2 wrapper class for the Adldap Module.
 * Look at https://packagist.org/packages/adldap/adldap for the Adldap Module
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * THANKS TO:
 * - Fedek. He wrote a similar plugin for yii 1.
 * - ztec. He is maintaining the Adldap repository on git
 *
 * @category ToolsAndUtilities
 * @package yii2-adldap-module
 * @author Matthias Maderer
 * @copyright (c) 2014 Matthias Maderer
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPLv2.1
 * @version 1.0.0
 * @link
 */


use yii\base\Component; //include YII component class
use Adldap\Adldap; //include the Adldap class
use Adldap\Exceptions\AdldapException;

class Ldap extends Component
{
    /**
     * The internal Adldap object.
     *
     * @var object Adldap
     */
    private $adLdapClass;

    /**
     * Connection variable for the Adldap constructor.
     *
     * @var Adldap\Connection\Ldap instance
     */
    public $connection = null;    
    
    /**
     * AutoConnect variable for the Adldap constructor.
     *
     * @var boolean autoConnect on instance creation
     */
    public $autoConnect = true;    

    /**
     * Options variable for the Adldap module.
     * See Adldap __construct function for possible values.
     *
     * @var array Array with option values
     */
    public $options = [];

    /**
     * init() called by yii.
     */
    public function init()
    {
        try {
            $this->adLdapClass = new Adldap($this->options, $this->connection, $this->autoConnect);
        } catch (AdldapException $e) {
            throw $e;
        }
    }

    /**
     * Use magic PHP function __call to route function calls to the Adldap class.
     * Look into the Adldap class for possible functions.
     *
     * @param string $methodName Method name from Adldap class
     * @param array $methodParams Parameters pass to method
     * @return mixed
     */
    public function __call($methodName, $methodParams)
    {
        if (method_exists($this->adLdapClass, $methodName)) {
            return call_user_func_array(array($this->adLdapClass, $methodName), $methodParams);
        } else {
            return parent::__call($methodName, $methodParams);
        }
    }
}
