<?php
	// Start the session
	session_start();

	// Insert the page header
	//$page_title = 'Welcome!';
	require_once('header.php');

	require_once('connectvars.php');
	$dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	// Show the navigation menu
	  require_once('navmenu.php');

   
$uid = $_SESSION['user_id'];


// the following code is the grad audit
$check = true;
// gets number of courses in form 1 that do not exist in transcript. Sudent cannot graduate if any exist.
$formcourses = mysqli_query($dbc, "select count(a.cno) from form1 a, transcript b where b.uid = '$uid' and a.uid = b.uid and (a.dept, a.cno) not in (select dept, cno from transcript where uid = '$uid');");
$fc = mysqli_fetch_array($formcourses);
if (!empty($fc)) {
    $fcourses = $fc[0];
    if ($fcourses > 0) {
        $check = false;
	    echo 'ERROR: Form1 check failed<br>';
    }
}
// checks if form 1 was approved
$form1info = mysqli_query($dbc, "select form1status from student where uid = '$uid';");
$f1 = mysqli_fetch_array($form1info);
if (!empty($f1)) {
    $form1 = $f1[0];
    if ($form1 != 2) {
        $check = false;
	    echo 'ERROR: Form1status check failed<br>';
    }
}
// finds the degree of the student
$dtype = mysqli_query($dbc, "select degree from student where uid = '$uid';");
$dt = mysqli_fetch_array($dtype);
    if (!empty($dt)) {
        $degree = $dt[0];
    }
    else {
        echo 'Error: could not determine degree type<br>';
    }



if ($degree == 'MS') {
    // makes sure gpa isn't below a 3.0
    $gpainfo = mysqli_query($dbc, "select gpa from student where uid = '$uid';");
    $g = mysqli_fetch_array($gpainfo);
    if (!empty($g)) {
        $gpa = $g[0];
        if ($gpa < 3.0) {
		$check = false;
		echo 'Error: gpa below 3.0';
	}
    }
    else {
        echo 'Error: could not determine gpa<br>';
    }
    // makes sure transcript meets course requirements
    $transcriptinfo = mysqli_query($dbc, "select dept, cno from transcript where uid = '$uid' and dept = 'CSCI' and cno = 6212;");
    $t = mysqli_fetch_array($transcriptinfo);
    if (empty($t)) {
        $check = false;
	    echo 'ERROR: Course req check failed<br>';
    }
    $transcriptinfo = mysqli_query($dbc, "select dept, cno from transcript where uid = '$uid' and dept = 'CSCI' and cno = 6221;");
    $t = mysqli_fetch_array($transcriptinfo);
    if (empty($t)) {
        $check = false;
	    echo 'ERROR: Course req check failed<br>';
    }
    $transcriptinfo = mysqli_query($dbc, "select dept, cno from transcript where uid = '$uid' and dept = 'CSCI' and cno = 6461;");
    $t = mysqli_fetch_array($transcriptinfo);
    if (empty($t)) {
        $check = false;
	    echo 'ERROR: Course req check failed<br>';
    }
    // makes sure student has taken at least 30 credit hours
    $credithours = mysqli_query($dbc, "select sum(credits) from transcript a, courses b where a.uid = '$uid' and a.dept = b.dept and a.cno = b.cno;");
	$c = mysqli_fetch_array($credithours);
    if (!empty($c)) {
        $credits = $c[0];
        if ($credits < 30) {
            $check = false;
		echo 'ERROR: Credit check failed<br>';
        }
    }	
    else {
        echo 'Error: could not determine credit hours<br>';
    }
    // makes sure student doesn't have more than 2 grades below a B
    $gradesbelowb = mysqli_query($dbc, "select count(grade) from transcript where uid = '$uid' and grade not in (select grade from transcript where uid = '$uid' and (grade = 'A' or grade = 'B' or grade = 'IP'));");
	$grade = mysqli_fetch_array($gradesbelowb);
    if (!empty($grade)) {
        $grades = $grade[0];
        if ($grades > 2) {
            $check = false;
		echo 'ERROR: Min grades req check failed<br>';
        }
    }	
    else {
        echo 'Error: could not determine grades<br>';
    }
    if ($check == true){
        // sets gradapp attribute to 2, which signifies that the student needs to be approved to graduate by grad secretary
        $apply = "update student set gradapp = 2 where uid = '$uid';";
        mysqli_query($dbc, $apply);   
        echo '<br>Application sent in.';
    }
    else {
	echo '<br>Application revoked.';
    }
}
// if student is a phd student
else {
    // makes sure gpa isn't below a 3.5
    $gpainfo = mysqli_query($dbc, "select gpa from student where uid = '$uid';");
    $g = mysqli_fetch_array($gpainfo);
    if (!empty($g)) {
        $gpa = $g[0];
        if ($gpa < 3.5) {
            $check = false;
        }
    }
    else {
        echo 'Error: could not determine gpa<br>';
    }
    // makes sure student has taken at least 36 credit hours
    $credithours = mysqli_query($dbc, "select sum(credits) from transcript a, courses b where a.uid = '$uid' and a.dept = b.dept and a.cno = b.cno;");
	$c = mysqli_fetch_array($credithours);
    if (!empty($c)) {
        $credits = $c[0];
        if ($credits < 36) {
            $check = false;
        }
    }	
    else {
        echo 'Error: could not determine gpa<br>';
    }
    // makes sure student has taken at least 30 credit hours in CSCI courses
    $corecredithours = mysqli_query($dbc, "select sum(credits) from transcript a, courses b where a.uid = '$uid' and b.dept = 'CSCI' and a.dept = b.dept and a.cno = b.cno;");
	$cc = mysqli_fetch_array($corecredithours);
    if (!empty($cc)) {
        $corecredits = $cc[0];
        if ($corecredits < 30) {
            $check = false;
        }
    }	
    else {
        echo 'Error: could not determine gpa<br>';
    }
    // makes sure student doesn't have more than 1 grade below a B
    $gradesbelowb = mysqli_query($dbc, "select count(grade) from transcript where uid = '$uid' and grade not in (select grade from transcript where uid = '$uid' and (grade = 'A' or grade = 'B' or grade = 'IP'));");
	$grade = mysqli_fetch_array($gradesbelowb);
    if (!empty($grade)) {
        $grades = $grade[0];
        if ($grades > 1) {
            $check = false;
        }
    }	
    else {
        echo 'Error: could not determine grades<br>';
    }
    if ($check == true) {
        // sets the gradapp attribute to 1, which signifies that the student has applied but still needs their thesis approved
        $apply = "update student set gradapp = 1 where uid = '$uid';";
        mysqli_query($dbc, $apply);
    	echo '<br>Application sent in. Awaiting approval of thesis.';
    }
    else {
	echo '<br>Application revoked.';
    }
}
echo '<br>';
echo '<br>';

echo '<form><input type="button" class="button" value="Return to previous page" onClick="javascript:history.go(-1)"></form>';


    require_once('footer.php');
?>
