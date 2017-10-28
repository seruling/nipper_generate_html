<?php
$head = "
<!DOCTYPE html>
	<html>
	<head>
	<style>
	body {
		font-family:'Calibri (Body)';
	}
	table, th, td {
	    border: 1px solid black;
	    border-collapse: collapse;
	    font-size:11pt;
	}
	th {
		color: #fff;
		background-color: #aaa;
		padding-left: 5px;
		padding-right: 5px;
	}
	td {
		padding-left: 5px;
		padding-right: 5px;
	}
	.critical {
		background-color: rgb(192,0,0);
	}
	.high {
		background-color: rgb(255,0,0);
	}
	.medium {
		background-color: rgb(255,192,0);
	}
	.low {
		background-color: rgb(146,208,80);
	}
	.total {
		background-color: rgb(0,176,240);
	}
	.th_head {
		color: #fff;
	}
	.bg-grey {
		background-color: #aaa;
	}
	.text-white {
		color: #fff;
		font-weight: bold;
	}
	.text-center {
		text-align: center;
	}
	</style>
	</head>
	<body>
";
$files = array();
$dir = opendir('.');
function cleanup_text($string) {
	$string = str_replace("\n", "", $string);
	$string = str_replace("\r", "", $string);
	return $string;
}
function make_tableable($string) {
	$string = str_replace("heading>", "th>", $string);
	$string = str_replace("headings>", "thead>", $string);
	$string = str_replace("tablebody>", "tbody>", $string);
	$string = str_replace("tablerow>", "tr>", $string);
	$string = str_replace("tablecell>", "td>", $string);
	$string = str_replace("item>", "p>", $string);
	return $string;
}

while(false != ($file = readdir($dir))) {
		if (preg_match("/.xml/", $file)) {
                $files[] = $file; 
        }   
}
$total_critical = 0;
$total_high = 0;
$total_medium = 0;
$total_low = 0;
$total_all = 0;
$all_devices_issues_count_row = "";
$final_output = "";
foreach($files as $file) {
	$reports=simplexml_load_file($file) or die("Error: Cannot create object");
	$device_count = 0;

//issue box
	$issues = "";
	$issue_count = 0;
	$issue_boxes = "";
	// echo $reports->report->part[1]->section[1]->issuedetails->ratings->rating[0];
	foreach($reports->report->part[1] as $section) { 
		$issue_title = $reports->report->part[1]->section[$issue_count]["title"];
		if (!(($issue_title == "Introduction") || ($issue_title == "Conclusions") || ($issue_title == "Recommendations") || ($issue_title == "Mitigation Classification"))) {
			if ($reports->report->part[1]->section[$issue_count]->section[0]->table) {
				$desc2_table_count = 0;
				foreach($reports->report->part[1]->section[$issue_count]->section[0]->table as $desc2_table) { 
					$issue_detail = make_tableable($reports->report->part[1]->section[$issue_count]->section[0]->table[$desc2_table_count]->asXML());
					$desc2_table_count++;
				}
			}
			else {
				$issue_detail = "\n";
			}
			$issue_desc = $reports->report->part[1]->section[$issue_count]->section[0]->text[0];
			$issue_rating = $reports->report->part[1]->section[$issue_count]->issuedetails->ratings->rating[0];
			if($issue_rating == "Critical") { $issue_box_rating_cell_colour = "critical"; }
			if($issue_rating == "High") { $issue_box_rating_cell_colour = "high"; }
			if($issue_rating == "Medium") { $issue_box_rating_cell_colour = "medium"; }
			if($issue_rating == "Low") { $issue_box_rating_cell_colour = "low"; }
			$issue_impact = $reports->report->part[1]->section[$issue_count]->section[1]->text[0];
			$issue_recommedation = $reports->report->part[1]->section[$issue_count]->section[2]->text[0];
			if($issue_rating != "Informational") {
				$issue_boxes .= "
				<table>
				<tr><td class='bg-grey text-white'>Findings</td><td class='bg-grey text-white text-center'>$issue_title</td></tr>
				<tr><td class='bg-grey text-white'>Risk</td><td class='$issue_box_rating_cell_colour text-white text-center'>$issue_rating</td></tr>
				<tr><td class='bg-grey text-white'>Description</td><td>$issue_desc<br/><br/>$issue_detail</td></tr>
				<tr><td class='bg-grey text-white'>Impact</td><td>$issue_impact</td></tr>
				<tr><td class='bg-grey text-white'>Recommendation</td><td>$issue_recommedation</td></tr>
				</table>
				<br/>
				";
			}
		}
		$issue_count++;
	}
//recommendation-table
	$device_name[$device_count] = $reports->information->devices->device['name'];
	$device_type[$device_count] = $reports->information->devices->device['type'];
	$device_os[$device_count] = $reports->information->devices->device['os'];
	$device_version[$device_count] = $reports->information->devices->device['version'];
	$issue_critical[$device_count] = 0;
	$issue_high[$device_count] = 0;
	$issue_medium[$device_count] = 0;
	$issue_low[$device_count] = 0;
	$issue_total[$device_count] = 0;

	$table_row = 0;
	$section_recommendation = $issue_count-2;
	$output_table_recommendation = "";
	foreach($reports->report->part[1]->section[$section_recommendation]->table->tablebody->tablerow as $tttt) {
		$rating = $reports->report->part[1]->section[$section_recommendation]->table->tablebody->tablerow[$table_row]->tablecell[1]->item[0];
		if ($rating != "Informational") {
			$recommendation_table_rating_cell_colour = "";
			if($rating == "Critical") { $issue_critical[$device_count]++; $recommendation_table_rating_cell_colour = "critical"; }
			if($rating == "High") { $issue_high[$device_count]++; $recommendation_table_rating_cell_colour = "high"; }
			if($rating == "Medium") { $issue_medium[$device_count]++; $recommendation_table_rating_cell_colour = "medium"; }
			if($rating == "Low") { $issue_low[$device_count]++; $recommendation_table_rating_cell_colour = "low"; }
			$issue = $reports->report->part[1]->section[$section_recommendation]->table->tablebody->tablerow[$table_row]->tablecell[0]->item[0];
			$recommendation = $reports->report->part[1]->section[$section_recommendation]->table->tablebody->tablerow[$table_row]->tablecell[2]->item[0];
			$output_table_recommendation .= "<tr><td class='$recommendation_table_rating_cell_colour text-white'>$rating</td><td>$issue</td><td>$recommendation</td></tr>";
		}
		$table_row++;
	}

	$issue_total[$device_count] = $issue_critical[$device_count] + $issue_high[$device_count] + $issue_medium[$device_count] + $issue_low[$device_count];
	$total_critical += $issue_critical[$device_count];
	$total_high += $issue_high[$device_count];
	$total_medium += $issue_medium[$device_count];
	$total_low += $issue_low[$device_count];
	$total_all += $issue_total[$device_count];
	$device_issues_count_row = "<tr><td>1</td><td>". $device_type[$device_count] ."</td><td>". $device_name[$device_count] ."</td><td>". $issue_critical[$device_count] ."</td><td>". $issue_high[$device_count] ."</td><td>". $issue_medium[$device_count] ."</td><td>". $issue_low[$device_count] ."</td><td>". $issue_total[$device_count] ."</td></tr>";
	$all_devices_issues_count_row .= $device_issues_count_row;
	$final_output .= "<h2>".$device_name[$device_count]."</h2>";
	$final_output .= "<h3>Compliance Summary</h3>";
	$final_output .= "
		<table>
		<tr><th>No</th><th>Device Name</th><th class='critical'>Critical</th><th class='high'>High</th><th class='medium'>Medium</th><th class ='low'>Low</th><th>Total</th></tr>"
		. str_replace("<td>". $device_type[$device_count] ."</td>","",$device_issues_count_row) .
		"</table>
	";
	$final_output .= "<h3>Detailed Result</h3>";
	$final_output .= $issue_boxes;

	$final_output .= "<h3>Recommendation</h3>";
	$final_output .= "<table><tr><th>Rating</th><th>Issue</th><th>Recommendation</th></tr>" . $output_table_recommendation . "</table><br/>";
	$device_count++;
}
$summary_table = "<br/><br/><table><tr><th>No</th><th>Device Type</th><th>Device Name</th><th class='critical'>Critical</th><th class='high'>High</th><th class='medium'>Medium</th><th class ='low'>Low</th><th>Total</th></tr>$all_devices_issues_count_row<tr><td></td><td></td><td>Total</td><td>". $total_critical ."</td><td>". $total_high ."</td><td>". $total_medium ."</td><td>". $total_low ."</td><td>". $total_all ."</td></tr></table><br/><br/>";
$final_output = $head . $summary_table . $final_output;
$output_file = "Output_" . date("ymdHis") . ".html";
file_put_contents($output_file, $final_output);
?>
