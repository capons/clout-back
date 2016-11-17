<?php

/**
 * This class processes CSV, MS Excel and text files for contacs
 * SOME FUNCTIONS WERE TRANSFERED DIRECTLY FROM THE OLD CLOUT SYSTEM
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.1.0
 * @copyright Clout
 * @created 03/07/2013
 */
class _csv_processor extends CI_Model
{
	#Description Variable to hold file content for CSV upload contacts
    private $fileContent;
    #Description Variable to store parsed email ids from different sources
    private $data=array();
    #Description To hold intermediate Type value for CSV uploads
    private $format;
    #Description Number of insert commands at a time, this value will be used while inserting contacts into
    #user_contacts table
    private $maxInserts;
	#Current user id
    private $userId;
	#The location of the file
	private $fileLocation;
	#Existing contacts
	private $existingContacts;
	
	
    function __construct()
    {
        parent::__construct();
        #Set number of max insert commands at a time.
        $this->maxInserts = MAX_EMAILS_TO_IMPORT;
    }
	
	
	#Get contacts from upload csv files. Saves file contents in $this->filecontent variable and parse email ids
    #from that string depends upon the format specified. After parsing email ids (Name and Email), eliminates
    #duplicates, inserts in imported_contacts table for the user and returns an array with "Mail Type" and "Number of Contacts"
    #to the caller routine for further processing
    public function get_contacts_through_file($userId, $fileLocation, $format, $return='list', $existingEmails=array())
    {
    	log_message('debug', '_csv_processor/get_contacts_through_file');
    	log_message('debug', '_csv_processor/get_contacts_through_file:: [1] userId='.$userId.' fileLocation='.$fileLocation.' format='.$format.' return='.$return.' existingEmails='.json_encode($existingEmails));
        #Save user contact in local variable
        $this->userId = $userId;
		$this->existingContacts = $existingEmails;
		
        #Get contents only if the file exists
        if(!empty($fileLocation))
        {
            #Save file contents into a local variable without saving the file in another location
            $this->filecontent = remove_quotes(preg_replace("/[\r]/","",file_get_contents($fileLocation))); 
			$this->fileLocation = $fileLocation;
        }
		
        #Save format in local variable
        $this->format=$format;
        #Parse contacts
        $this->get_contacts_from_file();
        #Check and remove duplicate email ids
        $this->check_dup_emailids();
		
        #Return Contacts data to caller for displaying to user to select
        if($this->filecontent=="" && count($this->data)==0) return array("result" =>FALSE, "message"=>"No Data found in Input");
		else if($this->filecontent!="" && count($this->data)==0) return array("result" =>FALSE, "message"=>"Invalid data");
		else {
			if($return == 'array') return $this->get_email_array();
			else return array("result" =>TRUE, "message"=>"", "data"=>$this->data);
		}
    }
	
	
	
	#This is a private function of this class. This function decides which function should be called
    #to parse the file content depending upon the format specified.
    private function get_contacts_from_file()
    {
    	log_message('debug', '_csv_processor/get_contacts_from_file');
    	log_message('debug', '_csv_processor/get_contacts_from_file:: [1] format='.$this->format);
    	
        switch($this->format)
        {
            case 'csv_generic': #Generic CSV
                $this->parse_csv_sep(",");
                break;
            case 'csv_gmail': #Gmail Contacts CSV
                $this->parse_csv_with_header(array('First Name','Last Name','Email Address'));
                break;           
            case 'csv_outlook': #Outlook contacts CSV
            	$this->parse_csv_with_header(array('First Name','Last Name','E-mail Address'));
                break;
            case 'csv_yahoo': #Yahoo Contacts CSV
                $this->parse_csv_with_header(array('First','Last','Email'));
                break;            
            case 'text_commas': #Text format contacts CSV
                $this->parse_csv_text();
                break;
            case 'text_tabs': #Tab delimited Contacts CSV
                $this->parse_csv_sep("\t");
                break; 
        }   
		  
    }
	
	
	
	
	
	#This function parses the CSV file if it has a header row
	private function parse_csv_with_header($requiredFields)
	{
		log_message('debug', '_csv_processor/parse_csv_with_header');
		
		// exit function if file content is null
        if(empty($this->filecontent))
            return;
        else
        {
			// Explode the file content with new line to get number of contacts
            $emails = explode("\n",$this->filecontent);
            $cnt = count($emails);
			$titles = explode(',',$emails[0]);
			$titleKeys = array();
			foreach($titles AS $key=>$title) $titleKeys[$title] = $key;
			
			//Start at 1 as the titles take the first row
            for($i=1; $i < $cnt; $i++)
            {
                //Explode each entry with given seperator i.e, "," or "tab"
                $data=explode(',',$emails[$i]);
				$finalData = array("name"=>'',"email_address"=>'');
				
				// if number of fields in entry are less than 2, move to next entry
				if(count($data) < 2 || (!empty($data[$titleKeys[$requiredFields[2]]]) && in_array($data[$titleKeys[$requiredFields[2]]], $this->existingContacts))) continue;
				
				//First Name
				if(!empty($data[$titleKeys[$requiredFields[0]]])) $finalData['name'] .= $data[$titleKeys[$requiredFields[0]]];
				//Last Name
				if(!empty($data[$titleKeys[$requiredFields[1]]])) $finalData['name'] .= ' '.$data[$titleKeys[$requiredFields[1]]];
				//Email
				if(!empty($data[$titleKeys[$requiredFields[2]]])) $finalData['email_address'] = $data[$titleKeys[$requiredFields[2]]];
				
                //Save email info in data array
                if(!empty($finalData['email_address'])) $this->data[] = $finalData;
            }
            log_message('debug', '_csv_processor/parse_csv_with_header:: [1] finalData='.json_encode($finalData));
		}
	}
	
	
	
	
	
	#This is a private function of this class. This function parse email ids from a plain text file
    #with comma separator and store these email ids in this class data array
    private function parse_csv_text()
    {
    	log_message('debug', '_csv_processor/parse_csv_text');
    	
        // Exit function if file content is null
        if(empty($this->filecontent))
            return;
        else
        {
            //As this is function expects all email ids with comma seperation, lets remove new line 
            //and carriege return characters from content and explode the content with ",". 
            //So that will will get only email ids
            $emailids=explode(",",trim(preg_replace("/[\n\r\s+]/","",$this->filecontent)));
            //Get the number of contacts found
            $cnt=count($emailids);
			// Loop through all email ids and store them in data array
            for($i=0; $i < $cnt; $i++) 
            {
				if(!empty($emailids[$i]) && !in_array($emailids[$i], $this->existingContacts)) $this->data[]=array("name"=>"","email_address"=>$emailids[$i]);
			}
        }
    }
	
	
	
	
    #This is a private function of this class. This function parse email ids from a csv file with specific
    #seperator i.e, ",", or "/t" (tab) separator and store these email ids in this class data array.
    #As this is a csv file, it will have both name and email id
    private function parse_csv_sep($sep)
    {
    	log_message('debug', '_csv_processor/parse_csv_sep');
    	 
        // exit function if file content is null
        if(empty($this->filecontent))
            return;
        else
        {
            // Explode the file content with new line to get number of contacts
            $emails = explode("\n",$this->filecontent);
            $cnt = count($emails);
            for($i=0; $i < $cnt; $i++) // Loop through all entries
            {
                //Explode each entry with given seperator i.e, "," or "tab"
                $data=explode($sep,$emails[$i]);
				// if number of fields in entry are less than 2 or there is no "@" in second field (Might be Column headings), move to next entry
				if(count($data)<2 || !strpos($data[1],"@") ||  in_array($data[1], $this->existingContacts)) 
                    continue;
                //Save email info in data array
                $this->data[] = array("name"=>$data[0],"email_address"=>$data[1]);
            }
        }
    }
	
	
	
	
	
	
	#This is a private function of this class. This function clears the data array of email ids.
    #Some entries might not have Name values and some entries might not be real email ids.
    #This function will take care of these points.
    private function clear_data_array()
    {
    	log_message('debug', '_csv_processor/clear_data_array');
    	
        //Copy all email ids into temp variable
        $temp=$this->data;
        //Empty Email ids data array
        $this->data=array();
        //Get total number of email contacts available in temp variable
        $cnt=count($temp);
        for($i=0; $i < $cnt; $i++) //Loop through available email contacts
        {
            //Store email id in data array if already not exist in that
            if(filter_var($temp[$i]["email_address"], FILTER_VALIDATE_EMAIL))
            {
                $temp[$i]["email_address"] = strtolower(trim(preg_replace('/[\x00-\x09\x0B-\x19\x7F]/', '', $temp[$i]["email_address"])));
                if(trim($temp[$i]["name"])=="")
                {
                    $temp1 = explode("@",$temp[$i]["email_address"]);
                    $temp[$i]["name"] = trim($temp1[0]);
                }
                $this->data[]=$temp[$i];
            }
        }
    }
	
	
	
	
	#This is a private function of this class. This function makes email id's data array unique
    #i.e, removes duplicate entries and restore in this class data variable
    private function check_dup_emailids()
    {
    	log_message('debug', '_csv_processor/check_dup_emailids');
    	 
        //Clear the email data first
        $this->clear_data_array();
        //Copy all email ids into temp variable
        $temp=$this->data;
        //Empty Email ids data array
        $this->data=array();
        //Get total number of email contacts available in temp variable
        $cnt=count($temp);
        for($i=0; $i < $cnt; $i++) //Loop through available email contacts
        {
            //Store email id in data array if already not exist in that
            if(!in_array($temp[$i],$this->data) && filter_var($temp[$i]["email_address"], FILTER_VALIDATE_EMAIL))
                    $this->data[]=$temp[$i];
        }
    } 
	
	
	
	
	#This is a private function of this class. This function makes email id's data array unique
    #i.e, removes duplicate entries and restore in this class data variable
    private function get_email_array()
    {
    	log_message('debug', '_csv_processor/get_email_array');
    	
		$emails = array();
		foreach($this->data AS $row) if(!empty($row['email_address'])) array_push($emails, $row['email_address']);
		
		log_message('debug', '_csv_processor/get_email_array:: [1] emails'.json_encode($emails));
		return $emails;
	}
	
	
	
	
	
	
	
	
}


