<?php
set_time_limit(0);
function getGuid() {
  return loadDoc(doCurl("https://www.pivotaltracker.com/services/v3/tokens/active", "POST", array('username'=>$_POST['uname'], 'password'=>$_POST['pword'])))->getElementsByTagName('guid')->item(0)->nodeValue;
}
function getProjects() {
  $urlProjectId = ((isset($_GET['project_id'])) ? ($_GET['project_id']) : (null));
  foreach (loadDoc(doCurl("http://www.pivotaltracker.com/services/v4/projects", "GET", null))->getElementsByTagName('project') as $project) {
    $projectId = $project->getElementsByTagName('id')->item(0)->nodeValue;
    $selected = '';
    if ($projectId == $urlProjectId) {
      $urlProjectName = $project->getElementsByTagName('name')->item(0)->nodeValue;
      $selected = ' selected="selected"';
    }
?>
          <option value="<?php echo $projectId; ?>"<?php echo $selected; ?>><?php echo $project->getElementsByTagName('name')->item(0)->nodeValue; ?></option>
<?php
  }
?>
        </select>
      </label>
      <input id="loadProject" type="submit" value="Load" />
    </form>
    <script type="application/x-javascript">
      document.getElementById('loadProject').style.display="none";
    </script>
    <hr />
<?php
  if ($urlProjectId != null) {
?>
    <h3><?php echo $urlProjectName; ?></h3>
    <p>
      <a href="/plight/index.php?project_id=<?php echo $urlProjectId; ?>&amp;filter=type:bug">Bugs</a> 
<?php
    $label = strtok(loadDoc(doCurl("http://www.pivotaltracker.com/services/v4/projects/".$urlProjectId, "GET", null))->getElementsByTagName('labels')->item(0)->nodeValue, ',');
    $count = 0;
    while ($label !== false) {
      if (strpos($label, "[sprint") !== false) {
        $count = (($count < intval(substr($label, 7, -1))) ? (intval(substr($label, 7, -1))) : ($count));
      }
      $label = strtok(',');
    }
    for ($i = 1; $i < $count+1; $i++) {
?>
      | <a href="/plight/index.php?project_id=<?php echo $urlProjectId; ?>&amp;filter=label:[sprint<?php echo $i; ?>]">Sprint <?php echo $i; ?></a>
<?php
    }
?>
    </p>
<?php
  }
  else {
?>
    <p>Please select a project.</p>
<?php
  }
}
function doCurl($uri, $method, $data) {
  $curlHandler = curl_init();
  curl_setopt($curlHandler, CURLOPT_URL, $uri);
  curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
  if ($method == "POST") {
    curl_setopt($curlHandler, CURLOPT_POST, true);
    curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $data);
  }
  if (isset($_SESSION['guid'])) {
    curl_setopt($curlHandler, CURLOPT_HTTPHEADER, array('X-TrackerToken: '.$_SESSION['guid']));
  }
  curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, true);
  // fix for invalid CA cert when validating
  curl_setopt($curlHandler, CURLOPT_CAINFO, 'mozilla.pem');
  $output = curl_exec($curlHandler);
  curl_close($curlHandler);
  return $output;
}
function loadDoc($output) {
  $doc = null;
  if ($output == null || $output == '') {
?>
    <p>No information can be displayed because data is null.</p>
<?php
  }
  else {
    $doc = new DOMDocument();
    $doc->loadXML($output);
  }
  return $doc;
}
function getStories($projectId) {
  if ($projectId != null) {
?>
    <table>
<?php
    foreach (loadDoc(doCurl("http://www.pivotaltracker.com/services/v3/projects/".$projectId."/stories?filter=".((!isset($_GET['filter'])) ? ('label:[sprint1]') : (urlencode($_GET['filter']))), "GET", null))->getElementsByTagName('story') as $story) {
      $storyId = $story->getElementsByTagName('id')->item(0)->nodeValue;
      $estimate = (($story->getElementsByTagName('estimate')->length > 0) ? ($story->getElementsByTagName('estimate')->item(0)->nodeValue) : ($story->getElementsByTagName('story_type')->item(0)->nodeValue));
      $currentState = $story->getElementsByTagName('current_state')->item(0)->nodeValue;
      if ($estimate == "bug") {
        $currentState .= " bug";
        $estimate = "BUG";
      }
      elseif ($estimate == "chore") {
        $currentState .= " chore";
      }
?>
      <tr class="<?php echo $currentState; ?>">
        <td><a href="<?php echo $story->getElementsByTagName('url')->item(0)->nodeValue; ?>" target="_blank"><?php echo $storyId; ?></a></td>
        <td><?php echo str_replace(array("&", "<"), array("&amp;", "&lt;"), $story->getElementsByTagName('name')->item(0)->nodeValue); ?></td>
        <td><?php echo $estimate; ?></td>
<?php
      getStoryActivity($projectId, $storyId);
?>
      </tr>
<?php
    }
?>
    </table>
<?php
  }
}
function getStoryActivity($projectId, $storyId) {
  $activityArray = array();
  foreach (loadDoc(doCurl("http://www.pivotaltracker.com/services/v4/projects/".$projectId."/stories/".$storyId."/activities", "GET", null))->getElementsByTagName('activity') as $activity) {
    $currentState = (($activity->getElementsByTagName('current_state')->length > 0) ? ($activity->getElementsByTagName('current_state')->item(0)->nodeValue) : (null));
    $id = $activity->getElementsByTagName('id')->item(0)->nodeValue;
    if ($currentState != null && $currentState != '' && $currentState != "unscheduled") {
      $activityArray[$id]['occurred_at'] = $activity->getElementsByTagName('occurred_at')->item(0)->nodeValue;
      $activityArray[$id]['current_state'] = $currentState;
      $activityArray[$id]['initials'] = $activity->getElementsByTagName('initials')->item(0)->nodeValue;
    }
  }
  sort($activityArray);
  foreach ($activityArray as $activity) {
?>
        <td class="<?php echo $activity['current_state']; ?>"><?php echo $activity['current_state']; ?>: <?php echo date('Y-M-d H:i', strtotime($activity['occurred_at'])); ?> (<?php echo $activity['initials']; ?>)</td>
<?php
  }
}
?>
