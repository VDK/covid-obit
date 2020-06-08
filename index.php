<?php
include_once 'person.php';
include_once 'reference.php';
include_once 'region.php';
$error = '';
$qs = false;
$region = new Region('Q81068910'); //19-20 coronavirus pandemic

if(isset($_COOKIE['selected_region'])){
  $region->setQID($_COOKIE['selected_region']);
}
if (isset($_POST['fullname'])){
  $region->setQID($_POST['selected_region']);

  setcookie("selected_region", $region->getQID());

  $reference1 = new Reference(
    $_POST['ref_url'], 
    $_POST['ref_lang'],
    $_POST['ref_authors'], 
    $_POST['ref_title'], 
    $_POST['ref_pubdate']);
  $today = new DateTime('now');
  $loopDate = new DateTime('now');
  if ($reference1->getPubDate() != null){
    //shift "today" to pubDate
    $today = new DateTime(date('Y-m-d',$reference1->getPubDate()));
  }

  //handle person
  $person1 = new Person();
  $person1->setQID($_POST['person_QID']);
  $person1->setName($_POST['fullname']);
  $person1->setAge($_POST['age']);
  $person1->setDescription($_POST['description']);
  
  //set Date of Death
  $dod = " ".trim(strip_tags($_POST['dod']));
  if ($dod != " "){
    // makes in possible to input "last friday -1 weeks"
    if (!strripos("last ", $dod)){ 
      for ( $days = 7;  $days--;) {
        $dayOfWeek = $loopDate->modify( '+1 days' )->format( 'l' );
        if (strripos($dod, $dayOfWeek) != false){
          if ($dayOfWeek == $today->format('l')){
            $person1->setDOD($today->format("Y-m-d"));
          }
          else{
            $person1->setDOD ('last '.$dayOfWeek." ".$today->format("Y-m-d"));
          }
        }
      }
    }
    if(preg_match('/\d/', $dod) && strtotime($dod) != false){
      $person1->setDOD($dod);
    }
    else{
      //"if MONTH string detected take 'last MONTH' as dod"
      for ($months=0; $months < 11; $months++) { 
         $month = $loopDate->modify( '-1 months' )->format( 'F' );
         if (strripos($dod, $month) != false){
            $date = new DateTime($month." ".$today->format("Y"));
            if ($date > $today){
              $date->modify('-1 years');
            }
            $person1->setDOD($date->format("Y-m-d"), 10);
         }
      }
    }
    if (preg_match('/^ [21]\d{3}$/', $dod)){
      $person1->setDOD($dod."-01-01", 9);
    }
  }
  //end DOD
  //DOB
  $dob = trim(strip_tags($_POST['dob']));
  if (preg_match('/^[12]\d{3}$/', $dob)){
    $person1->setDOB($dob."-01-01", 9);
  }
  elseif(strtotime($dob) != false){
    $person1->setDOB($dob);
  }
  //reduce accuracy if only month + year:
  for ($months=0; $months < 11; $months++) { 
     $month = $loopDate->modify( '-1 months' )->format( 'F' );
     if (preg_match('/^'.$month.' [12]\d{3}$/i' , $dob)  
      || preg_match('/^[12]\d{3}\s*'.$month.'$/i' , $dob)  ){
        $person1->setDOB($dob, 10);
     }
  }
  
  
	if (!$person1->getQID()){
    $qs .= "CREATE
LAST|Len|\"".$person1->getName()."\"
LAST|Lde|\"".$person1->getName()."\"
LAST|Lfr|\"".$person1->getName()."\"
LAST|Lnl|\"".$person1->getName()."\"
LAST|Den|\"".$person1->getDescription()."\"
LAST|P31|Q5
LAST|".$region->getNationality('qs');
  }
  $qs .= 
   appendProp($person1->getQID(), $person1->getName('qs'))
  .appendProp($person1->getQID(), "P793|".$region->getQID(), $reference1->getQS()) 
  .appendProp($person1->getQID(), $person1->getDOB('qs'), $reference1->getQS())
  .appendProp($person1->getQID(), $person1->getDOD('qs'), $reference1->getQS())
  .appendProp($person1->getQID(), $reference1->getDescribedAtUrlQS())
  .appendProp($person1->getQID(), "P1196|Q3739104", $reference1->getQS()) //manner of death = natural
  .appendProp($person1->getQID(), "P509|Q84263196", $reference1->getQS()) //cause  of death = COVID-19
  .appendProp($person1->getQID(), "P1050|Q84263196|P1534|Q4|P582|+".date('Y-m-d', $person1->getDOD()).'T00:00:00Z/'.$person1->getDODAccuracy(), $reference1->getQS());//medical condition = COVID-19

  

}

function appendProp($qid = null, $prop = null, $ref = null){
  $qs = '';
  if ($qid == null){
    $qid = "LAST";
  }
  $propLines = explode("\n", $prop);
  foreach ($propLines as $propLine) {
    if (trim($propLine) != ''){
      $qs .= "\n".$qid."|".$propLine.$ref;
    }
  }
  return $qs;

}

?>
<!DOCTYPE html>
<html lang="en">
<head>

  <!-- Basic Page Needs
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta charset="utf-8">
  <title>covid-obid</title>
  <meta name="description" content="">
  <meta name="author" content="1Veertje">

  <meta http-equiv="Content-type" content="text/html; charset=utf-8" />

  <!-- Mobile Specific Metas
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- FONT
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <!-- <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css"> -->

  <!-- CSS
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="stylesheet" href="css/normalize.css">
  <link rel="stylesheet" href="css/skeleton.css">
  <link rel="stylesheet" href="css/style.css">

 <!--  Favicon
  ––––––––––––––––––––––––––––––––––––––––––––––––––
  <link rel="icon" type="image/png" href="images/favicon.png"> -->
 
 <!-- Javascripts
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<script type="text/javascript" src="script.js"></script>
</head>
<body>

  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <div class="container">
    <div class="row">
        <form class="form-wrapper"  method="POST"  target='_self' id="form"	>
			<div class="error"><?php echo $error; ?></div>
		   <div> 
        <div class="flex">
          <h1 id="region_label"><?php echo $region->getLabel();?></h1>
          <button type="button" id="up_button" class="<?php echo ($region->getParentQID() == null) ? "hidden": "";?>">up</button>
        </div>
        <input type="hidden" id="selected_region" name="selected_region" value="<?php echo $region->getQID();?>">
        <input type="hidden" id="parent" name="parent" value="<?php echo $region->getParentQID();?>">
        <div id="region_selection" class="<?php echo (count($region->getParts()) > 0)? "" : "hidden";?>">
        <label for="region">Choose a region:</label>
          <select id="region">
            <option/>
           <?php
           foreach ($region->getParts() as $qid => $label) {
             echo "<option value='".$qid."'>".$label."</option>\n";
           }
           if ($region->getQID() == 'Q81068910'){
            echo "<option value='Q83873577'>United States</option>\n";
           }
           ?>
         </select>
       </div>
        <p>
        Personal details:
        <div>
        <div class="flex">
        <div style="width: 100%"><label for="fullname">name</label>
        <input type="text" id="fullname"  name='fullname' ></div>

        <div><label for="age">age</label>
        <input type="text" id="age"  name='age' style="width:100px;"></div>
      </div>
        <label for="dod">date of death</label>
        <input type="text" id="dod"  name='dod' placeholder="Recently" >
        <label for="dob">date of birth</label>
        <input type="text" id="dob"  name='dob' placeholder="DOD - AGE" >
        <label for="description">short description</label>
        <input type="text" id="description" name='description' >
        </div>
        <div id='possible_match' >
          <input type="hidden" name="person_QID" id="person_QID">
          <ul id='responses' ></ul>
        </div>

        Reference:
          <label for="ref_url">URL</label>
          <input type="text" id="ref_url" placeholder="https://" name='ref_url' >

          <div id="ref_params" >
            <label for="ref_title">title</label>
            <div class="flex">
              <input type="text" id="ref_lang" placeholder="lang" name="ref_lang" style="width:50px" maxlength="3" value="en">
              <input type="text" id="ref_title" name="ref_title" >
            </div>
            <label for="ref_pubdate">publcation date</label>
            <input type="date" id="ref_pubdate"  name="ref_pubdate" >
            <label for="ref_authors">author</label>
            <input type="text" id="ref_authors"  name="ref_authors" >
          </div>
          <span style="clear:both;"/>
		    <input type="submit" class='button' value="go" id="submit">
		</div> 	
		</form>
	</div>

<?php
if ($qs ){?>
   <div class="form-wrapper">
<textarea id='quickstatement' style="height: 150px;" >
<?php echo($qs);	?>
</textarea>
<a onclick="sendQS()" style="cursor: pointer;">Import QuickStatement</a>
	</div>
<?php } ?>

</div>
<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->

	
</body>

</html>