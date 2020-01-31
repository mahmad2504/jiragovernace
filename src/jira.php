<?php 

namespace mahmad\JiraGovernace;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


function P20($value)
{
	return str_pad($value,20);
}
function P30($value)
{
	return str_pad($value,30);
}


function P15($value)
{
	return str_pad($value,15);
}
function P12($value)
{
	return str_pad($value,12);
}
function P10($value)
{
	return str_pad($value,10);
}
function P8($value)
{
	return str_pad($value,8);
}

function P5($value)
{
	return str_pad($value,5);
}
function R($value)
{
	return "\e[91m".$value."\e[0m";
}	
function Y($value)
{
	return "\e[93m".$value."\e[0m";
}
function G($value)
{
	return "\e[92m".$value."\e[0m";
}
function C($value)
{
	return "\e[96m".$value."\e[0m";
}
function TITLE($value)
{
	return "\e[104m".$value."\e[0m";
}

class Jira
{
    private $url = null;
	private $user = null;
	private $pass = null;
	public $taskdata = null;
	public $sprint_data = [];
	public $query;
	public $fields;
	function __construct($config)
	{
		
		$this->rebuild=$config['rebuild'];
		
		
		$storypoints_field = 'customfield_10022';
		$sprint_field  = 'customfield_11040';
	
	
		$this->url = 'https://jira.alm.mentorg.com';
		$this->user = $config['user'];
		$this->pass = $config['pass'];
		$this->taskdata = 
		[
			"fields"=>
			[
				"project"=>[
					"key"=>""
				],
				"issuetype"=>[
					"id"=>""
				],
				"summary"=>"",
				"description"=>""
			]
		];
		$this->query = 'fixversion='.$config['version'];
		$this->version = $config['version'];
		$this->fields=$sprint_field.",".$storypoints_field.",timeoriginalestimate,status,statuscategorychangedate,resolutiondate,created,labels,issuetype";
		$tasks = $this->Search($this->query,$this->fields,null);
		foreach($tasks as $task)
		{
			$this->ParseData($task,$sprint_field,$storypoints_field);	
		}
		foreach($this->sprint_data as $sprint)
		{
			$this->ProcessSprint($sprint);
		}
		usort($this->sprint_data, [$this,'cmp_sprintname']);
		
		if(!file_exists($this->version."/plan.xlsm"))
		{
			echo "plan.xlsm file not found in ".$this->version." folder";
			exit();
		}
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->version."/plan.xlsm"); 
		$data = $spreadsheet->getActiveSheet()->toArray(null,true,true,true); 
		
		$inplan=0;
		foreach($data as $row)
		{
			$i=0;
			foreach($row as $cell)
			{
				$i++;
				if($cell == null)
				{
					
				}
				else
				{
					
					$found=0;
					for($j=0;$j<count($this->sprint_data);$j++)
					{
						if(strcasecmp($cell,$this->sprint_data[$j]->name)==0)
						{
							$found=1;
							//unset($jira->sprint_data[$j]->tasks);
							$this->sprint_data[$j]->inplan = 1;
							
							if($i == 1)
								$this->sprint_data[$j]->ignore = 1;
							else
							{
								$inplan=1;
								$plan[$cell] = $cell;
							}
							//dump($jira->sprint_data[$j]);
							break;
						}
					}
				}
			}
		}
		if($inplan==0)
		{
			printf("%s\n",R("Plan is emplty. Mention some sprints in plan"));
			//usort($this->sprint_data, [$this,'cmp_sprintstate']);
			echo TITLE('All Sprints')."\n";
			printf("%s|%s|%s|%s|%s \n",C(P30('Sprint Name')),C(P5('ID')),C(P5('Board')),C(P5('Issue')),C(P5('Estimate')),C(P10('State')));
			$message = G('None');
			foreach($this->sprint_data as $sprint)
			{
				printf("%s|%s|%s|%s|%s|%s \n",P30($sprint->name),P5($sprint->no),P5($sprint->id),P5($sprint->issuecount),P5($sprint->estimate),P10($sprint->state));
				$message = '';
			}
			echo  $message."\n";
			exit();
		}
		/**********************************************************************/
		echo TITLE('Sprints out of plan')."\n";
		printf("%s|%s|%s|%s|%s \n",C(P30('Sprint Name')),C(P5('ID')),C(P5('Board')),C(P5('Issue')),C(P5('Estimate')),C(P10('State')));
		$message = G('None');
		foreach($this->sprint_data as $sprint)
		{
			if($sprint->inplan==0)
			{
				printf("%s|%s|%s|%s|%s \n",R(P30($sprint->name)),P5($sprint->no),P5($sprint->id),P5($sprint->issuecount),P5($sprint->estimate),P10($sprint->state));
				$message = '';
			}
		}
		echo  $message."\n";
		
		/**********************************************************************/
		$active_sprints = [];
		echo TITLE("Active Sprints!")."\n";
		printf("%s|%s|%s|%s|%s|%s \n",C(P30('Sprint Name')),C(P5('ID')),C(P5('Board')),C(P5('Issue')),C(P5('Estimate')),C(P5('Completed')));
		foreach($this->sprint_data as $sprint)
		{
			if(array_key_exists($sprint->name, $plan))
			{
				if($sprint->state == 'ACTIVE')
					$active_sprints[] = $sprint;
			}
		}
		sort($active_sprints);
		foreach($active_sprints as $sprint)
		{
			printf("%s|%s|%s|%s|%s|%s \n",P30($sprint->name),P5($sprint->no),P5($sprint->id),P5($sprint->issuecount),P5($sprint->estimate),P5($sprint->completed));
		}

		/**********************************************************************/
		$future_sprints = [];
		echo TITLE("Future Sprints!")."\n";
		printf("%s|%s|%s|%s|%s|%s \n",C(P30('Sprint Name')),C(P5('ID')),C(P5('Board')),C(P5('Issue')),C(P5('Estimate')),C(P5('Completed')));
		foreach($this->sprint_data as $sprint)
		{
			if(array_key_exists($sprint->name, $plan))
			{
				if($sprint->state == 'FUTURE')
					$future_sprints[] = $sprint;
			}
		}
		sort($future_sprints);
		foreach($future_sprints as $sprint)
		{
			printf("%s|%s|%s|%s|%s|%s \n",P30($sprint->name),P5($sprint->no),P5($sprint->id),P5($sprint->issuecount),P5($sprint->estimate),P5($sprint->completed));
		}
		/**********************************************************************/
		$closed_sprints = [];
		echo TITLE("Closed Sprints!")."\n";
		printf("%s|%s|%s|%s|%s|%s \n",C(P30('Sprint Name')),C(P5('ID')),C(P5('Board')),C(P5('Issue')),C(P5('Estimate')),C(P5('Completed')));
		
		foreach($this->sprint_data as $sprint)
		{
			if(array_key_exists($sprint->name, $plan))
			{
				if($sprint->state == 'CLOSED')
					$closed_sprints[] = $sprint;
			}
		}
		sort($closed_sprints);
		foreach($closed_sprints as $sprint)
		{
			printf("%s|%s|%s|%s|%s|%s \n",P30($sprint->name),P5($sprint->no),P5($sprint->id),P5($sprint->issuecount),P5($sprint->estimate),P5($sprint->completed));
		}



		/**********************************************************************/
		echo TITLE("Out of sprint tasks")."\n";
		printf("%s|%s|%s|%s|%s \n",C(P12('Jira Key')),C(P8('Estimate')),C(P10('Status')),C(P12('Issue Type')),C(P12('Created On')));
		$message = G('None');
		$tasks_array = (array)$tasks;
		usort($tasks_array, [$this,"cmp_createdon"]);
		$lastweekdate =  date('Y-m-d', strtotime(date("Y-m-d").' -7 Days'));
		$total_estimate = 0;
		foreach($tasks_array as $task)
		{

			if(($task->fields->_sprint == null)&&($task->fields->_status != 'RESOLVED')&&($task->fields->_issuetype != 'EPIC')&&
			($task->fields->_issuetype != 'REQUIREMENT'))
			{
				$createdon = P10($task->fields->_createdon);
				if($task->fields->_createdon>$lastweekdate)
					$createdon = Y(P10($task->fields->_createdon));
				$sprint_name = 'None';
				if($task->fields->_sprint != null)
					$sprint_name = $task->fields->_sprint->name;
				
				if($task->fields->_issuetype == 'DEFECT')
					printf("%s|%s|%s|%s|%s \n",R(P12($task->key)),P8($task->fields->_estimate),P10($task->fields->_status),Y(P12($task->fields->issuetype->name)),$createdon);
				else
					printf("%s|%s|%s|%s|%s \n",R(P12($task->key)),P8($task->fields->_estimate),P10($task->fields->_status),P12($task->fields->issuetype->name),$createdon);
				$total_estimate  += $task->fields->_estimate;
				$message = '';
			}
		}
		if($total_estimate > 0)
		{
			printf("%s\n",C('-------------------------------------'));
			printf("%s|%s\n",C('Total Work out of sprint'),C($total_estimate));
		}
		echo  $message."\n";


		/*******************************************************************/

		echo TITLE('Tasks Created Last week!')."\n";
		printf("%s|%s|%s|%s|%s|%s \n",C(P12('Jira Key')),C(P8('Estimate')),C(P12('Issue Type')),C(P10('Status')),C(P30('Sprint Name')),C(P10('Created')));
		$message = G('None');
		$lastweekdate =  date('Y-m-d', strtotime(date("Y-m-d").' -7 Days'));
		foreach($tasks_array as $task)
		{
			if($task->fields->_createdon>$lastweekdate)
			{
				$sprintname = 'none';
				if($task->fields->_sprint != null)
					$sprintname = $task->fields->_sprint->name;;
				
				if($task->fields->_issuetype == 'DEFECT')
					printf("%s|%s|%s|%s|%s|%s \n",P12($task->key),P8($task->fields->_estimate),Y(P12($task->fields->_issuetype)),P10($task->fields->_status),P30($sprintname),P10($task->fields->_createdon));
				else
					printf("%s|%s|%s|%s|%s|%s \n",P12($task->key),P8($task->fields->_estimate),P12($task->fields->_issuetype),P10($task->fields->_status),P30($sprintname),P10($task->fields->_createdon));
				
				
				//echo $task->key."  ".$task->fields->_estimate."  ".$task->fields->_status."  ".$task->fields->issuetype->name." ".$task->fields->_createdon."\n";
				$message = '';
			}
		}
		echo  $message."\n";



		/*******************************************************************/

		echo TITLE('Tasks With no estimate')."\n";
		printf("%s|%s|%s|%s|%s|%s \n",C(P12('Jira Key')),C(P8('Estimate')),C(P12('Issue Type')),C(P10('Status')),C(P30('Sprint Name')),C(P10('Created')));
		$message = G('None');
		$lastweekdate =  date('Y-m-d', strtotime(date("Y-m-d").' -7 Days'));
		foreach($tasks_array as $task)
		{
			if(($task->fields->_estimate == 0)&&($task->fields->_status != 'RESOLVED')&&($task->fields->_issuetype != 'EPIC')&&
			($task->fields->_issuetype != 'REQUIREMENT'))
			{
				$sprintname = 'none';
				if($task->fields->_sprint != null)
					$sprintname = $task->fields->_sprint->name;;
				
				if($task->fields->_issuetype == 'DEFECT')
					printf("%s|%s|%s|%s|%s|%s \n",R(P12($task->key)),P8($task->fields->_estimate),Y(P12($task->fields->_issuetype)),P10($task->fields->_status),P30($sprintname),P10($task->fields->_createdon));
				else
					printf("%s|%s|%s|%s|%s|%s \n",R(P12($task->key)),P8($task->fields->_estimate),P12($task->fields->_issuetype),P10($task->fields->_status),P30($sprintname),P10($task->fields->_createdon));
				
				//echo $task->key."  ".$task->fields->_estimate."  ".$task->fields->_status."  ".$task->fields->issuetype->name." ".$task->fields->_createdon."\n";
				$message = '';
			}
		}
		echo  $message."\n";


		/******************************************************************/
		echo TITLE('Tasks in sprints with no fixversion')."\n";
		printf("%s|%s \n",C(P12('Jira Key')),C(P30('Sprint Name')),C(P5('Board')),C(P10('Status')));
		$message = G('None');

		foreach($this->sprint_data as $sprint)
		{
			if(($sprint->inplan)&&($sprint->ignore==0))
			{
				$tasks = $this->IssuesInSprint($sprint->id,$sprint->no);
				foreach($tasks as $task)
				{
					if($task->fields->status->statusCategory->id == 3)
						$task->fields->_status = 'RESOLVED';
					else if($task->fields->status->statusCategory->id == 4)
						$task->fields->_status  = 'INPROGRESS';
					else
						$task->fields->_status  = 'OPEN';
				
					$task->matched = 0;
					foreach($sprint->tasks as $t)
					{
						if($task->key == $t->key)
						{
							$task->matched = 1;
							break;
						}
					}	
				}
				foreach($tasks as $task)
				{
					if(($task->matched == 0)&&(count($task->fields->fixVersions)==0)&&($task->fields->_status != 'RESOLVED'))
					{
						printf("%s|%s|%s \n",R(P12($task->key)),P30($sprint->name),P5($sprint->id),P10($task->fields->status->name));
					}
				}
				/*unset($sprint->tasks);
				dump($sprint);
				$resource=$jira_settings['url'].'/rest/agile/1.0/board/'.$sprint->id.'/sprint/'.$sprint->no.'/issue?fields=key';
				$jira->GetJiraResource($resource);
				echo "Out";
				exit();*/
			}
		}
		echo "Done";


	}
	private  function cmp_sprintstate($a, $b) {
		return strcmp($a->state, $b->state);
	}
	private  function cmp_sprintname($a, $b) {
		return strcmp($a->name, $b->name);
	}
	private function cmp_createdon($a, $b) 
	{
		return strcmp($b->fields->_createdon, $a->fields->_createdon);
	}

	function ProcessSprint($sprint)
	{
		$sprint->estimate  = 0;
		$sprint->inplan = 0;
		$sprint->ignore = 0;
		$sprint->completed = 0;
		$sprint->issuecount = count($sprint->tasks);
		foreach($sprint->tasks as $task)
		{
			$sprint->estimate += $task->fields->_estimate;
			if($task->fields->_status == 'RESOLVED')
				$sprint->completed += $task->fields->_estimate;
				
		}
		
	}
	public function ParseData($task,$field_sprint,$field_storypoints)
	{
		$task->fields->_storypoints=null;
		$task->fields->_estimate = 0;
		$task->fields->_closedon = null;
		//dump($task->fields->issuetype);
		$task->fields->_issuetype = $this->MapIssueType($task->fields->issuetype->name,$task->key);
		
		$task->fields->_createdon = explode("T",$task->fields->created)[0];
		 
		
		
		if($task->fields->status->statusCategory->id == 3)
			$task->fields->_status = 'RESOLVED';
		else if($task->fields->status->statusCategory->id == 4)
			$task->fields->_status  = 'INPROGRESS';
		else
			$task->fields->_status  = 'OPEN';
		
		if($task->fields->_status == 'RESOLVED')
		{
			if(isset($task->fields->resolutiondate))
				$task->fields->_closedon = explode('T',$task->fields->resolutiondate)[0];
			if(isset($task->fields->statuscategorychangedate))
				$task->fields->_closedon = explode('T',$task->fields->statuscategorychangedate)[0];
			if($task->fields->_closedon == null)
			{
				echo $task->key." Does not have closed on date\n";
			}
		}
		
		$task->fields->status = $task->fields->status->name; // Over write the original status structure as unneeded information
		
		if(isset($task->fields->$field_storypoints))
		     $task->fields->_storypoints=$task->fields->$field_storypoints;
		
		if($task->fields->_storypoints > 0 )
			$task->fields->_estimate = $task->fields->_storypoints;
		else if(isset($task->fields->timeoriginalestimate))
			$task->fields->_estimate = round($task->fields->timeoriginalestimate/(28800),3);
		
		
		$this->ParseSprintData($task,$field_sprint);
		
	}
	function MapIssueType($issuetype,$key)
	{
		if(($issuetype=='Cluster')||($issuetype=='Feature')||($issuetype == ' Customer Requirement')||($issuetype=='ESD Requirement')||($issuetype=='BSP Requirement')||($issuetype=='Requirement'))
			return 'REQUIREMENT';

		if(($issuetype=='Workpackage')||($issuetype=='Project')||($issuetype=='Subproject'))
			return 'WORKPACKAGE';

		if($issuetype=='Bug')
			return 'DEFECT';

		if($issuetype=='Epic')
			return 'EPIC';

		if(($issuetype=='DocTask')||($issuetype=='DevTask')||($issuetype=='QaTask')||($issuetype=='Documentation')||($issuetype=='Action')||($issuetype=='Dependency')||($issuetype=='Sub-task')||($issuetype=='Issue')||($issuetype=='Risk')||($issuetype=='Bug')||($issuetype=='Task')||($issuetype=='Story')||($issuetype=='Product Change Request')||($issuetype=='New Feature')||($issuetype=='Improvement'))
			return 'TASK';

		echo 'Error::Unmapped type=['.$key.' '.$issuetype.']'."\n";
		return 'TASK';
		//
	}
	private function ParseSprintData($task,$sprint)
	{
		$last_sequence = 0;
		$task->fields->_sprint = null;
		if(!isset($task->fields->$sprint))
			return;
		$index = "0";
		
		/*if(is_object($task->fields->$sprint->$index))
		{
			
			exit();
			$this->ParseSprintData_newversion($task,$sprint);
			return;
		}*/
		foreach($task->fields->$sprint as $sprintdata)
		{
			$str = $sprintdata;
			$sprint_info = explode(',',$str);
			for($i=0;$i<count($sprint_info);$i++)
			{
				$keyvalue = explode('=',$sprint_info[$i]);
				if($keyvalue[0] =='sequence')
				{
					$sequence = $keyvalue[1];
				}
			}
			//$sequence = explode('sequence=',$str)[1];
			//$sequence = explode(']',$sequence)[0];
			//echo $sequence;
			if($sequence < $last_sequence)
			{
				continue;
			}
			$last_sequence = $sequence;
			$sprint_info = explode(',',$str);
			for($i=0;$i<count($sprint_info);$i++)
			{
				$keyvalue = explode('=',$sprint_info[$i]);
				if($keyvalue[0] =='name')
				{
					$sprintname = $keyvalue[1];
				}
				else if($keyvalue[0] =='state')
				{
					$sprintstate = $keyvalue[1];
				}
				else if($keyvalue[0] == 'rapidViewId')
				{
					$sprintid = $keyvalue[1];
				}
				else if($keyvalue[0] == 'startDate')
				{
					$sprintstart = $keyvalue[1];
				}
				else if($keyvalue[0] == 'endDate')
				{
					$sprintend = $keyvalue[1];
				}
				else if(strpos($keyvalue[0],'[id')!== false)
				{
					$sprintno = $keyvalue[1];

				}
			}
		}
		$s = new \StdClass();
		$s->name = $sprintname;
		$s->state  = $sprintstate;
		$s->id = $sprintid;
		$s->no = $sprintno;
		if($sprintstart != '<null>')
			$s->start = explode('T',$sprintstart)[0];
		if($sprintend != '<null>')
			$s->end = explode('T',$sprintend)[0];
		$task->fields->_sprint = $s;
		if(array_key_exists($sprintno,$this->sprint_data))
		{
			$this->sprint_data[$sprintno]->tasks[$task->key] = $task;
		}
		else
		{
			$this->sprint_data[$sprintno] = $s;
			$this->sprint_data[$sprintno]->tasks[$task->key] = $task;
		}
	}
	private function ParseSprintData_newversion($task,$sprint)
	{
		$lastid = 0;
		$lastindex = -1;
		dump($task->fields->$sprint);
		exit();
		foreach($task->fields->$sprint as $sprintdata)
		{
			if($lastid < $sprintdata->id)
			{
				$lastid = $sprintdata->id;
				$lastindex  = $j;
			}
		}
		$sprintstart = null;
		$sprintend = null;
		//dd($task->fields->$sprint[$lastindex]);
		$sprintname = $task['fields'][$sprint][$lastindex]->name;
		$sprintstate = $task['fields'][$sprint][$lastindex]->state;
		$sprintid = $task['fields'][$sprint][$lastindex]->boardId;
		$sprintno = $task['fields'][$sprint][$lastindex]->id;
		if(isset($task['fields'][$sprint][$lastindex]->startDate))
			$sprintstart = explode('T',$task['fields'][$sprint][$lastindex]->startDate)[0];
		if(isset($task['fields'][$sprint][$lastindex]->endDate))
			$sprintend = explode('T',$task['fields'][$sprint][$lastindex]->endDate)[0];
		
		$s = new \StdClass();
		$s->name = $sprintname;
		$s->state  = $sprintstate;
		$s->id = $sprintid;
		$s->no = $sprintno;
		if($sprintstart != null)
			$s->start = explode('T',$sprintstart)[0];
		if($sprintend != null)
			$s->end = explode('T',$sprintend)[0];
		$task['sprint'] = $s;
		if(array_key_exists($sprintno,$this->sprint_data))
		{
			$this->sprint_data[$sprintno]->tasks[$task['key']] = $task;
		}
		else
		{
			$this->sprint_data[$sprintno] = $s;
			$this->sprint_data[$sprintno]->tasks[$task['key']] = $task;
		}
	}
	public function to_object(array $array, $class = 'stdClass')
    {
		$object = new $class;
		foreach ($array as $key => $value)
		{
				if (is_array($value))
				{
				// Convert the array to an object
						$value = $this->to_object($value, $class);
				}
				// Add the value to the object
				$object->{$key} = $value;
		}
		return $object;
    }
	public function Search($query,$fields=null,$order=null)
	{
		if (!file_exists($this->version)) {
			mkdir($this->version, 0777, true);
		}
		if (!file_exists($this->version."/cache")) {
			mkdir($this->version."/cache", 0777, true);
		}

		$filename = $this->version."/cache/".md5($query);
		$startAt = 0;
		$maxresults = 100;
		$tasks = [];
		if(file_exists($filename)&&$this->rebuild==0)
		{
			$tasks = json_decode(file_get_contents($filename));
			echo "Reading from cache\n";
			return $tasks;
		}
		$query = str_replace(" ","%20",$query);
		echo "Syncing with Jira ";
		while(1)
		{
			$resource=$resource=$this->url.'/rest/api/latest/'."search?jql=".$query.'&maxResults='.$maxresults.'&startAt='.$startAt;
			if($fields != null)
				$resource.='&fields='.$fields;
		
			//echo $resource."\n";
			echo "....";
			$t =  $this->GetJiraResource($resource);
			
			if($t == null)
				break;
			$count = count($t);		
			if($count == 0)
				break;
			$startAt += $count;
			foreach($t as $td)
				$tasks[$td['key']] = $this->to_object($td);
		
		}
		//echo $filename;
		echo "\n";
		file_put_contents( $filename, json_encode( $tasks ) );
		return $tasks;
	}
	public function IssuesInSprint($boardid,$sprintid)
	{
		$filename = $this->version."/cache/".md5($boardid.$sprintid);
		$tasks = [];
		if(file_exists($filename)&&$this->rebuild==0)
		{
			$tasks = json_decode(file_get_contents($filename));
			return $tasks;
		}
		$resource=$resource=$this->url.'/rest/agile/1.0/board/'.$boardid.'/sprint/'.$sprintid.'/issue?fields=key,fixVersions,status';
		$tasks =  $this->GetJiraResource($resource);
		
		file_put_contents( $filename, json_encode( $tasks ) );
		$tasks = json_decode(file_get_contents($filename));
		
		return $tasks;
	}
	
	public function GetJiraResource($resource,$data=null)
	{
		//echo $resource."\n";
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_USERPWD => $this->user.':'.$this->pass,
		CURLOPT_URL =>$resource,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => array('Content-type: application/json')));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		if($data != null)
		{
			curl_setopt_array($curl, array(
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $data
				));
		}
		$result = curl_exec($curl);
		$ch_error = curl_error($curl);
		$code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);

		if ($ch_error)
		{
			dump('Error::'.$ch_error);
			return null;
		}
		else if($code == 200)
		{
			$data = json_decode($result,true);
			//exit();
			$tasks = array();
			if(isset($data["worklogs"]))
			{
				return $data["worklogs"];
			}
			if(isset($data["issues"]))
			{
				if(count($data["issues"])==0)
				{
					return $tasks;
				}
				foreach($data["issues"] as $task)
				{
					//echo $task['key']."\n";
					$tasks[$task['key']] = $task;
				}
				return $tasks;
			}
			else if(isset($data['forestUpdates']))
			{
				return $data['forestUpdates'][1]['formula'];
			}
			dump($data);
			exit();
			return $data;
		}
		else
		{
			//dd($result);
			dump("Error::Code - ".$code);
			return null;
		}
	}
	function CreateTicket($key,$issuetypeid,$summary,$description)
	{
		$this->taskdata['fields']['project']['key'] = $key;
		$this->taskdata['fields']['issuetype']['id'] = $issuetypeid;
		$this->taskdata['fields']['summary'] = $summary;
		$this->taskdata['fields']['description'] = $description;
		
		$data = json_encode($this->taskdata);
		$resource=$this->url.'/rest/api/latest/issue';
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_USERPWD => $this->user.':'.$this->pass,
		CURLOPT_URL =>$resource,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => array('Content-type: application/json')));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		if($data != null)
		{
			curl_setopt_array($curl, array(
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $data
				));
		}
		$result = curl_exec($curl);
		$ch_error = curl_error($curl);
		$code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);
		if ($ch_error)
		{
			return -1;
		}
		if($code == 201)
		{
			$data = json_decode($result,true);
			return $data;
		}
		return -1;
	}
}
