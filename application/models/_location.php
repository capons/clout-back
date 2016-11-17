<?php
/**
 * This class generates and formats location details. 
 *
 * @author Al Zziwa <azziwa@gmail.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/22/2015
 */
class _location extends CI_Model
{
	
	# Locate address with google latitude and longitude
	function locate_with_google_lat_lng($locationPhrase,$searchBy='address')
	{
		log_message('debug', '_location/locate_with_google_lat_lng');
		log_message('debug', '_location/locate_with_google_lat_lng:: [1] locationPhrase='.$locationPhrase.' searchBy'.$searchBy);
		
		$location = array();
		
		# Set the CURL Options
		$data['key'] = GOOGLE_API_KEY;
		$data[$searchBy] = trim($locationPhrase);
		$addressList = $this->run_curl(GOOGLE_GEOCODING_API_URL.'?'.http_build_query($data));
		log_message('debug', '_location/locate_with_google_lat_lng:: [2] addressList='.json_encode($addressList));
		
		# Simply use the first address - usually the most accurate
		if(!empty($addressList['results'][0])){
			$raw = $addressList['results'][0];
			#$location = array('address', 'latitude','longitude', 'zipcode', 'city', 'state_code', 'state', 'country_code', 'country_name')
			if(!empty($raw['formatted_address'])) $location['address'] = $raw['formatted_address'];
			if(!empty($raw['geometry']['location']['lat'])) $location['latitude'] = $raw['geometry']['location']['lat'];
			if(!empty($raw['geometry']['location']['lng'])) $location['longitude'] = $raw['geometry']['location']['lng'];
			if(!empty($raw['address_components'])) {
				foreach($raw['address_components'] AS $component){
					if(!empty($component['types']) && in_array('postal_code', $component['types']) && !empty($component['short_name'])) $location['zipcode'] = $component['short_name'];
					if(!empty($component['types']) && in_array('administrative_area_level_1', $component['types']) && !empty($component['short_name'])) $location['state_code'] = $component['short_name'];
					if(!empty($component['types']) && in_array('administrative_area_level_1', $component['types']) && !empty($component['long_name'])) $location['state'] = $component['long_name'];
					if(!empty($component['types']) && in_array('country', $component['types']) && !empty($component['short_name'])) $location['country_code'] = $component['short_name'];
					if(!empty($component['types']) && in_array('country', $component['types']) && !empty($component['long_name'])) $location['country_name'] = $component['long_name'];
					if(!empty($component['types']) && in_array('locality', $component['types']) && !empty($component['long_name'])) $location['city'] = $component['long_name'];
				}
			}
		}
		
		log_message('debug', '_location/locate_with_google_lat_lng:: [3] location='.json_encode($location));
		return $location;
	}
	
	
	
	
	
	
	
	
	# Run the passed url on CURL 
	function run_curl($url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($curl, CURLOPT_REFERER, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($curl);
		curl_close($curl);
		#Return the result from the cURL execution
		return json_decode($result, TRUE);
	}
	
	
	
	

}
