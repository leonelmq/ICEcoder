<?php
session_start();
if (!$_SESSION['loggedIn']) {
	die("Sorry, you need to be logged in to use ICErepo");
}

function strClean($var) {
	// returns converted entities where there are HTML entity equivalents
	return htmlentities($var, ENT_QUOTES, "UTF-8");
}

function numClean($var) {
	// returns a number, whole or decimal or null
	return is_numeric($var) ? floatval($var) : false;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>ICErepo v<?php echo $version;?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script src="lib/base64.js"></script>
<script src="lib/github.js"></script>
<script src="lib/difflib.js"></script>
<script src="ice-repo.js"></script>
<link rel="stylesheet" type="text/css" href="ice-repo.css">
</head>

<body>
	
<?php
// Function to sort given values alphabetically
function alphasort($a, $b) {
	return strcmp($a->getPathname(), $b->getPathname());
}

// Class to put forward the values for sorting
class SortingIterator implements IteratorAggregate {
	private $iterator = null;
	public function __construct(Traversable $iterator, $callback) {
		$array = iterator_to_array($iterator);
		usort($array, $callback);
		$this->iterator = new ArrayIterator($array);
	}
	public function getIterator() {
	return $this->iterator;
	}
}

// Get a full list of dirs & files and begin sorting using above class & function
$repoPath = explode("@",strClean($_POST['repo']));
$repo = $repoPath[0];
$path = $repoPath[1];
$objectList = new SortingIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST), 'alphasort');

// Finally, we have our ordered list, so display
$i=0;
$dirListArray = array();
$dirSHAArray = array();
$dirTypeArray = array();
$finfo = finfo_open(FILEINFO_MIME_TYPE);
foreach ($objectList as $objectRef) {
	$fileFolderName = rtrim(substr($objectRef->getPathname(), strlen($path)),"..");
	if ($objectRef->getFilename()!="." && $fileFolderName[strlen($fileFolderName)-1]!="/") {
			$contents = file_get_contents($path.$fileFolderName);
			if (strpos(finfo_file($finfo, $path.$fileFolderName),"text")===0) {
				$contents = str_replace("\r","",$contents);
			};
			$store = "blob ".strlen($contents)."\000".$contents;
			$i++;
			array_push($dirListArray,ltrim($fileFolderName,"/"));
			array_push($dirSHAArray,sha1($store));
			$type = is_dir($path.$fileFolderName) ? "dir" : "file";
			array_push($dirTypeArray,$type);
	}
}
finfo_close($finfo);
?>

<script>
top.repo = '<?php echo $repo;?>';
top.path = '<?php echo $path;?>';
dirListArray = [<?php echo "'".implode("','", $dirListArray)."'";?>];
dirSHAArray  = [<?php echo "'".implode("','", $dirSHAArray)."'";?>];
dirTypeArray = [<?php echo "'".implode("','", $dirTypeArray)."'";?>];
</script>
	
<div id="compareList" class="mainContainer"></div>
	
<div id="commitPane" class="commitPane">
<b style='font-size: 18px'>COMMIT CHANGES:</b><br><br>
<form name="fcForm" action="file-control.php" target="fileControl" method="POST">
<input type="text" name="title" value="Title..." style="width: 260px; border: 0; background: #f8f8f8; margin-bottom: 10px" onFocus="titleDefault='Title...'; if(this.value==titleDefault) {this.value=''}" onBlur="if(this.value=='') {this.value=titleDefault}"><br>
<textarea name="message" style="width: 260px; height: 180px; border: 0; background: #f8f8f8; margin-bottom: 5px" onFocus="messageDefault='Message...'; if(this.value==messageDefault) {this.value=''}" onBlur="if(this.value=='') {this.value=messageDefault}">Message...</textarea>
<input type="hidden" name="token" value="<?php echo strClean($_POST['token']);?>">
<input type="hidden" name="username" value="<?php echo strClean($_POST['username']);?>">
<input type="hidden" name="password" value="<?php echo strClean($_POST['password']);?>">
<input type="hidden" name="path" value="<?php echo $path; ?>">	
<input type="hidden" name="rowID" value="">
<input type="hidden" name="gitRepo" value="<?php echo $repo; ?>">
<input type="hidden" name="repo" value="">
<input type="hidden" name="dir" value="">
<input type="hidden" name="action" value="">
<input type="submit" name="commit" value="Commit changes" onClick="return commitChanges()" style="border: 0; background: #555; color: #fff; cursor: pointer">
</form>
</div>
	
<div id="infoPane" class="infoPane"></div>
	
<script>
top.fcFormAlias = document.fcForm;
var github = new Github(<?php
if ($_POST['token']!="") {
	echo '{token: "'.strClean($_POST['token']).'", auth: "oauth"}';
} else{
	echo '{username: "'.strClean($_POST['username']).'", password: "'.strClean($_POST['password']).'", auth: "basic"}';
}?>);
repoListArray = [];
repoSHAArray = [];
window.onLoad=gitCommand('repo.show','<?php echo strClean($_POST['repo']);?>');
</script>
	
<iframe name="fileControl" style="display: none"></iframe>
	
</body>
	
</html>