<?php

$handle=fopen("./restore_list.csv", "r");

$zids=fgetcsv($handle,",");
fclose($handle);

//echo " 1";
$backup=new memcache();
//restore from storagebox1 to storagebox2
$backup->connect("storagebox1",11211);

$to_restore=new memcache();
$to_restore->connect("storagebox2",11211);


foreach($zids as $zid ){
   
        $tempblob=$backup->get($zid);
        if($tempblob ==FALSE ){
                echo"$zid FAILED KEY NOT FOUND ON BACKUP \n";
                break;
        }

        if($to_restore->get($zid) !=FALSE){

                $to_restore->set($userkey,$tempblob,MEMCACHE_COMPRESSED_LZO);
                echo "$zid SUCCESS\n ";
        }
        else echo " $zid FAILED KEY NOT FOUND ON OLD \n";

}
?>
