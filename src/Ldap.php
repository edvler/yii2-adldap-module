<?php
namespace Edvlerblog;
/**
 * YII2 wrapper class for the adLDAP Module.
 * Look at https://packagist.org/packages/adldap/adldap for the adLDAP Module
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
 * - ztec. He is maintaining the adLDAP repository on git
 * 
 * @category ToolsAndUtilities
 * @package yii2-adLDAP-module
 * @author Matthias Maderer
 * @copyright (c) 2014 Matthias Maderer
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPLv2.1
 * @version 1.0.0
 * @link 
 */


use yii\base\Component; //include YII component class
use adLDAP\adLDAP; //include the adLDAP class

class Ldap extends Component {
    /**
     * The internal adLDAP object.
     *
     * @var object adLDAP
     */
    private $adLdapClass=null;
	
    /**
     * Options variable for the adLDAP module.
     * See adLDAP __construct function for possible values.
     *
     * @var array Array with option values
     */	
    public $options=null;

    /**
     * init() called by yii.
     */	
    public function init() {
        try {
            $this->adLdapClass = new adLDAP($this->options);
        } catch (adLDAPException $e) {
            throw new CException($e);   
        }		
    }

    /**
     * Use magic PHP function __call to route function calls to the adLDAP class.
     * Look into the adLDAP class for possible functions.
     *
     * @param string $methodName Method name from adLDAP class
     * @param array $methodParams Parameters pass to method
     * @return mixed
     */	
    public function __call($methodName, $methodParams) {
            if ( method_exists( $this->adLdapClass, $methodName ) ) {
                return call_user_func_array(array($this->adLdapClass, $methodName), $methodParams);
            }       
    }
}
