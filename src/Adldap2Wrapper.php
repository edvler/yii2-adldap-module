<?php
namespace Edvlerblog\Adldap2;

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 * Dieses Programm ist Freie Software: Sie können es unter den Bedingungen
 * der GNU General Public License, wie von der Free Software Foundation,
 * Version 3 der Lizenz oder (nach Ihrer Wahl) jeder neueren
 * veröffentlichten Version, weiterverbreiten und/oder modifizieren.

 * Dieses Programm wird in der Hoffnung, dass es nützlich sein wird, aber
 * OHNE JEDE GEWÄHRLEISTUNG, bereitgestellt; sogar ohne die implizite
 * Gewährleistung der MARKTFÄHIGKEIT oder EIGNUNG FÜR EINEN BESTIMMTEN ZWECK.
 * Siehe die GNU General Public License für weitere Details.

 * Sie sollten eine Kopie der GNU General Public License zusammen mit diesem
 * Programm erhalten haben. Wenn nicht, siehe <http://www.gnu.org/licenses/>.
 *
 * THANKS TO:
 * - Fedek. He wrote a similar plugin for yii 1.
 * - stevebauman. He is maintaining the Adldap2 repository on github.com
 *
 * @category ToolsAndUtilities
 * @package yii2-adldap-module
 * @author Matthias Maderer
 * @copyright (c) 2017 Matthias Maderer
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version 2.0.0
 * @link https://github.com/edvler/yii2-adldap-module
 */


use yii\base\Component; //include YII component class
use Adldap\Adldap; //include the Adldap class

class Adldap2Wrapper extends Component
{
    /**
     * The Adldap instance.
     */
    public $adLdapInstance;
    
    /**
     * Array containig providers config
     */
    public $providers;  
    
    /*
     * The name of the default provider 
     */
    public $defaultProvider = "default";
    
    /**
     * init() called by yii.
     */
    public function init()
    {
        if(!isset($this->adLdapInstance)) {
            $this->adLdapInstance = new Adldap();
        }

        foreach($this->providers as $providerName=>$prodivderSettings) {
            $config = new \Adldap\Connections\Provider($prodivderSettings['config']);
            $this->adLdapInstance->addProvider($config, $providerName);
            
            if($prodivderSettings['autoconnect'] == true) {
                $this->adLdapInstance->connect($providerName);
            }
        }
        
        $providers = $this->adLdapInstance->getProviders();
        
        if (array_key_exists($this->defaultProvider, $providers)) {
            $this->adLdapInstance->setDefaultProvider($this->defaultProvider);
        } else {
            throw new \yii\base\Exception("The given defaultprovder with the name " . $this->defaultProvider . " could not be found. See https://github.com/edvler/yii2-adldap-module/blob/master/readme.md");
        }
    }

    
    /**
     * Use magic PHP function __call to route ALL function calls to the Adldap class.
     * Look into the Adldap class for possible functions.
     *
     * @param string $methodName Method name from Adldap class
     * @param array $methodParams Parameters pass to method
     * @return mixed
     */
    public function __call($methodName, $methodParams)
    {       
        return call_user_func_array([$this->adLdapInstance, $methodName], $methodParams);
    }
}
