<?php
/**
 *
 * @package   ##@@PACKAGE@@##
 * @version   ##@@VERSION@@##
 * @license   ##@@LICENSE@@##
 * @link      https://www.proudcommerce.com
 * @author    Stefan Moises <support@proudcommerce.com>
 * @copyright ProudCommerce | ##@@DATE@@##
 *
 * This Software is the property of Proud Sourcing GmbH
 * and is protected by copyright law, it is not freeware.
 *
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be
 * prosecuted by civil and criminal law.
 *
 * ##@@HASH@@##
 *
 **/

/**
 * Class Rocketboard
 *
 * @package ProudCommerce\Rocketboard\Controller
 */
class Rocketboard extends oxUBase
{

    /**
     * @var string
     */
    protected $apiVersion = "1.0";
    /**
     * Show debug messages
     *
     * @var boolean
     */
    private $debug = false;
    /**
     * DB connection
     *
     * @var [type]
     */
    private $oDb;
    /**
     * Utils object
     *
     * @var Utils
     */
    private $utils;

    /**
     * Main render function logic
     *
     * @return void
     */
    public function render()
    {
        $oConfig = oxRegistry::getConfig();
        $token = $oConfig->getShopConfVar('rocketToken');
        $reqToken = $oConfig->getRequestParameter('rocketToken');
        if ($token) {
            if (!$reqToken || $reqToken != $token) {
                die("Configuration token does not match parameter 'rocketToken'!");
            }
        } else {
            die("Please set a token in the configuration!");
        }

        $this->utils = oxRegistry::getUtils();
        $this->oDb = oxDb::getDb(oxDB::FETCH_MODE_ASSOC);
        $what = $oConfig->getRequestParameter('what');
        $data = [];
        switch ($what) {
            case 'oxid':
                $data = $this->getAppInfo($what);
                break;
            case 'plugins':
                $data = $this->getPluginInfo($what);
                break;
            default:
                $data = $this->getAppInfo($what);
        }

        array_walk_recursive(
            $data,
            function (&$value) {
            // Convert DateTime instances to ISO-8601 Strings
                if ($value instanceof DateTime) {
                    $value = $value->format(DateTime::ISO8601);
                }
            }
        );

        $data = $this->utf8ize($data);
        $this->utils->setHeader("Content-Type: application/json");
        $this->utils->showMessageAndExit(json_encode($data));
    }

    /**
     * @param $mixed
     *
     * @return array|bool|string
     */
    public function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[ $key ] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return utf8_encode($mixed);
        }

        return $mixed;
    }

    /**
     * Function to get all plugins and their versions.
     * @param string $what The type to return
     * @return array
     */
    public function getPluginInfo($what)
    {
        $data = [];
        $data['type'] = $what;
        $data['version'] = $this->apiVersion;
        $data['data'] = [];

        $aModules = $this->getModuleData();
        foreach ($aModules as $key => $oModule) {
            $data['data'][ $key ] = [
                'name'    => $oModule->getTitle(),
                'active'  => $oModule->isActive(),
                'version' => $oModule->getInfo('version'),
                'author'  => $oModule->getInfo('author'),
                'payload' => $this->getDetailedModuleData($oModule)
            ];
        }

        return $data;
    }

    /**
     * getModuleData() doesn't exist in OXID 4/5
     *
     * @param oxModule $oModule
     *
     * @return array
     */
    private function getDetailedModuleData($oModule)
    {
        $sModulePath = $oModule->getModuleFullPath($sModuleId);
        $sMetadataPath = $sModulePath . "/metadata.php";

        if ($sModulePath && file_exists($sMetadataPath) && is_readable($sMetadataPath)) {
            $aModule = [];
            include $sMetadataPath;

            return $aModule;
        }
    }

    /**
     * Get module data
     *
     * @return array
     */
    private function getModuleData()
    {
        $sModulesDir = oxRegistry::getConfig()->getModulesDir();
        $oModuleList = oxNew('oxModuleList');
        $aModules = $oModuleList->getModulesFromDir($sModulesDir);

        return $aModules;
    }

    /**
     * Function to get app info.
     * @param string $what The type to return
     * @return array
     */
    public function getAppInfo($what)
    {
        $shopData = $this->getShopData();
        $oConfig = oxRegistry::getConfig();
        $oShop = $oConfig->getActiveShop();

        $data = [];
        $data['version'] = $this->apiVersion;
        $data['type'] = $what;
        $data['application'] = [];
        $data['application']['contact'] = $oShop->oxshops__oxinfoemail->getRawValue();
        $data['application']['type'] = "oxid";
        $data['application']['url'] = $oConfig->getSslShopUrl();
        $data['application']['name'] = $oShop->oxshops__oxname->getRawValue();
        $data['application']['edition'] = $shopData['licence'];
        $data['application']['version'] = $shopData['version'];
        $data['application']['build'] = $shopData['revision'];

        $data['infrastructure'] = [];
        $data['infrastructure']['platform'] = 'PHP ' . phpversion();
        $data['infrastructure']['platform_info'] = $this->phpinfo2array();
        $data['infrastructure']['os'] = $shopData['os'] . " " . $shopData['arch'] . " " . $shopData['dist'] . " ";
        $data['infrastructure']['db'] = "MySQL " . $shopData['mysqlVersion'];
        $data['infrastructure']['web'] = $shopData['serverSoftware'];

        return $data;
    }

    /**
     * Get some shop details
     *
     * @return array
     */
    private function getShopData()
    {
        $oConfig = oxRegistry::getConfig();
        $oShop = $oConfig->getActiveShop();

        return [
            'os'             => PHP_OS ?: '',
            'arch'           => php_uname('m') ?: '',
            'dist'           => php_uname('r') ?: '',
            'licence'        => $oConfig->getEdition(),
            'version'        => $oConfig->getVersion(),
            'revision'       => $oConfig->getRevision(),
            'mysqlVersion'   => $this->oDb->getOne('SELECT @@version'),
            'serverSoftware' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : ''
        ];
    }
    /**
     * Function to convert the phpinfo() output to an array
     *
     * @return array
     */
    private function phpinfo2array()
    {
        $entitiesToUtf8 = function ($input) {
            // http://php.net/manual/en/function.html-entity-decode.php#104617
            return preg_replace_callback("/(&#[0-9]+;)/", function ($m) {
                return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
            }, $input);
        };
        $plainText = function ($input) use ($entitiesToUtf8) {
            return trim(html_entity_decode($entitiesToUtf8(strip_tags($input))));
        };
        $titlePlainText = function ($input) use ($plainText) {
            return $plainText($input);
        };
       
        ob_start();
        phpinfo(-1);
       
        $phpinfo = array();
    
        // Strip everything after the <h1>Configuration</h1> tag (other h1's)
        if (!preg_match('#(.*<h1[^>]*>\s*Configuration.*)<h1#s', ob_get_clean(), $matches)) {
            return array();
        }
       
        $input = $matches[1];
        $matches = array();
    
        if (preg_match_all(
            '#(?:<h2.*?>(?:<a.*?>)?(.*?)(?:<\/a>)?<\/h2>)|'.
            '(?:<tr.*?><t[hd].*?>(.*?)\s*</t[hd]>(?:<t[hd].*?>(.*?)\s*</t[hd]>(?:<t[hd].*?>(.*?)\s*</t[hd]>)?)?</tr>)#s',
            $input,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $fn = strpos($match[0], '<th') === false ? $plainText : $titlePlainText;
                if (strlen($match[1])) {
                    $phpinfo[$match[1]] = array();
                } elseif (isset($match[3])) {
                    $keys1 = array_keys($phpinfo);
                    $phpinfo[end($keys1)][$fn($match[2])] = isset($match[4]) ? array($fn($match[3]), $fn($match[4])) : $fn($match[3]);
                } else {
                    $keys1 = array_keys($phpinfo);
                    $phpinfo[end($keys1)][] = $fn($match[2]);
                }
            }
        }
       
        return $phpinfo;
    }
}
