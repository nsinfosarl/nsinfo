<?php

class Nsinfo
{
	public $db;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;
	}

    function InEurop($country='') {

        $country_code_in_EEC = array(
            'AT', // Austria
            'BE', // Belgium
            'BG', // Bulgaria
            'CY', // Cyprus
            'CZ', // Czech republic
            'DE', // Germany
            'DK', // Danemark
            'EE', // Estonia
            'ES', // Spain
            'FI', // Finland
            'FR', // France
//            'GB', // United Kingdom
            'GR', // Greece
            'HR', // Croatia
            'NL', // Holland
            'HU', // Hungary
            'IE', // Ireland
            // 'IM', // Isle of Man - Included in UK
            'IT', // Italy
            'LT', // Lithuania
            'LU', // Luxembourg
            'LV', // Latvia
            'MC', // Monaco - Included in France
            'MT', // Malta
            //'NO',	// Norway
            'PL', // Poland
            'PT', // Portugal
            'RO', // Romania
            'SE', // Sweden
            'SK', // Slovakia
            'SI', // Slovenia
            // 'UK', // United Kingdom
            //'CH',	// Switzerland - No. Swizerland in not in EEC
        );
        if (!empty(getDolGlobalString('MAIN_COUNTRIES_IN_EEC')))
        {
            // For example MAIN_COUNTRIES_IN_EEC = 'AT,BE,BG,CY,CZ,DE,DK,EE,ES,FI,FR,GB,GR,HR,NL,HU,IE,IM,IT,LT,LU,LV,MC,MT,PL,PT,RO,SE,SK,SI,UK'
            $country_code_in_EEC = explode(',', getDolGlobalString('MAIN_COUNTRIES_IN_EEC'));
        }

        return in_array($country,$country_code_in_EEC, true);
    }


	/**
	 * Verifie si un extrafield (name) existe dans un type de données défini (elementtype)
	 * @param $key
	 * @param $elementtype
	 * @return int
	 */
	public function fetch_extrafield($key, $elementtype)
	{
		$res = 0;
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."extrafields WHERE name = '".$key."' AND elementtype = '".$elementtype."'";
		$resql = $this->db->query($sql);
//		var_dump($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$res = $obj->rowid;
		}
		$this->db->free($resql);
		return $res;
	}


	/**
	 * @param $idobject
	 * @param $table
	 * @param $extra
	 * @return string
	 */
	public function getDataExtrafield($idobject, $table, $extra)
	{
		$data = '';
		$sql = 'SELECT '.$extra.' as data FROM '.MAIN_DB_PREFIX.$table.' WHERE fk_object = '.$idobject;
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$data = $obj->data;
		}
		$this->db->free($resql);
		return $data;
	}

}
