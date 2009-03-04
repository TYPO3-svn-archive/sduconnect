<?php
final class update_sdu_organisations{	
	
	public 		$account_id;
	public   	$collection_id;
	var $updateDetails;


	
	function update()
	{ 		
		$this->updateDetails->countFeed = 0;
		$this->updateDetails->countUpdate = 0;
		$this->updateDetails->countNew = 0;
		
		$this->read_xml();			
		$this->check_organisation_deleted();			
		if(!empty($this->_organisations)) $this->insert_organisations();
		return $this->updateDetails;
		
	}
	
	
	function read_xml()
	{
		
		$xml_file = 'http://xml.sduconnect.nl/organisation.xml?account_id='.$this->account_id.'&organisation_collection_id='.$this->collection_id.'&view=organisations';
		$xml = simplexml_load_file($xml_file);
		if($xml)
		{						
			$feed = array();
			$organisations = array();
			foreach($xml->organisations as $key => $item) {							

				foreach ($item->organisation as $o_key => $organisation)
				{
					$i++;
					$this->_organisation_ids[] .= $organisation->attributes();
					$organisations[$i]['organisation_id'] .= $organisation->attributes();
					$organisations[$i]['collectionId'] = $this->collection_id;
					$organisations[$i]['title'] .= $organisation->title;
					$organisations[$i]['searchstring'] .= preg_replace('/[^0-9a-zA-Z ]/','', strtolower(html_entity_decode( $organisation->title, ENT_QUOTES, "utf-8" ))); 
					$organisations[$i]['remark'] .= $organisation->remark;
					$organisations[$i]['visit_address_street'] .= $organisation->visit_address_street;
					$organisations[$i]['visit_address_number'] .= $organisation->visit_address_number;
					$organisations[$i]['visit_address_zip_code'] .= $organisation->visit_address_zip_code;
					$organisations[$i]['visit_address_city'] .= $organisation->visit_address_city;
					$organisations[$i]['post_address_street'] .= $organisation->post_address_street;
					$organisations[$i]['post_address_number'] .= $organisation->post_address_number;
					$organisations[$i]['post_address_po_box'] .= $organisation->post_address_po_box;
					$organisations[$i]['post_address_zip_code'] .= $organisation->post_address_zip_code;
					$organisations[$i]['post_address_city'] .= $organisation->post_address_city;
					$organisations[$i]['country'] .= $organisation->country;
					$organisations[$i]['phone_number'] .= $organisation->phone_number;
					$organisations[$i]['fax_number'] .= $organisation->fax_number;
					$organisations[$i]['email'] .= $organisation->email;
					$organisations[$i]['url'] .= $organisation->url;
					$organisations[$i]['logo'] .= $organisation->logo;
					
					$meta_datas = array();

					foreach ($organisation->meta_data->synoniems->synoniem as $meta_data)
					{
						$meta_datas[] = $meta_data->value;
					}
					
					$organisations[$i]['tags'] = implode(',',$meta_datas);
					$this->updateDetails->countFeed++;
				}
							
			}
			$this->_organisations = $organisations;
			$this->_organisations_meta_data = $meta_datas;
		}
		else return false;
	}
	
	function insert_organisations()
	{		
		foreach($this->_organisations AS $insertArray)
		{
			
			$check = $this->check_organisation($insertArray['organisation_id']);
			if(!empty($check))
			{
				if($check['organisation_id'] == $insertArray['organisation_id'] )//&&  $check['updated'] != $insertArray['updated']
				{
					$this->_organisation_id = $insertArray['organisation_id'];
					unset($insertArray['organisation_id']);
					$query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_sduconnect_organisation','organisation_id = '.intval($this->_organisation_id),$insertArray);
					$this->updateDetails->countUpdate++;
				}
			}
			else
			{
				$query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_sduconnect_organisation', $insertArray);
				$this->updateDetails->countNew++;
			}
			$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
			if (mysql_error())      debug(array(mysql_error(),$query));
		}
	}
	function check_organisation($organisation_id)
	{
		if(empty($organisation_id))	return false;
		
		$query = "SELECT * FROM tx_sduconnect_organisation WHERE organisation_id = '$organisation_id' ";
		$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		return $row;
	}
	function check_organisation_deleted()
	{
		if(!is_array($this->_organisation_ids))	return false;
		$organisation_ids = implode(",",$this->_organisation_ids);
		if(empty($organisation_ids))	return false;
		$where = "organisation_id NOT IN (".$organisation_ids.") ";
		$query = $GLOBALS['TYPO3_DB']->DELETEquery('tx_sduconnect_organisation', $where);
		$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		
		if (mysql_error())      debug(array(mysql_error(),$query));
		return;
	}	
	
	public static function truncate_Collection($aCollectionId){
		if($aCollectionId && is_int($aCollectionId)){
			$where = "collectionId=".(int) $aCollectionId;
			$query = $GLOBALS['TYPO3_DB']->DELETEquery('tx_sduconnect_organisation', $where);
			$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
			if (mysql_error()){
				debug(array(mysql_error(),$query));
				return false;
			}
			return true;
		}
		else {
			$query = 'TRUNCATE TABLE `tx_sduconnect_organisation` ';
			$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);	
			if (mysql_error()){
				debug(array(mysql_error(),$query));
				return false;
			}
			return true;
		}
	}
}
?>