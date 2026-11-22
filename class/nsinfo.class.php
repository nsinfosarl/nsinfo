<?php

class Nsinfo
{
	public $db;

	/**
	 * Constructeur
	 *
	 * @param DoliDb $db Gestionnaire de base de données
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;
	}

    function InEurop($country='') {

        $country_code_in_EEC = array(
            'AT', // Autriche
            'BE', // Belgique
            'BG', // Bulgarie
            'CY', // Chypre
            'CZ', // République tchèque
            'DE', // Allemagne
            'DK', // Danemark
            'EE', // Estonie
            'ES', // Espagne
            'FI', // Finlande
            'FR', // France
//            'GB', // United Kingdom
            'GR', // Grèce
            'HR', // Croatie
            'NL', // Pays-Bas
            'HU', // Hongrie
            'IE', // Irlande
            // 'IM', // Île de Man - incluse dans le Royaume-Uni
            'IT', // Italie
            'LT', // Lituanie
            'LU', // Luxembourg
            'LV', // Lettonie
            'MC', // Monaco - inclus dans la France
            'MT', // Malte
            //'NO',	// Norway
            'PL', // Pologne
            'PT', // Portugal
            'RO', // Roumanie
            'SE', // Suède
            'SK', // Slovaquie
            'SI', // Slovénie
            // 'UK', // United Kingdom
            //'CH',	// Suisse - non, la Suisse ne fait pas partie de la CEE
        );
        if (!empty(getDolGlobalString('MAIN_COUNTRIES_IN_EEC')))
        {
            // Par exemple MAIN_COUNTRIES_IN_EEC = 'AT,BE,BG,CY,CZ,DE,DK,EE,ES,FI,FR,GB,GR,HR,NL,HU,IE,IM,IT,LT,LU,LV,MC,MT,PL,PT,RO,SE,SK,SI,UK'
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
			if ($obj) {
				$res = $obj->rowid;
			}
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
