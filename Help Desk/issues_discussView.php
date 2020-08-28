<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

@session_start() ;

include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

$allowed = relatedToIssue($connection2, $_GET["issueID"], $_SESSION[$guid]["gibbonPersonID"]);
if ((!hasTechnicianAssigned($connection2, $_GET["issueID"]) && isTechnician($connection2, $_SESSION[$guid]["gibbonPersonID"])) || getPermissionValue($connection2, $_SESSION[$guid]["gibbonPersonID"], "fullAccess")) {
    $allowed = true;
}

if (isModuleAccessible($guid, $connection2) == false || !$allowed) {
    //Acess denied
    $page->addError('You do not have access to this action.');
    exit();
} else {
    $issueID = $_GET["issueID"] ;
    $data = array("issueID" => $issueID) ;

    try {
        $sql = "SELECT helpDeskIssue.* , surname , preferredName , title FROM helpDeskIssue JOIN gibbonPerson ON (helpDeskIssue.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE issueID=:issueID " ;
        $result=$connection2->prepare($sql);
        $result->execute($data);

        $sql2 = "SELECT helpDeskTechnicians.*, surname , title, preferredName, helpDeskIssue.createdByID, helpDeskIssue.status AS issueStatus, privacySetting FROM helpDeskIssue JOIN helpDeskTechnicians ON (helpDeskIssue.technicianID=helpDeskTechnicians.technicianID) JOIN gibbonPerson ON (helpDeskTechnicians.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE issueID=:issueID " ;
        $result2 = $connection2->prepare($sql2);
        $result2->execute($data);
        $array2 = $result2->fetch();

        $sql3 = "SELECT issueDiscussID, comment, timestamp, gibbonPersonID FROM helpDeskIssueDiscuss WHERE issueID=:issueID ORDER BY timestamp ASC" ;
        $result3 = $connection2->prepare($sql3);
        $result3->execute($data);

        $sql4 = "SELECT surname , preferredName , title FROM helpDeskIssue JOIN gibbonPerson ON (helpDeskIssue.createdByID=gibbonPerson.gibbonPersonID) WHERE issueID=:issueID";
        $result4 = $connection2->prepare($sql4);
        $result4->execute($data);
        $row4 = $result4->fetch();
    } catch (PDOException $e) {
    }

    $privacySetting = $array2["privacySetting"];
    if ($array2["issueStatus"]=="Resolved" && !getPermissionValue($connection2, $_SESSION[$guid]["gibbonPersonID"], "fullAccess")) {
        if ($privacySetting == "No one") {
            $page->addError('You do not have access to this action.');
            exit();
        } else if ($privacySetting == "Related" && !relatedToIssue($connection2, $issueID, $_SESSION[$guid]["gibbonPersonID"])) {
            $page->addError('You do not have access to this action.');
            exit();
        }
        else if ($privacySetting == "Owner" && !isPersonsIssue($connection2, $issueID, $_SESSION[$guid]["gibbonPersonID"])) {
            $page->addError('You do not have access to this action.');
            exit();
        }
    }


    if (!isset($array2["gibbonPersonID"])) {
        $technicianName = "Unassigned" ;
    } else {
        $technicianName = formatName($array2["title"] , $array2["preferredName"] , $array2["surname"] , "Student", false, false);
    }

    $page->breadcrumbs->add(__('Discuss Issue'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (!isset($array2["technicianID"])) {
        $array2["technicianID"] = null;
    }

    if (technicianExists($connection2, $array2["technicianID"]) && !isPersonsIssue($connection2, $issueID, $_SESSION[$guid]["gibbonPersonID"]) && !getPermissionValue($connection2, $_SESSION[$guid]["gibbonPersonID"], "resolveIssue")) {
        if (!($array2["technicianID"] == getTechnicianID($connection2, $_SESSION[$guid]["gibbonPersonID"]))) {
            $page->addError('You do not have access to this action.');
            exit();
        }
    }

    $issueDiscussID = null;
    if (isset($_GET['issueDiscussID'])) {
        $issueDiscussID = $_GET['issueDiscussID'];
    }

    $tdWidth = "20%" ;

    $row = $result->fetch();

    $createdByShow = $row["createdByID"] != $row["gibbonPersonID"];
    if ($createdByShow) {
        $tdWidth = "16.7%";
    }

    $studentName = formatName($row["title"] , $row["preferredName"] , $row["surname"] , "Student", false, false);
    print "<h1>" . $row["issueName"] . "</h1>" ;
    print "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>" ;
        print "<tr>" ;
            print "<td style='width: " . $tdWidth . "; vertical-align: top'>" ;
                print "<span style='font-size: 115%; font-weight: bold'>" . __('ID') . "</span><br/>" ;
                print intval($issueID) ;
            print "</td>" ;
            print "<td style='width: " . $tdWidth . "; vertical-align: top'>" ;
                print "<span style='font-size: 115%; font-weight: bold'>" . __('Owner') . "</span><br/>" ;
                print $studentName ;
            print "</td>" ;
            print "<td style='width: " . $tdWidth . "; vertical-align: top'>" ;
                print "<span style='font-size: 115%; font-weight: bold'>" . __('Technician') . "</span><br/>" ;
                print $technicianName;
            print "</td>" ;
            print "<td style='width: " . $tdWidth . "; vertical-align: top'>" ;
                print "<span style='font-size: 115%; font-weight: bold'>" . __('Date') . "</span><br/>" ;
                $date2 =dateConvertBack($guid, $row['date']);
                if ($date2 == "30/11/-0001") {
                    $date2 = "No date";
                }
                print $date2 ;
            print "</td>" ;
            if ($createdByShow) {
                print "<td style='width: " . $tdWidth . "; vertical-align: top'>" ;
                    print "<span style='font-size: 115%; font-weight: bold'>" . __('Created By') . "</span><br/>" ;
                    print formatName($row4["title"] , $row4["preferredName"] , $row4["surname"] , "Student", false, false);
                print "</td>" ;
            }
            print "<td style='width: " . $tdWidth . "; vertical-align: top'>" ;
                print "<span style='font-size: 115%; font-weight: bold'>" . __('Privacy') . "</span><br/>" ;

                if (isPersonsIssue($connection2, $_GET["issueID"], $_SESSION[$guid]["gibbonPersonID"]) || getPermissionValue($connection2, $_SESSION[$guid]["gibbonPersonID"], "fullAccess")) {
                    print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/issues_discussEdit.php&issueID=". $_GET["issueID"] . "'>" .  __($row["privacySetting"]);
                } else {
                    print $row["privacySetting"];
                }
            print "</td>" ;
        print "</tr>" ;
    print "</table>" ;
    print "<h2 style='padding-top: 30px'>" . __('Description') . "</h2>" ;
    print "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>" ;
        print "<tr>" ;
            print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top'>". $row["description"] ."</td>" ;
        print "</tr>" ;

        if ($array2["technicianID"] == null && (!relatedToIssue($connection2, $_GET["issueID"], $_SESSION[$guid]["gibbonPersonID"]) || getPermissionValue($connection2, $_SESSION[$guid]["gibbonPersonID"], "fullAccess"))) {
            print "<tr>";
                print "<td class='right'>";
                    if (getPermissionValue($connection2, $_SESSION[$guid]["gibbonPersonID"], "acceptIssue") && !isPersonsIssue($connection2, $issueID, $_SESSION[$guid]["gibbonPersonID"])) {
                        print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/issues_acceptProcess.php?issueID=". $issueID . "'>" .  __('Accept');
                        print "<img title=" . __('Accept ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>";
                    }
                    if (getPermissionValue($connection2, $_SESSION[$guid]["gibbonPersonID"], "assignIssue")) {
                        print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/issues_assign.php&issueID=". $issueID . "'>" .  __('Assign');
                        print "<img style='margin-left: 5px' title=" . __('Assign ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>";
                    }
                print "</td>";
            print "</tr>";
        }
    print "</table>" ;

    if ($array2["technicianID"] != null) {
        print "<a name='discuss'></a>" ;
        print "<h2 style='padding-top: 30px'>" . __('Discuss') . "</h2>" ;
        print "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>" ;
            print "<tr>" ;
                print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top; max-width: 752px!important;' colspan=3>" ;
                    if ($array2["issueStatus"] != "Resolved") {
                        print "<div style='margin: 0px' class='linkTop'>" ;
                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/issues_discussView.php&issueID=" . $_GET["issueID"] . "'>" . __('Refresh') . "<img style='margin-left: 5px' title='" . __('Refresh') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/refresh.png'/></a>" ;
                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/issues_discussPost.php&issueID=" . $_GET["issueID"] . "'>" .  __('Add') . "<img style='margin-left: 5px' title='" . __('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>";
                            if (getPermissionValue($connection2, $_SESSION[$guid]["gibbonPersonID"], "resolveIssue") || isPersonsIssue($connection2, $issueID, $_SESSION[$guid]["gibbonPersonID"])) {
                                print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/issues_resolveProcess.php?issueID=". $_GET["issueID"] . "'>" .  __('Resolve');
                                print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/issues_resolveProcess.php?issueID=". $_GET["issueID"] . "'><img title=" . __('Resolve ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a>";
                            }
                        print "</div>" ;
                    }
                    
                        while ($row3 = $result3->fetch()){
                            $bgc = "#EDF7FF";
                            if (!isPersonsIssue($connection2, $issueID, $row3["gibbonPersonID"])) {
                                $bgc = "#FFEDFE";
                            }
                            if ($row3['issueDiscussID'] == $issueDiscussID) {
                                $bgc = "#FFE3E3";
                            }
                            print "<table class='noIntBorder' cellspacing='0' style='width: 100% ; padding: 1px 3px; margin-bottom: -2px; margin-top: 50; margin-left: 0px ; background-color: #f9f9f9'>" ;
                                print "<tr>" ;
                                    if (isPersonsIssue($connection2, $issueID, $row3["gibbonPersonID"])) {
                                        print "<td style='width: 12%; background-color:" . $bgc . "; color: #777'><i>". $studentName . " " . __('said') . "</i>:</td>" ;
                                    } else {
                                        $techName = $technicianName;
                                        if (getTechWorkingOnIssue($connection2, $issueID)["personID"] != $row3["gibbonPersonID"]) {
                                            $data2=array("gibbonPersonID"=>$row3["gibbonPersonID"]) ;

                                            try {
                                                $sql5="SELECT surname, preferredName, title FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
                                                $result5=$connection2->prepare($sql5);
                                                $result5->execute($data2);
                                                $row5 = $result5->fetch();
                                            } catch (PDOException $e) {
                                            }

                                            $techName = formatName($row5["title"] , $row5["preferredName"] , $row5["surname"] , "Student", false, false);
                                        }
                                        print "<td style='width: 12%; background-color:" . $bgc . "; color: #777'><i>". $techName . " " . __('said') . "</i>:</td>" ;
                                    }
                                    print "<td style='background-color:" . $bgc . ";'><div>" . $row3["comment"] . "</div></td>" ;
                                    print "<td style='width: 15%; background-color:" . $bgc . "; color: #777; text-align: right'><i>" . __('Posted at') . " <b>" . substr($row3["timestamp"],11,5) . "</b> on <b>" . dateConvertBack($guid, $row3["timestamp"]) . "</b></i></td>" ;
                                print "</tr>" ;
                            print "</table>" ;
                        }
                    
                print "</td>" ;
            print "</tr>" ;
        print "</table>" ;
    }
}
?>