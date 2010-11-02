#!/usr/bin/php
<?php
function get_oplist($file) {
  $tmp=file_get_contents($file);
  $tmp=explode("\n", $tmp);
  $num=intval(array_shift($tmp));
  $ops=array();
  while ($val=array_shift($tmp)) {
    $ops[]=$val;
  }
  return array('num'=>$num,'ops'=>$ops);
}

$num_of_ops = 5;

$min_votes = 2;


db_connect();
$votes = db_get_votes($min_votes);
$sum = 0;
foreach ($votes as $key => $val) {
  $votes[$key] = array($sum, $val+$sum);
  $sum += $val;
}

function op_find(&$votes, $rand) {
  foreach ($votes as $op_nick => $val) {
    if ( ($val[0] < $rand) && ($rand <= $val[1]) )
      return $op_nick;
  }
  return false;
}


// нефиг выбирать больше опов чем есть
$num_of_ops = min ($num_of_ops, count($votes));

$ops = array();

while (count($ops) < $num_of_ops) {
  $op_name = op_find($votes, rand(1,$sum));
  if (! isset($ops[$op_name]) )
    $ops[$op_name] = array ('class' => 3);
}

$db_ops=db_get_ops();

// $ops список новых опов $db_ops список старых

$changed = array();
foreach ($db_ops as $nick => $class) {
  if (isset($ops[$nick])) { //снова ОП
    // удаляем тех, кто совпадает
    unset($ops[$nick]);
  } else { // юзер больше не ОП
    $changed[$nick] = array ('class' => 2);
  }
}
// теперь в $ops остались те, кто только что стал опом
$changed = array_merge($changed, $ops);
// массив user, new_class

$myFile = "/tmp/ops_changed.txt";
$fh = fopen($myFile, 'w') or die("can't open file");
foreach ($changed as $nick => $op) {
    fwrite($fh, $nick."\n");
}
fclose($fh);
system("chmod 777 $myFile");

db_set_ops($changed);

putenv("HOME=/etc/verlihub/");
putenv("LC_ALL=ru_RU.utf8");
$tmp=my_exec("/usr/local/bin/microdc2", $input='say пыщ');

echo $tmp['stderr'];

function my_exec($cmd, $input='')
{$proc=proc_open($cmd, array(0=>array('pipe', 'r'), 1=>array('pipe', 'w'), 2=>array('pipe', 'w')), $pipes);
  sleep(1);
  fwrite($pipes[0], $input);
  sleep(1);
  fclose($pipes[0]);
  $stdout=stream_get_contents($pipes[1]);fclose($pipes[1]);
  $stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);
  $rtn=proc_close($proc);
  return array('stdout'=>$stdout,
	       'stderr'=>$stderr,
	       'return'=>$rtn
	       );
}

function db_connect() {
  /* Переменные для соединения с базой данных */
  $hostname = "localhost";
  $username = "verliuser";
  $password = "verlipass";
  $dbName = "verlihub";
  
  /* создать соединение */
  mysql_connect($hostname,$username,$password) OR DIE("Не могу создать соединение "); 
  /* выбрать базу данных. Если произойдет ошибка - вывести ее */
  mysql_select_db($dbName) or die(mysql_error());
}

function db_get_votes($min_votes) {
  $query = "select lower(op_nick) as op_nick, count(*) as count from votes INNER JOIN reglist ON reglist.nick=votes.op_nick".
    " WHERE class IN (2,3) GROUP BY op_nick HAVING count > ".intval($min_votes);
  /* Выполнить запрос. Если произойдет ошибка - вывести ее. */
  $res = mysql_query($query) or die(mysql_error());
  $out = array();
  while ($row=mysql_fetch_assoc($res)) {
    $out[$row['op_nick']] = $row['count'];
  }
  return $out;
}

function db_get_ops() {
  $query = "select lower(nick) as nick, class from reglist WHERE class = 3;";
  /* Выполнить запрос. Если произойдет ошибка - вывести ее. */
  $res = mysql_query($query) or die(mysql_error());
  $out = array();
  while ($row=mysql_fetch_assoc($res)) {
    $out[$row['nick']] = $row['class'];
  }
  return $out;
}

function db_set_ops($ops) {
  foreach ($ops as $nick => $op) {
    $query = "UPDATE reglist SET `class`='".intval($op['class'])."' WHERE lower(nick)='".mysql_escape_string($nick)."' LIMIT 1";
    mysql_query($query) or print(mysql_error());
  }
  return;
}
