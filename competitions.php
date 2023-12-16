/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
?>
<style>
	table#upcoming {
		width: 100%;
	}
	div#upcoming-container {
		overflow: auto;
	}
	td.not-full {
		background-color: #d9ead3; 
	}
	td.almost-full {
		background-color: #fff2cc;
	}
	td.full {
		background-color: #f4cccc;
	}
	th {
		background-color: #dddddd;
	}
</style>
<link rel="stylesheet" href="https://icons.cubing.net/css/cubing-icons.css">
<div id="upcoming-container">
<table id="upcoming">
	<tr>
		<th>Competition</th>
		<th>Events</th>
		<th>Date</th>
		<th>Location</th>
		<th>Registration</th>
	</tr>

<?php

function sortComps($a, $b) {
	if ($a['date']['from'] == $b['date']['from']) {
		return 0;
	}
	return ($a['date']['from'] < $b['date']['from']) ? -1 : 1;
}
	
function isRegistered($person) {
	return $person['registration']['status'] == "accepted";
}
	
$now = new DateTime("now", new DateTimeZone("America/New_York"));
$month = $now->format('m');
$year = $now->format('Y');

for ($i = 0; $i < 12; $i++) {
	$contents = file_get_contents('https://raw.githubusercontent.com/robiningelbrecht/wca-rest-api/master/api/competitions/'.$year.'/'.str_pad(strval($month), 2, "0", STR_PAD_LEFT).'.json', false);
	if (!$contents) {
		continue;
	}
	$comps = json_decode($contents, true)["items"];
	uasort($comps, 'sortComps');
	foreach ($comps as $comp) {
		$isCNE = false;
		foreach ($comp['organisers'] as $organizer) {
			if ($organizer['email'] == 'wca-contact@cubenewengland.com') {
				$isCNE = true;
			}
		}
		if (!$isCNE) {
			continue;
		}
		$startDate = DateTime::createFromFormat('Y-m-d', $comp['date']['from']);
		$endDate = DateTime::createFromFormat('Y-m-d', $comp['date']['till']);
		if ($endDate->getTimestamp() < $now->getTimestamp()) {
			continue;
		}
		$wcifContent = file_get_contents("https://api.worldcubeassociation.org/competitions/".$comp['id']."/wcif/public");
		if (!$wcifContent) {
			continue;
		}
		$wcif = json_decode($wcifContent, true);
		$competitorLimit = $wcif['competitorLimit'];
		$registered = count(array_filter($wcif['persons'], 'isRegistered'));
		?>
		<tr>
			<td class="comp-name-cell">
				<a href="https://wca.link/<?php echo $comp['id']; ?>">
					<?php echo $comp['name']; ?>
				</a>
			</td>
			<td class="events-cell">
				<nobr>
				<?php
					$eventsShown = 0;
					foreach(Array("333" => "3x3x3 Cube",
								  "222" => "2x2x2 Cube",
								  "444" => "4x4x4 Cube",
								  "555" => "5x5x5 Cube",
								  "666" => "6x6x6 Cube",
								  "777" => "7x7x7 Cube",
								  "333bf" => "3x3x3 Blindfolded",
								  "333fm" => "3x3x3 Fewest Moves",
								  "333oh" => "3x3x3 One-Handed",
								  "clock" => "Clock",
								  "minx" => "Megaminx",
								  "pyram" => "Pyraminx",
								  "skewb" => "Skewb",
								  "sq1" => "Square-1",
								  "444bf" => "4x4x4 Blindfolded",
								  "555bf" => "5x5x5 Blindfolded",
								  "333mbf" => "3x3x3 Multi-Blind") as $eventId => $eventName) {
						if (in_array(strval($eventId), $comp['events'], true)) {
							?><span class="cubing-icon event-<?php echo $eventId; ?>" title="<?php echo $eventName; ?>"></span>&nbsp;<?php
							
							$eventsShown++;
						}
					}
				?>
				</nobr>
			</td>
			<td class="comp-date-cell">
				<?php
					if ($startDate != $endDate) {
						echo str_replace(" 0", " ", $startDate->format('F d').' - '.$endDate->format('F d, Y'));
					} else {
						echo str_replace(" 0", " ", $startDate->format('F d, Y'));
					}
				?>
			</td>
			<td class="comp-location-cell">
				<?php
					list($city, $stateName) = explode(", ", $comp['city']);
					$states = Array(
						"Massachusetts" => "MA",
						"Vermont" => "VT",
						"New Hampshire" => "NH",
						"Rhode Island" => "RI",
						"Connecticut" => "CT",
						"Maine" => "ME",
					);
					echo $city.", ".$states[trim($stateName)]; 
				?>
			</td>
			<td class="<?php
				if ($registered >= $competitorLimit) {
					echo "full";
				} else if ($registered >= $competitorLimit * 0.75) {
					echo "almost-full";
				} else {
					echo "not-full";
				}
			?>">
				<?php echo $registered." / ".$competitorLimit; ?>
			</td>
		</tr>
		<?php
	}
	
	$month += 1;
	if ($month > 12) {
		$month = 1;
		$year += 1;
	}
}
?>
</table>
</div>
