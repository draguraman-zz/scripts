<?php

$handle=fopen("./restore_list_stage.csv", "r");

$zids=fgetcsv($handle,",");
  fclose($handle);

while ($zids != FALSE)
{
   var_dump($zids); 
}

$backup=new memcache();
$backup->connect("citymobile-mb-user2-new",11211);

//For Prod
/*$to_restore=new memcache();
$to_restore->connect("citymobile-mb-user2-a-10",11211); */

//for testing Restoring

$to_restore_stage=new memcache();
$to_restore_stage->connect("citymobile-staging-mb-user-01",11211);

    foreach($zids as $zid ){
    $flags=0;
    $userkey="city_USER_".$zid;
    $tempblob=$backup->get($userkey,&$flags);
    if($tempblob ==FALSE){
        error_log("The user blob  $zid could not be fetched");
    break;        
    }
    echo "The Flags for the MBs are ".$flags;

    //Restoring below to stage first for testing
   
    $stageuserkey="citymobile_stage_USER_".$zid;
    if($to_restore_stage->get( $stageuserkey) !=FALSE){
    $to_restore_stage->set( $stageuserkey,$tempblob,$flags);
    echo "Restore Complete for $zid";
    }
    else echo " The user Key could not be SET because key doesnt exist in reciepient";
    
    
    //Restoring to the actual MB-a-10
    /*
    $to_restore->set($userkey,$tempblob,$flags);
    
    echo " $zid was succesfully restored";
    */
    }

?>